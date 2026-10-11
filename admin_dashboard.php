<?php 
include "lang_config.php";
include "connection_settings.php";

// التحقق: هل هو أدمن
if (isset($_SESSION['email']) && $_SESSION['role'] == 'admin') {
    $admin_college = $_SESSION['college']; 
    if (isset($_SESSION['msg'])) {
        $msg = $_SESSION['msg'];
        unset($_SESSION['msg']);
    }
    // ---------------------------------------------------------
    // 1. كود إضافة مستخدم جديد يدوي (عند ضغط الزر)
    // ---------------------------------------------------------
    if (isset($_POST['add_user'])) {
        $new_name = $_POST['name'];
        $new_email = $_POST['email'];
        $new_pass = $_POST['pass']; 
        $new_confirm = $_POST['confirm_pass'];
        $new_role = $_POST['role'];
        $new_college = $_POST['college'];
        $new_dept = $_POST['department'];
        //  شروط التحقق للايميلات تبعت الكلية
        $regex_cs = '/^\d{2}[01]09(01|06)\d{3}@st\.aabu\.edu\.jo$/';
        $regex_is = '/^\d{2}[01]09(02|04|07|09)\d{3}@st\.aabu\.edu\.jo$/';
        $regex_ai = '/^\d{2}[01]0908\d{3}@st\.aabu\.edu\.jo$/';
        $regex_sec = '/^\d{2}[01]0906\d{3}@st\.aabu\.edu\.jo$/';
        
        $pass_valid = (strlen($new_pass) >= 8 && preg_match('/[A-Za-z]/', $new_pass) && preg_match('/[0-9]/', $new_pass) && preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_pass));

        $valid_student_email = true;
        if ($new_role == 'student') {
            if ($new_dept == 'cs' && !preg_match($regex_cs, $new_email)) $valid_student_email = false;
            elseif ($new_dept == 'is' && !preg_match($regex_is, $new_email)) $valid_student_email = false;
            elseif ($new_dept == 'ai' && !preg_match($regex_ai, $new_email)) $valid_student_email = false;
        }

        if ($new_pass !== $new_confirm) {
            $msg = $lang == 'ar' ? "⚠️ كلمات المرور غير متطابقة!" : "⚠️ Passwords do not match!";
        } elseif ($new_role == 'student' && !$valid_student_email) {
            $msg = $lang == 'ar' ? "⚠️ صيغة إيميل الطالب لا تطابق القسم المختار!" : "⚠️ Student email format does not match the selected department!";
        } elseif ($new_role == 'supervisor' && !str_ends_with($new_email, "@aabu.edu.jo") && !str_ends_with($new_email, "@st.aabu.edu.jo")) {
            $msg = $lang == 'ar' ? "⚠️ إيميل المشرف يجب أن ينتهي بـ @aabu.edu.jo" : "⚠️ Supervisor email must end with @aabu.edu.jo";
        } elseif (!$pass_valid) {
            $msg = $lang == 'ar' ? "⚠️ كلمة المرور ضعيفة! يجب أن تحتوي على 8 خانات على الأقل (حروف، أرقام، رموز)" : "⚠️ Weak password! Must be at least 8 chars (letters, numbers, symbols)";
        } else {
            $check_student = "SELECT email FROM students WHERE email=?";
            $stmt_check_s = mysqli_prepare($conn, $check_student);
            mysqli_stmt_bind_param($stmt_check_s, "s", $new_email);
            mysqli_stmt_execute($stmt_check_s);
            $res_check_s = mysqli_stmt_get_result($stmt_check_s);

            $check_supervisor = "SELECT email FROM supervisors WHERE email=?";
            $stmt_check_d = mysqli_prepare($conn, $check_supervisor);
            mysqli_stmt_bind_param($stmt_check_d, "s", $new_email);
            mysqli_stmt_execute($stmt_check_d);
            $res_check_d = mysqli_stmt_get_result($stmt_check_d);

            if (mysqli_num_rows($res_check_s) > 0 || mysqli_num_rows($res_check_d) > 0) {
                $msg = $lang == 'ar' ? "⚠️ هذا البريد الإلكتروني مسجل مسبقاً!" : "⚠️ This email is already registered!";
            } else {
                $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $dept_map = ['cs' => '01', 'is' => '02', 'ai' => '08'];
                $dept_code = $dept_map[$new_dept] ?? $new_dept;
                
                if ($new_role == 'student') {
                    $student_id = explode('@', $new_email)[0];
                    $sql_insert = "INSERT INTO students (student_id, name, email, password, dept_code) VALUES (?, ?, ?, ?, ?)";
                    $stmt_in = mysqli_prepare($conn, $sql_insert);
                    mysqli_stmt_bind_param($stmt_in, "sssss", $student_id, $new_name, $new_email, $hashed_pass, $dept_code);
                } else {
                    // Generate supervisor ID based on current year (2-digit) + serial number (2-digit)
                    $current_year = date("y");
                    // Get the maximum serial for the current year
                    $stmt_max_id = mysqli_prepare($conn, "SELECT MAX(CAST(SUBSTRING(supervisor_id, 3) AS UNSIGNED)) AS max_serial FROM supervisors WHERE supervisor_id LIKE CONCAT(?, '%') AND supervisor_id REGEXP '^[0-9]+$'");
                    mysqli_stmt_bind_param($stmt_max_id, "s", $current_year);
                    mysqli_stmt_execute($stmt_max_id);
                    $row_max_id = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_max_id));
                    
                    $serial = ($row_max_id['max_serial'] === null) ? 0 : intval($row_max_id['max_serial']) + 1;
                    $supervisor_id = $current_year . str_pad($serial, 2, "0", STR_PAD_LEFT);
                    $sql_insert = "INSERT INTO supervisors (supervisor_id, name, email, password, dept_code) VALUES (?, ?, ?, ?, ?)";
                    $stmt_in = mysqli_prepare($conn, $sql_insert);
                    mysqli_stmt_bind_param($stmt_in, "sssss", $supervisor_id, $new_name, $new_email, $hashed_pass, $dept_code);
                }

                if (mysqli_stmt_execute($stmt_in)) {
                    $_SESSION['msg'] = $lang == 'ar' ? "✅ تم إضافة المستخدم بنجاح!" : "✅ User added successfully!";
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $msg = $lang == 'ar' ? "حدث خطأ أثناء الإضافة." : "Error occurred during addition.";
                }
            }
        }
    }

    // ---------------------------------------------------------
    // كود رفع الطلاب بشكل جماعي عبر ملف Excel/CSV (Bulk Upload)
    // ---------------------------------------------------------
    if (isset($_POST['bulk_upload'])) {
        $bulk_dept = $_POST['bulk_department'];
        $bulk_role = isset($_POST['bulk_role']) ? $_POST['bulk_role'] : 'student';
        
        if (isset($_FILES['student_file']) && $_FILES['student_file']['error'] == 0) {
            $file = $_FILES['student_file']['tmp_name'];
            $handle = fopen($file, "r");
            
            $success_count = 0;
            $failed_count = 0;
            $errors = [];
            
            // Auto-detect delimiter (comma or semicolon)
            $first_line = fgets($handle);
            $delimiter = ",";
            if ($first_line !== FALSE) {
                $comma_count = substr_count($first_line, ',');
                $semicolon_count = substr_count($first_line, ';');
                if ($semicolon_count > $comma_count) {
                    $delimiter = ";";
                }
                rewind($handle);
            }
            
            // Skip the header row if it contains text and detect column indices
            $header = fgetcsv($handle, 1000, $delimiter);
            
            $name_idx = 0;
            $email_idx = 1;
            $id_idx = 2;
            
            if ($header !== FALSE) {
                $clean_headers = [];
                foreach ($header as $h) {
                    if (!mb_check_encoding($h, 'UTF-8')) {
                        if (function_exists('iconv')) {
                            $converted = iconv("WINDOWS-1256", "UTF-8//IGNORE", $h);
                            if ($converted !== false) {
                                $h = $converted;
                            }
                        }
                    }
                    $h = str_replace("\xEF\xBB\xBF", "", $h);
                    $clean_headers[] = trim(mb_strtolower($h, 'UTF-8'));
                }
                
                // Dynamic index matching based on column headers
                foreach ($clean_headers as $idx => $title) {
                    if (str_contains($title, 'الاسم') || str_contains($title, 'name') || str_contains($title, 'username') || str_contains($title, 'اسم')) {
                        $name_idx = $idx;
                    }
                }
                foreach ($clean_headers as $idx => $title) {
                    if (str_contains($title, 'البريد') || str_contains($title, 'email') || str_contains($title, 'إيميل') || str_contains($title, 'ايميل')) {
                        $email_idx = $idx;
                    }
                }
                foreach ($clean_headers as $idx => $title) {
                    if (str_contains($title, 'الرقم') || str_contains($title, 'id') || str_contains($title, 'جامعي') || str_contains($title, 'وظيفي')) {
                        if ($idx !== $name_idx && $idx !== $email_idx) {
                            $id_idx = $idx;
                        }
                    }
                }
            }
            
            // Validation Patterns for Students
            $regex_cs = '/^\d{2}[01]09(01|06)\d{3}@st\.aabu\.edu\.jo$/';
            $regex_is = '/^\d{2}[01]09(02|04|07|09)\d{3}@st\.aabu\.edu\.jo$/';
            $regex_ai = '/^\d{2}[01]0908\d{3}@st\.aabu\.edu\.jo$/';
            
            // If role is supervisor, fetch the base sequential ID once before parsing the CSV
            $current_year = date("y");
            $next_serial = 0;
            if ($bulk_role == 'supervisor') {
                $stmt_max_id = mysqli_prepare($conn, "SELECT MAX(CAST(SUBSTRING(supervisor_id, 3) AS UNSIGNED)) AS max_serial FROM supervisors WHERE supervisor_id LIKE CONCAT(?, '%') AND supervisor_id REGEXP '^[0-9]+$'");
                mysqli_stmt_bind_param($stmt_max_id, "s", $current_year);
                mysqli_stmt_execute($stmt_max_id);
                $row_max_id = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_max_id));
                $next_serial = ($row_max_id['max_serial'] === null) ? 0 : intval($row_max_id['max_serial']) + 1;
            }

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                // Skip empty lines
                if (empty($data) || count($data) < 2) continue;
                
                // Convert non-UTF8 encodings (like Excel Windows-1256 Arabic) and remove UTF-8 BOM
                foreach ($data as $key => $val) {
                    if (!mb_check_encoding($val, 'UTF-8')) {
                        // Use iconv as it has native support for WINDOWS-1256 on all PHP/Windows installations
                        if (function_exists('iconv')) {
                            $converted = iconv("WINDOWS-1256", "UTF-8//IGNORE", $val);
                            if ($converted !== false) {
                                $val = $converted;
                            }
                        }
                    }
                    // Remove UTF-8 Byte Order Mark (BOM) if present
                    $val = str_replace("\xEF\xBB\xBF", "", $val);
                    $data[$key] = $val;
                }
                
                $username = isset($data[$name_idx]) ? trim($data[$name_idx]) : '';
                
                // Advanced Sanitization: Strip trailing whitespaces, tabs, newlines, carriage returns, Unicode control codes, non-breaking spaces (NBSPs), and zero-width spaces
                $email = isset($data[$email_idx]) ? preg_replace('/[\s\x00-\x1F\x7F-\x9F\x{00A0}\x{200B}]/u', '', $data[$email_idx]) : '';
                $id_val = isset($data[$id_idx]) ? preg_replace('/[\s\x00-\x1F\x7F-\x9F\x{00A0}\x{200B}]/u', '', $data[$id_idx]) : '';
                
                // Extract student ID from email prefix if ID is empty or contains email format
                if ($bulk_role == 'student' && !empty($email)) {
                    if (empty($id_val)) {
                        $id_val = explode('@', $email)[0];
                    } elseif (str_contains($id_val, '@')) {
                        $id_val = explode('@', $id_val)[0];
                    }
                }

                if ($bulk_role == 'student') {
                    if (empty($username) || empty($email) || empty($id_val)) {
                        $errors[] = $lang == 'ar' ? "⚠️ حقل ناقص في السطر: " . implode(", ", $data) : "⚠️ Missing field in row: " . implode(", ", $data);
                        $failed_count++;
                        continue;
                    }
                } else {
                    if (empty($username) || empty($email)) {
                        $errors[] = $lang == 'ar' ? "⚠️ حقل ناقص في السطر: " . implode(", ", $data) : "⚠️ Missing field in row: " . implode(", ", $data);
                        $failed_count++;
                        continue;
                    }
                }
                
                // Validate email pattern based on chosen role
                $valid = true;
                if ($bulk_role == 'student') {
                    if ($bulk_dept == 'cs' && !preg_match($regex_cs, $email)) {
                        $valid = false;
                        $errors[] = "⚠️ $email: " . ($lang == 'ar' ? "لا يطابق صيغة إيميل علوم الحاسوب (01)" : "Does not match Computer Science format (01)");
                    } elseif ($bulk_dept == 'is' && !preg_match($regex_is, $email)) {
                        $valid = false;
                        $errors[] = "⚠️ $email: " . ($lang == 'ar' ? "لا يطابق صيغة إيميل نظم المعلومات (02/04/07/09)" : "Does not match Information Systems format (02/04/07/09)");
                    } elseif ($bulk_dept == 'ai' && !preg_match($regex_ai, $email)) {
                        $valid = false;
                        $errors[] = "⚠️ $email: " . ($lang == 'ar' ? "لا يطابق صيغة إيميل الذكاء الاصطناعي (08)" : "Does not match AI format (08)");
                    }
                    
                    if (!$valid) {
                        $failed_count++;
                        continue;
                    }
                    
                    // Validate university ID corresponds to email prefix
                    $extracted_id = explode('@', $email)[0];
                    if ($extracted_id !== $id_val) {
                        $errors[] = "⚠️ $id_val: " . ($lang == 'ar' ? "الرقم الجامعي لا يطابق البادئة في البريد الإلكتروني $extracted_id!" : "Student ID does not match email prefix $extracted_id!");
                        $failed_count++;
                        continue;
                    }
                    
                    // Check if already registered
                    $check_s = mysqli_prepare($conn, "SELECT email FROM students WHERE email=? OR student_id=?");
                    mysqli_stmt_bind_param($check_s, "ss", $email, $id_val);
                    mysqli_stmt_execute($check_s);
                    $res_s = mysqli_stmt_get_result($check_s);
                    
                    $check_d = mysqli_prepare($conn, "SELECT email FROM supervisors WHERE email=?");
                    mysqli_stmt_bind_param($check_d, "s", $email);
                    mysqli_stmt_execute($check_d);
                    $res_d = mysqli_stmt_get_result($check_d);
                    
                    if (mysqli_num_rows($res_s) > 0 || mysqli_num_rows($res_d) > 0) {
                        $errors[] = "⚠️ $email / $id_val: " . ($lang == 'ar' ? "مسجل مسبقاً في النظام!" : "Already registered in the system!");
                        $failed_count++;
                        continue;
                    }
                    
                    // Hash default password 'st@12345'
                    $default_pass = password_hash('st@12345', PASSWORD_DEFAULT);
                    $dept_map = ['cs' => '01', 'is' => '02', 'ai' => '08'];
                    $dept_code = $dept_map[$bulk_dept] ?? '01';
                    
                    $ins_stmt = mysqli_prepare($conn, "INSERT INTO students (student_id, name, email, password, dept_code) VALUES (?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($ins_stmt, "sssss", $id_val, $username, $email, $default_pass, $dept_code);
                    if (mysqli_stmt_execute($ins_stmt)) {
                        $success_count++;
                    } else {
                        $errors[] = "⚠️ " . mysqli_error($conn);
                        $failed_count++;
                    }
                } else {
                    // For Supervisors
                    // Validate supervisor email ends with @aabu.edu.jo or @st.aabu.edu.jo
                    if (!str_ends_with($email, "@aabu.edu.jo") && !str_ends_with($email, "@st.aabu.edu.jo")) {
                        $errors[] = "⚠️ $email: " . ($lang == 'ar' ? "بريد المشرف الإلكتروني يجب أن ينتهي بـ @aabu.edu.jo" : "Supervisor email must end with @aabu.edu.jo");
                        $failed_count++;
                        continue;
                    }
                    
                    // Check if already registered
                    $check_s = mysqli_prepare($conn, "SELECT email FROM students WHERE email=?");
                    mysqli_stmt_bind_param($check_s, "s", $email);
                    mysqli_stmt_execute($check_s);
                    $res_s = mysqli_stmt_get_result($check_s);
                    
                    // Check if already registered
                    $check_d = mysqli_prepare($conn, "SELECT email FROM supervisors WHERE email=?");
                    mysqli_stmt_bind_param($check_d, "s", $email);
                    mysqli_stmt_execute($check_d);
                    $res_d = mysqli_stmt_get_result($check_d);
                    
                    if (mysqli_num_rows($res_s) > 0 || mysqli_num_rows($res_d) > 0) {
                        $errors[] = "⚠️ $email: " . ($lang == 'ar' ? "مسجل مسبقاً في النظام!" : "Already registered in the system!");
                        $failed_count++;
                        continue;
                    }
                    
                    // Generate supervisor ID
                    $supervisor_id = $current_year . str_pad($next_serial, 2, "0", STR_PAD_LEFT);
                    
                    // Hash default password 'doc@12345'
                    $default_pass = password_hash('dr@12345', PASSWORD_DEFAULT);
                    $dept_map = ['cs' => '01', 'is' => '02', 'ai' => '08'];
                    $dept_code = $dept_map[$bulk_dept] ?? '01';
                    
                    $ins_stmt = mysqli_prepare($conn, "INSERT INTO supervisors (supervisor_id, name, email, password, dept_code) VALUES (?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($ins_stmt, "sssss", $supervisor_id, $username, $email, $default_pass, $dept_code);
                    if (mysqli_stmt_execute($ins_stmt)) {
                        $success_count++;
                        $next_serial++;
                    } else {
                        $errors[] = "⚠️ " . mysqli_error($conn);
                        $failed_count++;
                    }
                }
            }
            fclose($handle);
            
            $_SESSION['msg'] = $lang == 'ar' 
                ? "تقرير الرفع الجماعي: تم إضافة $success_count مستخدم بنجاح. فشل إضافة $failed_count مستخدم." 
                : "Bulk Upload Report: Successfully added $success_count users. Failed to add $failed_count users.";
                
            if (!empty($errors)) {
                $_SESSION['bulk_errors'] = $errors;
            } else {
                unset($_SESSION['bulk_errors']);
            }
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $_SESSION['msg'] = $lang == 'ar' ? "⚠️ فشل في قراءة الملف أو لم يتم اختياره!" : "⚠️ File upload failed or no file selected!";
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // ---------------------------------------------------------
    // 2. كود حذف مستخدم
    // ---------------------------------------------------------
    if (isset($_GET['delete_id']) && isset($_GET['delete_role'])) {
        $id = $_GET['delete_id'];
        $role = $_GET['delete_role'];
        
        if ($role == 'student') {
            // Delete projects first to avoid foreign key errors
            $stmt_p1_del = mysqli_prepare($conn, "DELETE FROM project_1 WHERE student_id=?");
            mysqli_stmt_bind_param($stmt_p1_del, "s", $id);
            mysqli_stmt_execute($stmt_p1_del);
            
            $stmt_p2_del = mysqli_prepare($conn, "DELETE FROM project_2 WHERE student_id=?");
            mysqli_stmt_bind_param($stmt_p2_del, "s", $id);
            mysqli_stmt_execute($stmt_p2_del);
            
            // Delete student
            $stmt_del = mysqli_prepare($conn, "DELETE FROM students WHERE student_id=?");
            mysqli_stmt_bind_param($stmt_del, "s", $id);
            mysqli_stmt_execute($stmt_del);
        } elseif ($role == 'supervisor') {
            // Set supervisor to NULL in projects
            $stmt_p1_upd = mysqli_prepare($conn, "UPDATE project_1 SET supervisor_id=NULL WHERE supervisor_id=?");
            mysqli_stmt_bind_param($stmt_p1_upd, "s", $id);
            mysqli_stmt_execute($stmt_p1_upd);
            
            $stmt_p2_upd = mysqli_prepare($conn, "UPDATE project_2 SET supervisor_id=NULL WHERE supervisor_id=?");
            mysqli_stmt_bind_param($stmt_p2_upd, "s", $id);
            mysqli_stmt_execute($stmt_p2_upd);
            
            // Delete supervisor
            $stmt_del = mysqli_prepare($conn, "DELETE FROM supervisors WHERE supervisor_id=?");
            mysqli_stmt_bind_param($stmt_del, "s", $id);
            mysqli_stmt_execute($stmt_del);
        }

        header("Location: admin_dashboard.php?msg=" . ($lang == 'ar' ? "تم الحذف بنجاح" : "Deleted successfully"));
        exit();
    }
    
    // كود إضافة إعلان جديد
    if (isset($_POST['add_announcement'])) {
        $title   = trim($_POST['ann_title']);
        $content = trim($_POST['ann_content']);

        $sql_ann = "INSERT INTO announcements (title, content) VALUES (?, ?)";
        $stmt_ann = mysqli_prepare($conn, $sql_ann);
        mysqli_stmt_bind_param($stmt_ann, "ss", $title, $content);
        if (mysqli_stmt_execute($stmt_ann)) {
            $_SESSION['msg'] = ($lang == 'ar' ? "✅ تم نشر الإعلان بنجاح!" : "✅ Announcement published!");
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // حذف إعلان
    if (isset($_GET['delete_ann'])) {
        $ann_id = $_GET['delete_ann'];
        $sql_del_ann = "DELETE FROM announcements WHERE ann_id=?";
        $stmt_del_ann = mysqli_prepare($conn, $sql_del_ann);
        mysqli_stmt_bind_param($stmt_del_ann, "i", $ann_id);
        mysqli_stmt_execute($stmt_del_ann);
        header("Location: admin_dashboard.php?msg=" . ($lang == 'ar' ? 'تم حذف الإعلان!' : 'Announcement deleted!'));
        exit();
    }

    // جلب الإعلانات
    $stmt_ann_list = mysqli_prepare($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
    mysqli_stmt_execute($stmt_ann_list);
    $res_ann = mysqli_stmt_get_result($stmt_ann_list);

    // جلب مشاريع التخرج 1
    $stmt_proj1 = mysqli_prepare($conn, "SELECT '1' AS project_type, p.project_id, p.project_title, p.grade, p.supervisor_id, p.evaluated_at, s.name AS student_name, d.name AS supervisor_name FROM project_1 p JOIN students s ON p.student_id = s.student_id LEFT JOIN supervisors d ON p.supervisor_id = d.supervisor_id ORDER BY p.project_id DESC");
    mysqli_stmt_execute($stmt_proj1);
    $result_projects_1 = mysqli_stmt_get_result($stmt_proj1);

    // جلب مشاريع التخرج 2
    $stmt_proj2 = mysqli_prepare($conn, "SELECT '2' AS project_type, p.project_id, p.project_title, p.grade, p.supervisor_id, p.evaluated_at, s.name AS student_name, d.name AS supervisor_name FROM project_2 p JOIN students s ON p.student_id = s.student_id LEFT JOIN supervisors d ON p.supervisor_id = d.supervisor_id ORDER BY p.project_id DESC");
    mysqli_stmt_execute($stmt_proj2);
    $result_projects_2 = mysqli_stmt_get_result($stmt_proj2);

    // جلب المشرفين
    $stmt_docs = mysqli_prepare($conn, "SELECT supervisor_id AS id, name, email, dept_code AS department FROM supervisors ORDER BY supervisor_id DESC");
    mysqli_stmt_execute($stmt_docs);
    $result_supervisors = mysqli_stmt_get_result($stmt_docs);

    // جلب الطلاب
    $stmt_studs = mysqli_prepare($conn, "SELECT student_id AS id, name, email, dept_code AS department FROM students ORDER BY student_id DESC");
    mysqli_stmt_execute($stmt_studs);
    $result_students = mysqli_stmt_get_result($stmt_studs);
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
<body>
    <div class="container">
        <div class="header-actions">
            <div style="display: flex; gap: 10px;">
                <a href="sign_out.php" class="logout"><?php echo T('logout'); ?></a>
                <a href="?change_lang=<?php echo $lang == 'ar' ? 'en' : 'ar'; ?>" class="lang-btn" style="padding: 8px 15px; border-radius: 5px; text-decoration: none;">
                    <?php echo T('switch_lang'); ?>
                </a>
            </div>
            <h4 class="subtitle"><?php echo ($lang == 'ar' ? 'كلية: ' : 'College: ') . T(strtolower($admin_college)); ?></h4>
        </div>

        <h1><?php echo T('admin_panel'); ?> ⚙️</h1>

        <?php if (isset($msg)) { echo "<p style='text-align:center; font-weight:bold; color:blue;'>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</p>"; } ?>
        <?php if (isset($_GET['msg'])) { echo "<p style='text-align:center; font-weight:bold; color:green;'>" . htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8') . "</p>"; } ?>

        <!-- 1. نموذج إضافة مستخدم يدوي -->
        <div class="collapsible-header" onclick="toggleCollapsible(this)">
            <span><?php echo T('add_user'); ?> (<?php echo $lang == 'ar' ? 'يدوي' : 'Manual'; ?>)</span>
            <i class="fa-solid fa-chevron-down accordion-icon"></i>
        </div>
        <div class="collapsible-content">
            <form action="admin_dashboard.php" method="POST">
                <input type="text" name="name" placeholder="<?php echo T('name'); ?>" required>
                <input type="email" name="email" placeholder="<?php echo T('email'); ?>" required>
                <div class="password-wrapper">
                    <input type="password" name="pass" id="pass" placeholder="<?php echo T('password'); ?>" required>
                    <span class="toggle-password" onclick="togglePass('pass', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </span>
                </div>
                
                <div class="password-wrapper">
                    <input type="password" name="confirm_pass" id="confirm_pass" placeholder="<?php echo $lang == 'ar' ? 'تأكيد كلمة المرور' : 'Confirm Password'; ?>" required>
                    <span class="toggle-password" onclick="togglePass('confirm_pass', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </span>
                </div>
                
                <select name="role" required>
                    <option value="student"><?php echo T('student'); ?></option>
                    <option value="supervisor"><?php echo T('supervisor'); ?></option>
                </select>

                <select name="department" required>
                    <option value="" disabled selected>-- <?php echo T('department'); ?> --</option>
                    <option value="cs"><?php echo T('cs'); ?></option>
                    <option value="is"><?php echo T('is'); ?></option>
                    <option value="ai"><?php echo T('ai'); ?></option>
                </select>

                <input type="hidden" name="college" value="<?php echo $admin_college; ?>">
                <button type="submit" name="add_user" class="btn-add" style="margin-top: 10px;"><?php echo $lang == 'ar' ? 'حفظ وإضافة' : 'Save & Add'; ?></button>
            </form>
        </div>

        <!-- 2. نموذج رفع جماعي للمستخدمين عبر Excel/CSV -->
        <div class="collapsible-header" onclick="toggleCollapsible(this)">
            <span><?php echo $lang == 'ar' ? 'رفع جماعي للمستخدمين (CSV)' : 'Bulk User Upload (CSV)'; ?></span>
            <i class="fa-solid fa-chevron-down accordion-icon"></i>
        </div>
        <div class="collapsible-content">
            <p id="bulk-upload-instructions" style="font-size: 13px; color: #555; margin-bottom: 15px; background: #eef2f5; padding: 10px; border-radius: 6px; line-height: 1.5;">
                <?php echo $lang == 'ar' 
                    ? "ℹ️ يرجى رفع ملف CSV يحتوي على الأعمدة التالية بالترتيب: <strong>(الاسم الكامل، البريد الإلكتروني، الرقم الجامعي)</strong>."
                    : "ℹ️ Please upload a CSV file with these columns in order: <strong>(Full Name, Email, University ID)</strong>."; 
                ?>
            </p>
            
            <form action="admin_dashboard.php" method="POST" enctype="multipart/form-data">
                <div class="bulk-container">
                    <i class="fa-solid fa-file-excel" style="font-size: 40px; color: #2e7d32; margin-bottom: 10px;"></i>
                    <br>
                    <input type="file" name="student_file" accept=".csv" required style="font-size: 14px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-weight: bold; font-size: 14px; margin-bottom: 5px; display: block; color: #1a3c40;">
                        <?php echo $lang == 'ar' ? 'نوع المستخدمين المرفوعين:' : 'Type of Uploaded Users:'; ?>
                    </label>
                    <div style="display: flex; gap: 20px; align-items: center; margin-top: 5px;">
                        <label style="cursor: pointer; font-size: 14px; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="bulk_role" value="student" checked>
                            <?php echo $lang == 'ar' ? 'طلاب' : 'Students'; ?>
                        </label>
                        <label style="cursor: pointer; font-size: 14px; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="bulk_role" value="supervisor">
                            <?php echo $lang == 'ar' ? 'مشرفين' : 'Supervisors'; ?>
                        </label>
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="font-weight: bold; font-size: 14px; margin-bottom: 5px; display: block; color: #1a3c40;">
                        <?php echo T('department'); ?>:
                    </label>
                    <select name="bulk_department" required style="width: 100%; max-width: 300px; padding: 10px;">
                        <option value="cs"><?php echo T('cs'); ?></option>
                        <option value="is"><?php echo T('is'); ?></option>
                        <option value="ai"><?php echo T('ai'); ?></option>
                    </select>
                </div>
                
                <button type="submit" name="bulk_upload" class="btn-save" style="width: auto; background: #2e7d32;"><?php echo $lang == 'ar' ? 'بدء الرفع الجماعي' : 'Start Bulk Upload'; ?></button>
            </form>
            
            <?php if (isset($_SESSION['bulk_errors']) && !empty($_SESSION['bulk_errors'])) { ?>
                <div style="margin-top: 20px;">
                    <h4 style="color: #c53030;"><?php echo $lang == 'ar' ? 'سجل الأخطاء أثناء الرفع:' : 'Upload Error Log:'; ?></h4>
                    <div class="error-list">
                        <ul>
                            <?php foreach ($_SESSION['bulk_errors'] as $err) {
                                echo "<li>" . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . "</li>";
                            } ?>
                        </ul>
                    </div>
                </div>
                <?php unset($_SESSION['bulk_errors']); ?>
            <?php } ?>
        </div>

        <!-- 3. نموذج إدارة الإعلانات العامة -->
        <div class="collapsible-header" onclick="toggleCollapsible(this)">
            <span><?php echo $lang == 'ar' ? 'إدارة الإعلانات العامة' : 'Manage Public Announcements'; ?></span>
            <i class="fa-solid fa-chevron-down accordion-icon"></i>
        </div>
        <div class="collapsible-content">
            <form method="POST">
                <input type="text" name="ann_title" placeholder="<?php echo $lang == 'ar' ? 'عنوان الإعلان' : 'Announcement Title'; ?>" required>
                <textarea name="ann_content" placeholder="<?php echo $lang == 'ar' ? 'محتوى الإعلان...' : 'Announcement Content...'; ?>" required style="height: 80px;"></textarea>
                <button type="submit" name="add_announcement" class="btn-save" style="width: auto; margin-top: 10px;"><?php echo $lang == 'ar' ? 'نشر الإعلان' : 'Publish Announcement'; ?></button>
            </form>
            
            <div style="margin-top: 20px;">
                <table style="font-size: 13px;" id="announcements_table">
                    <thead>
                        <tr>
                            <th><?php echo T('project_title'); ?></th>
                            <th><?php echo T('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($ann = mysqli_fetch_assoc($res_ann)) { ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td>
                                    <a href="admin_dashboard.php?delete_ann=<?php echo $ann['ann_id']; ?>" style="color:red; text-decoration:none;" onclick="return confirm('<?php echo $lang == 'ar' ? 'هل تريد حذف الإعلان؟' : 'Delete announcement?'; ?>')">❌</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <hr style="border: 1px solid #ddd; margin: 30px 0;">

        <!-- جدول مشاريع تخرج 1 -->
        <h3 style="color: #006064; margin-top: 30px;"><?php echo ($lang == 'ar' ? 'مشاريع تخرج 1 - كلية ' : 'Graduation Projects 1 - ') . T(strtolower($admin_college)); ?>:</h3>
        
        <div class="table-actions-bar">
            <input type="text" id="search_projects_1" class="search-input" onkeyup="filterTable('projects_1_table', 'search_projects_1')" placeholder="<?php echo $lang == 'ar' ? 'ابحث في مشاريع تخرج 1...' : 'Search project 1...'; ?>">
            <button onclick="printTable('projects_1_table', '<?php echo $lang == 'ar' ? 'تقرير مشاريع تخرج 1 - كلية تكنولوجيا المعلومات' : 'Graduation Projects 1 Report - IT College'; ?>')" class="btn-print">
                <i class="fa-solid fa-print"></i> <?php echo $lang == 'ar' ? 'طباعة التقرير' : 'Print Report'; ?>
            </button>
        </div>

        <div class="table-container" style="max-height: 350px !important; overflow-y: auto !important; border: 2px solid #ddd !important; border-radius: 12px; margin-bottom: 40px; background: #fff;">
            <table style="margin: 0; width: 100%;" id="projects_1_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo $lang == 'ar' ? 'اسم الطالب' : 'Student Name'; ?></th>
                        <th><?php echo T('project_title'); ?></th>
                        <th><?php echo T('supervisor'); ?></th>
                        <th><?php echo T('status'); ?></th>
                        <th><?php echo T('grade'); ?></th>
                        <th><?php echo $lang == 'ar' ? 'تاريخ آخر تقييم/مراجعة' : 'Last Evaluation/Review Date'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_projects_1) > 0) { 
                        while ($proj = mysqli_fetch_assoc($result_projects_1)) { ?>
                            <tr>
                                <td style="font-weight: bold; color: #006064;">#<?php echo htmlspecialchars($proj['project_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><strong><?php echo htmlspecialchars($proj['student_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td style="color:#d4af37; font-weight:bold;">
                                    <?php echo htmlspecialchars($proj['project_title'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="phase-badge phase-1">
                                        <?php echo T('project') . ' 1'; ?>
                                    </span>
                                </td>
                                <td><?php echo $proj['supervisor_name'] ? htmlspecialchars($proj['supervisor_name'], ENT_QUOTES, 'UTF-8') : "--"; ?></td>
                                <td>
                                    <?php 
                                    if (!empty($proj['grade'])) {
                                        echo "<span class='status-badge status-done'>" . ($lang == 'ar' ? 'تم التقييم' : 'Graded') . "</span>";
                                    } elseif ($proj['supervisor_id'] != NULL) {
                                        echo "<span class='status-badge status-working'>" . ($lang == 'ar' ? 'قيد العمل' : 'In Progress') . "</span>";
                                    } else {
                                        echo "<span class='status-badge status-available'>" . ($lang == 'ar' ? 'متاح' : 'Available') . "</span>";
                                    }
                                    ?>
                                </td>
                                <td style="font-weight:bold; font-size:16px;">
                                    <?php echo !empty($proj['grade']) ? "<span style='color: #2e7d32;'>" . htmlspecialchars($proj['grade'], ENT_QUOTES, 'UTF-8') . " / 100</span>" : "--"; ?>
                                </td>
                                <td style="color: #666; font-size: 13px; font-weight: bold;">
                                    <?php echo !empty($proj['evaluated_at']) ? htmlspecialchars($proj['evaluated_at'], ENT_QUOTES, 'UTF-8') : "--"; ?>
                                </td>
                            </tr>
                    <?php } 
                    } else { ?>
                        <tr><td colspan="7"><?php echo $lang == 'ar' ? 'لا توجد مشاريع مسجلة بعد.' : 'No projects registered yet.'; ?></td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <hr style="border: 1px solid #ddd; margin: 30px 0;">

        <!-- جدول مشاريع تخرج 2 -->
        <h3 style="color: #006064;"><?php echo ($lang == 'ar' ? 'مشاريع تخرج 2 - كلية ' : 'Graduation Projects 2 - ') . T(strtolower($admin_college)); ?>:</h3>
        
        <div class="table-actions-bar">
            <input type="text" id="search_projects_2" class="search-input" onkeyup="filterTable('projects_2_table', 'search_projects_2')" placeholder="<?php echo $lang == 'ar' ? 'ابحث في مشاريع تخرج 2...' : 'Search project 2...'; ?>">
            <button onclick="printTable('projects_2_table', '<?php echo $lang == 'ar' ? 'تقرير مشاريع تخرج 2 - كلية تكنولوجيا المعلومات' : 'Graduation Projects 2 Report - IT College'; ?>')" class="btn-print">
                <i class="fa-solid fa-print"></i> <?php echo $lang == 'ar' ? 'طباعة التقرير' : 'Print Report'; ?>
            </button>
        </div>

        <div class="table-container" style="max-height: 350px !important; overflow-y: auto !important; border: 2px solid #ddd !important; border-radius: 12px; margin-bottom: 40px; background: #fff;">
            <table style="margin: 0; width: 100%;" id="projects_2_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo $lang == 'ar' ? 'اسم الطالب' : 'Student Name'; ?></th>
                        <th><?php echo T('project_title'); ?></th>
                        <th><?php echo T('supervisor'); ?></th>
                        <th><?php echo T('status'); ?></th>
                        <th><?php echo T('grade'); ?></th>
                        <th><?php echo $lang == 'ar' ? 'تاريخ آخر تقييم/مراجعة' : 'Last Evaluation/Review Date'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_projects_2) > 0) { 
                        while ($proj = mysqli_fetch_assoc($result_projects_2)) { ?>
                            <tr>
                                <td style="font-weight: bold; color: #006064;">#<?php echo htmlspecialchars($proj['project_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><strong><?php echo htmlspecialchars($proj['student_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td style="color:#d4af37; font-weight:bold;">
                                    <?php echo htmlspecialchars($proj['project_title'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="phase-badge phase-2">
                                        <?php echo T('project') . ' 2'; ?>
                                    </span>
                                </td>
                                <td><?php echo $proj['supervisor_name'] ? htmlspecialchars($proj['supervisor_name'], ENT_QUOTES, 'UTF-8') : "--"; ?></td>
                                <td>
                                    <?php 
                                    if (!empty($proj['grade'])) {
                                        echo "<span class='status-badge status-done'>" . ($lang == 'ar' ? 'تم التقييم' : 'Graded') . "</span>";
                                    } elseif ($proj['supervisor_id'] != NULL) {
                                        echo "<span class='status-badge status-working'>" . ($lang == 'ar' ? 'قيد العمل' : 'In Progress') . "</span>";
                                    } else {
                                        echo "<span class='status-badge status-available'>" . ($lang == 'ar' ? 'متاح' : 'Available') . "</span>";
                                    }
                                    ?>
                                </td>
                                <td style="font-weight:bold; font-size:16px;">
                                    <?php echo !empty($proj['grade']) ? "<span style='color: #2e7d32;'>" . htmlspecialchars($proj['grade'], ENT_QUOTES, 'UTF-8') . " / 100</span>" : "--"; ?>
                                </td>
                                <td style="color: #666; font-size: 13px; font-weight: bold;">
                                    <?php echo !empty($proj['evaluated_at']) ? htmlspecialchars($proj['evaluated_at'], ENT_QUOTES, 'UTF-8') : "--"; ?>
                                </td>
                            </tr>
                    <?php } 
                    } else { ?>
                        <tr><td colspan="7"><?php echo $lang == 'ar' ? 'لا توجد مشاريع مسجلة بعد.' : 'No projects registered yet.'; ?></td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- جدول المشرفين -->
        <h3 style="color: #1a3c40;"><?php echo $lang == 'ar' ? 'أعضاء هيئة التدريس (المشرفين):' : 'Faculty Members (Supervisors):'; ?></h3>
        
        <div class="table-actions-bar">
            <input type="text" id="search_supervisors" class="search-input" onkeyup="filterTable('supervisors_table', 'search_supervisors')" placeholder="<?php echo $lang == 'ar' ? 'ابحث عن مشرف...' : 'Search supervisors...'; ?>">
            <button onclick="printTable('supervisors_table', '<?php echo $lang == 'ar' ? 'تقرير أعضاء هيئة التدريس (المشرفين)' : 'Faculty Members (Supervisors) Report'; ?>')" class="btn-print">
                <i class="fa-solid fa-print"></i> <?php echo $lang == 'ar' ? 'طباعة التقرير' : 'Print Report'; ?>
            </button>
        </div>

        <div class="table-container" style="max-height: 350px !important; overflow-y: auto !important; border: 2px solid #ddd !important; border-radius: 12px; margin-bottom: 40px; background: #fff;">
            <table style="margin: 0; width: 100%;" id="supervisors_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo T('name'); ?></th>
                        <th><?php echo T('email'); ?></th>
                        <th><?php echo T('department'); ?></th>
                        <th><?php echo T('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result_supervisors)) { 
                    $dept_map = ['01' => 'cs', '02' => 'is', '08' => 'ai'];
                    $dept_key = $dept_map[$row['department']] ?? $row['department'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo T($dept_key); ?></td>
                        <td>
                            <a href="admin_dashboard.php?delete_id=<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>&delete_role=supervisor" class="btn-del" onclick="return confirm('<?php echo $lang == 'ar' ? 'هل أنت متأكد؟' : 'Are you sure?'; ?>');"><?php echo T('delete'); ?></a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- جدول الطلاب -->
        <h3 style="color: #006064; margin-top: 40px;"><?php echo $lang == 'ar' ? 'قائمة الطلاب:' : 'Student List:'; ?></h3>
        
        <div class="table-actions-bar">
            <input type="text" id="search_students" class="search-input" onkeyup="filterTable('students_table', 'search_students')" placeholder="<?php echo $lang == 'ar' ? 'ابحث عن طالب...' : 'Search students...'; ?>">
            <button onclick="printTable('students_table', '<?php echo $lang == 'ar' ? 'تقرير الطلاب المسجلين' : 'Registered Students Report'; ?>')" class="btn-print">
                <i class="fa-solid fa-print"></i> <?php echo $lang == 'ar' ? 'طباعة التقرير' : 'Print Report'; ?>
            </button>
        </div>

        <div class="table-container" style="max-height: 350px !important; overflow-y: auto !important; border: 2px solid #ddd !important; border-radius: 12px; margin-bottom: 40px; background: #fff;">
            <table style="margin: 0; width: 100%;" id="students_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo T('name'); ?></th>
                        <th><?php echo T('email'); ?></th>
                        <th><?php echo T('department'); ?></th>
                        <th><?php echo T('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result_students)) { 
                    $dept_map = ['01' => 'cs', '02' => 'is', '08' => 'ai'];
                    $dept_key = $dept_map[$row['department']] ?? $row['department'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo T($dept_key); ?></td>
                        <td>
                            <a href="admin_dashboard.php?delete_id=<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>&delete_role=student" class="btn-del" onclick="return confirm('<?php echo $lang == 'ar' ? 'هل أنت متأكد؟' : 'Are you sure?'; ?>');"><?php echo T('delete'); ?></a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
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

    function toggleCollapsible(header) {
        var content = header.nextElementSibling;
        content.classList.toggle('active');
        var icon = header.querySelector('.accordion-icon');
        if (content.classList.contains('active')) {
            icon.style.transform = 'rotate(180deg)';
        } else {
            icon.style.transform = 'rotate(0deg)';
        }
    }

    function filterTable(tableId, inputId) {
        var input = document.getElementById(inputId);
        var filter = input.value.toLowerCase();
        var table = document.getElementById(tableId);
        var tr = table.getElementsByTagName("tr");
        
        for (var i = 1; i < tr.length; i++) {
            var show = false;
            var td = tr[i].getElementsByTagName("td");
            for (var j = 0; j < td.length; j++) {
                if (td[j]) {
                    var textValue = td[j].textContent || td[j].innerText;
                    if (textValue.toLowerCase().indexOf(filter) > -1) {
                        show = true;
                        break;
                    }
                }
            }
            tr[i].style.display = show ? "" : "none";
        }
    }

    function printTable(tableId, title) {
        var table = document.getElementById(tableId);
        var rows = table.getElementsByTagName("tr");
        
        var printContent = "<h2 style='text-align: center; font-family: Segoe UI, sans-serif; color: #1a3c40;'>" + title + "</h2>";
        printContent += "<table border='1' cellspacing='0' cellpadding='10' style='width:100%; border-collapse:collapse; font-family: Segoe UI, sans-serif; text-align:center;'>";
        
        printContent += "<thead>" + rows[0].innerHTML + "</thead><tbody>";
        
        var visibleRowCount = 0;
        for (var i = 1; i < rows.length; i++) {
            if (rows[i].style.display !== "none") {
                var newRowHtml = "";
                var cells = rows[i].getElementsByTagName("td");
                for (var j = 0; j < cells.length; j++) {
                    // Skip action column (last column) if table has action column
                    if (j === cells.length - 1 && (tableId === 'supervisors_table' || tableId === 'students_table' || tableId === 'announcements_table')) {
                        continue;
                    }
                    newRowHtml += "<td>" + cells[j].innerHTML + "</td>";
                }
                printContent += "<tr>" + newRowHtml + "</tr>";
                visibleRowCount++;
            }
        }
        
        if (visibleRowCount === 0) {
            printContent += "<tr><td colspan='100'>No results found</td></tr>";
        }
        
        printContent += "</tbody></table>";
        
        var win = window.open("", "", "width=900,height=700");
        win.document.write("<html><head><title>" + title + "</title>");
        var dir = document.documentElement.dir || "ltr";
        win.document.write("<style>body { direction: " + dir + "; font-family: sans-serif; padding: 20px; } table { width:100%; border-collapse:collapse; } th, td { border:1px solid #ddd; padding:8px; text-align:center; } th { background-color:#f2f2f2; }</style>");
        win.document.write("</head><body>");
        win.document.write(printContent);
        win.document.write("<script>window.onload = function() { window.print(); window.close(); }<\/script>");
        win.document.write("</body></html>");
        win.document.close();
    }

    document.querySelectorAll('input[name="bulk_role"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var instructions = document.getElementById('bulk-upload-instructions');
            if (this.value === 'student') {
                instructions.innerHTML = <?php echo json_encode($lang == 'ar' 
                    ? "ℹ️ يرجى رفع ملف CSV يحتوي على الأعمدة التالية بالترتيب: <strong>(الاسم الكامل، البريد الإلكتروني، الرقم الجامعي)</strong>." 
                    : "ℹ️ Please upload a CSV file with these columns in order: <strong>(Full Name, Email, University ID)</strong>."); ?>;
            } else {
                instructions.innerHTML = <?php echo json_encode($lang == 'ar' 
                    ? "ℹ️ يرجى رفع ملف CSV يحتوي على الأعمدة التالية: <strong>(الاسم الكامل، البريد الإلكتروني)</strong>. سيتم توليد الرقم الوظيفي تلقائياً." 
                    : "ℹ️ Please upload a CSV file with these columns: <strong>(Full Name, Email)</strong>. The Job ID will be generated automatically."); ?>;
            }
        });
    });
    </script>
</body>
</html>

<?php 
} else {
    header("Location: admin_portal.php");
    exit();
}
?>