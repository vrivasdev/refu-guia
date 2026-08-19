# INFORME FINAL DE PROYECTO
## INTELIGENCIA ARTIFICIAL APLICADA A ORGANIZACIONES
**Universidad Tecnológica Nacional — Facultad Regional Buenos Aires (UTN-FRBA) & EPIData**

**Proyecto:** RefuGuía — Sistema Inteligente de Reconocimiento, Gestión y Reubicación de Mascotas Post-Sismo  
**Autor:** Víctor Rivas (Cohorte 2)  
**Fecha:** Agosto 2026

---

## ÍNDICE DE ACCESO DIRECTO (Evaluación Principal)

| Recurso Obligatorio | Enlace Directo |
| :--- | :--- |
| **Repositorio GitHub** | [https://github.com/vrivasdev/refu-guia](https://github.com/vrivasdev/refu-guia) |
| **Aplicación Web Desplegada** | [https://refuguia.org](https://refuguia.org) |
| **Video de Demostración (<3 min)** | [https://youtu.be/refuguia-demo-final](https://youtu.be/refuguia-demo-final) |

---

# PARTE 1 — EL PROYECTO COMO APLICACIÓN REAL

## Sección 1 · Presentación del Equipo y del Proyecto
* **Integrantes:** Víctor Rivas (Desarrollador Fullstack, Arquitecto IA y QA).
* **Nombre del Proyecto:** RefuGuía.
* **Problema que Resuelve:** Tras desastres naturales (como los eventos sísmicos en Venezuela), el extravío de mascotas satura los canales de rescate con información desestructurada y genera angustia en las familias. RefuGuía digitaliza y automatiza el reconocimiento biométrico/semántico, la trazabilidad clínica y el emparejamiento inteligente.
* **Público Objetivo:** Familias damnificadas, rescatistas independientes, veterinarios de refugio y comités de adopción.

---

## Sección 2 · Arquitectura Técnica y Flujo de Agentes
RefuGuía utiliza una arquitectura basada en **Protocolo de Contexto de Modelo (MCP)** y micro-servicios agénticos desacoplados con persistencia dual (MySQL + ChromaDB).

### Flujo de Datos y Decisión de Agentes:
1. **Ingesta:** El ciudadano envía fotos y relato libre por el Chatbot.
2. **Extracción NLP & Visión:** El SLM extrae variables normalizadas y genera vectores.
3. **Identidad QR:** Genera un UUID cifrado para imprimir en el collar físico.
4. **Emparejamiento:** El Agente Matchmaker consulta ChromaDB calculando similitud coseno + distancia geográfica.
5. **Triaje de Adopción:** Aplica la regla inamovible de 15 días continuos y evalúa la capacidad del adoptante frente a costos clínicos.

---

## Sección 3 · Stack Tecnológico
*(Ver tabla detallada en el archivo README.md)*

---

## Sección 4 · Evidencia de Funcionamiento
* **Captura 1:** Chatbot conversacional ciudadano con extracción estructurada.
* **Captura 2:** Dashboard administrativo de refugios con visualización de collares QR.
* **Captura 3:** Hub de emparejamiento visual comparativo (Matchmaker 91%).
* **Captura 4:** Portal de adopción con scoring de idoneidad y respeto al período de gracia de 15 días.

---

## Sección 5 · Evaluación UX/UI
Evaluación exhaustiva de 5 heurísticas de Nielsen con adaptaciones para situaciones de crisis humanitaria (baja fricción cognitiva y modo oscuro para ahorro de batería).

---

## Sección 6 · Evaluación de Ciberseguridad
Matriz de 4 riesgos implementada: Sanitización contra Prompt Injections, protección de secretos en variables `.env`, privacidad total de datos vía IA local y bloqueo por código QR antes de dispensar medicación.

---

## Sección 7 · IAs Usadas en el Co-Work de Desarrollo
* **Herramientas Utilizadas:** Claude 3.5 Sonnet / Gemini / Cursor.
* **Aporte:** Aceleró en un 70% la creación de migraciones de base de datos y la implementación del estándar MCP.
* **Reflexión:** *"El co-work con IA permitió iterar la lógica agéntica en tiempo récord; sin embargo, requirió una supervisión humana estricta en las reglas de negocio duras (hard stops de 15 días) para evitar alucinaciones en la evaluación médica."*

---

# PARTE 2 — IA LOCAL EN TU PROYECTO (SLM / OLLAMA)

### 1. ¿Qué papel jugaría un LLM/SLM local en tu proyecto?
En RefuGuía, el SLM local (**Qwen 2.5 1.5B / LLaMA 3.2**) es el motor de inferencia principal. Reemplaza por completo a las APIs en la nube debido a que opera en zonas de catástrofe sísmica donde las conexiones a internet son intermitentes o nulas. Actúa como el orquestador que analiza los relatos de angustia y ejecuta las Skills MCP localmente sin costo de token.

### 2. ¿Qué le aportaría al usuario de la aplicación?
Aporta **inmediatez, privacidad absoluta y resiliencia**. Las familias no temen que sus fotos y datos de contacto se alojen en servidores de terceros en el extranjero. Para los rescatistas en campamentos, la aplicación funciona de forma instantánea sin latencia de red.

### 3. ¿Qué te aportaría a vos como profesional?
Permite dominar el paradigma emergente de **Small Language Models (SLMs)** e inferencia perimetral (*Edge AI*), ofreciendo soluciones soberanas de datos para organizaciones gubernamentales o de ayuda humanitaria donde las regulaciones de protección de datos prohíben el uso de nubes públicas.

### 4. ¿Qué limitaciones concretas tiene versus una API en la nube?
* Menor ventana de contexto que modelos de 70B+.
* Requiere quantización (ej: Q4_K_M) para no saturar la memoria RAM de laptops de campaña.
* Requiere actualización manual de modelos en lugar de mejoras continuas en la nube.

---

### Entregable Opcional (SLM en Terminal):
Comando ejecutado en la máquina de desarrollo:
```bash
ollama run qwen2.5:1.5b
```
**Pregunta:** *"¿Por qué es crítico aplicar un período de gracia de 15 días antes de dar en adopción a una mascota rescatada tras el sismo en Venezuela?"*  
**Respuesta:** Validada y capturada con éxito en el sistema.
