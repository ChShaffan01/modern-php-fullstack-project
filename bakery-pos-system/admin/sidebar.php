<?php
// admin/sidebar.php
?>
<!-- Sidebar -->
<div class="sidebar">
    <!-- Sidebar Header with Branding -->
    <div class="sidebar-header">
        <div class="brand-info">
            <div class="brand-icon">
                <i class="fas fa-bread-slice"></i>
            </div>
            <div class="brand-text">
                <h5 class="brand-name"><?php echo SITE_NAME; ?></h5>
                <small class="brand-tag">Admin Panel</small>
            </div>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- User Profile -->
    <div class="user-profile">
        <div class="user-avatar">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="user-info">
            <h6 class="user-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Administrator'; ?></h6>
            <span class="user-role badge">Admin</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">
        <div class="menu-section">
            <h6 class="menu-title">MAIN</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </span>
                        <span class="nav-text">Dashboard</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <h6 class="menu-title">MANAGEMENT</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-box"></i>
                        </span>
                        <span class="nav-text">Products</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'products.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="inventory.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'inventory.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-warehouse"></i>
                        </span>
                        <span class="nav-text">Inventory</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'inventory.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-tags"></i>
                        </span>
                        <span class="nav-text">Categories</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'categories.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <h6 class="menu-title">REPORTS</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="sales_report.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sales_report.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-chart-line"></i>
                        </span>
                        <span class="nav-text">Sales Reports</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'sales_report.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </span>
                        <span class="nav-text">Orders</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'orders.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li> -->
                <!-- <li class="nav-item">
                    <a href="access_check.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'access_check.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-chart-pie"></i>
                        </span>
                        <span class="nav-text">Access Check</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'access_check.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li> -->
            </ul>
        </div>

        <div class="menu-section">
            <h6 class="menu-title">SYSTEM</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-users-cog"></i>
                        </span>
                        <span class="nav-text">Users</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'users.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="fas fa-cogs"></i>
                        </span>
                        <span class="nav-text">Settings</span>
                        <?php if(basename($_SERVER['PHP_SELF']) === 'settings.php'): ?>
                            <span class="nav-indicator"></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="sidebar-footer">
        <div class="quick-actions">
            <a href="../cashier/pos.php" class="quick-action pos-action">
                <i class="fas fa-cash-register"></i>
                <span>POS Terminal</span>
            </a>
            <a href="../logout.php" class="quick-action logout-action">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
        <div class="sidebar-info">
            <small class="text-muted">
                <i class="fas fa-clock"></i> 
                <?php echo date('h:i A'); ?>
            </small>
        </div>
    </div>
</div>

<!-- CSS Styles -->
<style>
/* Professional Bakery Sidebar Styles */
:root {
    --sidebar-bg: #2c3e50;
    --sidebar-accent: #8B4513;
    --sidebar-hover: #34495e;
    --sidebar-text: #ecf0f1;
    --sidebar-active: #3498db;
    --bakery-gold: #f39c12;
    --bakery-cream: #fef9e7;
}

.sidebar {
    width: 260px;
    height: 100vh;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

/* Sidebar Header */
.sidebar-header {
    padding: 20px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(0,0,0,0.2);
}

.brand-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--sidebar-accent), var(--bakery-gold));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
}

.brand-text {
    line-height: 1.2;
}

.brand-name {
    margin: 0;
    font-weight: 600;
    font-size: 1.1rem;
    color: white;
}

.brand-tag {
    font-size: 0.75rem;
    color: var(--bakery-gold);
    opacity: 0.8;
}

.sidebar-toggle {
    background: transparent;
    border: none;
    color: var(--sidebar-text);
    cursor: pointer;
    font-size: 1.2rem;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s;
}

.sidebar-toggle:hover {
    background: rgba(255,255,255,0.1);
    transform: rotate(90deg);
}

/* User Profile */
.user-profile {
    padding: 20px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
}

.user-info {
    flex: 1;
}

.user-name {
    margin: 0;
    font-weight: 600;
    font-size: 0.95rem;
}

.user-role {
    background: var(--bakery-gold);
    color: white;
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 10px;
    margin-top: 5px;
}

/* Menu Sections */
.sidebar-menu {
    flex: 1;
    overflow-y: auto;
    padding: 15px 0;
}

.menu-section {
    margin-bottom: 20px;
}

.menu-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255,255,255,0.5);
    padding: 0 20px;
    margin-bottom: 10px;
    font-weight: 600;
}

.nav {
    padding: 0;
}

.nav-item {
    margin-bottom: 2px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: var(--sidebar-text);
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
    border-left: 3px solid transparent;
}

.nav-link:hover {
    background: var(--sidebar-hover);
    color: white;
    border-left-color: var(--bakery-gold);
}

.nav-link.active {
    background: linear-gradient(90deg, rgba(139, 69, 19, 0.2), transparent);
    color: white;
    border-left-color: var(--sidebar-accent);
    font-weight: 500;
}

.nav-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 1.1rem;
    color: var(--bakery-gold);
}

.nav-text {
    flex: 1;
    font-size: 0.9rem;
}

.nav-indicator {
    width: 6px;
    height: 6px;
    background: var(--sidebar-accent);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* Sidebar Footer */
.sidebar-footer {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding: 15px;
    background: rgba(0,0,0,0.2);
}

.quick-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.quick-action {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    color: var(--sidebar-text);
    text-decoration: none;
    transition: all 0.3s;
    font-size: 0.85rem;
}

.quick-action:hover {
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.quick-action i {
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.quick-action.pos-action {
    color: #2ecc71;
}

.quick-action.logout-action {
    color: #e74c3c;
}

.sidebar-info {
    text-align: center;
    font-size: 0.8rem;
    color: rgba(255,255,255,0.5);
}

/* Collapsed Sidebar State */
.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .brand-text,
.sidebar.collapsed .user-info,
.sidebar.collapsed .menu-title,
.sidebar.collapsed .nav-text,
.sidebar.collapsed .quick-action span,
.sidebar.collapsed .sidebar-info {
    display: none;
}

.sidebar.collapsed .brand-icon {
    margin: 0 auto;
}

.sidebar.collapsed .user-avatar {
    margin: 0 auto;
    width: 40px;
    height: 40px;
}

.sidebar.collapsed .nav-link {
    justify-content: center;
    padding: 15px;
}

.sidebar.collapsed .nav-icon {
    margin: 0;
}

/* Main Content Area */
.main-content {
    margin-left: 260px;
    width: calc(100% - 260px);
    min-height: 100vh;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.sidebar.collapsed ~ .main-content {
    margin-left: 70px;
    width: calc(100% - 70px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .mobile-menu-toggle {
        display: block;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
    }
}
</style>

<!-- JavaScript for Interactive Features -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
    
    // Load saved sidebar state
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }
    
    // Highlight current page in menu
    const currentPage = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href.includes(currentPage)) {
            link.classList.add('active');
        }
    });
    
    // Mobile menu toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});
</script>

<!-- Mobile Menu Toggle Button (Add to your main layout if needed) -->
<!-- 
<button class="mobile-menu-toggle d-lg-none">
    <i class="fas fa-bars"></i>
</button>
-->