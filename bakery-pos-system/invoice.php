<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Start session
// session_start();

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get invoice ID from URL
$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no ID provided, check for latest sale from session
if ($invoiceId === 0 && isset($_SESSION['last_sale_id'])) {
    $invoiceId = $_SESSION['last_sale_id'];
}

if ($invoiceId === 0) {
    die('No invoice specified. Please provide a valid invoice ID.');
}

require_once 'includes/db_connect.php';

// Get sale information
$sql = "SELECT s.*, u.full_name as cashier_name 
        FROM sales s 
        LEFT JOIN users u ON s.user_id = u.id 
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $invoiceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Invoice not found.');
}

$sale = $result->fetch_assoc();

// Get sale items
$sql = "SELECT si.*, p.product_code 
        FROM sale_items si 
        LEFT JOIN products p ON si.product_id = p.id 
        WHERE si.sale_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $invoiceId);
$stmt->execute();
$itemsResult = $stmt->get_result();

// Check if user has permission to view this invoice
// (Admin can view all, cashier can only view their own)
if ($_SESSION['role'] !== 'admin' && $sale['user_id'] != $_SESSION['user_id']) {
    die('You do not have permission to view this invoice.');
}

// Set invoice number
$invoiceNumber = $sale['invoice_no'];

// Determine if we're in print mode
$isPrint = isset($_GET['print']) || isset($_GET['mode']) && $_GET['mode'] === 'print';

// Helper function to convert numbers to words
function numberToWords($number) {
    $whole = floor($number);
    $fraction = round(($number - $whole) * 100);
    
    $words = convertToWords($whole);
    
    if ($fraction > 0) {
        return $words . ' and ' . convertToWords($fraction) . ' cents';
    }
    
    return $words . ' dollars';
}

