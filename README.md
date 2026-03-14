# Laravel Observability Stack - Hackathon Project

A complete Laravel 12 application with full LGTM (Loki, Grafana, Tempo, Prometheus) observability stack for monitoring, logging, tracing, and metrics.

## 🎯 Project Overview

This is a production-ready Laravel application featuring:
- **Order & Product Management** with Livewire 3 + PowerGrid v6.9.2
- **Full LGTM Observability Stack** (Loki, Grafana, Tempo, Prometheus)
- **Real-time Metrics** with custom Prometheus exporters
- **Structured JSON Logging** shipped to Loki via Promtail
- **Distributed Tracing** with OpenTelemetry → Tempo
- **3 Custom Grafana Dashboards** for application health, database performance, and log analysis

---

## 📊 Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Application                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Orders     │  │   Products   │  │  Dashboard   │     │
│  │  (Livewire)  │  │  (Livewire)  │  │   (Stats)    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         Observability Middleware Layer               │  │
│  │  • PrometheusMetricsMiddleware (HTTP metrics)       │  │
│  │  • OpenTelemetryMiddleware (Distributed tracing)    │  │
│  │  • LogContextMiddleware (Structured logging)        │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Prometheus  │    │     Loki     │    │    Tempo     │
│  (Metrics)   │    │    (Logs)    │    │   (Traces)   │
│   :9090      │    │    :3100     │    │    :3200     │
└──────────────┘    └──────────────┘    └──────────────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            ▼
                    ┌──────────────┐
                    │   Grafana    │
                    │ (Dashboards) │
                    │    :3000     │
                    └──────────────┘
```

---

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd hackathon-14-03-2026
```

2. **Start all services**
```bash
docker-compose up -d
```

3. **Install dependencies (if needed)**
```bash
docker exec hackathon_app composer install
docker exec hackathon_app npm install
docker exec hackathon_app npm run build
```

4. **Run migrations**
```bash
docker exec hackathon_app php artisan migrate --seed
```

5. **Access the application**
- **Laravel App:** http://localhost:8001
- **Grafana:** http://localhost:3000 (admin/admin)
- **Prometheus:** http://localhost:9090
- **Loki:** http://localhost:3100

---

## 📦 Services & Ports

| Service | Port | Description |
|---------|------|-------------|
| Laravel App (Nginx) | 8001 | Main application |
| Grafana | 3000 | Visualization & dashboards |
| Prometheus | 9090 | Metrics collection |
| Loki | 3100 | Log aggregation |
| Tempo | 3200, 4317, 4318 | Distributed tracing |
| MySQL | 3308 | Database |

---

## 📈 Grafana Dashboards

### 1. Application Health Dashboard
**URL:** http://localhost:3000/d/laravel-app-health

**Panels:**
- Requests Per Minute
- Error Rate % (gauge)
- P95 Response Latency
- Top 10 Slowest Endpoints
- Orders Created (stat)
- Login Failures (stat)
- HTTP Status Code Breakdown (pie chart)
- Test Suite Activity & Latency

### 2. Database Performance Dashboard
**URL:** http://localhost:3000/d/laravel-db-performance

**Panels:**
- Avg DB Query Duration (ms)
- HTTP Response Time P95
- Slow Queries Log Stream (> 100ms)
- DB Error Count (last 5m)
- Total HTTP Requests
- N+1 Query Test Latency
- DB Error Log Stream

### 3. Logs Analysis Dashboard
**URL:** http://localhost:3000/d/laravel-logs-analysis

**Panels:**
- Login Failures Over Time
- Log Count by Severity (pie chart)
- Validation Errors (live stream)
- Error & Critical Logs (live stream)
- Total Errors (last 1h)
- Login Failures (last 1h)
- Orders Created (last 1h)
- Validation Errors (last 1h)
- All Logs (full stream)
- Logs Filtered by trace_id

---

## 🧪 Testing & Demo Routes

### Seed All Metrics at Once
```bash
docker exec hackathon_app php artisan metrics:seed
```
This creates 8 orders, runs 45 DB queries, and triggers 1 DB error.

### Individual Test Routes

| Route | Purpose | Dashboard Impact |
|-------|---------|------------------|
| `/test-observability/seed-counters` | Creates 5 orders | Orders Created counter |
| `/test-observability/db-load` | Runs 50 SELECT queries | Avg DB Query Duration |
| `/test-observability/db-error` | Triggers DB error | DB Error Count |
| `/test-observability/slow` | 1-3 second delay | P95 Latency |
| `/test-observability/error` | Random 500 error | Error Rate % |
| `/test-observability/heavy` | 2MB response | Saturation testing |

**Quick Test Script:**
```bash
# Generate comprehensive test data
curl http://localhost:8001/test-observability/seed-counters
curl http://localhost:8001/test-observability/db-load
curl http://localhost:8001/test-observability/db-error
curl http://localhost:8001/test-observability/slow
curl http://localhost:8001/test-observability/error
```

