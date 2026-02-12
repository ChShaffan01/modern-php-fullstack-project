<?php session_start(); 
include '../includes/db.php';
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - School Management System</title>
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
                    <h4 class="navbar-brand mb-0">Student Management</h4>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-person-plus"></i> Add New Student
                        </button>

                    </div>
                </div>
            </nav>
            
            <!-- Students Content -->
            <div class="container-fluid">
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" placeholder="Search by name or ID...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select class="form-select" name='class'>
                                    <?php
                                 include '../includes/db.php';
                                 $q = $conn->query("SELECT id, class_name, section FROM classes");
                                 while($c = $q->fetch_assoc()){
                                  echo "<option value='{$c['id']}'>
                                    Class {$c['class_name']} - {$c['section']}
                                        </option>";
                                }
                                ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name='gender'>
                                    <option value="">All Genders</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" name="filter" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </form>
                        <table>
                            
                        </table>
                         <?php if(isset($_GET['filter'])): ?>
                            <div class="mt-3">
                                <strong>Filtered Results:</strong>
                                <?php
                                $search = $_GET['search'] ?? '';
                                $class = $_GET['class'] ?? '';
                                $gender = $_GET['gender'] ?? '';
                                
                                $sql = "SELECT students.id,students.name,students.dob,
                                students.gender,students.email,students.phone,
                                classes.class_name,classes.section
                                FROM students JOIN classes ON 
                                students.class_id = classes.id WHERE 1=1";
                                
                                if(!empty($search)){
                                    $sql .= " AND (students.name LIKE '%$search%' OR students.id LIKE '%$search%')";
                                }
                                
                                if(!empty($class)){
                                    $sql .= " AND students.class_id = '$class'";
                                }
                                
                                if(!empty($gender)){
                                    $sql .= " AND students.gender = '$gender'";
                                }
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                
                                 if($result->num_rows > 0){
                                     while($row = $result->fetch_assoc()){ ?>
                                      <div style=" padding:5px; margin:5px; display:flex; justify-content:space-between; align-items:center;">
                                    
                                        <span><?php echo $row['id']; ?></span>
                                        <span>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?php echo substr($row['name'], 0, 2); ?>
                                                </div>
                                                <?php echo $row['name']; ?>
                                            </div>
                                        </span>
                                        <span>Class <?php echo $row['class_name']; ?> - <?php echo $row['section']; ?></span>
                                        <span><?php echo $row['dob']; ?></span>
                                        <span class="badge bg-info"><?php echo $row['gender']; ?></span>
                                        <span><?php echo $row['email']; ?></span>
                                        <span><?php echo $row['phone']; ?></span>
                                        <span>
                                           <button class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="modal" data-bs-target="#editStudentModal">
                                               <a href="students.php?id=<?php echo $row['id']; ?>"
                                                   style="color:black;">
                                                    <i class="bi bi-pencil"></i>
                                                    </a>
                                            </button>
                                                <a href="../includes/delete_student.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"onclick="return confirm('Are you sure you want to delete?');">
                                                    <i class="bi bi-trash"></i>
                                                    </a>
                                     </span>
                                     </div>
                                <?php }} else {echo "<p>No matching students found.</p>";}
                                 endif;?>
                                 
                            </div>
                       </div>
                    </div>
                </div>
                
                <!-- Students Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Student List</h5>
                        <span class="badge bg-primary"><?php $stmt = $conn->prepare("SELECT COUNT(*) FROM students"); $stmt->execute(); $result = $stmt->get_result(); $count = $result->fetch_row()[0]; echo "Total: $count Students"; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Class</th>
                                        <th>Date of Birth</th>
                                        <th>Gender</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                  <?php
                                include '../includes/db.php';
                                // Fetch recent students
                                $stmt = $conn->prepare("   SELECT students.id,students.name,students.dob,
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
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?php echo substr($row['name'], 0, 2); ?>
                                                </div>
                                                <?php echo $row['name']; ?>
                                            </div>
                                        </td>
                                        <td>Class <?php echo $row['class_name']; ?> - <?php echo $row['section']; ?></td>
                                        <td><?php echo $row['dob']; ?></td>
                                        <td><span class="badge bg-info"><?php echo $row['gender']; ?></span></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo $row['phone']; ?></td>
                                        <td>
                                
                                            <button class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="modal" data-bs-target="#editStudentModal">
                                               <a href="students.php?id=<?php echo $row['id']; ?>"
                                                   style="color:black;">
                                                    <i class="bi bi-pencil"></i>
                                                    </a>
                                            </button>
                                                <a href="../includes/delete_student.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"onclick="return confirm('Are you sure you want to delete?');">
                                                    <i class="bi bi-trash"></i>
                                                    </a>
                                        </td>
                                    </tr>
                                    <?php }} else { ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No recent students found.</td>
                                    </tr>
                                    <?php } ?>      
                                </tbody>

                            </table>
                        </div>
                      
                     
                        <!-- Pagination -->
                        <!-- <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm" method="POST" method="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name='name' class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" name='dob' class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-select" name='gender' required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Class *</label>
                                
                                <select class="form-select" name='class' required>
                                <?php
                                 include '../includes/db.php';
                                 $q = $conn->query("SELECT id, class_name, section FROM classes");
                                 while($c = $q->fetch_assoc()){
                                  echo "<option value='{$c['id']}'>
                                    Class {$c['class_name']} - {$c['section']}
                                        </option>";
                                }
                                ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name='email' class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name='phone' class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name='address' rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addStudentForm" name="add_stu" class="btn btn-primary">Add Student</button>
                </div>
            </div>
        </div>
    </div>
    <?php 
    include '../includes/db.php';
    if(isset($_POST['add_stu'])){
     
    function clean_data($data){
        $data = htmlspecialchars(trim($data));
        return $data;
    }
    $name = clean_data($_POST['name']);
    $dob = clean_data($_POST['dob']);
    $gender = clean_data($_POST['gender']);
    $class = clean_data($_POST['class']);
    $dob = clean_data($_POST['dob']);
    $email = clean_data($_POST['email']);
    $phone = clean_data($_POST['phone']);
    $address = clean_data($_POST['address']);
    
    $stmt = $conn->prepare("INSERT INTO `students`(`class_id`, `name`, `dob`, `gender`, `email`, `phone`) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss",$class,$name,$dob,$gender,$email,$phone);
    if($stmt->execute()){
    header("Location: students.php?success=1");
    exit;
  } else {
    echo "Error: " . $stmt->error;
  }
}

    
    ?>
    
   <!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <?php
        if(isset($_GET['id'])){
          $student_id = (int)$_GET['id'];

          $stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
          $stmt->bind_param("i",$student_id);
          $stmt->execute();
          $student = $stmt->get_result()->fetch_assoc();
        }
        ?>

        <?php if(!empty($student)): ?>
        <form method="POST" id="editStudentForm">

          <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">

          <div class="row">

            <div class="col-md-6 mb-3">
              <label>Name</label>
              <input type="text" name="name" class="form-control" value="<?php echo $student['name']; ?>" required>
            </div>

            <div class="col-md-6 mb-3">
              <label>DOB</label>
              <input type="date" name="dob" class="form-control" value="<?php echo $student['dob']; ?>" required>
            </div>

            <div class="col-md-6 mb-3">
              <label>Gender</label>
              <select name="gender" class="form-select">
                <option value="male" <?= $student['gender']=='male'?'selected':'' ?>>Male</option>
                <option value="female" <?= $student['gender']=='female'?'selected':'' ?>>Female</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label>Class</label>
              <select name="class" class="form-select">
                <?php
                $q = $conn->query("SELECT id,class_name,section FROM classes");
                while($c = $q->fetch_assoc()){
                  $selected = ($c['id']==$student['class_id'])?'selected':'';
                  echo "<option value='{$c['id']}' $selected>
                        Class {$c['class_name']} - {$c['section']}
                        </option>";
                }
                ?>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label>Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo $student['email']; ?>">
            </div>

            <div class="col-md-6 mb-3">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control" value="<?php echo $student['phone']; ?>">
            </div>

          </div>
        </form>
        <?php endif; ?>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="editStudentForm" name="update_student" class="btn btn-primary">
          Save Changes
        </button>
      </div>

    </div>
  </div>
</div>
<?php
if(isset($_POST['update_student'])){
  $id     = (int)$_POST['student_id'];
  $name   = $_POST['name'];
  $dob    = $_POST['dob'];
  $gender = $_POST['gender'];
  $class  = $_POST['class'];
  $email  = $_POST['email'];
  $phone  = $_POST['phone'];

  $stmt = $conn->prepare("
    UPDATE students
    SET class_id=?, name=?, dob=?, gender=?, email=?, phone=?
    WHERE id=?
  ");
  $stmt->bind_param("isssssi",$class,$name,$dob,$gender,$email,$phone,$id);
  $stmt->execute();

  header("Location: students.php?updated=1");
  exit;
}
?>
<?php if(isset($_GET['id'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function(){
  new bootstrap.Modal(
    document.getElementById('editStudentModal')
  ).show();
});
</script>
<?php endif; ?>
    <!-- Footer -->  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</body>
</html>