---
name: skill_evaluar_compatibilidad_adopcion
version: "1.0.0"
category: "Triaje y Bienestar Animal"
description: "Evalúa solicitudes de adopción aplicando Hard-Stops de solvencia económica, aptitud de vivienda y compatibilidad interespecie."
author: "RefuGuía AI Core"
timeout_ms: 4000
parameters:
  type: object
  required:
    - pet_id
    - monthly_income_usd
    - housing_type
    - hours_dedicated_daily
  properties:
    pet_id:
      type: integer
    monthly_income_usd:
      type: number
      description: "Ingreso familiar disponible para la mascota."
    housing_type:
      type: string
      description: "Tipo de inmueble (casa con patio, apartamento, etc)."
    hours_dedicated_daily:
      type: integer
      description: "Horas de compañía y cuidado diario."
---

# ❤️ Skill: Evaluación Experta de Idoneidad para Adopción

## 🎯 Propósito
Determinar si un postulante cumple con los requerimientos esenciales para brindar un hogar definitivo seguro a un animal rescatado con traumas post-sismo.

## 🚫 Criterios de Hard-Stop (Rechazo Automático Inmediato)
1. **Ingreso Mensual < $30 USD:** Rechazo directo por incapacidad material para cubrir vacunas y alimentación veterinaria básica.
2. **Perro Grande en Habitación sin Patio:** Rechazo por inviabilidad de bienestar etológico.
3. **Mascota con Fobia a Felinos + Adoptante con Gatos:** Rechazo por riesgo de conflicto interespecie.
