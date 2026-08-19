# 📋 Guía Oficial de Pruebas Manuales por Roles y Casos de Uso
## Proyecto: RefuGuía — Sistema Agéntico Post-Sismo con IA Local (SLM) y Protocolo MCP

---

### 📌 Tabla de Acceso y Credenciales Preconfiguradas

| Persona / Usuario | Rol de Sistema | Correo Electrónico | Contraseña | Acceso Rápido |
| :--- | :--- | :--- | :--- | :--- |
| **Dra. Carmen López** | `shelter_admin` (Coordinadora) | `carmen.refugio@refuguia.org` | `Password123!` | Botón *"Admin de Refugio"* |
| **Carlos Mendoza** | `rescuer` (Rescatista de Campo) | `carlos.rescate@refuguia.org` | `Password123!` | Botón *"Rescatista de Campo"* |
| **María Fernández** | `citizen` (Ciudadana Damnificada) | `maria.f@gmail.com` | `Password123!` | Botón *"Ciudadana Damnificada"* |
| **Andrés Morales** | `adopter` (Adoptante Responsable) | `andres.m@gmail.com` | `Password123!` | Botón *"Adoptante"* |

---

## 🏥 CASO DE USO 1: Rol Administradora de Refugio (Dra. Carmen López)

### 🧪 Prueba 1.1: Control Clínico y Bloqueo de Fármacos sin Validación QR
* **Objetivo:** Verificar la regla de negocio y ciberseguridad que prohíbe la administración de medicamentos críticos sin lectura de QR físico.
* **Paso a Paso:**
  1. Inicia sesión con el botón rápido **Admin de Refugio** (`carmen.refugio@refuguia.org`).
  2. Haz clic en la pestaña **`🏥 Refugios & QR`**.
  3. En la columna izquierda, selecciona la mascota **"Rescatado Toby (Provisorio)"** (`RG-2026-000512`).
  4. En la sección *"Administrar Tratamiento / Fármaco Crítico"*, escribe:
     * **Fármaco:** `Cefalexina 500mg (Antibiótico)`
     * **Veterinario:** `Dra. Carmen López`
     * **Checkbox de Escaneo QR:** **NO marcar** (dejar desmarcado).
  5. Intenta presionar el botón *"Registrar Fármaco"*.
  6. Ahora, **marca el checkbox** `¿Código QR físico escaneado y verificado en collar?` y presiona el botón.
* **Resultado Esperado (Salida):**
  * Con el checkbox desmarcado: El botón permanece deshabilitado y se muestra la alerta `❌ Bloqueo activo: Debes marcar la confirmación de escaneo de QR`.
  * Con el checkbox marcado: Se registra el tratamiento con éxito, se genera un **Hash criptográfico SHA-256** de auditoría y se agrega al historial clínico de la mascota.

---

### 🧪 Prueba 1.2: Terminal de IA Local (Qwen 2.5:1.5B) y Telemetría
* **Objetivo:** Validar la inferencia en tiempo real del SLM local sobre Ollama y el escudo contra inyección de prompts.
* **Paso a Paso:**
  1. En el menú superior (solo visible para este rol), haz clic en **`💻 SLM Local`**.
  2. Verifica que el chip superior indique `Ollama Conectado (qwen2.5:1.5b)` en verde.
  3. Haz clic en el botón de preset **`🐕 Extraer Entidades de Mascota`** o escribe:
     * *Prompt:* `Extrae en JSON: Gata tricolor pequeña asustada rescatada en escombros en La Guaira, sin collar.`
  4. Presiona el botón **`🚀 Ejecutar Inferencia Local`**.
  5. Luego, en la caja inferior de seguridad, haz clic en **`Probar Filtro`**.
* **Resultado Esperado (Salida):**
  * En la consola se despliega la respuesta estructurada de Qwen 2.5 con telemetría en vivo: `tokens generados (~80-130)`, `tiempo de inferencia (~1-4s)` y `velocidad (~30-40 t/s)`.
  * En el escudo de seguridad, el prompt malicioso es neutralizado con la etiqueta `[CONTENIDO_FILTRADO_POR_SEGURIDAD]`.

---

## 🚒 CASO DE USO 2: Rol Rescatista de Campo (Carlos Mendoza)

### 🧪 Prueba 2.1: Ingreso de Mascota Rescatada y Generación de Collar QR
* **Objetivo:** Registrar un animal encontrado en escombros mediante el asistente inteligente y generar su identificador de emergencia.
* **Paso a Paso:**
  1. Cambia de usuario haciendo clic en tu avatar en la barra superior -> `🔄 Cambiar de Usuario / Rol` -> selecciona **Rescatista de Campo**.
  2. Dirígete a la pestaña **`💬 Chat Ciudadano`**.
  3. Presiona la tarjeta rápida **`🏠 Encontré / Rescaté una mascota`**.
  4. En el campo de texto, ingresa la siguiente descripción de campo:
     * *Texto:* `Acabamos de extraer de los escombros en La Guaira a un perro Golden mestizo color canela claro, tamaño grande, macho, tiene barro en el lomo y una herida leve en la oreja derecha.`
  5. Presiona **`Enviar 🚀`**.
