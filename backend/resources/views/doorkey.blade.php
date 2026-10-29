<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DoorKey</title>
    <style>
        :root {
            --bg: #0b1220;
            --panel: #111d2f;
            --panel-soft: #121f36;
            --primary: #2d7df6;
            --primary-dark: #1b2d48;
            --success: #4ecb9a;
            --success-dark: #143326;
            --danger: #652d3b;
            --danger-soft: #3a2028;
            --text: #eef5ff;
            --muted: #aebfda;
            --label: #c4d2e7;
            --border: #1c2e4f;
            --warning: #ffd166;
            --warning-soft: #332611;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 18px 60px;
        }

        .screen { display: none; }
        .screen.active { display: block; }

        .auth-card, .card, .metric, .property-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
        }

        .auth-card {
            max-width: 620px;
            margin: 40px auto;
            padding: 22px;
        }

        .eyebrow {
            color: #8fb4ff;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 8px;
        }

        h1, h2, h3, p { margin-top: 0; }
        h1 { font-size: 2.1rem; margin-bottom: 12px; }
        h2 { font-size: 1.7rem; margin-bottom: 14px; }
        h3 { font-size: 1.1rem; }

        .muted { color: var(--muted); }
        .small { font-size: 12px; }

        .field {
            margin-bottom: 14px;
        }

        label {
            display: block;
            color: var(--label);
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid #2a3d5d;
            background: var(--panel-soft);
            color: var(--text);
            font-size: 16px;
        }

        .btn, button {
            border: none;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn.primary, button.primary {
            background: var(--primary);
            color: white;
        }

        .btn.secondary, button.secondary {
            background: var(--primary-dark);
            color: var(--text);
        }

        .btn.success, button.success {
            background: var(--success);
            color: #061611;
        }

        .btn.danger, button.danger {
            background: var(--danger);
            color: white;
        }

        .btn.full {
            width: 100%;
            margin-top: 8px;
        }

        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .switch-link {
            margin-top: 12px;
            text-align: center;
        }

        .demo-card {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 12px;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .brand {
            font-size: 2rem;
            font-weight: 800;
        }

        .user-chip {
            padding: 10px 14px;
            border-radius: 12px;
            background: var(--primary-dark);
            color: var(--text);
            font-weight: 700;
        }

        .role-header {
            color: var(--muted);
            margin-bottom: 8px;
        }

        .section {
            margin-top: 18px;
        }

        .property-grid, .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .property-card, .card, .metric {
            padding: 16px;
        }

        .property-card h3, .card h3 { margin-bottom: 8px; }
        .property-header, .card-header {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
        }

        .price {
            color: #9be7c2;
            font-weight: 800;
        }

        .status {
            display: inline-block;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 12px;
            font-weight: 800;
        }

        .status.pending { background: var(--warning-soft); color: var(--warning); }
        .status.approved { background: var(--success-dark); color: #98edbf; }
        .status.denied { background: var(--danger-soft); color: #ffb2b2; }

        .code-box {
            margin-top: 8px;
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: 8px;
            color: #9be7c2;
        }

        .empty {
            color: var(--muted);
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
        }

        .input-row {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .input-row input {
            flex: 1;
        }

        .metric {
            min-height: 100px;
        }

        .metric .value {
            font-size: 2rem;
            font-weight: 800;
            margin-top: 12px;
        }

        .account-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .toast {
            position: fixed;
            right: 16px;
            bottom: 18px;
            background: #1a2b3d;
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            opacity: 0;
            transform: translateY(16px);
            transition: 0.25s ease;
            pointer-events: none;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
        @media (max-width: 640px) {
            .topbar { flex-direction: column; align-items: flex-start; }
            .code-box { letter-spacing: 5px; font-size: 1.6rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="authScreen" class="screen active">
            <div class="auth-card">
                <div class="eyebrow">Smart stay platform</div>
                <h1>DoorKey</h1>
                <p class="muted">Welcome back. Guests can register, hosts approve check-ins, and admins manage the platform.</p>

                <form id="authForm">
                    <div id="nameField" class="field" style="display:none;">
                        <label for="nameInput">Full name</label>
                        <input id="nameInput" type="text" placeholder="Your name" />
                    </div>

                    <div class="field">
                        <label for="emailInput">Email</label>
                        <input id="emailInput" type="email" placeholder="you@example.com" />
                    </div>

                    <div class="field">
                        <label for="passwordInput">Password</label>
                        <input id="passwordInput" type="password" placeholder="Enter password" />
                    </div>

                    <button id="submitAuthBtn" class="btn primary full" type="submit">Sign in</button>
                    <button id="toggleAuthModeBtn" class="btn secondary full" type="button">Register as a guest</button>
                </form>

                <div class="demo-card">
                    <h3>Demo accounts</h3>
                    <p class="small muted">Host: host@doorkey.demo / host123</p>
                    <p class="small muted">Admin: admin@doorkey.demo / admin123</p>
                </div>
            </div>
        </div>

        <div id="dashboardScreen" class="screen">
            <div class="topbar">
                <div>
                    <div class="role-header" id="roleLabel">Guest account</div>
                    <div class="brand">DoorKey</div>
                </div>
                <div class="user-chip" id="userNameLabel">Guest</div>
                <button class="btn secondary" type="button" id="logoutBtn">Sign out</button>
            </div>

            <h2 id="welcomeText">Welcome</h2>

            <div id="guestSection" class="section">
                <h3>Browse stays</h3>
                <div id="propertiesList" class="property-grid"></div>
                <div class="section">
                    <h3>My bookings</h3>
                    <div id="guestBookingsList"></div>
                </div>
            </div>

            <div id="hostSection" class="section">
                <h3>Booking requests</h3>
                <div id="hostBookingsList"></div>
            </div>

            <div id="adminSection" class="section">
                <h3>Operations overview</h3>
                <div id="metricsList" class="metric-grid"></div>

                <div class="section">
                    <h3>All bookings</h3>
                    <div id="adminBookingsList"></div>
                </div>

                <div class="section">
                    <h3>Account controls</h3>
                    <div id="adminUsersList"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const listings = [
            { id: 'skyline', title: 'Skyline Loft', city: 'New York', price: 220, details: '2 guests · 1 bed · Fast Wi‑Fi', host_id: 1 },
            { id: 'harbor', title: 'Harbor View', city: 'Seattle', price: 185, details: '4 guests · 2 beds · EV charging', host_id: 1 },
            { id: 'sunset', title: 'Sunset A-Frame', city: 'Austin', price: 260, details: '3 guests · Patio · Smart lock', host_id: 1 },
        ];

        const state = {
            token: localStorage.getItem('doorkey_token') || '',
            user: JSON.parse(localStorage.getItem('doorkey_user') || 'null'),
            bookingList: [],
            metrics: {},
            users: [],
            isRegistering: false,
        };

        const authScreen = document.getElementById('authScreen');
        const dashboardScreen = document.getElementById('dashboardScreen');
        const nameField = document.getElementById('nameField');
        const authForm = document.getElementById('authForm');
        const submitAuthBtn = document.getElementById('submitAuthBtn');
        const toggleAuthModeBtn = document.getElementById('toggleAuthModeBtn');
        const emailInput = document.getElementById('emailInput');
        const passwordInput = document.getElementById('passwordInput');
        const nameInput = document.getElementById('nameInput');
        const toast = document.getElementById('toast');

        function showToast(message) {
            toast.textContent = message;
            toast.classList.add('show');
            clearTimeout(showToast.timer);
            showToast.timer = setTimeout(() => toast.classList.remove('show'), 2600);
        }

        function setAuthMode(isRegistering) {
            state.isRegistering = isRegistering;
            nameField.style.display = isRegistering ? 'block' : 'none';
            submitAuthBtn.textContent = isRegistering ? 'Create guest account' : 'Sign in';
            toggleAuthModeBtn.textContent = isRegistering ? 'I already have an account' : 'Register as a guest';
        }

        function saveSession(token, user) {
            state.token = token;
            state.user = user;
            localStorage.setItem('doorkey_token', token);
            localStorage.setItem('doorkey_user', JSON.stringify(user));
        }

        function clearSession() {
            state.token = '';
            state.user = null;
            state.bookingList = [];
            state.metrics = {};
            state.users = [];
            localStorage.removeItem('doorkey_token');
            localStorage.removeItem('doorkey_user');
        }

        async function apiFetch(url, options = {}) {
            const headers = {
                Accept: 'application/json',
                ...(options.headers || {})
            };

            if (state.token) {
                headers.Authorization = `Bearer ${state.token}`;
            }

            if (options.body) {
                headers['Content-Type'] = 'application/json';
            }

            const response = await fetch(url, {
                method: options.method || 'GET',
                headers,
                body: options.body ? JSON.stringify(options.body) : undefined,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }

            return data;
        }

        async function handleSubmit(event) {
            event.preventDefault();
            const email = emailInput.value.trim();
            const password = passwordInput.value;

            try {
                let result;
                if (state.isRegistering) {
                    const name = nameInput.value.trim();
                    if (!name || !email || !password) {
                        showToast('Enter your name, email, and password.');
                        return;
                    }
                    result = await apiFetch('/api/register', {
                        method: 'POST',
                        body: { name, email, password }
                    });
                } else {
                    if (!email || !password) {
                        showToast('Enter your email and password.');
                        return;
                    }
                    result = await apiFetch('/api/login', {
                        method: 'POST',
                        body: { email, password }
                    });
                }

                saveSession(result.token, result.user);
                emailInput.value = '';
                passwordInput.value = '';
                nameInput.value = '';
                setAuthMode(false);
                renderDashboard();
            } catch (error) {
                showToast(error.message || 'Unable to continue');
            }
        }

        async function logout() {
            try {
                if (state.token) {
                    await apiFetch('/api/logout', { method: 'POST' });
                }
            } catch (error) {
                console.warn(error);
            }

            clearSession();
            renderDashboard();
        }

        async function loadBookings() {
            const bookings = await apiFetch('/api/bookings');
            state.bookingList = bookings;
            return bookings;
        }

        async function loadAdminData() {
            const [metrics, bookings, users] = await Promise.all([
                apiFetch('/api/admin/metrics'),
                apiFetch('/api/bookings'),
                apiFetch('/api/admin/users')
            ]);
            state.metrics = metrics;
            state.bookingList = bookings;
            state.users = users;
        }

        function renderProperties() {
            const guestList = document.getElementById('propertiesList');
            guestList.innerHTML = '';

            listings.forEach((listing) => {
                const hasBooking = state.bookingList.some((booking) => booking.listing_id === listing.id && booking.status !== 'Denied');
                const item = document.createElement('div');
                item.className = 'property-card';
                item.innerHTML = `
                    <div class="property-header">
                        <h3>${listing.title}</h3>
                        <span class="price">$${listing.price}</span>
                    </div>
                    <p class="muted">${listing.city}</p>
                    <p class="muted">${listing.details}</p>
                    <button class="btn primary full" data-listing='${JSON.stringify(listing)}' ${hasBooking ? 'disabled' : ''}>
                        ${hasBooking ? 'Unavailable' : 'Book this stay'}
                    </button>
                `;
                item.querySelector('button').addEventListener('click', async () => {
                    const listingData = JSON.parse(item.querySelector('button').dataset.listing);
                    await bookListing(listingData);
                });
                guestList.appendChild(item);
            });
        }

        function renderGuestBookings() {
            const guestBookingsList = document.getElementById('guestBookingsList');
            const myBookings = state.bookingList.filter((booking) => booking.guest_id === state.user.id);
            guestBookingsList.innerHTML = '';

            if (!myBookings.length) {
                guestBookingsList.innerHTML = '<div class="empty">Your bookings and access codes will appear here.</div>';
                return;
            }

            myBookings.forEach((booking) => {
                const card = document.createElement('div');
                card.className = 'card';
                card.innerHTML = `
                    <div class="card-header">
                        <h3>${booking.listing_title}</h3>
                        <span class="status ${booking.status.toLowerCase()}">${booking.status}</span>
                    </div>
                    <p class="muted">Your booking access code</p>
                    <div class="code-box">${booking.code}</div>
                    ${booking.go_enabled ? '<button class="btn success full" type="button">Go — check in</button>' : '<p class="muted">Waiting for a host to match and approve the code.</p>'}
                `;

                const button = card.querySelector('button');
                if (button) {
                    button.addEventListener('click', () => showToast(`Check-in ready for ${booking.listing_title}.`));
                }
                guestBookingsList.appendChild(card);
            });
        }

        function renderHostBookings() {
            const hostBookingsList = document.getElementById('hostBookingsList');
            const hostBookings = state.bookingList.filter((booking) => booking.host_id === state.user.id || booking.host_id === null);
            hostBookingsList.innerHTML = '';

            if (!hostBookings.length) {
                hostBookingsList.innerHTML = '<div class="empty">New booking requests will appear here.</div>';
                return;
            }

            hostBookings.forEach((booking) => {
                const card = document.createElement('div');
                card.className = 'card';
                const codeValue = booking.code || '000000';
                const formHtml = booking.status === 'Pending' ? `
                    <div class="input-row">
                        <input type="text" maxlength="6" class="host-code-input" placeholder="Match code" value="" />
                        <button class="btn primary approve-btn" type="button">Approve</button>
                    </div>
                    <button class="btn danger full deny-btn" type="button">Deny booking</button>
                ` : '';

                card.innerHTML = `
                    <div class="card-header">
                        <h3>${booking.listing_title}</h3>
                        <span class="status ${booking.status.toLowerCase()}">${booking.status}</span>
                    </div>
                    <p class="muted">Guest: ${booking.guest_name}</p>
                    <p class="muted">Booking code: ${codeValue}</p>
                    ${formHtml || '<p class="muted">Approved — the guest now has the Go button enabled.</p>'}
                `;

                const codeInput = card.querySelector('.host-code-input');
                const approveBtn = card.querySelector('.approve-btn');
                const denyBtn = card.querySelector('.deny-btn');

                if (approveBtn) {
                    approveBtn.addEventListener('click', async () => {
                        try {
                            const inputValue = (codeInput ? codeInput.value : '').trim();
                            if (!inputValue || inputValue.length !== 6) {
                                showToast('Enter the guest’s 6-digit code.');
                                return;
                            }
                            await apiFetch(`/api/bookings/${booking.id}/approve`, {
                                method: 'POST',
                                body: { code: inputValue }
                            });
                            await loadBookings();
                            renderDashboard();
                            showToast('Booking approved successfully.');
                        } catch (error) {
                            showToast(error.message || 'Approval failed');
                        }
                    });
                }

                if (denyBtn) {
                    denyBtn.addEventListener('click', async () => {
                        try {
                            await apiFetch(`/api/admin/bookings/${booking.id}`, {
                                method: 'PATCH',
                                body: { status: 'Denied' }
                            });
                            await loadBookings();
                            renderDashboard();
                            showToast('Booking denied.');
                        } catch (error) {
                            showToast(error.message || 'Unable to deny booking');
                        }
                    });
                }

                hostBookingsList.appendChild(card);
            });
        }

        function renderMetrics() {
            const metricsList = document.getElementById('metricsList');
            metricsList.innerHTML = '';
            const items = [
                { label: 'Bookings', value: state.metrics.bookings || 0 },
                { label: 'Active', value: state.metrics.active_bookings || 0 },
                { label: 'Accounts', value: state.metrics.users || 0 },
                { label: 'Pending', value: state.metrics.pending || 0 },
            ];

            items.forEach((item) => {
                const metric = document.createElement('div');
                metric.className = 'metric';
                metric.innerHTML = `<div>${item.label}</div><div class="value">${item.value}</div>`;
                metricsList.appendChild(metric);
            });
        }

        function renderAdminBookings() {
            const adminBookingsList = document.getElementById('adminBookingsList');
            adminBookingsList.innerHTML = '';

            state.bookingList.forEach((booking) => {
                const card = document.createElement('div');
                card.className = 'card';
                card.innerHTML = `
                    <div class="card-header">
                        <h3>${booking.listing_title}</h3>
                        <span class="status ${booking.status.toLowerCase()}">${booking.status}</span>
                    </div>
                    <p class="muted">${booking.guest_name} · Code ${booking.code}</p>
                    <div class="input-row">
                        <button class="btn success" type="button" data-status="Approved">Approve</button>
                        <button class="btn danger" type="button" data-status="Denied">Deny</button>
                    </div>
                `;

                card.querySelector('[data-status="Approved"]').addEventListener('click', async () => {
                    try {
                        await apiFetch(`/api/admin/bookings/${booking.id}`, {
                            method: 'PATCH',
                            body: { status: 'Approved' }
                        });
                        await loadAdminData();
                        renderDashboard();
                    } catch (error) {
                        showToast(error.message || 'Unable to approve booking');
                    }
                });

                card.querySelector('[data-status="Denied"]').addEventListener('click', async () => {
                    try {
                        await apiFetch(`/api/admin/bookings/${booking.id}`, {
                            method: 'PATCH',
                            body: { status: 'Denied' }
                        });
                        await loadAdminData();
                        renderDashboard();
                    } catch (error) {
                        showToast(error.message || 'Unable to deny booking');
                    }
                });

                adminBookingsList.appendChild(card);
            });
        }

        function renderAdminUsers() {
            const adminUsersList = document.getElementById('adminUsersList');
            adminUsersList.innerHTML = '';

            state.users.forEach((person) => {
                const row = document.createElement('div');
                row.className = 'card';
                row.innerHTML = `
                    <div class="account-row">
                        <div>
                            <h3>${person.name}</h3>
                            <p class="muted">${person.email} • ${person.role}</p>
                        </div>
                        <span class="status ${person.active ? 'approved' : 'denied'}">${person.active ? 'Active' : 'Disabled'}</span>
                    </div>
                    <button class="btn secondary full" type="button">${person.active ? 'Disable account' : 'Enable account'}</button>
                `;

                row.querySelector('button').addEventListener('click', async () => {
                    try {
                        await apiFetch(`/api/admin/users/${person.id}`, { method: 'PATCH' });
                        await loadAdminData();
                        renderDashboard();
                    } catch (error) {
                        showToast(error.message || 'Unable to update account');
                    }
                });

                adminUsersList.appendChild(row);
            });
        }

        async function renderDashboard() {
            if (!state.user || !state.token) {
                authScreen.classList.add('active');
                dashboardScreen.classList.remove('active');
                return;
            }

            authScreen.classList.remove('active');
            dashboardScreen.classList.add('active');

            document.getElementById('roleLabel').textContent = `${state.user.role} account`;
            document.getElementById('userNameLabel').textContent = state.user.name;
            document.getElementById('welcomeText').textContent = state.user.role === 'Guest'
                ? 'Find a stay and manage your access'
                : state.user.role === 'Host'
                    ? 'Review and approve guest bookings'
                    : 'Platform administration';

            document.getElementById('guestSection').style.display = state.user.role === 'Guest' ? 'block' : 'none';
            document.getElementById('hostSection').style.display = state.user.role === 'Host' ? 'block' : 'none';
            document.getElementById('adminSection').style.display = state.user.role === 'Admin' ? 'block' : 'none';

            try {
                if (state.user.role === 'Guest') {
                    await loadBookings();
                    renderProperties();
                    renderGuestBookings();
                } else if (state.user.role === 'Host') {
                    await loadBookings();
                    renderHostBookings();
                } else {
                    await loadAdminData();
                    renderMetrics();
                    renderAdminBookings();
                    renderAdminUsers();
                }
            } catch (error) {
                showToast(error.message || 'Unable to load dashboard');
            }
        }

        async function bookListing(listing) {
            try {
                await apiFetch('/api/bookings', {
                    method: 'POST',
                    body: {
                        listing_id: listing.id,
                        listing_title: listing.title,
                        host_id: listing.host_id,
                        price: listing.price,
                        guest_name: state.user.name,
                    }
                });
                await loadBookings();
                renderDashboard();
                showToast(`Booking requested for ${listing.title}.`);
            } catch (error) {
                showToast(error.message || 'Booking failed');
            }
        }

        document.getElementById('toggleAuthModeBtn').addEventListener('click', () => setAuthMode(!state.isRegistering));
        authForm.addEventListener('submit', handleSubmit);
        document.getElementById('logoutBtn').addEventListener('click', logout);

        setAuthMode(false);
        if (state.user && state.token) {
            renderDashboard();
        } else {
            authScreen.classList.add('active');
            dashboardScreen.classList.remove('active');
        }
    </script>
</body>
</html>
