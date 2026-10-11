<?php
session_start();
// تحديد مفتاح اللغة الخاص بهذا المستخدم قبل مسح الجلسة
$role_context = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
if ($role_context == 'guest' && basename($_SERVER['PHP_SELF']) == 'admin_portal.php') {
    $role_context = 'admin';
}
$lang_key = 'lang_' . $role_context;
$current_lang = isset($_SESSION[$lang_key]) ? $_SESSION[$lang_key] : (isset($_COOKIE[$lang_key]) ? $_COOKIE[$lang_key] : 'ar');

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

session_unset();
session_destroy();

// إعادة بدء جلسة نظيفة ووضع اللغة فيها للزائر المستقبلي
session_start();
// نحفظها كلغة زائر حتى تظهر الواجهة الخارجية باللغة التي كان عليها
$_SESSION['lang_guest'] = $current_lang;
$_SESSION[$lang_key] = $current_lang;

header("Location: index.php");
exit();
?>