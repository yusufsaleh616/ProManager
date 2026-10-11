<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. تحديد سياق المستخدم لعزل اللغة (طالب، مشرف، أدمن، أو زائر)
$role_context = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
// إذا كان زائراً في بوابة الأدمن، نعتبره أدمن مبدئياً
if ($role_context == 'guest' && basename($_SERVER['PHP_SELF']) == 'admin_portal.php') {
    $role_context = 'admin';
}
$lang_key = 'lang_' . $role_context;

// 2. فحص اللغة: الأولوية للارتباط المتغير، ثم الجلسة المخصصة، ثم الكوكي المخصص
if (isset($_GET['change_lang'])) {
    $lang = $_GET['change_lang'] == 'en' ? 'en' : 'ar';
    $_SESSION[$lang_key] = $lang;
    setcookie($lang_key, $lang, time() + (86400 * 30), "/"); // حفظ لمدة 30 يوم
    
    // إزالة المتغير من الرابط بعد التبديل
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $url);
    exit();
}

if (isset($_SESSION[$lang_key])) {
    $lang = $_SESSION[$lang_key];
} elseif (isset($_COOKIE[$lang_key])) {
    $lang = $_COOKIE[$lang_key];
    $_SESSION[$lang_key] = $lang;
} else {
    // إذا لم يكن يملك لغة مخصصة لدوره، نرث لغة الزائر إن وجدت، وإلا الافتراضي عربي
    if (isset($_SESSION['lang_guest'])) {
        $lang = $_SESSION['lang_guest'];
    } elseif (isset($_COOKIE['lang_guest'])) {
        $lang = $_COOKIE['lang_guest'];
    } else {
        $lang = 'ar';
    }
    $_SESSION[$lang_key] = $lang;
}

$translations = [
    'ar' => [
        'title' => 'إداراة مشاريع التخرج',
        'welcome' => 'مرحباً بك يا ',
        'logout' => 'تسجيل خروج',
        'student_panel' => 'صفحة الطالب - إدارة المشاريع',
        'supervisor_panel' => 'لوحة تحكم المشرف',
        'admin_panel' => 'لوحة تحكم المسؤول',
        'login' => 'تسجيل الدخول',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'college' => 'الكلية',
        'role' => 'الدخول بصفة',
        'student' => 'طالب',
        'supervisor' => 'عضو هيئة تدريس',
        'admin' => 'مدير النظام',
        'submit' => 'دخول',
        'forgot_pass' => 'نسيت كلمة السر؟',
        'project_title' => 'عنوان المشروع',
        'project_desc' => 'وصف المشروع',
        'upload_file' => 'رفع الملف للاعتماد',
        'notes' => 'توجيهات وملاحظات المشرف',
        'grade' => 'النتيجة النهائية',
        'no_project' => 'لم تقم بتسجيل مشروع بعد',
        'save_project' => 'حفظ وتسجيل المشروع',
        'supervisor' => 'المشرف',
        'status' => 'حالة المشروع',
        'actions' => 'إجراءات',
        'add_user' => 'إضافة مستخدم جديد',
        'name' => 'الاسم',
        'delete' => 'حذف',
        'switch_lang' => 'English',
        'lang_code' => 'ar',
        'dir' => 'rtl',
        'forgot_pass_title' => 'استعادة كلمة المرور',
        'enter_email' => 'أدخل بريدك الإلكتروني المسجل',
        'send_otp' => 'إرسال رمز التحقق',
        'enter_otp' => 'أدخل رمز التحقق المرسل لهاتفك/ايميلك',
        'verify_otp' => 'تحقق من الرمز',
        'new_password' => 'كلمة المرور الجديدة',
        'reset_submit' => 'تغيير كلمة المرور',
        'otp_invalid' => 'رمز التحقق غير صحيح أو انتهت صلاحيته',
        'otp_sent' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
        'email_not_found' => 'هذا البريد الإلكتروني غير مسجل لدينا',
        'department' => 'القسم',
        'is' => 'نظم المعلومات',
        'cs' => 'علوم الحاسوب',
        'ai' => 'الذكاء الإصطناعي وعلم البيانات',

        'it' => 'تكنولوجيا المعلومات',
        'show_pass' => 'إظهار',
        'hide_pass' => 'إخفاء'
    ],
    'en' => [
        'title' => 'Project Management System',
        'welcome' => 'Welcome, ',
        'logout' => 'Logout',
        'student_panel' => 'Student Dashboard - Projects',
        'supervisor_panel' => 'Supervisor Dashboard',
        'admin_panel' => 'Admin Dashboard',
        'login' => 'Login',
        'email' => 'Email',
        'password' => 'Password',
        'college' => 'College',
        'role' => 'Login as',
        'student' => 'Student',
        'supervisor' => 'Faculty Member',
        'admin' => 'System Admin',
        'submit' => 'Login',
        'forgot_pass' => 'Forgot password?',
        'project_title' => 'Project Title',
        'project_desc' => 'Project Description',
        'upload_file' => 'Upload File for Approval',
        'notes' => 'Supervisor Guidelines & Notes',
        'grade' => 'Final Grade',
        'no_project' => 'No project registered yet',
        'save_project' => 'Save & Register Project',
        'supervisor' => 'Supervisor',
        'status' => 'Project Status',
        'actions' => 'Actions',
        'add_user' => 'Add New User',
        'name' => 'Name',
        'delete' => 'Delete',
        'switch_lang' => 'العربية',
        'lang_code' => 'en',
        'dir' => 'ltr',
        'forgot_pass_title' => 'Password Recovery',
        'enter_email' => 'Enter your registered email',
        'send_otp' => 'Send OTP Code',
        'enter_otp' => 'Enter the OTP sent to your email',
        'verify_otp' => 'Verify Code',
        'new_password' => 'New Password',
        'reset_submit' => 'Reset Password',
        'otp_invalid' => 'Invalid or expired OTP code',
        'otp_sent' => 'OTP has been sent to your email',
        'email_not_found' => 'This email is not registered',
        'department' => 'Department',
        'is' => 'Information Systems',
        'cs' => 'Computer Science',
        'ai' => 'AI & Data Science',

        'it' => 'Information Technology',
        'show_pass' => 'Show',
        'hide_pass' => 'Hide'
    ]
];

function T($key) {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}
?>
