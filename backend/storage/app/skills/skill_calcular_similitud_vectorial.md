---
name: skill_calcular_similitud_vectorial
version: "1.0.0"
category: "Recuperación Vectorial (RAG & Embeddings)"
description: "Calcula la similitud semántica y del coseno en ChromaDB ponderada con la distancia geoespacial (Haversine) entre reportes perdidos y encontrados."
author: "RefuGuía AI Core"
timeout_ms: 8000
parameters:
  type: object
  required:
    - lost_pet_id
    - found_pet_id
  properties:
    lost_pet_id:
      type: integer
      description: "ID de la mascota reportada como perdida por la familia."
    found_pet_id:
      type: integer
      description: "ID de la mascota rescatada en refugio."
---

# ⚡ Skill: Cálculo de Similitud Vectorial & Geoespacial

## 🎯 Propósito
Ejecutar la comparación híbrida entre representaciones vectoriales densas (embeddings) y variables físicas/geográficas para emitir una probabilidad de coincidencia confiable.

## ⚖️ Fórmula de Ponderación
* **Puntaje Total** = `(Similitud Coseno Vectorial * 0.45) + (Coincidencia Rasgos Físicos * 0.35) + (Proximidad Geográfica * 0.20)`

## 🚦 Umbrales de Negocio
* **Score ≥ 80% (Verde):** Match de Alta Probabilidad. Dispara alerta automática y notificación prioritaria a la familia.
* **Score 50% - 79% (Amarillo):** Coincidencia Dudosa. Requiere revisión visual y triaje manual por un rescatista.
* **Score < 50% (Rojo):** Descarte automático.
