<?php
// 文件: pages/home.php - 首页（最新成员按添加时间排序）
$members = getAllMembers();
$clanSummary = getClanSummary();
$clanEvents = getClanEvents();
$clanPhotos = getClanPhotos();
$loggedIn = isLoggedIn();

// ★★★ 获取最新10位成员（按 id 倒序，即最近添加的） ★★★
$latestMembers = array_slice(array_reverse($members), 0, 10);

// 获取最新10张照片
$latestPhotos = $clanPhotos;
usort($latestPhotos, function($a, $b) {
    return strtotime($b['upload_date']) - strtotime($a['upload_date']);
});
$latestPhotos = array_slice($latestPhotos, 0, 10);

// 获取最新一条留言
$db = getDB();
$latestMessage = $db->querySingle("SELECT * FROM guestbook WHERE status = 1 ORDER BY created_at DESC LIMIT 1", true);
?>
<div class="home-grid">
    <!-- 1. 家族综述 - 只显示2行，点击跳转 -->
    <a href="?page=summary" class="home-card home-summary" style="text-decoration:none;color:inherit;display:block;">
        <div class="home-card-header">
            <h3>📖 家族综述</h3>
            <span class="home-card-more">查看全文 →</span>
        </div>
        <div class="home-card-body">
            <p class="home-text-clamp"><?= htmlspecialchars($clanSummary) ?></p>
        </div>
    </a>

    <!-- 2. 最新家族记事 - 只显示2行，点击跳转 -->
    <?php $latestEvent = !empty($clanEvents) ? $clanEvents[0] : null; ?>
    <a href="?page=events" class="home-card home-event" style="text-decoration:none;color:inherit;display:block;">
        <div class="home-card-header">
            <h3>📅 最新记事</h3>
            <span class="home-card-more">查看全部 →</span>
        </div>
        <div class="home-card-body">
            <?php if ($latestEvent): ?>
                <div class="home-event-title"><?= htmlspecialchars($latestEvent['title']) ?></div>
                <p class="home-text-clamp"><?= htmlspecialchars($latestEvent['content']) ?></p>
                <div class="home-event-date">📅 <?= htmlspecialchars($latestEvent['event_date'] ?: '日期未定') ?></div>
            <?php else: ?>
                <p style="color:#8a7a6a;font-size:0.9rem;">暂无家族记事</p>
            <?php endif; ?>
        </div>
    </a>

    <!-- 3. 最新留言，点击跳转 -->
    <a href="?page=guestbook" class="home-card home-guestbook" style="text-decoration:none;color:inherit;display:block;">
        <div class="home-card-header">
            <h3>💬 最新留言</h3>
            <span class="home-card-more">查看全部 →</span>
        </div>
        <div class="home-card-body">
            <?php if ($latestMessage): ?>
                <div class="home-guestbook-name">👤 <?= htmlspecialchars($latestMessage['name']) ?></div>
                <p class="home-text-clamp"><?= htmlspecialchars($latestMessage['content']) ?></p>
                <div class="home-event-date">📅 <?= date('Y-m-d', strtotime($latestMessage['created_at'])) ?></div>
            <?php else: ?>
                <p style="color:#8a7a6a;font-size:0.9rem;">暂无留言，快来写下第一条吧！</p>
            <?php endif; ?>
        </div>
    </a>
</div>

<!-- 统计卡片 - 四个都加链接 -->
<div class="home-stats">
    <a href="?page=members" class="home-stat-item" style="text-decoration:none;color:inherit;display:block;">
        <div class="home-stat-icon">👥</div>
        <div class="home-stat-number"><?= count($members) ?></div>
        <div class="home-stat-label">家族成员 →</div>
    </a>
    <a href="?page=events" class="home-stat-item" style="text-decoration:none;color:inherit;display:block;">
        <div class="home-stat-icon">📅</div>
        <div class="home-stat-number"><?= count($clanEvents) ?></div>
        <div class="home-stat-label">家族记事 →</div>
    </a>
    <a href="?page=photos" class="home-stat-item" style="text-decoration:none;color:inherit;display:block;">
        <div class="home-stat-icon">🖼️</div>
        <div class="home-stat-number"><?= count($clanPhotos) ?></div>
        <div class="home-stat-label">家族相册 →</div>
    </a>
    <a href="?page=guestbook" class="home-stat-item" style="text-decoration:none;color:inherit;display:block;">
        <div class="home-stat-icon">💬</div>
        <div class="home-stat-number"><?= $latestMessage ? '有新留言' : '暂无' ?></div>
        <div class="home-stat-label">留言板 →</div>
    </a>
</div>

