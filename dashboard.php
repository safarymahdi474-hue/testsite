<?php
// dashboard.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);
$auth->checkAuth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    $auth->logout();
    header("Location: login.php");
    exit();
}

$userName = $currentUser['display_name'];
$userInitials = getUserInitials($currentUser['first_name'] ?? '', $currentUser['last_name'] ?? '', $userName);
$userAvatar = $currentUser['avatar'] ?? '';
$activePage = 'dashboard';

try {
    $userStmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $orderStmt = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amount FROM orders");
    $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
    $totalOrders = $orderData['total'];
    $totalRevenue = $orderData['total_amount'] ?? 0;

    $productStmt = $conn->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $productStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $recentOrders = $conn->query("
        SELECT o.*, p.name as product_name
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    $recentActivities = $conn->query("
        SELECT a.*, u.full_name
        FROM activities a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطا در دریافت اطلاعات: " . $e->getMessage());
}

$chartData = [
    'labels' => ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
    'revenue' => [65000, 72000, 84000, 79000, 92000, 84250],
    'expenses' => [42000, 45000, 48000, 43000, 51000, 49000]
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>داشبورد مدیریت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>

<main class="main">
    <div class="content-area">
        <div class="page-header">
            <div>
                <h1 class="page-title">نمای کلی داشبورد</h1>
                <div class="breadcrumb-bar">خانه / <span>داشبورد</span></div>
            </div>
            <button class="btn-orange">
                <i class="bi bi-plus-lg"></i> گزارش جدید
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon-box" style="background:#FEF3E9;color:#F97316">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-value"><?php echo e(number_format((float) $totalRevenue, 0)); ?>$</div>
                    <div class="stat-label">درآمد کل</div>
                    <div class="stat-change up">
                        <i class="bi bi-arrow-up-short"></i> بروزرسانی از داده‌های ثبت‌شده
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon-box" style="background:#EEF2FF;color:#6366F1">
                        <i class="bi bi-bag-check"></i>
                    </div>
                    <div class="stat-value"><?php echo e(number_format((int) $totalOrders)); ?></div>
                    <div class="stat-label">سفارشات کل</div>
                    <div class="stat-change up">
                        <i class="bi bi-arrow-up-short"></i> تعداد سفارش‌های ثبت‌شده
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon-box" style="background:#F0FDF4;color:#10B981">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-value"><?php echo e(number_format((int) $totalUsers)); ?></div>
                    <div class="stat-label">کاربران فعال</div>
                    <div class="stat-change up">
                        <i class="bi bi-arrow-up-short"></i> کاربران موجود در سیستم
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon-box" style="background:#FFF1F2;color:#EF4444">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="stat-value"><?php echo e(number_format((int) $totalProducts)); ?></div>
                    <div class="stat-label">محصولات</div>
                    <div class="stat-change down">
                        <i class="bi bi-arrow-right-short"></i> اقلام موجود در کاتالوگ
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="chart-card">
                    <div class="card-header-row">
                        <div>
                            <p class="card-title">نمودار درآمد</p>
                            <p class="card-subtitle">درآمد و هزینه ماهانه سال ۱۴۰۳</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="badge-pill active">سالانه</button>
                            <button class="badge-pill">ماهانه</button>
                            <button class="badge-pill">هفتگی</button>
                        </div>
                    </div>
                    <canvas id="revenueChart" height="110"></canvas>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="chart-card">
                    <div class="card-header-row">
                        <div>
                            <p class="card-title">تفکیک فروش</p>
                            <p class="card-subtitle">بر اساس دسته‌بندی</p>
                        </div>
                    </div>
                    <canvas id="donutChart" height="185"></canvas>
                    <div class="mt-3">
                        <div class="donut-legend-item"><span class="legend-label"><span class="legend-dot" style="background:#F97316"></span> الکترونیک</span><strong style="font-size:13px">۴۲٪</strong></div>
                        <div class="donut-legend-item"><span class="legend-label"><span class="legend-dot" style="background:#6366F1"></span> پوشاک</span><strong style="font-size:13px">۲۸٪</strong></div>
                        <div class="donut-legend-item"><span class="legend-label"><span class="legend-dot" style="background:#10B981"></span> کتاب</span><strong style="font-size:13px">۱۸٪</strong></div>
                        <div class="donut-legend-item"><span class="legend-label"><span class="legend-dot" style="background:#F59E0B"></span> سایر</span><strong style="font-size:13px">۱۲٪</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="card-header-row px-4 pt-4">
                        <div>
                            <p class="card-title">سفارشات اخیر</p>
                            <p class="card-subtitle">آخرین ۵ تراکنش</p>
                        </div>
                    </div>
                    <table>
                        <thead>
                        <tr>
                            <th>شماره</th>
                            <th>مشتری</th>
                            <th>محصول</th>
                            <th>مبلغ</th>
                            <th>وضعیت</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <?php
                            $statusClass = 'status-info';
                            $statusLabel = $order['status'];
                            if ($order['status'] === 'delivered') {
                                $statusClass = 'status-success';
                                $statusLabel = 'تحویل‌شده';
                            } elseif ($order['status'] === 'pending') {
                                $statusClass = 'status-warning';
                                $statusLabel = 'در انتظار';
                            } elseif ($order['status'] === 'cancelled') {
                                $statusClass = 'status-danger';
                                $statusLabel = 'لغو‌شده';
                            } elseif ($order['status'] === 'shipped') {
                                $statusClass = 'status-info';
                                $statusLabel = 'در حمل';
                            }
                            ?>
                            <tr>
                                <td><strong>#<?php echo e($order['order_number']); ?></strong></td>
                                <td><?php echo e($order['customer_name']); ?></td>
                                <td><?php echo e($order['product_name'] ?? '-'); ?></td>
                                <td><strong>$<?php echo e(number_format((float) $order['amount'], 0)); ?></strong></td>
                                <td><span class="status-badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="chart-card mb-3">
                    <div class="card-header-row">
                        <div>
                            <p class="card-title">فعالیت تیم</p>
                            <p class="card-subtitle">آخرین رویدادها</p>
                        </div>
                    </div>

                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-dot" style="background:#FEF3E9;color:#F97316">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="activity-text">
                                    <span class="activity-bold"><?php echo e($activity['full_name'] ?: 'کاربر'); ?></span>
                                    <?php echo e($activity['description']); ?>
                                </div>
                                <div class="activity-time"><?php echo e(formatDateTimeValue($activity['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="chart-card">
                    <div class="card-header-row">
                        <div>
                            <p class="card-title">اهداف فروش</p>
                            <p class="card-subtitle">این فصل</p>
                        </div>
                    </div>

                    <div class="progress-item"><div class="progress-meta"><span>الکترونیک</span><span style="color:#F97316">۷۸٪</span></div><div class="progress"><div class="progress-bar" style="width:78%;background:#F97316"></div></div></div>
                    <div class="progress-item"><div class="progress-meta"><span>پوشاک</span><span style="color:#6366F1">۵۵٪</span></div><div class="progress"><div class="progress-bar" style="width:55%;background:#6366F1"></div></div></div>
                    <div class="progress-item"><div class="progress-meta"><span>کتاب</span><span style="color:#10B981">۹۰٪</span></div><div class="progress"><div class="progress-bar" style="width:90%;background:#10B981"></div></div></div>
                    <div class="progress-item"><div class="progress-meta"><span>سایر</span><span style="color:#F59E0B">۴۰٪</span></div><div class="progress"><div class="progress-bar" style="width:40%;background:#F59E0B"></div></div></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const chartData = <?php echo json_encode($chartData, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
