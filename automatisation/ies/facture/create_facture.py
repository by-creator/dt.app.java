"""Génère une facture pour un BL sur le portail IES AGL Group."""

import json
import os
import re
import sys
import time
from datetime import datetime
from pathlib import Path
import subprocess
from urllib import error, request

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.common.exceptions import NoSuchElementException, StaleElementReferenceException
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
    print(f"✗ Erreur : paramètre BL_NUMBER manquant")
    sys.exit(1)

def clear_download_dir(directory: Path) -> None:
    for old_file in directory.iterdir():
        if old_file.is_file():
            old_file.unlink(missing_ok=True)

def list_downloaded_files(directory: Path) -> list[Path]:
    return sorted([
        file_path for file_path in directory.iterdir()
        if file_path.is_file() and file_path.suffix != ".crdownload" and not file_path.name.startswith(".")
    ])

def wait_for_new_download(directory: Path, previous_files: set[str], timeout: int = 30) -> Path | None:
    deadline = time.time() + timeout
    while time.time() < deadline:
        current_files = list_downloaded_files(directory)
        for file_path in current_files:
            if file_path.name not in previous_files:
                return file_path
        time.sleep(1)
    return None



def download_facture_pdf(driver, wait, invoices_url: str, screenshot_path: Path) -> bool:
    driver.get(invoices_url)
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    time.sleep(2)
    driver.save_screenshot(str(RESULTS_DIR / "step_factures_page.png"))
    print(f"✓ Page des factures — URL : {driver.current_url}")

    clear_download_dir(DOWNLOAD_DIR)

    # Updated for Facture buttons
    candidates = driver.find_elements(By.XPATH,
        "//*[@title='Télécharger Facture' or @title='Telecharger Facture' or "
        "contains(@href,'GenerateFactureReport') or contains(@href,'GenerateInvoiceReport') or "
        "contains(@onclick,'GenerateFactureReport') or contains(@onclick,'GenerateInvoiceReport')]"
    )
    print(f"  → boutons téléchargement trouvés : {len(candidates)}")
    
    visible_indexes = [i+1 for i, c in enumerate(candidates) if c.is_displayed()]

    if not visible_indexes:
        driver.save_screenshot(str(screenshot_path))
        print("✗ Aucun bouton de téléchargement Facture trouvé")
        return False

    print(f"✓ Boutons visibles : {len(visible_indexes)}")

    downloaded_files = []
    previous_files = {f.name for f in list_downloaded_files(DOWNLOAD_DIR)}
    handles_before = set(driver.window_handles)

    for button_position in visible_indexes:
        current_candidates = driver.find_elements(By.XPATH,
            "//*[@title='Télécharger Facture' or @title='Telecharger Facture' or "
            "contains(@href,'GenerateFactureReport') or contains(@href,'GenerateInvoiceReport')]"
        )
        if len(current_candidates) < button_position:
            print(f"✗ Bouton #{button_position} introuvable")
            continue

        download_btn = current_candidates[button_position - 1]
        if not download_btn.is_displayed():
            continue

        # No label check as per user request

        # Download
        driver.execute_script("arguments[0].scrollIntoView({block:'center'});", download_btn)
        time.sleep(0.5)
        download_btn.click()
        print(f"✓ Clic Télécharger Facture #{button_position}")

        # Handle popup if any
        new_handles = set(driver.window_handles) - handles_before
        for handle in new_handles:
            driver.switch_to.window(handle)
            driver.close()
        driver.switch_to.window(driver.window_handles[0])

        new_file = wait_for_new_download(DOWNLOAD_DIR, previous_files)
        if new_file:
            downloaded_files.append(new_file)
            previous_files.add(new_file.name)
            print(f"✓ Facture téléchargée : {new_file.name}")
        time.sleep(1)

    driver.save_screenshot(str(screenshot_path))

    if downloaded_files:
        print(f"✓ {len(downloaded_files)} facture(s) téléchargée(s)")
        return True
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
    print(f"Mode : {'headless' if HEADLESS else 'visible'}")

    driver = webdriver.Chrome(options=options)
    driver.execute_cdp_cmd("Browser.setDownloadBehavior", {
        "behavior": "allow",
        "downloadPath": str(DOWNLOAD_DIR.absolute()),
    })
    wait = WebDriverWait(driver, TIMEOUT)

    try:
        # Login
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

        # Download factures
        invoices_url = INVOICES_URL.format(BL_NUMBER)
        if download_facture_pdf(driver, wait, invoices_url, SCREENSHOT):
            upload_script = Path(__file__).parent / "upload_to_b2.py"
            result = subprocess.run(
                [sys.executable, str(upload_script)],
                env={**os.environ, "BL_NUMBER": BL_NUMBER, "DATE": DATE},
            )
            if result.returncode == 0:
                print("✓ Success")
            sys.exit(result.returncode)
        else:
            print("✗ No factures found/downloaded")
            sys.exit(1)

    except Exception as e:
        driver.save_screenshot(str(SCREENSHOT))
        print(f"✗ Error: {e}")
        sys.exit(1)
    finally:
        driver.quit()

if __name__ == "__main__":
    main()

