<?php
// 文件: pages/photos.php - 家族相册（完整功能版）
$albums = getPhotoAlbums();
$allPhotos = getAllPhotosWithAlbum();
$loggedIn = isLoggedIn();
$currentAlbum = isset($_GET['album']) ? intval($_GET['album']) : 0;

// 获取当前显示的照片
if ($currentAlbum == 0) {
    $displayPhotos = $allPhotos;
} else {
    $displayPhotos = [];
    foreach ($allPhotos as $p) {
        if ($p['album_id'] == $currentAlbum) {
            $displayPhotos[] = $p;
        }
    }
}
?>
<div class="page-header">
    <h2>🖼️ 家族相册</h2>
    <?php if ($loggedIn): ?>
        <div style="margin-top:8px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn" onclick="document.getElementById('addAlbumForm').style.display='block'">📁 新建相册</button>
            <button class="btn" onclick="document.getElementById('addPhotoForm').style.display='block'">📷 上传照片</button>
        </div>
    <?php endif; ?>
</div>

<!-- 新建相册表单 -->
<?php if ($loggedIn): ?>
<div id="addAlbumForm" style="display:none;margin-top:12px;background:#fcf7f2;padding:16px;border-radius:16px;border:1px solid #e6d7c6;margin-bottom:20px;">
    <form method="post" action="member_actions.php">
        <input type="hidden" name="action" value="add_album">
        <input type="hidden" name="return_page" value="photos">
        <div class="form-row">
            <div class="form-group"><label>相册名称 *</label><input type="text" name="name" required placeholder="如：家族活动"></div>
            <div class="form-group"><label>描述</label><input type="text" name="description" placeholder="相册描述"></div>
        </div>
        <button type="submit" class="btn">创建相册</button>
        <button type="button" class="btn-sm" onclick="document.getElementById('addAlbumForm').style.display='none'">取消</button>
    </form>
</div>
<?php endif; ?>

