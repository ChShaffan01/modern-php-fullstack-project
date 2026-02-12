document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;
            
            // Simple validation
            if (!email || !password || !role) {
                showAlert('Please fill in all fields', 'danger');
                return;
            }
            
            // Demo authentication
            const demoAdmin = {
                email: 'admin@school.com',
                password: 'password',
                role: 'admin'
            };
            
            const demoTeacher = {
                email: 'teacher@school.com',
                password: 'password',
                role: 'teacher'
            };
            
            let isValid = false;
            let userData = null;
            
            // Check demo credentials
            if (email === demoAdmin.email && password === demoAdmin.password && role === demoAdmin.role) {
                isValid = true;
                userData = {
                    name: 'Administrator',
                    email: email,
                    role: role
                };
            } else if (email === demoTeacher.email && password === demoTeacher.password && role === demoTeacher.role) {
                isValid = true;
                userData = {
                    name: 'John Teacher',
                    email: email,
                    role: role
                };
            }
            
            if (isValid) {
                // Store user data in localStorage (for demo)
                localStorage.setItem('user', JSON.stringify(userData));
                
                // Show success message
                showAlert('Login successful! Redirecting to dashboard...', 'success');
                
                // Redirect to admin dashboard
                setTimeout(() => {
                    window.location.href = 'admin/dashboard.php';
                }, 1500);
            } else {
                showAlert('Invalid credentials. Please try again.', 'danger');
            }
        });
    }
    
    // Show alert function
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Remove any existing alerts
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Insert alert before form
        loginForm.parentNode.insertBefore(alertDiv, loginForm);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
});