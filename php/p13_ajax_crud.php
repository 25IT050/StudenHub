<?php
// =======================================================
// Practical 13: AJAX/Fetch-Based CRUD without Page Reload
// Features: Client-Side Fetch API (GET, POST, PUT, DELETE)
//           No full-page reloads, dynamic table rendering
//           Intermediate: Live search with 300ms debounce
//           Advanced: Centralized error handling, HTTP status codes,
//                     Toast notifications, and loading spinners
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$pageTitle = "Practical 13 - Asynchronous AJAX CRUD via Fetch API";
require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="margin: 0; font-size: 26px; color: #1e293b;">
                ⚡ AJAX/Fetch-Based CRUD (Zero Page Reload)
            </h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">
                Practical 13: Asynchronous REST Operations, 300ms Debounced Search, Modal Form, HTTP Status Feedback
            </p>
        </div>
        <button onclick="openModal('add')" class="btn btn-primary">
            ➕ Add Event via AJAX
        </button>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; max-width: 380px;"></div>

    <!-- Search & Filter Controls -->
    <div class="card" style="padding: 18px 24px;">
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; align-items: end;">
            <div class="form-field" style="margin: 0;">
                <label>🔍 Live Search (Debounced 300ms)</label>
                <input type="text" id="searchInput" placeholder="Type event title, venue, or keywords...">
            </div>

            <div class="form-field" style="margin: 0;">
                <label>🏷️ Category</label>
                <select id="categoryFilter" onchange="fetchEvents()">
                    <option value="">All Categories</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Competition">Competition</option>
                    <option value="Seminar">Seminar</option>
                    <option value="Exhibition">Exhibition</option>
                </select>
            </div>

            <div class="form-field" style="margin: 0;">
                <label>📌 Status</label>
                <select id="statusFilter" onchange="fetchEvents()">
                    <option value="">All Statuses</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <div>
                <button onclick="resetFilters()" class="btn btn-secondary" style="height: 38px;">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Dynamic Events Table Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                📡 Live API Events Feed
                <span id="recordCountBadge" class="badge-practical">Loading...</span>
            </h2>
            <div id="loadingIndicator" style="display: none; align-items: center; gap: 8px; color: var(--primary); font-size: 13px; font-weight: 600;">
                <span>⏳ Fetching data asynchronously...</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="hub-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Event Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th style="text-align: right; width: 170px;">AJAX Actions</th>
                    </tr>
                </thead>
                <tbody id="eventsTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                            Connecting to JSON API endpoint...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- API Inspector Card for Practical Evaluation -->
    <div class="card" style="background: #0f172a; color: #f8fafc; border: none;">
        <div class="card-header" style="border-bottom: 1px solid #334155;">
            <h3 class="card-title" style="color: #38bdf8;">
                🔬 RESTful API Traffic & Status Monitor
            </h3>
            <span style="font-size: 12px; color: #94a3b8;">Endpoint: <code>php/api_events.php</code></span>
        </div>
        <div style="font-family: monospace; font-size: 13px; padding-top: 8px;">
            <div style="margin-bottom: 6px;">
                <strong>Last Request:</strong> <span id="lastApiMethod" style="color: #4ade80;">GET</span> &nbsp;
                <strong>HTTP Status:</strong> <span id="lastApiStatus" style="color: #facc15;">200 OK</span>
            </div>
            <div style="background: #1e293b; padding: 12px; border-radius: 6px; overflow-x: auto; max-height: 140px; color: #cbd5e1;" id="lastApiResponse">
                // Real-time JSON payload will appear here upon each user interaction.
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add / Edit Event -->
<div id="eventModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(3px); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; width: 95%; max-width: 580px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden; animation: popIn 0.2s ease;">
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="margin: 0; font-size: 18px; color: #1e293b;">Add New Event</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>

        <form id="ajaxEventForm" onsubmit="handleFormSubmit(event)" style="padding: 24px;">
            <input type="hidden" id="modalEventId" value="">

            <div class="form-field">
                <label>Event Title *</label>
                <input type="text" id="modalTitleInput" required placeholder="e.g. NextGen Web Frameworks Seminar">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-field">
                    <label>Category *</label>
                    <select id="modalCategoryInput" required>
                        <option value="Workshop">Workshop</option>
                        <option value="Competition">Competition</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Exhibition">Exhibition</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Status *</label>
                    <select id="modalStatusInput" required>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-field">
                    <label>Event Date *</label>
                    <input type="date" id="modalDateInput" required>
                </div>

                <div class="form-field">
                    <label>Venue *</label>
                    <input type="text" id="modalVenueInput" required placeholder="e.g. Auditorium">
                </div>
            </div>

            <div class="form-field">
                <label>Description *</label>
                <textarea id="modalDescInput" rows="3" required placeholder="Event agenda, requirements, and outcomes..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" id="modalSubmitBtn" class="btn btn-primary">
                    💾 Save via AJAX
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes popIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

