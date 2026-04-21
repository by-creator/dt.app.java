"""Connexion automatique au portail IES AGL Group."""

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

URL = "https://ies.aglgroup.com/dkrp/Login"
EMAIL = "marcdamien04@gmail.com"
PASSWORD = "6W91PthfBCMfs3"
TIMEOUT = 15

RESULTS_DIR = Path(__file__).parent / "results"
RESULTS_DIR.mkdir(exist_ok=True)
SCREENSHOT = RESULTS_DIR / f"login_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png"


def main():
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1920,1080")

    driver = webdriver.Chrome(options=options)
    wait = WebDriverWait(driver, TIMEOUT)

    try:
        print(f"Ouverture de {URL}")
        driver.get(URL)

        email_field = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "input[type='email'], input[name*='email'], input[id*='email'], input[placeholder*='mail']")))
        email_field.clear()
        email_field.send_keys(EMAIL)

        password_field = driver.find_element(By.CSS_SELECTOR, "input[type='password']")
        password_field.clear()
        password_field.send_keys(PASSWORD)

        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit'], input[type='submit']")
        submit_btn.click()

        time.sleep(2)

        driver.save_screenshot(str(SCREENSHOT))
        print(f"Capture d'écran : {SCREENSHOT}")

        current_url = driver.current_url
        page_source = driver.page_source.lower()

        if "login" in current_url.lower() and any(kw in page_source for kw in ["invalid", "incorrect", "erreur", "error", "wrong"]):
            print("✗ Échec de la connexion : identifiants invalides")
            sys.exit(1)

        print(f"✓ Connexion réussie — URL : {current_url}")
        sys.exit(0)

    except Exception as e:
        driver.save_screenshot(str(SCREENSHOT))
        print(f"✗ Erreur : {e}")
        sys.exit(1)

    finally:
        driver.quit()


if __name__ == "__main__":
    main()
