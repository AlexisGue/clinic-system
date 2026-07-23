# Arquitectura — Sistema de Gestión para Clínicas

> Contrato de diseño. Toda decisión nueva debe registrarse aquí.
> Adaptado desde el monorepo Laravel 12 + Vue 3 (capas y seguridad intactas).

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12, PHP 8.2, MySQL (XAMPP), Laravel Sanctum (SPA) |
| Frontend | Vue 3, Vite, Pinia, Vue Router, Axios, Tailwind CSS 4 |

## Monorepo

```text
clinic-system/   (carpeta actual: inventory-system → renombrar cuando convenga)
├── backend/     → API REST Laravel
├── frontend/    → SPA Vue
└── docs/        → Arquitectura y manuales
```

## Backend: capas

Middleware → Form Request → Controller → Service → Repository → MySQL.  
Salida: API Resources. Envelope `{ message, data }` / error `{ message, code, errors }`.

Módulos en `app/Modules/{Module}/` con Controllers, Models, Services, Repositories, Requests, Resources, Policies, `routes.php`.

### Módulos v1

| Módulo | Responsabilidad |
|---|---|
| Auth | Login / logout / me (Sanctum SPA) |
| Users | Usuarios + roles Spatie |
| Audit | Listado auditoría (Owen-It) |
| Settings | Datos de la clínica (key/value) |
| Catalogs | Specialties, medicines, payment_methods |
| Patients | Pacientes, contactos, historial agregado |
| Doctors | Perfil médico, especialidades N:N, horarios |
| Appointments | Citas, calendario, reprogramar, cancelar |
| Consultations | Consulta clínica + signos vitales |
| Prescriptions | Recetas + ítems + PDF |
| Payments | Cobros ligeros (monto + método) |
| Dashboard | KPIs clínicos + gráficas |
| Reports | Pacientes, citas, consultas, médicos, pagos |

## Roles

| Rol | Alcance |
|---|---|
| `admin` | Todo |
| `doctor` | Pacientes (ver), sus citas, consultas, recetas |
| `receptionist` | Pacientes, citas, cobros; **sin** texto clínico de consultas/recetas |

## Decisiones de dominio

1. **Una sola clínica** (settings globales).
2. Citas no se borran: `cancelled` / `no_show`. Reprogramar actualiza fechas.
3. **Sin solapes** del mismo médico; cita debe caer en `doctor_schedules` (hard-block).
4. Consulta: desde cita o walk-in. Al `finalize` → appointment `completed`.
5. Receta solo desde consulta; snapshot de nombre de medicamento.
6. Pago opcional; no bloquea finalizar consulta (`require_payment_to_complete` = false).
7. Folios: citas `A-000001`, consultas `C-000001`, recetas `R-000001`.
8. Dinero: `DECIMAL(12,2)`, moneda default **USD**.

## Tablas principales

users, Spatie ×5, settings, audits, payment_methods, specialties, medicines, patients, patient_contacts, doctors, doctor_specialty, doctor_schedules, appointments, consultations, vital_signs, prescriptions, prescription_items, payments.

## API

Prefijo `/api/v1`. Auth, CRUD por recurso, acciones de dominio:

- `POST appointments/{id}/reschedule|cancel`
- `GET appointments/calendar`
- `POST consultations/{id}/finalize|vitals`
- `POST prescriptions/{id}/cancel`, `GET prescriptions/{id}/pdf`
- `POST payments`, `GET dashboard/summary`, `GET reports/{type}`

## Seguridad (heredada)

Sanctum SPA, CSRF, CORS origen único, rate limits login/api, SecurityHeaders, policies + Spatie, Form Requests, `Model::shouldBeStrict()` en local, Password defaults, HTTPS en prod, auditoría Owen-It.

## Frontend

`src/modules/{auth,patients,doctors,appointments,consultations,prescriptions,payments,dashboard,reports,settings,users,audit,catalogs}/` espejo del backend. UI en español.

## Roadmap

Fases 0–9: shell → catálogos → pacientes → médicos → citas → consultas → recetas → pagos → dashboard/reportes → hardening/tests/manual.
