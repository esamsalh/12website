<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$tools = getTools();
$categories = getCategories();

// حذف أداة
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $targetTool = getToolById($deleteId);
    if ($targetTool) {
        $cat = getCategoryById($targetTool['category_id'] ?? '');
        if ($cat && isset($targetTool['page_slug'])) {
            $subSlug = !empty($targetTool['sub_slug']) ? trim($targetTool['sub_slug'], '/') . '/' : '';
            $dirName = getCategoryPhysicalDir($cat['slug']);
            $filePath = SITE_PATH . '/' . $dirName . '/' . $subSlug . $targetTool['page_slug'] . '.html';
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        $tools = array_values(array_filter($tools, function($t) use ($deleteId) {
            return $t['id'] !== $deleteId;
        }));
        saveTools($tools);
        if ($cat) {
            generateCategoryIndex($cat['id']);
        }
    }
    $_SESSION['flash'] = 'تم حذف الأداة';
    $_SESSION['flash_type'] = 'success';
    header('Location: tools.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h1>جميع الأدوات (<?= count($tools) ?>)</h1>
    <a href="add_tool.php" class="btn btn-primary">+ إضافة أداة جديدة</a>
</div>

<?php if (empty($tools)): ?>
    <div class="card">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            <h3>لا توجد أدوات</h3>
            <p>أضف أول أداة الآن</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العنوان</th>
                        <th>التصنيف</th>
                        <th>الحالة</th>
                        <th>الصفحة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach (array_reverse($tools) as $t): 
                        $cat = getCategoryById($t['category_id'] ?? '');
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($t['title_ar']) ?></strong></td>
                            <td><?= $cat ? htmlspecialchars($cat['name_ar']) : '<span style="color:#ef4444;">غير مصنف</span>' ?></td>
                            <td><?= isset($t['page_slug']) ? '<span style="color:#10B981;">منشور</span>' : '<span style="color:#F59E0B;">مسودة</span>' ?></td>
                            <td>
                                <?php if (isset($t['page_slug']) && $cat): 
                                    $subSlug = !empty($t['sub_slug']) ? trim($t['sub_slug'], '/') . '/' : '';
                                ?>
                                    <a href="../<?= htmlspecialchars(getCategoryPhysicalDir($cat['slug'])) ?>/<?= $subSlug ?><?= htmlspecialchars($t['page_slug']) ?>.html" target="_blank" style="color:#6366F1;">عرض</a>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="edit_tool.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                                <a href="tools.php?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete()">حذف</a>
                                <?php if (!isset($t['page_slug'])): ?>
                                    <a href="add_tool.php?action=generate&id=<?= $t['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('إنشاء الصفحة الآن؟')">نشر</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
