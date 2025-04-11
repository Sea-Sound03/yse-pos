<?php
/**
 * ユーザーモデルクラス - パスワードハッシュ化なしバージョン
 * 
 * 注意：このバージョンはテスト・開発環境専用です
 * 本番環境では必ずパスワードをハッシュ化してください
 */
class User {
    private $conn;
    private $table = 'users';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // ユーザー登録 (ハッシュ化なし)
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                (username, password, display_name, role) 
                VALUES (:username, :password, :display_name, :role)";
        
        $stmt = $this->conn->prepare($query);
        
        // パスワードをそのまま保存 (ハッシュ化なし)
        $plain_password = $data['password'];
        
        // データのバインド
        $stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindParam(':password', $plain_password, PDO::PARAM_STR);
        $stmt->bindParam(':display_name', $data['display_name'], PDO::PARAM_STR);
        $stmt->bindParam(':role', $data['role'], PDO::PARAM_STR);
        
        // クエリ実行
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // ユーザー認証 (ハッシュ化なし)
    public function authenticate($username, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        // プレーンテキストのパスワード比較
        if ($user && $password === $user['password']) {
            // 最終ログイン時間更新
            $this->updateLastLogin($user['id']);
            
            // アクティビティログ記録
            try {
                require_once 'ActivityLog.php';
                $log = new ActivityLog($this->conn);
                $log->create([
                    'user_id' => $user['id'],
                    'action' => 'login',
                    'description' => "ユーザー {$username} がログインしました。"
                ]);
            } catch (Exception $e) {
                // ActivityLogクラスが存在しない場合も続行する
            }
            
            return $user;
        }
        
        return false;
    }
    
    // 最終ログイン時間更新
    private function updateLastLogin($id) {
        $query = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // 全ユーザー取得
    public function getAll() {
        $query = "SELECT id, username, display_name, role, created_at, last_login FROM " . $this->table . " ORDER BY id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 指定IDのユーザー取得
    public function getById($id) {
        $query = "SELECT id, username, display_name, role, created_at, last_login FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ユーザー更新 (ハッシュ化なし)
    public function update($id, $data) {
        $updateFields = [];
        $params = [];
        
        // 更新可能なフィールド
        $allowedFields = ['display_name', 'role'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        // パスワード更新がある場合
        if (!empty($data['password'])) {
            $updateFields[] = "password = :password";
            $params[':password'] = $data['password']; // ハッシュ化せずにパスワードを更新
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $params[':id'] = $id;
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    // ユーザー削除
    public function delete($id) {
        // 関連データを確認
        $query = "SELECT COUNT(*) as count FROM sales WHERE user_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            // 売上データが存在する場合は削除せず、売上のユーザーIDをNULLに設定
            $query = "UPDATE sales SET user_id = NULL WHERE user_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // ユーザー削除
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // ユーザー名による存在チェック
    public function usernameExists($username, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE username = :username";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    // パスワード変更
    public function changePassword($id, $newPassword) {
        $query = "UPDATE " . $this->table . " SET password = :password WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $newPassword, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // 権限別ユーザー数取得
    public function countByRole() {
        $query = "SELECT role, COUNT(*) as count FROM " . $this->table . " GROUP BY role";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['role']] = $row['count'];
        }
        
        return $result;
    }
    
    // アクティブでないユーザーを取得 (最終ログインから30日以上経過)
    public function getInactiveUsers($days = 30) {
        $query = "SELECT id, username, display_name, role, created_at, last_login 
                FROM " . $this->table . " 
                WHERE last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}