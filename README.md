# Clinic System

Sistema de gestión para clínicas (Laravel 12 API + Vue 3 SPA).  
Arquitectura modular, Sanctum SPA, Spatie permissions.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12 · Sanctum · MySQL · DomPDF |
| Frontend | Vue 3 · Vite · Pinia · Vue Router · Axios · Tailwind CSS 4 |

## Estructura

```text
├── backend/    # API REST (Controllers → Services → Repositories)
├── frontend/   # SPA Vue
└── docs/       # docs/ARCHITECTURE.md
```

## Requisitos

- PHP >= 8.2 · Composer · Node.js >= 20 · MySQL (XAMPP)

## Instalación

```bash
# 1) Crear BD: clinic_system (utf8mb4_unicode_ci)

# 2) Backend
cd backend
composer install
copy .env.example .env
# DB_DATABASE=clinic_system, FRONTEND_URL, SANCTUM_STATEFUL_DOMAINS
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000

# 3) Frontend
cd frontend
npm install
copy .env.example .env
# VITE_API_URL=http://localhost:8000
npm run dev
```

Abrir: http://localhost:5173

## Credenciales demo (solo local / staging)

> No se crean en `APP_ENV=production`. Cámbialas antes de exponer el entorno.

| Rol | Correo | Contraseña |
|---|---|---|
| Admin | `admin@demo.test` | `Admin1234` |
| Médico | `doctor@demo.test` | `Doctor1234` |
| Recepción | `recepcion@demo.test` | `Recepcion1234` |

Roles: `admin`, `doctor`, `receptionist`.

## Despliegue (Vercel + Render)

Pasos completos: [`docs/DEPLOY.md`](docs/DEPLOY.md).

## Módulos

Auth · Users/Roles/Audit · Pacientes · Médicos · Citas · Consultas · Recetas · Cobros ligeros · Dashboard · Reportes · Settings · Catálogos (especialidades, medicamentos).

Detalle: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).
