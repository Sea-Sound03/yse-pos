<?php
/**
 * 共通ユーティリティ関数
 */

// 金額を通貨形式でフォーマット
function format_currency($amount) {
    return '¥' . number_format($amount);
}

// 日付をフォーマット
function format_date($date, $format = 'Y-m-d') {
    $datetime = new DateTime($date);
    return $datetime->format($format);
}

// 日本語の曜日を取得
function get_day_of_week($date) {
    $weekday = ['日', '月', '火', '水', '木', '金', '土'];
    $datetime = new DateTime($date);
    return $weekday[$datetime->format('w')];
}

// 消費税計算
function calculate_tax($amount, $taxRate = null) {
    if ($taxRate === null) {
        $taxRate = get_tax_rate() / 100;
    } else {
        $taxRate = $taxRate / 100;
    }
    
    return $amount * $taxRate;
}

// 税込み金額計算
function calculate_total_with_tax($amount, $taxRate = null) {
    if ($taxRate === null) {
        $taxRate = get_tax_rate() / 100;
    } else {
        $taxRate = $taxRate / 100;
    }
    
    return $amount * (1 + $taxRate);
}

// HTMLエスケープ
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// CSRFトークン生成
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRFトークン検証
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// ページネーション関数
function paginate($items, $perPage = 10, $page = 1) {
    $totalItems = count($items);
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages));
    
    $offset = ($page - 1) * $perPage;
    $paginatedItems = array_slice($items, $offset, $perPage);
    
    return [
        'items' => $paginatedItems,
        'currentPage' => $page,
        'perPage' => $perPage,
        'totalItems' => $totalItems,
        'totalPages' => $totalPages
    ];
}

// ページネーションリンク生成
function pagination_links($pagination, $baseUrl = '?', $queryParams = []) {
    $html = '<div class="flex justify-center">';
    $html .= '<nav class="inline-flex rounded-md shadow">';
    
    // 前のページへのリンク
    if ($pagination['currentPage'] > 1) {
        $prevUrl = build_pagination_url($baseUrl, $pagination['currentPage'] - 1, $queryParams);
        $html .= '<a href="' . $prevUrl . '" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 rounded-l-md">前へ</a>';
    } else {
        $html .= '<span class="px-4 py-2 border border-gray-300 bg-gray-100 text-gray-400 rounded-l-md">前へ</span>';
    }
    
    // ページ番号
    for ($i = 1; $i <= $pagination['totalPages']; $i++) {
        if ($i == $pagination['currentPage']) {
            $html .= '<span class="px-4 py-2 border border-blue-500 bg-blue-500 text-white">' . $i . '</span>';
        } else {
            $url = build_pagination_url($baseUrl, $i, $queryParams);
            $html .= '<a href="' . $url . '" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">' . $i . '</a>';
        }
    }
    
    // 次のページへのリンク
    if ($pagination['currentPage'] < $pagination['totalPages']) {
        $nextUrl = build_pagination_url($baseUrl, $pagination['currentPage'] + 1, $queryParams);
        $html .= '<a href="' . $nextUrl . '" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 rounded-r-md">次へ</a>';
    } else {
        $html .= '<span class="px-4 py-2 border border-gray-300 bg-gray-100 text-gray-400 rounded-r-md">次へ</span>';
    }
    
    $html .= '</nav>';
    $html .= '</div>';
    
    return $html;
}

// ページネーション用URL生成
function build_pagination_url($baseUrl, $page, $queryParams = []) {
    $params = array_merge($queryParams, ['page' => $page]);
    $query = http_build_query($params);
    
    // ベースURLにクエリ文字列がすでに含まれているかチェック
    if (strpos($baseUrl, '?') !== false) {
        return $baseUrl . '&' . $query;
    } else {
        return $baseUrl . '?' . $query;
    }
}

// ログアウト処理
function logout() {
    // セッション変数をクリア
    $_SESSION = [];
    
    // セッションクッキーの削除
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // セッション破棄
    session_destroy();
}

// アクティビティログの記録
function log_activity($action, $description) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        // ActivityLogクラスがなければ何もしない
        if (!file_exists(dirname(__FILE__) . '/../models/ActivityLog.php')) {
            return false;
        }
        
        require_once dirname(__FILE__) . '/../config/database.php';
        require_once dirname(__FILE__) . '/../models/ActivityLog.php';
        
        $db = new Database();
        $log = new ActivityLog($db->getConnection());
        
        return $log->create([
            'user_id' => $_SESSION['user_id'],
            'action' => $action,
            'description' => $description
        ]);
    } catch (Exception $e) {
        error_log('アクティビティログ記録エラー: ' . $e->getMessage());
        return false;
    }
}

// IPアドレス取得
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// ユーザーエージェント取得
function get_user_agent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

// CSVダウンロード用ヘッダー設定
function set_csv_headers($filename) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// 権限レベルを数値で取得
function get_role_level($role) {
    $roles = [
        'cashier' => 1,
        'manager' => 2,
        'admin' => 3
    ];
    
    return $roles[$role] ?? 0;
}

// 権限名を日本語に変換
function get_role_name($role) {
    $roleNames = [
        'cashier' => 'レジ担当',
        'manager' => '管理者',
        'admin' => '上級管理者'
    ];
    
    return $roleNames[$role] ?? '不明';
}

// 設定情報取得関数
function get_setting($key, $default = null) {
    try {
        require_once dirname(__FILE__) . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        
        $result = $stmt->fetch();
        if ($result) {
            return $result['setting_value'];
        }
    } catch (Exception $e) {
        // データベースエラーの場合はデフォルト値を返す
        error_log('データベースエラー: ' . $e->getMessage());
    }
    
    return $default;
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

// 税率取得関数
function get_tax_rate($is_reduced = false) {
    $key = $is_reduced ? 'reduced_tax_rate' : 'tax_rate';
    $rate = get_setting($key);
    return $rate ? (float)$rate : ($is_reduced ? 8.0 : 10.0);
}