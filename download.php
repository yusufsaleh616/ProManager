<?php
session_start();
include "connection_settings.php";

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    die("Unauthorized access");
}

if (!isset($_GET['file']) || !isset($_GET['name'])) {
    die("Invalid request");
}

// حماية من ثغرات Directory Traversal بجلب اسم الملف فقط بدون مسارات
$file = basename($_GET['file']);
$filepath = 'uploads/' . $file;
$download_name = $_GET['name']; 

if (file_exists($filepath)) {
    // تحديد نوع الملف
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filepath);
    finfo_close($finfo);

    // إرسال الهيدرز لإجبار التحميل بالاسم الجديد
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($download_name) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    
    // مسح الـ Buffer لمنع أي أخطاء في تكوين الملف
    ob_clean();
    flush();
    
    // قراءة الملف وإرساله للمستخدم
    readfile($filepath);
    exit;
} else {
    die("File not found");
}
?>
