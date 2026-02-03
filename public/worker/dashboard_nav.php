<?php
if (!isset($user)) {
    session_start();
    if (!empty($_SESSION['user'])) {
        $_SESSION['user']['role'] = strtolower(trim($_SESSION['user']['role'] ?? ''));
        $user = $_SESSION['user'];
    } else {
        $user = null;
    }
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/global-theme.css">

<script>
    // Immediate theme application to prevent flash
    (function () {
        const saved = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', saved);
    })();
</script>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">📦 Leltározó rendszer</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-3 mt-3 mt-lg-0">
                <!-- Theme Toggle -->
                <li class="nav-item">
                    <button class="btn btn-outline-light border-0 w-100 text-start text-lg-center"
                        onclick="toggleTheme()" title="Téma váltása">
                        <span id="theme-icon" class="me-2 me-lg-0">☀️</span> <span class="d-lg-none">Téma váltása</span>
                    </button>
                </li>

                <?php if ($user): ?>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link text-white d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle"></i>
                            <span>
                                <?php
                                $displayName = trim(($user['last_name'] ?? '') . ' ' . ($user['first_name'] ?? ''));
                                echo htmlspecialchars($displayName ?: $user['email']);
                                ?>
                            </span>
                            <span
                                class="badge bg-light text-primary rounded-pill ms-2"><?= htmlspecialchars($user['role'] ?? '') ?></span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="../logout.php" class="btn btn-light text-primary w-100 fw-bold">Kijelentkezés</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
    function toggleTheme() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme') || 'dark';
        const next = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateIcon(next);
    }

    function updateIcon(theme) {
        const icon = document.getElementById('theme-icon');
        if (icon) icon.textContent = theme === 'light' ? '🌙' : '☀️';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const current = localStorage.getItem('theme') || 'dark';
        updateIcon(current);
    });
</script>

<style>
    .hover-opacity:hover {
        opacity: 0.8;
    }

    .badge-role {
        background: rgba(255, 255, 255, 0.2) !important;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        color: white !important;
    }
</style>