---

## 🔧 Key Features

### 1. Prometheus Metrics
**Endpoint:** http://localhost:8001/metrics

**Tracked Metrics:**
- `laravel_orders_created_total` - Total orders created
- `laravel_products_created_total` - Total products created
- `laravel_login_failed_total` - Failed login attempts
- `laravel_validation_errors_total` - Validation errors
- `laravel_http_requests_total` - Total HTTP requests
- `laravel_http_errors_total` - HTTP errors (4xx/5xx)
- `laravel_http_response_time_seconds` - Response time histogram

**Storage:** File-based (`storage/metrics.json`) - persists across PHP-FPM requests without Redis/APCu.

### 2. Structured Logging
**Log File:** `storage/logs/laravel-json.log`

**Log Format:** JSON with context fields
```json
{
  "message": "DB query executed",
  "context": {
    "trace_id": "abc-123",
    "user_id": "guest",
    "endpoint": "GET /orders",
    "db_execution_time_ms": 12.5,
    "db_statement": "SELECT * FROM orders"
  },
  "level": 100,
  "level_name": "DEBUG"
}
```

**Shipped to Loki via Promtail** - queries available in Grafana with LogQL.

### 3. Distributed Tracing
**OpenTelemetry → Tempo**

- HTTP request spans
- DB query spans with execution time
- Custom service spans (e.g., OrderProcessingService)
- Trace IDs in logs for correlation

### 4. Database Query Monitoring
All DB queries are logged with:
- `db_execution_time_ms` - Query duration
- `db_statement` - SQL query
- `db_connection` - Connection name
- `trace_id` - For correlation with traces

**Slow Query Detection:** Queries > 100ms highlighted in dashboard.

---

## 🏗️ Application Features

### Order Management
- Create, Read, Update, Delete orders
- Real-time table with PowerGrid
- Edit modal with validation
- Status tracking (pending, processing, completed, cancelled)

### Product Management
- Create, Read, Update, Delete products
- Real-time table with PowerGrid
- Edit modal with validation
- Stock tracking

### Dashboard
- Total orders count
- Total products count
- Recent orders list
- Quick stats

---

## 🛠️ Technical Stack

### Backend
- **Laravel 12** - PHP framework
- **Livewire 3** - Full-stack framework
- **PowerGrid v6.9.2** - Data tables
- **MySQL 8.0** - Database
- **OpenTelemetry SDK** - Distributed tracing
- **Prometheus Client PHP** - Metrics export

### Frontend
- **Tailwind CSS** - Styling
- **Alpine.js** - JavaScript framework (via Livewire)
- **Blade Templates** - Templating engine

### Observability
- **Prometheus** - Metrics collection & storage
- **Loki** - Log aggregation
- **Tempo** - Distributed tracing
- **Grafana** - Visualization & dashboards
- **Promtail** - Log shipping agent

### Infrastructure
- **Docker & Docker Compose** - Containerization
- **Nginx** - Web server
- **PHP-FPM 8.4** - PHP runtime

---

## 📁 Project Structure

```
.
├── app/
│   ├── Console/Commands/
│   │   └── SeedMetrics.php          # Artisan command to seed metrics
│   ├── Http/
│   │   ├── Controllers/             # Controllers
│   │   └── Middleware/
│   │       ├── LogContextMiddleware.php
│   │       ├── OpenTelemetryMiddleware.php
│   │       └── PrometheusMetricsMiddleware.php
│   ├── Livewire/                    # Livewire components
│   │   ├── OrderForm.php
│   │   ├── OrderTable.php
│   │   ├── ProductForm.php
│   │   └── ProductTable.php
│   ├── Models/                      # Eloquent models
│   ├── Providers/
│   │   ├── AppServiceProvider.php   # Metrics event listeners
│   │   └── OpenTelemetryServiceProvider.php
│   └── Services/
│       ├── MetricsService.php       # File-based Prometheus metrics
│       └── OrderProcessingService.php
├── docker/
│   ├── grafana/provisioning/
│   │   ├── dashboards/              # 3 custom dashboards
│   │   └── datasources/             # Prometheus, Loki, Tempo
│   ├── loki/                        # Loki config
│   ├── nginx/                       # Nginx config
│   ├── prometheus/                  # Prometheus config
│   ├── promtail/                    # Promtail config
│   └── tempo/                       # Tempo config
├── routes/
│   └── web.php                      # Routes + test endpoints
├── storage/
│   ├── logs/laravel-json.log        # JSON logs
│   └── metrics.json                 # Prometheus metrics storage
├── docker-compose.yml               # All services
├── Dockerfile                       # Laravel app container
├── GRAFANA_TEST_ROUTES.md          # Test routes reference
└── README.md                        # This file
```

---

## 🔍 Observability Flow

