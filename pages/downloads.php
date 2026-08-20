<?php
// 文件: pages/downloads.php - 资料下载（完整版）
$db = getDB();
$loggedIn = isLoggedIn();
$currentUser = getCurrentUser();

// 判断是否为管理员（用户名 admin）
$isAdmin = ($loggedIn && $currentUser === 'admin');

// ★★★ 文件格式图标映射 ★★★
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf'   => ['icon' => '📄', 'label' => 'PDF'],
        'doc'   => ['icon' => '📝', 'label' => 'Word'],
        'docx'  => ['icon' => '📝', 'label' => 'Word'],
        'xls'   => ['icon' => '📊', 'label' => 'Excel'],
        'xlsx'  => ['icon' => '📊', 'label' => 'Excel'],
        'ppt'   => ['icon' => '📽️', 'label' => 'PowerPoint'],
        'pptx'  => ['icon' => '📽️', 'label' => 'PowerPoint'],
        'txt'   => ['icon' => '📃', 'label' => '文本'],
        'zip'   => ['icon' => '📦', 'label' => '压缩包'],
        'rar'   => ['icon' => '📦', 'label' => '压缩包'],
        '7z'    => ['icon' => '📦', 'label' => '压缩包'],
        'jpg'   => ['icon' => '🖼️', 'label' => '图片'],
        'jpeg'  => ['icon' => '🖼️', 'label' => '图片'],
        'png'   => ['icon' => '🖼️', 'label' => '图片'],
        'gif'   => ['icon' => '🖼️', 'label' => '图片'],
        'webp'  => ['icon' => '🖼️', 'label' => '图片'],
        'mp4'   => ['icon' => '🎬', 'label' => '视频'],
        'avi'   => ['icon' => '🎬', 'label' => '视频'],
        'mkv'   => ['icon' => '🎬', 'label' => '视频'],
        'mp3'   => ['icon' => '🎵', 'label' => '音频'],
        'wav'   => ['icon' => '🎵', 'label' => '音频'],
        'exe'   => ['icon' => '⚙️', 'label' => '可执行'],
        'apk'   => ['icon' => '📱', 'label' => '安卓'],
    ];
    return $icons[$ext] ?? ['icon' => '📎', 'label' => strtoupper($ext)];
}

function getFileExt($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// ★★★ 处理下载请求 ★★★
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $file = $db->querySingle("SELECT * FROM downloads WHERE id = $id", true);
    
    if ($file && !empty($file['file_path']) && file_exists($file['file_path'])) {
        $db->exec("UPDATE downloads SET download_count = download_count + 1 WHERE id = $id");
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $ext = getFileExt($file['file_name']);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        $downloadName = $file['title'] . '.' . $ext;
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
        header('Content-Length: ' . filesize($file['file_path']));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($file['file_path']);
        exit;
    } else {
        header('Location: ?page=downloads&msg=文件不存在，请联系管理员&error=1');
        exit;
    }
}

