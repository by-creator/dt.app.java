"""Génère un proforma pour un BL sur le portail IES AGL Group."""

import os
import re
import sys
import time
from datetime import datetime
from pathlib import Path

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait, Select

LOGIN_URL = "https://ies.aglgroup.com/dkrp/Login"
INVOICES_URL = "https://ies.aglgroup.com/DKRP/Customer/BillOfLadingInvoices?blNumber={}"
PENDING_URL = "https://ies.aglgroup.com/DKRP/Customer/BillOfLadingPendingInvoicing?blId={}&blNumber={}"
EMAIL = "marcdamien04@gmail.com"
PASSWORD = "6W91PthfBCMfs3"
TIMEOUT = 20

RESULTS_DIR = Path(__file__).parent / "results"
RESULTS_DIR.mkdir(exist_ok=True)
DOWNLOAD_DIR = RESULTS_DIR / "downloads"
DOWNLOAD_DIR.mkdir(exist_ok=True)
SCREENSHOT = RESULTS_DIR / f"proforma_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png"

BL_NUMBER = os.environ.get("BL_NUMBER", "").strip()
DATE = os.environ.get("DATE", "").strip()
CLIENT_FACTURE = os.environ.get("CLIENT_FACTURE", "").strip()
HEADLESS = os.environ.get("HEADLESS", "true").strip().lower() != "false"

for val, name in [(BL_NUMBER, "BL_NUMBER"), (DATE, "DATE"), (CLIENT_FACTURE, "CLIENT_FACTURE")]:
    if not val:
        print(f"✗ Erreur : paramètre {name} manquant")
        sys.exit(1)


def wait_for_download(directory: Path, timeout: int = 30) -> bool:
    deadline = time.time() + timeout
    while time.time() < deadline:
        files = [f for f in directory.iterdir()
                 if f.is_file() and not f.suffix == ".crdownload" and not f.name.startswith(".")]
        if files:
            return True
        time.sleep(1)
    return False


