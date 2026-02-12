
<nav class="sidebar">
    <div class="sidebar-header p-3">
        <h3 class="sidebar-brand">
            <i class="bi bi-mortarboard-fill"></i> SchoolSys
        </h3>
        <hr class="bg-light">
    </div>
    <?php $user_role = $_SESSION['user_role']; ?>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" href="students.php">
                <i class="bi bi-people-fill"></i> <span>Students</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : ''; ?>" href="teachers.php">
                <i class="bi bi-person-badge-fill"></i> <span>Teachers</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>" href="attendance.php">
                <i class="bi bi-calendar-check"></i> <span>Attendance</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'marks.php' ? 'active' : ''; ?>" href="marks.php">
                <i class="bi bi-journal-check"></i> <span>Marks</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'check_result.php' ? 'active' : ''; ?>" href="check_result.php">
                 <i class="bi bi-building"></i> <span>Check    Result</span>
            </a>
        </li>
        
    </ul>
    
    <div class="sidebar-footer p-3 position-absolute bottom-0 w-100">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="bi bi-person-circle fs-4"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
                <small class="text-muted"><?php echo ucfirst($user_role); ?></small>
            </div>
            <a href="../logout.php" class="text-light">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</nav>