<!-- 最新成员 & 最新照片 -->
<div class="home-two-col">
    <div class="home-col">
        <div class="home-col-header">
            <h3>👤 最新成员</h3>
            <a href="?page=members" class="home-card-more">查看全部 →</a>
        </div>
        <div class="home-col-body">
            <?php if (empty($latestMembers)): ?>
                <div style="color:#8a7a6a;font-size:0.9rem;padding:10px 0;">暂无成员</div>
            <?php else: ?>
                <?php foreach ($latestMembers as $m): ?>
                    <div class="home-member-item" onclick="openDetail(<?= $m['id'] ?>)">
                        <span class="home-member-name"><?= htmlspecialchars($m['name']) ?></span>
                        <span class="home-member-gen">第<?= $m['generation'] ?>世</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="home-col">
        <div class="home-col-header">
            <h3>🖼️ 最新照片</h3>
            <a href="?page=photos" class="home-card-more">查看全部 →</a>
        </div>
        <div class="home-col-body">
            <?php if (empty($latestPhotos)): ?>
                <div style="color:#8a7a6a;font-size:0.9rem;padding:10px 0;">暂无照片</div>
            <?php else: ?>
                <div class="home-photo-grid">
                    <?php foreach ($latestPhotos as $p): ?>
                        <a href="?page=photos" class="home-photo-item">
                            <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* ===== 首页样式 ===== */
.home-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.home-card {
    background: #fcf7f2;
    border-radius: 16px;
    padding: 14px 18px;
    border: 1px solid #e6d7c6;
    transition: 0.3s;
    cursor: pointer;
}
.home-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.10);
    transform: translateY(-2px);
    border-color: #b2977d;
}

.home-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ede5db;
    padding-bottom: 8px;
    margin-bottom: 8px;
}
.home-card-header h3 {
    color: #5f4a34;
    font-weight: 400;
    font-size: 1.05rem;
    letter-spacing: 1px;
    margin: 0;
}
.home-card-more {
    color: #b2977d;
    text-decoration: none;
    font-size: 0.8rem;
    cursor: pointer;
}
.home-card-more:hover {
    text-decoration: underline;
}

.home-card-body {
    font-size: 0.95rem;
    line-height: 1.6;
    color: #3e2e1f;
}
.home-text-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.6;
    margin: 0;
}

.home-event-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: #3f2e1d;
}
.home-event-date {
    font-size: 0.75rem;
    color: #a48d78;
    margin-top: 4px;
}
.home-guestbook-name {
    font-weight: 500;
    color: #5f4a34;
    font-size: 0.9rem;
}

/* ===== 统计卡片 ===== */
.home-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}
.home-stat-item {
    background: #fcf7f2;
    border-radius: 14px;
    padding: 16px;
    text-align: center;
    border: 1px solid #e6d7c6;
    transition: 0.3s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}
.home-stat-item:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transform: translateY(-2px);
    border-color: #b2977d;
}
.home-stat-icon {
    font-size: 1.8rem;
}
.home-stat-number {
    font-size: 1.4rem;
    font-weight: 300;
    color: #3f2e1d;
}
.home-stat-label {
    font-size: 0.8rem;
    color: #8a7a6a;
}

/* ===== 两栏布局 ===== */
.home-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.home-col {
    background: #fcf7f2;
    border-radius: 16px;
    padding: 14px 18px;
    border: 1px solid #e6d7c6;
}
.home-col-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ede5db;
    padding-bottom: 8px;
    margin-bottom: 10px;
}
.home-col-header h3 {
    color: #5f4a34;
    font-weight: 400;
    font-size: 1.05rem;
    letter-spacing: 1px;
    margin: 0;
}

.home-col-body {
    max-height: 320px;
    overflow-y: auto;
}

.home-member-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid #f0e8df;
    font-size: 0.9rem;
    cursor: pointer;
    transition: 0.15s;
}
.home-member-item:hover {
    background: #f5ede4;
    padding-left: 6px;
    border-radius: 4px;
}
.home-member-name {
    font-weight: 500;
    color: #3f2e1d;
}
.home-member-gen {
    font-size: 0.75rem;
    color: #a48d78;
}

/* ===== 照片网格 ===== */
.home-photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    gap: 8px;
}
.home-photo-item {
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid #ede5db;
    cursor: pointer;
    transition: 0.2s;
    display: block;
    text-decoration: none;
}
.home-photo-item:hover {
    transform: scale(1.05);
    border-color: #b2977d;
}
.home-photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ===== 移动端适配 ===== */
@media (max-width: 768px) {
    .home-grid {
        grid-template-columns: 1fr;
    }
    .home-two-col {
        grid-template-columns: 1fr;
    }
    .home-photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    }
}
</style>