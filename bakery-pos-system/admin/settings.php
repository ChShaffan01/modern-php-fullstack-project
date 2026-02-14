<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'access_check.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_connect.php';

$message = '';
$messageType = '';
$activeTab = $_GET['tab'] ?? 'general';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        $settings = $_POST['settings'] ?? [];
        
        foreach ($settings as $key => $value) {
            update_setting($key, $value);
        }
        
        $message = 'Settings updated successfully!';
        $messageType = 'success';
    }
    
    elseif ($action === 'backup_database') {
        $backupResult = backup_database('../backups/');
        
        if ($backupResult['success']) {
            $message = 'Database backup created successfully! File: ' . basename($backupResult['file']);
            $messageType = 'success';
        } else {
            $message = 'Backup failed: ' . $backupResult['error'];
            $messageType = 'danger';
        }
    }
    
    elseif ($action === 'restore_database') {
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $tempFile = $_FILES['backup_file']['tmp_name'];
            $restoreResult = restore_database($tempFile);
            
            if ($restoreResult['success']) {
                $message = 'Database restored successfully!';
                $messageType = 'success';
            } else {
                $message = 'Restore failed: ' . $restoreResult['error'];
                $messageType = 'danger';
            }
        } else {
            $message = 'Please select a valid backup file';
            $messageType = 'danger';
        }
    }
    
    elseif ($action === 'toggle_maintenance') {
        $mode = $_POST['mode'] ?? 'enable';
        
        if ($mode === 'enable') {
            $messageText = $_POST['maintenance_message'] ?? 'System is under maintenance. Please check back later.';
            enable_maintenance_mode($messageText);
            $message = 'Maintenance mode enabled!';
            $messageType = 'warning';
        } else {
            disable_maintenance_mode();
            $message = 'Maintenance mode disabled!';
            $messageType = 'success';
        }
    }
    
    elseif ($action === 'clear_cache') {
        // Clear session cache
        session_unset();
        session_destroy();
        session_start();
        
        // Clear file cache (if any)
        $cacheDir = '../cache/';
        if (file_exists($cacheDir)) {
            array_map('unlink', glob($cacheDir . '/*'));
        }
        
        $message = 'Cache cleared successfully!';
        $messageType = 'success';
    }
    
    elseif ($action === 'test_email') {
        $testEmail = $_POST['test_email'] ?? '';
        
        if (validate_email($testEmail)) {
            $subject = 'Test Email from ' . SITE_NAME;
            $body = 'This is a test email sent from ' . SITE_NAME . ' on ' . date('Y-m-d H:i:s');
            
            if (send_email($testEmail, $subject, $body)) {
                $message = 'Test email sent successfully to ' . $testEmail;
                $messageType = 'success';
            } else {
                $message = 'Failed to send test email';
                $messageType = 'danger';
            }
        } else {
            $message = 'Invalid email address';
            $messageType = 'danger';
        }
    }
}

// Get all settings
$settings = get_settings();

// Get system information
$systemInfo = get_system_info();
$diskUsage = get_disk_usage();

// Get recent backups
$backups = [];
$backupDir = '../backups/';
if (file_exists($backupDir)) {
    $files = glob($backupDir . '*.sql');
    rsort($files);
    $backups = array_slice($files, 0, 10);
}

// Get timezone list
$timezoneList = get_timezone_list();

// Get date format options
$dateFormatOptions = get_date_format_options();

// Get time format options
$timeFormatOptions = get_time_format_options();

