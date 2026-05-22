<?php
class SetupController {
    public function index() {
        // Kurulum zaten yapıldıysa engelle
        try {
            $users = Database::fetchAll("SELECT id FROM users LIMIT 1");
            if (!empty($users)) {
                echo '<div style="font-family:Inter,sans-serif;max-width:500px;margin:100px auto;text-align:center;color:#e2e8f0;background:#1a1e2e;padding:40px;border-radius:16px;">
                    <h2>⚠️ Kurulum zaten yapılmış</h2>
                    <p>Sistem kullanıma hazır.</p>
                    <a href="?page=login" style="color:#818cf8;">Giriş Yap →</a>
                </div>';
                return;
            }
        } catch (\Exception $e) {
            // DB henüz yok, kurulum yapılacak
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->install();
            return;
        }

        $this->showForm();
    }

    private function showForm() {
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Kurulum - İşletme Takip</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>
                *{margin:0;padding:0;box-sizing:border-box}
                body{font-family:'Inter',sans-serif;background:#0a0c13;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center}
                .setup-card{background:#12151f;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:48px;max-width:480px;width:100%}
                h1{font-size:24px;margin-bottom:8px}
                p.sub{color:#94a3b8;margin-bottom:32px}
                label{display:block;font-size:13px;color:#94a3b8;margin-bottom:6px;font-weight:500}
                input{width:100%;padding:12px 16px;background:#1a1e2e;border:1px solid rgba(255,255,255,0.08);border-radius:10px;color:#e2e8f0;font-size:14px;margin-bottom:20px;font-family:inherit}
                input:focus{outline:none;border-color:#818cf8}
                button{width:100%;padding:14px;background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:10px;color:#fff;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit;transition:transform 0.2s}
                button:hover{transform:translateY(-1px)}
                .icon{font-size:40px;margin-bottom:16px}
            </style>
        </head>
        <body>
            <div class="setup-card">
                <div class="icon">🚀</div>
                <h1>Kurulum</h1>
                <p class="sub">Admin hesabınızı oluşturun. Veritabanı otomatik kurulacaktır.</p>
                <form method="POST">
                    <label>Admin Kullanıcı Adı</label>
                    <input type="text" name="username" value="admin" required>
                    <label>Admin Şifre</label>
                    <input type="password" name="password" required minlength="4">
                    <label>Ad Soyad</label>
                    <input type="text" name="full_name" value="Yönetici" required>
                    <button type="submit">Kurulumu Başlat</button>
                </form>
            </div>
        </body>
        </html>
        <?php
    }

    private function install() {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $fullName = trim($_POST['full_name']);

        try {
            // Schema'yı çalıştır
            $config = require BASE_PATH . '/config/database.php';
            $pdo = new PDO(
                "mysql:host={$config['host']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $sql = file_get_contents(BASE_PATH . '/sql/schema.sql');
            $pdo->exec($sql);
            $pdo = null;

            // Admin kullanıcı ekle
            $hash = password_hash($password, PASSWORD_BCRYPT);
            Database::insert('users', [
                'username' => $username,
                'password_hash' => $hash,
                'full_name' => $fullName,
                'role' => 'admin',
            ]);

            echo '<div style="font-family:Inter,sans-serif;max-width:500px;margin:100px auto;text-align:center;color:#e2e8f0;background:#1a1e2e;padding:40px;border-radius:16px;">
                <div style="font-size:40px;margin-bottom:16px">✅</div>
                <h2>Kurulum Tamamlandı!</h2>
                <p style="color:#94a3b8;margin:16px 0">Veritabanı oluşturuldu ve admin hesabınız hazır.</p>
                <a href="?page=login" style="display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-decoration:none;border-radius:10px;font-weight:600">Giriş Yap →</a>
            </div>';

        } catch (\Exception $e) {
            echo '<div style="font-family:Inter,sans-serif;max-width:500px;margin:100px auto;color:#f87171;background:#1a1e2e;padding:40px;border-radius:16px;">
                <h2>❌ Kurulum Hatası</h2>
                <p style="margin-top:12px;color:#94a3b8">' . htmlspecialchars($e->getMessage()) . '</p>
                <a href="?page=setup" style="color:#818cf8;display:inline-block;margin-top:16px">← Tekrar Dene</a>
            </div>';
        }
    }
}
