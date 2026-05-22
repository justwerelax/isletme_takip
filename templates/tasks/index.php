<?php
// Yapılacaklar sayfası — TaskController tarafından çağrılır

$priorityLabel = ['low' => 'Düşük', 'medium' => 'Orta', 'high' => 'Yüksek'];

// Öncelik: ikon + renk paleti (sol çizgi yerine ikon bazlı)
$priorityIcon  = ['low' => 'minus', 'medium' => 'equal', 'high' => 'flame'];
$priorityColor = ['low' => '#64748b', 'medium' => '#3b82f6', 'high' => '#f97316'];
$priorityBg    = ['low' => 'rgba(100,116,139,0.12)', 'medium' => 'rgba(59,130,246,0.12)', 'high' => 'rgba(249,115,22,0.12)'];
?>

<!-- Üst Bar -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <div class="task-stat-pill">
            <i data-lucide="list-checks" style="width:15px;height:15px;"></i>
            <span>Bekleyen: <strong><?= (int)$stats['total'] - (int)$stats['done'] ?></strong></span>
        </div>
        <div class="task-stat-pill" style="background:rgba(16,185,129,0.1); border-color:rgba(16,185,129,0.2); color:#10b981;">
            <i data-lucide="check-circle-2" style="width:15px;height:15px;"></i>
            <span>Tamamlanan: <strong><?= (int)$stats['done'] ?></strong></span>
        </div>
        <?php if ($stats['urgent'] > 0): ?>
        <div class="task-stat-pill" style="background:rgba(249,115,22,0.1); border-color:rgba(249,115,22,0.25); color:#f97316;">
            <i data-lucide="flame" style="width:15px;height:15px;"></i>
            <span>Acil: <strong><?= (int)$stats['urgent'] ?></strong></span>
        </div>
        <?php endif; ?>
    </div>
    <div style="display:flex; gap:10px;">
        <button onclick="openModal('addCategoryModal')" class="btn btn-ghost btn-sm">
            <i data-lucide="folder-plus"></i> Kategori Ekle
        </button>
        <button onclick="openModal('addTaskModal')" class="btn btn-primary btn-sm">
            <i data-lucide="plus"></i> Görev Ekle
        </button>
    </div>
</div>

<!-- ===== AKTİF GÖREVLER ===== -->
<?php if (empty($categories)): ?>
<div class="empty-state">
    <i data-lucide="clipboard-list" style="width:64px;height:64px;color:#64748b"></i>
    <h3>Henüz kategori yok</h3>
    <p>Başlamak için bir kategori oluşturun.</p>
    <button onclick="openModal('addCategoryModal')" class="btn btn-primary">Kategori Ekle</button>
</div>
<?php else: ?>

