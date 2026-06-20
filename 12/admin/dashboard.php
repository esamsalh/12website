<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$tools = getTools();
$categories = getCategories();
$totalTools = count($tools);
$totalCats = count($categories);
$recentTools = array_slice(array_reverse($tools), 0, 5);

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h1>لوحة التحكم</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="num"><?= $totalTools ?></div>
        <div class="lbl">إجمالي الأدوات</div>
    </div>
    <div class="stat-card">
        <div class="num"><?= $totalCats ?></div>
        <div class="lbl">التصنيفات</div>
    </div>
    <div class="stat-card">
        <div class="num"><?= count(array_filter($tools, function($t) { return isset($t['page_slug']); })) ?></div>
        <div class="lbl">صفحات منشأة</div>
    </div>
    <div class="stat-card">
        <div class="num"><?= count(array_filter($tools, function($t) { $f = $t['category_id'] ?? ''; $cats = getCategories(); foreach($cats as $c) { if($c['id'] === $f) return true; } return false; })) ?></div>
        <div class="lbl">أدوات مصنفة</div>
    </div>
</div>

<div class="card">
    <div class="card-title">آخر الأدوات المضافة</div>
    <?php if (empty($recentTools)): ?>
        <div class="empty-state"><p>لا توجد أدوات بعد. <a href="add_tool.php" style="color:#6366F1;font-weight:700;">أضف أول أداة</a></p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العنوان</th>
                        <th>التصنيف</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($recentTools as $t): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($t['title_ar']) ?></strong></td>
                            <td><?php $cat = getCategoryById($t['category_id'] ?? ''); echo $cat ? htmlspecialchars($cat['name_ar']) : '<span style="color:#ef4444;">غير مصنف</span>'; ?></td>
                            <td><?= isset($t['page_slug']) ? '<span style="color:#10B981;">منشور</span>' : '<span style="color:#F59E0B;">مسودة</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
