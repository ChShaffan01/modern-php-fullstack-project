<?php 
session_start(); 
include '../includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$class_id = $subject = $exam_type = $exam_date = $total_marks = $passing_marks = $percentage = "";
$students = [];
$success_msg = $error_msg = "";

// Initialize variables to avoid undefined errors
$class_id = isset($_POST['class_id']) ? $_POST['class_id'] : (isset($_GET['class_id']) ? $_GET['class_id'] : "");
$subject = isset($_POST['subject']) ? $_POST['subject'] : (isset($_GET['subject']) ? $_GET['subject'] : "");
$exam_type = isset($_POST['exam_type']) ? $_POST['exam_type'] : "";
$exam_date = isset($_POST['exam_date']) ? $_POST['exam_date'] : date('Y-m-d');
$total_marks = isset($_POST['total_marks']) ? $_POST['total_marks'] : 100;
$passing_marks = isset($_POST['passing_marks']) ? $_POST['passing_marks'] : 40;
$percentage = ($total_marks > 0 && $passing_marks > 0) ? ($passing_marks / $total_marks) * 100 : 0;

// Create marks entry
if(isset($_POST['create_marks'])) {
    $class_id = $_POST['class_id'];
    $subject = $_POST['subject'];
    $exam_type = $_POST['exam_type'];
    $exam_date = $_POST['exam_date'];
    $total_marks = $_POST['total_marks'];
    $passing_marks = $_POST['passing_marks'];
    
    $percentage = ($total_marks > 0 && $passing_marks > 0) ? ($passing_marks / $total_marks) * 100 : 0;
    
    // Fetch students for the selected class
    if($class_id && $subject) {
        $student_sql = "SELECT students.id, students.name, students.class_id 
                       FROM students 
                       WHERE students.class_id = ? 
                       ORDER BY students.class_id";
        $student_stmt = $conn->prepare($student_sql);
        $student_stmt->bind_param("i", $class_id);
        $student_stmt->execute();
        $result = $student_stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// Save individual marks
if(isset($_POST['save_single_marks'])) {
    $student_id = $_POST['student_id'];
    $marks_obtained = $_POST['marks_obtained'];
    $subject = $_POST['subject'];
    $class_id = $_POST['class_id'];
    $total_marks_val = $_POST['total_marks'];
    $exam_date_val = $_POST['exam_date'];
    $exam_type_val = $_POST['exam_type'];
    
    // Validate marks
    if($marks_obtained >= 0 && $marks_obtained <= $total_marks_val) {
        // Check if marks already exist for this student, subject and exam type
        $check_sql = "SELECT id FROM marks WHERE student_id = ? AND subject = ? AND exam_type = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iss", $student_id, $subject, $exam_type_val);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            // Update existing marks
            $update_sql = "UPDATE marks SET marks = ?, obtain_marks = ?, date = ? 
                          WHERE student_id = ? AND subject = ? AND exam_type = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iisiss", $total_marks_val, $marks_obtained, 
                                    $exam_date_val, $student_id, $subject, $exam_type_val);
            if($update_stmt->execute()) {
                $success_msg = "Marks updated successfully for student!";
            } else {
                $error_msg = "Error updating marks: " . $conn->error;
            }
        } else {
            // Insert new marks
            $insert_sql = "INSERT INTO marks (student_id, subject, marks, date, obtain_marks, exam_type, class_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("issiisi", $student_id, $subject, $total_marks_val, 
                                    $exam_date_val, $marks_obtained, $exam_type_val, $class_id);
            if($insert_stmt->execute()) {
                $success_msg = "Marks saved successfully!";
            } else {
                $error_msg = "Error saving marks: " . $conn->error;
            }
        }
    } else {
        $error_msg = "Marks must be between 0 and $total_marks_val";
    }
    
    // Keep form data after submission
    $_POST['create_marks'] = true;
}

