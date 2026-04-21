"""Session IES partagée : login via requests (sans Chrome), cache de cookies."""

import json
import os
import time
from pathlib import Path

import requests
from bs4 import BeautifulSoup

LOGIN_URL = "https://ies.aglgroup.com/dkrp/Login"
EMAIL = "marcdamien04@gmail.com"
PASSWORD = "6W91PthfBCMfs3"

_CACHE_FILE = Path(os.environ.get("TMPDIR", "/tmp")) / "ies_session_cookies.json"
_SESSION_MAX_AGE = 3600  # secondes avant de forcer un re-login


def _load_cached_cookies() -> dict | None:
    try:
        if not _CACHE_FILE.exists():
            return None
        data = json.loads(_CACHE_FILE.read_text())
        if time.time() - data.get("ts", 0) > _SESSION_MAX_AGE:
            return None
        return data.get("cookies")
    except Exception:
        return None


def _save_cached_cookies(cookies: dict) -> None:
    try:
        _CACHE_FILE.write_text(json.dumps({"ts": time.time(), "cookies": cookies}))
    except Exception:
        pass


def _is_session_valid(session: requests.Session) -> bool:
    """Vérifie que les cookies en cache donnent toujours accès à une page protégée."""
    try:
        resp = session.get(
            "https://ies.aglgroup.com/DKRP/Customer/BillOfLadingInvoices?blNumber=TEST",
            timeout=10, allow_redirects=False
        )
        return resp.status_code == 200
    except Exception:
        return False


def _login_with_requests() -> requests.Session:
    """Login complet via requests, retourne la session authentifiée."""
    session = requests.Session()
    session.headers.update({
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36",
    })

    # Récupérer la page de login pour le token CSRF (ASP.NET)
    resp = session.get(LOGIN_URL, timeout=15)
    resp.raise_for_status()

    soup = BeautifulSoup(resp.text, "html.parser")
    token_input = soup.find("input", {"name": "__RequestVerificationToken"})
    token = token_input["value"] if token_input else ""

    # Détecter les noms des champs email/password
    email_field = soup.find("input", {"type": "email"})
    if not email_field:
        for inp in soup.find_all("input"):
            name = (inp.get("name") or "").lower()
            id_ = (inp.get("id") or "").lower()
            if "email" in name or "email" in id_ or "mail" in name or "mail" in id_:
                email_field = inp
                break
    password_field = soup.find("input", {"type": "password"})

    email_name = email_field["name"] if email_field and email_field.get("name") else "Email"
    password_name = password_field["name"] if password_field and password_field.get("name") else "Password"

    payload = {
        email_name: EMAIL,
        password_name: PASSWORD,
    }
    if token:
        payload["__RequestVerificationToken"] = token

    login_resp = session.post(LOGIN_URL, data=payload, timeout=15, allow_redirects=True)

    if "login" in login_resp.url.lower():
        raise RuntimeError("Login IES échoué (mauvais identifiants ou formulaire changé)")

    print("✓ Connecté via requests")
    return session


def get_session() -> requests.Session:
    """
    Retourne une session requests authentifiée.
    Utilise le cache si les cookies sont encore valides, sinon re-login.
    """
    cached = _load_cached_cookies()
    if cached:
        session = requests.Session()
        session.headers.update({
            "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36",
        })
        session.cookies.update(cached)
        if _is_session_valid(session):
            print("✓ Session IES restaurée depuis le cache")
            return session
        print("  → Cache expiré, re-login...")

    session = _login_with_requests()
    _save_cached_cookies(dict(session.cookies))
    return session


def get_session_from_selenium_cookies(cookies: dict) -> requests.Session:
    """Construit une session requests à partir des cookies récupérés par Selenium."""
    session = requests.Session()
    session.headers.update({
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36",
    })
    session.cookies.update(cookies)
    _save_cached_cookies(cookies)
    return session
