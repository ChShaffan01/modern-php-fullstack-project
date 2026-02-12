<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$class_id = $exam_type = "";
$class_info = null;
$students_results = [];
$class_stats = [];

// Get class results
if(isset($_POST['view_class_result'])) {
    $class_id = $_POST['class_id'];
    $exam_type = $_POST['exam_type'];
    
    // Get class information
    $class_sql = "SELECT class_name, section FROM classes WHERE id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->bind_param("i", $class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    
    if($class_result->num_rows > 0) {
        $class_info = $class_result->fetch_assoc();
        
        // Get all students in this class
        $students_sql = "SELECT id, name, class_id FROM students 
                        WHERE class_id = ? 
                        ORDER BY class_id";
        $students_stmt = $conn->prepare($students_sql);
        $students_stmt->bind_param("i", $class_id);
        $students_stmt->execute();
        $students_result = $students_stmt->get_result();
        
        while($student = $students_result->fetch_assoc()) {
            // Get marks for each student
            $marks_sql = "SELECT m.subject, m.marks, m.obtain_marks, 
                                 (m.obtain_marks/m.marks)*100 as percentage
                          FROM marks m
                          WHERE m.student_id = ? AND m.exam_type = ?
                          ORDER BY m.subject";
            $marks_stmt = $conn->prepare($marks_sql);
            $marks_stmt->bind_param("is", $student['id'], $exam_type);
            $marks_stmt->execute();
            $marks_result = $marks_stmt->get_result();
            
            $subjects = [];
            $total_marks = 0;
            $total_obtained = 0;
            
            while($mark = $marks_result->fetch_assoc()) {
                $subjects[] = $mark;
                $total_marks += $mark['marks'];
                $total_obtained += $mark['obtain_marks'];
            }
            
            $overall_percentage = ($total_marks > 0) ? ($total_obtained / $total_marks) * 100 : 0;
            
            // Determine overall grade
            $overall_grade = '';
            if($overall_percentage >= 80) $overall_grade = 'A+';
            elseif($overall_percentage >= 70) $overall_grade = 'A';
            elseif($overall_percentage >= 60) $overall_grade = 'B';
            elseif($overall_percentage >= 50) $overall_grade = 'C';
            elseif($overall_percentage >= 40) $overall_grade = 'D';
            else $overall_grade = 'F';
            
            $students_results[] = [
                'id' => $student['id'],
                'name' => $student['name'],
                'class_id' => $student['class_id'],
                'subjects' => $subjects,
                'total_marks' => $total_marks,
                'total_obtained' => $total_obtained,
                'percentage' => $overall_percentage,
                'grade' => $overall_grade,
                'status' => $overall_percentage >= 40 ? 'Pass' : 'Fail'
            ];
        }
        
        // Calculate class statistics
        if(!empty($students_results)) {
            $total_students = count($students_results);
            $pass_count = 0;
            $total_percentage = 0;
            
            foreach($students_results as $student) {
                if($student['status'] == 'Pass') $pass_count++;
                $total_percentage += $student['percentage'];
            }
            
            $class_stats = [
                'total_students' => $total_students,
                'pass_count' => $pass_count,
                'fail_count' => $total_students - $pass_count,
                'pass_percentage' => ($total_students > 0) ? ($pass_count / $total_students) * 100 : 0,
                'average_percentage' => ($total_students > 0) ? $total_percentage / $total_students : 0
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Results - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
        }
        
        .stats-card {
            border-radius: 10px;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-card.success {
            background: linear-gradient(45deg, #1cc88a, #17a673);
        }
        
        .stats-card.primary {
            background: linear-gradient(45deg, #4e73df, #2e59d9);
        }
        
        .stats-card.warning {
            background: linear-gradient(45deg, #f6c23e, #dda20a);
        }
        
        .student-result-card {
            border-left: 4px solid #4e73df;
            transition: all 0.3s;
        }
        
        .student-result-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .grade-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include '../includes/auth.php'; ?>
    
    <div class="d-flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content flex-grow-1">
            <nav class="navbar navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid px-4">
                    <h4 class="navbar-brand mb-0 fw-bold text-primary">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Class Results
                    </h4>
                </div>
            </nav>
            
            <div class="container-fluid px-4">
                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Select Class & Exam</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Select Class</label>
                                    <select name="class_id" class="form-select" required>
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
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Exam Type</label>
                                    <select name="exam_type" class="form-select" required>
                                        <option value="">-- Select Exam --</option>
                                        <option value="Mid-term" <?php echo ($exam_type == 'Mid-term') ? 'selected' : ''; ?>>Mid-term</option>
                                        <option value="Final" <?php echo ($exam_type == 'Final') ? 'selected' : ''; ?>>Final</option>
                                        <option value="Quiz 1" <?php echo ($exam_type == 'Quiz 1') ? 'selected' : ''; ?>>Quiz 1</option>
                                        <option value="Quiz 2" <?php echo ($exam_type == 'Quiz 2') ? 'selected' : ''; ?>>Quiz 2</option>
                                        <option value="Assignment" <?php echo ($exam_type == 'Assignment') ? 'selected' : ''; ?>>Assignment</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="view_class_result" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-eye me-2"></i> View Class Results
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if($class_info && !empty($students_results)): ?>
                    <!-- Class Results -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-chalkboard me-2"></i>
                                <?php echo $class_info['class_name'] . ' - ' . $class_info['section']; ?> | 
                                <?php echo $exam_type; ?> Exam Results
                            </h4>
                        </div>
                        
                        <div class="card-body">
                            <!-- Class Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="stats-card primary">
                                        <h3><?php echo $class_stats['total_students']; ?></h3>
                                        <p class="mb-0">Total Students</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card success">
                                        <h3><?php echo $class_stats['pass_count']; ?></h3>
                                        <p class="mb-0">Passed Students</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card warning">
                                        <h3><?php echo $class_stats['fail_count']; ?></h3>
                                        <p class="mb-0">Failed Students</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card primary">
                                        <h3><?php echo number_format($class_stats['average_percentage'], 2); ?>%</h3>
                                        <p class="mb-0">Class Average</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Students Results Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Subjects</th>
                                            <th>Obtained</th>
                                            <th>Total</th>
                                            <th>Percentage</th>
                                            <th>Grade</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($students_results as $student): ?>
                                        <tr>
                                            <td><strong><?php echo $student['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td>
                                                <?php echo count($student['subjects']); ?> subjects
                                            </td>
                                            <td><?php echo $student['total_obtained']; ?></td>
                                            <td><?php echo $student['total_marks']; ?></td>
                                            <td>
                                                <span class="fw-bold <?php echo $student['percentage'] >= 40 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo number_format($student['percentage'], 2); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $grade_class = '';
                                                if($student['grade'] == 'A+') $grade_class = 'bg-success';
                                                elseif($student['grade'] == 'A') $grade_class = 'bg-info';
                                                elseif($student['grade'] == 'B') $grade_class = 'bg-primary';
                                                elseif($student['grade'] == 'C') $grade_class = 'bg-warning';
                                                elseif($student['grade'] == 'D') $grade_class = 'bg-secondary';
                                                else $grade_class = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $grade_class; ?> grade-badge">
                                                    <?php echo $student['grade']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $student['status'] == 'Pass' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $student['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="check_result.php?student_id=<?php echo $student['id']; ?>&class_id=<?php echo $class_id; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Export Button -->
                            <div class="text-end mt-3">
                                <button onclick="window.print()" class="btn btn-success">
                                    <i class="fas fa-print me-2"></i> Print Results
                                </button>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif(isset($_POST['view_class_result'])): ?>
                    <!-- No Results Found -->
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-clipboard-list fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted mb-3">No Results Found</h4>
                            <p class="text-muted mb-4">
                                No results found for <?php echo $exam_type; ?> exam in this class.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>