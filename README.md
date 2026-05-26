# Wellbeing Monitor — Remote Employee Psychological State Platform

Production-oriented MVP for daily wellbeing check-ins via browser extension and HR/manager analytics dashboard.

## Architecture overview

```
┌─────────────────────┐         HTTPS/JSON          ┌──────────────────────────────┐
│ Browser Extension   │ ──────────────────────────► │ Nginx → PHP-FPM (Laravel 12) │
│ (Manifest V3)       │   secret_key + employee     │                              │
└─────────────────────┘                             │  ┌─────────┐  ┌──────────┐  │
                                                    │  │ API v1  │  │ Dashboard│  │
┌─────────────────────┐         Session/Sanctum     │  │Extension│  │  Blade   │  │
│ Web Dashboard       │ ◄────────────────────────── │  └────┬────┘  └────┬─────┘  │
│ (Blade + Chart.js)  │                             │       │            │        │
└─────────────────────┘                             │       ▼            ▼        │
                                                    │  Services / Repositories    │
                                                    │       │            │        │
                                                    │  ┌────┴────┐  ┌────┴────┐  │
                                                    │  │PostgreSQL│  │ Redis   │  │
                                                    │  └─────────┘  └─────────┘  │
                                                    └──────────────────────────────┘
```

### Key decisions

| Area | Choice | Why |
|------|--------|-----|
| Auth (dashboard) | **Laravel Sanctum** | Native Laravel integration, session for Blade + token for API |
| Auth (extension) | **company `secret_key`** | Simple multi-tenant identification without OAuth complexity for MVP |
| Dashboard UI | **Blade + Vite + Tailwind + Chart.js** | Single deploy unit with Laravel, fast MVP, no separate SPA infra |
| Extension | **Vanilla JS (ES modules)** | Minimal bundle, no build step, ideal for MV3 popup/service worker constraints |
| Architecture | **Feature modules + Repository/Service** | SOLID, testable, ready for department scaling |
| Queue/Cache | **Redis** | Production-ready async processing and analytics cache |

## Project structure

```
kurs/
├── backend/                 # Laravel 12 API + Dashboard
│   ├── app/
│   │   ├── DTOs/
│   │   ├── Enums/
│   │   ├── Http/
│   │   ├── Jobs/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   └── Services/
│   ├── database/
│   ├── resources/views/
│   └── routes/
├── extension/               # Chrome MV3 extension
├── docker/
│   ├── nginx/
│   └── php/
├── docker-compose.yml
├── Makefile
└── README.md
```

## Quick start (Docker)

### Prerequisites

- Docker + Docker Compose
- Make (optional)

### 1. Configure environment

```bash
cp .env.example .env
cp backend/.env.example backend/.env
```

### 2. Start stack

```bash
make setup
```

Or manually:

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

### 3. Open services

- Dashboard: http://localhost:8080/login
- Health check: http://localhost:8080/up
- API base: http://localhost:8080/api/v1

### Demo credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@acme.test | password |
| HR | hr@acme.test | password |

### Demo extension secret key

```
demo_secret_key_12345678901234567890123456789012
```

## Local development (without Docker)

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
php artisan serve
php artisan queue:work redis
```

## API examples

### Extension: get survey questions

```http
GET /api/v1/extension/survey/questions?secret_key=demo_secret_key_12345678901234567890123456789012
```

Response:

```json
{
  "data": [
    {
      "id": 1,
      "question": "How would you rate your overall mood today?",
      "type": "scale",
      "sort_order": 1,
      "options": { "min": 1, "max": 5 }
    }
  ]
}
```

### Extension: submit check-in

```http
POST /api/v1/extension/check-in
Content-Type: application/json

