<?php
// session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('cashier')) {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - <?php echo SITE_NAME; ?></title>
    
    <!-- Allow inline scripts for Bootstrap -->
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self';
        connect-src 'self' https://cdn.jsdelivr.net;
        script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
        style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;
        font-src 'self' https://cdnjs.cloudflare.com;
        img-src 'self' data: https:;
    ">
    
    <!-- Bootstrap from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts for bakery vibe -->
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bakery POS Styles -->
    <style>
        :root {
            --bakery-brown: #8B4513;
            --warm-brown: #A0522D;
            --light-brown: #D2691E;
            --cream: #FFF8DC;
            --golden: #DAA520;
            --pastry-pink: #FFE4E1;
            --bread-color: #E6C9A8;
            --success-green: #2E7D32;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e9f7 100%);
            min-height: 100vh;
            color: #5D4037;
        }
        
        /* Bakery Navbar */
        .navbar-bakery {
            background: linear-gradient(135deg, var(--bakery-brown) 0%, var(--warm-brown) 100%);
            padding: 12px 0;
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.2);
        }
        
        .navbar-brand-bakery {
            font-family: 'Pacifico', cursive;
            font-size: 1.8rem;
            color: var(--cream) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-brand-bakery i {
            color: var(--golden);
            font-size: 1.5rem;
        }
        
        .welcome-text {
            font-size: 1rem;
            color: var(--cream);
            font-weight: 500;
        }
        
        .time-display {
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            color: var(--golden);
            border: 1px solid rgba(218, 165, 32, 0.3);
        }
        
        /* Main Container */
        .container-fluid {
            padding: 20px;
        }
        
        /* Product Grid */
        .product-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
            border: 1px solid rgba(139, 69, 19, 0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.15);
            border-color: var(--golden);
        }
        
        .product-card .card-body {
            padding: 20px;
            background: linear-gradient(to bottom, white, var(--pastry-pink));
        }
        
        .product-card .card-title {
            font-weight: 600;
            color: var(--bakery-brown);
            font-size: 1.1rem;
            margin-bottom: 10px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--success-green);
            margin: 10px 0;
        }
        
        .product-stock {
            font-size: 0.85rem;
            color: var(--light-brown);
            background: var(--cream);
            padding: 3px 10px;
            border-radius: 10px;
            display: inline-block;
        }
        
        .product-card .card-footer {
            background: rgba(139, 69, 19, 0.05);
            border-top: 1px solid rgba(139, 69, 19, 0.1);
            padding: 15px;
        }
        
        .btn-add-to-cart {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--bakery-brown) 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-add-to-cart:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        }
        
        /* Search and Filter */
        .search-container {
            margin-bottom: 25px;
        }
        
        .input-group-bakery {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        
        .input-group-bakery .input-group-text {
            background: var(--bakery-brown);
            color: white;
            border: none;
            padding: 0 20px;
        }
        
        .input-group-bakery .form-control {
            border: none;
            padding: 12px 15px;
            font-size: 1rem;
        }
        
        .form-select-bakery {
            border-radius: 12px;
            border: 2px solid var(--cream);
            padding: 12px 15px;
            font-size: 1rem;
            color: var(--bakery-brown);
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        
        .form-select-bakery:focus {
            border-color: var(--golden);
            box-shadow: 0 0 0 0.25rem rgba(218, 165, 32, 0.25);
        }
        
        /* Shopping Cart */
        .cart-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            height: 100%;
            border: 2px solid var(--golden);
        }
        
        .cart-header {
            background: linear-gradient(135deg, var(--bakery-brown) 0%, var(--warm-brown) 100%);
            color: white;
            padding: 20px;
            border-bottom: 3px solid var(--golden);
        }
        
        .cart-header h5 {
            font-family: 'Pacifico', cursive;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cart-body {
            padding: 20px;
            background: linear-gradient(to bottom, white, var(--cream));
            max-height: 500px;
            overflow-y: auto;
        }
        
        .cart-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--light-brown);
        }
        
        .cart-empty i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .cart-item {
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 12px;
            border-left: 4px solid var(--golden);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cart-item-name {
            font-weight: 600;
            color: var(--bakery-brown);
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            font-size: 0.9rem;
            color: var(--light-brown);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-quantity {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid var(--light-brown);
            background: white;
            color: var(--bakery-brown);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .btn-quantity:hover:not(:disabled) {
            background: var(--light-brown);
            color: white;
        }
        
        .btn-quantity:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 2px solid var(--cream);
            border-radius: 8px;
            padding: 5px;
            font-weight: 600;
            color: var(--bakery-brown);
        }
        
        .cart-item-total {
            font-weight: 700;
            color: var(--success-green);
            font-size: 1.1rem;
        }
        
        .btn-remove-item {
            background: transparent;
            border: none;
            color: #dc3545;
            font-size: 1.2rem;
            transition: all 0.3s;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-remove-item:hover {
            background: rgba(220, 53, 69, 0.1);
            transform: rotate(90deg);
        }
        
        /* Totals Section */
        .totals-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border: 2px solid var(--cream);
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--cream);
        }
        
        .total-label {
            color: var(--bakery-brown);
            font-weight: 500;
        }
        
        .total-amount {
            font-weight: 600;
            color: var(--bakery-brown);
        }
        
        .total-grand {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--success-green);
            border-bottom: none;
            border-top: 2px solid var(--golden);
            padding-top: 15px;
            margin-top: 5px;
        }
        
        /* Customer Input */
        .customer-input {
            margin: 20px 0;
        }
        
        .form-control-bakery {
            border-radius: 12px;
            border: 2px solid var(--cream);
            padding: 12px 15px;
            font-size: 1rem;
            color: var(--bakery-brown);
            background: white;
            transition: all 0.3s;
        }
        
        .form-control-bakery:focus {
            border-color: var(--golden);
            box-shadow: 0 0 0 0.25rem rgba(218, 165, 32, 0.25);
        }
        
        .form-control-bakery::placeholder {
            color: var(--light-brown);
            opacity: 0.7;
        }
        
        /* Action Buttons */
        .action-buttons {
            margin-top: 25px;
        }
        
        .btn-process-sale {
            background: linear-gradient(135deg, var(--success-green) 0%, #4CAF50 100%);
            color: white;
            border: none;
            padding: 15px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-process-sale:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }
        
        .btn-process-sale:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .btn-clear-cart {
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545;
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-clear-cart:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            max-width: 400px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
            border-left: 5px solid;
        }
        
        .notification-success {
            border-left-color: var(--success-green);
        }
        
        .notification-error {
            border-left-color: #dc3545;
        }
        
        .notification-info {
            border-left-color: var(--bakery-brown);
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-brand-bakery {
                font-size: 1.5rem;
            }
            
            .welcome-text {
                font-size: 0.9rem;
            }
            
            .product-card .card-title {
                font-size: 1rem;
                min-height: auto;
            }
            
            .cart-body {
                max-height: 400px;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--cream);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--light-brown);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--bakery-brown);
        }
    </style>
