# Guia para Contribuidores

## 1. Alcance
Esta guia aplica a cualquier colaborador interno o externo autorizado.

## 2. Flujo de trabajo sugerido
1. Crear rama desde la rama estable.
2. Implementar cambios pequenos y enfocados.
3. Probar flujo afectado localmente.
4. Actualizar documentacion cuando aplique.
5. Abrir Pull Request con contexto funcional y tecnico.

## 3. Estructura esperada del Pull Request
- Problema que resuelve.
- Cambios realizados.
- Riesgos conocidos.
- Pasos de validacion manual.
- Evidencia (capturas o logs relevantes).

## 4. Criterios de aceptacion
- No rompe flujos existentes.
- Mantiene estandares de seguridad.
- No agrega secretos ni datos sensibles al repositorio.
- Incluye cambios de documentacion cuando corresponde.

## 5. Recomendaciones tecnicas
- Evitar refactors masivos en una sola PR.
- Priorizar cambios atomicos por modulo.
- Reusar componentes y funciones existentes antes de duplicar logica.

## 6. Convenciones
- Nombres de ramas sugeridos:
  - `feature/<descripcion-corta>`
  - `fix/<descripcion-corta>`
  - `docs/<descripcion-corta>`
- Mensajes de commit claros y orientados a accion.

## 7. Soporte
Si tienes dudas de arquitectura o seguridad, abrir un issue interno antes de implementar cambios de alto impacto.
