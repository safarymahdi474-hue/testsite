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

$errors = [];
$successMessage = '';
$avatarUploadPath = $currentUser['avatar'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($firstName === '') {
        $errors['first_name'] = 'نام الزامی است.';
    }

    if ($lastName === '') {
        $errors['last_name'] = 'نام خانوادگی الزامی است.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'ایمیل واردشده معتبر نیست.';
    } elseif ($auth->isEmailTaken($email, (int) $currentUser['id'])) {
        $errors['email'] = 'این ایمیل قبلاً ثبت شده است.';
    }

    if ($phone === '') {
        $errors['phone'] = 'شماره تماس الزامی است.';
    } elseif (!preg_match('/^\+?[0-9\-\s\(\)]{8,20}$/', $phone)) {
        $errors['phone'] = 'شماره تماس معتبر نیست.';
    }

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors['avatar'] = 'آپلود تصویر با خطا مواجه شد.';
        } else {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                $errors['avatar'] = 'فرمت تصویر باید JPG، PNG، GIF یا WEBP باشد.';
            } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                $errors['avatar'] = 'حجم تصویر باید کمتر از 2 مگابایت باشد.';
            } else {
                $uploadDirectory = __DIR__ . '/assets/uploads/avatars';
                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0775, true);
                }

                $extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $fileName = 'avatar_' . (int) $currentUser['id'] . '_' . time() . '.' . $extension;
                $targetPath = $uploadDirectory . '/' . $fileName;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                    $avatarUploadPath = 'assets/uploads/avatars/' . $fileName;
                } else {
                    $errors['avatar'] = 'ذخیره تصویر انجام نشد.';
                }
            }
        }
    }

    if (empty($errors)) {
        $updated = $auth->updateCurrentUserProfile([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'avatar' => $avatarUploadPath
        ]);

        if ($updated) {
            $successMessage = 'اطلاعات پروفایل با موفقیت ذخیره شد.';
            $currentUser = $auth->getCurrentUser();
        } else {
            $errors['general'] = 'ذخیره اطلاعات انجام نشد. دوباره تلاش کنید.';
        }
    } else {
        $currentUser['first_name'] = $firstName;
        $currentUser['last_name'] = $lastName;
        $currentUser['phone'] = $phone;
        $currentUser['email'] = $email;
        $currentUser['avatar'] = $avatarUploadPath;
    }
}

$userName = $currentUser['display_name'] ?? trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
$userName = trim($userName) !== '' ? $userName : ($currentUser['username'] ?? 'کاربر');
$userInitials = getUserInitials($currentUser['first_name'] ?? '', $currentUser['last_name'] ?? '', $userName);
$userAvatar = $currentUser['avatar'] ?? '';
$activePage = 'edit-profile';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ویرایش پروفایل - PowerAdmin</title>
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
                <h1 class="page-title">ویرایش پروفایل</h1>
                <div class="breadcrumb-bar">خانه / پروفایل من / <span>ویرایش پروفایل</span></div>
            </div>
            <a href="profile.php" class="btn-orange">
                <i class="bi bi-arrow-right"></i> بازگشت به پروفایل
            </a>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="chart-card">
                    <div class="card-header-row">
                        <div>
                            <p class="card-title">فرم بروزرسانی</p>
                            <p class="card-subtitle">اطلاعات حساب کاربری خود را ویرایش کنید</p>
                        </div>
                    </div>

                    <?php if ($successMessage !== ''): ?>
                        <div class="form-alert form-alert-success"><?php echo e($successMessage); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="form-alert form-alert-danger"><?php echo e($errors['general']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" class="profile-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="profile-label" for="first_name">نام</label>
                                <input class="profile-input <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" type="text" id="first_name" name="first_name" value="<?php echo e($currentUser['first_name'] ?? ''); ?>">
                                <?php if (isset($errors['first_name'])): ?><div class="field-error"><?php echo e($errors['first_name']); ?></div><?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="profile-label" for="last_name">نام خانوادگی</label>
                                <input class="profile-input <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" type="text" id="last_name" name="last_name" value="<?php echo e($currentUser['last_name'] ?? ''); ?>">
                                <?php if (isset($errors['last_name'])): ?><div class="field-error"><?php echo e($errors['last_name']); ?></div><?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="profile-label" for="phone">شماره تماس</label>
                                <input class="profile-input <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" type="text" id="phone" name="phone" value="<?php echo e($currentUser['phone'] ?? ''); ?>">
                                <?php if (isset($errors['phone'])): ?><div class="field-error"><?php echo e($errors['phone']); ?></div><?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="profile-label" for="email">ایمیل</label>
                                <input class="profile-input <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" type="email" id="email" name="email" value="<?php echo e($currentUser['email'] ?? ''); ?>">
                                <?php if (isset($errors['email'])): ?><div class="field-error"><?php echo e($errors['email']); ?></div><?php endif; ?>
                            </div>

                            <div class="col-12">
                                <label class="profile-label" for="avatar">تصویر پروفایل</label>
                                <input class="profile-input <?php echo isset($errors['avatar']) ? 'is-invalid' : ''; ?>" type="file" id="avatar" name="avatar" accept=".jpg,.jpeg,.png,.gif,.webp">
                                <?php if (isset($errors['avatar'])): ?><div class="field-error"><?php echo e($errors['avatar']); ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button type="submit" class="btn-orange">
                                <i class="bi bi-check2-circle"></i> ذخیره تغییرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="chart-card profile-summary-card">
                    <div class="profile-avatar-large">
                        <?php if (!empty($userAvatar)): ?>
                            <img src="<?php echo e($userAvatar); ?>" alt="<?php echo e($userName); ?>" class="avatar-image">
                        <?php else: ?>
                            <?php echo e($userInitials); ?>
                        <?php endif; ?>
                    </div>
                    <h2 class="profile-name"><?php echo e($userName); ?></h2>
                    <div class="profile-meta"><?php echo e($currentUser['email'] ?? ''); ?></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="assets/js/main.js"></script>
</body>
</html>
