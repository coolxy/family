<?php
// 文件: member_actions.php - 完整版
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config.php';
require_once 'tree.php';

// ============================================================
// 图片压缩函数
// ============================================================
function compressImage($source, $destination, $maxWidth = 800, $quality = 80) {
    // 获取图片信息
    $info = getimagesize($source);
    if (!$info) return false;
    
    $width = $info[0];
    $height = $info[1];
    $type = $info[2];
    
    // 如果图片尺寸小于最大宽度，直接复制
    if ($width <= $maxWidth) {
        return copy($source, $destination);
    }
    
    // 计算缩放比例
    $ratio = $maxWidth / $width;
    $newWidth = $maxWidth;
    $newHeight = intval($height * $ratio);
    
    // 创建画布
    $image = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    // 创建缩放后的图片
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // 处理透明背景
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // 缩放
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // 保存
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($newImage, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($newImage, $destination, 8);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($newImage, $destination);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($newImage, $destination, $quality);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($newImage);
    
    return $result;
}

// ============================================================
// 辅助函数
// ============================================================

function checkAuth() {
    if (!isLoggedIn()) {
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=members&msg=请先登录&error=1');
        exit;
    }
}

// ============================================================
// POST 请求处理
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $db = getDB();
    $returnPage = $_POST['return_page'] ?? 'members';
    
    // ==========================================================
    // 1. 成员添加
    // ==========================================================
    if ($action === 'add') {
        checkAuth();
        
        $name = SQLite3::escapeString(trim($_POST['name'] ?? ''));
        $gender = SQLite3::escapeString($_POST['gender'] ?? '男');
        $birth = SQLite3::escapeString($_POST['birth_date'] ?? '');
        $death = SQLite3::escapeString($_POST['death_date'] ?? '');
        $father = intval($_POST['father_id'] ?? 0);
        $mother_name = SQLite3::escapeString(trim($_POST['mother_name'] ?? ''));
        $spouse = intval($_POST['spouse_id'] ?? 0);
        $generation = intval($_POST['generation'] ?? 1);
        $bio = SQLite3::escapeString($_POST['biography'] ?? '');
        $rank = SQLite3::escapeString($_POST['rank'] ?? '');
        $profession = SQLite3::escapeString($_POST['profession'] ?? '');
        $spouse_name = SQLite3::escapeString(trim($_POST['spouse_name'] ?? ''));
        $spouse_birth = SQLite3::escapeString($_POST['spouse_birth'] ?? '');
        $spouse_death = SQLite3::escapeString($_POST['spouse_death'] ?? '');
        $spouse_biography = SQLite3::escapeString($_POST['spouse_biography'] ?? '');
        $spouse_hometown = SQLite3::escapeString($_POST['spouse_hometown'] ?? '');
        $address = SQLite3::escapeString($_POST['address'] ?? '');
        
        // ★★★ 查找母亲ID ★★★
        $mother_id = 0;
        if (!empty($mother_name)) {
            $motherMember = $db->querySingle("SELECT id FROM members WHERE name = '$mother_name' LIMIT 1", true);
            if ($motherMember) {
                $mother_id = $motherMember['id'];
            }
        }
        
        // 头像上传
        $avatar = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $uploadDir = 'uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'member_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
                    $avatar = $uploadDir . $filename;
                }
            }
        }
        
        // 如果选择了父亲，自动计算世代
        if ($father) {
            $members = getAllMembers();
            $generation = calculateGeneration($father, 0, $members);
        }
        
        // ★★★ SQL 中添加 mother_id 字段 ★★★
        $sql = "INSERT INTO members (
            name, gender, birth_date, death_date, father_id, mother_id, mother_name,
            spouse_id, generation, biography, rank, profession, 
            spouse_name, spouse_birth, spouse_death, spouse_biography, 
            spouse_hometown, address, avatar
        ) VALUES (
            '$name', '$gender', '$birth', '$death', $father, $mother_id, '$mother_name',
            $spouse, $generation, '$bio', '$rank', '$profession',
            '$spouse_name', '$spouse_birth', '$spouse_death', '$spouse_biography',
            '$spouse_hometown', '$address', '$avatar'
        )";
        
        $result = $db->exec($sql);
        if ($result === false) {
            die("<h1>数据库错误</h1>
                 <p><strong>错误信息：</strong> " . $db->lastErrorMsg() . "</p>
                 <p><strong>SQL语句：</strong> <pre>" . htmlspecialchars($sql) . "</pre></p>
                 <p><a href='index.php?page=" . $returnPage . "'>返回</a></p>");
        }
        
        $newId = $db->lastInsertRowID();
        
        if ($father) {
            $db->exec("INSERT INTO relationships (member_id, related_id, relation_type) VALUES ($newId, $father, '父子')");
        }
        if ($spouse) {
            $db->exec("INSERT INTO relationships (member_id, related_id, relation_type) VALUES ($newId, $spouse, '配偶')");
        }
        if ($mother_id) {
            $db->exec("INSERT INTO relationships (member_id, related_id, relation_type) VALUES ($newId, $mother_id, '母子')");
        }
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=成员添加成功');
        exit;
    }
    
    // ==========================================================
    // 2. 成员删除
    // ==========================================================
    if ($action === 'delete') {
        checkAuth();
        
        $id = intval($_POST['id'] ?? 0);
        $member = getMember($id);
        if ($member && !empty($member['avatar']) && file_exists($member['avatar'])) {
            unlink($member['avatar']);
        }
        $db->exec("DELETE FROM members WHERE id = $id");
        $db->exec("DELETE FROM relationships WHERE member_id = $id OR related_id = $id");
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=成员已删除');
        exit;
    }
    
    // ==========================================================
    // 3. 成员更新（完整版，支持母节点/父节点模式）
    // ==========================================================
    if ($action === 'update') {
        checkAuth();
        
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            die('无效的成员ID');
        }
        
        $oldMember = getMember($id);
        if (!$oldMember) {
            die('成员不存在');
        }
        
        // 保存旧值用于联动
        $oldSpouseName = $oldMember['spouse_name'] ?? '';
        $oldMotherName = $oldMember['mother_name'] ?? '';
        $oldMotherId = $oldMember['mother_id'] ?? 0;
        $oldFatherId = $oldMember['father_id'] ?? 0;
        
        // 获取表单数据
        $name = SQLite3::escapeString(trim($_POST['name'] ?? ''));
        $gender = SQLite3::escapeString($_POST['gender'] ?? '男');
        $birth = SQLite3::escapeString($_POST['birth_date'] ?? '');
        $death = SQLite3::escapeString($_POST['death_date'] ?? '');
        $father = intval($_POST['father_id'] ?? 0);
        $mother = intval($_POST['mother_id'] ?? 0);
        $spouse = intval($_POST['spouse_id'] ?? 0);
        $generation = intval($_POST['generation'] ?? 1);
        $bio = SQLite3::escapeString($_POST['biography'] ?? '');
        $rank = SQLite3::escapeString($_POST['rank'] ?? '');
        $profession = SQLite3::escapeString($_POST['profession'] ?? '');
        $spouse_name = SQLite3::escapeString(trim($_POST['spouse_name'] ?? ''));
        $spouse_birth = SQLite3::escapeString($_POST['spouse_birth'] ?? '');
        $spouse_death = SQLite3::escapeString($_POST['spouse_death'] ?? '');
        $spouse_biography = SQLite3::escapeString($_POST['spouse_biography'] ?? '');
        $spouse_hometown = SQLite3::escapeString($_POST['spouse_hometown'] ?? '');
        $address = SQLite3::escapeString($_POST['address'] ?? '');
        
        // ★★★ 获取父节点模式/母节点模式参数 ★★★
        $parent_spouse_name = SQLite3::escapeString(trim($_POST['parent_spouse_name'] ?? ''));
        $isMotherNode = intval($_POST['is_mother_node'] ?? 0);
        
        // ★★★ 根据模式决定 father_id 和 mother_id ★★★
        $final_father_id = 0;
        $final_mother_id = 0;
        $final_mother_name = '';
        $final_father_name = '';
        
        if ($isMotherNode) {
            // ★★★ 母节点模式：使用 mother_id（下拉菜单），父亲姓名输入框 ★★★
            $final_mother_id = $mother;  // 下拉菜单选中的母亲ID
            $final_father_name = $parent_spouse_name;  // 父亲姓名输入框
            $final_father_id = 0;  // 父节点模式下不使用 father_id
            
            // 如果有父亲姓名，查找对应的父亲ID（可选，用于关系表）
            if (!empty($final_father_name)) {
                $fatherMember = $db->querySingle("SELECT id FROM members WHERE name = '$final_father_name' AND id != $id LIMIT 1", true);
                if ($fatherMember) {
                    $final_father_id = $fatherMember['id'];
                }
            }
        } else {
            // ★★★ 父节点模式：使用 father_id（下拉菜单），母亲姓名输入框 ★★★
            $final_father_id = $father;  // 下拉菜单选中的父亲ID
            $final_mother_name = $parent_spouse_name;  // 母亲姓名输入框
            
            // 如果有母亲姓名，查找对应的母亲ID
            if (!empty($final_mother_name)) {
                $motherMember = $db->querySingle("SELECT id FROM members WHERE name = '$final_mother_name' AND id != $id LIMIT 1", true);
                if ($motherMember) {
                    $final_mother_id = $motherMember['id'];
                }
            }
        }
        
        // 头像处理
        $avatar = $oldMember['avatar'] ?? '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $uploadDir = 'uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'member_' . $id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
                    if (!empty($avatar) && file_exists($avatar)) {
                        unlink($avatar);
                    }
                    $avatar = $uploadDir . $filename;
                }
            }
        }
        
        // 自动计算世代
        if ($final_father_id) {
            $members = getAllMembers();
            $newGen = calculateGeneration($final_father_id, 0, $members);
            if ($newGen > $generation) {
                $generation = $newGen;
            }
        }
        if ($final_mother_id) {
            $members = getAllMembers();
            $newGen = calculateGeneration(0, $final_mother_id, $members);
            if ($newGen > $generation) {
                $generation = $newGen;
            }
        }
        
        // ★★★ 执行更新 ★★★
        $sql = "UPDATE members SET 
            name = '$name',
            gender = '$gender',
            birth_date = '$birth',
            death_date = '$death',
            father_id = $final_father_id,
            mother_id = $final_mother_id,
            mother_name = '$final_mother_name',
            spouse_id = $spouse,
            generation = $generation,
            biography = '$bio',
            rank = '$rank',
            profession = '$profession',
            spouse_name = '$spouse_name',
            spouse_birth = '$spouse_birth',
            spouse_death = '$spouse_death',
            spouse_biography = '$spouse_biography',
            spouse_hometown = '$spouse_hometown',
            address = '$address',
            avatar = '$avatar'
            WHERE id = $id";
        
        $result = $db->exec($sql);
        if ($result === false) {
            die("<h1>数据库更新错误</h1>
                 <p><strong>错误信息：</strong> " . $db->lastErrorMsg() . "</p>
                 <p><strong>SQL语句：</strong> <pre>" . htmlspecialchars($sql) . "</pre></p>");
        }
        
        // ★★★ 联动1: 配偶更新 → 其子女的母亲自动匹配 ★★★
        if ($oldSpouseName != $spouse_name && !empty($spouse_name)) {
            $children = $db->query("SELECT id FROM members WHERE father_id = $id OR mother_id = $id");
            while ($child = $children->fetchArray(SQLITE3_ASSOC)) {
                $childId = $child['id'];
                // 更新子女的 mother_name
                $db->exec("UPDATE members SET mother_name = '$spouse_name' WHERE id = $childId");
                
                // 查找是否有同名的母亲成员，更新 mother_id
                $motherMember = $db->querySingle("SELECT id FROM members WHERE name = '$spouse_name' AND id != $id LIMIT 1", true);
                if ($motherMember) {
                    $db->exec("UPDATE members SET mother_id = {$motherMember['id']} WHERE id = $childId");
                }
            }
        }
        
        // ★★★ 联动2: 母亲姓名更新 → 其父亲的配偶自动更新 ★★★
        if ($oldMotherName != $final_mother_name && !empty($final_mother_name) && $final_father_id > 0) {
            $db->exec("UPDATE members SET spouse_name = '$final_mother_name' WHERE id = $final_father_id");
            $db->exec("DELETE FROM relationships WHERE (member_id = $final_father_id AND related_id = $id) OR (member_id = $id AND related_id = $final_father_id)");
            $db->exec("INSERT INTO relationships (member_id, related_id, relation_type) VALUES ($final_father_id, $id, '配偶')");
        }
        
        // ★★★ 联动3: 母亲ID变化，更新关系表 ★★★
        if ($oldMotherId != $final_mother_id && $final_mother_id > 0) {
            // 删除旧的母子关系
            $db->exec("DELETE FROM relationships WHERE member_id = $id AND related_id = $oldMotherId");
            // 添加新的母子关系
            $db->exec("INSERT INTO relationships (member_id, related_id, relation_type) VALUES ($id, $final_mother_id, '母子')");
        }
        
        // ★★★ 联动4: 父亲ID变化，更新关系表 ★★★
        if ($oldFatherId != $final_father_id && $final_father_id > 0) {
            // 删除旧的父子关系
            $db->exec("DELETE FROM relationships WHERE member_id = $id AND related_id = $oldFatherId");
            // 添加新的父子关系
            $db->exec("INSERT INTO relationships (member_id, related_id, relation_type) VALUES ($id, $final_father_id, '父子')");
        }
        
        // ★★★ 联动5: 配偶姓名变化，更新配偶成员的spouse_id ★★★
        if ($oldSpouseName != $spouse_name && !empty($spouse_name)) {
            $members = getAllMembers();
            updateSpouseRelationships($id, $spouse_name, $members);
        }
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=成员信息已更新');
        exit;
    }
    
    // ==========================================================
    // 4. 更新宗族综述
    // ==========================================================
    if ($action === 'update_summary') {
        checkAuth();
        $content = SQLite3::escapeString($_POST['content'] ?? '');
        updateClanSummary($content);
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=宗族综述已更新');
        exit;
    }
    
    // ==========================================================
    // 5. 家族记事 - 添加（含图片压缩）
    // ==========================================================
    if ($action === 'add_event') {
        checkAuth();
        
        $title = SQLite3::escapeString($_POST['title'] ?? '');
        $content = SQLite3::escapeString($_POST['content'] ?? '');
        $event_date = SQLite3::escapeString($_POST['event_date'] ?? date('Y-m-d'));
        $image_path = '';
        
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
            $uploadDir = 'uploads/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'event_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $filepath = $uploadDir . $filename;
                
                $compressed = compressImage($_FILES['event_image']['tmp_name'], $filepath, 800, 80);
                if ($compressed) {
                    $image_path = $uploadDir . $filename;
                    $content .= "\n\n[图片]:" . $image_path;
                }
            }
        }
        
        $db->exec("INSERT INTO clan_events (title, content, event_date, image_path) VALUES ('$title', '$content', '$event_date', '$image_path')");
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=记事添加成功');
        exit;
    }
    
    // ==========================================================
    // 6. 家族记事 - 编辑（含图片压缩）
    // ==========================================================
    if ($action === 'edit_event') {
        checkAuth();
        
        $id = intval($_POST['id'] ?? 0);
        $title = SQLite3::escapeString($_POST['title'] ?? '');
        $content = SQLite3::escapeString($_POST['content'] ?? '');
        $event_date = SQLite3::escapeString($_POST['event_date'] ?? date('Y-m-d'));
        
        $oldEvent = $db->querySingle("SELECT image_path FROM clan_events WHERE id=$id", true);
        $image_path = $oldEvent ? $oldEvent['image_path'] : '';
        
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }
            $image_path = '';
            $content = preg_replace('/\n\n\[图片\]:[^\n]+/', '', $content);
        }
        
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
            $uploadDir = 'uploads/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'event_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $filepath = $uploadDir . $filename;
                
                $compressed = compressImage($_FILES['event_image']['tmp_name'], $filepath, 800, 80);
                if ($compressed) {
                    if (!empty($image_path) && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $uploadDir . $filename;
                    $content = preg_replace('/\n\n\[图片\]:[^\n]+/', '', $content);
                    $content .= "\n\n[图片]:" . $image_path;
                }
            }
        }
        
        $db->exec("UPDATE clan_events SET 
            title='$title', 
            content='$content', 
            event_date='$event_date',
            image_path='$image_path'
            WHERE id=$id");
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=记事已更新');
        exit;
    }
    
    // ==========================================================
    // 7. 家族记事 - 删除
    // ==========================================================
    if ($action === 'delete_event') {
        checkAuth();
        
        $id = intval($_POST['id'] ?? 0);
        $event = $db->querySingle("SELECT image_path FROM clan_events WHERE id=$id", true);
        if ($event && !empty($event['image_path']) && file_exists($event['image_path'])) {
            unlink($event['image_path']);
        }
        $db->exec("DELETE FROM clan_events WHERE id=$id");
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=记事已删除');
        exit;
    }
    
    // ==========================================================
    // 8. 相册分类 - 添加
    // ==========================================================
    if ($action === 'add_album') {
        checkAuth();
        
        $name = SQLite3::escapeString(trim($_POST['name'] ?? ''));
        $description = SQLite3::escapeString(trim($_POST['description'] ?? ''));
        
        if (empty($name)) {
            header('HTTP/1.1 303 See Other');
            header('Location: index.php?page=' . $returnPage . '&msg=相册名称不能为空&error=1');
            exit;
        }
        
        $db->exec("INSERT INTO photo_albums (name, description) VALUES ('$name', '$description')");
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=相册创建成功');
        exit;
    }
    
    // ==========================================================
    // 9. 相册分类 - 编辑
    // ==========================================================
    if ($action === 'edit_album') {
        checkAuth();
        
        $id = intval($_POST['id'] ?? 0);
        $name = SQLite3::escapeString(trim($_POST['name'] ?? ''));
        $description = SQLite3::escapeString(trim($_POST['description'] ?? ''));
        
        if ($id <= 0 || empty($name)) {
            header('HTTP/1.1 303 See Other');
            header('Location: index.php?page=' . $returnPage . '&msg=参数错误&error=1');
            exit;
        }
        
        $db->exec("UPDATE photo_albums SET name = '$name', description = '$description' WHERE id = $id");
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=相册已更新');
        exit;
    }
    
    // ==========================================================
    // 10. 相册分类 - 删除
    // ==========================================================
    if ($action === 'delete_album') {
        checkAuth();
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            header('HTTP/1.1 303 See Other');
            header('Location: index.php?page=' . $returnPage . '&msg=无效的相册ID&error=1');
            exit;
        }
        
        $photos = $db->query("SELECT image_path FROM clan_photos WHERE album_id = $id");
        while ($photo = $photos->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($photo['image_path']) && file_exists($photo['image_path'])) {
                unlink($photo['image_path']);
            }
        }
        $db->exec("DELETE FROM clan_photos WHERE album_id = $id");
        $db->exec("DELETE FROM photo_albums WHERE id = $id");
        
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $returnPage . '&msg=相册已删除');
        exit;
    }
    
    // ==========================================================
    // 11. 家族相册 - 添加照片（跳转回原相册）
    // ==========================================================
    if ($action === 'add_photo') {
        checkAuth();
        
        // 使用隐藏字段的 album_id，而不是下拉菜单的
        $album_id = intval($_POST['album_id'] ?? 0);
        $title = SQLite3::escapeString(trim($_POST['title'] ?? ''));
        $description = SQLite3::escapeString(trim($_POST['description'] ?? ''));
        $uploader = SQLite3::escapeString(getCurrentUser() ?: '');
        $image_path = '';
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $uploadDir = 'uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'photo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                    $image_path = $uploadDir . $filename;
                }
            }
        }
        
        if ($image_path) {
            $db->exec("INSERT INTO clan_photos (album_id, title, description, image_path, uploader) 
                       VALUES ($album_id, '$title', '$description', '$image_path', '$uploader')");
            
            // 跳转回当前相册
            $redirectPage = 'photos';
            if ($album_id > 0) {
                $redirectPage .= '&album=' . $album_id;
            }
            header('HTTP/1.1 303 See Other');
            header('Location: index.php?page=' . $redirectPage . '&msg=照片上传成功');
        } else {
            header('HTTP/1.1 303 See Other');
            header('Location: index.php?page=photos' . ($album_id > 0 ? '&album=' . $album_id : '') . '&msg=上传失败，请检查文件格式&error=1');
        }
        exit;
    }
    
    // ==========================================================
    // 12. 家族相册 - 删除照片
    // ==========================================================
    if ($action === 'delete_photo') {
        checkAuth();
        
        $id = intval($_POST['id'] ?? 0);
        $album_id = intval($_POST['album_id'] ?? 0);
        $photo = $db->querySingle("SELECT image_path FROM clan_photos WHERE id=$id", true);
        if ($photo && !empty($photo['image_path']) && file_exists($photo['image_path'])) {
            unlink($photo['image_path']);
        }
        $db->exec("DELETE FROM clan_photos WHERE id=$id");
        
        // 删除后跳转回原相册
        $redirectPage = 'photos';
        if ($album_id > 0) {
            $redirectPage .= '&album=' . $album_id;
        }
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $redirectPage . '&msg=照片已删除');
        exit;
    }
    
    // ==========================================================
    // 13. 家族相册 - 编辑照片信息
    // ==========================================================
    if ($action === 'edit_photo') {
        checkAuth();
        
        $id = intval($_POST['id'] ?? 0);
        $album_id = intval($_POST['album_id'] ?? 0);
        $title = SQLite3::escapeString(trim($_POST['title'] ?? ''));
        $description = SQLite3::escapeString(trim($_POST['description'] ?? ''));
        // 获取原相册ID用于跳转
        $oldPhoto = $db->querySingle("SELECT album_id FROM clan_photos WHERE id=$id", true);
        $oldAlbumId = $oldPhoto ? $oldPhoto['album_id'] : 0;
        
        if ($id <= 0) {
            header('HTTP/1.1 303 See Other');
            header('Location: index.php?page=' . $returnPage . '&msg=无效的照片ID&error=1');
            exit;
        }
        
        $sql = "UPDATE clan_photos SET 
                album_id = $album_id,
                title = '$title', 
                description = '$description' 
                WHERE id = $id";
        $result = $db->exec($sql);
        
        if ($result === false) {
            header('HTTP/1.1 303 See Other');
            header('Location: index.php?page=' . $returnPage . '&msg=更新失败：' . urlencode($db->lastErrorMsg()) . '&error=1');
            exit;
        }
        
        // 跳转回原相册（使用更新后的 album_id）
        $redirectPage = 'photos';
        if ($album_id > 0) {
            $redirectPage .= '&album=' . $album_id;
        } elseif ($oldAlbumId > 0) {
            $redirectPage .= '&album=' . $oldAlbumId;
        }
        header('HTTP/1.1 303 See Other');
        header('Location: index.php?page=' . $redirectPage . '&msg=照片信息已更新');
        exit;
    }
}

