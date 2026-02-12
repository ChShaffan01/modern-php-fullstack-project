<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <i class="bi bi-mortarboard-fill display-1 text-primary mb-4"></i>
                        <h1 class="display-4 mb-3">School Management System</h1>
                        <p class="lead mb-4">A comprehensive system to manage students, teachers, classes, attendance, and marks</p>
                        <div class="row mt-5">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-people-fill fs-1 text-success mb-3"></i>
                                        <h5>Student Management</h5>
                                        <p class="text-muted">Add, edit, and manage student records</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-person-badge-fill fs-1 text-warning mb-3"></i>
                                        <h5>Teacher Management</h5>
                                        <p class="text-muted">Manage teacher information and assignments</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-calendar-check fs-1 text-danger mb-3"></i>
                                        <h5>Attendance Tracking</h5>
                                        <p class="text-muted">Track daily student attendance</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-journal-check fs-1 text-info mb-3"></i>
                                        <h5>Marks Management</h5>
                                        <p class="text-muted">Record and analyze student performance</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <a href="login.php" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-box-arrow-in-right"></i> Login to System
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>