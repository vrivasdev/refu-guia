---
name: skill_peritaje_visual_moondream
version: 1.0.0
description: Peritaje visual anatómico y cotejo multimodal de fotografías de mascotas post-sismo utilizando el modelo VLM local Moondream.
agent: Agente_Peritaje_Visual_VLM
input_schema:
  type: object
  properties:
    lost_pet_id:
      type: integer
      description: ID de la mascota perdida reportada por el ciudadano.
    found_pet_id:
      type: integer
      description: ID de la mascota rescatada en inventario de refugio.
    photo_lost_url:
      type: string
      description: URL o Base64 de la fotografía aportada por la familia.
    photo_found_url:
      type: string
      description: URL o Base64 de la fotografía tomada en campo por rescatistas.
  required:
    - lost_pet_id
    - found_pet_id
output_schema:
  type: object
  properties:
    visual_similarity_score:
      type: number
      description: Porcentaje de coincidencia anatómica y de patrón (0-100%).
    anatomical_verdict:
      type: string
      description: Dictamen forense pericial generado por el VLM Moondream.
    features_evaluated:
      type: object
      description: Desglose de análisis de pelaje, orejas y estructura facial.
    confidence_tier:
      type: string
      enum: [HIGH_CONFIDENCE_MATCH, REQUIRES_MANUAL_REVIEW]
---

# Skill: Peritaje Visual Multimodal (Moondream VLM)

## Contexto Operativo
En situaciones de catástrofe y sismos, las descripciones verbales de los damnificados pueden ser subjetivas o inexactas debido al shock emocional. Esta Skill ejecuta un **peritaje visual directo a nivel de píxeles** comparando la fotografía del animal antes del sismo con la fotografía tomada en el campamento de rescate.

## Arquitectura de Inferencia
* **Modelo Multimodal:** `moondream:latest` (1.4B parámetros - ViT + Phi-1.5).
* **Protocolo:** MCP (Model Context Protocol).
* **Aislamiento:** 100% On-Premise en Ollama sin salida a internet (Zero Data Leakage).

## Reglas de Evaluación
1. **Patrón de Manto:** Se identifican manchas bicolores, manchas pectorales y distribución de pelaje.
2. **Estructura Facial:** Proporción hocico/cráneo y color de iris.
3. **Morfología Auricular:** Orejas erguidas, semi-caídas o caídas.
