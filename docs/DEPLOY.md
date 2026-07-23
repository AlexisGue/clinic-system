# Despliegue gratis (demo) — Vercel + Render

Guía para publicar **Clinic System** y compartirlo con amigos.

## Arquitectura

| Pieza | Servicio | URL ejemplo |
|---|---|---|
| Frontend (Vue) | [Vercel](https://vercel.com) | `https://clinic-system.vercel.app` |
| API (Laravel) | [Render](https://render.com) | `https://clinic-system-api.onrender.com` |
| Base de datos | Render PostgreSQL | (interna) |

> El plan free de Render **duerme** el servicio ~15 min sin uso. La primera carga puede tardar 30–60 s.

---

## 1) Backend en Render

### 1.1 Base de datos
1. Entra a [render.com](https://render.com) → **New +** → **PostgreSQL**.
2. Name: `clinic-system-db` · Plan: **Free**.
3. Guarda el panel **Connections** (Internal Database URL / host, user, password, database).

### 1.2 Web Service (Docker)
1. **New +** → **Web Service** → conecta el repo `AlexisGue/clinic-system`.
2. Configura:
   - **Root Directory:** `backend`
   - **Runtime:** Docker
   - **Dockerfile Path:** `./Dockerfile` (relativo a `backend`)
   - Plan: **Free**
3. **Environment** (Variables):

```env
APP_NAME=Clinic System
APP_ENV=production
APP_DEBUG=false
APP_KEY=          # generar abajo
APP_URL=https://TU-SERVICIO.onrender.com
APP_TIMEZONE=America/Mexico_City

FRONTEND_URL=https://TU-APP.vercel.app

DB_CONNECTION=pgsql
DB_HOST=         # de Render Postgres
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=10080
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
SESSION_SAME_SITE=none

SANCTUM_STATEFUL_DOMAINS=TU-APP.vercel.app

RUN_SEED=true
SEED_DEMO_USERS=true
LOG_LEVEL=error
```

4. Genera `APP_KEY` en tu PC:

```bash
cd backend
php artisan key:generate --show
```

5. Deploy. Anota la URL pública de la API (ej. `https://clinic-system-api.onrender.com`).
6. Prueba: `https://TU-API.onrender.com/up` debe responder OK.

> Primero puedes poner un `FRONTEND_URL` temporal (`https://localhost`) y actualizarlo cuando Vercel te dé la URL real; luego **Redeploy** la API.

---

## 2) Frontend en Vercel

1. Entra a [vercel.com](https://vercel.com) → **Add New** → **Project** → importa `AlexisGue/clinic-system`.
2. Configura:
   - **Root Directory:** `frontend`
   - Framework Preset: **Vite**
   - Build Command: `npm run build`
   - Output Directory: `dist`
3. **Environment Variable:**

```env
VITE_API_URL=https://TU-API.onrender.com
```

(sin barra final)

4. Deploy. Copia la URL (ej. `https://clinic-system-xxxx.vercel.app`).

---

## 3) Cruzar URLs (importante)

En **Render** actualiza y redespliega:

```env
FRONTEND_URL=https://clinic-system-xxxx.vercel.app
SANCTUM_STATEFUL_DOMAINS=clinic-system-xxxx.vercel.app
```

(sin `https://` en `SANCTUM_STATEFUL_DOMAINS`)

En **Vercel**, confirma:

```env
VITE_API_URL=https://TU-API.onrender.com
```

---

## 4) Probar

1. Abre la URL de Vercel.
2. Login demo:
   - `admin@demo.test` / `Admin1234`
   - `doctor@demo.test` / `Doctor1234`
   - `recepcion@demo.test` / `Recepcion1234`
3. Si el login falla por cookies/CORS: revisa que `SESSION_SAME_SITE=none`, `SESSION_SECURE_COOKIE=true` y que el dominio de Vercel coincida exacto en Sanctum.

---

## Notas

- No subas `.env` al repo (ya está en `.gitignore`).
- `RUN_SEED=true` carga datos demo en cada arranque del contenedor; pon `false` si no quieres re-sembrar.
- Esto es para **demo académica/amigos**, no para pacientes reales en producción.
