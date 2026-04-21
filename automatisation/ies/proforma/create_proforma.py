"""Génère un proforma pour un BL sur le portail IES AGL Group."""

import json
import os
import re
import smtplib
import subprocess
import sys
import time
from datetime import datetime
from email.message import EmailMessage
from pathlib import Path
from urllib import error, request

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.common.exceptions import NoSuchElementException, StaleElementReferenceException
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
DOWNLOAD_DIR = Path(os.environ.get("DOWNLOAD_DIR", str(RESULTS_DIR / "downloads"))).expanduser()
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


def load_properties() -> dict[str, str]:
    properties = {}
    app_props = Path(__file__).resolve().parents[3] / "src" / "main" / "resources" / "application.properties"
    if not app_props.exists():
        return properties
    for line in app_props.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        properties[key.strip()] = value.strip()
    return properties


def resolve_property(raw_value: str, env: dict[str, str]) -> str:
    match = re.fullmatch(r"\$\{([^:}]+)(?::([^}]*))?\}", raw_value)
    if not match:
        return raw_value
    env_name, default_value = match.groups()
    return env.get(env_name, default_value or "")


def send_not_found_email(compte: str, properties: dict[str, str]) -> None:
    smtp_host = properties.get("spring.mail.host", "smtp.gmail.com")
    smtp_port = int(properties.get("spring.mail.port", "587"))
    username = resolve_property(properties.get("spring.mail.username", ""), os.environ)
    password = resolve_property(properties.get("spring.mail.password", ""), os.environ)

    if not username or not password:
        raise RuntimeError("Configuration SMTP introuvable")

    message = EmailMessage()
    message["From"] = username
    message["To"] = "marc.bongoyeba@dakar-terminal.com"
    message["Subject"] = f"Ajout client a facturer IES - {compte}"
    message.set_content(
        "Bonjour,\n\n"
        f"Merci d'ajouter le client a facturer {compte} au compte generique IES.\n"
        "Statut: not found\n"
    )

    with smtplib.SMTP(smtp_host, smtp_port, timeout=30) as server:
        server.starttls()
        server.login(username, password)
        server.send_message(message)


def find_mysql_connector_jar() -> str | None:
    m2_repo = Path.home() / ".m2" / "repository" / "com" / "mysql" / "mysql-connector-j"
    if not m2_repo.exists():
        return None
    jars = sorted(m2_repo.glob("*/mysql-connector-j-*.jar"), reverse=True)
    return str(jars[0]) if jars else None


def insert_not_found_in_db(compte: str) -> None:
    mysql_jar = find_mysql_connector_jar()
    if not mysql_jar:
        raise RuntimeError("Driver MySQL introuvable dans ~/.m2")

    helper_java = Path(__file__).parent / "UpdateIesAccountFallback.java"
    app_props = Path(__file__).resolve().parents[3] / "src" / "main" / "resources" / "application.properties"
    command = [
        "java",
        "--class-path",
        mysql_jar,
        str(helper_java),
        compte,
        "not found",
        str(app_props),
    ]
    completed = subprocess.run(command, capture_output=True, text=True, timeout=30)
    if completed.returncode != 0:
        error_output = (completed.stderr or completed.stdout).strip()
        raise RuntimeError(error_output or "Insertion SQL impossible")


def notify_not_found_via_app(compte: str, properties: dict[str, str]) -> bool:
    base_url = resolve_property(properties.get("app.base-url", "http://localhost:8080"), os.environ).rstrip("/")
    token = resolve_property(
        properties.get("app.automation.internal-token", "dt-app-internal-automation-token"),
        os.environ,
    )
    payload = json.dumps({"compte": compte}).encode("utf-8")
    req = request.Request(
        f"{base_url}/api/ies/accounts/not-found",
        data=payload,
        headers={
            "Content-Type": "application/json",
            "X-Automation-Token": token,
        },
        method="POST",
    )
    try:
        with request.urlopen(req, timeout=10) as response:
            return 200 <= response.status < 300
    except error.URLError:
        return False


def handle_client_not_found(compte: str) -> None:
    properties = load_properties()
    app_notified = notify_not_found_via_app(compte, properties)
    if app_notified:
        print(f"✓ Notification envoyée et table update_ies_accounts mise à jour pour {compte}")
        return

    db_inserted = False
    try:
        insert_not_found_in_db(compte)
        db_inserted = True
        print(f"✓ Insertion effectuée dans update_ies_accounts pour {compte} avec statut not found")
    except Exception as db_error:
        print(f"✗ Impossible d'insérer {compte} dans update_ies_accounts : {db_error}")

    try:
        send_not_found_email(compte, properties)
        print(
            f"✓ Email de rajout envoyé pour {compte}"
        )
    except Exception as mail_error:
        print(
            f"✗ Impossible d'envoyer la notification de client introuvable pour {compte} : {mail_error}"
        )
        return

    if db_inserted:
        print(f"✓ Mail envoyé et insertion SQL enregistrée pour {compte}")


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


def clear_download_dir(directory: Path) -> None:
    for old_file in directory.iterdir():
        if old_file.is_file():
            old_file.unlink(missing_ok=True)


def list_downloaded_files(directory: Path) -> list[Path]:
    return sorted(
        [
            file_path for file_path in directory.iterdir()
            if file_path.is_file() and file_path.suffix != ".crdownload" and not file_path.name.startswith(".")
        ]
    )


