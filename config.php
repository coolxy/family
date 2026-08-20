<?php
// 文件: config.php - 数据库配置与初始化（完整修复版）
// ============================================================
// 功能说明：
// - SQLite数据库连接与初始化
// - 自动创建所有表结构
// - 自动检查并添加缺失的字段
// - 提供成员数据操作函数
// - 会话管理
// - 层级菜单生成
// - 世代计算
// - 配偶关系更新
// - 相册分类管理
// ============================================================

// ============================================================
// 家族名称配置（全站统一）
// ============================================================

/**
 * 获取家族名称
 * 修改这里即可全站更新
 */
function getClanName() {
    return '许氏';  // ★★★ 改这里就行 ★★★
}

/**
 * 获取完整家族名称（带"宗谱"）
 */
function getClanFullName() {
    return getClanName() . '宗谱';
}

/**
 * 获取家族全称（带"家族"）
 */
function getClanFamilyName() {
    return getClanName() . '家族';
}

/**
 * 获取网站标题（用于页面标题）
 */
function getSiteTitle() {
    return getClanFullName() . ' · 族谱系统';
}

// ============================================================
// 1. 数据库连接与初始化
// ============================================================

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new SQLite3('data/family_tree.db');
        
        // ---------- 创建 members 表 ----------
        $db->exec('CREATE TABLE IF NOT EXISTS members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            gender TEXT CHECK(gender IN ("男","女")),
            birth_date TEXT,
            death_date TEXT,
            father_id INTEGER,
            mother_id INTEGER,
            spouse_id INTEGER,
            generation INTEGER,
            biography TEXT,
            rank TEXT,
            profession TEXT,
            spouse_name TEXT,
            spouse_birth TEXT,
            spouse_death TEXT,
            spouse_biography TEXT,
            spouse_hometown TEXT,
            address TEXT,
            mother_name TEXT,
            avatar TEXT
        )');
        
        // ---------- 创建 relationships 表 ----------
        $db->exec('CREATE TABLE IF NOT EXISTS relationships (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            member_id INTEGER,
            related_id INTEGER,
            relation_type TEXT
        )');
        
        // ---------- 创建 clan_summary 表 ----------
        $db->exec("CREATE TABLE IF NOT EXISTS clan_summary (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content TEXT
        )");
        
        // ---------- 创建 clan_events 表（添加 image_path 字段） ----------
        $db->exec("CREATE TABLE IF NOT EXISTS clan_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            content TEXT,
            event_date TEXT,
            image_path TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // ---------- 创建 photo_albums 表 ----------
        $db->exec("CREATE TABLE IF NOT EXISTS photo_albums (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // ---------- 创建 clan_photos 表 ----------
        $db->exec("CREATE TABLE IF NOT EXISTS clan_photos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            album_id INTEGER DEFAULT 0,
            title TEXT,
            description TEXT,
            image_path TEXT,
            uploader TEXT,
            upload_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // ---------- 创建 users 表 ----------
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            real_name TEXT,
            member_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // ---------- 创建 guestbook 表 ----------
        $db->exec("CREATE TABLE IF NOT EXISTS guestbook (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            content TEXT NOT NULL,
            ip TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status INTEGER DEFAULT 1,
            reply TEXT,
            reply_at DATETIME
        )");
        
        // ---------- 创建 downloads 表 ----------
        $db->exec("CREATE TABLE IF NOT EXISTS downloads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            file_name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_size TEXT,
            uploader TEXT,
            download_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // ============================================================
        // 检查并添加缺失的字段（members 表）
        // ============================================================
        $columns = [];
        $result = $db->query("PRAGMA table_info(members)");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }
        
        $missingColumns = [
            'rank' => 'TEXT',
            'profession' => 'TEXT',
            'spouse_name' => 'TEXT',
            'spouse_birth' => 'TEXT',
            'spouse_death' => 'TEXT',
            'spouse_biography' => 'TEXT',
            'spouse_hometown' => 'TEXT',
            'address' => 'TEXT',
            'mother_name' => 'TEXT',
            'avatar' => 'TEXT'
        ];
        
        foreach ($missingColumns as $col => $type) {
            if (!in_array($col, $columns)) {
                $db->exec("ALTER TABLE members ADD COLUMN $col $type");
            }
        }
        
        // ============================================================
        // 检查并添加缺失的字段（clan_photos 表）
        // ============================================================
        $columns = [];
        $result = $db->query("PRAGMA table_info(clan_photos)");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }
        
        if (!in_array('album_id', $columns)) {
            $db->exec("ALTER TABLE clan_photos ADD COLUMN album_id INTEGER DEFAULT 0");
        }
        if (!in_array('uploader', $columns)) {
            $db->exec("ALTER TABLE clan_photos ADD COLUMN uploader TEXT");
        }
        
        // ============================================================
        // ★★★ 检查并添加缺失的字段（clan_events 表）★★★
        // ============================================================
        $columns = [];
        $result = $db->query("PRAGMA table_info(clan_events)");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }
        
        if (!in_array('image_path', $columns)) {
            $db->exec("ALTER TABLE clan_events ADD COLUMN image_path TEXT");
        }
        
        // ============================================================
        // 初始化默认数据
        // ============================================================
        
        // 初始化宗族综述
        $count = $db->querySingle("SELECT COUNT(*) FROM clan_summary");
        if ($count == 0) {
            $db->exec("INSERT INTO clan_summary (content) VALUES ('许氏家族，源远流长。先祖以忠厚传家，诗书继世。族人遍布四方，英才辈出。今修族谱，以彰祖德，联宗谊，垂后世。')");
        }
        
        // 添加默认相册
        $albumCount = $db->querySingle("SELECT COUNT(*) FROM photo_albums");
        if ($albumCount == 0) {
            $db->exec("INSERT INTO photo_albums (name, description, sort_order) VALUES ('默认相册', '系统默认相册', 0)");
        }
        
        // 创建默认管理员账户 (admin / 123456)
        $adminCheck = $db->querySingle("SELECT COUNT(*) FROM users WHERE username='admin'");
        if ($adminCheck == 0) {
            $hashed = password_hash('123456', PASSWORD_DEFAULT);
            $db->exec("INSERT INTO users (username, password) VALUES ('admin', '$hashed')");
        }
    }
    return $db;
}