<!-- 上传照片表单 -->
<?php if ($loggedIn): ?>
<div id="addPhotoForm" style="display:none;margin-top:12px;background:#fcf7f2;padding:16px;border-radius:16px;border:1px solid #e6d7c6;margin-bottom:20px;">
    <form method="post" action="member_actions.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_photo">
        <input type="hidden" name="return_page" value="photos">
        <!-- 当前相册ID，用于跳转回原相册 -->
        <input type="hidden" name="album_id" value="<?= $currentAlbum ?>">
        <div class="form-row">
            <div class="form-group"><label>选择相册</label>
                <select name="album_id_display" onchange="document.querySelector('input[name=album_id]').value=this.value">
                    <?php foreach ($albums as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= ($currentAlbum == $a['id'] || ($currentAlbum == 0 && $a['id'] == 1)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>照片</label><input type="file" name="photo" accept="image/*" required></div>
        </div>
        <div class="form-group"><label>标题</label><input type="text" name="title" placeholder="照片标题"></div>
        <div class="form-group"><label>描述</label><textarea name="description" rows="2" placeholder="照片描述..."></textarea></div>
        <button type="submit" class="btn">上传</button>
        <button type="button" class="btn-sm" onclick="document.getElementById('addPhotoForm').style.display='none'">取消</button>
    </form>
</div>
<?php endif; ?>

<!-- 相册分类导航 -->
<div class="album-nav">
    <a href="?page=photos&album=0" class="album-nav-item <?= $currentAlbum == 0 ? 'active' : '' ?>">📷 全部照片 (<?= count($allPhotos) ?>)</a>
    <?php foreach ($albums as $a): 
        $albumPhotoCount = 0;
        foreach ($allPhotos as $p) {
            if ($p['album_id'] == $a['id']) $albumPhotoCount++;
        }
    ?>
        <a href="?page=photos&album=<?= $a['id'] ?>" class="album-nav-item <?= $currentAlbum == $a['id'] ? 'active' : '' ?>">
            📁 <?= htmlspecialchars($a['name']) ?> (<?= $albumPhotoCount ?>)
            <?php if ($loggedIn): ?>
                <span class="album-actions">
                    <button class="btn-sm" onclick="event.preventDefault();editAlbum(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['name'])) ?>', '<?= htmlspecialchars(addslashes($a['description'])) ?>')">✎</button>
                    <form method="post" action="member_actions.php" style="display:inline;" onsubmit="return confirm('确认删除此相册及其所有照片？')">
                        <input type="hidden" name="action" value="delete_album">
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <input type="hidden" name="return_page" value="photos">
                        <button type="submit" class="btn-sm btn-danger" style="font-size:0.6rem;padding:1px 6px;">×</button>
                    </form>
                </span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- 编辑相册表单（隐藏） -->
<?php if ($loggedIn): ?>
<div id="editAlbumForm" style="display:none;margin-top:12px;background:#f5efe9;padding:16px;border-radius:16px;border:1px solid #e6d7c6;margin-bottom:16px;">
    <form method="post" action="member_actions.php">
        <input type="hidden" name="action" value="edit_album">
        <input type="hidden" name="id" id="editAlbumId" value="">
        <input type="hidden" name="return_page" value="photos">
        <div class="form-row">
            <div class="form-group"><label>相册名称</label><input type="text" name="name" id="editAlbumName" required></div>
            <div class="form-group"><label>描述</label><input type="text" name="description" id="editAlbumDesc"></div>
        </div>
        <button type="submit" class="btn" style="font-size:0.85rem;padding:6px 16px;">💾 保存修改</button>
        <button type="button" class="btn-sm" onclick="document.getElementById('editAlbumForm').style.display='none'">取消</button>
    </form>
</div>
<?php endif; ?>

<!-- 相册网格 -->
<?php if (empty($displayPhotos)): ?>
    <div class="empty-state">
        <?php if ($currentAlbum == 0): ?>
            暂无任何照片，请上传第一张照片。
        <?php else: ?>
            此相册暂无照片，请上传第一张照片。
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="photo-grid">
        <?php foreach ($displayPhotos as $p): ?>
            <div class="photo-card" id="photo-<?= $p['id'] ?>">
                <img src="<?= htmlspecialchars($p['image_path']) ?>" 
                     alt="<?= htmlspecialchars($p['title']) ?>" 
                     loading="lazy"
                     onclick="openPhotoViewer('<?= htmlspecialchars($p['image_path']) ?>', '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars(addslashes($p['description'])) ?>')">
                <div class="photo-info">
                    <div class="photo-title" id="photo-title-<?= $p['id'] ?>"><?= htmlspecialchars($p['title'] ?: '无标题') ?></div>
                    <div class="photo-desc" id="photo-desc-<?= $p['id'] ?>"><?= htmlspecialchars($p['description'] ?: '') ?></div>
                    <div class="photo-meta">
                        <span class="photo-time">📅 <?= date('Y-m-d', strtotime($p['upload_date'])) ?></span>
                        <?php if (!empty($p['uploader'])): ?>
                            <span class="photo-uploader">👤 <?= htmlspecialchars($p['uploader']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($p['album_name'])): ?>
                            <span class="photo-album">📁 <?= htmlspecialchars($p['album_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($loggedIn): ?>
                    <div class="photo-actions">
                        <button class="btn-sm btn-edit" onclick="editPhoto(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars(addslashes($p['description'])) ?>')">✎ 编辑</button>
                        <form method="post" action="member_actions.php" style="display:inline;" onsubmit="return confirm('确认删除此照片？')">
                            <input type="hidden" name="action" value="delete_photo">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="album_id" value="<?= $currentAlbum ?>">
                            <input type="hidden" name="return_page" value="photos">
                            <button type="submit" class="btn-sm btn-danger">删除</button>
                        </form>
                    </div>
                    <!-- 编辑表单（隐藏） -->
                    <div class="photo-edit-form" id="editPhotoForm-<?= $p['id'] ?>" style="display:none;margin-top:10px;padding:12px;background:#f5efe9;border-radius:12px;">
                        <form method="post" action="member_actions.php">
                            <input type="hidden" name="action" value="edit_photo">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="return_page" value="photos">
                            <div class="form-group">
                                <label>标题</label>
                                <input type="text" name="title" value="<?= htmlspecialchars($p['title']) ?>">
                            </div>
                            <div class="form-group">
                                <label>描述</label>
                                <textarea name="description" rows="2"><?= htmlspecialchars($p['description']) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>移动至相册</label>
                                <select name="album_id">
                                    <?php foreach ($albums as $a): ?>
                                        <option value="<?= $a['id'] ?>" <?= ($p['album_id'] == $a['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($a['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn" style="font-size:0.85rem;padding:6px 16px;">💾 保存修改</button>
                            <button type="button" class="btn-sm" onclick="document.getElementById('editPhotoForm-<?= $p['id'] ?>').style.display='none'">取消</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- 照片查看器（全屏放大 + 拖拽查看） -->
<!-- ============================================================ -->
<div id="photoViewer" class="photo-viewer" style="display:none;">
    <div class="photo-viewer-close" onclick="closePhotoViewer()">&times;</div>
    <div class="photo-viewer-toolbar">
        <button class="btn-sm" onclick="event.stopPropagation();zoomPhoto('in')">➕ 放大</button>
        <button class="btn-sm" onclick="event.stopPropagation();zoomPhoto('out')">➖ 缩小</button>
        <button class="btn-sm" onclick="event.stopPropagation();rotatePhoto()">🔄 旋转</button>
        <button class="btn-sm" onclick="event.stopPropagation();resetPhotoView()">⟲ 重置</button>
        <span class="photo-zoom-level" id="photoZoomLevel">100%</span>
        <span style="color:rgba(255,255,255,0.4);font-size:0.75rem;margin-left:4px;">🖱 拖拽移动</span>
    </div>
    <div class="photo-viewer-content" onclick="closePhotoViewer()">
        <div class="photo-viewer-wrapper" id="photoViewerWrapper" onclick="event.stopPropagation();">
            <img id="photoViewerImg" src="" alt="查看照片" 
                 ondblclick="event.stopPropagation();togglePhotoZoom()">
        </div>
        <div class="photo-viewer-info" id="photoViewerInfo" onclick="event.stopPropagation();"></div>
    </div>
</div>

<style>
/* ===== 相册导航样式 ===== */
.album-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 16px 0;
    padding: 10px 14px;
    background: #fcf7f2;
    border-radius: 12px;
    border: 1px solid #e6d7c6;
}

.album-nav-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 14px;
    border-radius: 20px;
    text-decoration: none;
    color: #5f4a34;
    font-size: 0.9rem;
    background: #ffffff;
    border: 1px solid #e6d7c6;
    transition: 0.2s;
}

.album-nav-item:hover {
    background: #f0e8df;
    border-color: #b2977d;
}

.album-nav-item.active {
    background: #b2977d;
    color: white;
    border-color: #b2977d;
}

.album-actions {
    display: inline-flex;
    gap: 2px;
    margin-left: 4px;
}
.album-actions .btn-sm {
    font-size: 0.65rem;
    padding: 0 4px;
    line-height: 1.4;
    background: transparent;
    color: inherit;
}
.album-actions .btn-sm:hover {
    background: rgba(0,0,0,0.1);
}
.album-actions .btn-danger {
    color: #b17a6b;
}
.album-actions .btn-danger:hover {
    background: rgba(177,122,107,0.2);
}

/* ===== 相册样式 ===== */
.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 16px;
}

.photo-card {
    background: #ffffff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #ede5db;
    transition: 0.2s;
    display: flex;
    flex-direction: column;
}

.photo-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.10);
    transform: translateY(-2px);
}

.photo-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-bottom: 1px solid #ede5db;
    background: #f5efe9;
    cursor: pointer;
    transition: opacity 0.2s;
}

