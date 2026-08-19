---
name: skill_extraer_entidades_nlp
version: "1.0.0"
category: "Procesamiento de Lenguaje Natural (NLP)"
description: "Extrae entidades estructuradas (especie, raza, colores, tamaño, traumatismos y ubicación) a partir de descripciones ciudadanas o notas de voz transcritas en contexto de sismo."
author: "RefuGuía AI Core"
timeout_ms: 5000
parameters:
  type: object
  required:
    - raw_text
  properties:
    raw_text:
      type: string
      description: "Texto libre o transcripción del ciudadano reportando una mascota."
---

# 🧠 Skill: Extracción de Entidades NLP

## 🎯 Propósito
Procesar lenguaje natural no estructurado proveniente de reportes de emergencia post-sismo y convertirlo en un esquema normalizado JSON listo para indexación vectorial y base de datos relacional.

## 📋 Reglas de Extracción
1. **Normalización de Especie:** Mapear términos como `perrito, can, firulais` a `canine` y `gato, minino, felino` a `feline`.
2. **Detección de Traumatismos:** Identificar cojeras, sangrado, deshidratación, temblores por estrés o laceraciones.
3. **Extracción de Ubicación:** Aislar referencias geográficas (ej: Catia, La Guaira, El Hatillo).

## 📥 Esquema de Salida Esperado (JSON)
```json
{
  "species": "canine | feline | other",
  "breed": "string",
  "size": "small | medium | large",
  "primary_color": "string",
  "secondary_color": "string",
  "coat_pattern": "string",
  "distinctive_marks": "string",
  "trauma_observed": "string",
  "location_extracted": "string"
}
```