### 1. HTTP Request Flow
```
User Request
    ↓
Nginx (:8001)
    ↓
PHP-FPM (Laravel)
    ↓
PrometheusMetricsMiddleware → Increment counters, observe latency
    ↓
OpenTelemetryMiddleware → Create span, send to Tempo
    ↓
LogContextMiddleware → Add trace_id, user_id to logs
    ↓
Controller/Livewire Component
    ↓
DB Query → Log with db_execution_time_ms
    ↓
Response
```

### 2. Metrics Flow
```
Event (Order Created, HTTP Request, etc.)
    ↓
MetricsService → Increment counter in storage/metrics.json
    ↓
Prometheus scrapes /metrics endpoint (every 15s)
    ↓
Grafana queries Prometheus
    ↓
Dashboard displays metrics
```

### 3. Logs Flow
```
Log::info/debug/error()
    ↓
Monolog → Format as JSON
    ↓
Write to storage/logs/laravel-json.log
    ↓
Promtail tails file (every 10-30s)
    ↓
Ship to Loki
    ↓
Grafana queries Loki with LogQL
    ↓
Dashboard displays logs
```

### 4. Traces Flow
```
HTTP Request
    ↓
OpenTelemetry Tracer → Create span
    ↓
DB::listen() → Create child span for each query
    ↓
Export to Tempo via OTLP HTTP (port 4318)
    ↓
Grafana queries Tempo
    ↓
View trace timeline
```

---

## 🐛 Troubleshooting

### Dashboards showing "No Data"

1. **Check Prometheus is scraping:**
```bash
curl http://localhost:9090/api/v1/targets
```
Should show `laravel-app` target as `up`.

2. **Check metrics endpoint:**
```bash
curl http://localhost:8001/metrics
```
Should return Prometheus format metrics.

3. **Generate test data:**
```bash
docker exec hackathon_app php artisan metrics:seed
```

### Logs not appearing in Grafana

1. **Check Promtail is running:**
```bash
docker logs hackathon_promtail --tail 20
```

2. **Check log file exists:**
```bash
docker exec hackathon_app ls -lh storage/logs/laravel-json.log
```

3. **Query Loki directly:**
```bash
curl "http://localhost:3100/loki/api/v1/label/job/values"
```
Should return `["laravel_app"]`.

### Containers not starting

```bash
# Check container status
docker ps -a

# View logs for specific service
docker logs hackathon_app
docker logs hackathon_grafana

# Restart all services
docker-compose down
docker-compose up -d
```

---

## 📝 Configuration Files

### Environment Variables (.env)
```env
DB_HOST=db                    # Docker MySQL service
REDIS_HOST=redis              # Docker Redis service (not used for metrics)
LOG_STACK=loki                # Use Loki logging stack
LOG_LEVEL=debug               # Log DB queries
```

### Prometheus Config
```yaml
scrape_configs:
  - job_name: 'laravel-app'
    metrics_path: '/metrics'
    static_configs:
      - targets: ['host.docker.internal:8001']
```

### Loki Config
- Retention: 168h (7 days)
- Ingestion rate: 4MB/s
- Query timeout: 5m

### Grafana Datasources
- **Prometheus:** http://hackathon_prometheus:9090
- **Loki:** http://hackathon_loki:3100
- **Tempo:** http://hackathon_tempo:3200

---

## 🎥 Demo Video Checklist

1. ✅ Show application running (Orders & Products CRUD)
2. ✅ Open Grafana dashboards (all 3)
3. ✅ Run `php artisan metrics:seed` to generate data
4. ✅ Show metrics updating in real-time
5. ✅ Click test route links in Application Health dashboard
6. ✅ Show DB query logs with execution times
7. ✅ Show error logs and DB error count
8. ✅ Show slow queries panel
9. ✅ Demonstrate trace correlation with trace_id
10. ✅ Show Prometheus /metrics endpoint

---

## 🤝 Contributing

This is a hackathon project. For production use, consider:
- Adding authentication to Grafana
- Securing Prometheus/Loki endpoints
- Implementing proper log retention policies
- Adding alerting rules
- Setting up backup strategies

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 🙏 Acknowledgments

- Laravel Framework
- Grafana Labs (LGTM Stack)
- OpenTelemetry Community
- Prometheus Community
- PowerGrid Team

---

**Built with ❤️ for the Hackathon - March 14, 2026**

Here is the video link : https://softwareitconsulting-my.sharepoint.com/:v:/g/personal/ratnesh_upadhyay_kombee_com/IQBGU3nauXbCRKpILJyUYdEgAZDBi1Uf0JwWBSUMCtS5q-M?nav=eyJyZWZlcnJhbEluZm8iOnsicmVmZXJyYWxBcHAiOiJPbmVEcml2ZUZvckJ1c2luZXNzIiwicmVmZXJyYWxBcHBQbGF0Zm9ybSI6IldlYiIsInJlZmVycmFsTW9kZSI6InZpZXciLCJyZWZlcnJhbFZpZXciOiJNeUZpbGVzTGlua0NvcHkifX0&e=NxYLKj