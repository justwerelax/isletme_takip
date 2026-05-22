<?php
class SettingsController {
    
    public function __construct() {
        Auth::requireAdmin(); // Ayarlar sayfasına sadece admin girebilir
    }

    public function index() {
        $user = Auth::user();
        
        $partners   = Database::fetchAll("SELECT * FROM partners ORDER BY sort_order, id");
        $allCats    = Database::fetchAll("SELECT * FROM expense_categories ORDER BY sort_order, id");
        // Ana kategoriler önce, altındaki alt kategoriler hemen arkasına gelecek şekilde sırala
        $parentCategories = array_values(array_filter($allCats, fn($c) => !$c['parent_id']));
        $categories = [];
        foreach ($parentCategories as $p) {
            $categories[] = $p;
            foreach ($allCats as $c) {
                if ((int)$c['parent_id'] === (int)$p['id']) {
                    $categories[] = $c;
                }
            }
        }
        $users      = Database::fetchAll("SELECT id, username, full_name, role, is_active FROM users ORDER BY created_at");

        $pageTitle = 'Sistem Ayarları';
        $currentPage = 'settings';
        require BASE_PATH . '/templates/layout.php';
    }

    public function savePartner() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name']);
            
            // Format rate "0,47" to "0.47" if user types comma, or "47" to "0.47"
            $rateStr = str_replace(',', '.', $_POST['profit_share']);
            $profitShare = (float)$rateStr;
            if ($profitShare > 1) {
                $profitShare = $profitShare / 100; // Auto convert 47 to 0.47
            }
            
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isCashReserve = isset($_POST['is_cash_reserve']) ? 1 : 0;
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            // Toplam kontrolü (sadece aktif ortaklar için)
            if ($isActive) {
                $otherTotal = Database::fetch("SELECT SUM(profit_share) as total FROM partners WHERE id != ? AND is_active = 1", [$id])['total'] ?? 0;
                if (($otherTotal + $profitShare) > 1.0001) {
                    $_SESSION['flash_error'] = 'Toplam pay %100\'ü geçemez. Lütfen "Pay Dağıtımı" aracını kullanın veya diğer ortakların payını düşürün.';
                    header("Location: ?page=settings");
                    exit;
                }
            }

            if ($id > 0) {
                Database::update('partners', [
                    'name' => $name,
                    'profit_share' => $profitShare,
                    'sort_order' => $sortOrder,
                    'is_cash_reserve' => $isCashReserve,
                    'is_active' => $isActive
                ], "id = ?", [$id]);
                $_SESSION['flash_success'] = 'Ortak bilgileri güncellendi.';
            } else {
                Database::insert('partners', [
                    'name' => $name,
                    'profit_share' => $profitShare,
                    'sort_order' => $sortOrder,
                    'is_cash_reserve' => $isCashReserve,
                    'is_active' => $isActive
                ]);
                $_SESSION['flash_success'] = 'Yeni ortak eklendi.';
            }
        }
        header("Location: ?page=settings");
        exit;
    }

    public function bulkSaveShares() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shares'])) {
            $shares = $_POST['shares'];
            $total = 0;
            foreach ($shares as $id => $val) {
                $total += (float)$val;
            }

            // Basit doğrulama (0.1 tolerans ile yuvarlama hataları için)
            if (abs($total - 100) > 0.1) {
                $_SESSION['flash_error'] = "Toplam pay %100 olmalıdır. (Mevcut: %{$total})";
                header("Location: ?page=settings");
                exit;
            }

            foreach ($shares as $id => $val) {
                $rate = (float)$val / 100;
                Database::update('partners', ['profit_share' => $rate], "id = ?", [(int)$id]);
            }

            $_SESSION['flash_success'] = 'Pay oranları başarıyla güncellendi.';
        }
        header("Location: ?page=settings");
        exit;
    }

    public function saveCategory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id        = (int)($_POST['id'] ?? 0);
            $name      = trim($_POST['name']);
            $parentId  = (int)($_POST['parent_id'] ?? 0) ?: null;
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive  = isset($_POST['is_active']) ? 1 : 0;

            $slug = strtolower(str_replace([' ','ğ','ü','ş','ı','ö','ç','Ğ','Ü','Ş','İ','Ö','Ç'],['-','g','u','s','i','o','c','g','u','s','i','o','c'], $name));
            $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);

            if ($id > 0) {
                Database::update('expense_categories', [
                    'name'       => $name,
                    'slug'       => $slug,
                    'parent_id'  => $parentId,
                    'sort_order' => $sortOrder,
                    'is_active'  => $isActive
                ], "id = ?", [$id]);
                $_SESSION['flash_success'] = 'Kategori güncellendi.';
            } else {
                Database::insert('expense_categories', [
                    'name'       => $name,
                    'slug'       => $slug,
                    'parent_id'  => $parentId,
                    'sort_order' => $sortOrder,
                    'is_active'  => $isActive
                ]);
                $_SESSION['flash_success'] = 'Yeni kategori eklendi.';
            }
        }
        header("Location: ?page=settings");
        exit;
    }

    public function saveUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username']);
            $fullName = trim($_POST['full_name']);
            $role = $_POST['role'] === 'admin' ? 'admin' : 'viewer';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $password = $_POST['password'] ?? '';

            $data = [
                'username' => $username,
                'full_name' => $fullName,
                'role' => $role,
                'is_active' => $isActive
            ];

            if ($password !== '') {
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            try {
                if ($id > 0) {
                    Database::update('users', $data, "id = ?", [$id]);
                    $_SESSION['flash_success'] = 'Kullanıcı güncellendi.';
                } else {
                    if ($password === '') {
                        $_SESSION['flash_error'] = 'Yeni kullanıcı için parola zorunludur.';
                        header("Location: ?page=settings");
                        exit;
                    }
                    Database::insert('users', $data);
                    $_SESSION['flash_success'] = 'Yeni kullanıcı eklendi.';
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Kullanıcı adı zaten kullanımda olabilir.';
            }
        }
        header("Location: ?page=settings");
        exit;
    }
}
