
<?php
$currentUser = isset($_SESSION['user_id']) ? [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'display_name' => $_SESSION['display_name'] ?? $_SESSION['username'],
    'role' => $_SESSION['user_role'] ?? 'cashier'
] : null;

$navItems = [
    [
        'name' => 'ホーム',
        'url' => '/',
        'icon' => 'fa-home',
        'requiresLogin' => false
    ],
    [
        'name' => 'レジ',
        'url' => '/pos/index.php',
        'icon' => 'fa-cash-register',
        'requiresLogin' => true
    ],
    [
        'name' => '売上管理',
        'url' => '/sales/index.php',
        'icon' => 'fa-chart-line',
        'requiresLogin' => true,
        'requiredRole' => 'manager'
    ],
    [
        'name' => '商品管理',
        'url' => '/products/index.php',
        'icon' => 'fa-box',
        'requiresLogin' => true,
        'requiredRole' => 'manager'
    ],
    [
        'name' => 'ユーザー管理',
        'url' => '/users/index.php',
        'icon' => 'fa-users',
        'requiresLogin' => true,
        'requiredRole' => 'admin'
    ],
    [
        'name' => '設定',
        'url' => '/settings/index.php',
        'icon' => 'fa-cog',
        'requiresLogin' => true,
        'requiredRole' => 'admin'
    ]
];
?>

<nav class="bg-gray-800 text-white shadow-lg" x-data="{ open: false }">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-3">
            <!-- ロゴ -->
            <div class="flex items-center">
                <a href="/" class="text-xl font-bold"><?php echo htmlspecialchars(get_setting('site_name', APP_NAME)); ?></a>
            </div>
            
            <!-- デスクトップメニュー -->
            <div class="hidden md:flex space-x-4">
                <?php foreach ($navItems as $item): ?>
                    <?php 
                    $showItem = !$item['requiresLogin'] || 
                                ($currentUser && (!isset($item['requiredRole']) || has_permission($item['requiredRole'])));
                    ?>
                    <?php if ($showItem): ?>
                        <a href="<?php echo $item['url']; ?>" class="px-3 py-2 rounded hover:bg-gray-700">
                            <i class="fas <?php echo $item['icon']; ?> mr-1"></i>
                            <?php echo $item['name']; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- ユーザーメニュー -->
            <div class="hidden md:flex items-center">
                <?php if ($currentUser): ?>
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center text-sm focus:outline-none">
                            <span class="mr-2"><?php echo htmlspecialchars($currentUser['display_name']); ?></span>
                            <i class="fas fa-user-circle text-xl"></i>
                        </button>
                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                            <a href="/users/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">プロフィール</a>
                            <a href="/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">ログアウト</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="px-3 py-2 rounded hover:bg-gray-700">
                        <i class="fas fa-sign-in-alt mr-1"></i>
                        ログイン
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- モバイルメニューボタン -->
            <div class="md:hidden flex items-center">
                <button @click="open = !open" class="focus:outline-none">
                    <i class="fas" :class="open ? 'fa-times' : 'fa-bars'"></i>
                </button>
            </div>
        </div>
        
        <!-- モバイルメニュー -->
        <div x-show="open" class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <?php foreach ($navItems as $item): ?>
                    <?php 
                    $showItem = !$item['requiresLogin'] || 
                                ($currentUser && (!isset($item['requiredRole']) || has_permission($item['requiredRole'])));
                    ?>
                    <?php if ($showItem): ?>
                        <a href="<?php echo $item['url']; ?>" class="block px-3 py-2 rounded hover:bg-gray-700">
                            <i class="fas <?php echo $item['icon']; ?> mr-1"></i>
                            <?php echo $item['name']; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($currentUser): ?>
                    <a href="/users/profile.php" class="block px-3 py-2 rounded hover:bg-gray-700">
                        <i class="fas fa-user mr-1"></i>
                        プロフィール
                    </a>
                    <a href="/logout.php" class="block px-3 py-2 rounded hover:bg-gray-700">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        ログアウト
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="block px-3 py-2 rounded hover:bg-gray-700">
                        <i class="fas fa-sign-in-alt mr-1"></i>
                        ログイン
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>