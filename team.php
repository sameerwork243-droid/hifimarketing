<?php
// ===== START SESSION & CHECK LOGIN =====
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$isAdmin = $isLoggedIn && $_SESSION['user_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HIFI Marketing & Technologies | Team</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="css/style.css" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HIFI Marketing & Technologies</title>
    
    <!-- ===== BROWSER TAB ICON (FAVICON) ===== -->
    <link rel="icon" href="/images/fav-icon.png" type="image/png" />
    <link rel="shortcut icon" href="/images/fav-icon.png" type="image/png" />
    
    <!-- Rest of head -->
  <style>
    /* ----- RESET & BASE – WHITE ----- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: #ffffff;
      color: #1e1f2a;
      line-height: 1.6;
    }
    a {
      text-decoration: none;
      color: inherit;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
    }

    /* ----- HEADER – LIGHT ----- */
    header {
      background: #ffffff;
      border-bottom: 1px solid #e9edf2;
      padding: 16px 0;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 12px rgba(0,0,0,0.02);
    }
    .header-inner {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      flex-wrap: wrap;
      gap: 20px;
    }
    .logo {
      font-size: 26px;
      font-weight: 900;
      letter-spacing: -0.5px;
      color: #1e1f2a;
      flex-shrink: 0;
    }
    .logo span {
      color: #4a5cf5;
    }
    nav ul {
      display: flex;
      list-style: none;
      gap: 28px;
    }
    nav ul li a {
      font-weight: 600;
      font-size: 15px;
      color: #2c2f3a;
      transition: color 0.2s;
      padding: 6px 0;
      border-bottom: 2px solid transparent;
    }
    nav ul li a:hover,
    nav ul li a.active {
      color: #4a5cf5;
      border-bottom-color: #4a5cf5;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-left: auto;
    }

    .header-right .btn-primary {
      background: #4a5cf5;
      color: #fff;
      padding: 10px 28px;
      border-radius: 40px;
      font-weight: 700;
      font-size: 14px;
      transition: 0.2s;
      border: none;
      box-shadow: 0 4px 12px rgba(74, 92, 245, 0.2);
      flex-shrink: 0;
      cursor: pointer;
    }
    .header-right .btn-primary:hover {
      background: #3a4be0;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(74, 92, 245, 0.25);
    }

    .header-right .btn-logout {
      background: transparent;
      color: #dc3545;
      padding: 8px 16px;
      border-radius: 40px;
      font-weight: 600;
      font-size: 14px;
      transition: 0.2s;
      border: 1px solid #dc3545;
      cursor: pointer;
    }
    .header-right .btn-logout:hover {
      background: #dc3545;
      color: #fff;
    }

    .header-right .user-name {
      color: #1a1c26;
      font-weight: 600;
      font-size: 14px;
    }
    .header-right .user-name i {
      color: #4a5cf5;
      margin-right: 6px;
    }

    .header-right .admin-badge {
      background: #4a5cf5;
      color: #fff;
      padding: 4px 12px;
      border-radius: 40px;
      font-weight: 600;
      font-size: 11px;
      letter-spacing: 0.5px;
    }

    .hamburger {
      display: none;
      font-size: 26px;
      cursor: pointer;
      color: #1e1f2a;
      background: none;
      border: none;
    }

    /* ===== THEME TOGGLE ===== */
    .theme-toggle {
      position: relative;
      width: 60px;
      height: 32px;
      background: #f0f3f8;
      border-radius: 50px;
      border: 2px solid #e9edf2;
      cursor: pointer;
      display: flex;
      align-items: center;
      padding: 3px;
      transition: 0.4s ease;
      flex-shrink: 0;
      box-shadow: inset 0 2px 6px rgba(0,0,0,0.05);
    }
    .theme-toggle:hover {
      border-color: #4a5cf5;
      box-shadow: 0 0 20px rgba(74, 92, 245, 0.15);
    }
    .theme-toggle .toggle-track {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      border-radius: 50px;
      background: #e9edf2;
      transition: 0.4s ease;
    }
    body.dark-mode .theme-toggle .toggle-track {
      background: #1e242c;
    }
    .theme-toggle .toggle-thumb {
      position: relative;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #ffffff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      z-index: 2;
    }
    body.dark-mode .theme-toggle .toggle-thumb {
      transform: translateX(28px);
      background: #4a5cf5;
    }
    .theme-toggle .toggle-thumb i {
      font-size: 14px;
      color: #4a5cf5;
      transition: 0.4s ease;
    }
    body.dark-mode .theme-toggle .toggle-thumb i {
      color: #ffffff;
    }
    .theme-toggle .toggle-icons {
      position: absolute;
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 8px;
      z-index: 1;
      pointer-events: none;
    }
    .theme-toggle .toggle-icons .icon-sun {
      font-size: 13px;
      color: #f59e0b;
      opacity: 1;
      transition: 0.4s ease;
    }
    .theme-toggle .toggle-icons .icon-moon {
      font-size: 13px;
      color: #6b7280;
      opacity: 0.5;
      transition: 0.4s ease;
    }
    body.dark-mode .theme-toggle .toggle-icons .icon-sun {
      opacity: 0.5;
    }
    body.dark-mode .theme-toggle .toggle-icons .icon-moon {
      opacity: 1;
      color: #fbbf24;
    }

    /* ----- TEAM – LIGHT ----- */
    #team {
      padding: 120px 0 60px;
      background: #ffffff;
    }
    .section-title {
      font-size: 36px;
      font-weight: 800;
      text-align: center;
      color: #1a1c26;
      margin-bottom: 8px;
      letter-spacing: -0.5px;
    }
    .section-sub {
      text-align: center;
      color: #3d4452;
      font-size: 17px;
      margin-bottom: 40px;
    }
    .team-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 30px;
      margin-top: 20px;
    }
    .team-card {
      background: #f8fafc;
      border: 1px solid #e9edf2;
      border-radius: 24px;
      padding: 28px 16px 22px;
      text-align: center;
      transition: 0.25s;
    }
    .team-card:hover {
      transform: translateY(-6px);
      border-color: #4a5cf5;
      box-shadow: 0 12px 30px rgba(74, 92, 245, 0.06);
    }
    .team-card .avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      margin: 0 auto 14px;
      background: #e9edf2;
      overflow: hidden;
      border: 2px solid #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .team-card .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .team-card h4 {
      font-size: 18px;
      font-weight: 700;
      color: #1a1c26;
    }
    .team-card p {
      color: #4a5260;
      font-size: 14px;
      margin-top: 4px;
    }

    /* ===== DEVELOPER CREDIT ===== */
    .developer-credit {
      text-align: center;
      padding: 20px 0 10px;
      font-size: 13px;
      color: #8a94a0;
      border-top: 1px solid #e9edf2;
      background: #f8fafc;
    }
    .developer-credit a {
      color: #4a5cf5;
      font-weight: 600;
      transition: 0.2s;
    }
    .developer-credit a:hover {
      color: #3a4be0;
      text-decoration: underline;
    }
    body.dark-mode .developer-credit {
      background: #0b0d10;
      border-top-color: #1e242c;
      color: #6b7a8a;
    }
    body.dark-mode .developer-credit a {
      color: #6c7aff;
    }
    body.dark-mode .developer-credit a:hover {
      color: #8a9aff;
    }

    /* ----- FOOTER – LIGHT ----- */
    footer {
      background: #f8fafc;
      border-top: 1px solid #e9edf2;
      padding: 32px 0 16px;
      text-align: center;
    }
    footer .socials {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-bottom: 12px;
    }
    footer .socials a {
      color: #2c3442;
      font-size: 20px;
      transition: 0.2s;
    }
    footer .socials a:hover {
      color: #4a5cf5;
      transform: translateY(-2px);
    }
    footer p {
      color: #4a5260;
      font-size: 14px;
    }

    body.dark-mode footer {
      background: #0b0d10;
      border-top-color: #1e242c;
    }
    body.dark-mode footer .socials a {
      color: #b0b8c5;
    }
    body.dark-mode footer .socials a:hover {
      color: #6c7aff;
    }
    body.dark-mode footer p {
      color: #6b7a8a;
    }

    /* ===== DARK MODE OVERRIDES ===== */
    body.dark-mode {
      background: #0b0d10;
      color: #eaeef2;
    }

    body.dark-mode header {
      background: #0b0d10;
      border-bottom-color: #1e242c;
    }

    body.dark-mode .logo {
      color: #eaeef2;
    }

    body.dark-mode nav ul li a {
      color: #b0b8c5;
    }

    body.dark-mode nav ul li a:hover,
    body.dark-mode nav ul li a.active {
      color: #6c7aff;
      border-bottom-color: #6c7aff;
    }

    body.dark-mode .header-right .user-name {
      color: #eaeef2;
    }

    body.dark-mode #team {
      background: #0b0d10;
    }

    body.dark-mode .section-title {
      color: #eaeef2;
    }

    body.dark-mode .section-sub {
      color: #b0b8c5;
    }

    body.dark-mode .team-card {
      background: #14191f;
      border-color: #1e242c;
    }

    body.dark-mode .team-card:hover {
      border-color: #6c7aff;
    }

    body.dark-mode .team-card h4 {
      color: #eaeef2;
    }

    body.dark-mode .team-card p {
      color: #b0b8c5;
    }

    body.dark-mode .team-card .avatar {
      background: #1e242c;
      border-color: #2a3340;
    }

    /* ----- RESPONSIVE ----- */
    @media (max-width: 768px) {
      nav ul {
        display: none;
        flex-direction: column;
        background: #ffffff;
        padding: 20px;
        width: 100%;
        border-radius: 16px;
        border: 1px solid #e9edf2;
        gap: 12px;
      }
      nav ul.show {
        display: flex;
      }
      .hamburger {
        display: block;
      }
      .header-inner nav {
        order: 3;
        flex-basis: 100%;
      }
      .header-right .btn-primary {
        display: none;
      }
      .section-title {
        font-size: 28px;
      }
      .team-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
      }
      .theme-toggle {
        width: 52px;
        height: 28px;
      }
      .theme-toggle .toggle-thumb {
        width: 20px;
        height: 20px;
      }
      body.dark-mode .theme-toggle .toggle-thumb {
        transform: translateX(24px);
      }
      body.dark-mode nav ul {
        background: #14191f;
        border-color: #1e242c;
      }
    }

    @media (max-width: 480px) {
      .team-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
      }
      .team-card {
        padding: 16px 10px;
      }
      .team-card .avatar {
        width: 64px;
        height: 64px;
      }
      .theme-toggle {
        width: 48px;
        height: 26px;
      }
      .theme-toggle .toggle-thumb {
        width: 18px;
        height: 18px;
      }
      body.dark-mode .theme-toggle .toggle-thumb {
        transform: translateX(22px);
      }
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header>
    <div class="container header-inner">
      <div class="logo">HIFI <span>Marketing & Technologies</span></div>
      <nav>
        <ul id="navMenu">
          <li><a href="index.php">Home</a></li>
          <li><a href="services.php">Services</a></li>
          <li><a href="about.php">About</a></li>
          <li><a href="team.php" class="active">Team</a></li>
          <li><a href="careers.php">Careers</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </nav>

      <!-- ===== RIGHT SIDE: Theme Toggle + User Controls ===== -->
      <div class="header-right">
        <div class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
          <div class="toggle-track"></div>
          <div class="toggle-icons">
            <span class="icon-sun"><i class="fas fa-sun"></i></span>
            <span class="icon-moon"><i class="fas fa-moon"></i></span>
          </div>
          <div class="toggle-thumb">
            <i class="fas fa-adjust"></i>
          </div>
        </div>

        <?php if ($isLoggedIn): ?>
          <span class="user-name">
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?>
          </span>
          <?php if ($isAdmin): ?>
            <span class="admin-badge">Admin</span>
          <?php endif; ?>
          <a href="logout.php" class="btn-logout">Logout</a>
        <?php else: ?>
          <a href="login.php" class="btn-primary">Get Started</a>
        <?php endif; ?>
      </div>

      <button class="hamburger" onclick="toggleMenu()">☰</button>
    </div>
  </header>

  <!-- TEAM -->
  <section id="team">
    <div class="container">
      <h2 class="section-title">DEDICATED DIGITAL EXPERT TEAM</h2>
      <p class="section-sub">Meet the passionate people behind your success.</p>
      <div class="team-grid">
        <div class="team-card"><div class="avatar"><img src="images/sir asad.png" alt="Jahangir" /></div><h4>Engr Asad B Zaman</h4><p>CEO</p></div>
        <div class="team-card"><div class="avatar"><img src="images/sir umair.jpg" alt="Asfahan" /></div><h4>Umair Qayyum</h4><p>Marketing Director</p></div>
        <div class="team-card"><div class="avatar"><img src="images/naghma riaz.png" alt="Rashed" /></div><h4>Naghma Riaz</h4><p>Project Manager</p></div>
        <div class="team-card"><div class="avatar"><img src="images/faizan.png" alt="Asmar" /></div><h4>Muhammad Faizan</h4><p>AI developer-credit</p></div>
        <div class="team-card"><div class="avatar"><img src="images/shgufta.jpg" alt="Ali Raza" /></div><h4>Shgufta Munir</h4><p>Digital Marketer</p></div>
        <div class="team-card"><div class="avatar"><img src="images/team-6.jpg" alt="Ali Yar" /></div><h4>Hassnain</h4><p>Ebay Expert</p></div>
        <div class="team-card"><div class="avatar"><img src="images/team-7.jpg" alt="Aasia" /></div><h4>Ali</h4><p>Video Editor</p></div>
        <div class="team-card"><div class="avatar"><img src="images/team-8.jpg" alt="Bilal" /></div><h4>Sana Fatima</h4><p>Graphic Designer</p></div>
        <!-- Two Additional Team Members -->
        <div class="team-card"><div class="avatar"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop&crop=center" alt="Hamza" /></div><h4>Hamza Ali</h4><p>UI/UX Designer</p></div>
        <div class="team-card"><div class="avatar"><img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&h=200&fit=crop&crop=center" alt="Sara" /></div><h4>Sara Ahmed</h4><p>QA Engineer</p></div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="socials">
        <a href="https://www.facebook.com/hifimarketingglobal/"><i class="fab fa-facebook"></i></a>
        <a href="https://www.instagram.com/hifi.marketing/"><i class="fab fa-instagram"></i></a>
        <a href="https://www.linkedin.com/company/hifi-marketing-global"><i class="fab fa-linkedin"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
      </div>
      <p>&copy; 2026 HIFI Marketing &amp; Technologies. All rights reserved.</p>
    </div>
  </footer>

  <!-- ===== DEVELOPER CREDIT ===== -->
  <div class="developer-credit">
    <p>Developed by <a href="https://foryoumoazma.my.canva.site/fz-cube-tech">Muhammad Faizan</a></p>
  </div>

  <script src="js/main.js"></script>
  <script>
    // ===== MOBILE MENU =====
    function toggleMenu() {
      const menu = document.getElementById('navMenu');
      if (menu) {
        menu.classList.toggle('show');
        menu.classList.toggle('open');
      }
    }

    document.querySelectorAll('#navMenu a').forEach(link => {
      link.addEventListener('click', () => {
        const menu = document.getElementById('navMenu');
        if (menu) {
          menu.classList.remove('show');
          menu.classList.remove('open');
        }
      });
    });

    // ===== THEME TOGGLE =====
    function toggleTheme() {
      const body = document.body;
      if (body) {
        body.classList.toggle('dark-mode');
        if (body.classList.contains('dark-mode')) {
          localStorage.setItem('theme', 'dark');
        } else {
          localStorage.setItem('theme', 'light');
        }
      }
    }

    // ===== LOAD SAVED THEME =====
    document.addEventListener('DOMContentLoaded', function() {
      const savedTheme = localStorage.getItem('theme');
      const body = document.body;
      if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
      }
    });
  </script>
</body>
</html>