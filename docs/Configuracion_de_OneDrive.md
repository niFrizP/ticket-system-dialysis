# Configuracion de OneDrive

## 1. Objetivo
Definir recomendaciones para trabajar con este proyecto cuando la carpeta del repositorio se sincroniza con OneDrive en Windows.

## 2. Riesgos comunes
- Bloqueo temporal de archivos durante sincronizacion.
- Cambios de timestamps que generan ruido en herramientas.
- Conflictos por archivos de log o temporales.
- Lentitud en carpetas con muchos cambios pequenos.

## 3. Recomendaciones
- Evitar sincronizar carpetas de alta rotacion:
  - `logs/`
  - `uploads/`
  - `vendor/` (opcional, recomendable excluir si el entorno lo permite)
- No guardar `.env` en ubicaciones compartidas publicamente.
- Confirmar que `.gitignore` excluya archivos sensibles y de runtime.

## 4. Configuracion sugerida en Windows
1. Abrir configuracion de OneDrive.
2. Revisar carpeta sincronizada del proyecto.
3. Excluir subcarpetas no necesarias para sincronizacion (si la politica lo permite).
4. Activar disponibilidad local para archivos criticos de desarrollo.

## 5. Buenas practicas de equipo
- Definir una politica unica de sincronizacion por equipo.
- No editar simultaneamente el mismo archivo en multiples equipos sin pull previo.
- Ejecutar `git pull` antes de iniciar trabajo y antes de commitear.

## 6. Manejo de conflictos
Si aparece conflicto de OneDrive:
1. Identificar archivo fuente y archivo en conflicto.
2. Comparar cambios y resolver manualmente.
3. Ejecutar validacion basica del sistema.
4. Confirmar que no quedaron archivos duplicados tipo `-conflicted copy`.

## 7. Nota operativa
Para entornos productivos, se recomienda desplegar fuera de carpetas sincronizadas por OneDrive para evitar bloqueos y comportamiento no determinista.
