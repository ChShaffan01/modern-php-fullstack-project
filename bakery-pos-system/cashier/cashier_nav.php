
    <style>
        .cashier-nav {
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            color: #DAA520;
        }
        
        .nav-title {
            font-size: 18px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: #DAA520;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #333;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .time-display {
            background: rgba(0,0,0,0.2);
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="cashier-nav">
        <div class="nav-left">
            <div class="logo">
                <i class="fas fa-bread-slice"></i>
                <?php echo isset($site_name) ? $site_name : 'Bakery POS'; ?>
            </div>
            <div class="nav-title">
                <i class="fas fa-cash-register"></i>
                Point of Sale
            </div>
        </div>
        
        <div class="nav-right">
            <div class="time-display">
                <i class="fas fa-clock"></i>
                <span id="currentTime"><?php echo date('h:i A'); ?></span>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $initial = isset($_SESSION['full_name']) ? strtoupper(substr($_SESSION['full_name'], 0, 1)) : 'U';
                    echo $initial;
                    ?>
                </div>
                <div>
                    <div style="font-weight: 500;">
                        <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Cashier'; ?>
                    </div>
                    <div style="font-size: 12px; opacity: 0.8;">Cashier</div>
                </div>
            </div>
            
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </button>
        </div>
    </nav>

    <script>
        // Update time every second
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        
        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }
        
        // Keyboard shortcut for logout (Ctrl+L)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                e.preventDefault();
                logout();
            }
        });
    </script>
