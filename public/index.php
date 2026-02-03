<?php
require_once __DIR__ . '/../app/config/config.php';
?>
<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leltározási rendszer - Profi Dizájn</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<!-- Navigation -->

<body>
    <nav>
        <div class="logo">📦 Leltározó rendszer</div>
        <div class="menu-toggle" id="mobile-menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
        <ul class="nav-list">
            <li><a href="#features">Fő funkciók</a></li>
            <li><a href="#extra">Extra funkciók</a></li>
            <li><a href="#contact">Kapcsolat</a></li>
            <li><a href="home.php">Bejelentkezés/Regisztráció</a></li>
        </ul>
    </nav>

    <script>
        const menuToggle = document.getElementById('mobile-menu');
        const navList = document.querySelector('.nav-list');

        menuToggle.addEventListener('click', () => {
            navList.classList.toggle('active');
            menuToggle.classList.toggle('is-active');
        });
    </script>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-left">
            <h1 class="animate">Webes Leltározási Rendszer</h1>
            <p class="animate">Modern, gyors és biztonságos megoldás vállalatok és intézmények számára.</p>
            <div class="hero-ctas animate">
                <a href="#features" class="btn-primary">Fő funkciók megtekintése</a>
                <a href="#contact" class="btn-ghost">Kapcsolat</a>
            </div>
        </div>
        <div class="hero-right">
            <div class="floating-shape shape1"></div>
            <div class="floating-shape shape2"></div>
            <div class="floating-shape shape3"></div>
        </div>
    </section>

    <!-- Fő funkciók -->
    <section id="features" class="features-section">
        <h2 class="section-title animate">Fő funkciók</h2>
        <div class="features-grid">
            <div class="feature animate">
                <div class="feature-icon"><i class="fas fa-clipboard-list"></i></div>
                <h3>Leltár kezelés</h3>
                <p>Gyors és pontos eszköz nyilvántartás QR kód segítségével.</p>
            </div>
            <div class="feature animate">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h3>Csapat koordináció</h3>
                <p>Csapatok és helyiségek hozzárendelése a hatékony munkavégzésért.</p>
            </div>
            <div class="feature animate">
                <div class="feature-icon"><i class="fas fa-bell"></i></div>
                <h3>Értesítések</h3>
                <p>Automatikus e-mail értesítés a leltár indulásáról és státuszáról.</p>
            </div>
        </div>
    </section>

    <!-- Extra funkciók -->
    <section id="extra" class="extra-section">
        <h2 class="section-title animate">Extra funkciók</h2>
        <div class="extra-grid">
            <div class="extra-box animate">
                <div class="feature-icon"><i class="fas fa-clock"></i></div>
                <h3>Offline mód</h3>
                <p>A terepen gyűjtött adatok automatikusan szinkronizálódnak online.</p>
            </div>
            <div class="extra-box animate">
                <div class="feature-icon"><i class="fas fa-camera"></i></div>
                <h3>Fotódokumentáció</h3>
                <p>Hibás vagy sérült eszközökről azonnali fénykép készítése.</p>
            </div>
            <div class="extra-box animate">
                <div class="feature-icon"><i class="fas fa-file-pdf"></i></div>
                <h3>Automatikus riport</h3>
                <p>Hiányzó és hibás eszközök listája PDF formátumban.</p>
            </div>
        </div>
    </section>


    <!-- Kapcsolat -->
    <section id="contact" class="contact-section">
        <div class="contact-wrapper">
            <div class="contact-info animate">
                <h2>Kapcsolat</h2>
                <?php echo '<p><i class="fas fa-envelope"></i> ' . htmlspecialchars(MAIL_USER) . '</p>'; ?>
                <p><i class="fas fa-phone"></i> +381 63 123 2344</p>
                <p><i class="fas fa-map-marker-alt"></i> Szabadka, Szerbia</p>
            </div>
            <div class="contact-cta animate">
                <h3>Üzenet küldése</h3>
                <?php if (!empty($_GET['status']) && $_GET['status'] === 'sent'): ?>
                    <div class="alert-success"
                        style="padding:10px;border-radius:4px;margin-bottom:10px;color:#155724;background:#d4edda;">Üzeneted
                        elküldve. Köszönjük!</div>
                <?php elseif (!empty($_GET['status']) && $_GET['status'] === 'error'): ?>
                    <div class="alert-danger"
                        style="padding:10px;border-radius:4px;margin-bottom:10px;color:#721c24;background:#f8d7da;">Hiba
                        történt az üzenet küldésekor. Kérjük próbáld újra.</div>
                <?php endif; ?>
                <form method="post" action="contact_submit.php">
                    <div class="input-group">
                        <input type="text" name="name" required>
                        <label>Név</label>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" required>
                        <label>Email</label>
                    </div>
                    <div class="input-group">
                        <textarea name="message" required></textarea>
                        <label>Üzenet</label>
                    </div>
                    <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Küldés</button>
                </form>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer>
        &copy; 2025 Leltározó rendszer
    </footer>

    <script>
        // Scroll reveal
        const animateElements = document.querySelectorAll('.animate');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) { entry.target.classList.add('animate-show'); }
            });
        }, { threshold: 0.15 });
        animateElements.forEach(el => observer.observe(el));
    </script>

</body>

</html>