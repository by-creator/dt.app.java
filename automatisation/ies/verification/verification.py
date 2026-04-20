"""Vérifie si un BL a des factures sur le portail IES AGL Group."""

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
INVOICES_URL = "https://ies.aglgroup.com/dkrp/Customer/BillOfLadingInvoices?blNumber={}"
EMAIL = "marcdamien04@gmail.com"
PASSWORD = "6W91PthfBCMfs3"
TIMEOUT = 15

RESULTS_DIR = Path(__file__).parent / "results"
RESULTS_DIR.mkdir(exist_ok=True)
SCREENSHOT = RESULTS_DIR / f"verification_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png"

BL_NUMBER = os.environ.get("BL_NUMBER", "").strip()

if not BL_NUMBER:
    print("✗ Erreur : paramètre BL_NUMBER manquant")
    sys.exit(1)


def main():
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1920,1080")

    driver = webdriver.Chrome(options=options)
    wait = WebDriverWait(driver, TIMEOUT)

    try:
        # --- Connexion ---
        print(f"Connexion à {LOGIN_URL}")
        driver.get(LOGIN_URL)

        email_field = wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, "input[type='email'], input[name*='email'], input[id*='email'], input[placeholder*='mail']")
        ))
        email_field.clear()
        email_field.send_keys(EMAIL)

        password_field = driver.find_element(By.CSS_SELECTOR, "input[type='password']")
        password_field.clear()
        password_field.send_keys(PASSWORD)

        driver.find_element(By.CSS_SELECTOR, "button[type='submit'], input[type='submit']").click()
        time.sleep(2)

        if "login" in driver.current_url.lower():
            driver.save_screenshot(str(SCREENSHOT))
            print("✗ Erreur : échec de la connexion")
            sys.exit(1)

        print(f"✓ Connecté")

        # --- Navigation directe vers les factures du BL ---
        url = INVOICES_URL.format(BL_NUMBER)
        print(f"Navigation vers {url}")
        driver.get(url)
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        time.sleep(2)

        driver.save_screenshot(str(SCREENSHOT))
        print(f"Capture d'écran : {SCREENSHOT}")

        # --- Vérification ---
        if "Il n'y a pas encore de factures" in driver.page_source:
            print(f"✓ BL {BL_NUMBER} : aucune facture disponible")
            sys.exit(0)
        else:
            print(f"✗ BL {BL_NUMBER} : des factures sont présentes ou état inconnu")
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