.photo-card img:hover {
    opacity: 0.9;
}

.photo-info {
    padding: 12px 14px;
    flex: 1;
}

.photo-title {
    font-weight: 600;
    font-size: 1rem;
    color: #3f2e1d;
    margin-bottom: 4px;
}

.photo-desc {
    font-size: 0.85rem;
    color: #6f5b47;
    line-height: 1.4;
    min-height: 20px;
}

.photo-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 6px;
    font-size: 0.7rem;
    color: #a48d78;
}

.photo-meta .photo-uploader {
    color: #7b5d42;
}

.photo-actions {
    display: flex;
    gap: 6px;
    padding: 8px 14px 12px;
    border-top: 1px solid #f0e8df;
    flex-wrap: wrap;
}

.photo-actions .btn-sm {
    font-size: 0.75rem;
    padding: 4px 12px;
}

/* ===== 照片查看器（全屏） ===== */
.photo-viewer {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.92);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
    cursor: default;
    overflow: hidden;
}

.photo-viewer-close {
    position: fixed;
    top: 20px;
    right: 30px;
    font-size: 2.8rem;
    color: #fff;
    cursor: pointer;
    z-index: 10001;
    font-weight: 300;
    transition: 0.2s;
    line-height: 1;
    text-shadow: 0 2px 10px rgba(0,0,0,0.5);
}