<script>
// =======================================================
// Practical 13 Client-Side Fetch API Implementation
// =======================================================

const API_URL = 'api_events.php';
let cachedEvents = [];
let debounceTimer = null;

// 1. Debounced Search (300ms Delay) - Intermediate Requirement
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchEvents();
    }, 300);
});

// 2. Fetch Events (GET Request)
async function fetchEvents() {
    showLoading(true);
    const search = encodeURIComponent(document.getElementById('searchInput').value.trim());
    const category = encodeURIComponent(document.getElementById('categoryFilter').value);
    const status = encodeURIComponent(document.getElementById('statusFilter').value);

    const queryUrl = `${API_URL}?search=${search}&category=${category}&status=${status}`;

    try {
        const response = await fetch(queryUrl);
        const result = await response.json();

        updateApiMonitor('GET', response.status, result);

        if (response.ok && result.success) {
            cachedEvents = result.data;
            renderTable(cachedEvents);
            document.getElementById('recordCountBadge').textContent = `${cachedEvents.length} Event(s)`;
        } else {
            showToast('error', result.message || 'Failed to fetch events from API.');
        }
    } catch (err) {
        showToast('error', 'Network or server error while querying JSON API.');
        updateApiMonitor('GET', 'Error', { error: err.message });
    } finally {
        showLoading(false);
    }
}