// Get backup frequency options
$backupFrequencyOptions = get_backup_frequency_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
        }
        .nav-tabs .nav-link.active {
            color: #4e73df;
            border-bottom: 3px solid #4e73df;
            background: transparent;
        }
        .settings-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .system-status {
            border-left: 4px solid #4e73df;
            padding-left: 15px;
            margin-bottom: 20px;
        }
        .system-status.good { border-color: #1cc88a; }
        .system-status.warning { border-color: #f6c23e; }
        .system-status.danger { border-color: #e74a3b; }
        .progress-thin {
            height: 8px;
            border-radius: 4px;
            margin-top: 5px;
        }
        .backup-file {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #4e73df;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Heading -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-cog me-2"></i>System Settings
            </h1>
            <div class="text-muted">
                <small>System Version: 2.0.1</small>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'general' ? 'active' : ''; ?>" 
                        id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                    <i class="fas fa-sliders-h me-2"></i>General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'financial' ? 'active' : ''; ?>" 
                        id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button">
                    <i class="fas fa-money-bill-wave me-2"></i>Financial
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'email' ? 'active' : ''; ?>" 
                        id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button">
                    <i class="fas fa-envelope me-2"></i>Email
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'backup' ? 'active' : ''; ?>" 
                        id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button">
                    <i class="fas fa-database me-2"></i>Backup
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'maintenance' ? 'active' : ''; ?>" 
                        id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button">
                    <i class="fas fa-tools me-2"></i>Maintenance
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'system' ? 'active' : ''; ?>" 
                        id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button">
                    <i class="fas fa-info-circle me-2"></i>System Info
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="settingsTabContent">
            
            <!-- General Settings -->
            <div class="tab-pane fade <?php echo $activeTab === 'general' ? 'show active' : ''; ?>" id="general" role="tabpanel">
                <div class="settings-section">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="save_settings">
                        
                        <h4 class="mb-4"><i class="fas fa-sliders-h me-2"></i>General Settings</h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="site_name" class="form-label">Site Name *</label>
                                <input type="text" class="form-control" id="site_name" name="settings[site_name]" 
                                       value="<?php echo $settings['site_name'] ?? SITE_NAME; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="site_email" class="form-label">Site Email *</label>
                                <input type="email" class="form-control" id="site_email" name="settings[site_email]" 
                                       value="<?php echo $settings['site_email'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="site_phone" class="form-label">Site Phone</label>
                                <input type="text" class="form-control" id="site_phone" name="settings[site_phone]" 
                                       value="<?php echo $settings['site_phone'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="site_address" class="form-label">Site Address</label>
                                <textarea class="form-control" id="site_address" name="settings[site_address]" 
                                          rows="2"><?php echo $settings['site_address'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="timezone" class="form-label">Timezone *</label>
                                <select class="form-select" id="timezone" name="settings[timezone]" required>
                                    <?php foreach ($timezoneList as $tz => $tzName): ?>
                                    <option value="<?php echo $tz; ?>" 
                                            <?php echo ($settings['timezone'] ?? 'UTC') === $tz ? 'selected' : ''; ?>>
                                        <?php echo $tzName; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="date_format" class="form-label">Date Format</label>
                                <select class="form-select" id="date_format" name="settings[date_format]">
                                    <?php foreach ($dateFormatOptions as $format => $label): ?>
                                    <option value="<?php echo $format; ?>" 
                                            <?php echo ($settings['date_format'] ?? 'F d, Y') === $format ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="time_format" class="form-label">Time Format</label>
                                <select class="form-select" id="time_format" name="settings[time_format]">
                                    <?php foreach ($timeFormatOptions as $format => $label): ?>
                                    <option value="<?php echo $format; ?>" 
                                            <?php echo ($settings['time_format'] ?? 'h:i A') === $format ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="items_per_page" class="form-label">Items Per Page</label>
                                <input type="number" class="form-control" id="items_per_page" name="settings[items_per_page]" 
                                       min="5" max="100" value="<?php echo $settings['items_per_page'] ?? '20'; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                <input type="number" class="form-control" id="low_stock_threshold" name="settings[low_stock_threshold]" 
                                       min="1" value="<?php echo $settings['low_stock_threshold'] ?? '10'; ?>">
                                <small class="text-muted">Alert when stock falls below this level</small>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_stock_alerts" 
                                           name="settings[enable_stock_alerts]" value="1"
                                           <?php echo ($settings['enable_stock_alerts'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_stock_alerts">
                                        Enable Stock Alerts
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_email_notifications" 
                                           name="settings[enable_email_notifications]" value="1"
                                           <?php echo ($settings['enable_email_notifications'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_email_notifications">
                                        Enable Email Notifications
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save General Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Financial Settings -->
            <div class="tab-pane fade <?php echo $activeTab === 'financial' ? 'show active' : ''; ?>" id="financial" role="tabpanel">
                <div class="settings-section">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="save_settings">
                        
                        <h4 class="mb-4"><i class="fas fa-money-bill-wave me-2"></i>Financial Settings</h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="currency_symbol" class="form-label">Currency Symbol *</label>
                                <input type="text" class="form-control" id="currency_symbol" name="settings[currency_symbol]" 
                                       value="<?php echo $settings['currency_symbol'] ?? '$'; ?>" required maxlength="3">
                                <small class="text-muted">E.g., $, €, £, ¥, ₹</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="currency_code" class="form-label">Currency Code *</label>
                                <input type="text" class="form-control" id="currency_code" name="settings[currency_code]" 
                                       value="<?php echo $settings['currency_code'] ?? 'USD'; ?>" required maxlength="3">
                                <small class="text-muted">E.g., USD, EUR, GBP, JPY, INR</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="tax_rate" class="form-label">Tax Rate (%) *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="tax_rate" name="settings[tax_rate]" 
                                           step="0.01" min="0" max="50" 
                                           value="<?php echo $settings['tax_rate'] ?? '8'; ?>" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Sales tax percentage applied to all sales</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                                <input type="text" class="form-control" id="invoice_prefix" name="settings[invoice_prefix]" 
                                       value="<?php echo $settings['invoice_prefix'] ?? 'INV'; ?>" maxlength="10">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="invoice_start_number" class="form-label">Invoice Start Number</label>
                                <input type="number" class="form-control" id="invoice_start_number" name="settings[invoice_start_number]" 
                                       min="1" value="<?php echo $settings['invoice_start_number'] ?? '1000'; ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="receipt_header" class="form-label">Receipt Header</label>
                                <textarea class="form-control" id="receipt_header" name="settings[receipt_header]" 
                                          rows="2"><?php echo $settings['receipt_header'] ?? SITE_NAME; ?></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="receipt_footer" class="form-label">Receipt Footer</label>
                                <textarea class="form-control" id="receipt_footer" name="settings[receipt_footer]" 
                                          rows="2"><?php echo $settings['receipt_footer'] ?? 'Thank you for your business!'; ?></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="receipt_show_logo" 
                                           name="settings[receipt_show_logo]" value="1"
                                           <?php echo ($settings['receipt_show_logo'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="receipt_show_logo">
                                        Show Logo on Receipts
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="receipt_show_barcode" 
                                           name="settings[receipt_show_barcode]" value="1"
                                           <?php echo ($settings['receipt_show_barcode'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="receipt_show_barcode">
                                        Show Barcode on Receipts
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Financial Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Email Settings -->
            <div class="tab-pane fade <?php echo $activeTab === 'email' ? 'show active' : ''; ?>" id="email" role="tabpanel">
                <div class="settings-section">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="save_settings">
                        
                        <h4 class="mb-4"><i class="fas fa-envelope me-2"></i>Email Settings</h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="smtp_host" class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" id="smtp_host" name="settings[smtp_host]" 
                                       value="<?php echo $settings['smtp_host'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="smtp_port" class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" id="smtp_port" name="settings[smtp_port]" 
                                       value="<?php echo $settings['smtp_port'] ?? '587'; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="smtp_username" class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" id="smtp_username" name="settings[smtp_username]" 
                                       value="<?php echo $settings['smtp_username'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="smtp_password" class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" id="smtp_password" name="settings[smtp_password]" 
                                       value="<?php echo $settings['smtp_password'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="smtp_encryption" class="form-label">SMTP Encryption</label>
                                <select class="form-select" id="smtp_encryption" name="settings[smtp_encryption]">
                                    <option value="">None</option>
                                    <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="tls" <?php echo ($settings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="from_email" class="form-label">From Email Address</label>
                                <input type="email" class="form-control" id="from_email" name="settings[from_email]" 
                                       value="<?php echo $settings['from_email'] ?? $settings['site_email'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Test Email Configuration</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <input type="email" class="form-control" name="test_email" 
                                                       placeholder="Enter email address to send test">
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" name="action" value="test_email" 
                                                        class="btn btn-primary w-100">
                                                    <i class="fas fa-paper-plane me-2"></i>Send Test
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Email Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Backup Settings -->
            <div class="tab-pane fade <?php echo $activeTab === 'backup' ? 'show active' : ''; ?>" id="backup" role="tabpanel">
                <div class="settings-section">
                    <h4 class="mb-4"><i class="fas fa-database me-2"></i>Database Backup & Restore</h4>
                    
                    <!-- Backup Settings -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-save me-2"></i>Backup Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="save_settings">
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="backup_enabled" 
                                                       name="settings[backup_enabled]" value="1"
                                                       <?php echo ($settings['backup_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="backup_enabled">
                                                    Enable Automatic Backups
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="backup_frequency" class="form-label">Backup Frequency</label>
                                            <select class="form-select" id="backup_frequency" name="settings[backup_frequency]">
                                                <?php foreach ($backupFrequencyOptions as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" 
                                                        <?php echo ($settings['backup_frequency'] ?? 'daily') === $value ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Backup Retention</label>
                                            <input type="number" class="form-control" name="settings[backup_retention]" 
                                                   min="1" max="365" value="<?php echo $settings['backup_retention'] ?? '30'; ?>">
                                            <small class="text-muted">Number of days to keep backups</small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Backup Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-download me-2"></i>Manual Backup</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="backup_database">
                                        
                                        <div class="mb-3">
                                            <p>Create a manual backup of the entire database.</p>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Backup will include all data: products, sales, users, etc.
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-database me-2"></i>Create Backup Now
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Restore Database -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Restore Database</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="restore_database">
                                        
                                        <div class="mb-3">
                                            <label for="backup_file" class="form-label">Select Backup File</label>
                                            <input type="file" class="form-control" id="backup_file" name="backup_file" 
                                                   accept=".sql" required>
                                            <small class="text-muted">Only .sql files created by this system</small>
                                        </div>
                                        
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Warning:</strong> This will replace all current data with backup data. 
                                            Make sure to backup current data first!
                                        </div>
                                        
                                        <button type="submit" class="btn btn-warning w-100" 
                                                onclick="return confirm('WARNING: This will overwrite ALL current data. Continue?')">
                                            <i class="fas fa-upload me-2"></i>Restore Database
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Backups</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($backups)): ?>
                                        <div class="list-group">
                                            <?php foreach ($backups as $backup): 
                                                $filename = basename($backup);
                                                $filesize = filesize($backup);
                                                $filetime = filemtime($backup);
                                            ?>
                                            <div class="backup-file">
                                                <div class="d-flex justify-content-between">
                                                    <strong><?php echo $filename; ?></strong>
                                                    <span class="badge bg-info"><?php echo format_file_size($filesize); ?></span>
                                                </div>
                                                <small class="text-muted">
                                                    Created: <?php echo date('Y-m-d H:i:s', $filetime); ?>
                                                </small>
                                                <div class="mt-2">
                                                    <a href="<?php echo str_replace('../', '', $backup); ?>" 
                                                       class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-warning" 
                                                            onclick="restoreBackup('<?php echo $filename; ?>')">
                                                        <i class="fas fa-upload"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteBackup('<?php echo $filename; ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center py-3">No backups found</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance Settings -->
            <div class="tab-pane fade <?php echo $activeTab === 'maintenance' ? 'show active' : ''; ?>" id="maintenance" role="tabpanel">
                <div class="settings-section">
                    <h4 class="mb-4"><i class="fas fa-tools me-2"></i>System Maintenance</h4>
                    
                    <!-- Maintenance Mode -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header <?php echo is_maintenance_mode() ? 'bg-warning' : 'bg-success'; ?> text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>
                                        Maintenance Mode: <?php echo is_maintenance_mode() ? 'ON' : 'OFF'; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (is_maintenance_mode()): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="toggle_maintenance">
                                            <input type="hidden" name="mode" value="disable">
                                            
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Maintenance mode is active!</strong>
                                                <p class="mb-0 mt-2"><?php echo get_maintenance_message(); ?></p>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fas fa-play me-2"></i>Disable Maintenance Mode
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="toggle_maintenance">
                                            <input type="hidden" name="mode" value="enable">
                                            
                                            <div class="mb-3">
                                                <label for="maintenance_message" class="form-label">Maintenance Message</label>
                                                <textarea class="form-control" id="maintenance_message" 
                                                          name="maintenance_message" rows="3" required>
                                                    <?php echo get_maintenance_message(); ?>
                                                </textarea>
                                                <small class="text-muted">This message will be shown to users</small>
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Only administrators will be able to access the system during maintenance.
                                            </div>
                                            
                                            <button type="submit" class="btn btn-warning w-100">
                                                <i class="fas fa-pause me-2"></i>Enable Maintenance Mode
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-broom me-2"></i>System Cleanup</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="clear_cache">
                                        
                                        <div class="mb-3">
                                            <p>Clear system cache and temporary files.</p>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                This will clear session cache and temporary files. No data will be lost.
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-info w-100">
                                            <i class="fas fa-broom me-2"></i>Clear System Cache
                                        </button>
                                    </form>
                                    
                                    <hr class="my-4">
                                    
                                    <div class="mb-3">
                                        <h6>System Logs</h6>
                                        <div class="d-grid gap-2">
                                            <a href="logs.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-file-alt me-2"></i>View System Logs
                                            </a>
                                            <button class="btn btn-outline-danger" onclick="clearLogs()">
                                                <i class="fas fa-trash me-2"></i>Clear All Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Information -->
            <div class="tab-pane fade <?php echo $activeTab === 'system' ? 'show active' : ''; ?>" id="system" role="tabpanel">
                <div class="settings-section">
                    <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i>System Information</h4>
                    
                    <div class="row">
                        <!-- System Status -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-server me-2"></i>System Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="system-status <?php echo $diskUsage['used_percent'] > 90 ? 'danger' : ($diskUsage['used_percent'] > 70 ? 'warning' : 'good'); ?> mb-3">
                                        <h6>Disk Usage</h6>
                                        <div class="progress-thin progress mb-2">
                                            <div class="progress-bar bg-<?php echo $diskUsage['used_percent'] > 90 ? 'danger' : ($diskUsage['used_percent'] > 70 ? 'warning' : 'success'); ?>" 
                                                 style="width: <?php echo $diskUsage['used_percent']; ?>%"></div>
                                        </div>
                                        <small>
                                            Used: <?php echo format_file_size($diskUsage['used']); ?> of 
                                            <?php echo format_file_size($diskUsage['total']); ?> 
                                            (<?php echo number_format($diskUsage['used_percent'], 1); ?>%)
                                        </small>
                                    </div>
                                    
                                    <div class="system-status good mb-3">
                                        <h6>PHP Version</h6>
                                        <small><?php echo $systemInfo['php_version']; ?></small>
                                    </div>
                                    
                                    <div class="system-status good mb-3">
                                        <h6>MySQL Version</h6>
                                        <small><?php echo $systemInfo['mysql_version']; ?></small>
                                    </div>
                                    
                                    <div class="system-status good">
                                        <h6>Server Software</h6>
                                        <small><?php echo $systemInfo['server_software']; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PHP Configuration -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-code me-2"></i>PHP Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6>Memory Limit</h6>
                                        <small><?php echo $systemInfo['memory_limit']; ?></small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Max Execution Time</h6>
                                        <small><?php echo $systemInfo['max_execution_time']; ?> seconds</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Upload Max Filesize</h6>
                                        <small><?php echo $systemInfo['upload_max_filesize']; ?></small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Post Max Size</h6>
                                        <small><?php echo $systemInfo['post_max_size']; ?></small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Timezone</h6>
                                        <small><?php echo $systemInfo['timezone']; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Database Information -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>Database Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php
                                        // Get table sizes
                                        $tables = ['users', 'products', 'sales', 'sale_items', 'inventory_logs', 'settings'];
                                        foreach ($tables as $table): 
                                            $sql = "SELECT COUNT(*) as count FROM $table";
                                            $result = $conn->query($sql);
                                            $row = $result->fetch_assoc();
                                        ?>
                                        <div class="col-md-2 mb-3">
                                            <div class="border rounded p-3 text-center">
                                                <h6><?php echo ucfirst(str_replace('_', ' ', $table)); ?></h6>
                                                <h3 class="text-primary"><?php echo $row['count']; ?></h3>
                                                <small class="text-muted">records</small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Check -->
                    <div class="card mt-4">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>System Check</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            PHP Version
                                            <span class="badge bg-<?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'danger'; ?>">
                                                <?php echo PHP_VERSION; ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            MySQL Extension
                                            <span class="badge bg-<?php echo extension_loaded('mysqli') ? 'success' : 'danger'; ?>">
                                                <?php echo extension_loaded('mysqli') ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            GD Library
                                            <span class="badge bg-<?php echo extension_loaded('gd') ? 'success' : 'warning'; ?>">
                                                <?php echo extension_loaded('gd') ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            File Uploads
                                            <span class="badge bg-<?php echo ini_get('file_uploads') ? 'success' : 'danger'; ?>">
                                                <?php echo ini_get('file_uploads') ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Write Permissions
                                            <span class="badge bg-<?php echo is_writable('../uploads/') ? 'success' : 'danger'; ?>">
                                                <?php echo is_writable('../uploads/') ? 'OK' : 'Failed'; ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Session Support
                                            <span class="badge bg-<?php echo session_status() === PHP_SESSION_ACTIVE ? 'success' : 'danger'; ?>">
                                                <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Restore backup
        function restoreBackup(filename) {
            if (confirm('WARNING: This will overwrite ALL current data with backup "' + filename + '". Continue?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'restore_backup';
                form.appendChild(actionInput);
                
                const fileInput = document.createElement('input');
                fileInput.type = 'hidden';
                fileInput.name = 'backup_file';
                fileInput.value = filename;
                form.appendChild(fileInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Delete backup
        function deleteBackup(filename) {
            if (confirm('Delete backup file "' + filename + '"?')) {
                fetch('delete_backup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'filename=' + encodeURIComponent(filename)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Backup deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        // Clear logs
        function clearLogs() {
            if (confirm('Clear all system logs? This cannot be undone.')) {
                fetch('clear_logs.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Logs cleared successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        // Initialize maintenance mode toggle
        document.getElementById('maintenance_mode')?.addEventListener('change', function() {
            const messageDiv = document.getElementById('maintenanceMessageDiv');
            if (this.checked) {
                messageDiv.classList.remove('d-none');
            } else {
                messageDiv.classList.add('d-none');
            }
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Switch tabs based on URL hash
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash) {
                const tab = new bootstrap.Tab(document.getElementById(hash + '-tab'));
                tab.show();
            }
        });
    </script>
</body>
</html>