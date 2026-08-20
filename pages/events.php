<?php
// 文件: pages/events.php - 家族记事（图片显示在内容最后）
$clanEvents = getClanEvents();
$loggedIn = isLoggedIn();
?>
<div class="page-header"><h2>📅 家族记事</h2></div>

<?php if ($loggedIn): ?>
    <div style="margin-bottom:16px;">
        <button class="btn" onclick="document.getElementById('addEventForm').style.display='block'">➕ 添加记事</button>
        <div id="addEventForm" style="display:none;margin-top:12px;background:#fcf7f2;padding:16px;border-radius:16px;border:1px solid #e6d7c6;">
            <form method="post" action="member_actions.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_event">
                <input type="hidden" name="return_page" value="events">
                <div class="form-row">
                    <div class="form-group"><label>标题</label><input type="text" name="title" required></div>
                    <div class="form-group">
                        <label>日期</label>
                        <input type="date" name="event_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="form-group"><label>内容</label><textarea name="content" rows="4" placeholder="请输入记事内容..."></textarea></div>
                <div class="form-group"><label>图片（可选，将显示在内容最后）</label><input type="file" name="event_image" accept="image/*"></div>
                <button type="submit" class="btn">保存</button>
                <button type="button" class="btn-sm" onclick="document.getElementById('addEventForm').style.display='none'">取消</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($clanEvents)): ?>
    <div class="empty-state">暂无家族记事</div>
<?php else: foreach ($clanEvents as $e): 
    // 处理内容中的图片标记
    $displayContent = $e['content'];
    $displayImage = '';
    // 提取图片标记
    if (preg_match('/\n\n\[图片\]:([^\n]+)/', $displayContent, $matches)) {
        $displayImage = $matches[1];
        // 从内容中移除标记
        $displayContent = str_replace($matches[0], '', $displayContent);
    } elseif (!empty($e['image_path']) && file_exists($e['image_path'])) {
        // 兼容旧数据
        $displayImage = $e['image_path'];
    }
?>
    <div class="event-card" id="event-<?= $e['id'] ?>">
        <div style="font-weight:600;font-size:1.1rem;"><?= htmlspecialchars($e['title']) ?></div>
        <div class="event-date">📅 <?= htmlspecialchars($e['event_date'] ?: '日期未定') ?></div>
        <div class="event-content" style="margin-top:6px;line-height:1.8;">
            <?= nl2br(htmlspecialchars(trim($displayContent))) ?>
        </div>
        <!-- 图片显示在内容最后 -->
        <?php if (!empty($displayImage) && file_exists($displayImage)): ?>
            <div style="margin-top:12px;">
                <img src="<?= htmlspecialchars($displayImage) ?>" alt="<?= htmlspecialchars($e['title']) ?>" 
                     style="max-width:100%;max-height:400px;border-radius:10px;border:1px solid #ede5db;cursor:pointer;"
                     onclick="window.open('<?= htmlspecialchars($displayImage) ?>','_blank')">
            </div>
        <?php endif; ?>
        <?php if ($loggedIn): ?>
            <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap;border-top:1px solid #ede5db;padding-top:10px;">
                <button class="btn-sm btn-edit" onclick="editEvent(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['title'])) ?>', '<?= htmlspecialchars(addslashes(trim($displayContent))) ?>', '<?= htmlspecialchars($e['event_date']) ?>', '<?= htmlspecialchars($displayImage) ?>')">✎ 编辑</button>
                <form method="post" action="member_actions.php" style="display:inline;" onsubmit="return confirm('确认删除？')">
                    <input type="hidden" name="action" value="delete_event">
                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                    <input type="hidden" name="return_page" value="events">
                    <button type="submit" class="btn-sm btn-danger">删除</button>
                </form>
            </div>
            <div id="editEventForm-<?= $e['id'] ?>" style="display:none;margin-top:10px;background:#f5efe9;padding:14px;border-radius:12px;">
                <form method="post" action="member_actions.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_event">
                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                    <input type="hidden" name="return_page" value="events">
                    <div class="form-row">
                        <div class="form-group"><label>标题</label><input type="text" name="title" value="<?= htmlspecialchars($e['title']) ?>" required></div>
                        <div class="form-group">
                            <label>日期</label>
                            <input type="date" name="event_date" value="<?= htmlspecialchars($e['event_date']) ?>">
                        </div>
                    </div>
                    <div class="form-group"><label>内容</label><textarea name="content" rows="4"><?= htmlspecialchars(trim($displayContent)) ?></textarea></div>
                    <div class="form-group"><label>更换图片（可选）</label><input type="file" name="event_image" accept="image/*"></div>
                    <?php if (!empty($displayImage) && file_exists($displayImage)): ?>
                        <div style="margin-bottom:8px;">
                            <label style="font-size:0.85rem;color:#8a7a6a;">当前图片：</label>
                            <img src="<?= htmlspecialchars($displayImage) ?>" style="max-height:100px;border-radius:6px;display:block;margin-top:4px;">
                            <label style="font-size:0.8rem;color:#b17a6b;cursor:pointer;">
                                <input type="checkbox" name="remove_image" value="1"> 删除当前图片
                            </label>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn">保存修改</button>
                    <button type="button" class="btn-sm" onclick="document.getElementById('editEventForm-<?= $e['id'] ?>').style.display='none'">取消</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; endif; ?>

<style>
/* 日期输入框样式 */
input[type="date"] {
    width: 100%;
    padding: 7px 12px;
    border: 1px solid #dacfc2;
    border-radius: 18px;
    background: #ffffff;
    font-size: 0.9rem;
    transition: 0.2s;
    font-family: inherit;
}
input[type="date"]:focus {
    outline: none;
    border-color: #b2977d;
    box-shadow: 0 0 0 3px rgba(178, 151, 125, 0.12);
}
input[type="date"]::-webkit-calendar-picker-indicator {
    padding: 4px;
    cursor: pointer;
    opacity: 0.6;
}
input[type="date"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}
.event-content {
    word-break: break-word;
    white-space: pre-wrap;
}
</style>

<script>
function editEvent(id, title, content, date, image) {
    const form = document.getElementById('editEventForm-' + id);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        // 填充隐藏字段的图片路径
        const imageInput = form.querySelector('input[name="current_image"]');
        if (!imageInput && image) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'current_image';
            hidden.value = image;
            form.appendChild(hidden);
        }
    }
}
</script>