* **Resultado Esperado (Salida):**
  * El asistente SLM responde confirmando el ingreso oficial.
  * Se genera automáticamente un UUID único de emergencia (ej: `RG-2026-000589`).
  * Se renderiza la tarjeta de extracción NLP con especie `canine`, raza `Golden Mestizo`, color `Canela`, trauma `Herida en oreja`.
  * Se genera el **Collar con Código QR digital** listo para impresión y colocación física.

---

## 🙋‍♀️ CASO DE USO 3: Rol Ciudadana Damnificada (María Fernández)

### 🧪 Prueba 3.1: Reporte de Mascota Perdida y Búsqueda Inteligente
* **Objetivo:** Reportar a un perro extraviado durante el sismo y consultar coincidencias.
* **Paso a Paso:**
  1. Cambia de usuario al perfil **Ciudadana Damnificada** (`maria.f@gmail.com`).
  2. En **`💬 Chat Ciudadano`**, presiona la tarjeta **`🔍 Perdí a mi mascota`**.
  3. Ingresa la descripción de búsqueda familiar:
     * *Texto:* `Busco a mi perro Toby, es un Border Collie mestizo negro con pecho blanco y una mancha sobre el ojo izquierdo, se asustó con el temblor en Catia.`
  4. Presiona **`Enviar 🚀`**.
* **Resultado Esperado (Salida):**
  * El sistema registra el reporte de pérdida en la base de datos MySQL y genera los embeddings vectoriales en ChromaDB.
  * El asistente informa que el reporte está activo y que el **Matchmaker Hub** notificará coincidencias en tiempo real.

---

### 🧪 Prueba 3.2: Verificación de Match en el Matchmaker Hub (>80% Similitud)
* **Objetivo:** Visualizar el emparejamiento de alta confianza y validar el reencuentro presencial.
* **Paso a Paso:**
  1. Dirígete a la pestaña **`⚡ Matchmaker Hub`** (visible para la ciudadana).
  2. Observa la tarjeta del emparejamiento entre **Toby (Perdido)** y **Rescatado Toby (En Refugio)**.
  3. Verifica los puntajes:
     * **Score Total:** `91.5%` (Verde / Alta Certeza).
     * **Similitud Visual:** `95%` | **Semántica NLP:** `90%` | **Distancia:** `1.8 km`.
  4. Presiona el botón verde **`✅ Confirmar Reencuentro`**.
* **Resultado Esperado (Salida):**
  * El estado cambia a `reunified` (Reunificado con su familia).
  * Se detiene el conteo de los 15 días de gracia y la mascota queda excluida del circuito de adopción.

---

## ❤️ CASO DE USO 4: Rol Adoptante Responsable (Andrés Morales)

### 🧪 Prueba 4.1: Intento de Adopción con Bloqueo por Hard-Stop (Ingresos Insuficientes)
* **Objetivo:** Comprobar que el evaluador de IA aplica los criterios de bienestar animal antes de permitir una postulación.
* **Paso a Paso:**
  1. Cambia de usuario a **Adoptante** (`andres.m@gmail.com`).
  2. Haz clic en la pestaña **`❤️ Adopción (15d)`**.
  3. En la lista de mascotas adoptables (aquellas que ya superaron los 15 días de gracia sin reclamo familiar), selecciona a **"Milo (Gatito rescatado)"**.
  4. Completa el formulario con datos insuficientes:
     * **Nombre:** `Andrés Morales`
     * **Correo:** `andres.m@gmail.com`
     * **Ingreso Mensual (USD):** `15` *(Menor al costo de manutención)*
     * **Tipo de Vivienda:** `Habitación compartida sin patio`
     * **Horas dedicadas:** `1 hora/día`
  5. Presiona **`Enviar Postulación para Evaluación IA`**.
* **Resultado Esperado (Salida):**
  * **Dictamen:** `NO APTO (Hard-Stop Financiero)`.
  * **Explicación IA:** *"El presupuesto mensual declarado ($15 USD) es insuficiente para cubrir la alimentación balanceada y atención médica veterinaria de emergencia."*

---

### 🧪 Prueba 4.2: Postulación Aprobada con Alta Compatibilidad
* **Paso a Paso:**
  1. Ajusta los datos del formulario de adopción:
     * **Ingreso Mensual (USD):** `450`
     * **Tipo de Vivienda:** `Casa con patio cerrado y seguro`
     * **Horas dedicadas:** `4 horas/día`
     * **Experiencia:** `Intermedio / Dueño previo de gatos`
  2. Presiona **`Enviar Postulación para Evaluación IA`**.
* **Resultado Esperado (Salida):**
  * **Dictamen:** `APROBADO (Compatibilidad: 94%)`.
  * **Explicación IA:** *"El adoptante cuenta con solvencia económica demostrada, espacio físico seguro y tiempo diario suficiente para la adaptación post-trauma del felino."*
