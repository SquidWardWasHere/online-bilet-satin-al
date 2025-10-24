<header>
    <div class="header-content">
        <a href="/" class="logo">🚌 Bussio</a>

        <nav>
            <ul>
                <li><a href="/index.php">Ana Sayfa</a></li>

                <?php if (Security::isLoggedIn()): ?>
                    <?php if (Security::hasRole(ROLE_ADMIN)): ?>
                        <li><a href="/admin/index.php">Admin Panel</a></li>
                    <?php elseif (Security::hasRole(ROLE_COMPANY_ADMIN)): ?>
                        <li><a href="/company_admin/index.php">Firma Panel</a></li>
                    <?php else: ?>
                        <li><a href="/my_tickets.php">Biletlerim</a></li>
                        <li><a href="/profile.php">Hesabım</a></li>
                    <?php endif; ?>

                    <li class="user-info">
                        <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <?php if (Security::hasRole(ROLE_USER)): ?>
                            <?php
                            $db = Database::getInstance()->getConnection();
                            $stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch();
                            ?>
                            <span class="user-balance"><?php echo formatPrice($user['balance']); ?></span>
                        <?php endif; ?>
                    </li>
                    <li><a href="/logout.php">Çıkış</a></li>
                <?php else: ?>
                    <li><a href="/login.php">Giriş Yap</a></li>
                    <li><a href="/register.php">Kayıt Ol</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>