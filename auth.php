<?php
// 文件: auth.php - 登录/注册/个人信息处理
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $db = getDB();
    $returnPage = $_POST['return_page'] ?? 'home';
    
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (loginUser($username, $password)) {
            header('Location: index.php?page=' . $returnPage . '&msg=登录成功');
        } else {
            header('Location: index.php?page=' . $returnPage . '&msg=用户名或密码错误&error=1');
        }
        exit;
    }
    
    if ($action === 'register') {
        $username = SQLite3::escapeString($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $real_name = SQLite3::escapeString($_POST['real_name'] ?? '');
        $member_id = intval($_POST['member_id'] ?? 0);
        
        if ($password !== $confirm) {
            header('Location: index.php?page=' . $returnPage . '&msg=两次密码不一致&error=1');
            exit;
        }
        if (strlen($password) < 6) {
            header('Location: index.php?page=' . $returnPage . '&msg=密码至少6位&error=1');
            exit;
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        try {
            $db->exec("INSERT INTO users (username, password, real_name, member_id) VALUES ('$username', '$hashed', '$real_name', $member_id)");
            header('Location: index.php?page=' . $returnPage . '&msg=注册成功，请登录');
        } catch (Exception $e) {
            header('Location: index.php?page=' . $returnPage . '&msg=用户名已存在&error=1');
        }
        exit;
    }
    
    if ($action === 'update_profile') {
        if (!isLoggedIn()) {
            header('Location: index.php?page=' . $returnPage . '&msg=请先登录&error=1');
            exit;
        }
        $user_id = getCurrentUserId();
        $real_name = SQLite3::escapeString($_POST['real_name'] ?? '');
        $member_id = intval($_POST['member_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        
        if (!empty($password)) {
            if (strlen($password) < 6) {
                header('Location: index.php?page=' . $returnPage . '&msg=密码至少6位&error=1');
                exit;
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $db->exec("UPDATE users SET real_name='$real_name', member_id=$member_id, password='$hashed' WHERE id=$user_id");
        } else {
            $db->exec("UPDATE users SET real_name='$real_name', member_id=$member_id WHERE id=$user_id");
        }
        $_SESSION['real_name'] = $real_name;
        $_SESSION['member_id'] = $member_id;
        header('Location: index.php?page=' . $returnPage . '&msg=个人信息已更新');
        exit;
    }
    
    if ($action === 'logout') {
        logoutUser();
        header('Location: index.php?page=' . $returnPage . '&msg=已退出登录');
        exit;
    }
}
?>