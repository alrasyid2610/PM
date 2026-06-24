import http from "k6/http";
import { check, sleep } from "k6";

// ===== KONFIGURASI TEST =====
export const options = {
    stages: [
        { duration: "30s", target: 30 }, // Ramp-up: 0 -> 30 user
        { duration: "2m", target: 30 }, // Steady: tetap di 30 user
        { duration: "30s", target: 0 }, // Ramp-down: 30 -> 0 user
    ],
    thresholds: {
        http_req_duration: ["p(95)<2000"], // 95% request harus di bawah 2 detik
        http_req_failed: ["rate<0.01"], // Error rate harus di bawah 1%
    },
};

const BASE_URL = "http://localhost:8000";
const EMAIL = "admin@gmail.com";
const PASSWORD = "12345";

// ===== HELPER: Extract CSRF Token dari HTML =====
function extractCsrfToken(html) {
    const match = html.match(/name="_token"\s+value="([^"]+)"/);
    return match ? match[1] : null;
}

// ===== MAIN TEST FUNCTION (dijalankan tiap VU) =====
export default function () {
    // ===== STEP 1: GET halaman login, ambil CSRF token =====
    const loginPageRes = http.get(`${BASE_URL}/login`);

    check(loginPageRes, {
        "Login page loaded": (r) => r.status === 200,
    });

    const csrfToken = extractCsrfToken(loginPageRes.body);

    if (!csrfToken) {
        console.error("CSRF token tidak ditemukan!");
        return;
    }

    // ===== STEP 2: POST login dengan credentials + token =====
    const loginPayload = {
        _token: csrfToken,
        email: EMAIL,
        password: PASSWORD,
    };

    const loginRes = http.post(`${BASE_URL}/login`, loginPayload, {
        redirects: 0, // Jangan auto-follow redirect, biar kita bisa cek statusnya
    });

    check(loginRes, {
        "Login berhasil (redirect 302)": (r) => r.status === 302,
    });

    sleep(1); // simulasi delay kecil antara login dan buka dashboard

    // ===== STEP 3: GET dashboard (pakai cookie session otomatis dari VU ini) =====
    const dashboardRes = http.get(`${BASE_URL}/dashboard`);

    check(dashboardRes, {
        "Dashboard berhasil dimuat": (r) => r.status === 200,
        "Bukan ke-redirect ke login (auth gagal)": (r) =>
            !r.url.includes("/login"),
    });

    // ===== STEP 4: Simulasi user "membaca" dashboard =====
    sleep(Math.random() * 3 + 2); // sleep random 2-5 detik
}
