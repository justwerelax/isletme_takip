<?php
class TaskController {

    public function index() {
        $user = Auth::user();

        $categories = Database::fetchAll("
            SELECT * FROM task_categories WHERE is_active = 1 ORDER BY sort_order, id
        ");

        // Her kategoriye ait bekleyen görevleri çek
        foreach ($categories as &$cat) {
            $cat['tasks'] = Database::fetchAll("
                SELECT t.*, u.full_name as creator_name
                FROM tasks t
                LEFT JOIN users u ON t.created_by = u.id
                WHERE t.category_id = ? AND t.is_done = 0
                ORDER BY
                    FIELD(t.priority,'high','medium','low'),
                    t.sort_order ASC, t.created_at DESC
            ", [$cat['id']]);

            $cat['archived'] = Database::fetchAll("
                SELECT t.*, u.full_name as creator_name
                FROM tasks t
                LEFT JOIN users u ON t.created_by = u.id
                WHERE t.category_id = ? AND t.is_done = 1
                ORDER BY t.done_at DESC
            ", [$cat['id']]);

            $cat['pending'] = count($cat['tasks']);
            $cat['done']    = count($cat['archived']);
            $cat['total']   = $cat['pending'] + $cat['done'];
        }
        unset($cat);

        // Arşiv: tüm tamamlananlar kategori bazında
        $archiveCategories = [];
        foreach ($categories as $cat) {
            if (!empty($cat['archived'])) {
                $archiveCategories[] = $cat;
            }
        }

        // Özet istatistikler
        $stats = Database::fetch("
            SELECT
                COUNT(*) as total,
                SUM(is_done) as done,
                SUM(priority = 'high' AND is_done = 0) as urgent
            FROM tasks
        ");

        $pageTitle  = 'Yapılacaklar';
        $currentPage = 'tasks';
        require BASE_PATH . '/templates/layout.php';
    }

    // -------------------------------------------------------
    // Görev ekle
    // -------------------------------------------------------
    public function addTask() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?page=tasks"); exit;
        }

        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority    = in_array($_POST['priority'] ?? '', ['low','medium','high']) ? $_POST['priority'] : 'medium';
        $user        = Auth::user();

        if (!$title || !$categoryId) {
            $_SESSION['flash_error'] = 'Başlık ve kategori zorunludur.';
            header("Location: ?page=tasks"); exit;
        }

        Database::insert('tasks', [
            'category_id'  => $categoryId,
            'title'        => $title,
            'description'  => $description ?: null,
            'priority'     => $priority,
            'created_by'   => $user['id'],
        ]);

        $_SESSION['flash_success'] = 'Görev eklendi.';
        header("Location: ?page=tasks"); exit;
    }

    // -------------------------------------------------------
    // Tamamlandı / Geri al (toggle)
    // -------------------------------------------------------
    public function toggleTask() {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { header("Location: ?page=tasks"); exit; }

        $task = Database::fetch("SELECT * FROM tasks WHERE id = ?", [$id]);
        if (!$task) { header("Location: ?page=tasks"); exit; }

        $newDone  = $task['is_done'] ? 0 : 1;
        $doneAt   = $newDone ? date('Y-m-d H:i:s') : null;

        Database::update('tasks', ['is_done' => $newDone, 'done_at' => $doneAt], "id = ?", [$id]);

        header("Location: ?page=tasks"); exit;
    }

    // -------------------------------------------------------
    // Görev sil
    // -------------------------------------------------------
    public function deleteTask() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Database::query("DELETE FROM tasks WHERE id = ?", [$id]);
            $_SESSION['flash_success'] = 'Görev silindi.';
        }
        header("Location: ?page=tasks"); exit;
    }

    // -------------------------------------------------------
    // Kategori ekle
    // -------------------------------------------------------
    public function addCategory() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?page=tasks"); exit;
        }

        $name  = trim($_POST['name'] ?? '');
        $icon  = trim($_POST['icon'] ?? 'folder');
        $color = trim($_POST['color'] ?? '#818cf8');

        if (!$name) {
            $_SESSION['flash_error'] = 'Kategori adı zorunludur.';
            header("Location: ?page=tasks"); exit;
        }

        $slug = $this->makeSlug($name);

        Database::insert('task_categories', [
            'name'  => $name,
            'slug'  => $slug,
            'icon'  => $icon,
            'color' => $color,
        ]);

        $_SESSION['flash_success'] = 'Kategori eklendi.';
        header("Location: ?page=tasks"); exit;
    }

    // -------------------------------------------------------
    // Kategori sil
    // -------------------------------------------------------
    public function deleteCategory() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            Database::query("DELETE FROM task_categories WHERE id = ?", [$id]);
            $_SESSION['flash_success'] = 'Kategori ve görevleri silindi.';
        }
        header("Location: ?page=tasks"); exit;
    }

    // -------------------------------------------------------
    // Yardımcı: slug üret
    // -------------------------------------------------------
    private function makeSlug(string $text): string {
        $map = ['ğ'=>'g','ü'=>'u','ş'=>'s','ı'=>'i','ö'=>'o','ç'=>'c',
                'Ğ'=>'g','Ü'=>'u','Ş'=>'s','İ'=>'i','Ö'=>'o','Ç'=>'c'];
        $text = strtr($text, $map);
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        // Benzersizlik için suffix ekle
        $base = $text;
        $i = 1;
        while (Database::fetch("SELECT id FROM task_categories WHERE slug = ?", [$text])) {
            $text = $base . '-' . $i++;
        }
        return $text;
    }
}
