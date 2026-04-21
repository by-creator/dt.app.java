"""Génère une facture pour un BL sur le portail IES AGL Group."""

import os
import re
import sys
from datetime import datetime
from pathlib import Path

from bs4 import BeautifulSoup

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from ies_session import get_session

INVOICES_URL = "https://ies.aglgroup.com/DKRP/Customer/BillOfLadingInvoices?blNumber={}"

RESULTS_DIR = Path(__file__).parent / "results"
RESULTS_DIR.mkdir(exist_ok=True)
DOWNLOAD_DIR = Path(os.environ.get("DOWNLOAD_DIR", str(RESULTS_DIR / "downloads"))).expanduser()
DOWNLOAD_DIR.mkdir(exist_ok=True)

BL_NUMBER = os.environ.get("BL_NUMBER", "").strip()
DATE = os.environ.get("DATE", datetime.now().strftime("%d/%m/%Y")).strip()

if not BL_NUMBER:
    print("✗ Erreur : paramètre BL_NUMBER manquant")
    sys.exit(1)


def find_invoice_links(session, bl_number: str) -> list[str]:
    url = INVOICES_URL.format(bl_number)
    resp = session.get(url, timeout=20)
    resp.raise_for_status()
    print(f"✓ Page des factures — status {resp.status_code}")

    soup = BeautifulSoup(resp.text, "html.parser")
    links = soup.find_all("a", href=re.compile(r"GenerateInvoiceReport|GenerateFactureReport"))
    urls = [a["href"] for a in links if a.get("href")]
    # Rendre absolus les liens relatifs
    base = "https://ies.aglgroup.com"
    urls = [u if u.startswith("http") else base + u for u in urls]
    print(f"  → liens facture trouvés : {len(urls)}")
    for u in urls:
        print(f"      href='{u}'")
    return urls


def download_files(session, invoice_urls: list[str]) -> list[Path]:
    downloaded = []
    for idx, url in enumerate(invoice_urls, start=1):
        print(f"  → téléchargement facture #{idx} : {url}")
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
                filename = f"facture_{BL_NUMBER}_{idx}.pdf"

            dest = DOWNLOAD_DIR / filename
            dest.write_bytes(resp.content)
            downloaded.append(dest)
            print(f"✓ Facture téléchargée #{idx} : {filename} ({len(resp.content)} bytes)")
        except Exception as e:
            print(f"✗ Erreur téléchargement #{idx} : {e}")
    return downloaded


def main():
    session = get_session()

    invoice_urls = find_invoice_links(session, BL_NUMBER)
    if not invoice_urls:
        print("✗ Aucun lien GenerateInvoiceReport trouvé")
        sys.exit(1)

    downloaded = download_files(session, invoice_urls)
    if downloaded:
        print(f"✓ {len(downloaded)} facture(s) téléchargée(s)")
        sys.exit(0)
    else:
        print("✗ Aucune facture téléchargée")
        sys.exit(1)


if __name__ == "__main__":
    main()
