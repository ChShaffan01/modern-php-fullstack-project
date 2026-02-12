<?php session_start(); 
include '../includes/db.php';
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
} ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers - School Management System</title>
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
                    <h4 class="navbar-brand mb-0">Teacher Management</h4>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="bi bi-person-plus"></i> Add New Teacher
                        </button>
                    </div>
                </div>
            </nav>
            
            <!-- Teachers Content -->
            <div class="container-fluid">
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Total Teachers</h6>
                                <h3><?php $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers"); $stmt->execute(); $result = $stmt->get_result(); $count = $result->fetch_row()[0]; echo $count . "+"; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Active Teachers</h6>
                                <h3><?php $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers WHERE status = 'Active'"); $stmt->execute(); $result = $stmt->get_result(); $count = $result->fetch_row()[0]; echo  $count . "+"; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Subjects Taught</h6>
                                <h3><?php $stmt = $conn->prepare("SELECT COUNT(DISTINCT subject) FROM teachers"); $stmt->execute(); $result = $stmt->get_result(); $count = $result->fetch_row()[0]; echo $count . "+"; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Avg. Experience</h6>
                                <h3><?php $stmt = $conn->prepare("SELECT AVG(experience) FROM teachers"); $stmt->execute(); $result = $stmt->get_result(); $avg_experience = $result->fetch_row()[0]; echo number_format($avg_experience, 1)." Years+"; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Teachers Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Teacher List</h5>
                        <div class="input-group" style="width: 300px;">
                            <input type="text" class="form-control" name="search" placeholder="Search teachers...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Subject</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Assigned Classes</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                               <?php
                                include '../includes/db.php';
                                // Fetch recent students
                                $stmt = $conn->prepare("SELECT * FROM `teachers` WHERE 1");
                                $stmt->execute();
                                $result = $stmt->get_result();
                             if($result->num_rows > 0){
                                 while($row = $result->fetch_assoc()){
                                ?>
                                <tbody>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td>
                                            <?php echo $row['name']; ?>
                                          
                                        </td>
                                        <td><?php echo $row['subject']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo $row['phone']; ?></td>
                                        <td>Class : <?php echo $row['assigned_classes']; ?> </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2" >
                                                     <?php echo $row['status']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="modal" data-bs-target="#editTeacherModal">
                                               <a href="teachers.php?edit_id=<?php echo $row['id']; ?>" style="color:black;">
                                                <i class="bi bi-pencil"></i>
                                                </a>
                                            </button>
                                                <a href="../includes/teacher_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"onclick="return confirm('Are you sure you want to delete?');">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTeacherForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subject *</label>
                                <select class="form-select" name="subject" required>
                                    <option value="">Select Subject</option>
                                    <option>Mathematics</option>
                                    <option>Science</option>
                                    <option>English</option>
                                    <option>History</option>
                                    <option>Geography</option>
                                    <option>Computer Science</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Years of Experience</label>
                                <input type="number" name="experience" class="form-control" min="0" step="0.5">
                            </div>
                             <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                    <label class="form-label">Assigned Classes</label>

                                    <?php
                                    include '../includes/db.php';
                                    $q = $conn->query("SELECT id, class_name, section FROM classes");

                                    while($c = $q->fetch_assoc()):
                                    ?>
                                        <div class="form-check form-check-inline">
                                        <input 
                                            class="form-check-input"
                                            type="checkbox"
                                            name="class_ids[]"
                                            id="class_<?php echo $c['id']; ?>"
                                            value="<?php echo $c['id']; ?>"
                                        >
                                        <label class="form-check-label" for="class_<?php echo $c['id']; ?>">
                                            Class <?php echo $c['class_name']; ?> - <?php echo $c['section']; ?>
                                        </label>
                                        </div>
                                    <?php endwhile; ?>

                                    </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addTeacherForm" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                </div>
            </div>
        </div>
    </div>

      <?php 
    include '../includes/db.php';
    if(isset($_POST['add_teacher'])){
     
    function clean_data($data){
        $data = htmlspecialchars(trim($data));
        return $data;
    }
    $name = clean_data($_POST['name']);
   $class_ids = $_POST['class_ids'] ?? [];
   $assigned_classes = implode(',', $class_ids);
    $email = clean_data($_POST['email']);
    $status = clean_data($_POST['status']);
    $experience = clean_data($_POST['experience']);
    $subject = clean_data($_POST['subject']);
    $phone = clean_data($_POST['phone']);
    $address = clean_data($_POST['address']);
    
    $stmt = $conn->prepare("INSERT INTO `teachers`( `name`, `subject`, `email`, `phone`, `status`, `assigned_classes`, `experience`,`address`) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssss",$name,$subject,$email,$phone,$status,$assigned_classes,$experience,$address);
    if($stmt->execute()){
    // header("Location:teachers.php?success=1");
    exit;
  } else {
    echo "Error: " . $stmt->error;
  }
}

    ?>
    <!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Teacher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <?php
        if(isset($_GET['edit_id'])){
          $teacher_id = (int)$_GET['edit_id'];
          $stmt = $conn->prepare("SELECT * FROM teachers WHERE id=?");
          $stmt->bind_param("i",$teacher_id);
          $stmt->execute();
          $teacher = $stmt->get_result()->fetch_assoc();
        }
        ?>

        <?php if(!empty($teacher)): ?>
        <form method="POST" id="editTeacherForm">

          <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">

          <div class="row">

            <div class="col-md-6 mb-3">
              <label>Full Name</label>
              <input type="text" name="name" class="form-control"
                     value="<?php echo $teacher['name']; ?>" required>
            </div>

            <div class="col-md-6 mb-3">
              <label>Subject</label>
              <select name="subject" class="form-select" required>
                <?php
                $subjects = ['Mathematics','Science','English','History','Geography','Computer Science'];
                foreach($subjects as $sub){
                  $sel = ($teacher['subject']==$sub)?'selected':'';
                  echo "<option $sel>$sub</option>";
                }
                ?>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label>Email</label>
              <input type="email" name="email" class="form-control"
                     value="<?php echo $teacher['email']; ?>" required>
            </div>

            <div class="col-md-6 mb-3">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control"
                     value="<?php echo $teacher['phone']; ?>" required>
            </div>

            <div class="col-md-6 mb-3">
              <label>Experience (Years)</label>
              <input type="number" step="0.5" name="experience"
                     class="form-control"
                     value="<?php echo $teacher['experience']; ?>">
            </div>

            <div class="col-md-6 mb-3">
              <label>Status</label>
              <select name="status" class="form-select">
                <option value="active"   <?= $teacher['status']=='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $teacher['status']=='inactive'?'selected':'' ?>>Inactive</option>
              </select>
            </div>

            <!-- Assigned Classes -->
            <div class="col-md-12 mb-3">
              <label class="form-label">Assigned Classes</label>
              <?php
              $assigned = explode(',', $teacher['assigned_classes']);
              $q = $conn->query("SELECT id,class_name,section FROM classes");
              while($c = $q->fetch_assoc()){
                $checked = in_array($c['id'],$assigned)?'checked':'';
                echo "
                <div class='form-check form-check-inline'>
                  <input class='form-check-input'
                         type='checkbox'
                         name='class_ids[]'
                         value='{$c['id']}'
                         $checked>
                  <label class='form-check-label'>
                    Class {$c['class_name']} - {$c['section']}
                  </label>
                </div>";
              }
              ?>
            </div>

            <div class="col-md-12 mb-3">
              <label>Address</label>
              <textarea name="address" class="form-control"
                        rows="3"><?php echo $teacher['address']; ?></textarea>
            </div>

          </div>
        </form>
        <?php endif; ?>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="editTeacherForm"
                name="update_teacher"
                class="btn btn-primary">
          Save Changes
        </button>
      </div>

    </div>
  </div>
</div>
<?php
if(isset($_POST['update_teacher'])){

  $id        = (int)$_POST['teacher_id'];
  $name      = $_POST['name'];
  $subject   = $_POST['subject'];
  $email     = $_POST['email'];
  $phone     = $_POST['phone'];
  $status    = $_POST['status'];
  $experience= $_POST['experience'];
  $address   = $_POST['address'];

  $class_ids = $_POST['class_ids'] ?? [];
  $assigned_classes = implode(',', $class_ids);

  $stmt = $conn->prepare("
    UPDATE teachers SET
      name=?,
      subject=?,
      email=?,
      phone=?,
      status=?,
      assigned_classes=?,
      experience=?,
      address=?
    WHERE id=?
  ");

  $stmt->bind_param(
    "ssssssssi",
    $name,$subject,$email,$phone,$status,
    $assigned_classes,$experience,$address,$id
  );

  $stmt->execute();
//   header("Location: teachers.php?updated=1");+
  exit;
}
?>

    <?php if(isset($_GET['edit_id'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var editModal = new bootstrap.Modal(
        document.getElementById('editTeacherModal')
    );
    editModal.show();
});
</script>
<?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>