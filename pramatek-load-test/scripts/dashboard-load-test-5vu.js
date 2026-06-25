import http from "k6/http";
import { check, sleep } from "k6";

// ===== KONFIGURASI TEST - 5 VU =====
export const options = {
    stages: [
        { duration: "10s", target: 5 }, // Ramp-up: 0 -> 5 user
        { duration: "1m", target: 5 }, // Steady: tetap di 5 user
        { duration: "10s", target: 0 }, // Ramp-down: 5 -> 0 user
    ],
    thresholds: {
        http_req_duration: ["p(95)<2000"],
        http_req_failed: ["rate<0.01"],
    },
};

const BASE_URL = "http://localhost:8000";
const EMAIL = "admin@gmail.com";
const PASSWORD = "12345";

function extractCsrfToken(html) {
    const match = html.match(/name="_token"\s+value="([^"]+)"/);
    return match ? match[1] : null;
}

export default function () {
    const loginPageRes = http.get(`${BASE_URL}/login`);

    check(loginPageRes, {
        "Login page loaded": (r) => r.status === 200,
    });

    const csrfToken = extractCsrfToken(loginPageRes.body);

    if (!csrfToken) {
        console.error("CSRF token tidak ditemukan!");
        return;
    }

    const loginPayload = {
        _token: csrfToken,
        email: EMAIL,
        password: PASSWORD,
    };

    const loginRes = http.post(`${BASE_URL}/login`, loginPayload, {
        redirects: 0,
    });

    check(loginRes, {
        "Login berhasil (redirect 302)": (r) => r.status === 302,
    });

    sleep(1);

    const dashboardRes = http.get(`${BASE_URL}/dashboard`);

    check(dashboardRes, {
        "Dashboard berhasil dimuat": (r) => r.status === 200,
        "Bukan ke-redirect ke login (auth gagal)": (r) =>
            !r.url.includes("/login"),
    });

    sleep(Math.random() * 3 + 2);
}
