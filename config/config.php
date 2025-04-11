
<?php
/**
 * アプリケーション設定ファイル
 */

// エラー表示設定（開発環境ではON、本番環境ではOFFに設定）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// アプリケーション基本設定
define('APP_NAME', 'YSEレジシステム');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));
define('BASE_URL', '/');

// セッション設定
session_start();

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// 設定情報取得関数
function get_setting($key, $default = null) {
    try {
        require_once BASE_PATH . '/config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['setting_value'];
        }
    } catch (PDOException $e) {
        // データベースエラーの場合はデフォルト値を返す
        error_log('データベースエラー: ' . $e->getMessage());
    }
    
    return $default;
}

// 税率取得関数
function get_tax_rate($is_reduced = false) {
    $key = $is_reduced ? 'reduced_tax_rate' : 'tax_rate';
    $rate = get_setting($key);
    return $rate ? (float)$rate : ($is_reduced ? 8.0 : 10.0);
}

// 認証関連の関数
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit;
    }
}

function has_permission($required_role) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? '';
    
    // 権限階層
    $roles = [
        'cashier' => 1,
        'manager' => 2,
        'admin' => 3
    ];
    
    $user_level = $roles[$user_role] ?? 0;
    $required_level = $roles[$required_role] ?? 999; // 未定義の権限は高いレベルとして扱う
    
    return $user_level >= $required_level;
}

function require_permission($required_role) {
    if (!has_permission($required_role)) {
        $_SESSION['error'] = '権限がありません。';
        header('Location: /');
        exit;
    }
}