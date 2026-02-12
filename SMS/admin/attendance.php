<?php 
session_start(); 
include '../includes/db.php';  

$current_date = date('Y-m-d');
$date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : $current_date;
$class_id = isset($_POST['class_id']) ? $_POST['class_id'] : '';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if request is from View Attendance tab
$is_view_tab = isset($_POST['search_view']) || (isset($_POST['tab']) && $_POST['tab'] == 'viewAttendance');

// For View Attendance Tab
$view_class_id = isset($_POST['view_class_id']) ? $_POST['view_class_id'] : '';
$view_month = isset($_POST['view_month']) ? $_POST['view_month'] : date('Y-m');
$search_view = isset($_POST['search_view']) ? true : false;

// If coming from view tab, preserve the class selection
if($is_view_tab && !empty($view_class_id)) {
    $class_id = $view_class_id;
}

if(empty($class_id)) {
    $default_class = $conn->query("SELECT id FROM classes LIMIT 1");
    if($default_class->num_rows > 0) {
        $default_row = $default_class->fetch_assoc();
        $class_id = $default_row['id'];
    }
}

if(empty($view_class_id) && $is_view_tab) {
    $view_class_id = $class_id;
}

if(isset($_POST['save_attendance'])) {
    if(isset($_POST['student_ids']) && is_array($_POST['student_ids'])) {
        $student_ids = $_POST['student_ids'];
        
        foreach($student_ids as $student_id) {
            $attendance_key = 'attendance_' . $student_id;
            $remarks_key = 'remarks_' . $student_id;
            
            if(isset($_POST[$attendance_key])) {
                $status = $_POST[$attendance_key];
                $remarks = isset($_POST[$remarks_key]) ? $_POST[$remarks_key] : '';
                
                // Check if record exists
                $check_sql = "SELECT id FROM attendance WHERE student_id = ? AND day = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("is", $student_id, $date);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if($check_result->num_rows > 0) {
                    // Update existing
                    $update_sql = "UPDATE attendance SET statuss = ?, remarks = ? WHERE student_id = ? AND day = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ssis", $status, $remarks, $student_id, $date);
                    $update_stmt->execute();
                } else {
                    // Insert new
                    $insert_sql = "INSERT INTO attendance (student_id, day, statuss, remarks) VALUES (?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("isss", $student_id, $date, $status, $remarks);
                    $insert_stmt->execute();
                }
            }
        }
        $success_message = "Attendance saved successfully!";
    }
}

// Calculate statistics for Mark Attendance tab
$total_students = 0;
$present_today = 0;
$absent_today = 0;
$attendance_percentage = 0;

// Total students in selected class
if($class_id > 0) {
    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE class_id = ?");
    $total_stmt->bind_param("i", $class_id);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    if($total_row = $total_result->fetch_assoc()) {
        $total_students = $total_row['total'];
    }
}

// Present count for selected date
$present_stmt = $conn->prepare("SELECT COUNT(*) as present FROM attendance WHERE statuss = 'present' AND day = ?");
$present_stmt->bind_param("s", $date);
$present_stmt->execute();
$present_result = $present_stmt->get_result();
if($present_row = $present_result->fetch_assoc()) {
    $present_today = $present_row['present'];
}

// Absent count for selected date
$absent_stmt = $conn->prepare("SELECT COUNT(*) as absent FROM attendance WHERE statuss = 'absent' AND day = ?");
$absent_stmt->bind_param("s", $date);
$absent_stmt->execute();
$absent_result = $absent_stmt->get_result();
if($absent_row = $absent_result->fetch_assoc()) {
    $absent_today = $absent_row['absent'];
}

// Calculate percentage
if($total_students > 0) {
    $attendance_percentage = round(($present_today / $total_students) * 100, 2);
}

// For View Attendance Tab - Get last 5 working days
$working_days = [];
for($i = 4; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $working_days[] = $day;
}

