<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$categories = getCategories();
$route = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $nameAr = trim($_POST['name_ar'] ?? '');
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameFr = trim($_POST['name_fr'] ?? '');
    if ($nameAr) {
        $slug = slugify($nameAr);
        $ids = array_column($categories, 'id');
        if (in_array($slug, $ids)) {
            $slug .= '-' . uniqid();
        }
        $categories[] = [
            'id' => $slug,
            'slug' => $slug,
            'name_ar' => $nameAr,
            'name_en' => $nameEn ?: $nameAr,
            'name_fr' => $nameFr ?: $nameAr,
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveCategories($categories);

        $catDir = SITE_PATH . '/' . getCategoryPhysicalDir($slug);
        if (!is_dir($catDir)) {
            mkdir($catDir, 0755, true);
        }

        $_SESSION['flash'] = 'تم إضافة التصنيف بنجاح وتم إنشاء المجلد';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash'] = 'يرجى إدخال اسم التصنيف بالعربية';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: categories.php');
    exit;
}

if (isset($_GET['delete']) && $route === 'delete') {
    $deleteId = $_GET['delete'];
    $categories = array_values(array_filter($categories, function($c) use ($deleteId) {
        return $c['id'] !== $deleteId;
    }));
    saveCategories($categories);
    $_SESSION['flash'] = 'تم حذف التصنيف';
    $_SESSION['flash_type'] = 'success';
    header('Location: categories.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h1>التصنيفات</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addCatModal').classList.toggle('show')">+ إضافة تصنيف</button>
</div>

<div class="card" id="addCatModal" style="display:none;">
    <div class="card-title">إضافة تصنيف جديد</div>
    <form method="post">
        <div class="grid-2">
            <div class="form-group">
                <label>الاسم (عربي) *</label>
                <input class="form-control" name="name_ar" required>
            </div>
            <div class="form-group">
                <label>الاسم (English)</label>
                <input class="form-control" name="name_en">
            </div>
            <div class="form-group">
                <label>الاسم (Français)</label>
                <input class="form-control" name="name_fr">
            </div>
        </div>
        <p class="hint">سيتم إنشاء مجلد بنفس الاسم في المسار الرئيسي للمشروع</p>
        <div style="display:flex;gap:8px;margin-top:12px;">
            <button class="btn btn-success" type="submit" name="add_category">حفظ التصنيف</button>
            <button class="btn btn-sm" type="button" onclick="document.getElementById('addCatModal').style.display='none'" style="background:#E2E8F0;">إلغاء</button>
        </div>
    </form>
</div>

<?php if (empty($categories)): ?>
    <div class="card">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <h3>لا توجد تصنيفات</h3>
            <p>أضف أول تصنيف لتبدأ في تنظيم أدواتك</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم (عربي)</th>
                        <th>English</th>
                        <th>Français</th>
                        <th>المجلد</th>
                        <th>عدد الأدوات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($categories as $c): 
                        $toolCount = count(array_filter(getTools(), function($t) use ($c) { return ($t['category_id'] ?? '') === $c['id']; }));
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($c['name_ar']) ?></strong></td>
                            <td><?= htmlspecialchars($c['name_en'] ?? '') ?></td>
                            <td><?= htmlspecialchars($c['name_fr'] ?? '') ?></td>
                            <td><code><?= htmlspecialchars($c['slug']) ?>/</code></td>
                            <td><?= $toolCount ?></td>
                            <td class="actions">
                                <a href="categories.php?action=delete&delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete()">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
document.querySelector('.btn-primary[onclick]')?.addEventListener('click', function() {
    var m = document.getElementById('addCatModal');
    m.style.display = m.style.display === 'none' ? 'block' : 'none';
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
