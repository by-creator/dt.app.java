"""Génère une facture pour un BL sur le portail IES AGL Group."""

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
SCREENSHOT = RESULTS_DIR / f"facture_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png"

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
    print(f"Mode : {'headless' if HEADLESS else 'visible'}")
    return webdriver.Chrome(options=options)


def login_and_get_links(driver: webdriver.Chrome) -> tuple[list[str], dict]:
    """Login, navigate to invoice page, collect links + cookies, then quit Chrome."""
    wait = WebDriverWait(driver, TIMEOUT)

    print(f"Connexion {LOGIN_URL}")
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
        print("✗ Login failed")
        sys.exit(1)
    print("✓ Logged in")

    invoices_url = INVOICES_URL.format(BL_NUMBER)
    driver.get(invoices_url)
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    time.sleep(2)
    driver.save_screenshot(str(RESULTS_DIR / "step_factures_page.png"))
    print(f"✓ Page des factures — URL : {driver.current_url}")

    invoice_links = driver.find_elements(By.XPATH,
        "//a[contains(@href,'GenerateInvoiceReport') or contains(@href,'GenerateFactureReport')]"
    )
    print(f"  → liens facture trouvés : {len(invoice_links)}")
    invoice_urls = []
    for lnk in invoice_links:
        href = lnk.get_attribute("href")
        if href:
            print(f"      href='{href}'")
            invoice_urls.append(href)

    # Extraire les cookies avant de quitter Chrome
    selenium_cookies = {c["name"]: c["value"] for c in driver.get_cookies()}
    return invoice_urls, selenium_cookies


def download_with_requests(invoice_urls: list[str], cookies: dict) -> list[Path]:
    """Télécharge chaque facture via requests (pas via le navigateur)."""
    session = requests.Session()
    session.cookies.update(cookies)
    session.headers.update({
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36",
        "Referer": INVOICES_URL.format(BL_NUMBER),
    })

    downloaded = []
    for idx, url in enumerate(invoice_urls, start=1):
        print(f"  → téléchargement facture #{idx} : {url}")
        try:
            resp = session.get(url, timeout=60, allow_redirects=True)
            resp.raise_for_status()

            # Déterminer le nom du fichier
            filename = None
            cd = resp.headers.get("Content-Disposition", "")
            if cd:
                m = re.search(r'filename[^;=\n]*=(["\']?)([^"\';\n]+)\1', cd)
                if m:
                    filename = m.group(2).strip()
            if not filename:
                filename = f"facture_{BL_NUMBER}_{idx}.pdf"

            dest = DOWNLOAD_DIR / filename
            dest.write_bytes(resp.content)
            downloaded.append(dest)
            print(f"✓ Facture téléchargée #{idx} : {filename} ({len(resp.content)} bytes)")
        except Exception as e:
            print(f"✗ Erreur téléchargement #{idx} : {e}")

    return downloaded


def main():
    driver = build_driver()
    try:
        invoice_urls, cookies = login_and_get_links(driver)
    finally:
        driver.quit()  # libérer la mémoire Chrome dès que les données sont récupérées

    if not invoice_urls:
        print("✗ Aucun lien GenerateInvoiceReport trouvé")
        sys.exit(1)

    downloaded = download_with_requests(invoice_urls, cookies)
    if downloaded:
        print(f"✓ {len(downloaded)} facture(s) téléchargée(s)")
        sys.exit(0)
    else:
        print("✗ Aucune facture téléchargée")
        sys.exit(1)


if __name__ == "__main__":
    main()
