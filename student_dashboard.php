<?php 
// إضافة هيدرز لمنع المتصفح من تخزين ملفات الصفحةالقديمة
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include "lang_config.php";
include "connection_settings.php"; 
//في حال كان الادمن هو من ضاف الطالب فبتالي يجب عليه تغيير كلمه السر
if (isset($_SESSION['needs_password_change']) && $_SESSION['needs_password_change'] == 1) {
    header("Location: change_password_first.php");
    exit();
}
if (isset($_SESSION['role']) && $_SESSION['role'] == 'student' && isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    // جلب بيانات الطالب
    $stmt_student = mysqli_prepare($conn, "SELECT student_id, name, dept_code FROM students WHERE email=?");
    mysqli_stmt_bind_param($stmt_student, "s", $email);
    mysqli_stmt_execute($stmt_student);
    $student_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_student));
    if (!$student_data) {
        header("Location: index.php");
        exit();
    }
    $student_id = $student_data['student_id'];
    $student_name = $student_data['name'];
    $dept_code = $student_data['dept_code'];
    //1جلب بيانات مشروع
    $stmt_p1 = mysqli_prepare($conn, "SELECT * FROM project_1 WHERE student_id=?");
    mysqli_stmt_bind_param($stmt_p1, "s", $student_id);
    mysqli_stmt_execute($stmt_p1);
    $proj1 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_p1));
//اذا كان الطالب من قسم العلم او النظم يكون قادر على تسجيل مشروع 2
    if (in_array($dept_code, ['01', '02'])) {
        $stmt_p2 = mysqli_prepare($conn, "SELECT * FROM project_2 WHERE student_id=?");
        mysqli_stmt_bind_param($stmt_p2, "s", $student_id);
        mysqli_stmt_execute($stmt_p2);
        $proj2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_p2));
        //اذا من قسم الذكاء لا يوجد لديه مشروع2
    } else {
        $proj2 = null;
    }
    // --- منطق معالجة الرفع والتعديل ---
    if (isset($_POST['submit_project']) || isset($_POST['update_project'])) {
        $p_type = $_POST['p_type']; 
        $table = ($p_type == 1) ? "project_1" : "project_2";
        $is_edit = isset($_POST['update_project']);
//اذا طالب من الذكاء حاول رفع مشروع 2 اي التلاعب بلفرونت هذا يمنعه
        if ($p_type == 2 && $dept_code == '08') {
            header("Location: student_dashboard.php?error=Access Denied");
            exit();
        }
        $title = trim($_POST['title']);
        $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';    
//اذا كان الوصف بلمشروع الاول اكثر من 200 حرف يتم منع ذلك
        if ($p_type == 1 && mb_strlen($desc, 'UTF-8') > 200) {
            header("Location: student_dashboard.php?error=" . ($lang == 'ar' ? "الوصف طويل جداً" : "Description too long"));
            exit();
        }
        $current_project = ($p_type == 1) ? $proj1 : $proj2;
        $new_file_name = ($is_edit && $current_project) ? (($p_type == 1) ? $current_project['project_file'] : $current_project['source_code_file']) : "";
        // رفع الملف الجديد إذا وُجد
        if (isset($_FILES['p_file']) && $_FILES['p_file']['error'] === 0) {
            $file_name = $_FILES['p_file']['name'];
            $tmp_name = $_FILES['p_file']['tmp_name'];
            //هذه الداله تعمل على تحويل الحروف الى احرف صغيره مثلا PDF-->pdf
            $file_ex = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            // للتخصصات التي ليس لديها مشروع 2 (غير 01 و02)، يُسمح برفع ZIP/RAR 
            $no_proj2_depts = !in_array($dept_code, ['01', '02']);
            if ($p_type == 1 && $no_proj2_depts) {
                $allowed = ['pdf', 'docx', 'zip', 'rar'];
            } else {
                $allowed = ($p_type == 1) ? ['pdf', 'docx'] : ['zip', 'rar'];
            }            
//هذه الداله تضمن علي بان لا يقوم مخترق برفع ملف خبيث
            if (in_array($file_ex, $allowed)) {
                //هذه من اجل اذا قام طالبين برفع ملفين بنفس الاسم
                $new_file_name = time() . "p" . $p_type . "" . $student_id . "." . $file_ex;
                if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                move_uploaded_file($tmp_name, 'uploads/' . $new_file_name);
            } else {
                header("Location: student_dashboard.php?error=Invalid file type");
                exit();
            }
        } elseif (!$is_edit) {
            header("Location: student_dashboard.php?error=File is required");
            exit();
        }
        if ($is_edit) {
            //اذا بدنا نعدل مشروع 1
            if ($p_type == 1) {
                $sql = "UPDATE project_1 SET project_title=?, project_desc=?, project_file=?, is_note_new=0 WHERE student_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssss", $title, $desc, $new_file_name, $student_id);
                //اذا بدنا نعدل مشروع2
            } else {
                $sql = "UPDATE project_2 SET project_title=?, source_code_file=?, is_note_new=0 WHERE student_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $title, $new_file_name, $student_id);
            }
            mysqli_stmt_execute($stmt);
            header("Location: student_dashboard.php?success=" . ($lang == 'ar' ? "تم تعديل بيانات المشروع بنجاح" : "Project updated successfully"));
            exit();
            //في حال كان الطالب يسجل لاول مره ويتم اعطاء المشروع رقم فريد
        } else {
            $sql_max = "SELECT MAX(CAST(SUBSTRING(project_id, 3, 2) AS UNSIGNED)) AS max_serial FROM $table WHERE project_id LIKE ?";
            $like_param = $dept_code . "%";
            $stmt_max = mysqli_prepare($conn, $sql_max);
            mysqli_stmt_bind_param($stmt_max, "s", $like_param);
            mysqli_stmt_execute($stmt_max);
            $row_max = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_max));
            $project_id = $dept_code . str_pad(intval($row_max['max_serial']) + 1, 2, "0", STR_PAD_LEFT);
