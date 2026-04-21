"""Télécharge le BAD (Bon à Délivrer / DN) pour un BL sur le portail IES AGL Group."""

import os
import re
import sys
import time
from datetime import datetime
from pathlib import Path

import requests
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

LOGIN_URL = "https://ies.aglgroup.com/dkrp/Login"
INVOICES_URL = "https://ies.aglgroup.com/DKRP/Customer/BillOfLadingInvoices?blNumber={}"
EMAIL = "marcdamien04@gmail.com"
PASSWORD = "6W91PthfBCMfs3"
TIMEOUT = 20

RESULTS_DIR = Path(__file__).parent / "results"
RESULTS_DIR.mkdir(exist_ok=True)
DOWNLOAD_DIR = Path(os.environ.get("DOWNLOAD_DIR", str(RESULTS_DIR / "downloads"))).expanduser()
DOWNLOAD_DIR.mkdir(exist_ok=True)
SCREENSHOT = RESULTS_DIR / f"bad_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png"

BL_NUMBER = os.environ.get("BL_NUMBER", "").strip()
DATE = os.environ.get("DATE", datetime.now().strftime("%d/%m/%Y")).strip()
HEADLESS = os.environ.get("HEADLESS", "true").strip().lower() != "false"

if not BL_NUMBER:
    print("✗ Erreur : paramètre BL_NUMBER manquant")
    sys.exit(1)


def build_driver() -> webdriver.Chrome:
    options = Options()
    if HEADLESS:
        options.add_argument("--headless=new")
        options.add_argument("--disable-gpu")
        options.add_argument("--disable-software-rasterizer")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--no-first-run")
    options.add_argument("--window-size=1920,1080")
    print(f"Mode : {'headless' if HEADLESS else 'navigateur visible'}")
    return webdriver.Chrome(options=options)


def login_and_get_dn_links(driver: webdriver.Chrome) -> tuple[list[str], dict]:
    """Login, navigate, expand accordion, collect DN links + cookies, then ready to quit."""
    wait = WebDriverWait(driver, TIMEOUT)

    print(f"Connexion à {LOGIN_URL}")
    driver.get(LOGIN_URL)

    email_field = wait.until(EC.presence_of_element_located(
        (By.CSS_SELECTOR, "input[type='email'], input[name*='email'], input[id*='email'], input[placeholder*='mail']")
    ))
    email_field.clear()
    email_field.send_keys(EMAIL)
    driver.find_element(By.CSS_SELECTOR, "input[type='password']").send_keys(PASSWORD)
    driver.find_element(By.CSS_SELECTOR, "button[type='submit'], input[type='submit']").click()
    time.sleep(2)

    if "login" in driver.current_url.lower():
        driver.save_screenshot(str(SCREENSHOT))
        print("✗ Erreur : échec de la connexion")
        sys.exit(1)
    print("✓ Connecté")

    invoices_url = INVOICES_URL.format(BL_NUMBER)
    driver.get(invoices_url)
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    time.sleep(2)
    driver.save_screenshot(str(RESULTS_DIR / "step_bad_invoices_page.png"))
    print(f"✓ Page des factures — URL : {driver.current_url}")

    # Trouver et cliquer sur la flèche déroulante de la ligne "Facture"
    expand_candidates = driver.find_elements(By.XPATH,
        "//tr[.//*[contains(text(),'Facture')] or contains(.,'Facture')]"
        "  //*[self::button or self::a or self::span or self::i]"
        "  [contains(@class,'collapse') or contains(@class,'expand') or contains(@class,'toggle')"
        "   or contains(@class,'chevron') or contains(@class,'arrow') or contains(@class,'caret')"
        "   or contains(@class,'bi-chevron') or contains(@class,'fa-chevron')"
        "   or contains(@class,'bi-caret') or contains(@class,'accordion')]"
        " | "
        "//tr[.//*[contains(text(),'Facture')] or contains(.,'Facture')]"
        "  //td[1]//*[self::button or self::a or self::span or self::i]"
        " | "
        "//*[contains(text(),'Facture') or .//*[contains(text(),'Facture')]]"
        "  [self::tr or contains(@class,'row') or contains(@class,'item')]"
        "  //*[contains(@class,'toggle') or contains(@class,'expand') or contains(@class,'accordion')"
        "      or contains(@class,'chevron') or contains(@class,'arrow')]"
    )
    print(f"  → éléments déroulants trouvés : {len(expand_candidates)}")
    for c in expand_candidates:
        print(f"      tag={c.tag_name} class='{c.get_attribute('class')}' displayed={c.is_displayed()}")

    if not expand_candidates:
        driver.save_screenshot(str(SCREENSHOT))
        print("✗ Impossible de trouver la flèche déroulante de la ligne Facture")
        sys.exit(1)

    expand_btn = expand_candidates[0]
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", expand_btn)
    time.sleep(0.3)
    driver.execute_script("arguments[0].click();", expand_btn)
    print(f"  → clic JS sur : tag={expand_btn.tag_name} class='{expand_btn.get_attribute('class')}'")
    print("✓ Clic sur la flèche déroulante")
    time.sleep(1.5)
    driver.save_screenshot(str(RESULTS_DIR / "step_bad_expanded.png"))

    # Collecter les liens DN
    dn_links = driver.find_elements(By.XPATH,
        "//a[contains(@href,'PrintDeliveryNoteWithtout2Fa')]"
    )
    print(f"  → liens DN trouvés : {len(dn_links)}")
    dn_urls = []
    for lnk in dn_links:
        href = lnk.get_attribute("href")
        if href:
            print(f"      href='{href}'")
            dn_urls.append(href)

    if not dn_urls:
        driver.save_screenshot(str(SCREENSHOT))
        print("✗ Aucun lien PrintDeliveryNoteWithtout2Fa trouvé")
        sys.exit(1)

    cookies = {c["name"]: c["value"] for c in driver.get_cookies()}
    return dn_urls, cookies


def download_with_requests(dn_urls: list[str], cookies: dict) -> list[Path]:
    session = requests.Session()
    session.cookies.update(cookies)
    session.headers.update({
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36",
        "Referer": INVOICES_URL.format(BL_NUMBER),
    })

    downloaded = []
    for idx, url in enumerate(dn_urls, start=1):
        print(f"  → téléchargement BAD #{idx} : {url}")
        try:
            resp = session.get(url, timeout=60, allow_redirects=True)
            resp.raise_for_status()

            filename = None
            cd = resp.headers.get("Content-Disposition", "")
            if cd:
                m = re.search(r'filename[^;=\n]*=(["\']?)([^"\';\n]+)\1', cd)
                if m:
                    filename = m.group(2).strip()
            if not filename:
                filename = f"bad_{BL_NUMBER}_{idx}.pdf"

            dest = DOWNLOAD_DIR / filename
            dest.write_bytes(resp.content)
            downloaded.append(dest)
            print(f"✓ BAD téléchargé #{idx} : {filename} ({len(resp.content)} bytes)")
        except Exception as e:
            print(f"✗ Erreur téléchargement #{idx} : {e}")

    return downloaded


def main():
    driver = build_driver()
    try:
        dn_urls, cookies = login_and_get_dn_links(driver)
    finally:
        driver.quit()

    downloaded = download_with_requests(dn_urls, cookies)
    if downloaded:
        print(f"✓ {len(downloaded)} BAD téléchargé(s)")
        sys.exit(0)
    else:
        print("✗ Aucun téléchargement n'a abouti")
        sys.exit(1)


if __name__ == "__main__":
    main()