// ============================================================
// 2. 会话管理
// ============================================================

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    startSession();
    return $_SESSION['username'] ?? null;
}

function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? 0;
}

function loginUser($username, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['real_name'] = $user['real_name'] ?? '';
        $_SESSION['member_id'] = $user['member_id'] ?? 0;
        return true;
    }
    return false;
}

function logoutUser() {
    startSession();
    session_destroy();
}

// ============================================================
// 3. 成员数据操作
// ============================================================

function getMemberName($id) {
    if (!$id) return '';
    $db = getDB();
    $result = $db->querySingle("SELECT name FROM members WHERE id = $id", true);
    return $result ? $result['name'] : '';
}

function getMember($id) {
    $db = getDB();
    return $db->querySingle("SELECT * FROM members WHERE id = $id", true);
}

function getAllMembers() {
    $db = getDB();
    $result = $db->query("SELECT * FROM members ORDER BY generation ASC, id ASC");
    $members = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $members[] = $row;
    }
    return $members;
}

function getMembersPaginated($page = 1, $perPage = 30) {
    $db = getDB();
    $offset = ($page - 1) * $perPage;
    $result = $db->query("SELECT * FROM members ORDER BY generation ASC, id ASC LIMIT $perPage OFFSET $offset");
    $members = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $members[] = $row;
    }
    return $members;
}

function getMembersCount() {
    $db = getDB();
    return $db->querySingle("SELECT COUNT(*) FROM members");
}

function getClanSummary() {
    $db = getDB();
    $result = $db->querySingle("SELECT content FROM clan_summary LIMIT 1", true);
    return $result ? $result['content'] : '';
}

function updateClanSummary($content) {
    $db = getDB();
    $db->exec("UPDATE clan_summary SET content = '" . SQLite3::escapeString($content) . "'");
}

function getClanEvents() {
    $db = getDB();
    $result = $db->query("SELECT * FROM clan_events ORDER BY event_date DESC, created_at DESC");
    $events = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $events[] = $row;
    }
    return $events;
}

// ============================================================
// 4. 相册分类管理
// ============================================================

function getPhotoAlbums() {
    $db = getDB();
    $result = $db->query("SELECT * FROM photo_albums ORDER BY sort_order ASC, id ASC");
    $albums = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $albums[] = $row;
    }
    return $albums;
}

function getPhotosByAlbum($albumId = 0) {
    $db = getDB();
    $result = $db->query("SELECT * FROM clan_photos WHERE album_id = $albumId ORDER BY upload_date DESC");
    $photos = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $photos[] = $row;
    }
    return $photos;
}

function getAllPhotosWithAlbum() {
    $db = getDB();
    $result = $db->query("SELECT p.*, a.name as album_name 
                          FROM clan_photos p 
                          LEFT JOIN photo_albums a ON p.album_id = a.id 
                          ORDER BY p.upload_date DESC");
    $photos = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $photos[] = $row;
    }
    return $photos;
}

function getClanPhotos() {
    return getAllPhotosWithAlbum();
}

// ============================================================
// 5. 层级菜单（用于下拉选择父亲/母亲）
// ============================================================

