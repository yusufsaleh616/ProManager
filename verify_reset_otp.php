<?php
include "lang_config.php";
include "connection_settings.php";

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit();
}

$msg = "";
if (isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);
    
    if (isset($_SESSION['reset_otp']) && $otp == $_SESSION['reset_otp'] && time() < $_SESSION['reset_otp_expiry']) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $msg = ($lang == 'ar' ? "رمز التحقق غير صحيح أو منتهي الصلاحية!" : "Invalid or expired OTP!");
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo T('lang_code'); ?>" dir="<?php echo T('dir'); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang == 'ar' ? 'تحقق من الرمز' : 'Verify OTP'; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page" style="justify-content: center;">
    <div class="login-card">
        <h2 class="login-title">
            <?php echo $lang == 'ar' ? 'أدخل رمز التحقق' : 'Enter OTP'; ?>
        </h2>
        <p style="color:#fff; font-size:14px;"><?php echo ($lang == 'ar' ? "تم إرسال الرمز إلى: " : "Code sent to: ") . $_SESSION['reset_email']; ?></p>
        <?php if($msg) echo "<p style='text-align:center; color:#ffeb3b; font-weight:bold;'>$msg</p>"; ?>
        <form method="POST">
            <div class="input-group">
                <input type="text" name="otp" required placeholder="XXXXXX" maxlength="6" style="text-align:center; font-size:24px; letter-spacing:10px;">
            </div>
            <button type="submit" name="verify_otp"><?php echo $lang == 'ar' ? 'تحقق' : 'Verify'; ?></button>
        </form>
    </div>
</body>
</html>
