// ===== models/Sale.php =====

<?php
class Sale {
    private $conn;
    private $table = 'sales';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // 売上データの登録
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                (amount, tax_amount, total_amount, items_count, user_id, payment_method) 
                VALUES (:amount, :tax_amount, :total_amount, :items_count, :user_id, :payment_method)";
        
        $stmt = $this->conn->prepare($query);
        
        // データのバインド
        $stmt->bindParam(':amount', $data['amount'], PDO::PARAM_STR);
        $stmt->bindParam(':tax_amount', $data['tax_amount'], PDO::PARAM_STR);
        $stmt->bindParam(':total_amount', $data['total_amount'], PDO::PARAM_STR);
        $stmt->bindParam(':items_count', $data['items_count'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':payment_method', $data['payment_method'] ?? 'cash', PDO::PARAM_STR);
        
        // クエリ実行
        if ($stmt->execute()) {
            $sale_id = $this->conn->lastInsertId();
            
            // 売上詳細がある場合は保存
            if (!empty($data['items']) && is_array($data['items'])) {
                require_once 'SaleItem.php';
                $saleItem = new SaleItem($this->conn);
                
                foreach ($data['items'] as $item) {
                    $itemData = [
                        'sale_id' => $sale_id,
                        'product_id' => $item['product_id'] ?? null,
                        'item_name' => $item['name'] ?? '商品',
                        'price' => $item['price'],
                        'quantity' => $item['quantity'] ?? 1,
                        'tax_rate' => $item['tax_rate'] ?? 10,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'subtotal' => $item['subtotal'] ?? $item['price']
                    ];
                    
                    $saleItem->create($itemData);
                }
            }
            
            // アクティビティログ記録
            if (isset($_SESSION['user_id'])) {
                require_once 'ActivityLog.php';
                $log = new ActivityLog($this->conn);
                $log->create([
                    'user_id' => $_SESSION['user_id'],
                    'action' => 'create_sale',
                    'description' => "売上ID: {$sale_id} を登録しました。合計: {$data['total_amount']}円"
                ]);
            }
            
            return $sale_id;
        }
        
        return false;
    }
    
    // 全売上データの取得
    public function getAll($limit = null) {
        $query = "SELECT s.*, u.username, u.display_name
                FROM " . $this->table . " s
                LEFT JOIN users u ON s.user_id = u.id
                ORDER BY s.sale_date DESC";
                
        if ($limit) {
            $query .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // 指定IDの売上取得
    public function getById($id) {
        $query = "SELECT s.*, u.username, u.display_name
                FROM " . $this->table . " s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $sale = $stmt->fetch();
        
        if ($sale) {
            // 売上明細を取得
            require_once 'SaleItem.php';
            $saleItem = new SaleItem($this->conn);
            $sale['items'] = $saleItem->getBySaleId($id);
        }
        
        return $sale;
    }
    
    // 日付範囲による売上データの取得
    public function getByDateRange($startDate, $endDate) {
        $query = "SELECT s.*, u.username, u.display_name
                FROM " . $this->table . " s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.sale_date BETWEEN :start_date AND :end_date
                ORDER BY s.sale_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // 売上集計（日別）
    public function getDailySummary($startDate, $endDate) {
        $query = "SELECT 
                DATE(sale_date) as date,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount,
                SUM(tax_amount) as total_tax,
                SUM(total_amount) as total_sales
                FROM " . $this->table . "
                WHERE sale_date BETWEEN :start_date AND :end_date
                GROUP BY DATE(sale_date)
                ORDER BY DATE(sale_date)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // 売上集計（月別）
    public function getMonthlySummary($year) {
        $query = "SELECT 
                MONTH(sale_date) as month,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount,
                SUM(tax_amount) as total_tax,
                SUM(total_amount) as total_sales
                FROM " . $this->table . "
                WHERE YEAR(sale_date) = :year
                GROUP BY MONTH(sale_date)
                ORDER BY MONTH(sale_date)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // 本日の売上データを取得
    public function getTodaySales() {
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        return $this->getByDateRange($today, $tomorrow);
    }
    
    // 売上の更新（ステータス変更など）
    public function update($id, $data) {
        $updateFields = [];
        $params = [];
        
        // 更新可能なフィールド
        $allowedFields = ['status', 'payment_method', 'amount', 'tax_amount', 'total_amount'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
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
}