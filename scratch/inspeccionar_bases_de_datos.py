import urllib.request
import json
import subprocess
import sys

print("=" * 80)
print("🔍 AUDITORÍA EN VIVO: BD RELACIONAL (MySQL) & BD VECTORIAL (ChromaDB)")
print("=" * 80)

# 1. CONSULTA A BASE DE DATOS RELACIONAL (MySQL)
print("\n📦 1. REGISTROS EN BASE DE DATOS RELACIONAL (MySQL - Tabla 'pets'):")
print("-" * 80)
try:
    sql = 'SELECT id, uuid, name, species, breed, primary_color, status, location_address FROM pets ORDER BY id DESC LIMIT 5;'
    res = subprocess.run(
        ["docker", "compose", "exec", "-T", "database", "mysql", "-u", "refu_user", "-prefu_password_secret", "refuguia_db", "-e", sql],
        capture_output=True
    )
    print(res.stdout.decode('utf-8', errors='replace'))
except Exception as e:
    print(f"Error consultando MySQL: {e}")

# 2. AUDITORÍA CRIPTOGRÁFICA SHA-256 (clinical_records)
print("\n🔐 2. EXPEDIENTES CLÍNICOS E INMUTABILIDAD SHA-256 (Tabla 'clinical_records'):")
print("-" * 80)
try:
    sql2 = 'SELECT id, pet_id, veterinarian_name, critical_drug_administered, audit_hash, created_at FROM clinical_records ORDER BY id DESC LIMIT 3;'
    res2 = subprocess.run(
        ["docker", "compose", "exec", "-T", "database", "mysql", "-u", "refu_user", "-prefu_password_secret", "refuguia_db", "-e", sql2],
        capture_output=True
    )
    print(res2.stdout.decode('utf-8', errors='replace'))
except Exception as e:
    print(f"Error: {e}")

# 3. BASE DE DATOS VECTORIAL (ChromaDB v2)
print("\n🧠 3. BASE DE DATOS VECTORIAL (ChromaDB v2 - Colección 'pets_emergency_collection'):")
print("-" * 80)
try:
    collections_url = "http://localhost:8001/api/v2/tenants/default_tenant/databases/default_database/collections"
    req = urllib.request.Request(collections_url)
    with urllib.request.urlopen(req, timeout=3) as resp:
        collections = json.loads(resp.read().decode())
        print(f"Colecciones vectoriales activas: {len(collections)}")
        for col in collections:
            col_id = col.get('id')
            col_name = col.get('name')
            print(f"  • Colección: '{col_name}' (ID: {col_id})")

            # Obtener documentos indexados
            get_req = urllib.request.Request(
                f"{collections_url}/{col_id}/get",
                data=json.dumps({"include": ["documents", "metadatas"]}).encode(),
                headers={"Content-Type": "application/json"}
            )
            with urllib.request.urlopen(get_req, timeout=3) as get_resp:
                data = json.loads(get_resp.read().decode())
                ids = data.get("ids", [])
                docs = data.get("documents", [])
                metas = data.get("metadatas", [])
                print(f"    Total de Vectores Indexados: {len(ids)}")
                for i in range(len(ids)):
                    print(f"    [Vector #{ids[i]}]")
                    print(f"      - Texto Semántico: \"{docs[i]}\"")
                    print(f"      - Metadatos: {metas[i]}")
except Exception as e:
    print(f"Error consultando ChromaDB: {e}")

print("\n" + "=" * 80)
