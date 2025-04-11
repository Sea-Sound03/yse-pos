<?php
require_once 'config/config.php';

$pageTitle = 'ホーム';

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h1 class="text-3xl font-bold mb-4">YSEレジシステムへようこそ</h1>
        <p class="mb-4">このシステムはYSEレジシステムの基本機能を提供します。</p>
        
        <?php if (is_logged_in()): ?>
            <p>こんにちは、<strong><?= htmlspecialchars($_SESSION['display_name']) ?></strong>さん！</p>
        <?php else: ?>
            <p>機能を利用するには<a href="./login.php" class="text-blue-500 hover:underline">ログイン</a>してください。</p>
        <?php endif; ?>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- POSカード -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-blue-500 p-4">
                <h2 class="text-xl font-bold text-white">レジ操作</h2>
            </div>
            <div class="p-6">
                <div class="text-gray-600 mb-4">
                    商品の登録、会計処理を行うことができます。
                </div>
                <a href="/views/pos/index.php" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    レジを開く
                </a>
            </div>
        </div>
        
        <!-- 売上管理カード -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-green-500 p-4">
                <h2 class="text-xl font-bold text-white">売上管理</h2>
            </div>
            <div class="p-6">
                <div class="text-gray-600 mb-4">
                    売上データの確認、レポート出力を行えます。
                </div>
                <a href="/sales/index.php" class="inline-block bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    売上を確認
                </a>
            </div>
        </div>
        
        <!-- 商品管理カード -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-purple-500 p-4">
                <h2 class="text-xl font-bold text-white">商品管理</h2>
            </div>
            <div class="p-6">
                <div class="text-gray-600 mb-4">
                    商品の登録、編集、カテゴリ管理ができます。
                </div>
                <a href="/products/index.php" class="inline-block bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                    商品を管理
                </a>
            </div>
        </div>
        
        <!-- ユーザー管理カード -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-red-500 p-4">
                <h2 class="text-xl font-bold text-white">ユーザー管理</h2>
            </div>
            <div class="p-6">
                <div class="text-gray-600 mb-4">
                    ユーザーの追加、編集、権限設定ができます。
                </div>
                <a href="/users/index.php" class="inline-block bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    ユーザーを管理
                </a>
            </div>
        </div>
        
        <!-- 設定カード -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gray-500 p-4">
                <h2 class="text-xl font-bold text-white">システム設定</h2>
            </div>
            <div class="p-6">
                <div class="text-gray-600 mb-4">
                    税率設定、レシート設定など各種設定を行えます。
                </div>
                <a href="/settings/index.php" class="inline-block bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    設定を変更
                </a>
            </div>
        </div>
        
        <!-- ヘルプカード -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-yellow-500 p-4">
                <h2 class="text-xl font-bold text-white">ヘルプ</h2>
            </div>
            <div class="p-6">
                <div class="text-gray-600 mb-4">
                    システムの使い方や操作方法について確認できます。
                </div>
                <a href="#" class="inline-block bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                    ヘルプを表示
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>