function getMembersHierarchical($members, $excludeId = 0) {
    $childMap = [];
    $nameMap = [];
    
    foreach ($members as $m) {
        $nameMap[$m['id']] = $m['name'];
        $pid = $m['father_id'] ?: $m['mother_id'];
        if ($pid) {
            if (!isset($childMap[$pid])) {
                $childMap[$pid] = [];
            }
            $childMap[$pid][] = $m['id'];
        }
    }
    
    $roots = [];
    foreach ($members as $m) {
        if (!$m['father_id'] && !$m['mother_id']) {
            $roots[] = $m['id'];
        }
    }
    
    if (empty($roots) && !empty($members)) {
        $roots = array_column($members, 'id');
    }
    
    $result = [];
    
    function traverse($id, $childMap, $nameMap, &$result, $prefix = '', $generation = 0, $excludeId = 0) {
        if ($id == $excludeId) return;
        
        $result[] = [
            'id' => $id,
            'name' => $prefix . ($nameMap[$id] ?? '未知'),
            'generation' => $generation,
            'indent' => $generation
        ];
        
        if (isset($childMap[$id])) {
            foreach ($childMap[$id] as $childId) {
                traverse($childId, $childMap, $nameMap, $result, $prefix . '　', $generation + 1, $excludeId);
            }
        }
    }
    
    foreach ($roots as $rid) {
        traverse($rid, $childMap, $nameMap, $result, '', 0, $excludeId);
    }
    
    return $result;
}

function generateHierarchicalSelect($members, $selectedId = 0, $name = 'father_id', $label = '父亲', $excludeId = 0) {
    $hierarchical = getMembersHierarchical($members, $excludeId);
    $html = '<div class="form-group">';
    $html .= '<label>' . $label . '</label>';
    $html .= '<select name="' . $name . '">';
    $html .= '<option value="">-- 请选择' . $label . ' --</option>';
    
    foreach ($hierarchical as $m) {
        $selected = ($m['id'] == $selectedId) ? 'selected' : '';
        $indent = str_repeat('　', $m['indent']);
        $html .= '<option value="' . $m['id'] . '" ' . $selected . ' style="padding-left: ' . ($m['indent'] * 20) . 'px;">';
        $html .= $indent . htmlspecialchars($m['name']) . ' (世' . ($m['generation'] + 1) . ')';
        $html .= '</option>';
    }
    
    $html .= '</select>';
    $html .= '</div>';
    return $html;
}

// ============================================================
// 6. 世代计算
// ============================================================

function calculateGeneration($fatherId, $motherId, $members) {
    $maxGen = 0;
    foreach ($members as $m) {
        if ($m['id'] == $fatherId || $m['id'] == $motherId) {
            if ($m['generation'] > $maxGen) {
                $maxGen = $m['generation'];
            }
        }
    }
    return $maxGen + 1;
}

// ============================================================
// 7. 配偶关系更新（联动）
// ============================================================

function updateSpouseRelationships($memberId, $spouseName, $members) {
    if (empty($spouseName)) return;
    
    $db = getDB();
    
    foreach ($members as $m) {
        if ($m['name'] == $spouseName && $m['id'] != $memberId) {
            $db->exec("UPDATE members SET spouse_id = $memberId WHERE id = {$m['id']}");
            $db->exec("UPDATE members SET spouse_id = {$m['id']} WHERE id = $memberId");
            
            $db->exec("DELETE FROM relationships WHERE (member_id = $memberId AND related_id = {$m['id']}) OR (member_id = {$m['id']} AND related_id = $memberId)");
            $db->exec("INSERT INTO relationships (member_id, related_id, relation_type) VALUES ($memberId, {$m['id']}, '配偶')");
            break;
        }
    }
}

// ============================================================
// 8. 清理输出缓冲区（用于JSON响应）
// ============================================================

function cleanOutputBuffer() {
    while (ob_get_level()) {
        ob_end_clean();
    }
}

// ============================================================
// 9. 错误处理（用于JSON响应）
// ============================================================

function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: $errstr in $errfile on line $errline");
    return true;
}

// ============================================================
// 10. 确保上传目录存在
// ============================================================

function ensureUploadDirs() {
    $dirs = ['uploads/avatars/', 'uploads/photos/', 'uploads/events/'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

ensureUploadDirs();

// ============================================================
// 11. 获取用户关联的成员信息
// ============================================================

function getUserMemberInfo($userId) {
    $db = getDB();
    $result = $db->querySingle("SELECT member_id, real_name FROM users WHERE id = $userId", true);
    if ($result && $result['member_id'] > 0) {
        $member = getMember($result['member_id']);
        if ($member) {
            return [
                'member_id' => $result['member_id'],
                'real_name' => $result['real_name'],
                'member_name' => $member['name'],
                'generation' => $member['generation'],
                'gender' => $member['gender']
            ];
        }
    }
    return null;
}


?>