{
  "secret_key": "demo_secret_key_12345678901234567890123456789012",
  "employee": {
    "external_id": "emp-001",
    "email": "alice@acme.test",
    "name": "Alice Johnson",
    "department_external_id": "eng"
  },
  "answers": [
    { "question_id": 1, "answer": "4" },
    { "question_id": 2, "answer": "2" },
    { "question_id": 3, "answer": "yes" },
    { "question_id": 4, "answer": "Good day overall" }
  ]
}
```

Response `201`:

```json
{
  "message": "Check-in submitted successfully.",
  "data": {
    "company_id": 1,
    "employee_id": 1,
    "check_in_date": "2025-05-25",
    "answers_stored": 4
  }
}
```

### Dashboard API: login

```http
POST /api/v1/dashboard/auth/login
Content-Type: application/json

{
  "email": "admin@acme.test",
  "password": "password",
  "device_name": "postman"
}
```

### Dashboard API: analytics overview

```http
GET /api/v1/dashboard/analytics/overview?from=2025-05-01&to=2025-05-25
Authorization: Bearer {token}
```

## Browser extension setup

1. Open `chrome://extensions`
2. Enable **Developer mode**
3. **Load unpacked** → select `extension/` folder
4. Open extension **Options**
5. Paste demo secret key and employee ID (e.g. `emp-001`)
6. Open popup to submit daily check-in

### Why Vanilla JS over React for extension?

- MV3 service worker + popup have strict size/perf limits
- No bundler required for MVP
- Faster cold start in popup
- React can be introduced later if UI complexity grows

## ER diagram (text)

```
companies (1) ──< employees (N)
companies (1) ──< departments (N)
companies (1) ──< users (N)
departments (1) ──< employees (N)

surveys (1) ──< survey_questions (N)
companies (1) ──< surveys (N) [nullable = global template]

companies (1) ──< survey_answers (N)
employees (1) ──< survey_answers (N)
survey_questions (1) ──< survey_answers (N)
```

## Sequence: extension check-in

```
Employee -> Extension Popup: open & fill survey
Extension -> API: GET /survey/questions?secret_key=...
API -> DB: resolve company by secret_key, load active survey
API --> Extension: questions[]

Extension -> API: POST /check-in {secret_key, employee, answers[]}
API -> DB: find/create employee
API -> Service: validate questions, calculate scores
API -> DB: insert survey_answers
API -> Queue: ProcessCheckInAnalyticsJob
API --> Extension: 201 Created
Extension -> local storage: mark lastCheckInDate
```

## Security features

- Rate limiting (`extension`: 30/min, `login`: 5/min)
- Form Request validation
- CORS with extension origin patterns
- Secure HTTP headers middleware
- Eloquent ORM (SQL injection protection)
- Blade escaping (XSS protection)
- API versioning (`/api/v1/...`)
- Multi-tenant isolation by `company_id`

## Analytics

- Average mood score (scorable questions)
- Burnout risk indicator (7-day window + trend)
- Daily/weekly trends
- Department overview (architecture ready)
- Employee history timeline

## Scaling roadmap

1. **Departments**: already modeled (`departments`, `employees.department_id`)
2. **Pre-aggregates**: materialized daily metrics table + cache tags
3. **Tenant isolation**: row-level scopes + dedicated DB per enterprise tier
4. **SSO**: SAML/OIDC for dashboard users
5. **Extension auth**: rotate secret keys + HMAC signed payloads
6. **Observability**: OpenTelemetry, structured logs, Sentry
7. **Horizontal scale**: stateless app replicas, Redis cluster, read replicas
8. **Notifications**: Slack/Email alerts on high burnout risk
9. **Privacy**: anonymization layer + consent management (GDPR)
10. **Microservices split**: analytics worker service when load grows

## Makefile commands

| Command | Description |
|---------|-------------|
| `make up` | Start containers |
| `make down` | Stop containers |
| `make setup` | Full bootstrap |
| `make migrate` | Run migrations |
| `make seed` | Seed demo data |
| `make fresh` | Reset DB + seed |
| `make shell` | Shell into app container |
| `make logs` | Tail logs |

## Tests

```bash
docker compose exec app php artisan test
```

---

Built with PHP 8.3, Laravel 12, PostgreSQL, Redis, Nginx, Docker.
