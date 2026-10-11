<?php
include "connection_settings.php";
session_start();

// التحقق من أن المستخدم مسجل دخول (أدمن أو مشرف فقط)
if (isset($_SESSION['role']) && ($_SESSION['role'] == 'supervisor' || $_SESSION['role'] == 'admin')) {
    
    if (isset($_GET['file']) && isset($_GET['name'])) {
        $file_path = "uploads/" . basename($_GET['file']);
        $suggested_name = $_GET['name'];

        if (file_exists($file_path)) {
            // إرسال هيدرز لإجبار المتصفح على التحميل بالاسم الجديد
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $suggested_name . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            
            readfile($file_path);
            exit;
        } else {
            $error_msg = "⚠️ الملف غير موجود على السيرفر.";
        }
    } else {
        $error_msg = "⚠️ طلب غير صالح.";
    }
} else {
    $error_msg = "🚫 غير مسموح لك بالوصول لهذا الملف.";
}

if (isset($error_msg)) {
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>خطأ في التحميل</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="error-page-body">
    <div class="error-box">
        <h2>عذراً!</h2>
        <p><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="javascript:history.back()" class="btn-back">🔙 العودة للصفحة السابقة</a>
    </div>
</body>
</html>
<?php 
}
?>