<div class="tasks-grid">
    <?php foreach ($categories as $cat): ?>
    <div class="task-category-card">
        <!-- Kategori Başlık -->
        <div class="task-cat-header" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
            <div style="display:flex; align-items:center; gap:10px; flex:1;">
                <div class="task-cat-icon">
                    <i data-lucide="<?= htmlspecialchars($cat['icon']) ?>"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:15px; font-weight:700;"><?= htmlspecialchars($cat['name']) ?></h3>
                    <span style="font-size:11px; opacity:0.65;">
                        <?= $cat['pending'] ?> bekliyor
                    </span>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <button onclick="openAddTaskFor(<?= $cat['id'] ?>)"
                        class="btn btn-ghost btn-sm" style="padding:5px 8px;" title="Görev ekle">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i>
                </button>
                <form method="POST" action="?page=tasks&action=deleteCategory" style="display:inline;"
                      onsubmit="return confirm('Bu kategori ve tüm görevleri silinecek. Emin misiniz?')">
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" style="padding:5px 8px; color:var(--danger);" title="Sil">
                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- İlerleme Çubuğu -->
        <?php if ($cat['total'] > 0): ?>
        <div class="task-progress-bar">
            <div class="task-progress-fill"
                 style="width:<?= round(($cat['done'] / $cat['total']) * 100) ?>%;
                        background:<?= htmlspecialchars($cat['color']) ?>">
            </div>
        </div>
        <?php endif; ?>

        <!-- Görev Listesi (sadece bekleyenler) -->
        <div class="task-list">
            <?php if (empty($cat['tasks'])): ?>
            <div style="text-align:center; padding:22px 0; color:var(--text-muted); font-size:13px;">
                <i data-lucide="inbox" style="width:26px;height:26px; display:block; margin:0 auto 8px; opacity:0.35;"></i>
                Bekleyen görev yok
            </div>
            <?php else: ?>
                <?php foreach ($cat['tasks'] as $task):
                    $pColor = $priorityColor[$task['priority']];
                    $pBg    = $priorityBg[$task['priority']];
                    $pIcon  = $priorityIcon[$task['priority']];
                ?>
                <div class="task-item">
                    <!-- Öncelik göstergesi (ikon + renkli dot) -->
                    <div class="task-priority-dot" style="background:<?= $pBg ?>; color:<?= $pColor ?>;"
                         title="<?= $priorityLabel[$task['priority']] ?>">
                        <i data-lucide="<?= $pIcon ?>" style="width:12px;height:12px;"></i>
                    </div>

                    <!-- Checkbox -->
                    <form method="POST" action="?page=tasks&action=toggleTask" style="display:contents;">
                        <input type="hidden" name="id" value="<?= $task['id'] ?>">
                        <button type="submit" class="task-checkbox" title="Tamamlandı işaretle">
                            <i data-lucide="circle"></i>
                        </button>
                    </form>

                    <div class="task-body">
                        <span class="task-title"><?= htmlspecialchars($task['title']) ?></span>
                        <?php if ($task['description']): ?>
                        <span class="task-desc"><?= htmlspecialchars($task['description']) ?></span>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="?page=tasks&action=deleteTask" style="display:contents;">
                        <input type="hidden" name="id" value="<?= $task['id'] ?>">
                        <button type="submit" class="task-delete-btn" title="Sil"
                                onclick="return confirm('Bu görevi silmek istediğinize emin misiniz?')">
                            <i data-lucide="x" style="width:13px;height:13px;"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ===== ARŞİV ===== -->
<?php if (!empty($archiveCategories)): ?>
<div style="margin-top:40px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
        <div style="flex:1; height:1px; background:var(--border);"></div>
        <button onclick="toggleArchive()" id="archiveToggleBtn"
                style="display:flex; align-items:center; gap:8px; background:rgba(30,41,59,0.5);
                       border:1px solid var(--border); border-radius:20px; padding:7px 18px;
                       color:var(--text-muted); font-size:13px; font-weight:600; cursor:pointer;
                       transition:all 0.2s;">
            <i data-lucide="archive" style="width:15px;height:15px;"></i>
            Arşiv
            <span style="background:rgba(16,185,129,0.15); color:#10b981; border-radius:20px;
                         padding:1px 8px; font-size:11px; font-weight:700;"><?= (int)$stats['done'] ?></span>
            <i data-lucide="chevron-down" id="archiveChevron" style="width:14px;height:14px; transition:transform 0.25s;"></i>
        </button>
        <div style="flex:1; height:1px; background:var(--border);"></div>
    </div>

    <div id="archiveArea" style="display:none;">
        <div class="tasks-grid">
            <?php foreach ($archiveCategories as $cat): ?>
            <?php if (empty($cat['archived'])) continue; ?>
            <div class="task-category-card" style="opacity:0.85;">
                <div class="task-cat-header" style="--cat-color: <?= htmlspecialchars($cat['color']) ?>">
                    <div style="display:flex; align-items:center; gap:10px; flex:1;">
                        <div class="task-cat-icon">
                            <i data-lucide="<?= htmlspecialchars($cat['icon']) ?>"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:15px; font-weight:700;"><?= htmlspecialchars($cat['name']) ?></h3>
                            <span style="font-size:11px; opacity:0.65; color:#10b981;">
                                <?= $cat['done'] ?> tamamlandı
                            </span>
                        </div>
                    </div>
                </div>

                <div class="task-list">
                    <?php foreach ($cat['archived'] as $task):
                        $pColor = $priorityColor[$task['priority']];
                        $pBg    = $priorityBg[$task['priority']];
                        $pIcon  = $priorityIcon[$task['priority']];
                    ?>
                    <div class="task-item task-done">
                        <div class="task-priority-dot" style="background:<?= $pBg ?>; color:<?= $pColor ?>; opacity:0.5;"
                             title="<?= $priorityLabel[$task['priority']] ?>">
                            <i data-lucide="<?= $pIcon ?>" style="width:12px;height:12px;"></i>
                        </div>

                        <!-- Geri al -->
                        <form method="POST" action="?page=tasks&action=toggleTask" style="display:contents;">
                            <input type="hidden" name="id" value="<?= $task['id'] ?>">
                            <button type="submit" class="task-checkbox" title="Geri al" style="color:#10b981;">
                                <i data-lucide="check-circle-2"></i>
                            </button>
                        </form>

                        <div class="task-body">
                            <span class="task-title"><?= htmlspecialchars($task['title']) ?></span>
                            <?php if ($task['description']): ?>
                            <span class="task-desc"><?= htmlspecialchars($task['description']) ?></span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="?page=tasks&action=deleteTask" style="display:contents;">
                            <input type="hidden" name="id" value="<?= $task['id'] ?>">
                            <button type="submit" class="task-delete-btn" title="Sil"
                                    onclick="return confirm('Bu görevi silmek istediğinize emin misiniz?')">
                                <i data-lucide="x" style="width:13px;height:13px;"></i>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- ===================== MODAL: Görev Ekle ===================== -->
