#!/usr/bin/env python3
import os
import re
from datetime import datetime
from pathlib import Path

import boto3
from botocore.exceptions import ClientError

BL_NUMBER = os.environ.get("BL_NUMBER")
if not BL_NUMBER:
    print("BL_NUMBER required")
    exit(1)

DATE = os.environ.get("DATE", datetime.now().strftime("%d/%m/%Y"))
B2_DATE_PATH = DATE.replace("/", "-")
B2_ENDPOINT = "https://s3.us-east-005.backblazeb2.com"
DOWNLOAD_DIR = Path(__file__).parent / "results" / "downloads"
FACTURE_PATH = f"Facturation/Facture/{B2_DATE_PATH}/{BL_NUMBER}"


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


props = load_properties()
access_key = resolve_property(props.get("spring.cloud.aws.credentials.access-key", ""), os.environ)
secret_key = resolve_property(props.get("spring.cloud.aws.credentials.secret-key", ""), os.environ)
bucket_name = props.get("app.storage.b2.bucket-name", "dt-app")

if not access_key or not secret_key:
    print("✗ Credentials B2 introuvables dans application.properties")
    exit(1)

s3 = boto3.client(
    "s3",
    endpoint_url=B2_ENDPOINT,
    aws_access_key_id=access_key,
    aws_secret_access_key=secret_key,
)

uploaded = 0
for file_path in sorted(DOWNLOAD_DIR.glob("*")):
    if not file_path.is_file():
        continue
    b2_key = f"{FACTURE_PATH}/{file_path.name}"
    try:
        s3.upload_file(str(file_path), bucket_name, b2_key)
        print(f"✓ Uploaded {file_path.name} → {b2_key}")
        uploaded += 1
    except ClientError as e:
        print(f"✗ Upload failed for {file_path.name} : {e}")
        exit(1)

if uploaded == 0:
    print("✗ Aucun fichier à uploader dans results/downloads")
    exit(1)
