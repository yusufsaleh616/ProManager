<?php 
// إضافة هيدرز لمنع المتصفح من كاش الصفحة والملفات القديمة
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "lang_config.php";
include "connection_settings.php";

if (!isset($_SESSION['needs_password_change']) || $_SESSION['needs_password_change'] != 1 || !isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];
$role = $_SESSION['role'];
$msg = "";
$success = false;

if (isset($_POST['change_password'])) {
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    // Strong password validation (at least 8 chars, 1 letter, 1 number, 1 special char)
    $pass_valid = (strlen($new_pass) >= 8 && preg_match('/[A-Za-z]/', $new_pass) && preg_match('/[0-9]/', $new_pass) && preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_pass));

    if ($new_pass !== $confirm_pass) {
        $msg = $lang == 'ar' ? "⚠️ كلمات المرور غير متطابقة!" : "⚠️ Passwords do not match!";
    } elseif (!$pass_valid) {
        $msg = $lang == 'ar' ? "⚠️ كلمة المرور ضعيفة! يجب أن تحتوي على 8 خانات على الأقل (حروف، أرقام، رموز)" : "⚠️ Weak password! Must be at least 8 chars (letters, numbers, symbols)";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        
        if ($role == 'student') {
            $stmt = mysqli_prepare($conn, "UPDATE students SET password=?, needs_password_change=0 WHERE email=?");
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE supervisors SET password=?, needs_password_change=0 WHERE email=?");
        }
        mysqli_stmt_bind_param($stmt, "ss", $hashed_pass, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['needs_password_change'] = 0;
            $success = true;
            $msg = $lang == 'ar' ? "✅ تم تغيير كلمة المرور بنجاح! جاري تحويلك..." : "✅ Password changed successfully! Redirecting...";
            
            $redirect_url = ($role == 'student') ? "student_dashboard.php" : "supervisor_dashboard.php";
            header("refresh:2;url=$redirect_url");
        } else {
            $msg = $lang == 'ar' ? "⚠️ حدث خطأ أثناء التحديث." : "⚠️ Error occurred during update.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo T('lang_code'); ?>" dir="<?php echo T('dir'); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang == 'ar' ? 'تغيير كلمة المرور لأول مرة' : 'First Time Password Change'; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page" style="justify-content: center; position: relative;">
    <a href="?change_lang=<?php echo $lang == 'ar' ? 'en' : 'ar'; ?>" class="lang-btn" style="position: absolute; top: 20px; <?php echo $lang == 'ar' ? 'left' : 'right'; ?>: 20px; padding: 10px 20px; border-radius: 5px; text-decoration: none; color: #fff; background: rgba(0,0,0,0.5); font-weight: bold;">
        <i class="fa-solid fa-globe"></i> <?php echo T('switch_lang'); ?>
    </a>
    <div class="login-card" style="width: 420px;">
        <h2 class="login-title">
            <i class="fa-solid fa-key lock-icon"></i>
            <?php echo $lang == 'ar' ? 'تغيير كلمة المرور' : 'Change Password'; ?>
        </h2>
        
        <p style="font-size: 13px; color: #fff; line-height: 1.6; margin-bottom: 20px; background: rgba(255,255,255,0.15); padding: 10px; border-radius: 8px; text-align: center;">
            <?php echo $lang == 'ar' 
                ? "ℹ️ يرجى تغيير كلمة المرور لتفعيل حسابك وجعله أكثر أماناً، حيث قمت بتسجيل الدخول لأول مرة." 
                : "ℹ️ Please change your default password to activate your account and make it secure, as you are logging in for the first time."; 
            ?>
        </p>

        <?php if($msg): ?>
            <p style='text-align:center; color:#fff; font-weight:bold; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; margin-bottom: 20px;'>
                <?php echo $msg; ?>
            </p>
        <?php endif; ?>

        <?php if(!$success): ?>
            <form method="POST">
                <div class="input-group">
                    <label><?php echo $lang == 'ar' ? 'كلمة المرور الجديدة' : 'New Password'; ?></label>
                    <div class="password-wrapper">
                        <input type="password" name="new_pass" id="new_pass" required placeholder="••••••••">
                        <span class="toggle-password" onclick="togglePass('new_pass', this)">
                            <i class="fa-solid fa-eye-slash"></i>
                        </span>
                    </div>
                </div>

                <div class="input-group">
                    <label><?php echo $lang == 'ar' ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?></label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_pass" id="confirm_pass" required placeholder="••••••••">
                        <span class="toggle-password" onclick="togglePass('confirm_pass', this)">
                            <i class="fa-solid fa-eye-slash"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" name="change_password"><?php echo $lang == 'ar' ? 'تأكيد وتفعيل الحساب' : 'Confirm & Activate'; ?></button>
            </form>
        <?php endif; ?>
    </div>

    <script>
    function togglePass(id, btn) {
        var x = document.getElementById(id);
        var icon = btn.querySelector('i');
        if (x.type === "password") {
            x.type = "text";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            x.type = "password";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
    </script>
</body>
</html>
