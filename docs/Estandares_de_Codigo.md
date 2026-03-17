# Estandares de Codigo

## 1. Objetivo
Definir reglas minimas para mantener consistencia, legibilidad y seguridad en el proyecto.

## 2. Estilo general
- Usar PHP 7.4+ compatible.
- Mantener indentacion de 4 espacios.
- Nombrar funciones y variables en formato descriptivo.
- Evitar logica extensa en vistas cuando sea posible.

## 3. Seguridad
- Nunca hardcodear credenciales.
- Leer configuraciones sensibles desde `.env`.
- Sanitizar entradas de usuario y validar formato.
- Usar consultas preparadas para SQL.
- No exponer mensajes internos en produccion.

## 4. Manejo de errores
- Registrar errores tecnicos en logs.
- Devolver mensajes claros y seguros al usuario.
- Capturar excepciones en puntos de entrada (`process/`, controladores principales).

## 5. Base de datos
- Toda consulta debe ser parametrizada.
- Evitar `SELECT *` en codigo nuevo.
- Agregar indices para campos de busqueda frecuente.
- Toda modificacion estructural debe ir en `migrations/`.

## 6. Frontend
- Reutilizar estilos en `assets/css/custom.css`.
- Mantener JS de validacion en `assets/js/form-validation.js` u otro archivo por dominio funcional.
- Validar tanto en cliente como en servidor.

## 7. Convenciones de commits
Formato sugerido:

```text
tipo(scope): descripcion breve
```

Tipos sugeridos:
- feat
- fix
- docs
- refactor
- chore

Ejemplo:

```text
fix(process): valida centro medico activo antes de crear ticket
```

## 8. Checklist minimo antes de merge
- El flujo principal funciona de extremo a extremo.
- No hay secretos en el diff.
- Se actualizo documentacion si cambia comportamiento.
- Se validaron errores y logs del modulo modificado.
