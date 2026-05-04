# Tasklist: Módulo de Agenda OSPOS

## Arquitectura general

OSPOS es la única fuente de verdad. Google Calendar actúa como espejo de visualización móvil.

```
OSPOS (PC) crea/modifica turno
        ↓
Google Calendar API sincroniza automáticamente
        ↓
Celular / cualquier dispositivo ve los turnos
en Google Calendar en tiempo real
```

---

## Fase 1 — Base de datos

- [x] Crear migración `appointments` (id, customer_id, employee_id, service_id, start_time, end_time, status, notes, sale_id, google_event_id)
- [x] Crear migración `appointment_services` (id, name, duration_minutes, price, active)
- [x] Importar migraciones y verificar tablas en DB

---

## Fase 2 — Backend
 - [x] Crear `app/Models/Appointment_model.php`
  - [x] `get_by_date_range($start, $end)`
  - [x] `get_by_customer($customer_id)`
  - [x] `get_upcoming($limit)`
  - [x] `get_by_status($status)`
  - [x] Validación de solapamiento de turnos
 - [x] Crear `app/Models/Appointment_service_model.php`
  - [x] CRUD básico
  - [x] `get_all_active()`
 - [x] Crear `app/Controllers/Appointments.php`
  - [x] `index()` — vista principal con calendario
  - [x] `get_events()` — endpoint JSON para FullCalendar
  - [x] `create()` — crear turno con validación de solapamiento
  - [x] `update($id)` — modificar turno
  - [x] `delete($id)` — cancelación lógica
  - [x] `complete($id)` — marcar como completado y redirigir a venta
  - [x] `get_form($id = null)` — HTML del modal vía AJAX
 - [x] Crear `app/Controllers/Appointment_services.php`
  - [x] CRUD completo de servicios
 - [ ] Agregar permisos en `ospos_grants` para el nuevo módulo
 - [ ] Agregar rutas en `app/Config/Routes.php`
 - [x] Agregar permisos en `ospos_grants` para el nuevo módulo
 - [x] Agregar rutas en `app/Config/Routes.php`

---

## Fase 3 — Frontend

- [x] Agregar ítem "Agenda" al navbar de OSPOS (`app/Views/partial/navbar.php`)
- [x] Crear vista principal con FullCalendar (`app/Views/appointments/index.php`)
  - [x] Vistas: mes, semana, día
  - [x] Barra de filtros por estado
  - [x] Botón "Nuevo turno"
- [x] Endpoint JSON para FullCalendar (`get_events`)
- [x] Modal de creación/edición de turno
  - [x] Autocomplete de cliente (usando API existente de OSPOS)
  - [x] Selector de servicio con precio y duración precargados
  - [x] Fecha y hora inicio/fin
  - [x] Estado (pendiente/confirmado/completado/cancelado)
  - [x] Notas
- [x] Modal de gestión de servicios (ABM)
- [x] Drag & drop para mover turnos entre horarios

---

## Fase 4 — Integración con ventas OSPOS

- [x] Botón "Completar turno" que abre la venta en OSPOS con cliente y precio preseleccionados
- [x] Guardar `sale_id` en el turno al finalizar la venta
- [x] Pestaña "Turnos" en ficha de cliente existente de OSPOS

---

## Fase 5 — Sincronización Google Calendar

- [x] Configurar credenciales OAuth2 de Google en Settings de OSPOS
- [x] Guardar token OAuth en tabla de configuración existente
- [x] Al crear turno → crear evento en Google Calendar y guardar `google_event_id`
- [x] Al modificar turno → actualizar evento en Google Calendar
- [x] Al cancelar/eliminar turno → eliminar evento en Google Calendar

---

## Notas

- Solo se crean/modifican turnos desde OSPOS. Google Calendar es solo visualización.
- El celular solo necesita la cuenta de Google para ver los turnos, sin acceso a OSPOS.
- La sincronización es unidireccional: OSPOS → Google Calendar.
- El webhook inverso (Google Calendar → OSPOS) queda fuera del alcance por ahora.
