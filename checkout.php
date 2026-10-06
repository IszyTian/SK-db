<?php
session_start();
// Redirect immediately to login if someone tries to check out anonymously
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sukuma Fresh - Complete Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; padding: 20px; }
        .checkout-wrapper { max-width: 600px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h2 { color: #2e7d32; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        input[type="text"], input[type="date"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .radio-group { display: flex; gap: 20px; margin-bottom: 15px; }
        .cost-summary { background: #f9f9f9; padding: 15px; border-radius: 6px; border-left: 4px solid #4caf50; margin: 20px 0; }
        .cost-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .btn-order { background: #2e7d32; color: white; border: none; padding: 12px 20px; width: 100%; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn-order:hover { background: #1b5e20; }
        .hidden { display: none; }
    </style>
</head>
<body>

<div class="checkout-wrapper">
    <h2>Secure Checkout Details</h2>
    
    <form id="checkoutForm">
        <!-- 1. Select Fulfillment Strategy -->
        <div class="form-group">
            <label>Fulfillment Preference</label>
            <div class="radio-group">
                <label><input type="radio" name="fulfillment" value="deliver" checked onchange="toggleFulfillment(this.value)"> 🛵 Home Delivery</label>
                <label><input type="radio" name="fulfillment" value="pickup" onchange="toggleFulfillment(this.value)"> 🏪 Store Pickup</label>
            </div>
        </div>

        <!-- 2. Dynamic Input Fields based on preference -->
        <div class="form-group" id="addressSection">
            <label for="address">Delivery Address (Physical location or Estate/House Number)</label>
            <input type="text" id="address" placeholder="e.g. Kilimani, Wood Avenue, Block B Apt 4">
        </div>

        <div class="form-group">
            <label for="deliveryDate">Target Fulfilment Date</label>
            <!-- Blocks past dates historically using min restriction -->
            <input type="date" id="deliveryDate" min="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <!-- 3. Payment Methods -->
        <div class="form-group">
            <label for="paymentMethod">Payment Options</label>
            <select id="paymentMethod" required>
                <option value="mpesa">M-Pesa Express</option>
                <option value="cod">Cash / Lipa na M-Pesa on Delivery</option>
            </select>
        </div>

        <!-- 4. Dynamic Financial Summary Statement -->
        <div class="cost-summary">
            <div class="cost-row"><span>Items Subtotal:</span><span id="subtotalCost">Ksh 0.00</span></div>
            <div class="cost-row"><span>Fulfillment Surcharge:</span><span id="surchargeCost">Ksh 150.00</span></div>
            <hr>
            <div class="cost-row" style="font-size: 18px; font-weight: bold; color: #2e7d32;">
                <span>Total Due:</span><span id="totalCost">Ksh 0.00</span>
            </div>
        </div>

        <button type="submit" class="btn-order">Confirm & Place Order</button>
    </form>
</div>

<script>
    // Example Cart Context retrieved from LocalStorage or runtime cart memory
    const cart = JSON.parse(localStorage.getItem('sukuma_cart')) || [];
    const itemSubtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    let deliveryFee = 150; // Default flat-rate shipping fee within operational zone

    function calculateCosts() {
        document.getElementById('subtotalCost').innerText = `Ksh ${itemSubtotal.toFixed(2)}`;
        document.getElementById('surchargeCost').innerText = `Ksh ${deliveryFee.toFixed(2)}`;
        
        const absoluteTotal = itemSubtotal + deliveryFee;
        document.getElementById('totalCost').innerText = `Ksh ${absoluteTotal.toFixed(2)}`;
    }

    function toggleFulfillment(value) {
        const addressSection = document.getElementById('addressSection');
        if (value === 'pickup') {
            addressSection.classList.add('hidden');
            deliveryFee = 0; // Drop logistics premium for walk-in operations
        } else {
            addressSection.classList.remove('hidden');
            deliveryFee = 150;
        }
        calculateCosts();
    }

    // Initialize display components on window draw
    toggleFulfillment('deliver');

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fulfillmentMode = document.querySelector('input[name="fulfillment"]:checked').value;
        const address = document.getElementById('address').value;
        
        if(fulfillmentMode === 'deliver' && !address.trim()) {
            alert('Please specify a delivery location address.');
            return;
        }

        const orderPayload = {
            cart: cart,
            fulfillment: fulfillmentMode,
            address: fulfillmentMode === 'deliver' ? address : 'STORE_PICKUP',
            delivery_date: document.getElementById('deliveryDate').value,
            payment_method: document.getElementById('paymentMethod').value,
            total: itemSubtotal + deliveryFee
        };

        // Post securely directly via AJAX API layer to write data streams
        fetch('api.php?action=place_order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderPayload),
        })
        .then(res => {
            // Check if backend crashed with a 500 error before parsing JSON
            if (!res.ok) {
                throw new Error(`Server returned status code ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            // FIXED: Standardizing to check both common success keys (data.success OR data.status)
            if(data.success || data.status === 'success') {
                alert('Success! ' + (data.message || 'Your order has been recorded.'));
                localStorage.removeItem('sukuma_cart'); // Purge cache on absolute success
                window.location.href = 'profile.php'; // Reroute to account history layout instantly
            } else {
                alert('Checkout Error: ' + (data.message || 'Unknown processing rejection.'));
            }
        })
        .catch(err => {
            // Catches invalid JSON strings (like PHP database connection warning printouts)
            console.error('Order Submission Error:', err);
            alert('A system error occurred. Please verify your internet connection or check database logs.');
        });
    });

</script>
</body>
</html>
