<?php 
include "lang_config.php";
include "connection_settings.php";
//اجبار تغير كلمه المرور عندما تتم الاضافه من الادمن لاول مره
if (isset($_SESSION['needs_password_change']) && $_SESSION['needs_password_change'] == 1) {
    header("Location: change_password_first.php");
    exit();
}
//يتم جلب بيانات الدكتور من الداتا بيس
if (isset($_SESSION['role']) && $_SESSION['role'] == 'supervisor' && isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $stmt_doc = mysqli_prepare($conn, "SELECT supervisor_id, name, dept_code FROM supervisors WHERE email=?");
    mysqli_stmt_bind_param($stmt_doc, "s", $email);
    mysqli_stmt_execute($stmt_doc);
    $supervisor_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_doc));
    if (!$supervisor_data) {
        header("Location: index.php");
        exit();
    }
    $supervisor_id = $supervisor_data['supervisor_id'];
    $supervisor_name = $supervisor_data['name'];
    $supervisor_dept_code = $supervisor_data['dept_code'];
    //  1. منطق التبني (Adopt) 
    if (isset($_POST['adopt_project'])) {
        $p_type = $_POST['p_type'];
        $p_id = $_POST['p_id'];
        $table = ($p_type == 1) ? "project_1" : "project_2";
        // تحديث حقل المشرف ليصبح هذا المشرف
        $sql = "UPDATE $table SET supervisor_id=? WHERE project_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $supervisor_id, $p_id);
        mysqli_stmt_execute($stmt);
        // هنا نقوم بالربط والتبني التلقائي لمشروع 2 لنفس الطالب لمنع التشتت وتداخل المشرفين
        if ($p_type == 1) {
            $stmt_get_student = mysqli_prepare($conn, "SELECT student_id FROM project_1 WHERE project_id=?");
            mysqli_stmt_bind_param($stmt_get_student, "s", $p_id);
            mysqli_stmt_execute($stmt_get_student);
            $res_student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get_student));
            if ($res_student) {
                $st_id = $res_student['student_id'];
                $sql_auto = "UPDATE project_2 SET supervisor_id=? WHERE student_id=?";
                $stmt_auto = mysqli_prepare($conn, $sql_auto);
                mysqli_stmt_bind_param($stmt_auto, "ss", $supervisor_id, $st_id);
                mysqli_stmt_execute($stmt_auto);
            }
        }
        header("Location: supervisor_dashboard.php?success=" . urlencode("تم تبني المشروع بنجاح 🤝"));
        exit();
    }
    //  2. منطق إلغاء التبني (Unadopt) 
    if (isset($_POST['unadopt_project'])) {
        $p_type = $_POST['p_type'];
        $p_id = $_POST['p_id'];
        $table = ($p_type == 1) ? "project_1" : "project_2";
        // إرجاع المشرف إلى NULL 
        $sql = "UPDATE $table SET supervisor_id=NULL, grade=NULL, notes=NULL WHERE project_id=? AND supervisor_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $p_id, $supervisor_id);
        mysqli_stmt_execute($stmt);
        header("Location: supervisor_dashboard.php?success=" . urlencode("تم إلغاء تبني المشروع وإتاحة الخيارات للزملاء"));
        exit();
    }
   //  3.حفظ التقييم للمشاريع المتبناة
    if (isset($_POST['save_eval'])) {
        $p_type = $_POST['p_type'];
        $p_id = $_POST['p_id'];
        $grade = $_POST['grade'];
        $notes = $_POST['notes'];
        $table = ($p_type == 1) ? "project_1" : "project_2";
        //هنا يضمن لي بان اي طالب لديه مشرف فان المشرف الاخر غير قادر على رؤيه مشروع الطالب من خلال علامه الاستفهام
        $sql = "UPDATE $table SET grade=?, notes=?, is_note_new=1 WHERE project_id=? AND supervisor_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isss", $grade, $notes, $p_id, $supervisor_id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: supervisor_dashboard.php?success=" . urlencode("تم حفظ التقييم بنجاح وتنبيه الطالب 🔔"));
            exit();
        }
    }
    // مشروع 1: جلب طلاب نفس قسم المشرف
    $stmt_q1 = mysqli_prepare($conn, "SELECT p.*, s.name AS student_name, d.name AS doc_name FROM project_1 p JOIN students s ON p.student_id = s.student_id LEFT JOIN supervisors d ON p.supervisor_id = d.supervisor_id WHERE s.dept_code = ?");
    mysqli_stmt_bind_param($stmt_q1, "s", $supervisor_dept_code);
    mysqli_stmt_execute($stmt_q1);
    $q1 = mysqli_stmt_get_result($stmt_q1);
    // مشروع 2: يُعرض فقط للتخصصات (01 - علم الحاسوب) و (02 - نظم المعلومات)
    $show_proj2 = in_array($supervisor_dept_code, ['01', '02']);
    if ($show_proj2) {
        $stmt_q2 = mysqli_prepare($conn, "SELECT p.*, s.name AS student_name, d.name AS doc_name FROM project_2 p JOIN students s ON p.student_id = s.student_id LEFT JOIN supervisors d ON p.supervisor_id = d.supervisor_id WHERE s.dept_code = ?");
        mysqli_stmt_bind_param($stmt_q2, "s", $supervisor_dept_code);
        mysqli_stmt_execute($stmt_q2);
        $q2 = mysqli_stmt_get_result($stmt_q2);
    }
    // جلب الإعلانات
    $stmt_anns = mysqli_prepare($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
    mysqli_stmt_execute($stmt_anns);
    $announcements = mysqli_stmt_get_result($stmt_anns);
} else { 
    header("Location: index.php"); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="<?php echo T('lang_code'); ?>" dir="<?php echo T('dir'); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo T('supervisor_panel'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header-box">
            <h2 style="margin:0;">👨‍🏫 <?php echo $lang == 'ar' ? 'لوحة تقييم مشاريع التخرج' : 'Graduation Projects Evaluation Panel'; ?> - <?php echo $lang == 'ar' ? 'د. ' : 'Dr. '; ?><?php echo htmlspecialchars($supervisor_name); ?></h2>
            <div style="display: flex; gap: 10px;">
                <a href="change_password.php" class="btn-logout" style="background:#f39c12; text-decoration:none;"><i class="fa-solid fa-key"></i> <?php echo $lang == 'ar' ? 'تغيير كلمة المرور' : 'Change Password'; ?></a>
                <a href="?change_lang=<?php echo $lang == 'ar' ? 'en' : 'ar'; ?>" class="btn-logout" style="background:#0288d1;"><?php echo T('switch_lang'); ?></a>
                <a href="sign_out.php" class="btn-logout"> <?php echo T('logout'); ?></a>
            </div>
        </div>
        <?php if (isset($_GET['success'])) echo "<div class='alert-success'>✅ " . htmlspecialchars($_GET['success']) . "</div>"; ?>
        <?php if (mysqli_num_rows($announcements) > 0): ?>
        <div style="margin-bottom: 25px; background: #fff; border-radius: 12px; padding: 20px 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-right: 5px solid #0288d1;">
            <h3 style="margin: 0 0 15px 0; color: #0288d1; display: flex; align-items: center; gap: 8px;">
                📢 <?php echo $lang == 'ar' ? 'إعلانات من الإدارة' : 'Admin Announcements'; ?>
            </h3>
            <?php while ($ann = mysqli_fetch_assoc($announcements)): ?>
            <div style="padding: 12px 15px; border-bottom: 1px solid #edf2f7;">
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
        <div class="p-section">
            <h3 style="color: #1a3c40;">📘 <?php echo $lang == 'ar' ? 'مشاريع تخرج (1) - التوثيق والدراسة' : 'Graduation Projects (1) - Documentation & Study'; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php echo $lang == 'ar' ? 'اسم الطالب' : 'Student Name'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'عنوان المشروع' : 'Project Title'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'حالة الإشراف والتبني' : 'Supervision Status'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'ملف التوثيق' : 'Documentation File'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'العلامة (100)' : 'Grade (100)'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'الملاحظات التوجيهية' : 'Supervisor Notes'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'الإجراء' : 'Action'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($q1) > 0) { 
                        while($row = mysqli_fetch_assoc($q1)) { 
                            $is_mine = ($row['supervisor_id'] == $supervisor_id);
                            $is_free = (empty($row['supervisor_id']));
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                            <td>
                                <div style="font-weight:bold; color:#1a3c40; margin-bottom: 5px;"><?php echo htmlspecialchars($row['project_title']); ?></div>
                                <?php if(!empty($row['project_desc'])): ?>
                                    <div style="font-size: 12.5px; color: #444; background: #f8fafc; padding: 8px 10px; border-radius: 6px; border-right: 3px solid #1a3c40; max-width: 280px; word-wrap: break-word; line-height: 1.5; box-shadow: inset 0 1px 3px rgba(0,0,0,0.03);">
                                        <i class="fa-solid fa-circle-info" style="color: #0288d1; margin-inline-end: 4px;"></i> <?php echo nl2br(htmlspecialchars($row['project_desc'])); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #999; font-style: italic;"><?php echo $lang == 'ar' ? 'لا يوجد وصف' : 'No description'; ?></span>
                                <?php endif; ?>
                            </td>    
                            <td>
                                <?php if ($is_free): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="p_type" value="1">
                                        <input type="hidden" name="p_id" value="<?php echo $row['project_id']; ?>">
                                        <button type="submit" name="adopt_project" class="btn-adopt">🤝 <?php echo $lang == 'ar' ? 'تبني المشروع' : 'Adopt Project'; ?></button>
                                    </form>
                                <?php elseif ($is_mine): ?>
                                    <span style="color:#27ae60; font-weight:bold; display:block;">✔️ <?php echo $lang == 'ar' ? 'تحت إشرافك' : 'Under your supervision'; ?></span>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="p_type" value="1">
                                        <input type="hidden" name="p_id" value="<?php echo $row['project_id']; ?>">
                                        <button type="submit" name="unadopt_project" class="btn-unadopt" onclick="return confirm('<?php echo $lang == 'ar' ? 'عند إلغاء الإشراف سيتم تصفير العلامات المرصودة، هل أنت متأكد؟' : 'Canceling supervision will reset graded marks. Are you sure?'; ?>');">❌ <?php echo $lang == 'ar' ? 'إلغاء الإشراف' : 'Cancel Supervision'; ?></button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge-locked">🔐 <?php echo $lang == 'ar' ? 'متبنى من د.' : 'Adopted by Dr.'; ?> <?php echo htmlspecialchars($row['doc_name']); ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if ($is_mine): ?>
                                <form method="POST">
                                    <td><a href="download.php?file=<?php echo urlencode($row['project_file']); ?>&name=<?php echo rawurlencode($row['student_name'] . ' - ' . $row['project_title'] . '.' . pathinfo($row['project_file'], PATHINFO_EXTENSION)); ?>" class="btn-download">📥 <?php echo $lang == 'ar' ? 'تحميل PDF' : 'Download PDF'; ?></a></td>
                                    <td><input type="number" name="grade" class="grade-input" value="<?php echo $row['grade']; ?>" min="0" max="100"></td>
                                    <td><textarea name="notes" class="notes-area"><?php echo htmlspecialchars($row['notes']); ?></textarea></td>
                                    <td>
                                        <input type="hidden" name="p_type" value="1">
                                        <input type="hidden" name="p_id" value="<?php echo $row['project_id']; ?>">
                                        <button type="submit" name="save_eval" class="btn-eval">💾 <?php echo $lang == 'ar' ? 'حفظ' : 'Save'; ?></button>
                                    </td>
                                </form>
                            <?php else: ?>
                                <td style="color:#aaa;">🔒 <?php echo $lang == 'ar' ? 'مغلق' : 'Locked'; ?></td>
                                <td style="color:#aaa;">-</td>
                                <td style="color:#aaa; font-style:italic; font-size:13px;"><?php echo $lang == 'ar' ? 'المشروع محمي للمشرف فقط' : 'Protected for supervisor only'; ?></td>
                                <td style="color:#aaa;">🔒 <?php echo $lang == 'ar' ? 'مغلق' : 'Locked'; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php } } else { echo "<tr><td colspan='7'>" . ($lang == 'ar' ? 'لا توجد مشاريع مرفوعة حالياً في هذا القسم.' : 'No projects uploaded in this department currently.') . "</td></tr>"; } ?>
                </tbody>
            </table>
        </div>
        <?php if ($show_proj2): ?>
        <div class="p-section" style="border-top-color: #27ae60;">
            <h3 style="color: #27ae60;">💻 <?php echo $lang == 'ar' ? 'مشاريع تخرج (2) - الكود والموقع' : 'Graduation Projects (2) - Code & Website'; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php echo $lang == 'ar' ? 'اسم الطالب' : 'Student Name'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'اسم النظام النهائي' : 'Final System Title'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'حالة الإشراف والتبني' : 'Supervision Status'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'كود النظام' : 'System Code'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'العلامة (100)' : 'Grade (100)'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'ملاحظات المناقشة' : 'Discussion Notes'; ?></th>
                        <th><?php echo $lang == 'ar' ? 'الإجراء' : 'Action'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($q2) > 0) { 
                        while($row = mysqli_fetch_assoc($q2)) { 
                            $is_mine = ($row['supervisor_id'] == $supervisor_id);
                            $is_free = (empty($row['supervisor_id']));
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['project_title']); ?></td>                          
                            <td>
                                <?php if ($is_free): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="p_type" value="2">
                                        <input type="hidden" name="p_id" value="<?php echo $row['project_id']; ?>">
                                        <button type="submit" name="adopt_project" class="btn-adopt" style="background:#27ae60;">🤝 <?php echo $lang == 'ar' ? 'تبني الكود' : 'Adopt Code'; ?></button>
                                    </form>
                                <?php elseif ($is_mine): ?>
                                    <span style="color:#27ae60; font-weight:bold; display:block;">✔️ <?php echo $lang == 'ar' ? 'تحت إشرافك' : 'Under your supervision'; ?></span>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="p_type" value="2">
                                        <input type="hidden" name="p_id" value="<?php echo $row['project_id']; ?>">
                                        <button type="submit" name="unadopt_project" class="btn-unadopt" onclick="return confirm('<?php echo $lang == 'ar' ? 'هل أنت متأكد من إلغاء الإشراف؟' : 'Are you sure you want to cancel supervision?'; ?>');">❌ <?php echo $lang == 'ar' ? 'إلغاء الإشراف' : 'Cancel Supervision'; ?></button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge-locked">🔐 <?php echo $lang == 'ar' ? 'متبنى من د.' : 'Adopted by Dr.'; ?> <?php echo htmlspecialchars($row['doc_name']); ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if ($is_mine): ?>
                                <form method="POST">
                                    <td><a href="download.php?file=<?php echo urlencode($row['source_code_file']); ?>&name=<?php echo rawurlencode($row['student_name'] . ' - ' . $row['project_title'] . '.' . pathinfo($row['source_code_file'], PATHINFO_EXTENSION)); ?>" class="btn-download" style="color:#2e7d32; background:#e8f5e9;">📦 <?php echo $lang == 'ar' ? 'تحميل الكود (ZIP)' : 'Download Code (ZIP)'; ?></a></td>
                                    <td><input type="number" name="grade" class="grade-input" style="border-color:#27ae60;" value="<?php echo $row['grade']; ?>" min="0" max="100"></td>
                                    <td><textarea name="notes" class="notes-area"><?php echo htmlspecialchars($row['notes']); ?></textarea></td>
                                    <td>
                                        <input type="hidden" name="p_type" value="2">
                                        <input type="hidden" name="p_id" value="<?php echo $row['project_id']; ?>">
                                        <button type="submit" name="save_eval" class="btn-eval" style="background:#27ae60;">💾 <?php echo $lang == 'ar' ? 'حفظ' : 'Save'; ?></button>
                                    </td>
                                </form>
                            <?php else: ?>
                                <td style="color:#aaa;">🔒 <?php echo $lang == 'ar' ? 'مغلق' : 'Locked'; ?></td>
                                <td style="color:#aaa;">-</td>
                                <td style="color:#aaa; font-style:italic; font-size:13px;"><?php echo $lang == 'ar' ? 'الكود محمي للمشرف فقط' : 'Code protected for supervisor only'; ?></td>
                                <td style="color:#aaa;">🔒 <?php echo $lang == 'ar' ? 'مغلق' : 'Locked'; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php } } else { echo "<tr><td colspan='7'>" . ($lang == 'ar' ? 'لا توجد ملفات كود مرفوعة حالياً في هذا القسم.' : 'No code files uploaded in this department currently.') . "</td></tr>"; } ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>