<?php
// 文件: index.php - 主入口
require_once 'config.php';
require_once 'tree.php';

$members = getAllMembers();
$loggedIn = isLoggedIn();
$currentUser = getCurrentUser();
$userRealName = $_SESSION['real_name'] ?? '';
$userMemberId = $_SESSION['member_id'] ?? 0;
$activePage = $_GET['page'] ?? 'home';
$clanSummary = getClanSummary();

// 处理首页跳转参数
$jumpTo = $_GET['jump'] ?? '';
if ($jumpTo == 'members') $activePage = 'members';
elseif ($jumpTo == 'events') $activePage = 'events';
elseif ($jumpTo == 'photos') $activePage = 'photos';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getSiteTitle() ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📜</text></svg>">
    <link rel="stylesheet" href="style.css">
    <!-- 引入D3.js -->
    <script src="js/d3.v7.min.js"></script>
</head>

<body>
<div class="container">
    <!-- 顶部 -->
    <div class="header-top">
        <h1>📜 <?= getClanFullName() ?> <small>血脉传承 · 族脉永续</small></h1>
        <div class="user-area">
            <?php if ($loggedIn): ?>
                <span>👋 <?= htmlspecialchars($userRealName ?: $currentUser) ?></span>
                <button class="btn-sm" onclick="showProfile()">👤 个人信息</button>
                <form method="post" action="auth.php" style="display:inline;">
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="return_page" value="<?= $activePage ?>">
                    <button type="submit" class="btn-sm">退出</button>
                </form>
            <?php else: ?>
                <button class="btn-sm" onclick="showLogin()">登录</button>
                <button class="btn-sm" onclick="showRegister()">注册</button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 导航 -->
    <nav class="top-nav">
        <a href="?page=home" class="<?= $activePage == 'home' ? 'active' : '' ?>">🏠 主页</a>
        <a href="?page=summary" class="<?= $activePage == 'summary' ? 'active' : '' ?>">📖 家族综述</a>
        <a href="?page=events" class="<?= $activePage == 'events' ? 'active' : '' ?>">📅 家族记事</a>
        <a href="?page=photos" class="<?= $activePage == 'photos' ? 'active' : '' ?>">🖼️ 家族相册</a>
        <a href="?page=members" class="<?= $activePage == 'members' ? 'active' : '' ?>">👥 族人名录</a>
        <a href="?page=pedigree" class="<?= $activePage == 'pedigree' ? 'active' : '' ?>">🌳 世系图</a>
        <a href="?page=downloads" class="<?= $activePage == 'downloads' ? 'active' : '' ?>">📥 资料下载</a>
        <a href="?page=guestbook" class="<?= $activePage == 'guestbook' ? 'active' : '' ?>">💬 留言板</a>
    </nav>

    <?php if (isset($_GET['msg'])): ?>
        <div class="msg <?= isset($_GET['error']) ? 'msg-error' : '' ?>">✦ <?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- 内容区 -->
    <!-- ============================================================ -->
    <?php if ($activePage === 'pedigree'): ?>
        <div class="tab-container">
            <div class="tab-bar" id="tabBar">
                <button class="tab-btn active" data-tab="pedigree" data-persistent="true">🌳 总世系图</button>
            </div>
            <div id="tabContent">
                <div class="tab-panel active" id="tab-pedigree">
                    <?php include 'pages/pedigree.php'; ?>
                </div>
            </div>
        </div>
    <?php elseif ($activePage === 'members'): ?>
        <div class="page-content">
            <?php include 'pages/members.php'; ?>
        </div>
    <?php else: ?>
        <div class="page-content">
            <?php
            $pageFile = 'pages/' . $activePage . '.php';
            if (file_exists($pageFile)) {
                include $pageFile;
            } else {
                include 'pages/home.php';
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="footer-note"><?= getClanFamilyName() ?> · 族谱系统 · 传承家族记忆</div>
</div>

<!-- 模态框 -->
<!-- 登录 -->
<div id="loginModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <span class="close-btn" onclick="closeLogin()">&times;</span>
        <h3>🔐 登录</h3>
        <form method="post" action="auth.php">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="return_page" value="<?= $activePage ?>">
            <div class="form-group"><label>用户名</label><input type="text" name="username" required></div>
            <div class="form-group"><label>密码</label><input type="password" name="password" required></div>
            <button type="submit" class="btn" style="width:100%;">登录</button>
            <p style="margin-top:10px; text-align:center; font-size:0.9rem; color:#8a7a6a;">默认: admin / 123456</p>
        </form>
    </div>
</div>

<!-- 注册 -->
<div id="registerModal" class="modal">
    <div class="modal-content" style="max-width:450px;">
        <span class="close-btn" onclick="closeRegister()">&times;</span>
        <h3>📝 注册</h3>
        <form method="post" action="auth.php">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="return_page" value="<?= $activePage ?>">
            <div class="form-group"><label>用户名</label><input type="text" name="username" required></div>
            <div class="form-group"><label>真实姓名</label><input type="text" name="real_name" placeholder="如：许文正"></div>
            <div class="form-group"><label>关联族谱成员</label>
                <select name="member_id">
                    <option value="0">-- 暂不关联 --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (世<?= $m['generation'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>密码 (至少6位)</label><input type="password" name="password" required minlength="6"></div>
            <div class="form-group"><label>确认密码</label><input type="password" name="confirm_password" required></div>
            <button type="submit" class="btn" style="width:100%;">注册</button>
        </form>
    </div>
</div>

<!-- 个人信息 -->
<div id="profileModal" class="modal">
    <div class="modal-content" style="max-width:450px;">
        <span class="close-btn" onclick="closeProfile()">&times;</span>
        <h3>👤 个人信息</h3>
        <form method="post" action="auth.php">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="return_page" value="<?= $activePage ?>">
            <div class="form-group"><label>用户名</label><input type="text" value="<?= htmlspecialchars($currentUser) ?>" disabled style="background:#f0e8df;"></div>
            <div class="form-group"><label>真实姓名</label><input type="text" name="real_name" value="<?= htmlspecialchars($userRealName) ?>" placeholder="与族谱姓名对应"></div>
            <div class="form-group"><label>关联族谱成员</label>
                <select name="member_id">
                    <option value="0">-- 暂不关联 --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($userMemberId == $m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> (世<?= $m['generation'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>新密码（留空不修改）</label><input type="password" name="password" placeholder="至少6位"></div>
            <button type="submit" class="btn" style="width:100%;">💾 保存修改</button>
        </form>
        <?php if ($userMemberId > 0): $member = getMember($userMemberId); if ($member): ?>
            <div style="margin-top:14px; padding-top:12px; border-top:1px solid #ede5db;">
                <p style="font-size:0.9rem; color:#5f4a34;">📌 关联族谱成员：<strong><?= htmlspecialchars($member['name']) ?></strong></p>
                <p style="font-size:0.85rem; color:#8a7a6a;">世系：第 <?= $member['generation'] ?> 世 · <?= $member['gender'] ?></p>
            </div>
        <?php endif; endif; ?>
    </div>
</div>

<!-- 添丁入谱 -->
<div id="addMemberModal" class="modal">
    <div class="modal-content" style="max-width:700px;">
        <span class="close-btn" onclick="closeAddMember()">&times;</span>
        <h3>➕ 添丁入谱</h3>
        <form method="post" action="member_actions.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="return_page" value="<?= $activePage ?>">
            <?php $hierarchicalMembers = getMembersHierarchical($members); ?>
            
            <div class="form-row">
                <div class="form-group"><label>姓名 *</label><input type="text" name="name" required placeholder="例如：许文正"></div>
                <div class="form-group"><label>性别</label>
                    <select name="gender">
                        <option value="男">男</option>
                        <option value="女">女</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label>排行</label>
                    <select name="rank">
                        <option value="">-- 请选择 --</option>
                        <option value="长子">长子</option><option value="次子">次子</option>
                        <option value="三子">三子</option><option value="四子">四子</option>
                        <option value="五子">五子</option><option value="六子">六子</option>
                        <option value="七子">七子</option><option value="八子">八子</option>
                        <option value="九子">九子</option><option value="十子">十子</option>
                        <option value="长女">长女</option><option value="次女">次女</option>
                        <option value="三女">三女</option><option value="四女">四女</option>
                        <option value="五女">五女</option>
                    </select>
                </div>
                <div class="form-group"><label>世代</label><input type="number" name="generation" placeholder="自动计算" value="1" id="genInput"></div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label>出生日期</label><input type="text" name="birth_date" placeholder="如：1965-03-12"></div>
                <div class="form-group"><label>逝世日期</label><input type="text" name="death_date" placeholder="健在可留空"></div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label>父亲</label>
                    <select name="father_id" id="fatherSelect" onchange="updateGeneration()">
                        <option value="">-- 请选择父亲 --</option>
                        <?php foreach ($hierarchicalMembers as $m): ?>
                            <option value="<?= $m['id'] ?>" style="padding-left: <?= $m['indent'] * 20 ?>px;">
                                <?= str_repeat('　', $m['indent']) ?><?= htmlspecialchars($m['name']) ?> (世<?= $m['generation']+1 ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>母亲姓名</label><input type="text" name="mother_name" placeholder="直接录入母亲姓名"></div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label>职业</label><input type="text" name="profession" placeholder="职业/身份"></div>
                <div class="form-group"><label>居住地址</label><input type="text" name="address" placeholder="现居住地"></div>
            </div>
            
            <div class="form-group"><label>生平履历</label><textarea name="biography" rows="3" placeholder="生平事迹、功绩..."></textarea></div>
            
            <div class="form-group"><label>头像</label><input type="file" name="avatar" accept="image/*"></div>
            
            <div style="border-top:2px dashed #dacfc2; padding-top:14px; margin-top:8px;">
                <p style="color:#7b5d42; font-weight:500; margin-bottom:10px;">💑 配偶信息</p>
                
                <div class="form-row">
                    <div class="form-group"><label>配偶姓名</label><input type="text" name="spouse_name" placeholder="配偶姓名"></div>
                    <div class="form-group"><label>配偶出生日期</label><input type="text" name="spouse_birth" placeholder="如：1965-03-12"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group"><label>配偶逝世日期</label><input type="text" name="spouse_death" placeholder="健在可留空"></div>
                    <div class="form-group"><label>配偶娘家所在地</label><input type="text" name="spouse_hometown" placeholder="如：XX省XX县XX村"></div>
                </div>
                
                <div class="form-group"><label>配偶履历</label><textarea name="spouse_biography" rows="2" placeholder="配偶生平履历..."></textarea></div>
            </div>
            
            <button type="submit" class="btn" style="width:100%; margin-top:12px;">✨ 录入族谱</button>
        </form>
    </div>
</div>

<!-- 详情模态框 -->
<div id="detailModal" class="modal">
    <div class="modal-content" style="max-width:700px;">
        <span class="close-btn" onclick="closeDetail()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<script src="script.js"></script>
<script>
// ★★★ 初始化成员数据和登录状态 - 确保在所有页面中可用 ★★★
var allMembers = <?= json_encode($members) ?>;
var loggedIn = <?= $loggedIn ? 'true' : 'false' ?>;

// 确保全局变量可用
window.allMembers = allMembers;
window.loggedIn = loggedIn;

console.log('页面初始化: loggedIn =', loggedIn, '成员数 =', allMembers.length);

// 确保 openDetail 在全局可用（如果 script.js 未加载完成）
if (typeof window.openDetail !== 'function') {
    window.openDetail = function(memberId) {
        console.log('备用 openDetail 被调用:', memberId);
        if (typeof openDetail === 'function') {
            openDetail(memberId);
        } else {
            alert('详情功能加载中，请刷新页面后重试');
        }
    };
}

// 确保 openAddMember 在全局可用
if (typeof window.openAddMember !== 'function') {
    window.openAddMember = function() {
        console.log('备用 openAddMember 被调用');
        if (typeof openAddMember === 'function') {
            openAddMember();
        } else {
            alert('请先登录');
        }
    };
}
</script>
</body>
</html>