//في حال كان الشمروع 1 يعمل له اضافه لبياناته وغير ذلك يعمل اضافه لبيانات المشروع 2
            if ($p_type == 1) {
                $sql = "INSERT INTO project_1 (project_id, student_id, project_title, project_desc, project_file) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssss", $project_id, $student_id, $title, $desc, $new_file_name);
            } else {
                // فحص تقاطعي إذا كان للمشروع مشرف متبني في مشروع 1 لربطه مباشرة بفصل منفصل
                $stmt_check_sup = mysqli_prepare($conn, "SELECT supervisor_id FROM project_1 WHERE student_id=?");
                mysqli_stmt_bind_param($stmt_check_sup, "s", $student_id);
                mysqli_stmt_execute($stmt_check_sup);
                $res_sup = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check_sup));
                $assigned_supervisor = ($res_sup && !empty($res_sup['supervisor_id'])) ? $res_sup['supervisor_id'] : NULL;
                $sql = "INSERT INTO project_2 (project_id, student_id, project_title, source_code_file, supervisor_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssss", $project_id, $student_id, $title, $new_file_name, $assigned_supervisor);
            }
            mysqli_stmt_execute($stmt);
            header("Location: student_dashboard.php?success=" . ($lang == 'ar' ? "تم التسليم بنجاح" : "Submitted successfully"));
            exit();
        }
    }
    //  تصفير الإشعارات بحيث اذا الدكتور اعطى تقييم الجرس رح يصير 1 لما اضغط عليه ينقلني للتقيم وبتصفر
    if (isset($_GET['clear_notifications'])) {
        $stmt_cn1 = mysqli_prepare($conn, "UPDATE project_1 SET is_note_new = 0 WHERE student_id=?");
        mysqli_stmt_bind_param($stmt_cn1, "s", $student_id);
        mysqli_stmt_execute($stmt_cn1);
        $stmt_cn2 = mysqli_prepare($conn, "UPDATE project_2 SET is_note_new = 0 WHERE student_id=?");
        mysqli_stmt_bind_param($stmt_cn2, "s", $student_id);
        mysqli_stmt_execute($stmt_cn2);
    }
    // جلب بيانات مشروع 1 مع اسم المشرف المشرف
    $stmt_proj1 = mysqli_prepare($conn, "SELECT p.*, d.name AS doc_name FROM project_1 p LEFT JOIN supervisors d ON p.supervisor_id = d.supervisor_id WHERE p.student_id=?");
    mysqli_stmt_bind_param($stmt_proj1, "s", $student_id);
    mysqli_stmt_execute($stmt_proj1);
    $proj1 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_proj1));