// 3. Render HTML Table Dynamically
function renderTable(events) {
    const tbody = document.getElementById('eventsTableBody');
    if (!events || events.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 36px; color: #94a3b8;">
                    No events found matching current search/filter.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = events.map(ev => `
        <tr id="event-row-${ev.id}">
            <td><strong>#${ev.id}</strong></td>
            <td>
                <strong>${escapeHtml(ev.title)}</strong>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    ${escapeHtml(ev.description ? ev.description.substring(0, 70) + '...' : '')}
                </div>
            </td>
            <td>
                <span class="role-badge" style="background:#e0e7ff; color:var(--primary);">
                    ${escapeHtml(ev.category)}
                </span>
            </td>
            <td>${formatDate(ev.event_date)}</td>
            <td>📍 ${escapeHtml(ev.venue)}</td>
            <td>
                <span class="role-badge" style="background: ${ev.status === 'open' ? '#dcfce7' : '#fee2e2'}; color: ${ev.status === 'open' ? '#166534' : '#991b1b'};">
                    ${ev.status.toUpperCase()}
                </span>
            </td>
            <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px;">
                    <button onclick="openModal('edit', ${ev.id})" class="btn btn-secondary btn-sm" title="Edit via AJAX">
                        ✏️ Edit
                    </button>
                    <button onclick="deleteEvent(${ev.id}, '${escapeHtml(ev.title)}')" class="btn btn-danger btn-sm" title="Delete via AJAX">
                        🗑️ Delete
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// 4. Modal Open/Close Controls
function openModal(mode, eventId = null) {
    const modal = document.getElementById('eventModal');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('modalSubmitBtn');

    if (mode === 'edit' && eventId) {
        const ev = cachedEvents.find(item => item.id == eventId);
        if (!ev) return;

        title.textContent = `✏️ Edit Event (#${ev.id}) via AJAX`;
        submitBtn.textContent = '💾 Update via AJAX';
        document.getElementById('modalEventId').value = ev.id;
        document.getElementById('modalTitleInput').value = ev.title;
        document.getElementById('modalCategoryInput').value = ev.category;
        document.getElementById('modalStatusInput').value = ev.status;
        document.getElementById('modalDateInput').value = ev.event_date;
        document.getElementById('modalVenueInput').value = ev.venue;
        document.getElementById('modalDescInput').value = ev.description;
    } else {
        title.textContent = '➕ Add New Event via AJAX';
        submitBtn.textContent = '💾 Create via AJAX';
        document.getElementById('ajaxEventForm').reset();
        document.getElementById('modalEventId').value = '';
    }

    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('eventModal').style.display = 'none';
}

// 5. Handle Form Submission (POST / PUT Asynchronously)
async function handleFormSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('modalEventId').value;
    const isEdit = Boolean(id);

    const payload = {
        title: document.getElementById('modalTitleInput').value.trim(),
        category: document.getElementById('modalCategoryInput').value,
        status: document.getElementById('modalStatusInput').value,
        event_date: document.getElementById('modalDateInput').value,
        venue: document.getElementById('modalVenueInput').value.trim(),
        description: document.getElementById('modalDescInput').value.trim()
    };

    if (isEdit) {
        payload.id = parseInt(id);
    }

    const method = isEdit ? 'PUT' : 'POST';

    try {
        const response = await fetch(API_URL, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        updateApiMonitor(method, response.status, result);

        if (response.ok && result.success) {
            showToast('success', result.message);
            closeModal();
            fetchEvents(); // Refresh data without reloading page!
        } else {
            const errorMsg = Array.isArray(result.data) ? result.data.join(', ') : result.message;
            showToast('error', errorMsg || 'Operation failed.');
        }
    } catch (err) {
        showToast('error', 'Request failed: ' + err.message);
    }
}

// 6. Delete Event Asynchronously (DELETE Request)
async function deleteEvent(id, title) {
    if (!confirm(`Are you sure you want to delete event #${id} ('${title}')?\nThis action will execute asynchronously without a page reload.`)) {
        return;
    }

    try {
        const response = await fetch(`${API_URL}?id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        updateApiMonitor('DELETE', response.status, result);

        if (response.ok && result.success) {
            showToast('success', result.message);
            // Remove DOM row smoothly
            const row = document.getElementById(`event-row-${id}`);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            cachedEvents = cachedEvents.filter(ev => ev.id != id);
            document.getElementById('recordCountBadge').textContent = `${cachedEvents.length} Event(s)`;
        } else {
            showToast('error', result.message || 'Could not delete event.');
        }
    } catch (err) {
        showToast('error', 'Network failure during deletion.');
    }
}

// 7. Utility Helpers
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    fetchEvents();
}

function showLoading(isLoading) {
    document.getElementById('loadingIndicator').style.display = isLoading ? 'flex' : 'none';
}

function updateApiMonitor(method, status, data) {
    const methodEl = document.getElementById('lastApiMethod');
    const statusEl = document.getElementById('lastApiStatus');
    const responseEl = document.getElementById('lastApiResponse');

    methodEl.textContent = method;
    statusEl.textContent = status;
    statusEl.style.color = (status >= 200 && status < 300) ? '#4ade80' : '#f87171';
    responseEl.textContent = JSON.stringify(data, null, 2);
}

function showToast(type, message) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `alert ${type === 'success' ? 'alert-success' : 'alert-danger'}`;
    toast.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
    toast.style.margin = '0';
    toast.style.animation = 'popIn 0.2s ease';
    toast.innerHTML = `<strong>${type === 'success' ? '✅ Success:' : '❌ Error:'}</strong> ${escapeHtml(message)}`;

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

// Initial Fetch on Page Load
document.addEventListener('DOMContentLoaded', fetchEvents);
</script>

<footer class="hub-footer">
    StudentHub Portal &bull; Practical 13: PHP JSON API & AJAX/Fetch-Based CRUD &bull; Charusat ITUE203
</footer>

</body>
</html>
