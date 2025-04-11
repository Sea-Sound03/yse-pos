// ===== controllers/SaleController.php =====

<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Sale.php';

class SaleController {
    private $db;
    private $sale;
    
    public function __construct() {
        $this->db = new Database();
        $this->sale = new Sale($this->db->getConnection());
    }
    
    // 受信したリクエストを処理
    public function handleRequest() {
        header('Content-Type: application/json');
        
        // POSTリクエストのボディを取得
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid request data']);
            return;
        }
        
        // アクションによって処理を振り分け
        switch ($data['action']) {
            case 'addSale':
                $this->addSale($data);
                break;
            case 'getSales':
                $this->getSales();
                break;
            case 'getSaleById':
                $this->getSaleById($data['id'] ?? 0);
                break;
            case 'getTodaySales':
                $this->getTodaySales();
                break;
            case 'getDailySummary':
                $this->getDailySummary($data['startDate'] ?? null, $data['endDate'] ?? null);
                break;
            case 'getMonthlySummary':
                $this->getMonthlySummary($data['year'] ?? date('Y'));
                break;
            case 'updateSaleStatus':
                $this->updateSaleStatus($data['id'] ?? 0, $data['status'] ?? '');
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    // 売上データの登録処理
    private function addSale($data) {
        try {
            // リクエストの検証
            if (empty($data['items']) || !is_array($data['items'])) {
                echo json_encode(['success' => false, 'message' => '商品情報が無効です']);
                return;
            }
            
            $items = $data['items'];
            $totalAmount = $data['totalAmount'] ?? 0;
            $itemsCount = count($items);
            
            // 税抜き金額と消費税額を計算
            $taxRate = get_tax_rate() / 100; // config.phpから取得
            $taxExcludedAmount = $totalAmount / (1 + $taxRate);
            $taxAmount = $totalAmount - $taxExcludedAmount;
            
            // 現在のユーザーID（認証システムからセッションで取得）
            $userId = $_SESSION['user_id'] ?? 1;
            
            // 支払い方法
            $paymentMethod = $data['paymentMethod'] ?? 'cash';
            
            // 売上データを登録
            $saleId = $this->sale->create([
                'amount' => $taxExcludedAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'items_count' => $itemsCount,
                'user_id' => $userId,
                'payment_method' => $paymentMethod,
                'items' => $items
            ]);
            
            if ($saleId) {
                echo json_encode([
                    'success' => true, 
                    'saleId' => $saleId,
                    'message' => '売上を登録しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '売上の登録に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 売上データの取得処理
    private function getSales() {
        try {
            $sales = $this->sale->getAll();
            echo json_encode(['success' => true, 'sales' => $sales]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 指定IDの売上詳細取得
    private function getSaleById($id) {
        try {
            if (!$id) {
                echo json_encode(['success' => false, 'message' => '売上IDが指定されていません']);
                return;
            }
            
            $sale = $this->sale->getById($id);
            
            if ($sale) {
                echo json_encode(['success' => true, 'sale' => $sale]);
            } else {
                echo json_encode(['success' => false, 'message' => '指定された売上データが見つかりません']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 本日の売上取得
    private function getTodaySales() {
        try {
            $sales = $this->sale->getTodaySales();
            
            // 合計値を計算
            $totalSales = 0;
            $transactionCount = count($sales);
            
            foreach ($sales as $sale) {
                $totalSales += (float)$sale['total_amount'];
            }
            
            // 平均取引額
            $averageTransaction = $transactionCount > 0 ? $totalSales / $transactionCount : 0;
            
            echo json_encode([
                'success' => true,
                'sales' => $sales,
                'summary' => [
                    'totalSales' => $totalSales,
                    'transactionCount' => $transactionCount,
                    'averageTransaction' => $averageTransaction
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 日別集計取得
    private function getDailySummary($startDate, $endDate) {
        try {
            // 日付が指定されていない場合は今月の範囲を使用
            if (!$startDate || !$endDate) {
                $startDate = date('Y-m-01'); // 今月の1日
                $endDate = date('Y-m-t'); // 今月の末日
            }
            
            $summary = $this->sale->getDailySummary($startDate, $endDate);
            echo json_encode(['success' => true, 'summary' => $summary]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 月別集計取得
    private function getMonthlySummary($year) {
        try {
            $summary = $this->sale->getMonthlySummary($year);
            echo json_encode(['success' => true, 'summary' => $summary]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 売上ステータス更新
    private function updateSaleStatus($id, $status) {
        try {
            if (!$id) {
                echo json_encode(['success' => false, 'message' => '売上IDが指定されていません']);
                return;
            }
            
            // ステータスの検証
            $validStatuses = ['completed', 'refunded', 'voided'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => '無効なステータスです']);
                return;
            }
            
            $updated = $this->sale->update($id, ['status' => $status]);
            
            if ($updated) {
                echo json_encode([
                    'success' => true,
                    'message' => '売上ステータスを更新しました'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => '更新に失敗しました']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// コントローラーのインスタンス化と実行
$controller = new SaleController();
$controller->handleRequest();