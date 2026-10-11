<?php include "lang_config.php"; ?>
<!DOCTYPE html>
<html lang="<?php echo T('lang_code'); ?>" dir="<?php echo T('dir'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo T('title'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="header">
        <div class="header-content">
            <div class="header-text">
                <h1><?php echo $lang == 'ar' ? 'جامعة آل البيت' : 'Al al-Bayt University'; ?></h1>
                <h2><?php echo T('title'); ?></h2>
            </div>
            <a href="?change_lang=<?php echo $lang == 'ar' ? 'en' : 'ar'; ?>" class="lang-btn">
                <?php echo T('switch_lang'); ?>
            </a>
        </div>
    </div>
    <div class="login-card">
        <div class="login-title">
            <span><?php echo T('login'); ?></span>
            <span class="lock-icon">🔒</span>
        </div>
        <?php if (isset($_GET['error'])) { ?>
            <div style="background: #ffcdd2; color: #b71c1c; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 12px;">
                ⚠️ <?php echo $_GET['error']; ?>
            </div>
        <?php } ?>
        <?php if (isset($_GET['msg'])) { ?>
            <div style="background: #c8e6c9; color: #2e7d32; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 12px;">
                ✅ <?php echo $_GET['msg']; ?>
            </div>
        <?php } ?>
        <form action="process_login.php" method="post">
            <input type="hidden" name="college" value="IT">
            <div class="input-group">
                <label><?php echo T('college'); ?></label>
                <div style="padding: 10px; background: rgba(255,255,255,0.1); border-radius: 5px; color: #fff; font-weight: bold;">
                    🏢 <?php echo T('it'); ?>
                </div>
            </div>
            <div class="input-group">
                <label><?php echo T('department'); ?></label>
                <select name="department" required>
                    <option value="" disabled selected>-- <?php echo T('department'); ?> --</option>
                    <option value="cs"><?php echo T('cs'); ?></option>
                    <option value="is"><?php echo T('is'); ?></option>
                    <option value="ai"><?php echo T('ai'); ?></option>
                </select>
            </div>
            <div class="input-group">
                <label><?php echo T('email'); ?></label>
                <input type="text" name="uname" required>
            </div>
            <div class="input-group">
                <label><?php echo T('password'); ?></label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required>
                    <span class="toggle-password" onclick="togglePass('password', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </span>
                </div>
            </div>
            <div class="input-group">
                <label><?php echo T('role'); ?></label>
                <select name="role">
                    <option value="student"><?php echo T('student'); ?> 🎓</option>
                    <option value="supervisor"><?php echo T('supervisor'); ?> 👨‍🏫</option>
                </select>
            </div>
            <button type="submit"><?php echo T('login'); ?></button>
            <a href="forgot_password.php" class="forgot-pass"><?php echo T('forgot_pass'); ?></a>
        </form>
    </div>
    <div class="footer">
        © 2025 <?php echo $lang == 'ar' ? 'جميع الحقوق محفوظة - جامعة آل البيت' : 'All Rights Reserved - Al al-Bayt University'; ?>
    </div>
    <script>
    function togglePass(id, btn) {//لاخفاء كلمه المرور
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