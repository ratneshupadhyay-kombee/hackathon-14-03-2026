# Grafana Dashboard Test Routes

All dashboards are now fully populated with data. Use these routes to generate more traffic for demo purposes.

## Quick Test Commands (Run from project root)

### Seed All Metrics at Once
```bash
docker exec hackathon_app php artisan metrics:seed
```
This creates 8 orders, runs 45 DB queries (including slow ones), and triggers 1 DB error.

### Individual Test Routes

**Seed Order Counters:**
```
http://localhost:8001/test-observability/seed-counters
```
Creates 5 orders, increments `laravel_orders_created_total`

**Generate DB Query Logs:**
```
http://localhost:8001/test-observability/db-load
```
Runs 50 SELECT queries, populates "Avg DB Query Duration" panel

**Trigger DB Error:**
```
http://localhost:8001/test-observability/db-error
```
Generates a DB error log, populates "DB Error Count" panel

**Trigger Slow Request:**
```
http://localhost:8001/test-observability/slow
```
Random 1-3 second delay, populates latency panels

**Trigger Random Error:**
```
http://localhost:8001/test-observability/error
```
50% chance of 500 error, populates error rate panels

**Heavy Payload:**
```
http://localhost:8001/test-observability/heavy
```
Returns 2MB response, tests saturation

## Dashboard URLs

- **Application Health:** http://localhost:3000/d/laravel-app-health
- **Database Performance:** http://localhost:3000/d/laravel-db-performance
- **Logs Analysis:** http://localhost:3000/d/laravel-logs-analysis
- **Prometheus:** http://localhost:9090
- **Metrics Endpoint:** http://localhost:8001/metrics

## Current Metrics Status

✅ **Orders Created:** 21+ orders (counter persists across requests)
✅ **HTTP Requests:** All requests tracked with response time histogram
✅ **HTTP Errors:** 500 errors tracked
✅ **DB Query Logs:** All queries logged with `db_execution_time_ms` field
✅ **DB Errors:** DB errors logged and queryable in Loki
✅ **Slow Queries:** Queries > 100ms visible in "Slow Queries Log Stream"

## Notes

- Metrics persist to `storage/metrics.json` (file-based, no Redis/APCu needed)
- Logs ship to Loki via Promtail every ~10-30 seconds
- Prometheus scrapes `/metrics` every 15 seconds
- All dashboards refresh every 30 seconds
