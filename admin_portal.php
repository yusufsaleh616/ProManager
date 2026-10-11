<?php
include "lang_config.php";
include "connection_settings.php";

if (isset($_POST['admin_login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM admins WHERE email=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password']) || $password === $row['password']) {
            $_SESSION['name'] = $row['name'];
            $_SESSION['id'] = $row['admin_id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['email'] = $row['email'];
            $_SESSION['college'] = 'IT';
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = $lang == 'ar' ? "⚠️ بيانات الدخول غير صحيحة!" : "⚠️ Invalid credentials!";
        }
    } else {
        $error = $lang == 'ar' ? "⚠️ بيانات الدخول غير صحيحة!" : "⚠️ Invalid credentials!";
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo T('lang_code'); ?>" dir="<?php echo T('dir'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo T('admin_panel'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-portal">
    <div class="login-box">
        <div style="text-align: end; margin-bottom: 10px;">
            <a href="?change_lang=<?php echo $lang == 'ar' ? 'en' : 'ar'; ?>" class="lang-btn" style="padding: 3px 10px; font-size: 11px;"><?php echo T('switch_lang'); ?></a>
        </div>
        <h2 style="color: #1a3c40;">⚙️ <?php echo T('login'); ?> (Admin)</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 20px;"><?php echo $lang == 'ar' ? 'هذه البوابة مخصصة لمدير النظام فقط' : 'This portal is for system admin only'; ?></p>
        
        <?php if(isset($error)) { echo "<div class='error'>$error</div>"; } ?>

        <form action="admin_portal.php" method="POST">
            <input type="email" name="email" placeholder="<?php echo T('email'); ?>" required>
            <div class="password-wrapper">
                <input type="password" name="password" id="admin_password" placeholder="<?php echo T('password'); ?>" required>
                <span class="toggle-password" onclick="togglePass('admin_password', this)">
                    <i class="fa-solid fa-eye-slash"></i>
                </span>
            </div>
            <button type="submit" name="admin_login"><?php echo T('submit'); ?></button>
        </form>
        <br><br>
        <a href="index.php" style="font-size: 13px; color: #0288d1; text-decoration: none;">⬅️ <?php echo $lang == 'ar' ? 'العودة للبوابة الرئيسية' : 'Back to main portal'; ?></a>
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