// 处理上传和编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // 上传
    if ($action === 'upload' && $loggedIn) {
        $title = SQLite3::escapeString(trim($_POST['title'] ?? ''));
        $description = SQLite3::escapeString(trim($_POST['description'] ?? ''));
        $uploader = SQLite3::escapeString($currentUser ?: '管理员');
        
        if (empty($title)) {
            header('Location: ?page=downloads&msg=请输入资料标题&error=1');
            exit;
        }
        
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $uploadDir = 'uploads/downloads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'avi', 'mkv', 'mp3', 'wav'];
            if (in_array($ext, $allowed)) {
                $originalName = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
                $safeName = preg_replace('/[^a-zA-Z0-9_\x{4e00}-\x{9fa5}]/u', '_', $originalName);
                $filename = $safeName . '_' . time() . '.' . $ext;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
                    $file_size = formatFileSize($_FILES['file']['size']);
                    $db->exec("INSERT INTO downloads (title, description, file_name, file_path, file_size, uploader) 
                               VALUES ('$title', '$description', '$filename', '$filepath', '$file_size', '$uploader')");
                    header('Location: ?page=downloads&msg=资料上传成功');
                    exit;
                } else {
                    header('Location: ?page=downloads&msg=文件上传失败&error=1');
                    exit;
                }
            } else {
                header('Location: ?page=downloads&msg=不支持的文件格式&error=1');
                exit;
            }
        } else {
            header('Location: ?page=downloads&msg=请选择要上传的文件&error=1');
            exit;
        }
    }
    
    // 编辑资料
    if ($action === 'edit' && $loggedIn) {
        $id = intval($_POST['id'] ?? 0);
        $title = SQLite3::escapeString(trim($_POST['title'] ?? ''));
        $description = SQLite3::escapeString(trim($_POST['description'] ?? ''));
        
        $oldFile = $db->querySingle("SELECT * FROM downloads WHERE id = $id", true);
        if (!$oldFile) {
            header('Location: ?page=downloads&msg=资料不存在&error=1');
            exit;
        }
        
        if (!$isAdmin && $oldFile['uploader'] !== $currentUser) {
            header('Location: ?page=downloads&msg=您没有权限编辑此资料&error=1');
            exit;
        }
        
        if (empty($title)) {
            header('Location: ?page=downloads&msg=请输入资料标题&error=1');
            exit;
        }
        
        $file_name = $oldFile['file_name'];
        $file_path = $oldFile['file_path'];
        $file_size = $oldFile['file_size'];
        
        if (isset($_FILES['new_file']) && $_FILES['new_file']['error'] == 0) {
            $uploadDir = 'uploads/downloads/';
            $ext = strtolower(pathinfo($_FILES['new_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'avi', 'mkv', 'mp3', 'wav'];
            if (in_array($ext, $allowed)) {
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                $originalName = pathinfo($_FILES['new_file']['name'], PATHINFO_FILENAME);
                $safeName = preg_replace('/[^a-zA-Z0-9_\x{4e00}-\x{9fa5}]/u', '_', $originalName);
                $filename = $safeName . '_' . time() . '.' . $ext;
                $filepath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['new_file']['tmp_name'], $filepath)) {
                    $file_name = $filename;
                    $file_path = $filepath;
                    $file_size = formatFileSize($_FILES['new_file']['size']);
                }
            }
        }
        
        $db->exec("UPDATE downloads SET 
            title = '$title', 
            description = '$description',
            file_name = '$file_name',
            file_path = '$file_path',
            file_size = '$file_size'
            WHERE id = $id");
        
        header('Location: ?page=downloads&msg=资料已更新');
        exit;
    }
    
    // 删除
    if ($action === 'delete' && $loggedIn) {
        $id = intval($_POST['id'] ?? 0);
        $file = $db->querySingle("SELECT * FROM downloads WHERE id = $id", true);
        if (!$file) {
            header('Location: ?page=downloads&msg=资料不存在&error=1');
            exit;
        }
        
        if (!$isAdmin && $file['uploader'] !== $currentUser) {
            header('Location: ?page=downloads&msg=您没有权限删除此资料&error=1');
            exit;
        }
        
        if ($file && !empty($file['file_path']) && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        $db->exec("DELETE FROM downloads WHERE id = $id");
        header('Location: ?page=downloads&msg=资料已删除');
        exit;
    }
}

// 获取所有资料
$result = $db->query("SELECT * FROM downloads ORDER BY created_at DESC");
$downloads = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $downloads[] = $row;
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function canEdit($uploader, $isAdmin, $currentUser) {
    return $isAdmin || ($uploader === $currentUser);
}
?>
<div class="page-header">
    <h2>📥 <?= getClanFamilyName() ?> · 资料下载</h2>>
    <?php if ($loggedIn): ?>
        <div style="margin-top:8px;">
            <button class="btn" onclick="document.getElementById('uploadForm').style.display='block'">📤 上传资料</button>
        </div>
    <?php endif; ?>
</div>

<!-- 消息提示 -->
<?php if (isset($_GET['msg'])): ?>
    <div class="msg <?= isset($_GET['error']) ? 'msg-error' : '' ?>">✦ <?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<!-- 上传表单 -->
<?php if ($loggedIn): ?>
<div id="uploadForm" style="display:none;margin-top:12px;background:#fcf7f2;padding:16px;border-radius:16px;border:1px solid #e6d7c6;margin-bottom:20px;">
    <form method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="form-row">
            <div class="form-group"><label>资料标题</label>
                <input type="text" name="title" id="uploadTitle" placeholder="请输入资料标题，选择文件后自动填入">
            </div>
            <div class="form-group"><label>选择文件 *</label>
                <input type="file" name="file" id="uploadFile" required onchange="autoFillTitle()">
            </div>
        </div>
        <div class="form-group"><label>资料描述</label><textarea name="description" rows="2" placeholder="简要描述资料内容..."></textarea></div>
        <button type="submit" class="btn">📤 上传</button>
        <button type="button" class="btn-sm" onclick="document.getElementById('uploadForm').style.display='none'">取消</button>
    </form>
</div>
<?php endif; ?>

<!-- 资料列表 -->
<?php if (empty($downloads)): ?>
    <div class="empty-state">暂无资料，<?= $loggedIn ? '请点击上方「上传资料」添加。' : '请等待管理员上传。' ?></div>
<?php else: ?>
    <div class="downloads-grid">
        <?php foreach ($downloads as $d): 
            $canEdit = canEdit($d['uploader'], $isAdmin, $currentUser);
            $fileInfo = getFileIcon($d['file_name']);
            $fileExt = getFileExt($d['file_name']);
        ?>
            <div class="download-card" id="download-<?= $d['id'] ?>">
                <div class="download-header">
                    <div class="download-icon"><?= $fileInfo['icon'] ?></div>
                    <div class="download-format">
                        <span class="format-badge <?= $fileExt ?>"><?= $fileInfo['label'] ?></span>
                        <span class="format-ext">.<?= $fileExt ?></span>
                    </div>
                </div>
                <div class="download-info">
                    <div class="download-title"><?= htmlspecialchars($d['title']) ?></div>
                    <div class="download-desc"><?= htmlspecialchars($d['description'] ?: '暂无描述') ?></div>
                    <div class="download-meta">
                        <span>📅 <?= date('Y-m-d', strtotime($d['created_at'])) ?></span>
                        <span>📁 <?= htmlspecialchars($d['file_size'] ?: '未知大小') ?></span>
                        <span>👤 <?= htmlspecialchars($d['uploader'] ?: '管理员') ?></span>
                        <span>📥 下载 <?= $d['download_count'] ?> 次</span>
                        <?php if ($canEdit): ?>
                            <span style="color:#b2977d;">✎ 可编辑</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="download-actions">
                    <a href="?page=downloads&action=download&id=<?= $d['id'] ?>" class="btn">📥 下载</a>
                    <?php if ($canEdit): ?>
                        <button class="btn-sm btn-edit" onclick="editDownload(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['title'])) ?>', '<?= htmlspecialchars(addslashes($d['description'])) ?>')">✎ 编辑</button>
                        <form method="post" action="" style="display:inline;" onsubmit="return confirm('确认删除此资料？')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn-sm btn-danger">🗑️ 删除</button>
                        </form>
                    <?php endif; ?>
                </div>
                <!-- 编辑表单（隐藏） -->
                <?php if ($canEdit): ?>
                <div class="download-edit-form" id="editForm-<?= $d['id'] ?>" style="display:none;margin-top:10px;padding:12px;background:#f5efe9;border-radius:12px;">
                    <form method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <div class="form-row">
                            <div class="form-group"><label>资料标题</label>
                                <input type="text" name="title" id="editTitle-<?= $d['id'] ?>" value="<?= htmlspecialchars($d['title']) ?>">
                            </div>
                            <div class="form-group"><label>更换文件（可选）</label>
                                <input type="file" name="new_file" id="editFile-<?= $d['id'] ?>" onchange="autoFillEditTitle(<?= $d['id'] ?>)">
                            </div>
                        </div>
                        <div class="form-group"><label>资料描述</label><textarea name="description" rows="2"><?= htmlspecialchars($d['description']) ?></textarea></div>
                        <button type="submit" class="btn" style="font-size:0.85rem;padding:6px 16px;">💾 保存修改</button>
                        <button type="button" class="btn-sm" onclick="document.getElementById('editForm-<?= $d['id'] ?>').style.display='none'">取消</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
/* ===== 资料下载样式 ===== */
.downloads-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.download-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 16px 18px;
    border: 1px solid #ede5db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: 0.2s;
    display: flex;
    flex-direction: column;
}

.download-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transform: translateY(-2px);
    border-color: #b2977d;
}

