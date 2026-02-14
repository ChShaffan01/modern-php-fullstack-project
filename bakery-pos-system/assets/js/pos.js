$(document).ready(function() {
    console.log("POS System Loaded");
    
    // Cart array
    let cart = [];
    const TAX_RATE = 0.08; // 8% tax
    
    // Update current time
    function updateCurrentTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour12: true, 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
        $('#current-time').text(timeString);
    }
    
    // Update time every second
    setInterval(updateCurrentTime, 1000);
    updateCurrentTime();
    
    $('#checkout-btn').on('click', function () {

    if (cart.length === 0) {
        alert('Cart is empty');
        return;
    }

    const saleData = {
        customer_name: $('#customer-name').val(),
        customer_phone: $('#customer-phone').val(),
        payment_method: $('#payment-method').val(),
        items: cart
    };

    $.ajax({
        url: 'process_sale.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ sale_data: saleData }),
        success: function (res) {
            if (res.success) {
                alert('Sale Completed!');
                cart = [];
                updateCartDisplay();
                updateTotals();
            } else {
                alert(res.message);
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            alert('Sale failed');
        }
    });
});

    // Add product to cart
    $(document).on('click', '.add-to-cart', function(e) {
        e.preventDefault();
        
        const productCard = $(this).closest('.product-card');
        const productId = productCard.data('id');
        const productName = productCard.data('name');
        const price = parseFloat(productCard.data('price'));
        const stock = parseInt(productCard.data('stock'));
        
        console.log('Adding to cart:', { productId, productName, price, stock });
        
        // Check if product already in cart
        const existingIndex = cart.findIndex(item => item.id === productId);
        
        if (existingIndex !== -1) {
            // Product exists in cart, increase quantity
            if (cart[existingIndex].quantity < stock) {
                cart[existingIndex].quantity += 1;
            } else {
                alert('Cannot add more. Only ' + stock + ' units available.');
                return;
            }
        } else {
            // Add new product to cart
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
        
        // Show notification
        showNotification(productName + ' added to cart!');
    });
    
    // Update cart display
    function updateCartDisplay() {
        const cartItems = $('#cart-items');
        
        if (cart.length === 0) {
            cartItems.html('<div class="text-center text-muted p-3">Cart is empty</div>');
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
                                <button class="btn btn-outline-secondary decrease-qty" 
                                        data-index="${index}" ${item.quantity <= 1 ? 'disabled' : ''}>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center quantity-input" 
                                       value="${item.quantity}" min="1" max="${item.stock}" 
                                       data-index="${index}" style="width: 50px;">
                                <button class="btn btn-outline-secondary increase-qty" 
                                        data-index="${index}" ${item.quantity >= item.stock ? 'disabled' : ''}>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-2 text-end">
                            <strong>$${itemTotal.toFixed(2)}</strong>
                        </div>
                        <div class="col-1">
                            <button class="btn btn-sm btn-outline-danger remove-item" data-index="${index}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartItems.html(html);
    }
    
    // Update totals
    function updateTotals() {
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        
        const discount = parseFloat($('#discount').val()) || 0;
        const tax = subtotal * TAX_RATE;
        const total = subtotal + tax - discount;
        
        // Update display
        $('#subtotal').text('$' + subtotal.toFixed(2));
        $('#tax').text('$' + tax.toFixed(2));
        $('#total').text('$' + total.toFixed(2));
    }
    
    // Decrease quantity
    $(document).on('click', '.decrease-qty', function() {
        const index = $(this).data('index');
        if (cart[index].quantity > 1) {
            cart[index].quantity -= 1;
            updateCartDisplay();
            updateTotals();
        }
    });
    
    // Increase quantity
    $(document).on('click', '.increase-qty', function() {
        const index = $(this).data('index');
        if (cart[index].quantity < cart[index].stock) {
            cart[index].quantity += 1;
            updateCartDisplay();
            updateTotals();
        }
    });
    
    // Quantity input change
    $(document).on('change', '.quantity-input', function() {
        const index = $(this).data('index');
        let newQty = parseInt($(this).val());
        const maxStock = cart[index].stock;
        
        if (isNaN(newQty) || newQty < 1) {
            newQty = 1;
            $(this).val(1);
        }
        
        if (newQty > maxStock) {
            newQty = maxStock;
            $(this).val(maxStock);
            alert('Only ' + maxStock + ' units available in stock!');
        }
        
        cart[index].quantity = newQty;
        updateCartDisplay();
        updateTotals();
    });
    
    // Remove item from cart
    $(document).on('click', '.remove-item', function() {
        const index = $(this).data('index');
        const itemName = cart[index].name;
        
        if (confirm('Remove ' + itemName + ' from cart?')) {
            cart.splice(index, 1);
            updateCartDisplay();
            updateTotals();
            showNotification(itemName + ' removed from cart');
        }
    });
    
    // Clear cart
    $('#clear-cart').click(function() {
        if (cart.length === 0) {
            alert('Cart is already empty!');
            return;
        }
        
        if (confirm('Clear all items from cart?')) {
            cart = [];
            updateCartDisplay();
            updateTotals();
            $('#discount').val(0);
            showNotification('Cart cleared successfully');
        }
    });
    
    // Discount input change
    $('#discount').on('input', function() {
        let discount = parseFloat($(this).val()) || 0;
        
        if (discount < 0) {
            discount = 0;
            $(this).val(0);
        }
        
        updateTotals();
    });
    
    // Process sale
    $('#checkout-btn').click(function() {
        if (cart.length === 0) {
            alert('Please add items to cart first!');
            return;
        }
        
        const customerName = $('#customer-name').val().trim();
        if (!customerName) {
            alert('Customer name is required!');
            $('#customer-name').focus();
            return;
        }
        
        // Calculate totals
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        
        const discount = parseFloat($('#discount').val()) || 0;
        const tax = subtotal * TAX_RATE;
        const total = subtotal + tax - discount;
        
        // Prepare sale data
        const saleData = {
            customer_name: customerName,
            customer_phone: $('#customer-phone').val().trim(),
            payment_method: $('#payment-method').val(),
            discount: discount,
            subtotal: subtotal,
            tax: tax,
            grand_total: total,
            items: cart.map(item => ({
                id: item.id,
                name: item.name,
                price: item.price,
                quantity: item.quantity
            }))
        };
        
        console.log('Sale Data:', saleData);
        
        // Show confirmation
        const confirmMsg = `Process sale for ${customerName}?
        \nItems: ${cart.length}
        \nTotal: $${total.toFixed(2)}
        \nPayment: ${$('#payment-method').val()}`;
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        // Disable button and show loading
        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        // Send AJAX request
        $.ajax({
            url: 'process_sale.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ sale_data: saleData }),
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                
                if (response.success) {
                    // Success
                    alert('✅ Sale processed successfully!\nInvoice: ' + response.invoice_no);
                    
                    // Clear cart
                    cart = [];
                    updateCartDisplay();
                    updateTotals();
                    
                    // Reset form
                    $('#customer-name').val('');
                    $('#customer-phone').val('');
                    $('#discount').val(0);
                    
                    // Open invoice print window
                    if (response.invoice_id) {
                        window.open('print_invoice.php?invoice_id=' + response.invoice_id, '_blank');
                    }
                    
                    showNotification('Sale completed successfully!');
                } else {
                    // Error
                    alert('❌ Error: ' + (response.message || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                let errorMsg = 'Server error occurred.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    errorMsg = xhr.statusText || errorMsg;
                }
                
                alert('❌ Error: ' + errorMsg);
            },
            complete: function() {
                // Re-enable button
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Product search
    $('#search-product').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.product-card').each(function() {
            const productName = $(this).data('name').toLowerCase();
            if (productName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Category filter
    $('#category-filter').change(function() {
        const selectedCategory = $(this).val();
        
        $('.product-card').each(function() {
            const productCategory = $(this).data('category') || '';
            
            if (!selectedCategory || selectedCategory === '') {
                $(this).show();
            } else if (productCategory === selectedCategory) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Show notification
    function showNotification(message) {
        // Create notification element
        const notification = $('<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 1050; min-width: 250px;">' +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            message +
            '</div>');
        
        // Add to body
        $('body').append(notification);
        
        // Auto remove after 3 seconds
        setTimeout(function() {
            notification.alert('close');
        }, 3000);
    }
    
    // Initialize
    updateCartDisplay();
    updateTotals();
    
    // Add category data to product cards
    $('.product-card').each(function() {
        const productName = $(this).data('name').toLowerCase();
        let category = 'other';
        
        // Simple category detection based on product name
        if (productName.includes('bread') || productName.includes('loaf')) {
            category = 'bread';
        } else if (productName.includes('cake') || productName.includes('pastry')) {
            category = 'cake';
        } else if (productName.includes('cookie') || productName.includes('biscuit')) {
            category = 'cookies';
        } else if (productName.includes('muffin') || productName.includes('cupcake')) {
            category = 'muffins';
        }
        
        $(this).data('category', category);
    });
});
$('#checkout-btn').on('click', function () {

    if (cart.length === 0) {
        alert('Cart is empty');
        return;
    }

    const saleData = {
        customer_name: $('#customer-name').val(),
        customer_phone: $('#customer-phone').val(),
        payment_method: $('#payment-method').val(),
        items: cart
    };

    $.ajax({
        url: 'process_sale.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ sale_data: saleData }),
        success: function (res) {
            if (res.success) {
                alert('Sale Completed!');
                cart = [];
                updateCartDisplay();
                updateTotals();
            } else {
                alert(res.message);
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            alert('Sale failed');
        }
    });
});
