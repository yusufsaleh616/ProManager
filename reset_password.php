<?php
include "lang_config.php";
include "connection_settings.php";

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_role'])) {
    header("Location: forgot_password.php");
    exit();
}

$msg = "";
if (isset($_POST['change_pass'])) {
    $pass = $_POST['pass'];
    $confirm = $_POST['confirm_pass'];
    $email = $_SESSION['reset_email'];
    $role = $_SESSION['reset_role'];
    
    $pass_valid = (strlen($pass) >= 8 && preg_match('/[A-Za-z]/', $pass) && preg_match('/[0-9]/', $pass) && preg_match('/[!@#$%^&*(),.?":{}|<>]/', $pass));

    if ($pass !== $confirm) {
        $msg = ($lang == 'ar' ? "كلمات المرور غير متطابقة!" : "Passwords do not match!");
    } elseif (!$pass_valid) {
        $msg = ($lang == 'ar' ? "كلمة المرور ضعيفة!" : "Weak password!");
    } else {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        
        $table = "";
        if ($role == 'student') {
            $table = "students";
        } elseif ($role == 'supervisor') {
            $table = "supervisors";
        } elseif ($role == 'admin') {
            $table = "admins";
        }
        
        if (!empty($table)) {
            $stmt = mysqli_prepare($conn, "UPDATE $table SET password=? WHERE email=?");
            mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);
            if (mysqli_stmt_execute($stmt)) {
                // Clear reset session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_role']);
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_otp_expiry']);
                unset($_SESSION['otp_verified']);
                
                header("Location: index.php?msg=" . ($lang == 'ar' ? "تم تغيير كلمة السر بنجاح!" : "Password changed successfully!"));
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo T('lang_code'); ?>" dir="<?php echo T('dir'); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang == 'ar' ? 'تعيين كلمة سر جديدة' : 'Reset Password'; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page" style="justify-content: center;">
    <div class="login-card">
        <h2 class="login-title">
            <?php echo $lang == 'ar' ? 'كلمة سر جديدة' : 'New Password'; ?>
        </h2>
        <?php if($msg) echo "<p style='text-align:center; color:#ffeb3b; font-weight:bold;'>$msg</p>"; ?>
        <form method="POST">
            <div class="input-group">
                <label><?php echo T('password'); ?></label>
                <div class="password-wrapper">
                    <input type="password" name="pass" id="pass" required placeholder="********">
                    <span class="toggle-password" onclick="togglePass('pass', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </span>
                </div>
            </div>
            <div class="input-group">
                <label><?php echo $lang == 'ar' ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?></label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_pass" id="confirm_pass" required placeholder="********">
                    <span class="toggle-password" onclick="togglePass('confirm_pass', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </span>
                </div>
            </div>
            <button type="submit" name="change_pass"><?php echo $lang == 'ar' ? 'حفظ' : 'Save'; ?></button>
        </form>
    </div>
    <script>
    function togglePass(id, btn) {
        var input = document.getElementById(id);
        var icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
    </script>
</body>
</html>
