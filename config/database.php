
<?php
/**
 * データベース接続クラス
 */
class Database {
    // データベース接続パラメータ
    private $host = 'localhost';
    private $db_name = 'yse_pos';
    private $username = 'root';  // 本番環境では適切なユーザー名に変更
    private $password = '';      // 本番環境では適切なパスワードに設定
    private $conn;
    
    // データベース接続を取得
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }
        
        return $this->conn;
    }
}