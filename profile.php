<?php
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
$activePage = 'profile';
$userStatus = formatUserStatus($currentUser['status'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>پروفایل من - PowerAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>

<main class="main">
    <div class="content-area">
        <div class="page-header">
            <div>
                <h1 class="page-title">پروفایل من</h1>
                <div class="breadcrumb-bar">خانه / <span>پروفایل من</span></div>
            </div>
            <a href="edit-profile.php" class="btn-orange">
                <i class="bi bi-pencil-square"></i> ویرایش پروفایل
            </a>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="chart-card profile-summary-card">
                    <div class="profile-avatar-large">
                        <?php if (!empty($userAvatar)): ?>
                            <img src="<?php echo e($userAvatar); ?>" alt="<?php echo e($userName); ?>" class="avatar-image">
                        <?php else: ?>
                            <?php echo e($userInitials); ?>
                        <?php endif; ?>
                    </div>
                    <h2 class="profile-name"><?php echo e($userName); ?></h2>
                    <div class="profile-meta"><?php echo e($currentUser['username']); ?></div>
                    <span class="status-badge <?php echo e($userStatus['class']); ?>"><?php echo e($userStatus['label']); ?></span>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="table-card">
                    <div class="card-header-row px-4 pt-4">
                        <div>
                            <p class="card-title">اطلاعات حساب</p>
                            <p class="card-subtitle">مشخصات ثبت‌شده کاربر فعلی</p>
                        </div>
                    </div>
                    <div class="profile-details">
                        <div class="profile-detail-item"><span class="profile-detail-label">نام</span><strong><?php echo e($currentUser['first_name'] ?: 'ثبت نشده'); ?></strong></div>
                        <div class="profile-detail-item"><span class="profile-detail-label">نام خانوادگی</span><strong><?php echo e($currentUser['last_name'] ?: 'ثبت نشده'); ?></strong></div>
                        <div class="profile-detail-item"><span class="profile-detail-label">شماره تماس</span><strong><?php echo e($currentUser['phone'] ?: 'ثبت نشده'); ?></strong></div>
                        <div class="profile-detail-item"><span class="profile-detail-label">ایمیل</span><strong><?php echo e($currentUser['email']); ?></strong></div>
                        <div class="profile-detail-item"><span class="profile-detail-label">وضعیت کاربر</span><strong><?php echo e($userStatus['label']); ?></strong></div>
                        <div class="profile-detail-item"><span class="profile-detail-label">تاریخ عضویت</span><strong><?php echo formatDateTimeValue($currentUser['created_at'] ?? null); ?></strong></div>
                        <div class="profile-detail-item"><span class="profile-detail-label">آخرین بروزرسانی</span><strong><?php echo formatDateTimeValue($currentUser['updated_at'] ?? null); ?></strong></div>
                        <div class="profile-detail-item"><span class="profile-detail-label">آخرین ورود</span><strong><?php echo formatDateTimeValue($currentUser['last_login'] ?? null); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="assets/js/main.js"></script>
</body>
</html>
