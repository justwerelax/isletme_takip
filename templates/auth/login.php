<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş — İşletme Takip</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-bg">
        <div class="login-bg-orb login-bg-orb-1"></div>
        <div class="login-bg-orb login-bg-orb-2"></div>
        <div class="login-bg-orb login-bg-orb-3"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">İT</div>
                <h1>İşletme Takip</h1>
                <p>Hesabınıza giriş yapın</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success = ($_SESSION['flash_success'] ?? null)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <form method="POST" action="?page=login&action=login" class="login-form">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="username" name="username" placeholder="admin" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" placeholder="••••••" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    Giriş Yap
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