.download-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.download-icon {
    font-size: 2.4rem;
    line-height: 1;
    flex-shrink: 0;
}

.download-format {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.format-badge {
    display: inline-block;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #fff;
    background: #b2977d;
}

.format-badge.pdf { background: #e74c3c; }
.format-badge.doc { background: #2c6eb0; }
.format-badge.docx { background: #2c6eb0; }
.format-badge.xls { background: #1d7a3c; }
.format-badge.xlsx { background: #1d7a3c; }
.format-badge.ppt { background: #d24726; }
.format-badge.pptx { background: #d24726; }
.format-badge.zip { background: #f39c12; }
.format-badge.rar { background: #f39c12; }
.format-badge.7z { background: #f39c12; }
.format-badge.txt { background: #7f8c8d; }
.format-badge.jpg { background: #8e44ad; }
.format-badge.jpeg { background: #8e44ad; }
.format-badge.png { background: #8e44ad; }
.format-badge.gif { background: #8e44ad; }
.format-badge.webp { background: #8e44ad; }
.format-badge.mp4 { background: #e67e22; }
.format-badge.avi { background: #e67e22; }
.format-badge.mkv { background: #e67e22; }
.format-badge.mp3 { background: #2ecc71; }
.format-badge.wav { background: #2ecc71; }
.format-badge.exe { background: #c0392b; }
.format-badge.apk { background: #27ae60; }

.format-ext {
    font-size: 0.7rem;
    color: #95a5a6;
    background: #f0e8df;
    padding: 1px 8px;
    border-radius: 10px;
}

.download-info {
    flex: 1;
}

.download-title {
    font-weight: 600;
    font-size: 1.05rem;
    color: #3f2e1d;
    margin-bottom: 4px;
}

.download-desc {
    font-size: 0.85rem;
    color: #6f5b47;
    line-height: 1.4;
    margin-bottom: 6px;
}

.download-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 16px;
    font-size: 0.75rem;
    color: #a48d78;
    margin-top: 4px;
}

.download-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0e8df;
    flex-wrap: wrap;
}

.download-actions .btn {
    padding: 6px 20px;
    font-size: 0.85rem;
}

.download-actions .btn-sm {
    font-size: 0.75rem;
    padding: 6px 14px;
}

.download-edit-form .form-group {
    margin-bottom: 8px;
}
.download-edit-form .form-group label {
    font-size: 0.8rem;
    color: #5f4a34;
}
.download-edit-form .form-group input,
.download-edit-form .form-group textarea {
    font-size: 0.9rem;
    padding: 6px 12px;
    border-radius: 12px;
}

.msg-error {
    background: #f0d6c8;
    color: #7a3a2a;
}

@media (max-width: 700px) {
    .downloads-grid {
        grid-template-columns: 1fr;
    }
    .download-card {
        padding: 14px;
    }
    .download-header {
        flex-wrap: wrap;
    }
    .download-icon {
        font-size: 2rem;
    }
}
</style>

<script>
function autoFillTitle() {
    var fileInput = document.getElementById('uploadFile');
    var titleInput = document.getElementById('uploadTitle');
    if (fileInput && titleInput && fileInput.files && fileInput.files.length > 0) {
        var fileName = fileInput.files[0].name;
        // 去掉文件扩展名
        var title = fileName.replace(/\.[^.]+$/, '');
        // 如果标题输入框为空，则自动填入
        if (titleInput.value.trim() === '') {
            titleInput.value = title;
        }
    }
}

function autoFillEditTitle(id) {
    var fileInput = document.getElementById('editFile-' + id);
    var titleInput = document.getElementById('editTitle-' + id);
    if (fileInput && titleInput && fileInput.files && fileInput.files.length > 0) {
        var fileName = fileInput.files[0].name;
        var title = fileName.replace(/\.[^.]+$/, '');
        if (titleInput.value.trim() === '') {
            titleInput.value = title;
        }
    }
}

function editDownload(id, title, description) {
    var form = document.getElementById('editForm-' + id);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

document.querySelectorAll('.download-actions .btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm('确认下载此资料？')) {
            e.preventDefault();
        }
    });
});
</script>