def to_date_input(date_str: str) -> str:
    """Convertit JJ/MM/AAAA en AAAA-MM-JJ pour input[type='date']."""
    parts = date_str.split("/")
    if len(parts) == 3:
        return f"{parts[2]}-{parts[1]}-{parts[0]}"
    return date_str


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
    # Activer les téléchargements en mode headless via CDP
    driver.execute_cdp_cmd("Browser.setDownloadBehavior", {
        "behavior": "allow",
        "downloadPath": str(DOWNLOAD_DIR.absolute()),
    })
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
        driver.find_element(By.CSS_SELECTOR, "input[type='password']").send_keys(PASSWORD)
        driver.find_element(By.CSS_SELECTOR, "button[type='submit'], input[type='submit']").click()
        time.sleep(2)

        if "login" in driver.current_url.lower():
            driver.save_screenshot(str(SCREENSHOT))
            print("✗ Erreur : échec de la connexion")
            sys.exit(1)
        print("✓ Connecté")

        # --- Vérification : pas encore de factures (appel logique de ies/verification) ---
        invoices_url = INVOICES_URL.format(BL_NUMBER)
        driver.get(invoices_url)
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        time.sleep(2)

        if "Il n'y a pas encore de factures" not in driver.page_source:
            driver.save_screenshot(str(SCREENSHOT))
            print(f"✗ BL {BL_NUMBER} : des factures existent déjà — génération annulée")
            sys.exit(1)
        print(f"✓ BL {BL_NUMBER} : aucune facture — génération du proforma possible")

        # Extraire le blId depuis le lien de l'onglet "Eléments à Facturer"
        el_tab = driver.find_element(By.XPATH,
            "//a[contains(@href,'BillOfLadingPendingInvoicing')] | "
            "//button[contains(@onclick,'BillOfLadingPendingInvoicing')]"
        )
        tab_href = el_tab.get_attribute("href") or el_tab.get_attribute("onclick") or ""
        bl_id_match = re.search(r"blId=(\d+)", tab_href)
        if not bl_id_match:
            print("✗ Impossible d'extraire le blId depuis l'onglet Eléments à Facturer")
            sys.exit(1)
        bl_id = bl_id_match.group(1)
        print(f"✓ blId extrait : {bl_id}")

        # --- Navigation vers les éléments à facturer ---
        pending_url = PENDING_URL.format(bl_id, BL_NUMBER)
        print(f"Navigation vers {pending_url}")
        driver.get(pending_url)
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        time.sleep(2)

        # Cocher la case d'en-tête (sélectionne tous les éléments)
        header_cb = wait.until(EC.element_to_be_clickable(
            (By.CSS_SELECTOR, "thead input[type='checkbox'], th input[type='checkbox']")
        ))
        if not header_cb.is_selected():
            header_cb.click()
        time.sleep(1)
        print("✓ Case d'en-tête cochée")

        # Cliquer sur "Générer proforma" — ciblé sur button/a uniquement
        generer_proforma_btn = wait.until(EC.element_to_be_clickable(
            (By.XPATH,
             "//*[self::button or self::a or self::input]"
             "[contains(normalize-space(.),'Générer proforma') or @value='Générer proforma']")
        ))
        driver.execute_script("arguments[0].scrollIntoView({block:'center'});", generer_proforma_btn)
        time.sleep(0.5)
        generer_proforma_btn.click()
        print("✓ Clic sur Générer proforma")

        # Attendre l'ouverture du modal (polling sans wait.until)
        for _ in range(1, 8):
            time.sleep(1)
            candidates = driver.find_elements(By.XPATH,
                "//dialog | //*[@role='dialog'] | //*[contains(@class,'modal')]"
                "[.//*[contains(text(),'Génération de proforma')]]")
            if candidates:
                print("✓ Modal ouvert")
                break
        else:
            driver.save_screenshot(str(SCREENSHOT))
            print("✗ Modal non détecté après 7s")
            sys.exit(1)

        # Laisser le modal finir de se rendre
        time.sleep(1)
        driver.save_screenshot(str(RESULTS_DIR / "step_modal_open.png"))

        # --- Inspection : lister tous les inputs/selects visibles sur la page ---
        all_inputs = driver.find_elements(By.TAG_NAME, "input")
        print(f"  → tous les inputs visibles ({len(all_inputs)}) :")
        for inp in all_inputs:
            if inp.is_displayed():
                print(f"      type='{inp.get_attribute('type')}' id='{inp.get_attribute('id')}' name='{inp.get_attribute('name')}' placeholder='{inp.get_attribute('placeholder')}'")
        all_selects = driver.find_elements(By.TAG_NAME, "select")
        print(f"  → tous les selects visibles ({len(all_selects)}) :")
        for s in all_selects:
            if s.is_displayed():
                opts = [o.get_attribute('value') + ':' + o.text for o in Select(s).options[:6]]
                print(f"      id='{s.get_attribute('id')}' name='{s.get_attribute('name')}' options={opts}")

        # --- Remplir le formulaire du modal ---

        # Date d'enlèvement : chercher le premier input visible (type=date ou text avec placeholder date)
        date_input = None
        for inp in driver.find_elements(By.TAG_NAME, "input"):
            if inp.is_displayed():
                t = inp.get_attribute("type") or ""
                ph = (inp.get_attribute("placeholder") or "").lower()
                if t == "date" or "mm" in ph or "aaaa" in ph or "yyyy" in ph or "date" in ph:
                    date_input = inp
                    print(f"  → input date trouvé : type='{t}' placeholder='{inp.get_attribute('placeholder')}'")
                    break
        if not date_input:
            print("✗ Aucun input date trouvé")
            sys.exit(1)

        inp_type = date_input.get_attribute("type") or "text"
        parts = DATE.split("/")  # "20/04/2026" → ["20","04","2026"]
        if inp_type == "date":
            # Chrome type=date stocke en YYYY-MM-DD, injection JS la plus fiable
            iso_date = f"{parts[2]}-{parts[1]}-{parts[0]}"
            driver.execute_script(
                "arguments[0].value = arguments[1];"
                "arguments[0].dispatchEvent(new Event('input',  {bubbles:true}));"
                "arguments[0].dispatchEvent(new Event('change', {bubbles:true}));",
                date_input, iso_date
            )
        else:
            # Champ texte : saisir au format affiché JJ/MM/AAAA
            date_input.click()
            date_input.clear()
            date_input.send_keys(DATE)
        time.sleep(0.3)
        driver.save_screenshot(str(RESULTS_DIR / "step_date_filled.png"))
        print(f"✓ Date saisie : {DATE}")

        # Client facturé : premier select visible
        client_sel_el = None
        for s in driver.find_elements(By.TAG_NAME, "select"):
            if s.is_displayed():
                client_sel_el = s
                break
        if not client_sel_el:
            print("✗ Aucun select trouvé")
            sys.exit(1)

        sel = Select(client_sel_el)
        try:
            sel.select_by_value(CLIENT_FACTURE)
        except Exception:
            sel.select_by_visible_text(CLIENT_FACTURE)
        time.sleep(0.3)
        driver.save_screenshot(str(RESULTS_DIR / "step_client_filled.png"))
        print(f"✓ Client facturé sélectionné : {CLIENT_FACTURE}")

        # Cliquer sur "Générer" (bouton visible du modal)
        generer_btn = None
        for btn in driver.find_elements(By.TAG_NAME, "button"):
            if btn.is_displayed() and btn.text.strip() in ("Générer", "Generer"):
                generer_btn = btn
                break
        if not generer_btn:
            print("✗ Bouton Générer non trouvé")
            sys.exit(1)

        driver.save_screenshot(str(RESULTS_DIR / "step_before_generer.png"))
        generer_btn.click()
        time.sleep(3)
        print("✓ Proforma généré")

        # --- Retour sur la page des factures ---
        driver.get(invoices_url)
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        time.sleep(2)
        driver.save_screenshot(str(RESULTS_DIR / "step_invoices_page.png"))
        print(f"✓ Page des factures — URL : {driver.current_url}")
        print(f"  → capture : step_invoices_page.png")

        # Vider le dossier de téléchargement avant de cliquer
        for old_file in DOWNLOAD_DIR.iterdir():
            old_file.unlink(missing_ok=True)

        # Chercher le bouton de téléchargement du proforma
        download_btn = None
        candidates = driver.find_elements(By.XPATH,
            "//*[@title='Télécharger Proforma' or @title='Telecharger Proforma'"
            " or contains(@href,'GenerateProformaReport')"
            " or contains(@onclick,'GenerateProformaReport')]"
        )
        print(f"  → boutons téléchargement trouvés : {len(candidates)}")
        for c in candidates:
            print(f"      tag={c.tag_name} title='{c.get_attribute('title')}' href='{c.get_attribute('href')}' displayed={c.is_displayed()}")
            if c.is_displayed():
                download_btn = c
                break

        if not download_btn:
            driver.save_screenshot(str(SCREENSHOT))
            print("✗ Bouton de téléchargement non trouvé")
            sys.exit(1)

        # Conserver les handles d'onglets avant le clic
        handles_before = set(driver.window_handles)

        download_btn.click()
        print("✓ Clic sur Télécharger Proforma")
        time.sleep(2)

        # Si le clic a ouvert un nouvel onglet, le fermer (le téléchargement CDP suffit)
        new_handles = set(driver.window_handles) - handles_before
        for h in new_handles:
            driver.switch_to.window(h)
            driver.close()
        driver.switch_to.window(driver.window_handles[0])

        driver.save_screenshot(str(SCREENSHOT))

        # Attendre la fin du téléchargement
        if wait_for_download(DOWNLOAD_DIR, timeout=30):
            downloaded = [f.name for f in DOWNLOAD_DIR.iterdir() if f.is_file()]
            print(f"✓ Document téléchargé : {downloaded[0]}")
            sys.exit(0)
        else:
            print("✗ Téléchargement non détecté dans le délai imparti")
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