// Fetch attendance records for View tab
$view_attendance_data = [];
if(($search_view || $is_view_tab) && $view_class_id > 0) {
    // Get students in the class
    $student_sql = "SELECT id, name FROM students WHERE class_id = ? ORDER BY id";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bind_param("i", $view_class_id);
    $student_stmt->execute();
    $students_result = $student_stmt->get_result();
    
    while($student = $students_result->fetch_assoc()) {
        $student_id = $student['id'];
        $view_attendance_data[$student_id] = [
            'name' => $student['name'],
            'attendance' => [],
            'present_count' => 0,
            'absent_count' => 0
        ];
        
        // Get attendance for each day
        foreach($working_days as $day) {
            $att_sql = "SELECT statuss FROM attendance WHERE student_id = ? AND day = ?";
            $att_stmt = $conn->prepare($att_sql);
            $att_stmt->bind_param("is", $student_id, $day);
            $att_stmt->execute();
            $att_result = $att_stmt->get_result();
            
            if($att_row = $att_result->fetch_assoc()) {
                $status = $att_row['statuss'];
                $view_attendance_data[$student_id]['attendance'][$day] = $status;
                
                if($status == 'present') {
                    $view_attendance_data[$student_id]['present_count']++;
                } elseif($status == 'absent') {
                    $view_attendance_data[$student_id]['absent_count']++;
                }
            } else {
                $view_attendance_data[$student_id]['attendance'][$day] = 'notmarked';
            }
        }
        
        // Calculate percentage
        $total_days = count($working_days);
        $present_days = $view_attendance_data[$student_id]['present_count'];
        $view_attendance_data[$student_id]['percentage'] = ($total_days > 0) ? round(($present_days / $total_days) * 100, 2) : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .attendance-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 4px;
            font-weight: bold;
        }
        .attendance-present {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .attendance-absent {
            background-color: #f8d7da;
            color: #842029;
        }
        .attendance-notmarked {
            background-color: #fff3cd;
            color: #664d03;
        }
        .nav-tabs .nav-link.active {
            background-color: #f8f9fa;
            border-bottom-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../includes/auth.php'; ?>
    
    <div class="d-flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <nav class="navbar navbar-expand-lg navbar-light bg-white rounded mb-4 shadow-sm">
                <div class="container-fluid">
                    <h4 class="navbar-brand mb-0">Attendance Management</h4>
                    <div class="d-flex align-items-center">
                        <span class="me-3"><?php echo date('F d, Y'); ?></span>
                    </div>
                </div>
            </nav>
            
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show mx-3" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Selected Date</h6>
                                <h3><?php echo date('d/m/Y', strtotime($date)); ?></h3>
                                <small class="text-muted">Click to change</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Present</h6>
                                <h3 class="text-success"><?php echo $present_today; ?></h3>
                                <small class="text-muted">Out of <?php echo $total_students; ?> students</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Absent</h6>
                                <h3 class="text-danger"><?php echo $absent_today; ?></h3>
                                <small class="text-muted">Out of <?php echo $total_students; ?> students</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Attendance %</h6>
                                <h3 class="text-primary"><?php echo $attendance_percentage; ?>%</h3>
                                <small class="text-muted">For selected date</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <ul class="nav nav-tabs mb-4" id="attendanceTabs">
                    <li class="nav-item">
                        <button class="nav-link <?php echo !$is_view_tab ? 'active' : ''; ?>" 
                                data-bs-toggle="tab" data-bs-target="#markAttendance">
                            Mark Attendance
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?php echo $is_view_tab ? 'active' : ''; ?>" 
                                data-bs-toggle="tab" data-bs-target="#viewAttendance">
                            View Attendance
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- Mark Attendance Tab -->
                    <div class="tab-pane fade <?php echo !$is_view_tab ? 'show active' : ''; ?>" id="markAttendance">
                        <form action="" method="post">
                            <input type="hidden" name="tab" value="markAttendance">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <select class="form-select" name="class_id" id="selectClass">
                                                <option value="">-- Select Class --</option>
                                                <?php
                                                $q = $conn->query("SELECT id, class_name, section FROM classes");
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
                                            <input type="date" name="attendance_date" class="form-control" value="<?php echo $date; ?>">
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button type="submit" name="filter" class="btn btn-primary">
                                                <i class="bi bi-filter"></i> Filter
                                            </button>
                                            <button type="submit" name="save_attendance" class="btn btn-success">
                                                <i class="bi bi-save"></i> Save Attendance
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <?php if($class_id > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Roll No</th>
                                                    <th>Student Name</th>
                                                    <th>Status</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Fetch students for selected class   
                                                $student_sql = "SELECT students.id, students.name, 
                                                               attendance.statuss, attendance.remarks 
                                                               FROM students 
                                                               LEFT JOIN attendance ON students.id = attendance.student_id 
                                                               AND attendance.day = ?
                                                               WHERE students.class_id = ? 
                                                               ORDER BY students.id";
                                                $student_stmt = $conn->prepare($student_sql);
                                                $student_stmt->bind_param("si", $date, $class_id);
                                                $student_stmt->execute();
                                                $result = $student_stmt->get_result();
                                                
                                                if($result->num_rows > 0) {
                                                    while($row = $result->fetch_assoc()) {
                                                        $current_status = !empty($row['statuss']) ? $row['statuss'] : 'notmarked';
                                                        $current_remarks = !empty($row['remarks']) ? $row['remarks'] : '';
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td><?php echo $row['name']; ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <input type="radio" class="btn-check" name="attendance_<?php echo $row['id']; ?>" 
                                                                   id="notmarked_<?php echo $row['id']; ?>" value="notmarked" 
                                                                   <?php echo ($current_status == 'notmarked') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-warning" for="notmarked_<?php echo $row['id']; ?>">N-M</label>
                                                            
                                                            <input type="radio" class="btn-check" name="attendance_<?php echo $row['id']; ?>" 
                                                                   id="present_<?php echo $row['id']; ?>" value="present" 
                                                                   <?php echo ($current_status == 'present') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-success" for="present_<?php echo $row['id']; ?>">Present</label>
                                                            
                                                            <input type="radio" class="btn-check" name="attendance_<?php echo $row['id']; ?>" 
                                                                   id="absent_<?php echo $row['id']; ?>" value="absent" 
                                                                   <?php echo ($current_status == 'absent') ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-danger" for="absent_<?php echo $row['id']; ?>">Absent</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="student_ids[]" value="<?php echo $row['id']; ?>">
                                                        <input type="text" name="remarks_<?php echo $row['id']; ?>" 
                                                               class="form-control form-control-sm" 
                                                               value="<?php echo htmlspecialchars($current_remarks); ?>" 
                                                               placeholder="Optional">
                                                    </td>
                                                </tr>
                                                <?php
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="4" class="text-center">No students found in this class.</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-people display-1 text-muted"></i>
                                        <p class="text-muted">Please select a class to view students</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- View Attendance Tab -->
                    <div class="tab-pane fade <?php echo $is_view_tab ? 'show active' : ''; ?>" id="viewAttendance">
                        <form method="post" action="">
                            <input type="hidden" name="tab" value="viewAttendance">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Attendance Records</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <select class="form-select" name="view_class_id" id="selectClassView">
                                                <option value="">-- Select Class --</option>
                                                <?php
                                                $q = $conn->query("SELECT id, class_name, section FROM classes");
                                                while ($c = $q->fetch_assoc()) {
                                                    $selected = ($view_class_id == $c['id']) ? 'selected' : '';
                                                    echo "<option value='{$c['id']}' $selected>
                                                        Class {$c['class_name']} - {$c['section']}
                                                    </option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="month" name="view_month" class="form-control" value="<?php echo $view_month; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" name="search_view" class="btn btn-primary w-100">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-success w-100">
                                                <i class="bi bi-download"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <?php if(($search_view || $is_view_tab) && $view_class_id > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <?php foreach($working_days as $day): ?>
                                                    <th class="text-center"><?php echo date('d/m', strtotime($day)); ?></th>
                                                    <?php endforeach; ?>
                                                    <th class="text-center">Present</th>
                                                    <th class="text-center">Absent</th>
                                                    <th class="text-center">Attendance %</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(!empty($view_attendance_data)): ?>
                                                    <?php foreach($view_attendance_data as $student_id => $student_data): ?>
                                                    <tr>
                                                        <td><?php echo $student_data['name']; ?></td>
                                                        <?php foreach($working_days as $day): 
                                                            $status = $student_data['attendance'][$day];
                                                            $status_letter = ($status == 'present') ? 'P' : (($status == 'absent') ? 'A' : 'N');
                                                        ?>
                                                        <td class="text-center">
                                                            <span class="attendance-badge attendance-<?php echo $status; ?>">
                                                                <?php echo $status_letter; ?>
                                                            </span>
                                                        </td>
                                                        <?php endforeach; ?>
                                                        <td class="text-center"><?php echo $student_data['present_count']; ?></td>
                                                        <td class="text-center"><?php echo $student_data['absent_count']; ?></td>
                                                        <td class="text-center">
                                                            <?php 
                                                            $percentage = $student_data['percentage'];
                                                            $badge_class = ($percentage >= 75) ? 'bg-success' : (($percentage >= 50) ? 'bg-warning' : 'bg-danger');
                                                            ?>
                                                            <span class="badge <?php echo $badge_class; ?>">
                                                                <?php echo $percentage; ?>%
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="<?php echo count($working_days) + 4; ?>" class="text-center">
                                                            No attendance records found for this class.
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php elseif($is_view_tab): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar-check display-1 text-muted"></i>
                                        <p class="text-muted">Select a class and click Search to view attendance records</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store current active tab in session
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we need to activate view tab
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = '<?php echo $is_view_tab ? "viewAttendance" : "markAttendance"; ?>';
            
            // Activate the correct tab
            const triggerEl = document.querySelector(`[data-bs-target="#${activeTab}"]`);
            if(triggerEl) {
                const tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
            
            // Handle form submissions to stay on same tab
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    // Add a small delay to ensure tab state is preserved
                    setTimeout(() => {
                        const activeTabButton = document.querySelector('#attendanceTabs .nav-link.active');
                        if(activeTabButton) {
                            const tabTarget = activeTabButton.getAttribute('data-bs-target');
                            if(tabTarget === '#viewAttendance') {
                                // Add hidden input for view tab
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'tab';
                                hiddenInput.value = 'viewAttendance';
                                this.appendChild(hiddenInput);
                            }
                        }
                    }, 10);
                });
            });
            
            // Auto-submit form when class or date changes in mark attendance
            const selectClass = document.getElementById('selectClass');
            if(selectClass) {
                selectClass.addEventListener('change', function() {
                    // Add hidden input to indicate mark attendance tab
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'tab';
                    hiddenInput.value = 'markAttendance';
                    this.form.appendChild(hiddenInput);
                    this.form.submit();
                });
            }
            
            // Auto-submit form when class changes in view attendance
            const selectClassView = document.getElementById('selectClassView');
            if(selectClassView) {
                selectClassView.addEventListener('change', function() {
                    // Add hidden input to indicate view attendance tab
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'tab';
                    hiddenInput.value = 'viewAttendance';
                    this.form.appendChild(hiddenInput);
                    this.form.submit();
                });
            }
        });
        
        // Initialize tabs
        const triggerTabList = document.querySelectorAll('#attendanceTabs button')
        triggerTabList.forEach(triggerEl => {
            triggerEl.addEventListener('click', function(event) {
                event.preventDefault();
                const tabTrigger = new bootstrap.Tab(this);
                tabTrigger.show();
                
                // Store active tab in sessionStorage
                const tabTarget = this.getAttribute('data-bs-target');
                sessionStorage.setItem('activeAttendanceTab', tabTarget);
            });
        });
        
        // Restore active tab on page load
        window.addEventListener('load', function() {
            const savedTab = sessionStorage.getItem('activeAttendanceTab');
            if(savedTab) {
                const triggerEl = document.querySelector(`[data-bs-target="${savedTab}"]`);
                if(triggerEl) {
                    const tab = new bootstrap.Tab(triggerEl);
                    tab.show();
                }
            }
        });
    </script>
</body>
</html>