// Save all marks at once
if(isset($_POST['save_all_marks'])) {
    $subject = $_POST['subject'];
    $class_id = $_POST['class_id'];
    $total_marks_val = $_POST['total_marks'];
    $exam_date_val = $_POST['exam_date'];
    $exam_type_val = $_POST['exam_type'];
    $student_ids = $_POST['student_ids'];
    $marks_obtained_array = $_POST['marks_obtained'];
    
    $saved_count = 0;
    $error_count = 0;
    
    for($i = 0; $i < count($student_ids); $i++) {
        $student_id = $student_ids[$i];
        $marks_obtained = $marks_obtained_array[$i];
        
        // Skip if marks field is empty
        if($marks_obtained === "") continue;
        
        // Validate marks
        if($marks_obtained >= 0 && $marks_obtained <= $total_marks_val) {
            // Check if marks already exist
            $check_sql = "SELECT id FROM marks WHERE student_id = ? AND subject = ? AND exam_type = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iss", $student_id, $subject, $exam_type_val);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if($check_result->num_rows > 0) {
                // Update existing marks
                $update_sql = "UPDATE marks SET marks = ?, obtain_marks = ?, date = ? 
                              WHERE student_id = ? AND subject = ? AND exam_type = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iisiss", $total_marks_val, $marks_obtained, 
                                        $exam_date_val, $student_id, $subject, $exam_type_val);
                if($update_stmt->execute()) {
                    $saved_count++;
                } else {
                    $error_count++;
                }
            } else {
                // Insert new marks
                $insert_sql = "INSERT INTO marks (student_id, subject, marks, date, obtain_marks, exam_type) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("isssss", $student_id, $subject, $total_marks_val, 
                                        $exam_date_val, $marks_obtained, $exam_type_val,);
                if($insert_stmt->execute()) {
                    $saved_count++;
                } else {
                    $error_count++;
                }
            }
        }
    }
    
    if($saved_count > 0) {
        $success_msg = "Successfully saved marks for $saved_count students!";
    }
    if($error_count > 0) {
        $error_msg = "There were errors saving marks for $error_count students.";
    }
    
    // Keep form data after submission
    $_POST['create_marks'] = true;
}