.photo-viewer-close:hover {
    color: #b2977d;
    transform: scale(1.1);
}

.photo-viewer-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    cursor: default;
    padding: 60px 40px 100px 40px;
}

.photo-viewer-wrapper {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
    overflow: hidden;
    cursor: default;
}

.photo-viewer-wrapper img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.15s ease;
    transform-origin: center center;
    cursor: default;
    user-select: none;
    -webkit-user-drag: none;
    border-radius: 4px;
    box-shadow: 0 4px 40px rgba(0,0,0,0.3);
}

.photo-viewer-toolbar {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(30, 30, 30, 0.85);
    backdrop-filter: blur(12px);
    padding: 12px 24px;
    border-radius: 50px;
    display: flex;
    gap: 12px;
    align-items: center;
    z-index: 10000;
    flex-wrap: wrap;
    justify-content: center;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 4px 30px rgba(0,0,0,0.5);
}

.photo-viewer-toolbar .btn-sm {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.15);
    padding: 8px 18px;
    border-radius: 30px;
    cursor: pointer;
    transition: 0.25s;
    font-size: 0.9rem;
    min-height: 36px;
}

.photo-viewer-toolbar .btn-sm:hover {
    background: rgba(255,255,255,0.25);
    transform: scale(1.05);
}

.photo-viewer-toolbar .btn-sm:active {
    transform: scale(0.95);
}

.photo-zoom-level {
    color: #fff;
    font-size: 0.9rem;
    min-width: 55px;
    text-align: center;
    font-weight: 500;
}

.photo-viewer-info {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255,255,255,0.9);
    text-align: center;
    font-size: 1rem;
    max-width: 80%;
    z-index: 9999;
    pointer-events: none;
    text-shadow: 0 2px 12px rgba(0,0,0,0.6);
}

.photo-viewer-info .pv-title {
    font-weight: 600;
    font-size: 1.2rem;
    margin-bottom: 4px;
}

.photo-viewer-info .pv-desc {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.6);
}

/* ===== 移动端适配 ===== */
@media (max-width: 700px) {
    .photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
    }
    .photo-card img {
        height: 130px;
    }
    .photo-info {
        padding: 8px 10px;
    }
    .photo-title {
        font-size: 0.85rem;
    }
    .photo-desc {
        font-size: 0.75rem;
    }
    .photo-meta {
        font-size: 0.6rem;
        gap: 4px;
    }
    
    .photo-viewer-content {
        padding: 50px 16px 130px 16px;
    }
    .photo-viewer-close {
        top: 12px;
        right: 18px;
        font-size: 2.2rem;
    }
    .photo-viewer-toolbar {
        bottom: 16px;
        padding: 10px 14px;
        gap: 6px;
        width: 94%;
        border-radius: 30px;
    }
    .photo-viewer-toolbar .btn-sm {
        font-size: 0.75rem;
        padding: 6px 12px;
        min-height: 32px;
    }
    .photo-zoom-level {
        font-size: 0.75rem;
        min-width: 40px;
    }
    .photo-viewer-info {
        bottom: 80px;
        font-size: 0.85rem;
        max-width: 92%;
    }
    .photo-viewer-info .pv-title {
        font-size: 1rem;
    }
    .photo-viewer-info .pv-desc {
        font-size: 0.8rem;
    }
    
    .album-nav {
        padding: 8px 10px;
        gap: 4px;
    }
    .album-nav-item {
        font-size: 0.8rem;
        padding: 4px 10px;
    }
}

