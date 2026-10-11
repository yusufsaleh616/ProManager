
<?php 
session_start(); 
include "connection_settings.php"; 
if (isset($_POST['uname']) && isset($_POST['password']) && isset($_POST['role']) && isset($_POST['college']) && isset($_POST['department'])) {
    
    function validate($data){
       $data = trim($data);
       $data = stripslashes($data);
       $data = htmlspecialchars($data);
       return $data;
    }
    $email_input = validate($_POST['uname']); 
    $pass = trim($_POST['password']); // لا نستخدم htmlspecialchars على كلمة المرور لتجنب تشويهها قبل التحقق
    $role = validate($_POST['role']);
    $college = validate($_POST['college']); 
    $department = validate($_POST['department']);   
    if (empty($college)) {
        header("Location: index.php?error=الرجاء اختيار الكلية");
        exit();
    } else if (empty($department)) {
        header("Location: index.php?error=الرجاء اختيار القسم");
        exit();
    } else if (empty($email_input)) {
        header("Location: index.php?error=الرجاء إدخال البريد الإلكتروني الأكاديمي");
        exit();
    } else if(empty($pass)){
        header("Location: index.php?error=كلمة المرور مطلوبة");
        exit();
    } else {      
        if ($role == 'student') {
            $sql = "SELECT * FROM students WHERE email=?";
        } elseif ($role == 'supervisor') {
            $sql = "SELECT * FROM supervisors WHERE email=?";
        } else {
            header("Location: index.php?error=صلاحية غير صالحة");
            exit();
        }
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email_input);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        // 2. التحقق من وجود الحساب بالإيميل في النظام
        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);            
            // التحقق الذكي: هل القسم المختار من القائمة يطابق قسم صاحب الحساب؟
            $dept_map = ['cs' => '01', 'is' => '02', 'ai' => '08'];
            $expected_code = $dept_map[$department] ?? '';     
            if ($row['dept_code'] !== $expected_code && $row['dept_code'] !== $department) {
                header("Location: index.php?error=الحساب المدخل لا يتبع للقسم الذي اخترته!");
                exit();
            }    
            // 3. التحقق من كلمة المرور 
            if (password_verify($pass, $row['password']) || $pass === $row['password']) {
                $_SESSION['name'] = $row['name'];
                $_SESSION['email'] = $row['email']; 
                $_SESSION['role'] = $role;
                $_SESSION['college'] = $college; 
                $_SESSION['department'] = $department; 
                $_SESSION['dept_code'] = $row['dept_code'];
                $_SESSION['needs_password_change'] = $row['needs_password_change'];

                if ($role == 'student') {
                    $_SESSION['student_id'] = $row['student_id']; 
                } elseif ($role == 'supervisor') {
                    $_SESSION['supervisor_id'] = $row['supervisor_id']; 
                }

                if ($_SESSION['needs_password_change'] == 1) {
                    header("Location: change_password_first.php");
                } else {
                    if ($role == 'student') {
                        header("Location: student_dashboard.php");
                    } elseif ($role == 'supervisor') {
                        header("Location: supervisor_dashboard.php");
                    }
                }
                exit();
            } else {
                header("Location: index.php?error=كلمة المرور غير صحيحة");
                exit();
            }
        } else {
            header("Location: index.php?error=البريد الإلكتروني المدخل غير موجود بالنظام");
            exit();
        }
    }
} else {
    header("Location: index.php");
    exit();
}
?>