function convertToWords($number) {
    $ones = array(
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen'
    );
    
    $tens = array(
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    );
    
    if ($number < 20) {
        return $ones[$number];
    }
    
    if ($number < 100) {
        $ten = floor($number / 10);
        $unit = $number % 10;
        $words = $tens[$ten];
        if ($unit > 0) {
            $words .= '-' . $ones[$unit];
        }
        return $words;
    }
    
    if ($number < 1000) {
        $hundred = floor($number / 100);
        $remainder = $number % 100;
        $words = $ones[$hundred] . ' Hundred';
        if ($remainder > 0) {
            $words .= ' and ' . convertToWords($remainder);
        }
        return $words;
    }
    
    if ($number < 100000) {
        $thousand = floor($number / 1000);
        $remainder = $number % 1000;
        $words = convertToWords($thousand) . ' Thousand';
        if ($remainder > 0) {
            $words .= ' ' . convertToWords($remainder);
        }
        return $words;
    }
    
    // For larger numbers
    return 'Number too large';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Invoice #<?php echo $invoiceNumber; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts for bakery vibe -->
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bakery-brown: #8B4513;
            --warm-brown: #A0522D;
            --light-brown: #D2691E;
            --cream: #FFF8DC;
            --golden: #DAA520;
            --pastry-pink: #FFE4E1;
            --chocolate: #5D4037;
            --success-green: #2E7D32;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #f9f5f0 0%, #fff8f0 100%);
            color: var(--chocolate);
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .invoice-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.05;
            overflow: hidden;
        }
        
        .floating-receipt {
            position: absolute;
            font-size: 24px;
            opacity: 0.2;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg) scale(1);
            }
            50% {
                transform: translateY(-30px) rotate(180deg) scale(1.1);
            }
            100% {
                transform: translateY(0) rotate(360deg) scale(1);
            }
        }
        
        /* Invoice Container */
        .invoice-container {
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 50px rgba(139, 69, 19, 0.25);
            overflow: hidden;
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            animation: slideIn 0.8s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .invoice-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--golden), var(--light-brown), var(--bakery-brown));
            z-index: 2;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            
            .invoice-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            .invoice-bg {
                display: none !important;
            }
            
            @page {
                margin: 20mm;
            }
        }
        
        /* Header */
        .invoice-header {
            background: linear-gradient(135deg, var(--bakery-brown) 0%, var(--warm-brown) 100%);
            color: white;
            padding: 50px 40px;
            position: relative;
            overflow: hidden;
        }
        
        .invoice-header::before {
            content: '🧾🧾🧾';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            font-size: 70px;
            opacity: 0.1;
            text-align: center;
        }
        
        .bakery-logo {
            font-family: 'Pacifico', cursive;
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }
        
        .invoice-title {
            font-family: 'Dancing Script', cursive;
            font-size: 2.2rem;
            color: var(--golden);
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .invoice-number-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid var(--golden);
            border-radius: 15px;
            padding: 10px 25px;
            display: inline-block;
            font-size: 1.3rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }
        
        /* Invoice Info */
        .invoice-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 40px;
        }
        
        .info-card {
            background: var(--cream);
            border-radius: 20px;
            padding: 25px;
            border-left: 5px solid var(--golden);
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }
        
        .info-card-title {
            font-family: 'Dancing Script', cursive;
            font-size: 1.8rem;
            color: var(--bakery-brown);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed rgba(139, 69, 19, 0.2);
        }
        
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--light-brown);
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--chocolate);
            min-width: 120px;
        }
        
        .info-value {
            color: var(--bakery-brown);
            font-weight: 500;
        }
        
        /* Items Table */
        .items-section {
            padding: 0 40px;
        }
        
        .section-title {
            font-family: 'Dancing Script', cursive;
            font-size: 2rem;
            color: var(--bakery-brown);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--cream);
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        
        .items-table thead {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--bakery-brown) 100%);
            color: white;
        }
        
        .items-table th {
            padding: 18px;
            text-align: left;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid var(--cream);
            transition: all 0.3s ease;
        }
        
        .items-table tbody tr:hover {
            background: var(--pastry-pink);
            transform: translateX(5px);
        }
        
        .items-table tbody tr:nth-child(even) {
            background: rgba(255, 228, 225, 0.3);
        }
        
        .items-table td {
            padding: 18px;
            color: var(--chocolate);
        }
        
        .product-name-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-emoji {
            font-size: 1.5rem;
            width: 30px;
        }
        
        .product-name {
            font-weight: 500;
            color: var(--bakery-brown);
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Totals Section */
        .totals-section {
            padding: 40px;
            background: linear-gradient(135deg, var(--cream) 0%, #f8f4e6 100%);
        }
        
        .totals-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            max-width: 500px;
            margin-left: auto;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 25px;
            background: white;
            border-radius: 12px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .total-row:hover {
            border-color: var(--golden);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(218, 165, 32, 0.2);
        }
        
        .total-label {
            font-size: 1.1rem;
            color: var(--chocolate);
            font-weight: 500;
        }
        
        .total-amount {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--bakery-brown);
        }
        
        .grand-total {
            background: linear-gradient(135deg, var(--golden) 0%, #ffd700 100%);
            color: var(--chocolate);
            font-size: 1.3rem;
            font-weight: 700;
            border: 3px solid var(--light-brown);
            margin-top: 10px;
        }
        
        .grand-total .total-label {
            color: var(--chocolate);
            font-weight: 700;
        }
        
        .grand-total .total-amount {
            color: var(--chocolate);
            font-size: 1.5rem;
        }
        
        /* Amount in Words */
        .amount-words {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 25px;
            border-left: 4px solid var(--success-green);
        }
        
        .amount-title {
            color: var(--success-green);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Footer */
        .invoice-footer {
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, var(--chocolate) 0%, #3e2723 100%);
            color: white;
        }
        
        .thank-you {
            font-family: 'Dancing Script', cursive;
            font-size: 2.5rem;
            color: var(--golden);
            margin-bottom: 15px;
        }
        
        .footer-text {
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        .bakery-stamp {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            margin: 30px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 6px solid var(--golden);
            position: relative;
            overflow: hidden;
        }
        
        .stamp-text {
            font-family: 'Pacifico', cursive;
            color: var(--primary-brown);
            font-size: 1.4rem;
            text-align: center;
            padding: 15px;
            transform: rotate(-5deg);
        }
        
        .stamp-text::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(139, 69, 19, 0.1) 10px,
                rgba(139, 69, 19, 0.1) 20px
            );
        }
        
        /* Action Buttons */
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                position: static;
                justify-content: center;
                margin-bottom: 20px;
            }
        }
        
        .btn-invoice {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            font-family: 'Quicksand', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-print {
            background: linear-gradient(135deg, var(--bakery-brown) 0%, var(--warm-brown) 100%);
            color: white;
        }
        
        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.3);
        }
        
        .btn-download {
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
            color: white;
        }
        
        .btn-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.3);
        }
        
        .btn-back {
            background: white;
            color: var(--chocolate);
            border: 2px solid var(--chocolate);
        }
        
        .btn-back:hover {
            background: var(--chocolate);
            color: white;
        }
        
        /* QR Code */
        .qr-code {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            margin-top: 20px;
            display: inline-block;
            border: 2px dashed var(--golden);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .invoice-header {
                padding: 30px 20px;
            }
            
            .bakery-logo {
                font-size: 2.2rem;
            }
            
            .invoice-title {
                font-size: 1.8rem;
            }
            
            .invoice-info-grid {
                padding: 20px;
                grid-template-columns: 1fr;
            }
            
            .items-section, .totals-section {
                padding: 20px;
            }
            
            .items-table th, .items-table td {
                padding: 12px 8px;
            }
            
            .totals-grid {
                max-width: 100%;
            }
        }
        
        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--cream);
            border-top: 5px solid var(--bakery-brown);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="invoice-bg">
        <?php for($i=0; $i<20; $i++): ?>
        <div class="floating-receipt" style="
            top: <?php echo rand(5, 95); ?>%;
            left: <?php echo rand(5, 95); ?>%;
            font-size: <?php echo rand(20, 40); ?>px;
            animation-delay: -<?php echo rand(0, 20); ?>s;
            animation-duration: <?php echo rand(15, 35); ?>s;
        ">🧾</div>
        <?php endfor; ?>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button class="btn-invoice btn-print" onclick="printInvoice()">
            <i class="fas fa-print"></i> Print Invoice
        </button>
        <button class="btn-invoice btn-download" onclick="downloadPDF()">
            <i class="fas fa-download"></i> Download PDF
        </button>
        <button class="btn-invoice btn-back" onclick="goBack()">
            <i class="fas fa-arrow-left"></i> Go Back
        </button>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="bakery-logo">
                        <i class="fas fa-bread-slice"></i>
                        <?php echo SITE_NAME; ?>
                    </h1>
                    <p class="mb-3" style="opacity: 0.9; position: relative; z-index: 1;">
                        <i class="fas fa-map-marker-alt me-2"></i>123 Bakery Street, Sweet City<br>
                        <i class="fas fa-phone me-2"></i>(123) 456-7890 | 
                        <i class="fas fa-envelope me-2"></i>hello@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="invoice-title">Sweet Receipt</div>
                    <div class="invoice-number-badge">
                        <i class="fas fa-hashtag me-2"></i><?php echo $invoiceNumber; ?>
                    </div>
                    <p class="mt-3 mb-0" style="position: relative; z-index: 1;">
                        <i class="fas fa-calendar me-2"></i><?php echo date('F j, Y', strtotime($sale['sale_date'])); ?><br>
                        <i class="fas fa-clock me-2"></i><?php echo date('h:i A', strtotime($sale['sale_date'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info-grid">
            <div class="info-card">
                <h3 class="info-card-title">
                    <i class="fas fa-user-circle"></i> Billed To
                </h3>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="info-label">Customer:</div>
                    <div class="info-value"><?php echo htmlspecialchars($sale['customer_name']); ?></div>
                </div>
                <?php if (!empty($sale['customer_phone'])): ?>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo htmlspecialchars($sale['customer_phone']); ?></div>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="info-label">Date:</div>
                    <div class="info-value"><?php echo date('l, F j, Y', strtotime($sale['sale_date'])); ?></div>
                </div>
            </div>

            <div class="info-card">
                <h3 class="info-card-title">
                    <i class="fas fa-store"></i> Store Info
                </h3>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="info-label">Cashier:</div>
                    <div class="info-value"><?php echo htmlspecialchars($sale['cashier_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="info-label">Payment:</div>
                    <div class="info-value">
                        <?php 
                        $paymentMethods = [
                            'cash' => 'Cash',
                            'card' => 'Credit/Debit Card',
                            'mobile_money' => 'Mobile Money'
                        ];
                        echo $paymentMethods[$sale['payment_method']] ?? 'Cash';
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-badge-check"></i>
                    </div>
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span style="
                            background: <?php echo $sale['payment_status'] === 'paid' ? '#2E7D32' : '#DAA520'; ?>;
                            color: white;
                            padding: 4px 12px;
                            border-radius: 20px;
                            font-size: 0.9rem;
                            font-weight: 600;
                        ">
                            <?php echo ucfirst($sale['payment_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="items-section">
            <h3 class="section-title">Delicious Items Ordered</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    $totalItems = 0;
                    $itemsResult->data_seek(0); // Reset pointer
                    while ($item = $itemsResult->fetch_assoc()):
                        $totalItems += $item['quantity'];
                        $emoji = match(true) {
                            stripos($item['product_name'], 'bread') !== false => '🍞',
                            stripos($item['product_name'], 'cake') !== false => '🍰',
                            stripos($item['product_name'], 'cookie') !== false => '🍪',
                            stripos($item['product_name'], 'muffin') !== false => '🧁',
                            stripos($item['product_name'], 'croissant') !== false => '🥐',
                            stripos($item['product_name'], 'donut') !== false => '🍩',
                            stripos($item['product_name'], 'pastry') !== false => '🥮',
                            default => '🥖'
                        };
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td>
                            <div class="product-name-cell">
                                <span class="product-emoji"><?php echo $emoji; ?></span>
                                <div>
                                    <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <small class="text-muted">Code: <?php echo htmlspecialchars($item['product_code'] ?? 'N/A'); ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-right">$<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-right">$<?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="amount-words">
                        <div class="amount-title">
                            <i class="fas fa-file-invoice-dollar"></i> Amount in Words
                        </div>
                        <p style="font-style: italic; color: var(--chocolate);">
                            <strong><?php echo numberToWords($sale['grand_total']); ?> Only</strong>
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="totals-grid">
                        <div class="total-row">
                            <div class="total-label">Subtotal</div>
                            <div class="total-amount">$<?php echo number_format($sale['total_amount'], 2); ?></div>
                        </div>
                        
                        <div class="total-row">
                            <div class="total-label">Tax (8%)</div>
                            <div class="total-amount">$<?php echo number_format($sale['tax'], 2); ?></div>
                        </div>
                        
                        <?php if ($sale['discount'] > 0): ?>
                        <div class="total-row">
                            <div class="total-label">Discount</div>
                            <div class="total-amount">-$<?php echo number_format($sale['discount'], 2); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="total-row grand-total">
                            <div class="total-label">GRAND TOTAL</div>
                            <div class="total-amount">$<?php echo number_format($sale['grand_total'], 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="bakery-stamp">
                <div class="stamp-text">
                    PAID<br>
                    <small><?php echo date('M j, Y'); ?></small>
                </div>
            </div>
            
            <h3 class="thank-you">Thank You for Your Sweet Visit!</h3>
            <p class="footer-text">
                We appreciate your business and hope you enjoyed every bite!<br>
                For any inquiries, please contact us at (123) 456-7890
            </p>
            
            <div class="mt-4">
                <small style="opacity: 0.7;">
                    Invoice generated on <?php echo date('F j, Y, h:i A'); ?> | 
                    Freshness guaranteed for 2 days | 
                    Valid for 30 days
                </small>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
   <!-- Simple Invoice Script without auto-print -->
<script>
    // Simple invoice page with only manual controls
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Invoice page loaded - Manual mode');
        
        // Print button functionality
        const printButtons = document.querySelectorAll('[data-action="print"]');
        printButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                window.print();
            });
        });
        
        // Close button functionality
        const closeButtons = document.querySelectorAll('[data-action="close"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Close invoice window?')) {
                    window.close();
                }
            });
        });
        
        // Download buttons
        const pdfButtons = document.querySelectorAll('[data-action="pdf"]');
        pdfButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                alert('PDF download would be implemented here');
                // Implement PDF download logic
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Ctrl+S for save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                alert('Save functionality');
            }
            
            // Escape to close
            if (e.key === 'Escape') {
                if (confirm('Close window?')) {
                    window.close();
                }
            }
        });
        
        // Add print-specific styles
        const printStyle = document.createElement('style');
        printStyle.textContent = `
            @media print {
                body * {
                    visibility: hidden;
                }
                .invoice-container, .invoice-container * {
                    visibility: visible;
                }
                .invoice-container {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
                .no-print {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(printStyle);
    });
</script>
</body>
</html>