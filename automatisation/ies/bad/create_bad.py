"""Télécharge le BAD (Bon à Délivrer / DN) pour un BL sur le portail IES AGL Group."""

import os
import sys
import time
from datetime import datetime
from pathlib import Path

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


def clear_download_dir(directory: Path) -> None:
    for old_file in directory.iterdir():
        if old_file.is_file():
            old_file.unlink(missing_ok=True)


def list_downloaded_files(directory: Path) -> list[Path]:
    return sorted([
        f for f in directory.iterdir()
        if f.is_file() and f.suffix != ".crdownload" and not f.name.startswith(".")
    ])


def wait_for_new_download(directory: Path, previous_files: set[str], timeout: int = 30) -> Path | None:
    deadline = time.time() + timeout
    while time.time() < deadline:
        for f in list_downloaded_files(directory):
            if f.name not in previous_files:
                return f
        time.sleep(1)
    return None


def download_bad(driver, wait, invoices_url: str) -> bool:
    driver.get(invoices_url)
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    time.sleep(2)
    driver.save_screenshot(str(RESULTS_DIR / "step_bad_invoices_page.png"))
    print(f"✓ Page des factures — URL : {driver.current_url}")

    # --- Étape 1 : trouver et cliquer sur la flèche déroulante de la ligne "Facture" ---
    expand_candidates = driver.find_elements(By.XPATH,
        # Bouton/lien à l'intérieur d'une ligne contenant le texte "Facture"
        "//tr[.//*[contains(text(),'Facture')] or contains(.,'Facture')]"
        "  //*[self::button or self::a or self::span or self::i]"
        "  [contains(@class,'collapse') or contains(@class,'expand') or contains(@class,'toggle')"
        "   or contains(@class,'chevron') or contains(@class,'arrow') or contains(@class,'caret')"
        "   or contains(@class,'bi-chevron') or contains(@class,'fa-chevron')"
        "   or contains(@class,'bi-caret') or contains(@class,'accordion')]"
        " | "
        # Ou premier élément cliquable (icône circulaire) au début de la ligne Facture
        "//tr[.//*[contains(text(),'Facture')] or contains(.,'Facture')]"
        "  //td[1]//*[self::button or self::a or self::span or self::i]"
        " | "
        # Fallback : div/span avec classe d'accordéon dans la ligne
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
        return False

    # Utiliser le premier candidat trouvé — clic JS pour contourner is_displayed()=False
    expand_btn = expand_candidates[0]
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", expand_btn)
    time.sleep(0.3)
    driver.execute_script("arguments[0].click();", expand_btn)
    print(f"  → clic JS sur : tag={expand_btn.tag_name} class='{expand_btn.get_attribute('class')}'")


    print("✓ Clic sur la flèche déroulante")
    time.sleep(1.5)
    driver.save_screenshot(str(RESULTS_DIR / "step_bad_expanded.png"))

    # --- Étape 2 : collecter les URLs PrintDeliveryNoteWithtout2Fa ---
    dn_links = driver.find_elements(By.XPATH,
        "//a[contains(@href,'PrintDeliveryNoteWithtout2Fa')]"
    )
    print(f"  → liens DN trouvés : {len(dn_links)}")
    for lnk in dn_links:
        print(f"      href='{lnk.get_attribute('href')}'")

    dn_urls = [lnk.get_attribute("href") for lnk in dn_links if lnk.get_attribute("href")]
    if not dn_urls:
        driver.save_screenshot(str(SCREENSHOT))
        print("✗ Aucun lien PrintDeliveryNoteWithtout2Fa trouvé")
        return False

    clear_download_dir(DOWNLOAD_DIR)
    downloaded_files = []

    for idx, url in enumerate(dn_urls, start=1):
        previous_files = {f.name for f in list_downloaded_files(DOWNLOAD_DIR)}
        print(f"  → navigation DN #{idx} : {url}")
        driver.get(url)
        time.sleep(2)

        new_file = wait_for_new_download(DOWNLOAD_DIR, previous_files, timeout=30)
        if new_file:
            downloaded_files.append(new_file)
            print(f"✓ BAD téléchargé #{idx} : {new_file.name}")
        else:
            print(f"✗ Téléchargement non détecté pour DN #{idx}")

        # Revenir sur la page des factures pour le prochain lien éventuel
        if idx < len(dn_urls):
            driver.get(invoices_url)
            wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
            time.sleep(1.5)

    driver.save_screenshot(str(SCREENSHOT))

    if downloaded_files:
        print(f"✓ {len(downloaded_files)} BAD téléchargé(s)")
        return True

    print("✗ Aucun téléchargement n'a abouti")
    return False


def main():
    options = Options()
    if HEADLESS:
        options.add_argument("--headless=new")
        options.add_argument("--disable-gpu")
        options.add_argument("--disable-software-rasterizer")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--no-first-run")
    options.add_argument("--disable-popup-blocking")
    options.add_argument("--window-size=1920,1080")
    print(f"Mode : {'headless' if HEADLESS else 'navigateur visible'}")

    driver = webdriver.Chrome(options=options)
    driver.execute_cdp_cmd("Browser.setDownloadBehavior", {
        "behavior": "allow",
        "downloadPath": str(DOWNLOAD_DIR.absolute()),
    })
    wait = WebDriverWait(driver, TIMEOUT)

    try:
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
        if download_bad(driver, wait, invoices_url):
            print("✓ Success")
            sys.exit(0)
        sys.exit(1)

    except Exception as e:
        try:
            driver.save_screenshot(str(SCREENSHOT))
        except Exception:
            pass
        print(f"✗ Erreur : {e}")
        sys.exit(1)

    finally:
        driver.quit()


if __name__ == "__main__":
    main()
