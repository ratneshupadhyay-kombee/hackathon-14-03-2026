import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter } from 'k6/metrics';

// Custom metrics to track specific outcomes
const loginSuccess = new Counter('login_success');
const orderCreated = new Counter('order_created');

export const options = {
    // Spike Scenario: Rapidly scale up, stay, then scale down
    stages: [
        { duration: '30s', target: 50 },  // Ramp up to 50 users
        { duration: '1m', target: 100 },  // Spike to 100 concurrent users
        { duration: '30s', target: 100 }, // Stay at 100
        { duration: '30s', target: 0 },   // Ramp down
    ],
    thresholds: {
        http_req_failed: ['rate<0.01'], // http errors should be less than 1%
        http_req_duration: ['p(95)<2000'], // 95% of requests should be below 2s
    },
};

const BASE_URL = 'http://localhost:8001'; // If running from host. Use http://hackathon_nginx if running from another container.

export default function () {
    // 1. Perform Auto-Login (Performance bypass)
    let res = http.get(`${BASE_URL}/test-observability/auto-login`);
    
    check(res, {
        'authenticated successfully': (r) => r.status === 200,
    }) && loginSuccess.add(1);

    sleep(1);

    // 2. Create Order via the Stress-Test Endpoint
    const orderPayload = JSON.stringify({
        item: 'Load Test Product',
        amount: Math.floor(Math.random() * 500),
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    res = http.post(`${BASE_URL}/test-observability/stress-order`, orderPayload, params);

    check(res, {
        'order created': (r) => r.status === 200,
        'has order_id': (r) => r.json().order_id !== undefined,
    }) && orderCreated.add(1);

    // 3. Visit Dashboard to simulate app browsing
    http.get(`${BASE_URL}/dashboard`);

    sleep(Math.random() * 2 + 1); // Think time
}
