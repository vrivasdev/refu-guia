---
name: skill_generar_identidad_qr
version: "1.0.0"
category: "Identificación Física & Trazabilidad"
description: "Genera el identificador único UUID de emergencia y el payload encriptado del código QR para collares de campaña y carnet clínico."
author: "RefuGuía AI Core"
timeout_ms: 3000
parameters:
  type: object
  required:
    - pet_id
  properties:
    pet_id:
      type: integer
      description: "Identificador interno de la mascota en el sistema."
---

# 🏷️ Skill: Identidad Digital y Código QR de Campaña

## 🎯 Propósito
Garantizar la trazabilidad física ininterrumpida de cada animal rescatado mediante un collar impermeable con QR que enlaza su historial clínico y estado de búsqueda.

## 🔒 Payload de Seguridad
El QR contiene una firma digital para evitar falsificaciones en refugios de emergencia:
```json
{
  "system": "RefuGuia-Emergency",
  "uuid": "RG-2026-XXXXXX",
  "pet_id": 123,
  "sha256_sig": "hash-criptografico"
}
```
