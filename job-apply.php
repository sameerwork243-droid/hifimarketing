<?php
// ===== START SESSION =====
session_start();

// ===== INCLUDE CONFIG =====
require_once 'includes/config.php';
require_once 'includes/functions.php';

// ===== CHECK IF USER IS LOGGED IN =====
if (!isLoggedIn()) {
    header('Location: login.php?redirect=job-detail.php?id=' . ($_POST['job_id'] ?? 0) . '&tab=application');
    exit();
}

// ===== CHECK IF FORM SUBMITTED =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: job.php');
    exit();
}

// ===== GET FORM DATA =====
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$user_id = $_SESSION['user_id'];
$first_name = sanitize($_POST['first_name'] ?? '');
$last_name = sanitize($_POST['last_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$phone_code = sanitize($_POST['phone_code'] ?? '+92');

// ===== VALIDATION =====
$errors = [];

if ($job_id <= 0) {
    $errors[] = "Invalid job ID.";
}
if (empty($first_name)) {
    $errors[] = "First name is required.";
}
if (empty($last_name)) {
    $errors[] = "Last name is required.";
}
if (empty($email) || !validateEmail($email)) {
    $errors[] = "Valid email is required.";
}
if (empty($phone)) {
    $errors[] = "Phone number is required.";
}
if (empty($address)) {
    $errors[] = "Address is required.";
}

// ===== RESUME UPLOAD - FIXED =====
$resume_path = '';
$upload_dir = 'uploads/resumes/';

// Create directory if not exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
    $file_extension = pathinfo($_FILES['resume_file']['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        $errors[] = "Only PDF, DOC, and DOCX files are allowed.";
    } else {
        $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $resume_path = $upload_dir . $new_filename;
        
        if (!move_uploaded_file($_FILES['resume_file']['tmp_name'], $resume_path)) {
            $errors[] = "Failed to upload resume. Please try again.";
        }
    }
} else {
    // Check if file was uploaded but with error
    if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "File upload error: " . $_FILES['resume_file']['error'];
    } else {
        $errors[] = "Resume is required. Please upload your resume.";
    }
}

// ===== IF ERRORS, SHOW THEM =====
if (!empty($errors)) {
    $_SESSION['application_errors'] = $errors;
    header('Location: job-detail.php?id=' . $job_id . '&tab=application');
    exit();
}

// ===== SAVE TO DATABASE =====
$full_phone = $phone_code . $phone;
$status = 'pending';

$query = "INSERT INTO applications (job_id, user_id, first_name, last_name, email, phone, address, resume, status, applied_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iisssssss", $job_id, $user_id, $first_name, $last_name, $email, $full_phone, $address, $resume_path, $status);

if (mysqli_stmt_execute($stmt)) {
    $application_id = mysqli_insert_id($conn);
    
    // ===== SUCCESS =====
    $_SESSION['application_success'] = "Your application has been submitted successfully!";
    header('Location: user/dashboard.php');
    exit();
} else {
    $_SESSION['application_errors'] = ["Database error: " . mysqli_error($conn)];
    header('Location: job-detail.php?id=' . $job_id . '&tab=application');
    exit();
}

mysqli_stmt_close($stmt);
?>