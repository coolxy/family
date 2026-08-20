<?php
// 文件: pages/guestbook.php - 留言板
$db = getDB();
$loggedIn = isLoggedIn();

// 处理留言提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $name = SQLite3::escapeString(trim($_POST['name'] ?? ''));
        $content = SQLite3::escapeString(trim($_POST['content'] ?? ''));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (empty($name) || empty($content)) {
            $error = '姓名和内容不能为空';
        } else {
            $db->exec("INSERT INTO guestbook (name, content, ip) VALUES ('$name', '$content', '$ip')");
            $success = '留言成功，等待审核后显示';
            // 重定向避免重复提交
            header('Location: ?page=guestbook&msg=' . urlencode($success));
            exit;
        }
    }
    
    if ($action === 'reply' && $loggedIn) {
        $id = intval($_POST['id'] ?? 0);
        $reply = SQLite3::escapeString(trim($_POST['reply'] ?? ''));
        if (!empty($reply)) {
            $db->exec("UPDATE guestbook SET reply = '$reply', reply_at = datetime('now') WHERE id = $id");
            header('Location: ?page=guestbook&msg=回复成功');
            exit;
        }
    }
    
    if ($action === 'delete' && $loggedIn) {
        $id = intval($_POST['id'] ?? 0);
        $db->exec("DELETE FROM guestbook WHERE id = $id");
        header('Location: ?page=guestbook&msg=留言已删除');
        exit;
    }
    
    if ($action === 'toggle_status' && $loggedIn) {
        $id = intval($_POST['id'] ?? 0);
        $db->exec("UPDATE guestbook SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = $id");
        header('Location: ?page=guestbook&msg=状态已更新');
        exit;
    }
}

// 获取留言列表（游客只看审核通过的，管理员看全部）
if ($loggedIn) {
    $result = $db->query("SELECT * FROM guestbook ORDER BY created_at DESC");
} else {
    $result = $db->query("SELECT * FROM guestbook WHERE status = 1 ORDER BY created_at DESC");
}

$messages = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $messages[] = $row;
}
?>
<div class="page-header">
    <h2>💬 留言板</h2>
    <p style="color:#8a7a6a; font-size:0.9rem; margin-top:4px;">欢迎留下您的意见、建议或家族故事</p>
</div>

<!-- 留言表单 -->
<div class="guestbook-form">
    <h3>📝 写留言</h3>
    <?php if (isset($_GET['msg'])): ?>
        <div class="msg" style="margin-bottom:12px;">✦ <?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <!-- 称呼（单独一行） -->
        <div class="form-group">
            <label>您的称呼 *</label>
            <input type="text" name="name" required placeholder="请输入您的姓名或昵称">
        </div>
        <!-- 留言内容（单独一行） -->
        <div class="form-group">
            <label>留言内容 *</label>
            <textarea name="content" rows="4" required placeholder="请输入您的留言内容..."></textarea>
        </div>
        <button type="submit" class="btn">📤 提交留言</button>
    </form>
</div>