</head>
<body>
    <!-- Bakery Navbar -->
    <nav class="navbar navbar-expand-lg navbar-bakery">
        <div class="container-fluid">
            <a class="navbar-brand navbar-brand-bakery" href="#">
                <i class="fas fa-bread-slice"></i>
                <?php echo SITE_NAME; ?> POS
            </a>
            
            <div class="d-flex align-items-center">
                <div class="welcome-text me-3">
                    Welcome, <?php echo $_SESSION['full_name']; ?>
                </div>
                <div class="time-display me-3">
                    <i class="fas fa-clock me-1"></i>
                    <span id="current-time"></span>
                </div>
                <a href="../logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Product Grid -->
            <div class="col-lg-8 col-md-7 mb-4">
                <div class="row search-container">
                    <div class="col-md-6 mb-3">
                        <div class="input-group input-group-bakery">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="search-product" 
                                   placeholder="Search bakery items...">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <select class="form-select form-select-bakery" id="category-filter">
                            <option value="">All Categories</option>
                            <?php
                            $sql = "SELECT DISTINCT category FROM products ORDER BY category";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['category']}'>{$row['category']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row" id="product-grid">
                    <?php
                    $sql = "SELECT * FROM products WHERE quantity > 0 ORDER BY product_name";
                    $result = $conn->query($sql);
                    
                    while ($product = $result->fetch_assoc()):
                        $emoji = match(true) {
                            stripos($product['product_name'], 'bread') !== false => '🍞',
                            stripos($product['product_name'], 'cake') !== false => '🍰',
                            stripos($product['product_name'], 'cookie') !== false => '🍪',
                            stripos($product['product_name'], 'muffin') !== false => '🧁',
                            stripos($product['product_name'], 'croissant') !== false => '🥐',
                            stripos($product['product_name'], 'donut') !== false => '🍩',
                            stripos($product['product_name'], 'pastry') !== false => '🥮',
                            default => '🥖'
                        };
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4 product-card" 
                         data-id="<?php echo $product['id']; ?>"
                         data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                         data-price="<?php echo $product['price']; ?>"
                         data-stock="<?php echo $product['quantity']; ?>"
                         data-category="<?php echo htmlspecialchars($product['category'] ?? 'other'); ?>">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div style="font-size: 2rem; margin-bottom: 10px;"><?php echo $emoji; ?></div>
                                <h6 class="card-title"><?php echo $product['product_name']; ?></h6>
                                <p class="product-price">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </p>
                                <span class="product-stock">
                                    <i class="fas fa-box me-1"></i>
                                    Stock: <?php echo $product['quantity']; ?> <?php echo $product['unit']; ?>
                                </span>
                            </div>
                            <div class="card-footer text-center">
                                <button class="btn btn-add-to-cart add-to-cart">
                                    <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Shopping Cart -->
            <div class="col-lg-4 col-md-5 mb-4">
                <div class="cart-card">
                    <div class="cart-header">
                        <h5>
                            <i class="fas fa-shopping-basket"></i>
                            Your Bakery Cart
                        </h5>
                    </div>
                    <div class="cart-body">
                        <div id="cart-items">
                            <div class="cart-empty">
                                <i class="fas fa-shopping-basket"></i>
                                <h6>Your cart is empty</h6>
                                <p class="text-muted">Add some delicious bakery items!</p>
                            </div>
                        </div>
                        
                        <div class="totals-section">
                            <div class="total-row">
                                <div class="total-label">Subtotal:</div>
                                <div class="total-amount" id="subtotal">$0.00</div>
                            </div>
                            <div class="total-row">
                                <div class="total-label">Tax (8%):</div>
                                <div class="total-amount" id="tax">$0.00</div>
                            </div>
                            <div class="total-row total-grand">
                                <div class="total-label">Total:</div>
                                <div class="total-amount" id="total">$0.00</div>
                            </div>
                        </div>

                        <div class="customer-input">
                            <input type="text" class="form-control form-control-bakery" 
                                   id="customer-name" placeholder="Customer Name">
                        </div>

                        <div class="action-buttons d-grid gap-3">
                            <button class="btn btn-process-sale" id="checkout-btn">
                                <i class="fas fa-check-circle"></i> Process Sale
                            </button>
                            <button class="btn btn-clear-cart" id="clear-cart">
                                <i class="fas fa-trash-alt"></i> Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript remains the same, just styling changed -->
    <!-- Inline JavaScript -->
    <script>
        // Your existing JavaScript code remains exactly the same
        // Only CSS has been updated for bakery theme
        
        console.log('Bakery POS System Starting...');
        
        // Cart array
        let cart = [];
        const TAX_RATE = 0.08;
        
        // Update time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('current-time').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        updateTime();
        
        // Add to cart
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-to-cart') || 
                e.target.closest('.add-to-cart')) {
                
                const btn = e.target.classList.contains('add-to-cart') ? 
                            e.target : e.target.closest('.add-to-cart');
                const productCard = btn.closest('.product-card');
                
                const productId = productCard.getAttribute('data-id');
                const productName = productCard.getAttribute('data-name');
                const price = parseFloat(productCard.getAttribute('data-price'));
                const stock = parseInt(productCard.getAttribute('data-stock'));
                
                console.log('Adding product:', productName);
                
                // Check if already in cart
                const existingIndex = cart.findIndex(item => item.id === productId);
                
                if (existingIndex !== -1) {
                    if (cart[existingIndex].quantity < stock) {
                        cart[existingIndex].quantity += 1;
                    } else {
                        alert('Cannot add more. Only ' + stock + ' units available.');
                        return;
                    }
                } else {
                    if (stock > 0) {
                        cart.push({
                            id: productId,
                            name: productName,
                            price: price,
                            quantity: 1,
                            stock: stock
                        });
                    } else {
                        alert('Product is out of stock!');
                        return;
                    }
                }
                
                updateCartDisplay();
                updateTotals();
                
                // Show simple notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success alert-dismissible position-fixed';
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 250px;';
                notification.innerHTML = `
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                    ${productName} added to cart!
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            }
        });
        
        // Update cart display
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            
            if (cart.length === 0) {
                cartItems.innerHTML = '<div class="text-center text-muted p-3">Cart is empty</div>';
                return;
            }
            
            let html = '';
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                html += `
                    <div class="cart-item">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <strong>${item.name}</strong>
                                <br>
                                <small>$${item.price.toFixed(2)} each</small>
                            </div>
                            <div class="col-4">
                                <div class="input-group input-group-sm">
                                    <button class="btn btn-outline-secondary" onclick="changeQuantity(${index}, -1)" ${item.quantity <= 1 ? 'disabled' : ''}>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" 
                                           value="${item.quantity}" min="1" max="${item.stock}" 
                                           onchange="updateQuantity(${index}, this.value)" style="width: 50px;">
                                    <button class="btn btn-outline-secondary" onclick="changeQuantity(${index}, 1)" ${item.quantity >= item.stock ? 'disabled' : ''}>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-2 text-end">
                                <strong>$${itemTotal.toFixed(2)}</strong>
                            </div>
                            <div class="col-1">
                                <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
        }
        
        // Change quantity functions (must be global)
        window.changeQuantity = function(index, change) {
            const newQty = cart[index].quantity + change;
            
            if (newQty < 1 || newQty > cart[index].stock) return;
            
            cart[index].quantity = newQty;
            updateCartDisplay();
            updateTotals();
        }
        
        window.updateQuantity = function(index, value) {
            let newQty = parseInt(value);
            if (isNaN(newQty) || newQty < 1) newQty = 1;
            if (newQty > cart[index].stock) {
                newQty = cart[index].stock;
                alert('Only ' + cart[index].stock + ' units available!');
            }
            
            cart[index].quantity = newQty;
            updateCartDisplay();
            updateTotals();
        }
        
        window.removeItem = function(index) {
            const itemName = cart[index].name;
            if (confirm('Remove ' + itemName + ' from cart?')) {
                cart.splice(index, 1);
                updateCartDisplay();
                updateTotals();
            }
        }
        
        // Update totals
        function updateTotals() {
            let subtotal = 0;
            cart.forEach(item => {
                subtotal += item.price * item.quantity;
            });
            
            const tax = subtotal * TAX_RATE;
            const total = subtotal + tax;
            
            document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('tax').textContent = '$' + tax.toFixed(2);
            document.getElementById('total').textContent = '$' + total.toFixed(2);
        }
        
        // Clear cart
        document.getElementById('clear-cart').addEventListener('click', function() {
            if (cart.length === 0) {
                alert('Cart is already empty!');
                return;
            }
            
            if (confirm('Clear all items from cart?')) {
                cart = [];
                updateCartDisplay();
                updateTotals();
            }
        });
        
        // Checkout