@media (max-width: 400px) {
    .photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }
    .photo-card img {
        height: 100px;
    }
    .photo-viewer-toolbar .btn-sm {
        font-size: 0.7rem;
        padding: 4px 10px;
        min-height: 28px;
    }
}

/* 编辑表单内联样式 */
.photo-edit-form .form-group {
    margin-bottom: 8px;
}
.photo-edit-form .form-group label {
    font-size: 0.8rem;
    color: #5f4a34;
}
.photo-edit-form .form-group input,
.photo-edit-form .form-group textarea,
.photo-edit-form .form-group select {
    font-size: 0.9rem;
    padding: 6px 12px;
    border-radius: 12px;
}
</style>

<script>
// ============================================================
// 相册编辑
// ============================================================
function editAlbum(id, name, description) {
    var form = document.getElementById('editAlbumForm');
    if (form) {
        document.getElementById('editAlbumId').value = id;
        document.getElementById('editAlbumName').value = name;
        document.getElementById('editAlbumDesc').value = description || '';
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// 照片编辑
// ============================================================
function editPhoto(id, title, description) {
    var form = document.getElementById('editPhotoForm-' + id);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================================
// 照片查看器（全屏 + 拖拽）
// ============================================================
var currentZoom = 1;
var currentRotation = 0;
var translateX = 0;
var translateY = 0;

var isDragging = false;
var dragStartX = 0;
var dragStartY = 0;
var dragStartTranslateX = 0;
var dragStartTranslateY = 0;

var touchStartX = 0;
var touchStartY = 0;
var touchStartTranslateX = 0;
var touchStartTranslateY = 0;
var touchStartDist = 0;
var touchZoom = 1;

function openPhotoViewer(src, title, description) {
    var viewer = document.getElementById('photoViewer');
    var img = document.getElementById('photoViewerImg');
    var info = document.getElementById('photoViewerInfo');
    
    img.src = src;
    currentZoom = 1;
    currentRotation = 0;
    translateX = 0;
    translateY = 0;
    document.getElementById('photoZoomLevel').textContent = '100%';
    img.style.transform = 'scale(1) rotate(0deg) translate(0px, 0px)';
    
    info.innerHTML = '';
    if (title && title !== '无标题' && title !== 'null') {
        info.innerHTML += '<div class="pv-title">' + title + '</div>';
    }
    if (description && description !== 'null') {
        info.innerHTML += '<div class="pv-desc">' + description + '</div>';
    }
    
    viewer.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closePhotoViewer() {
    document.getElementById('photoViewer').style.display = 'none';
    document.body.style.overflow = '';
}

function zoomPhoto(direction) {
    var img = document.getElementById('photoViewerImg');
    var step = 0.1;
    if (direction === 'in') {
        currentZoom = Math.min(currentZoom + step, 4);
    } else {
        currentZoom = Math.max(currentZoom - step, 0.2);
    }
    updatePhotoTransform(img);
    document.getElementById('photoZoomLevel').textContent = Math.round(currentZoom * 100) + '%';
}

function togglePhotoZoom() {
    if (currentZoom > 1.5) {
        currentZoom = 1;
        translateX = 0;
        translateY = 0;
    } else {
        currentZoom = 2.5;
    }
    var img = document.getElementById('photoViewerImg');
    updatePhotoTransform(img);
    document.getElementById('photoZoomLevel').textContent = Math.round(currentZoom * 100) + '%';
}

function rotatePhoto() {
    var img = document.getElementById('photoViewerImg');
    currentRotation = (currentRotation + 90) % 360;
    updatePhotoTransform(img);
}

function resetPhotoView() {
    var img = document.getElementById('photoViewerImg');
    currentZoom = 1;
    currentRotation = 0;
    translateX = 0;
    translateY = 0;
    updatePhotoTransform(img);
    document.getElementById('photoZoomLevel').textContent = '100%';
}

function updatePhotoTransform(img) {
    img.style.transform = 'scale(' + currentZoom + ') rotate(' + currentRotation + 'deg) translate(' + translateX + 'px, ' + translateY + 'px)';
}

// 鼠标拖拽
document.addEventListener('mousedown', function(e) {
    var viewer = document.getElementById('photoViewer');
    if (viewer.style.display !== 'flex') return;
    if (currentZoom <= 1) return;
    
    var img = document.getElementById('photoViewerImg');
    if (!img.contains(e.target)) return;
    
    isDragging = true;
    dragStartX = e.clientX;
    dragStartY = e.clientY;
    dragStartTranslateX = translateX;
    dragStartTranslateY = translateY;
    img.style.cursor = 'grabbing';
    e.preventDefault();
});

document.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    var dx = e.clientX - dragStartX;
    var dy = e.clientY - dragStartY;
    translateX = dragStartTranslateX + dx;
    translateY = dragStartTranslateY + dy;
    var img = document.getElementById('photoViewerImg');
    updatePhotoTransform(img);
    e.preventDefault();
});

document.addEventListener('mouseup', function() {
    if (isDragging) {
        isDragging = false;
        var img = document.getElementById('photoViewerImg');
        img.style.cursor = 'default';
    }
});

// 触摸拖拽
document.addEventListener('touchstart', function(e) {
    var viewer = document.getElementById('photoViewer');
    if (viewer.style.display !== 'flex') return;
    var img = document.getElementById('photoViewerImg');
    if (!img.contains(e.target)) return;
    
    if (e.touches.length === 1) {
        if (currentZoom <= 1) return;
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        touchStartTranslateX = translateX;
        touchStartTranslateY = translateY;
        isDragging = true;
        e.preventDefault();
    } else if (e.touches.length === 2) {
        var dx = e.touches[0].clientX - e.touches[1].clientX;
        var dy = e.touches[0].clientY - e.touches[1].clientY;
        touchStartDist = Math.sqrt(dx * dx + dy * dy);
        touchZoom = currentZoom;
        e.preventDefault();
    }
}, { passive: false });

document.addEventListener('touchmove', function(e) {
    var viewer = document.getElementById('photoViewer');
    if (viewer.style.display !== 'flex') return;
    var img = document.getElementById('photoViewerImg');
    
    if (e.touches.length === 1 && isDragging) {
        var dx = e.touches[0].clientX - touchStartX;
        var dy = e.touches[0].clientY - touchStartY;
        translateX = touchStartTranslateX + dx;
        translateY = touchStartTranslateY + dy;
        updatePhotoTransform(img);
        e.preventDefault();
    } else if (e.touches.length === 2) {
        var dx = e.touches[0].clientX - e.touches[1].clientX;
        var dy = e.touches[0].clientY - e.touches[1].clientY;
        var dist = Math.sqrt(dx * dx + dy * dy);
        var scale = dist / touchStartDist;
        currentZoom = Math.min(Math.max(touchZoom * scale, 0.2), 4);
        document.getElementById('photoZoomLevel').textContent = Math.round(currentZoom * 100) + '%';
        updatePhotoTransform(img);
        e.preventDefault();
    }
}, { passive: false });

document.addEventListener('touchend', function() {
    isDragging = false;
});

// 滚轮缩放
document.addEventListener('DOMContentLoaded', function() {
    var wrapper = document.getElementById('photoViewerWrapper');
    if (wrapper) {
        wrapper.addEventListener('wheel', function(e) {
            var viewer = document.getElementById('photoViewer');
            if (viewer.style.display === 'flex') {
                e.preventDefault();
                if (e.deltaY < 0) {
                    zoomPhoto('in');
                } else {
                    zoomPhoto('out');
                }
            }
        }, { passive: false });
    }
});

// 键盘快捷键
document.addEventListener('keydown', function(e) {
    var viewer = document.getElementById('photoViewer');
    if (viewer.style.display === 'flex') {
        if (e.key === 'Escape') {
            closePhotoViewer();
        } else if (e.key === '+' || e.key === '=') {
            e.preventDefault();
            zoomPhoto('in');
        } else if (e.key === '-') {
            e.preventDefault();
            zoomPhoto('out');
        } else if (e.key === 'r' || e.key === 'R') {
            rotatePhoto();
        } else if (e.key === '0') {
            resetPhotoView();
        }
    }
});
</script>