<!-- 留言列表 -->
<div class="guestbook-list">
    <h3>📋 全部留言 (<?= count($messages) ?>条)</h3>
    <?php if (empty($messages)): ?>
        <div class="empty-state">暂无留言，快来写下第一条吧！</div>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
            <div class="guestbook-item" id="msg-<?= $m['id'] ?>">
                <div class="gb-header">
                    <span class="gb-name"><?= htmlspecialchars($m['name']) ?></span>
                    <span class="gb-time"><?= date('Y-m-d H:i', strtotime($m['created_at'])) ?></span>
                    <?php if (!$loggedIn && $m['status'] == 0): ?>
                        <span class="gb-status" style="color:#f39c12;">⏳ 审核中</span>
                    <?php endif; ?>
                    <?php if ($loggedIn): ?>
                        <span class="gb-status" style="color:<?= $m['status'] ? '#27ae60' : '#f39c12' ?>;">
                            <?= $m['status'] ? '✅ 已公开' : '⏳ 待审核' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="gb-content"><?= nl2br(htmlspecialchars($m['content'])) ?></div>
                
                <!-- 管理员回复 -->
                <?php if (!empty($m['reply'])): ?>
                    <div class="gb-reply">
                        <span class="gb-reply-label">📌 管理员回复：</span>
                        <?= nl2br(htmlspecialchars($m['reply'])) ?>
                        <span class="gb-time" style="font-size:0.75rem;">
                            —— <?= date('Y-m-d H:i', strtotime($m['reply_at'])) ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <!-- 管理员操作 -->
                <?php if ($loggedIn): ?>
                    <div class="gb-actions">
                        <button class="btn-sm btn-edit" onclick="toggleReply(<?= $m['id'] ?>)">💬 回复</button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn-sm <?= $m['status'] ? 'btn-edit' : 'btn-warning' ?>">
                                <?= $m['status'] ? '🔒 隐藏' : '🔓 显示' ?>
                            </button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('确认删除此留言？')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn-sm btn-danger">🗑️ 删除</button>
                        </form>
                    </div>
                    <!-- 回复表单 -->
                    <div class="gb-reply-form" id="replyForm-<?= $m['id'] ?>" style="display:none; margin-top:10px;">
                        <form method="post">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <div class="form-row">
                                <div class="form-group" style="flex:3;">
                                    <textarea name="reply" rows="2" placeholder="输入回复内容..."></textarea>
                                </div>
                                <div class="form-group" style="flex:0 0 auto;">
                                    <button type="submit" class="btn" style="margin-top:8px;">💾 回复</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
/* ============================================================
   留言板样式
   ============================================================ */
.guestbook-form {
    background: #fcf7f2;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid #e6d7c6;
}
.guestbook-form h3 {
    color: #5f4a34;
    margin-bottom: 12px;
    font-weight: 400;
    letter-spacing: 1px;
}

.guestbook-list h3 {
    color: #5f4a34;
    margin-bottom: 16px;
    font-weight: 400;
    letter-spacing: 1px;
}

.guestbook-item {
    background: #ffffff;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 14px;
    border-left: 4px solid #b2977d;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: 0.15s;
}
.guestbook-item:hover {
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.gb-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 16px;
    margin-bottom: 8px;
}
.gb-name {
    font-weight: 700;
    font-size: 1.05rem;
    color: #3f2e1d;
}
.gb-time {
    font-size: 0.8rem;
    color: #8a7a6a;
}
.gb-status {
    font-size: 0.75rem;
    padding: 2px 10px;
    border-radius: 20px;
    background: #f0e8df;
    color: #6f5b47;
}
.gb-content {
    color: #3e2e1f;
    line-height: 1.7;
    padding: 4px 0;
    word-break: break-word;
}

.gb-reply {
    margin-top: 10px;
    padding: 10px 14px;
    background: #f5efe9;
    border-radius: 10px;
    border-left: 3px solid #b2977d;
    color: #3e2e1f;
    line-height: 1.6;
}
.gb-reply-label {
    font-weight: 600;
    color: #5f4a34;
}

.gb-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #ede5db;
}
.btn-warning {
    background: #f39c12;
    color: white;
}
.btn-warning:hover {
    background: #e08e0b;
}

.gb-reply-form {
    margin-top: 8px;
}

/* 移动端适配 */
@media (max-width: 700px) {
    .guestbook-form {
        padding: 14px;
    }
    .guestbook-item {
        padding: 12px 14px;
    }
    .gb-header {
        gap: 4px 8px;
    }
    .gb-name {
        font-size: 0.95rem;
    }
    .gb-actions {
        gap: 4px;
    }
    .gb-actions .btn-sm {
        font-size: 0.7rem;
        padding: 4px 10px;
    }
}
</style>

<script>
function toggleReply(id) {
    const form = document.getElementById('replyForm-' + id);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}
</script>