// ============================================================
// GET 请求处理（AJAX接口）
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    // ==========================================================
    // 1. 成员详情
    // ==========================================================
    if ($_GET['action'] === 'detail') {
        error_reporting(0);
        ini_set('display_errors', 0);
        if (function_exists('cleanOutputBuffer')) {
            cleanOutputBuffer();
        }
        
        $id = intval($_GET['id']);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if ($id <= 0) {
            echo json_encode(['error' => '无效的成员ID']);
            exit;
        }
        
        $member = getMember($id);
        if (!$member) {
            echo json_encode(['error' => '成员不存在']);
            exit;
        }
        
        try {
            $members = getAllMembers();
            
            // ★★★ 判断当前成员的父节点是谁 ★★★
            // 如果当前成员有 mother_id，且对应的成员是女性，则使用母节点模式
            // 否则使用父节点模式
            $isMotherNode = false;
            
            if ($member['mother_id']) {
                // 查找母亲
                $motherMember = null;
                foreach ($members as $m) {
                    if ($m['id'] == $member['mother_id']) {
                        $motherMember = $m;
                        break;
                    }
                }
                // 如果母亲存在且是女性，使用母节点模式
                if ($motherMember && $motherMember['gender'] == '女') {
                    $isMotherNode = true;
                }
            }
            
            // 如果没有 mother_id，但 father_id 对应的成员是女性（数据异常），也使用母节点模式
            if (!$isMotherNode && $member['father_id']) {
                $fatherMember = null;
                foreach ($members as $m) {
                    if ($m['id'] == $member['father_id']) {
                        $fatherMember = $m;
                        break;
                    }
                }
                if ($fatherMember && $fatherMember['gender'] == '女') {
                    $isMotherNode = true;
                }
            }
            
            // ★★★ 根据节点类型生成对应的下拉菜单 ★★★
            if ($isMotherNode) {
                // 母节点模式：母亲下拉菜单
                $parentSelect = generateHierarchicalSelect($members, $member['mother_id'] ?? 0, 'mother_id', '母亲', $id);
                $spouseLabel = '父亲姓名';
                $spouseValue = $member['father_name'] ?? '';
            } else {
                // 父节点模式：父亲下拉菜单
                $parentSelect = generateHierarchicalSelect($members, $member['father_id'] ?? 0, 'father_id', '父亲', $id);
                $spouseLabel = '母亲姓名';
                $spouseValue = $member['mother_name'] ?? '';
            }
            
            echo json_encode([
                'member' => $member,
                'parentSelect' => $parentSelect,
                'spouseLabel' => $spouseLabel,
                'spouseValue' => $spouseValue,
                'isMotherNode' => $isMotherNode
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // ============================================================
    // 支系图数据 - 修复 buildBranchData 函数
    // ============================================================
    if ($_GET['action'] === 'branch') {
        error_reporting(0);
        ini_set('display_errors', 0);
        if (function_exists('cleanOutputBuffer')) {
            cleanOutputBuffer();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        $id = intval($_GET['id']);
        if ($id <= 0) {
            echo json_encode(['error' => '无效的成员ID']);
            exit;
        }
        
        try {
            $members = getAllMembers();
            
            // ★★★ 调试：检查成员是否存在 ★★★
            $rootMember = null;
            foreach ($members as $m) {
                if ($m['id'] == $id) {
                    $rootMember = $m;
                    break;
                }
            }
            
            if (!$rootMember) {
                echo json_encode(['error' => '成员不存在']);
                exit;
            }
            
            // ★★★ 构建完整的支系树 ★★★
            $branchTree = buildBranchTree($id, $members);
            
            if (!$branchTree) {
                echo json_encode(['error' => '无法构建支系图']);
                exit;
            }
            
            // ★★★ 构建D3树形数据（递归）★★★
            function buildBranchData($node, $members) {
                $memberData = null;
                foreach ($members as $m) {
                    if ($m['id'] == $node['id']) { 
                        $memberData = $m; 
                        break; 
                    }
                }
                
                $item = [
                    'id' => $node['id'],
                    'name' => $node['name'],
                    'rank' => $memberData ? ($memberData['rank'] ?? '') : '',
                    'birth' => $memberData ? ($memberData['birth_date'] ?? '') : '',
                    'death' => $memberData ? ($memberData['death_date'] ?? '') : '',
                    'generation' => $memberData ? ($memberData['generation'] ?? 1) : 1,
                    'gender' => $memberData ? ($memberData['gender'] ?? '') : '',
                    'children' => []
                ];
                
                // ★★★ 递归处理子节点 ★★★
                if (!empty($node['children'])) {
                    foreach ($node['children'] as $child) {
                        $item['children'][] = buildBranchData($child, $members);
                    }
                }
                
                return $item;
            }
            
            $branchData = buildBranchData($branchTree, $members);
            
            // ★★★ 调试日志 ★★★
            error_log("支系图数据: " . json_encode($branchData));
            
            echo json_encode([
                'branchData' => [$branchData],
                'name' => $branchTree['name']
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
        }
        exit;
    }
}

// 如果没有匹配任何操作，返回404
http_response_code(404);
echo '404 Not Found';
?>