"""Supprime une proforma pour un BL sur le portail IES AGL Group."""

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
SCREENSHOT = RESULTS_DIR / f"delete_proforma_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png"

BL_NUMBER = os.environ.get("BL_NUMBER", "").strip()
HEADLESS = os.environ.get("HEADLESS", "true").strip().lower() != "false"

if not BL_NUMBER:
    print("✗ Erreur : paramètre BL_NUMBER manquant")
    sys.exit(1)


def delete_proforma(driver, wait, invoices_url: str) -> bool:
    driver.get(invoices_url)
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    time.sleep(2)
    driver.save_screenshot(str(RESULTS_DIR / "step_delete_invoices_page.png"))
    print(f"✓ Page des factures — URL : {driver.current_url}")

    # Chercher le bouton corbeille (trash)
    candidates = driver.find_elements(By.XPATH,
        "//*[self::button or self::a]"
        "["
        "  .//*[contains(@class,'trash') or contains(@class,'delete') or contains(@class,'fa-trash')"
        "       or contains(@class,'bi-trash') or contains(@class,'icon-trash')]"
        "  or contains(@class,'trash') or contains(@class,'delete') or contains(@title,'upprimer')"
        "  or contains(@onclick,'upprimer') or contains(@onclick,'elete') or contains(@data-action,'delete')"
        "]"
    )
    print(f"  → boutons corbeille trouvés : {len(candidates)}")
    for c in candidates:
        print(f"      tag={c.tag_name} class='{c.get_attribute('class')}' "
              f"title='{c.get_attribute('title')}' displayed={c.is_displayed()}")

    visible = [c for c in candidates if c.is_displayed()]
    if not visible:
        driver.save_screenshot(str(SCREENSHOT))
        print("✗ Aucun bouton corbeille visible")
        return False

    delete_btn = visible[0]
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", delete_btn)
    time.sleep(0.5)
    delete_btn.click()
    print("✓ Clic sur le bouton corbeille")

    # Attendre l'ouverture du modal de confirmation
    for _ in range(1, 10):
        time.sleep(1)
        modals = driver.find_elements(By.XPATH,
            "//dialog | //*[@role='dialog'] | //*[contains(@class,'modal')]"
            "[.//*[contains(text(),'upprimer') or contains(text(),'onfirm')]]"
        )
        if modals:
            print("✓ Modal de confirmation ouvert")
            break
    else:
        driver.save_screenshot(str(SCREENSHOT))
        print("✗ Modal de confirmation non détecté après 9s")
        return False

    time.sleep(0.5)
    driver.save_screenshot(str(RESULTS_DIR / "step_delete_modal.png"))

    # Cliquer sur "Valider" dans le modal
    valider_btn = None
    for btn in driver.find_elements(By.TAG_NAME, "button"):
        if btn.is_displayed() and btn.text.strip() in ("Valider", "Confirmer", "Oui", "OK"):
            valider_btn = btn
            break
    if not valider_btn:
        # Fallback : bouton primaire visible dans le modal
        for btn in driver.find_elements(By.XPATH,
            "//*[@role='dialog']//button | //*[contains(@class,'modal')]//button"
        ):
            if btn.is_displayed() and btn.text.strip() not in ("Fermer", "Annuler", "Non", "×", ""):
                valider_btn = btn
                break

    if not valider_btn:
        driver.save_screenshot(str(SCREENSHOT))
        print("✗ Bouton Valider introuvable dans le modal")
        return False

    print(f"  → bouton confirmation : '{valider_btn.text.strip()}'")
    valider_btn.click()
    print("✓ Clic sur Valider — suppression confirmée")
    time.sleep(2)

    driver.save_screenshot(str(SCREENSHOT))
    return True


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
        if delete_proforma(driver, wait, invoices_url):
            print(f"✓ Proforma pour BL {BL_NUMBER} supprimée avec succès")
            sys.exit(0)
        else:
            print(f"✗ Échec de la suppression de la proforma pour BL {BL_NUMBER}")
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
