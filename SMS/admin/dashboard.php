<?php session_start(); 

include '../includes/db.php';
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
else { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/auth.php'; ?>

    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white rounded mb-4 shadow-sm">
                <div class="container-fluid">
                    <h4 class="navbar-brand mb-0">Dashboard</h4>
                    <div class="d-flex align-items-center">
                        <span class="me-3 text-muted" id="current-date">
                            <?php echo date('F d, Y'); ?>
                        </span>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card students">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Total Students</h6>
                                        <h3 id="total-students"><?php include '../includes/db.php'; $stmt = $conn->prepare("SELECT COUNT(*) FROM students"); $stmt->execute(); $result = $stmt->get_result(); $count = $result->fetch_row()[0]; echo $count; ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-people-fill fs-1 text-success"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="bi bi-arrow-up"></i> <?php echo $count > 0 ? "12%" : "0%"; ?> </span>
                                    <span class="text-muted">from last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card teachers">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Total Teachers</h6>
                                        <h3 id="total-teachers"><?php include '../includes/db.php'; $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers"); $stmt->execute(); $result = $stmt->get_result(); $count = $result->fetch_row()[0]; echo $count; ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-person-badge-fill fs-1 text-warning"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="bi bi-arrow-up"></i> 5% </span>
                                    <span class="text-muted">from last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card classes">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Total Classes</h6>
                                        <h3 id="total-classes"><?php include '../includes/db.php'; $stmt = $conn->prepare("SELECT COUNT(*) FROM classes"); $stmt->execute(); $result = $stmt->get_result(); $count = $result->fetch_row()[0]; echo $count; ?></h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-building fs-1 text-danger"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-muted">Active classes</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card attendance">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Today's Attendance</h6>
                                        <h3 id="attendance-rate">94%</h3>
                                    </div>
                                    <div>
                                        <i class="bi bi-calendar-check fs-1 text-info"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="bi bi-arrow-up"></i> 2% </span>
                                    <span class="text-muted">from yesterday</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Students</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Class</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                <?php
                                include '../includes/db.php';
                                // Fetch recent students
                                $stmt = $conn->prepare("  SELECT students.id,students.name,students.dob,
                                students.gender,students.email,students.phone,
                                classes.class_name,classes.section
                                FROM students JOIN classes ON 
                                students.class_id = classes.id
                                ORDER BY students.id DESC LIMIT 5");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if($result->num_rows > 0){
                                 while($row = $result->fetch_assoc()){
                                ?>
                                        <tbody>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo $row['name']; ?></td>
                                                <td><?php echo $row['class_name']; ?></td>
                                                <td><?php echo $row['email']; ?></td>
                                                <td><?php echo $row['phone']; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary"><i class="bi bi-eye"></i></button>
                                                    <button class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></button>
                                                </td>
                                            </tr>
                                           
                                            <?php }} else { ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No recent students found.</td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end">
                                    <a href="students.php" class="btn btn-primary">View All Students</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Upcoming Events</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <div class="list-group-item border-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Annual Sports Day</h6>
                                            <small>Tomorrow</small>
                                        </div>
                                        <p class="mb-1">School grounds, 9:00 AM</p>
                                    </div>
                                    <div class="list-group-item border-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Parent-Teacher Meeting</h6>
                                            <small>Dec 15</small>
                                        </div>
                                        <p class="mb-1">Main auditorium, 2:00 PM</p>
                                    </div>
                                    <div class="list-group-item border-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Science Fair</h6>
                                            <small>Dec 20</small>
                                        </div>
                                        <p class="mb-1">Science lab, 10:00 AM</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="students.php?action=add" class="btn btn-success">
                                        <i class="bi bi-person-plus"></i> Add New Student
                                    </a>
                                    <a href="attendance.php" class="btn btn-info">
                                        <i class="bi bi-calendar-check"></i> Mark Attendance
                                    </a>
                                    <a href="marks.php?action=add" class="btn btn-warning">
                                        <i class="bi bi-journal-plus"></i> Add Marks
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php } ?>