def wait_for_new_download(directory: Path, previous_files: set[str], timeout: int = 30) -> Path | None:
    deadline = time.time() + timeout
    while time.time() < deadline:
        current_files = list_downloaded_files(directory)
        for file_path in current_files:
            if file_path.name not in previous_files:
                return file_path
        time.sleep(1)
    return None


def download_proforma_pdf(driver, wait, invoices_url: str, screenshot_path: Path) -> bool:
    # Revenir sur la page des factures pour utiliser le même scénario de téléchargement.
    driver.get(invoices_url)
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    time.sleep(2)
    driver.save_screenshot(str(RESULTS_DIR / "step_invoices_page.png"))
    print(f"✓ Page des factures — URL : {driver.current_url}")
    print("  → capture : step_invoices_page.png")

    clear_download_dir(DOWNLOAD_DIR)

    candidates = driver.find_elements(By.XPATH,
        "//*[@title='Télécharger Proforma' or @title='Telecharger Proforma'"
        " or contains(@href,'GenerateProformaReport')"
        " or contains(@onclick,'GenerateProformaReport')]"
    )
    print(f"  → boutons téléchargement trouvés : {len(candidates)}")
    visible_indexes = []
    for index, candidate in enumerate(candidates, start=1):
        print(
            f"      tag={candidate.tag_name} title='{candidate.get_attribute('title')}' "
            f"href='{candidate.get_attribute('href')}' displayed={candidate.is_displayed()}"
        )
        if candidate.is_displayed():
            visible_indexes.append(index)

    if not visible_indexes:
        driver.save_screenshot(str(screenshot_path))
        print("✗ Bouton de téléchargement non trouvé")
        return False

    print(f"✓ Boutons visibles à traiter : {len(visible_indexes)}")

    downloaded_files = []
    failed_indexes = []

    for button_position in visible_indexes:
        current_candidates = driver.find_elements(By.XPATH,
            "//*[@title='Télécharger Proforma' or @title='Telecharger Proforma'"
            " or contains(@href,'GenerateProformaReport')"
            " or contains(@onclick,'GenerateProformaReport')]"
        )
        if len(current_candidates) < button_position:
            failed_indexes.append(button_position)
            print(f"✗ Bouton de téléchargement #{button_position} introuvable au moment du clic")
            continue

        download_btn = current_candidates[button_position - 1]
        if not download_btn.is_displayed():
            failed_indexes.append(button_position)
            print(f"✗ Bouton de téléchargement #{button_position} non visible au moment du clic")
            continue

        # Re-fetch pour éviter le StaleElementReferenceException avant la vérification
        fresh_candidates = driver.find_elements(By.XPATH,
            "//*[@title='Télécharger Proforma' or @title='Telecharger Proforma'"
            " or contains(@href,'GenerateProformaReport')"
            " or contains(@onclick,'GenerateProformaReport')]"
        )
        if len(fresh_candidates) < button_position:
            failed_indexes.append(button_position)
            print(f"✗ Bouton #{button_position} introuvable après re-fetch")
            continue
        download_btn = fresh_candidates[button_position - 1]

        print("  → download without label check")

        previous_files = {file_path.name for file_path in list_downloaded_files(DOWNLOAD_DIR)}
        handles_before = set(driver.window_handles)

        driver.execute_script("arguments[0].scrollIntoView({block:'center'});", download_btn)
        time.sleep(0.5)
        download_btn.click()
        print(f"✓ Clic sur Télécharger Proforma #{button_position}")
        time.sleep(2)

        new_handles = set(driver.window_handles) - handles_before
        for handle in new_handles:
            driver.switch_to.window(handle)
            driver.close()
        driver.switch_to.window(driver.window_handles[0])

        downloaded_file = wait_for_new_download(DOWNLOAD_DIR, previous_files, timeout=30)
        if downloaded_file:
            downloaded_files.append(downloaded_file.name)
            print(f"✓ Document téléchargé #{button_position} : {downloaded_file.name}")
            continue

        failed_indexes.append(button_position)
        print(f"✗ Téléchargement non détecté pour le bouton #{button_position} dans le délai imparti")

    driver.save_screenshot(str(screenshot_path))

    if downloaded_files and not failed_indexes:
        print(f"✓ Téléchargements terminés : {len(downloaded_files)} fichier(s)")
        return True

    if downloaded_files:
        print(
            f"✓ Téléchargements partiels : {len(downloaded_files)} réussi(s), "
            f"{len(failed_indexes)} échec(s)"
        )
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
            print(f"✗ BL {BL_NUMBER} : des factures existent déjà — génération annulée")
            print("→ tentative de téléchargement automatique du PDF existant")
            if download_proforma_pdf(driver, wait, invoices_url, SCREENSHOT):
                upload_script = Path(__file__).parent / "upload_to_b2.py"
                result = subprocess.run(
                    [sys.executable, str(upload_script)],
                    env={**os.environ, "BL_NUMBER": BL_NUMBER, "DATE": DATE},
                )
                sys.exit(result.returncode)
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
        except NoSuchElementException:
            try:
                sel.select_by_visible_text(CLIENT_FACTURE)
            except NoSuchElementException:
                handle_client_not_found(CLIENT_FACTURE)
                raise
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

        if download_proforma_pdf(driver, wait, invoices_url, SCREENSHOT):
            upload_script = Path(__file__).parent / "upload_to_b2.py"
            result = subprocess.run(
                [sys.executable, str(upload_script)],
                env={**os.environ, "BL_NUMBER": BL_NUMBER, "DATE": DATE},
            )
            sys.exit(result.returncode)
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
