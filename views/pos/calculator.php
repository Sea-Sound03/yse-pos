
<div class="calculator-container" x-data="calculatorApp()">
    <div id="alert-container" class="mb-3"></div>
    
    <div class="display-container mb-4">
        <input type="text" id="calculator-display" x-model="displayValue" readonly class="w-full p-4 text-right text-2xl border rounded">
    </div>
    
    <div class="calculator-keys grid grid-cols-4 gap-2">
        <!-- 1行目 -->
        <button class="all-clear col-span-2 bg-red-500 text-white p-3 rounded" @click="clearAll">AC</button>
        <button class="tax bg-blue-500 text-white p-3 rounded" @click="calculateTax">税込</button>
        <button class="operator bg-gray-300 p-3 rounded" @click="handleOperator('/')">/</button>
        
        <!-- 2行目 -->
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('7')">7</button>
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('8')">8</button>
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('9')">9</button>
        <button class="operator bg-gray-300 p-3 rounded" @click="handleOperator('*')">×</button>
        
        <!-- 3行目 -->
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('4')">4</button>
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('5')">5</button>
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('6')">6</button>
        <button class="operator bg-gray-300 p-3 rounded" @click="handleOperator('-')">-</button>
        
        <!-- 4行目 -->
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('1')">1</button>
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('2')">2</button>
        <button class="p-3 bg-gray-200 rounded" @click="inputDigit('3')">3</button>
        <button class="operator bg-gray-300 p-3 rounded" @click="handleOperator('+')">+</button>
        
        <!-- 5行目 -->
        <button class="p-3 bg-gray-200 rounded col-span-2" @click="inputDigit('0')">0</button>
        <button class="decimal p-3 bg-gray-200 rounded" @click="inputDecimal">.</button>
        <button class="equals bg-blue-500 text-white p-3 rounded" @click="handleEquals">=</button>
    </div>
    
    <div class="action-buttons mt-4 grid grid-cols-2 gap-2">
        <button id="btn-add-item" class="add-sale bg-green-500 text-white p-3 rounded" @click="addItem">商品追加</button>
        <button id="btn-submit-sale" class="bg-purple-500 text-white p-3 rounded" @click="submitSale">計上</button>
    </div>
    
    <div class="current-sale mt-4">
        <h3 class="text-lg font-bold mb-2">現在の会計</h3>
        <ul id="sale-items" class="border rounded p-2 min-h-24">
            <template x-if="items.length === 0">
                <li class="text-gray-500 text-center py-2">商品がありません</li>
            </template>
            <template x-for="(item, index) in items" :key="index">
                <li class="py-1 px-2 flex justify-between items-center border-b last:border-b-0">
                    <div>
                        <span x-text="item.name || '商品'"></span>
                        <span class="text-sm text-gray-500 ml-2" x-text="formatCurrency(item.amount)"></span>
                    </div>
                    <button class="text-red-500 hover:text-red-700" @click="removeItem(index)">
                        <i class="fas fa-times"></i>
                    </button>
                </li>
            </template>
        </ul>
        <div class="total-container mt-2 text-right">
            <span class="font-bold">合計:</span>
            <span id="total-amount" x-text="formatCurrency(calculateTotal())"></span>
        </div>
    </div>
</div>

<script>
function calculatorApp() {
    return {
        displayValue: '0',
        firstOperand: null,
        waitingForSecondOperand: false,
        operator: null,
        items: [],
        
        // 数字入力処理
        inputDigit(digit) {
            if (this.waitingForSecondOperand === true) {
                this.displayValue = digit;
                this.waitingForSecondOperand = false;
            } else {
                this.displayValue = this.displayValue === '0' ? digit : this.displayValue + digit;
            }
        },
        
        // 小数点入力処理
        inputDecimal() {
            if (this.waitingForSecondOperand) return;
            if (!this.displayValue.includes('.')) {
                this.displayValue += '.';
            }
        },
        
        // 演算子処理
        handleOperator(nextOperator) {
            const inputValue = parseFloat(this.displayValue);
            
            if (this.firstOperand === null) {
                this.firstOperand = inputValue;
            } else if (this.operator) {
                const result = this.calculate(this.firstOperand, inputValue, this.operator);
                this.displayValue = String(result);
                this.firstOperand = result;
            }
            
            this.waitingForSecondOperand = true;
            this.operator = nextOperator;
        },
        
        // イコール処理
        handleEquals() {
            if (!this.operator || this.waitingForSecondOperand) {
                return;
            }
            
            const inputValue = parseFloat(this.displayValue);
            const result = this.calculate(this.firstOperand, inputValue, this.operator);
            
            this.displayValue = String(result);
            this.firstOperand = result;
            this.operator = null;
            this.waitingForSecondOperand = true;
        },
        
        // 計算実行
        calculate(firstOperand, secondOperand, operator) {
            if (operator === '+') {
                return firstOperand + secondOperand;
            } else if (operator === '-') {
                return firstOperand - secondOperand;
            } else if (operator === '*') {
                return firstOperand * secondOperand;
            } else if (operator === '/') {
                return firstOperand / secondOperand;
            }
            
            return secondOperand;
        },
        
        // 税込み計算（10%）
        calculateTax() {
            const amount = parseFloat(this.displayValue);
            const taxAmount = amount * 0.1;
            this.displayValue = String(amount + taxAmount);
        },
        
        // クリア処理
        clearAll() {
            this.displayValue = '0';
            this.firstOperand = null;
            this.waitingForSecondOperand = false;
            this.operator = null;
        },
        
        // 商品追加
        addItem() {
            if (this.displayValue === '0') {
                return;
            }
            
            this.items.push({
                name: '商品',
                amount: parseFloat(this.displayValue),
                timestamp: new Date()
            });
            
            this.clearAll();
        },
        
        // 商品削除
        removeItem(index) {
            this.items.splice(index, 1);
        },
        
        // 合計計算
        calculateTotal() {
            return this.items.reduce((total, item) => total + item.amount, 0);
        },
        
        // 金額フォーマット
        formatCurrency(amount) {
            return '¥' + parseFloat(amount).toLocaleString('ja-JP', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },
        
        // 売上計上
        submitSale() {
            if (this.items.length === 0) {
                this.showAlert('計上する商品がありません', 'warning');
                return;
            }
            
            const totalAmount = this.calculateTotal();
            
            fetch('/controllers/SaleController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'addSale',
                    items: this.items.map(item => ({
                        name: item.name,
                        price: item.amount,
                        quantity: 1
                    })),
                    totalAmount: totalAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showAlert('売上を計上しました', 'success');
                    this.items = [];
                } else {
                    this.showAlert('エラーが発生しました: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('通信エラーが発生しました', 'error');
            });
        },
        
        // アラート表示
        showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} mb-2 p-2 rounded ${type === 'success' ? 'bg-green-100 text-green-800' : type === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}`;
            alert.textContent = message;
            alertContainer.appendChild(alert);
            
            // 3秒後に自動的に消える
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
    };
}
</script>