// If viewing existing marks (from filters)
if(isset($_GET['class_id']) && isset($_GET['subject'])) {
    $class_id = $_GET['class_id'];
    $subject = $_GET['subject'];
    
    // Fetch class name
    $class_sql = "SELECT class_name, section FROM classes WHERE id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->bind_param("i", $class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    $class_info = $class_result->fetch_assoc();
    $class_name = $class_info ? "Class {$class_info['class_name']} - {$class_info['section']}" : "Class $class_id";
    
    // Fetch students for the selected class
    if($class_id && $subject) {
        $student_sql = "SELECT students.id, students.name, students.class_id
                       FROM students 
                       WHERE students.class_id = ? 
                       ORDER BY students.class_id";
        $student_stmt = $conn->prepare($student_sql);
        $student_stmt->bind_param("i", $class_id);
        $student_stmt->execute();
        $result = $student_stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// Fetch students if form was submitted
if(isset($_POST['create_marks'])) {
    $class_id = $_POST['class_id'];
    $subject = $_POST['subject'];
    $exam_type = $_POST['exam_type'];
    $exam_date = $_POST['exam_date'];
    $total_marks = $_POST['total_marks'];
    $passing_marks = $_POST['passing_marks'];
    
    $percentage = ($total_marks > 0 && $passing_marks > 0) ? ($passing_marks / $total_marks) * 100 : 0;
    
    // Fetch class name
    $class_sql = "SELECT class_name, section FROM classes WHERE id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->bind_param("i", $class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    $class_info = $class_result->fetch_assoc();
    $class_name = $class_info ? "Class {$class_info['class_name']} - {$class_info['section']}" : "Class $class_id";
    
    if($class_id && $subject) {
        $student_sql = "SELECT students.id, students.name, students.class_id 
                       FROM students 
                       WHERE students.class_id = ? 
                       ORDER BY students.class_id";
        $student_stmt = $conn->prepare($student_sql);
        $student_stmt->bind_param("i", $class_id);
        $student_stmt->execute();
        $result = $student_stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Management - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6c757d;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            background-color: #f8f9fc;
            min-height: 100vh;
        }
        
        .navbar {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 1rem 0;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f8f9fc;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .marks-input {
            transition: all 0.3s;
            border: 1px solid #d1d3e2;
        }
        
        .marks-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .grade-indicator {
            display: inline-block;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            color: white;
        }
        
        .modal-content {
            border-radius: 0.5rem;
            border: none;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .stats-card {
            background: linear-gradient(45deg, var(--primary-color), #2e59d9);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .stats-card p {
            opacity: 0.9;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/auth.php'; ?>
    
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid px-4">
                    <h4 class="navbar-brand mb-0 fw-bold text-primary">
                        <i class="fas fa-chart-line me-2"></i>Marks Management
                    </h4>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMarksModal">
                            <i class="fas fa-plus-circle me-1"></i> Add Marks
                        </button>
                    </div>
                </div>
            </nav>
            
            <!-- Success/Error Messages -->
            <?php if($success_msg): ?>
                <div class="container-fluid px-4">
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($error_msg): ?>
                <div class="container-fluid px-4">
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="container-fluid px-4">
                <!-- Filters Card -->
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-filter me-1"></i>Filter Marks
                        </h6>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" method="get" action="">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Class</label>
                                <select name="class_id" class="form-select">
                                    <option value="">-- Select Class --</option>
                                    <?php
                                    $q = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section");
                                    while ($c = $q->fetch_assoc()) {
                                        $selected = ($class_id == $c['id']) ? 'selected' : '';
                                        echo "<option value='{$c['id']}' $selected>
                                            Class {$c['class_name']} - {$c['section']}
                                        </option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Subject</label>
                                <select name="subject" class="form-select">
                                    <option value="">-- Select Subject --</option>
                                    <option value="Mathematics" <?php echo ($subject == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                                    <option value="Science" <?php echo ($subject == 'Science') ? 'selected' : ''; ?>>Science</option>
                                    <option value="English" <?php echo ($subject == 'English') ? 'selected' : ''; ?>>English</option>
                                    <option value="History" <?php echo ($subject == 'History') ? 'selected' : ''; ?>>History</option>
                                    <option value="Geography" <?php echo ($subject == 'Geography') ? 'selected' : ''; ?>>Geography</option>
                                    <option value="Computer Science" <?php echo ($subject == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Search Marks
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if((isset($_POST['create_marks']) || (isset($_GET['class_id']) && isset($_GET['subject']))) && !empty($students)): ?>
                    <!-- Marks Entry Card -->
                    <div class="card">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-edit me-2"></i>
                                    <?php echo htmlspecialchars($subject); ?> - <?php echo isset($exam_type) ? htmlspecialchars($exam_type) : 'Exam'; ?>
                                </h5>
                                <small class="text-muted">
                                    <?php echo isset($class_name) ? $class_name : "Class $class_id"; ?> | 
                                    Total Marks: <?php echo $total_marks; ?> | 
                                    Passing: <?php echo $passing_marks; ?> (<?php echo number_format($percentage, 2); ?>%) | 
                                    Date: <?php echo isset($exam_date) ? $exam_date : date('Y-m-d'); ?>
                                </small>
                            </div>
                            <div>
                                <?php if(isset($exam_type)): ?>
                                    <span class="badge bg-info fs-6"><?php echo $exam_type; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="post" action="" id="marksForm">
                                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                                <input type="hidden" name="total_marks" value="<?php echo $total_marks; ?>">
                                <input type="hidden" name="exam_date" value="<?php echo isset($exam_date) ? $exam_date : date('Y-m-d'); ?>">
                                <input type="hidden" name="exam_type" value="<?php echo isset($exam_type) ? htmlspecialchars($exam_type) : 'General'; ?>">
                                <input type="hidden" name="passing_marks" value="<?php echo $passing_marks; ?>">
                                <input type="hidden" name="create_marks" value="1">
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Roll No</th>
                                                <th>Student Name</th>
                                                <th>Marks Obtained</th>
                                                <th>Max Marks</th>
                                                <th>Percentage</th>
                                                <th>Grade</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                          <?php 
$counter = 1;
$total_obtained = 0;
$student_count = 0;
foreach($students as $student): 
    // Fetch existing marks if any
    $existing_sql = "SELECT obtain_marks FROM marks 
                   WHERE student_id = ? AND subject = ?";
    
    // Add exam_type condition if it's set and not empty
    if(isset($exam_type) && !empty($exam_type)) {
        $existing_sql .= " AND exam_type = ?";
    }
    
    $existing_stmt = $conn->prepare($existing_sql);
    
    // Check if prepare was successful
    if(!$existing_stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    if(isset($exam_type) && !empty($exam_type)) {
        $existing_stmt->bind_param("iss", $student['id'], $subject, $exam_type);
    } else {
        $existing_stmt->bind_param("is", $student['id'], $subject);
    }
    
    $existing_stmt->execute();
    $existing_result = $existing_stmt->get_result();
    $existing_marks = "";
    
    if($existing_result->num_rows > 0) {
        $existing_row = $existing_result->fetch_assoc();
        $existing_marks = $existing_row['obtain_marks'];
    }
    
    $student_percentage = ($existing_marks !== "" && $total_marks > 0) ? 
        ($existing_marks / $total_marks) * 100 : 0;
    
    // Determine grade
    $grade = '';
    $grade_class = '';
    if($student_percentage >= 80) {
        $grade = 'A+';
        $grade_class = 'bg-success';
    } elseif($student_percentage >= 70) {
        $grade = 'A';
        $grade_class = 'bg-info';
    } elseif($student_percentage >= 60) {
        $grade = 'B';
        $grade_class = 'bg-primary';
    } elseif($student_percentage >= 50) {
        $grade = 'C';
        $grade_class = 'bg-warning';
    } elseif($student_percentage >= 40) {
        $grade = 'D';
        $grade_class = 'bg-secondary';
    } else {
        $grade = 'F';
        $grade_class = 'bg-danger';
    }
    
    // Determine status
    $status = $existing_marks !== "" ? 'Saved' : 'Pending';
    $status_class = $existing_marks !== "" ? 'badge bg-success' : 'badge bg-warning';
    
    // Add to totals
    if($existing_marks !== "") {
        $total_obtained += $existing_marks;
        $student_count++;
    }
?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($student['class_id']); ?></strong></td>
                                                <td><?php echo $counter++; ?></td>
                                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                <td width="150">
                                                    <input type="number" name="marks_obtained[]" 
                                                           class="form-control form-control-sm marks-input" 
                                                           value="<?php echo $existing_marks; ?>" 
                                                           placeholder="Enter marks"
                                                           min="0" max="<?php echo $total_marks; ?>"
                                                           data-student-id="<?php echo $student['id']; ?>">
                                                    <input type="hidden" name="student_ids[]" value="<?php echo $student['id']; ?>">
                                                </td>
                                                <td><?php echo $total_marks; ?></td>
                                                <td>
                                                    <?php if($existing_marks !== ""): ?>
                                                        <span class="fw-bold <?php echo $student_percentage >= 40 ? 'text-success' : 'text-danger'; ?>">
                                                            <?php echo number_format($student_percentage, 2); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($existing_marks !== ""): ?>
                                                        <span class="grade-indicator <?php echo $grade_class; ?>">
                                                            <?php echo $grade; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="<?php echo $status_class; ?>">
                                                        <i class="fas <?php echo $existing_marks !== "" ? 'fa-check' : 'fa-clock'; ?> me-1"></i>
                                                        <?php echo $status; ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-success save-single-btn"
                                                            data-student-id="<?php echo $student['id']; ?>"
                                                            title="Save this student's marks">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Class Average:</td>
                                                <td class="fw-bold">
                                                    <?php 
                                                    $class_average = ($student_count > 0) ? $total_obtained / $student_count : 0;
                                                    echo number_format($class_average, 2);
                                                    ?>
                                                </td>
                                                <td class="fw-bold"><?php echo $total_marks; ?></td>
                                                <td class="fw-bold">
                                                    <?php 
                                                    $class_percentage = ($student_count > 0 && $total_marks > 0) ? 
                                                        ($total_obtained / ($student_count * $total_marks)) * 100 : 0;
                                                    echo number_format($class_percentage, 2); ?>%
                                                </td>
                                                <td colspan="3" class="fw-bold">
                                                    <?php 
                                                    if($class_percentage >= 80) echo '<span class="text-success">Excellent Performance</span>';
                                                    elseif($class_percentage >= 60) echo '<span class="text-primary">Good Performance</span>';
                                                    elseif($class_percentage >= 40) echo '<span class="text-warning">Average Performance</span>';
                                                    else echo '<span class="text-danger">Needs Improvement</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div>
                                        <span class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <?php echo count($students); ?> students found
                                        </span>
                                    </div>
                                    <div>
                                        <button type="submit" name="save_all_marks" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i> Save All Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                <?php elseif((isset($_POST['create_marks']) || (isset($_GET['class_id']) && isset($_GET['subject']))) && empty($students)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-user-graduate display-1 text-muted mb-4"></i>
                            <h4 class="text-muted mb-3">No Students Found</h4>
                            <p class="text-muted mb-4">There are no students enrolled in the selected class.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMarksModal">
                                <i class="fas fa-plus-circle me-2"></i> Try Another Class
                            </button>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-chart-line display-1 text-primary mb-4"></i>
                            <h4 class="text-primary mb-3">Welcome to Marks Management</h4>
                            <p class="text-muted mb-4">Create a new marks entry or use the filters to view existing marks.</p>
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addMarksModal">
                                <i class="fas fa-plus-circle me-2"></i> Create New Marks Entry
                            </button>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <h3>
                                    <?php 
                                    $total_students = $conn->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'];
                                    echo $total_students;
                                    ?>
                                </h3>
                                <p><i class="fas fa-user-graduate me-2"></i> Total Students</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card" style="background: linear-gradient(45deg, var(--success-color), #17a673);">
                                <h3>
                                    <?php 
                                    $total_marks_entries = $conn->query("SELECT COUNT(*) as total FROM marks")->fetch_assoc()['total'];
                                    echo $total_marks_entries;
                                    ?>
                                </h3>
                                <p><i class="fas fa-file-alt me-2"></i> Marks Entries</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card" style="background: linear-gradient(45deg, var(--info-color), #258ea1);">
                                <h3>
                                    <?php 
                                    $total_classes = $conn->query("SELECT COUNT(*) as total FROM classes")->fetch_assoc()['total'];
                                    echo $total_classes;
                                    ?>
                                </h3>
                                <p><i class="fas fa-chalkboard-teacher me-2"></i> Active Classes</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
     
    <!-- Add Marks Modal -->
    <div class="modal fade" id="addMarksModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Create New Marks Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="marksEntryForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Select Class *</label>
                                <select class="form-select" name="class_id" required>
                                    <option value="">-- Select Class --</option>
                                    <?php
                                    $q = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section");
                                    while ($c = $q->fetch_assoc()) {
                                        $selected = ($class_id == $c['id']) ? 'selected' : '';
                                        echo "<option value='{$c['id']}' $selected>
                                            Class {$c['class_name']} - {$c['section']}
                                        </option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Select Subject *</label>
                                <select class="form-select" name="subject" required>
                                    <option value="">-- Select Subject --</option>
                                    <option value="Mathematics" <?php echo ($subject == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                                    <option value="Science" <?php echo ($subject == 'Science') ? 'selected' : ''; ?>>Science</option>
                                    <option value="English" <?php echo ($subject == 'English') ? 'selected' : ''; ?>>English</option>
                                    <option value="History" <?php echo ($subject == 'History') ? 'selected' : ''; ?>>History</option>
                                    <option value="Geography" <?php echo ($subject == 'Geography') ? 'selected' : ''; ?>>Geography</option>
                                    <option value="Computer Science" <?php echo ($subject == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Exam Type *</label>
                                <select class="form-select" name="exam_type" required>
                                    <option value="">-- Select Exam --</option>
                                    <option value="Mid-term" <?php echo (isset($exam_type) && $exam_type == 'Mid-term') ? 'selected' : ''; ?>>Mid-term</option>
                                    <option value="Final" <?php echo (isset($exam_type) && $exam_type == 'Final') ? 'selected' : ''; ?>>Final</option>
                                    <option value="Quiz 1" <?php echo (isset($exam_type) && $exam_type == 'Quiz 1') ? 'selected' : ''; ?>>Quiz 1</option>
                                    <option value="Quiz 2" <?php echo (isset($exam_type) && $exam_type == 'Quiz 2') ? 'selected' : ''; ?>>Quiz 2</option>
                                    <option value="Assignment" <?php echo (isset($exam_type) && $exam_type == 'Assignment') ? 'selected' : ''; ?>>Assignment</option>
                                    <option value="Unit Test" <?php echo (isset($exam_type) && $exam_type == 'Unit Test') ? 'selected' : ''; ?>>Unit Test</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Exam Date *</label>
                                <input type="date" name="exam_date" class="form-control" 
                                       value="<?php echo isset($exam_date) ? $exam_date : date('Y-m-d'); ?>" 
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Total Marks *</label>
                                <input type="number" name="total_marks" class="form-control" 
                                       value="<?php echo isset($total_marks) ? $total_marks : '100'; ?>" 
                                       required min="1" step="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Passing Marks *</label>
                                <input type="number" name="passing_marks" class="form-control" 
                                       value="<?php echo isset($passing_marks) ? $passing_marks : '40'; ?>" 
                                       required min="0" step="1">
                                <div class="form-text">Minimum marks required to pass</div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    After creating the marks entry, you'll be able to enter marks for each student. You can save marks individually or all at once.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" name="create_marks" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Create Marks Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden Form for Single Student Save -->
    <form id="singleSaveForm" method="post" action="" style="display: none;">
        <input type="hidden" name="student_id" id="singleStudentId">
        <input type="hidden" name="marks_obtained" id="singleMarksObtained">
        <input type="hidden" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
        <input type="hidden" name="class_id" value="<?php echo isset($class_id) ? $class_id : ''; ?>">
        <input type="hidden" name="total_marks" value="<?php echo isset($total_marks) ? $total_marks : ''; ?>">
        <input type="hidden" name="exam_date" value="<?php echo isset($exam_date) ? $exam_date : ''; ?>">
        <input type="hidden" name="exam_type" value="<?php echo isset($exam_type) ? htmlspecialchars($exam_type) : 'General'; ?>">
        <input type="hidden" name="save_single_marks" value="1">
        <input type="hidden" name="create_marks" value="1">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle individual save button clicks
            document.querySelectorAll('.save-single-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const studentId = this.getAttribute('data-student-id');
                    const marksInput = document.querySelector(`.marks-input[data-student-id="${studentId}"]`);
                    const marksValue = marksInput.value;
                    
                    // Validate marks
                    const totalMarks = <?php echo isset($total_marks) ? $total_marks : 100; ?>;
                    if(marksValue === '') {
                        alert('Please enter marks for this student.');
                        marksInput.focus();
                        return;
                    }
                    
                    if(parseFloat(marksValue) < 0 || parseFloat(marksValue) > totalMarks) {
                        alert(`Marks must be between 0 and ${totalMarks}`);
                        marksInput.focus();
                        return;
                    }
                    
                    // Show loading state
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    this.disabled = true;
                    
                    // Set values in hidden form
                    document.getElementById('singleStudentId').value = studentId;
                    document.getElementById('singleMarksObtained').value = marksValue;
                    
                    // Submit the form
                    document.getElementById('singleSaveForm').submit();
                });
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Calculate percentage as user types
            document.querySelectorAll('.marks-input').forEach(input => {
                input.addEventListener('input', function() {
                    const marks = parseFloat(this.value);
                    const totalMarks = <?php echo isset($total_marks) ? $total_marks : 100; ?>;
                    const row = this.closest('tr');
                    
                    if(!isNaN(marks) && marks >= 0 && marks <= totalMarks) {
                        const percentage = (marks / totalMarks) * 100;
                        const percentageCell = row.cells[5];
                        const gradeCell = row.cells[6];
                        const statusCell = row.cells[7];
                        
                        // Update percentage
                        percentageCell.innerHTML = `<span class="fw-bold ${percentage >= 40 ? 'text-success' : 'text-danger'}">${percentage.toFixed(2)}%</span>`;
                        
                        // Update grade
                        let grade = '';
                        let gradeClass = '';
                        if(percentage >= 80) {
                            grade = 'A+';
                            gradeClass = 'bg-success';
                        } else if(percentage >= 70) {
                            grade = 'A';
                            gradeClass = 'bg-info';
                        } else if(percentage >= 60) {
                            grade = 'B';
                            gradeClass = 'bg-primary';
                        } else if(percentage >= 50) {
                            grade = 'C';
                            gradeClass = 'bg-warning';
                        } else if(percentage >= 40) {
                            grade = 'D';
                            gradeClass = 'bg-secondary';
                        } else {
                            grade = 'F';
                            gradeClass = 'bg-danger';
                        }
                        
                        gradeCell.innerHTML = `<span class="grade-indicator ${gradeClass}">${grade}</span>`;
                        
                        // Update status
                        statusCell.innerHTML = '<span class="badge bg-warning"><i class="fas fa-edit me-1"></i>Unsaved</span>';
                    }
                });
            });
        });
    </script>
</body>
</html>