// جلب بيانات مشروع2 مع اسم المشرف
    $proj2 = null;
    if (in_array($dept_code, ['01', '02'])) {
        $stmt_proj2 = mysqli_prepare($conn, "SELECT p.*, d.name AS doc_name FROM project_2 p LEFT JOIN supervisors d ON p.supervisor_id = d.supervisor_id WHERE p.student_id=?");
        mysqli_stmt_bind_param($stmt_proj2, "s", $student_id);
        mysqli_stmt_execute($stmt_proj2);
        $proj2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_proj2));
    }
    $notif_count = 0;
    $scroll_target = ""; 
    if ($proj1 && $proj1['is_note_new'] == 1) { $notif_count++; $scroll_target = "#project1_section"; }
    if ($proj2 && $proj2['is_note_new'] == 1) { $notif_count++; $scroll_target = "#project2_section"; }
    $bell_link = "student_dashboard.php?clear_notifications=1" . $scroll_target;
    // جلب الإعلانات لم يتم تفعيله
    $stmt_anns = mysqli_prepare($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
    mysqli_stmt_execute($stmt_anns);
    $announcements = mysqli_stmt_get_result($stmt_anns);
} else { header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="<?php echo T('lang_code'); ?>" dir="<?php echo T('dir'); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo T('student_panel'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="top-bar">
            <h2><?php echo T('welcome') . $student_name; ?></h2>
            <div class="nav-right" style="gap: 10px;">
                <a href="<?php echo $bell_link; ?>" class="notif-wrapper" title="<?php echo $lang == 'ar' ? 'الملاحظات الجديدة' : 'New Notifications'; ?>" style="margin-inline-end: 10px;">
                    🔔
                    <?php if ($notif_count > 0): ?>
                        <span class="notif-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="change_password.php" class="btn" style="background:#f39c12; color:white; padding:10px 15px; border-radius:6px; text-decoration:none; display:flex; align-items:center; gap:5px;"><i class="fa-solid fa-key"></i> <?php echo $lang == 'ar' ? 'تغيير كلمة المرور' : 'Change Password'; ?></a>
                <a href="?change_lang=<?php echo $lang == 'ar' ? 'en' : 'ar'; ?>" class="lang-btn" style="text-decoration: none; padding: 10px 15px; border-radius: 6px;">
                    <?php echo T('switch_lang'); ?>
                </a>
                <a href="sign_out.php" class="btn btn-logout"><?php echo T('logout'); ?></a>
            </div>
        </div>
        <?php if (isset($_GET['success'])) echo "<div class='alert'>".htmlspecialchars($_GET['success'])."</div>"; ?>
        <?php if (isset($_GET['error'])) echo "<div class='alert-error'>".htmlspecialchars($_GET['error'])."</div>"; ?>
        <?php if (mysqli_num_rows($announcements) > 0): ?>
        <div class="announcements-section" style="margin-bottom: 25px; background: #fff; border-radius: 12px; padding: 20px 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-right: 5px solid #0288d1;">
            <h3 style="margin: 0 0 15px 0; color: #0288d1; display: flex; align-items: center; gap: 8px;">
                📢 <?php echo $lang == 'ar' ? 'إعلانات من الإدارة' : 'Admin Announcements'; ?>
            </h3>
            <?php while ($ann = mysqli_fetch_assoc($announcements)): ?>
            <div style="padding: 12px 15px; border-bottom: 1px solid #edf2f7; last-child:border-bottom:none;">
                <h4 style="margin: 0 0 5px 0; color: #1a3c40; font-size: 15px;">
                    🔔 <?php echo htmlspecialchars($ann['title']); ?>
                </h4>
                <p style="margin: 0 0 6px 0; color: #555; font-size: 14px; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                </p>
                <span style="font-size: 12px; color: #999;">
                    🕐 <?php echo date('Y-m-d H:i', strtotime($ann['created_at'])); ?>
                </span>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
        <div class="project-box" id="project1_section">
            <h3 style="color: #1a3c40;"><?php echo $lang == 'ar' ? 'مشروع تخرج (1) - التوثيق' : 'Graduation Project (1) - Documentation'; ?></h3>
            <hr>
            <?php if (!$proj1): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="p_type" value="1">
                    <label><?php echo $lang == 'ar' ? 'عنوان المشروع:' : 'Project Title:'; ?></label>
                    <input type="text" name="title" required>
                    <label><?php echo $lang == 'ar' ? 'وصف مختصر (حتى 200 حرف):' : 'Brief Description (up to 200 chars):'; ?></label>
                    <textarea name="desc" maxlength="200" required></textarea>
                    <?php if (!in_array($dept_code, ['01', '02'])): ?>
                    <label><?php echo $lang == 'ar' ? 'رفع الملف (PDF/DOCX للتوثيق أو ZIP/RAR للكود):' : 'Upload File (PDF/DOCX for docs or ZIP/RAR for code):'; ?></label>
                    <input type="file" name="p_file" accept=".pdf,.docx,.zip,.rar" required>
                    <small style="color:#888;"><?php echo $lang == 'ar' ? '* ارفع ملف التوثيق (PDF/DOCX) أو ملف الكود (ZIP/RAR)' : '* Upload documentation (PDF/DOCX) or code file (ZIP/RAR)'; ?></small>
                    <?php else: ?>
                    <label><?php echo $lang == 'ar' ? 'رفع ملف الدكيومنتيشن (PDF/DOCX):' : 'Upload Documentation File (PDF/DOCX):'; ?></label>
                    <input type="file" name="p_file" accept=".pdf,.docx" required>
                    <?php endif; ?>
                    <button type="submit" name="submit_project" class="btn btn-save"><?php echo $lang == 'ar' ? 'حفظ وتسجيل مشروع 1' : 'Save & Register Project 1'; ?></button>
                </form>
                    <?php elseif (isset($_GET['edit_p1'])): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="p_type" value="1">
                    <label><?php echo $lang == 'ar' ? 'تعديل عنوان المشروع:' : 'Edit Project Title:'; ?></label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($proj1['project_title']); ?>" required>
                    <label><?php echo $lang == 'ar' ? 'تعديل وصف المشروع:' : 'Edit Project Description:'; ?></label>
                    <textarea name="desc" maxlength="200" required><?php echo htmlspecialchars($proj1['project_desc']); ?></textarea>
                    <?php if (!in_array($dept_code, ['01', '02'])): ?>
                    <label><?php echo $lang == 'ar' ? 'تحديث الملف الحالي (PDF/DOCX/ZIP/RAR):' : 'Update Current File (PDF/DOCX/ZIP/RAR):'; ?></label>
                    <input type="file" name="p_file" accept=".pdf,.docx,.zip,.rar">
                    <?php else: ?>
                    <label><?php echo $lang == 'ar' ? 'تحديث الملف الحالي' : 'Update Current File'; ?></label>
                    <input type="file" name="p_file" accept=".pdf,.docx">
                    <?php endif; ?>
                    <button type="submit" name="update_project" class="btn btn-save" style="background:#2980b9;"><?php echo $lang == 'ar' ? 'حفظ التعديلات' : 'Save Changes'; ?></button>
                    <a href="student_dashboard.php" class="btn-cancel"><?php echo $lang == 'ar' ? 'إلغاء' : 'Cancel'; ?></a>
                </form>
                    <?php else: ?>
                <div class="info-display">
                    <p><strong><?php echo $lang == 'ar' ? 'رقم المشروع:' : 'Project ID:'; ?></strong> <span style="color:#e67e22;">#<?php echo $proj1['project_id']; ?></span></p>
                    <p><strong><?php echo $lang == 'ar' ? 'العنوان:' : 'Title:'; ?></strong> <?php echo htmlspecialchars($proj1['project_title']); ?></p>
                    <p><strong><?php echo $lang == 'ar' ? 'وصف المشروع:' : 'Description:'; ?></strong> <span style="color:#555; font-style:italic;"><?php echo !empty($proj1['project_desc']) ? htmlspecialchars($proj1['project_desc']) : ($lang == 'ar' ? 'لا يوجد وصف.' : 'No description.'); ?></span></p>
                    <p><strong><?php echo $lang == 'ar' ? 'ملف التوثيق:' : 'Documentation File:'; ?></strong> <a href="download.php?file=<?php echo urlencode($proj1['project_file']); ?>&name=<?php echo rawurlencode($student_name . ' - ' . $proj1['project_title'] . '.' . pathinfo($proj1['project_file'], PATHINFO_EXTENSION)); ?>" style="color: #3498db; text-decoration: none; font-weight:bold;"><?php echo $lang == 'ar' ? 'اضغط هنا لتحميل ملفك الحالي' : 'Click here to download your current file'; ?></a></p>                  
                    <a href="student_dashboard.php?edit_p1=1#project1_section" class="btn-edit"><?php echo $lang == 'ar' ? 'تعديل بيانات المشروع' : 'Edit Project Details'; ?></a>                   
                    <div class="notes-box" style="background:#f9f9f9; padding:15px; border:1px dashed #ccc; margin-top:15px; <?php echo ($proj1['is_note_new'] == 1) ? 'border-color: red; background: #fff5f5;' : ''; ?>">
                        <h4 style="margin:0;"><?php echo $lang == 'ar' ? 'ملاحظات المشرف والتقييم:' : 'Supervisor Notes & Evaluation:'; ?> 
                            <?php if($proj1['is_note_new'] == 1): ?> <span style="color:red; font-size:12px;"><?php echo $lang == 'ar' ? '(جديد)' : '(New)'; ?></span> <?php endif; ?>
                        </h4>
                        <p style="margin-top: 10px;"><strong><?php echo $lang == 'ar' ? 'المشرف:' : 'Supervisor Supervisor:'; ?></strong> <?php echo !empty($proj1['doc_name']) ? ($lang == 'ar' ? "د. " : "Dr. ") . htmlspecialchars($proj1['doc_name']) : "<span style='color:#7f8c8d; font-style:italic;'>" . ($lang == 'ar' ? "بانتظار تبني المشروع من قبل مشرف المساق" : "Waiting for project adoption by course supervisor") . "</span>"; ?></p>
                        <p><strong><?php echo $lang == 'ar' ? 'الملاحظات:' : 'Notes:'; ?></strong> <?php echo !empty($proj1['notes']) ? nl2br(htmlspecialchars($proj1['notes'])) : ($lang == 'ar' ? "لا توجد ملاحظات بعد." : "No notes yet."); ?></p>
                        <p><strong><?php echo $lang == 'ar' ? 'العلامة:' : 'Grade:'; ?></strong> <span style="font-size:20px; color:#d32f2f;"><?php echo !empty($proj1['grade']) ? $proj1['grade'] : "--"; ?> / 100</span></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
                            <?php if ($dept_code != '08'): ?>
            <?php if ($proj1): ?>
                <div class="project-box" id="project2_section" style="border-top-color: #27ae60;">
                    <h3 style="color: #27ae60;"><?php echo $lang == 'ar' ? 'مشروع تخرج (2) - الكود  (اختياري)' : 'Graduation Project (2) - Code (Optional)'; ?></h3>
                    <hr>
                    <?php if (!$proj2): ?>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="p_type" value="2">
                            <label><?php echo $lang == 'ar' ? 'عنوان النظام النهائي:' : 'Final System Title:'; ?></label>
                            <input type="text" name="title" required>
                            <label><?php echo $lang == 'ar' ? 'رفع ملف الكود (ZIP/RAR):' : 'Upload Code File (ZIP/RAR):'; ?></label>
                            <input type="file" name="p_file" accept=".zip,.rar" required>
                            <button type="submit" name="submit_project" class="btn btn-save" style="background:#27ae60;"><?php echo $lang == 'ar' ? 'رفع كود المشروع' : 'Upload Project Code'; ?></button>
                        </form>
                    <?php elseif (isset($_GET['edit_p2'])): ?>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="p_type" value="2">
                            <label><?php echo $lang == 'ar' ? 'تعديل اسم النظام النهائي:' : 'Edit Final System Title:'; ?></label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($proj2['project_title']); ?>" required>
                            <label><?php echo $lang == 'ar' ? 'تحديث ملف الكود (ZIP/RAR):' : 'Update Code File (ZIP/RAR):'; ?></label>
                            <input type="file" name="p_file" accept=".zip,.rar">
                            <button type="submit" name="update_project" class="btn btn-save" style="background:#27ae60;"><?php echo $lang == 'ar' ? 'حفظ التعديلات' : 'Save Changes'; ?></button>
                            <a href="student_dashboard.php" class="btn-cancel"><?php echo $lang == 'ar' ? 'إلغاء' : 'Cancel'; ?></a>
                        </form>
                    <?php else: ?>
                        <div class="info-display">
                            <p><strong><?php echo $lang == 'ar' ? 'رقم المشروع:' : 'Project ID:'; ?></strong> <span style="color:#27ae60;">#<?php echo $proj2['project_id']; ?></span></p>
                            <p><strong><?php echo $lang == 'ar' ? 'اسم النظام:' : 'System Title:'; ?></strong> <?php echo htmlspecialchars($proj2['project_title']); ?></p>
                            <p><strong><?php echo $lang == 'ar' ? 'الملف المرفوع:' : 'Uploaded File:'; ?></strong> <a href="download.php?file=<?php echo urlencode($proj2['source_code_file']); ?>&name=<?php echo rawurlencode($student_name . ' - ' . $proj2['project_title'] . '.' . pathinfo($proj2['source_code_file'], PATHINFO_EXTENSION)); ?>" style="color:#27ae60; text-decoration:none; font-weight:bold;"><?php echo $lang == 'ar' ? 'تحميل ملف الكود الحالي (ZIP)' : 'Download Current Code File (ZIP)'; ?></a></p>                           
                            <a href="student_dashboard.php?edit_p2=1#project2_section" class="btn-edit" style="background:#27ae60;"><?php echo $lang == 'ar' ? 'تعديل كود المشروع' : 'Edit Project Code'; ?></a>
                            <div class="notes-box" style="background:#f0fff4; padding:15px; border:1px dashed #27ae60; margin-top:15px; <?php echo ($proj2['is_note_new'] == 1) ? 'border-color: red; background: #fff5f5;' : ''; ?>">
                                <h4 style="margin:0;"><?php echo $lang == 'ar' ? 'تقييم الكود والمناقشة:' : 'Code Evaluation & Discussion:'; ?> 
                                    <?php if($proj2['is_note_new'] == 1): ?> <span style="color:red; font-size:12px;"><?php echo $lang == 'ar' ? '(جديد)' : '(New)'; ?></span> <?php endif; ?>
                                </h4>
                                <p style="margin-top: 10px;"><strong><?php echo $lang == 'ar' ? 'المشرف المشرف:' : 'Supervisor Supervisor:'; ?></strong> <?php echo !empty($proj2['doc_name']) ? ($lang == 'ar' ? "د. " : "Dr. ") . htmlspecialchars($proj2['doc_name']) : "<span style='color:#7f8c8d; font-style:italic;'>" . ($lang == 'ar' ? 'بانتظار تبني الكود من قبل مشرف المساق' : 'Waiting for code adoption by course supervisor') . "</span>"; ?></p>
                                <p><strong><?php echo $lang == 'ar' ? 'الملاحظات:' : 'Notes:'; ?></strong> <?php echo !empty($proj2['notes']) ? nl2br(htmlspecialchars($proj2['notes'])) : ($lang == 'ar' ? 'بانتظار مراجعة الكود.' : 'Waiting for code review.'); ?></p>
                                <p><strong><?php echo $lang == 'ar' ? 'العلامة :' : 'Grade :'; ?></strong> <span style="font-size:20px; color:#27ae60"><?php echo !empty($proj2['grade']) ? $proj2['grade'] : "--"; ?> / 100</span></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="project-box" style="opacity: 0.6; background: #eee;">
                    <h3><?php echo $lang == 'ar' ? 'مشروع تخرج (2)' : 'Graduation Project (2)'; ?></h3>
                    <p><?php echo $lang == 'ar' ? 'هذا القسم سيفتح تلقائياً بعد رفع متطلبات "مشروع 1".' : 'This section will automatically unlock after uploading "Project 1" requirements.'; ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>