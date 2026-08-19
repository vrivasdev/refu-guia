---
name: skill_verificar_periodo_gracia
version: "1.0.0"
category: "Reglas Jurídicas y Negocio de Emergencia"
description: "Valida de forma estricta e inamovible la regla legal de los 15 días continuos de búsqueda familiar antes de habilitar la adopción."
author: "RefuGuía Legal & AI"
timeout_ms: 2000
parameters:
  type: object
  required:
    - pet_id
  properties:
    pet_id:
      type: integer
      description: "ID de la mascota rescatada en refugio."
---

# ⏳ Skill: Validador del Período de Gracia Legal (15 Días)

## 🎯 Propósito
Evitar la reubicación o adopción prematura de mascotas cuyos dueños damnificados puedan estar incomunicados o en centros de salud post-sismo.

## 📜 Regla Legal Inamovible
1. Toda mascota ingresada por rescate (`report_type: found`) tiene un período de gracia de **15 días continuos** a partir de su fecha de rescate.
2. Durante este período, el estado es estrictamente `in_shelter` o `searching_family`.
3. Queda terminantemente **prohibida** su publicación o postulación para adopción antes de cumplirse los 15 días.
4. Cumplidos los 15 días sin reclamo de tutor legal, el estado cambia a `adoptable`.
