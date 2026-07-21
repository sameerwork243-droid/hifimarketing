<?php
// =============================================
// FOOTER.PHP - SIMPLE VERSION
// =============================================
?>

    </main>
    <!-- ===== MAIN CONTENT ENDS HERE ===== -->

    <!-- ============================================= -->
    <!-- ===== FOOTER ===== -->
    <!-- ============================================= -->
    <footer>
        <div class="container">
            <div class="socials">
                <a href="https://www.facebook.com/hifimarketingglobal/"><i class="fab fa-facebook"></i></a>
                <a href="https://www.instagram.com/hifi.marketing/"><i class="fab fa-instagram"></i></a>
                <a href="https://www.linkedin.com/company/hifi-marketing-global"><i class="fab fa-linkedin"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> HIFI Marketing &amp; Technologies. All rights reserved.</p>
            <p class="developer-credit">
                Developed by <a href="https://foryoumoazma.my.canva.site/fz-cube-tech" target="_blank">Muhammad Faizan</a>
            </p>
        </div>
    </footer>

    <!-- ============================================= -->
    <!-- ===== SCRIPTS ===== -->
    <!-- ============================================= -->
    <script src="/HifiWebsite/js/main.js"></script>
    
    <!-- ============================================= -->
    <!-- ===== EXTRA JS (PAGE SPECIFIC) ===== -->
    <!-- ============================================= -->
    <?php if (isset($extra_js)): ?>
        <script src="<?php echo $extra_js; ?>"></script>
    <?php endif; ?>
    
    <!-- ============================================= -->
    <!-- ===== THEME TOGGLE SCRIPT ===== -->
    <!-- ============================================= -->
    <script>
        // =============================================
        // THEME TOGGLE
        // =============================================
        function toggleTheme() {
            const body = document.body;
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        }

        // =============================================
        // MOBILE MENU TOGGLE
        // =============================================
        function toggleMenu() {
            const menu = document.getElementById('navMenu');
            if (menu) {
                menu.classList.toggle('show');
                menu.classList.toggle('open');
            }
        }

        // =============================================
        // LOAD SAVED THEME
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
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
            
            document.addEventListener('click', function(e) {
                const menu = document.getElementById('navMenu');
                const hamburger = document.querySelector('.hamburger');
                if (window.innerWidth <= 768) {
                    if (menu && hamburger) {
                        if (!menu.contains(e.target) && !hamburger.contains(e.target)) {
                            menu.classList.remove('show');
                            menu.classList.remove('open');
                        }
                    }
                }
            });
        });

        console.log('✅ HIFI Website Loaded Successfully!');
    </script>

</body>
</html>