// Checkout
document.getElementById('checkout-btn').addEventListener('click', function() {
    if (cart.length === 0) {
        alert('Please add items to cart first!');
        return;
    }
    
    const customerName = document.getElementById('customer-name').value.trim();
    if (!customerName) {
        alert('Customer name is required!');
        document.getElementById('customer-name').focus();
        return;
    }
    
    // Calculate totals
    let subtotal = 0;
    cart.forEach(item => {
        subtotal += item.price * item.quantity;
    });
    
    const tax = subtotal * TAX_RATE;
    const total = subtotal + tax;
    
    // Prepare sale data
    const saleData = {
        customer_name: customerName,
        items: cart.map(item => ({
            id: item.id,
            name: item.name,
            price: item.price,
            quantity: item.quantity
        })),
        subtotal: subtotal,
        tax: tax,
        grand_total: total,
        payment_method: 'cash'
    };
    
    console.log('Sending sale data:', saleData);
    
    // Disable button during processing
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Show processing notification
    showProcessingNotification();
    
    // Send to server
    fetch('process_sale.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ sale_data: saleData })
    })
    .then(response => response.json())
    .then(responseData => {
        console.log('Full Response:', responseData);
        
        if (responseData.success) {
            // Access invoice_id correctly
            const invoiceId = responseData.data.invoice_id;
            const invoiceNo = responseData.data.invoice_no;
            
            console.log('Extracted - Invoice ID:', invoiceId, 'Invoice No:', invoiceNo);
            
            // Clear cart
            cart = [];
            updateCartDisplay();
            updateTotals();
            document.getElementById('customer-name').value = '';
            
            if (invoiceId) {
                // DIRECTLY OPEN PRINT INVOICE - NO ALERT
                // Open in new tab
                const printWindow = window.open(
                    'print_invoice.php?invoice_id=' + invoiceId, 
                    '_blank'
                );
                
                // Optional: Show success notification briefly
                showSuccessNotification('Sale processed! Invoice printing...');
                
                // Optional: Focus on print window
                if (printWindow) {
                    printWindow.focus();
                }
            } else {
                // Fallback if no invoice ID
                showSuccessNotification('Sale processed successfully!');
            }
        } else {
            // Show error without alert
            showErrorNotification('Error: ' + responseData.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorNotification('Server error occurred. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        removeProcessingNotification();
    });
});

// Notification functions
function showProcessingNotification() {
    const notification = document.createElement('div');
    notification.id = 'processing-notification';
    notification.className = 'alert alert-info alert-dismissible position-fixed';
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <strong>Processing Sale...</strong>
        </div>
        <div class="mt-2">
            <small>Please wait while we process your sale</small>
        </div>
    `;
    document.body.appendChild(notification);
}

function removeProcessingNotification() {
    const notification = document.getElementById('processing-notification');
    if (notification) {
        notification.remove();
    }
}

function showSuccessNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible position-fixed';
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: fadeIn 0.3s;
    `;
    notification.innerHTML = `
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success!</strong>
        </div>
        <div class="mt-2">${message}</div>
    `;
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-danger alert-dismissible position-fixed';
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: fadeIn 0.3s;
    `;
    notification.innerHTML = `
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error!</strong>
        </div>
        <div class="mt-2">${message}</div>
    `;
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Add animation style
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);
        // Product search
        document.getElementById('search-product').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach(card => {
                const productName = card.getAttribute('data-name').toLowerCase();
                if (productName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Category filter
        document.getElementById('category-filter').addEventListener('change', function() {
            const selectedCategory = this.value;
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach(card => {
                const productCategory = card.getAttribute('data-category') || '';
                
                if (!selectedCategory || selectedCategory === '' || productCategory === selectedCategory) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Initialize
        updateCartDisplay();
        updateTotals();
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>