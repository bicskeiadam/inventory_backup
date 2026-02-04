<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Company.php';
require_once __DIR__ . '/../../app/models/Inventory.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
$role = strtolower(trim($user['role'] ?? ''));
$isEmployerOrAdmin = in_array($role, ['employer', 'admin']);
?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Leltár Rendszer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Container */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.primary::before {
            background: var(--primary);
        }

        .stat-card.success::before {
            background: var(--secondary);
        }

        .stat-card.warning::before {
            background: var(--warning);
        }

        .stat-card.danger::before {
            background: var(--danger);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.primary {
            background: rgba(99, 102, 241, 0.2);
        }

        .stat-icon.success {
            background: rgba(16, 185, 129, 0.2);
        }

        .stat-icon.warning {
            background: rgba(245, 158, 11, 0.2);
        }

        .stat-icon.danger {
            background: rgba(239, 68, 68, 0.2);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Cards */
        .card {
            background: var(--bg-card) !important;
            border-radius: 16px !important;
            border: 1px solid var(--border) !important;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1.25rem 1.5rem !important;
            border-bottom: 1px solid var(--border) !important;
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            background: transparent !important;
        }

        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .card-body {
            padding: 1.5rem !important;
        }

        /* Chart */
        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
        }

        /* Inventory Status */
        .inventory-status-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .inventory-status-item {
            text-align: center;
            padding: 1.25rem;
            background: var(--bg-surface);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .inventory-status-item .count {
            font-size: 2rem;
            font-weight: 700;
        }

        .inventory-status-item .label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .inventory-status-item.active .count {
            color: var(--secondary);
        }

        .inventory-status-item.scheduled .count {
            color: var(--warning);
        }

        .inventory-status-item.finished .count {
            color: var(--text-secondary);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary) !important;
            text-decoration: none !important;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .action-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .action-btn .icon {
            font-size: 1.25rem;
        }

        /* Activity List */
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .activity-icon.pending {
            background: rgba(245, 158, 11, 0.2);
        }

        .activity-icon.approved {
            background: rgba(16, 185, 129, 0.2);
        }

        .activity-icon.rejected {
            background: rgba(239, 68, 68, 0.2);
        }

        .activity-content {
            flex: 1;
            min-width: 0;
        }

        .activity-title {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .activity-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .activity-badge.pending {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .activity-badge.approved {
            background: rgba(16, 185, 129, 0.2);
            color: var(--secondary);
        }

        .activity-badge.rejected {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        /* Buttons */
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Loading & Empty */
        .loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .empty-state .icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }

            .dashboard-container {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.8rem;
            }

            .card-header {
                padding: 1rem !important;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .card-body {
                padding: 1rem !important;
            }

            .btn-outline {
                width: 100%;
                text-align: center;
            }

            .inventory-status-grid {
                grid-template-columns: 1fr !important;
                gap: 0.75rem !important;
            }

            .chart-container {
                height: 250px !important;
                width: 100% !important;
                overflow: hidden;
            }
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">👋 Üdv,
                <?php
                $dispName = trim(($user['last_name'] ?? '') . ' ' . ($user['first_name'] ?? ''));
                echo htmlspecialchars($dispName ?: $user['email']);
                ?>!
            </h1>
            <p class="dashboard-subtitle">Itt találod az összes fontos információt egy helyen</p>
        </div>

        <?php if ($isEmployerOrAdmin): ?>
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon primary">📋</div>
                    <div class="stat-value" id="statInventories">-</div>
                    <div class="stat-label">Összes leltár</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon warning">⏳</div>
                    <div class="stat-value" id="statPending">-</div>
                    <div class="stat-label">Várakozó beküldés</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon success">📦</div>
                    <div class="stat-value" id="statItems">-</div>
                    <div class="stat-label">Nyilvántartott eszköz</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-icon danger">👥</div>
                    <div class="stat-value" id="statWorkers">-</div>
                    <div class="stat-label">Munkatárs</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Left Column -->
                <div>
                    <!-- Inventory Status -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📊 Leltár állapotok</h3>
                        </div>
                        <div class="card-body">
                            <div class="inventory-status-grid">
                                <div class="inventory-status-item active">
                                    <div class="count" id="invActive">-</div>
                                    <div class="label">Aktív</div>
                                </div>
                                <div class="inventory-status-item scheduled">
                                    <div class="count" id="invScheduled">-</div>
                                    <div class="label">Ütemezett</div>
                                </div>
                                <div class="inventory-status-item finished">
                                    <div class="count" id="invFinished">-</div>
                                    <div class="label">Befejezett</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📈 Beküldések (utolsó 7 nap)</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="submissionsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3>⚡ Gyors műveletek</h3>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="inventory_submissions.php" class="action-btn">
                                    <span class="icon">📥</span>
                                    <span>Beküldések</span>
                                </a>
                                <a href="inventories.php" class="action-btn">
                                    <span class="icon">📋</span>
                                    <span>Leltárak</span>
                                </a>
                                <a href="items.php" class="action-btn">
                                    <span class="icon">📦</span>
                                    <span>Eszközök</span>
                                </a>
                                <a href="rooms.php" class="action-btn">
                                    <span class="icon">🏠</span>
                                    <span>Helyiségek</span>
                                </a>
                                <a href="teams.php" class="action-btn">
                                    <span class="icon">👥</span>
                                    <span>Csapatok</span>
                                </a>
                                <a href="employer_assign_workers.php" class="action-btn">
                                    <span class="icon">➕</span>
                                    <span>Munkások</span>
                                </a>
                                <?php if ($role === 'admin'): ?>
                                    <a href="admin_users.php" class="action-btn">
                                        <span class="icon">🔐</span>
                                        <span>Admin Panel</span>
                                    </a>
                                    <a href="admin_employers.php" class="action-btn">
                                        <span class="icon">💼</span>
                                        <span>Munkáltatók</span>
                                    </a>
                                    <a href="companies.php" class="action-btn">
                                        <span class="icon">🏢</span>
                                        <span>Vállalatok</span>
                                    </a>
                                    <a href="admin_device_logs.php" class="action-btn">
                                        <span class="icon">📱</span>
                                        <span>Eszköz Napló</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h3>🕐 Legutóbbi beküldések</h3>
                            <a href="inventory_submissions.php" class="btn-outline">Mind</a>
                        </div>
                        <div class="card-body">
                            <ul class="activity-list" id="activityList">
                                <li class="loading-spinner">
                                    <div class="spinner"></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <?php
            // Fetch worker stats
            $activeInventoriesCount = '-';
            $mySubmissionsCount = '-';

            $db = (new Database())->getConnection();

            // Get worker's company
            $stmt = $db->prepare("SELECT company_id FROM company_user WHERE user_id = ? LIMIT 1");
            $stmt->execute([$user['id']]);
            $workerCompany = $stmt->fetch();

            if ($workerCompany) {
                $companyModel = new Company($db); // Ensure Company model is available or use raw query
                $inventoryModel = new Inventory($db); // Ensure Inventory model is available
        
                // Count Active Inventories
                // Logic: Get all inventories for company, filter by status
                // Optimization: Do simple count query
                $stmt = $db->prepare("SELECT COUNT(*) FROM inventories WHERE company_id = ? AND status IN ('active', 'running')");
                $stmt->execute([$workerCompany['company_id']]);
                $activeInventoriesCount = $stmt->fetchColumn();

                // Count My Submissions
                $stmt = $db->prepare("SELECT COUNT(*) FROM inventory_submissions WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $mySubmissionsCount = $stmt->fetchColumn();
            }
            ?>
            <!-- Worker View -->
            <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
                <div class="stat-card primary">
                    <div class="stat-icon primary">📋</div>
                    <div class="stat-value"><?= $activeInventoriesCount ?></div>
                    <div class="stat-label">Aktív leltáraim</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon success">✅</div>
                    <div class="stat-value"><?= $mySubmissionsCount ?></div>
                    <div class="stat-label">Beküldött</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>⚡ Gyors műveletek</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="worker_companies.php" class="action-btn"><span
                                class="icon">💼</span><span>Cégeim</span></a>
                        <a href="inventories.php" class="action-btn"><span class="icon">📋</span><span>Leltáraim</span></a>
                        <a href="profile.php" class="action-btn"><span class="icon">👤</span><span>Profilom</span></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isEmployerOrAdmin): ?>
        <script>
            async function loadDashboard() {
                try {
                    const response = await fetch('../api/dashboard_api.php');
                    const text = await response.text();
                    console.log('API Response:', text);

                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        return;
                    }

                    if (data.error) {
                        console.error('Dashboard error:', data.error);
                        return;
                    }

                    console.log('Parsed data:', data);

                    document.getElementById('statInventories').textContent = data.stats?.total_inventories || 0;
                    document.getElementById('statPending').textContent = data.stats?.pending_submissions || 0;
                    document.getElementById('statItems').textContent = data.stats?.total_items || 0;
                    document.getElementById('statWorkers').textContent = data.stats?.workers_count || 0;

                    document.getElementById('invActive').textContent = data.stats?.inventories?.active || 0;
                    document.getElementById('invScheduled').textContent = data.stats?.inventories?.scheduled || 0;
                    document.getElementById('invFinished').textContent = data.stats?.inventories?.finished || 0;

                    updateActivityList(data.recent_submissions || []);
                    updateChart(data.charts?.submissions_per_day || []);
                } catch (error) {
                    console.error('Failed to load dashboard:', error);
                }
            }

            function updateActivityList(submissions) {
                const list = document.getElementById('activityList');
                if (submissions.length === 0) {
                    list.innerHTML = '<li class="empty-state"><div class="icon">📭</div><p>Még nincs beküldés</p></li>';
                    return;
                }
                const statusLabels = { pending: 'Várakozik', approved: 'Elfogadva', rejected: 'Elutasítva' };
                const statusIcons = { pending: '⏳', approved: '✅', rejected: '❌' };
                list.innerHTML = submissions.map(sub => `
        <li class="activity-item">
            <div class="activity-icon ${sub.status}">${statusIcons[sub.status] || '📋'}</div>
            <div class="activity-content">
                <div class="activity-title">${sub.inventory_name}</div>
                <div class="activity-meta">${sub.worker_name} · ${sub.created_at}</div>
            </div>
            <span class="activity-badge ${sub.status}">${statusLabels[sub.status] || sub.status}</span>
        </li>
    `).join('');
            }

            let submissionsChart = null;
            function updateChart(data) {
                const ctx = document.getElementById('submissionsChart').getContext('2d');
                const labels = [];
                const today = new Date();
                for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    labels.push(date.toLocaleDateString('hu-HU', { month: 'short', day: 'numeric' }));
                }
                const dataMap = {};
                data.forEach(item => {
                    const d = new Date(item.date);
                    const key = d.toLocaleDateString('hu-HU', { month: 'short', day: 'numeric' });
                    dataMap[key] = item.count;
                });
                const chartData = labels.map(label => dataMap[label] || 0);

                if (submissionsChart) submissionsChart.destroy();
                submissionsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Beküldések',
                            data: chartData,
                            borderColor: '#6366F1',
                            backgroundColor: 'rgba(99, 102, 241, 0.15)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#6366F1',
                            borderWidth: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1, color: '#94A3B8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                            x: { ticks: { color: '#94A3B8' }, grid: { display: false } }
                        }
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                loadDashboard();
                
                // Initialize auto-refresh every 15 seconds - reuses loadDashboard for full refresh
                setInterval(() => {
                    loadDashboard();
                }, 15000);
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>