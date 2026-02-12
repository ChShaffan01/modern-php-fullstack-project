<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-bg">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card login-card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-mortarboard-fill display-4 text-primary"></i>
                            <h2 class="mt-3">School Management System</h2>
                            <p class="text-muted">Please sign in to continue</p>
                        </div>
                        
                        <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>' method='post' id="loginForm">


                            <?php
                                if(isset($_SESSION['error'])){
                                    echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
                                    unset($_SESSION['error']);
                                } ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" placeholder="Enter email" name='email' required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" placeholder="Enter password" name='pwd' required >
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="role" class="form-label">Login as</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="teacher">Teacher</option>
                                </select>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                                </button>
                            </div>
                            
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/auth.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>