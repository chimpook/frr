# Fire Risk Findings CRUD Application

A full-stack CRUD application for managing fire risk findings.

## Tech Stack

- **Backend**: Symfony 7.x + PHP 8.2 + Doctrine ORM
- **Frontend**: Vue 3 + Vuetify 3 + TypeScript + Webpack
- **Database**: MariaDB 11.x
- **Web Server**: OpenLiteSpeed
- **Orchestration**: Docker + docker-compose

## Data Model

| Field | Type | Description |
|-------|------|-------------|
| id | string | Auto-generated (SF1, SF2, SF3...) |
| location | text | Location of the finding |
| risk_range | enum | Low, Medium, or High |
| comment | text | Detailed comment |
| recommendations | text | Recommended actions |
| resolved | boolean | Resolution status (default: false) |
| created_at | timestamp | Auto-generated |
| updated_at | timestamp | Auto-updated |

## Quick Start

### Prerequisites

- Docker and Docker Compose installed
- Ports 3000 and 8080 available

### Setup

1. Clone the repository and navigate to the project directory:

```bash
cd /path/to/project
```

2. Copy the environment file:

```bash
cp .env.example .env
```

3. Start the services:

```bash
docker-compose up -d
```

4. Wait for services to be healthy, then run database migrations:

```bash
docker-compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

5. Access the application:
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8080/api

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/findings | List all findings (paginated) |
| GET | /api/findings/{id} | Get a single finding |
| POST | /api/findings | Create a new finding |
| PUT | /api/findings/{id} | Update a finding |
| DELETE | /api/findings/{id} | Delete a finding |
| GET | /api/health | Health check endpoint |

### Query Parameters

- `page`: Page number (default: 1)
- `limit`: Items per page (default: 10, max: 100)

### Example API Requests

**Create a finding:**
```bash
curl -X POST http://localhost:8080/api/findings \
  -H "Content-Type: application/json" \
  -d '{
    "location": "Building A, Floor 2",
    "risk_range": "High",
    "comment": "Fire extinguisher expired",
    "recommendations": "Replace fire extinguisher immediately"
  }'
```

**List findings:**
```bash
curl http://localhost:8080/api/findings?page=1&limit=10
```

**Get a single finding:**
```bash
curl http://localhost:8080/api/findings/SF1
```

**Update a finding:**
```bash
curl -X PUT http://localhost:8080/api/findings/SF1 \
  -H "Content-Type: application/json" \
  -d '{"resolved": true}'
```

**Delete a finding:**
```bash
curl -X DELETE http://localhost:8080/api/findings/SF1
```

**Health check:**
```bash
curl http://localhost:8080/api/health
```

## Development

### Backend Development

The backend code is mounted as a volume, so changes are reflected immediately. To install new Composer packages:

```bash
docker-compose exec backend composer require package-name
```

To clear the Symfony cache:

```bash
docker-compose exec backend php bin/console cache:clear
```

### Frontend Development

For local frontend development with hot reloading:

```bash
cd frontend
npm install
npm run dev
```

The dev server will proxy API requests to the backend.

### Running Migrations

Create a new migration:

```bash
docker-compose exec backend php bin/console make:migration
```

Run migrations:

```bash
docker-compose exec backend php bin/console doctrine:migrations:migrate
```

## Docker Services

| Service | Port | Description |
|---------|------|-------------|
| frontend | 3000 | Vue.js application (OpenLiteSpeed) |
| backend | 8080 | Symfony API (OpenLiteSpeed + PHP 8.2) |
| database | 3306 (internal) | MariaDB 11.x |

## Stopping the Application

```bash
docker-compose down
```

To remove all data (including database):

```bash
docker-compose down -v
```

## Troubleshooting

### Backend not starting

Check the logs:
```bash
docker-compose logs backend
```

### Database connection issues

Ensure the database is healthy:
```bash
docker-compose ps
```

Wait for the database to be ready before running migrations.

### Clear all caches

```bash
docker-compose exec backend php bin/console cache:clear
docker-compose restart backend
```