<div class="modal-overlay" id="addTaskModal">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header">
            <h3><i data-lucide="plus-circle"></i> Yeni Görev</h3>
            <button type="button" class="modal-close" onclick="closeModal('addTaskModal')">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form method="POST" action="?page=tasks&action=addTask">
            <div class="modal-body" style="display:flex; flex-direction:column; gap:16px;">
                <div class="form-group">
                    <label class="form-label">Kategori <span style="color:var(--danger)">*</span></label>
                    <select name="category_id" id="taskCategorySelect" class="select-input" required>
                        <option value="">— Seçin —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Görev Başlığı <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="title" class="form-input" placeholder="Görev başlığını yazın..." required maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">Açıklama <span style="color:var(--text-muted); font-weight:400;">(isteğe bağlı)</span></label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Detay veya notlar..." style="resize:vertical;"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Öncelik</label>
                    <div style="display:flex; gap:10px;">
                        <label class="priority-radio">
                            <input type="radio" name="priority" value="low">
                            <span class="priority-radio-label low">
                                <i data-lucide="minus" style="width:13px;height:13px;"></i> Düşük
                            </span>
                        </label>
                        <label class="priority-radio">
                            <input type="radio" name="priority" value="medium" checked>
                            <span class="priority-radio-label medium">
                                <i data-lucide="equal" style="width:13px;height:13px;"></i> Orta
                            </span>
                        </label>
                        <label class="priority-radio">
                            <input type="radio" name="priority" value="high">
                            <span class="priority-radio-label high">
                                <i data-lucide="flame" style="width:13px;height:13px;"></i> Yüksek
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addTaskModal')">İptal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> Ekle</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== MODAL: Kategori Ekle ===================== -->
<div class="modal-overlay" id="addCategoryModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3><i data-lucide="folder-plus"></i> Yeni Kategori</h3>
            <button type="button" class="modal-close" onclick="closeModal('addCategoryModal')">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form method="POST" action="?page=tasks&action=addCategory">
            <div class="modal-body" style="display:flex; flex-direction:column; gap:16px;">
                <div class="form-group">
                    <label class="form-label">Kategori Adı <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="örn. Elektrik İşleri" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">İkon <span style="color:var(--text-muted); font-weight:400;">(Lucide ikon adı)</span></label>
                    <input type="text" name="icon" class="form-input" value="folder" placeholder="hammer, wrench, file-text...">
                    <small style="color:var(--text-muted); font-size:11px;">
                        <a href="https://lucide.dev/icons/" target="_blank" style="color:var(--accent);">lucide.dev/icons</a> adresinden ikon adı bulabilirsiniz.
                    </small>
                </div>
                <div class="form-group">
                    <label class="form-label">Renk</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="color" name="color" value="#818cf8"
                               style="width:44px; height:36px; border:none; background:none; cursor:pointer; padding:0;">
                        <span style="font-size:12px; color:var(--text-muted);">Kategori rengi</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addCategoryModal')">İptal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> Oluştur</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddTaskFor(catId) {
    const sel = document.getElementById('taskCategorySelect');
    if (sel) sel.value = catId;
    openModal('addTaskModal');
}

