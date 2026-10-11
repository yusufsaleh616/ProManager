<?php
include "lang_config.php";
include "connection_settings.php";

$msg = "";
if (isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    
    // Check in students first
    $stmt = mysqli_prepare($conn, "SELECT email FROM students WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $role = "";
    
    if (mysqli_num_rows($res) > 0) {
        $role = "student";
    } else {
        // Check in supervisors
        $stmt2 = mysqli_prepare($conn, "SELECT email FROM supervisors WHERE email=?");
        mysqli_stmt_bind_param($stmt2, "s", $email);
        mysqli_stmt_execute($stmt2);
        $res2 = mysqli_stmt_get_result($stmt2);
        
        if (mysqli_num_rows($res2) > 0) {
            $role = "supervisor";
        } else {
            // Check in admins
            $stmt3 = mysqli_prepare($conn, "SELECT email FROM admins WHERE email=?");
            mysqli_stmt_bind_param($stmt3, "s", $email);
            mysqli_stmt_execute($stmt3);
            $res3 = mysqli_stmt_get_result($stmt3);
            
            if (mysqli_num_rows($res3) > 0) {
                $role = "admin";
            }
        }
    }
    
    if (!empty($role)) {
        $otp = rand(100000, 999999);
        
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_role'] = $role;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_otp_expiry'] = time() + (10 * 60); // 10 minutes
        
        $msg = ($lang == 'ar' ? "تم إرسال رمز التحقق: <span style='color: #f9a825; font-size: 20px;'>$otp</span>" : "OTP sent: <span style='color: #f9a825; font-size: 20px;'>$otp</span>");
        header("refresh:3; url=verify_reset_otp.php");
    } else {
        $msg = ($lang == 'ar' ? "هذا البريد غير مسجل!" : "Email not registered!");
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo T('lang_code'); ?>" dir="<?php echo T('dir'); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo T('forgot_pass'); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page" style="justify-content: center;">
    <div class="login-card">
        <h2 class="login-title">
            <?php echo T('forgot_pass'); ?>
        </h2>
        <?php if($msg) echo "<p style='text-align:center; color:#fff; font-weight:bold; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px;'>$msg</p>"; ?>
        <form method="POST">
            <div class="input-group">
                <label><?php echo T('email'); ?></label>
                <input type="email" name="email" required placeholder="example@aabu.edu.jo">
            </div>
            <button type="submit" name="request_reset"><?php echo $lang == 'ar' ? 'إرسال رمز التحقق' : 'Send OTP'; ?></button>
        </form>
        <br>
        <a href="index.php" style="color:#fff; text-decoration:none; font-size:13px;"><?php echo $lang == 'ar' ? 'العودة لتسجيل الدخول' : 'Back to Login'; ?></a>
    </div>
</body>
</html>