function toggleArchive() {
    const area    = document.getElementById('archiveArea');
    const chevron = document.getElementById('archiveChevron');
    const btn     = document.getElementById('archiveToggleBtn');
    const isOpen  = area.style.display !== 'none';

    area.style.display = isOpen ? 'none' : 'block';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    btn.style.color = isOpen ? 'var(--text-muted)' : 'var(--text-main)';
}
</script>

<style>
/* ===== TASKS PAGE STYLES ===== */

.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.task-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: rgba(30,41,59,0.5);
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: 13px;
    color: var(--text-secondary);
}

/* Kategori Kartı */
.task-category-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.task-cat-header {
    padding: 14px 16px;
    background: rgba(30,41,59,0.4);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
    border-top: 3px solid var(--cat-color, #818cf8);
}

.task-cat-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--cat-color, #818cf8) 15%, transparent);
    color: var(--cat-color, #818cf8);
    flex-shrink: 0;
}

/* İlerleme Çubuğu */
.task-progress-bar {
    height: 3px;
    background: rgba(255,255,255,0.04);
}
.task-progress-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.4s ease;
}

/* Görev Listesi */
.task-list {
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
}

.task-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 8px;
    border-radius: 9px;
    border: 1px solid transparent;
    transition: background 0.15s, border-color 0.15s;
}
.task-item:hover {
    background: var(--bg-hover);
    border-color: var(--border);
}
.task-item:hover .task-delete-btn { opacity: 1; }

/* Tamamlanan */
.task-item.task-done .task-title {
    text-decoration: line-through;
    opacity: 0.4;
}
.task-item.task-done { opacity: 0.75; }

/* Öncelik dot (ikon kutucuğu) */
.task-priority-dot {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Checkbox */
.task-checkbox {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    color: var(--text-muted);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    transition: color 0.15s, transform 0.15s;
}
.task-checkbox:hover { color: #10b981; transform: scale(1.15); }
.task-checkbox svg { width: 17px; height: 17px; }

/* Görev içeriği */
.task-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}
.task-title {
    font-size: 13.5px;
    font-weight: 500;
    color: var(--text-main);
    line-height: 1.4;
    word-break: break-word;
}
.task-desc {
    font-size: 11.5px;
    color: var(--text-muted);
    line-height: 1.4;
    word-break: break-word;
}

/* Sil butonu */
.task-delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    opacity: 0;
    padding: 3px;
    border-radius: 6px;
    transition: opacity 0.15s, color 0.15s, background 0.15s;
    flex-shrink: 0;
}
.task-delete-btn:hover {
    color: #ef4444;
    background: rgba(239,68,68,0.1);
}

/* Öncelik radio butonları */
.priority-radio { cursor: pointer; }
.priority-radio input { display: none; }
.priority-radio-label {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    border: 1.5px solid var(--border);
    color: var(--text-muted);
    transition: all 0.15s;
    cursor: pointer;
}
.priority-radio input:checked + .priority-radio-label.low    { background: rgba(100,116,139,0.15); border-color: #64748b; color: #94a3b8; }
.priority-radio input:checked + .priority-radio-label.medium { background: rgba(59,130,246,0.15);  border-color: #3b82f6; color: #3b82f6; }
.priority-radio input:checked + .priority-radio-label.high   { background: rgba(249,115,22,0.15);  border-color: #f97316; color: #f97316; }
.priority-radio-label:hover { border-color: var(--accent); color: var(--accent); }
</style>
