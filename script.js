// Remove the hardcoded products array entirely!
let products = [];
let cart = JSON.parse(localStorage.getItem('sukuma_cart')) || []; // Keep existing line

document.addEventListener("DOMContentLoaded", () => {
    updateCartUI(); // Keep existing line

    // Fetch live products out of your MySQL database via PHP
    fetch('api.php?action=get_products')
        .then(response => response.json())
        .then(data => {
            products = data; // Assign database data to your global products reference
            
            // Re-fire template loads with database contents
            if (document.getElementById('product-list')) {
                displayProducts(products, 'product-list', false);
                document.getElementById('search-bar').addEventListener('input', filterProducts);
                document.getElementById('category-filter').addEventListener('change', filterProducts);
            }
            
            if (document.getElementById('sales-list')) {
                const saleItems = products.filter(p => p.isSale);
                displayProducts(saleItems, 'sales-list', true);
            }
        });
        
    setupEventListeners(); // Keep existing line
});

 

// Structural Template Generation
function displayProducts(list, elementId, salesOnly) {
    const grid = document.getElementById(elementId);
    if (!grid) return;
    grid.innerHTML = "";

    list.forEach(product => {
        if (salesOnly && !product.isSale) return;
        const currentPrice = product.isSale ? product.salePrice : product.price;
        
        grid.innerHTML += `
            <div class="product-card"  style=" color:black;">
                ${product.isSale ? '<span class="badge">SALE</span>' : ''}
                <img src="${product.image}" alt="${product.name}">
                <h3>${product.name}</h3>
                <p class="price"><span ;">Ksh ${currentPrice}</span> ${product.isSale ? `<span style="text-decoration:line-through; color:gray; font-size:14px;">Ksh ${product.price}</span>` : ''}</p>
                <button class="btn" onclick="addToCart(${product.id})">Add to Cart</button>
            </div>
        `;
    });
}
//Searching and dorting cart
function filterProducts() {
    const searchValue = document.getElementById('search-bar').value.toLowerCase();
    const categoryValue = document.getElementById('category-filter').value;

    const filtered = products.filter(product => {
        const matchesSearch = product.name.toLowerCase().includes(searchValue);
        const matchesCategory = categoryValue === 'all' || product.category === categoryValue;
        return matchesSearch && matchesCategory;
    });
    displayProducts(filtered, 'product-list', false);
}

// Shopping Cart Mechanics
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    const cartItem = cart.find(item => item.id === productId);
    const finalPrice = product.isSale ? product.salePrice : product.price;

    if (cartItem) {
        cartItem.quantity++;
    } else {
        cart.push({ ...product, price: finalPrice, quantity: 1 });
    }
    saveAndRefreshCart();
}

function changeQuantity(id, change) {
    const item = cart.find(i => i.id === id);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.id !== id);
        }
    }
    saveAndRefreshCart();
}

function saveAndRefreshCart() {
    localStorage.setItem('sukuma_cart', JSON.stringify(cart));
    updateCartUI();
}

function updateCartUI() {
    const container = document.getElementById('cart-items-container');
    const totalCount = document.getElementById('cart-count');
    const totalPriceElement = document.getElementById('cart-total-price');
    const modalTotal = document.getElementById('modal-total');
    
    if(!container) return;
    container.innerHTML = "";
    
    let totalItems = 0;
    let totalPrice = 0;

    cart.forEach(item => {
        totalItems += item.quantity;
        totalPrice += item.price * item.quantity;

        container.innerHTML += `
            <div class="cart-item">
                <div>
                    <h4>${item.name}</h4>
                    <p>KES ${item.price} x ${item.quantity}</p>
                </div>
                <div class="cart-item-controls">
                    <button onclick="changeQuantity(${item.id}, -1)">-</button>
                    <button onclick="changeQuantity(${item.id}, 1)">+</button>
                </div>
            </div>
        `;
    });

    if(totalCount) totalCount.textContent = totalItems;
    if(totalPriceElement) totalPriceElement.textContent = totalPrice;
    if(modalTotal) modalTotal.textContent = totalPrice;
}

// Layout Event Configuration
function setupEventListeners() {
    const cartToggle = document.getElementById('cart-toggle');
    const closeCart = document.getElementById('close-cart');
    const cartSidebar = document.getElementById('cart-sidebar');
    const openCheckout = document.getElementById('open-checkout');
    const checkoutModal = document.getElementById('checkout-modal');
    const closeModal = document.getElementById('close-modal');

    if(cartToggle) cartToggle.addEventListener('click', () => cartSidebar.classList.add('open'));
    if(closeCart) closeCart.addEventListener('click', () => cartSidebar.classList.remove('open'));

    if(openCheckout) {
        openCheckout.addEventListener('click', () => {
            if (cart.length === 0) {
                alert("Your shopping cart is empty!");
                return;
            }

             // Quick network request check to see if user session is valid on server
            fetch('api.php?action=place_order', { method: 'POST', body: JSON.stringify({}) }) 
            .then(res => res.json())
            .then(data => {
                if (data.message === 'Unauthorized. You must log in to checkout.') {
                    alert("Please sign in or register before checking out!");
                    window.location.href = "login.html";
                } else {
                    // Authorized path

            checkoutModal.classList.add('open');
                }
        })
      .catch(() => {
                // If it fails with an empty payload validation error instead of unauthorized, user is logged in
                checkoutModal.classList.add('open');
            });
        });
    }
    }
    if(closeModal) closeModal.addEventListener('click', () => checkoutModal.classList.remove('open'));

    // Dynamic Registration Form Submission Handling
const regForm = document.getElementById('register-form');
if (regForm) {
    regForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Wrap form elements automatically
        const formData = new FormData(regForm);

        fetch('register.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                regForm.reset();
            }
        })
        .catch(err => console.error("Error submitting registration:", err));
    });
}

// Dynamic Contact Form Submission Handling
const contactForm = document.getElementById('contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(contactForm);

        fetch('contact.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                contactForm.reset();
            }
        })
        .catch(err => console.error("Error submitting contact form:", err));
    });
}



   const checkoutForm = document.getElementById('checkout-form');
if(checkoutForm) {
    checkoutForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Grab dynamic inputs from your layout DOM
        const address = document.getElementById('delivery-address').value; 
        const paymentMethod = document.querySelector('input[name="payment"]:checked')?.value || 'Cash on Delivery';
        const total = parseFloat(document.getElementById('modal-total').textContent);

        const orderData = {
            address: address,
            payment_method: paymentMethod,
            total: total,
            cart: cart
        };

        // Post order array to your live PHP API
        fetch('api.php?action=place_order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        })
        .then(res => res.json())
        .then(resData => {
            if(resData.success) {
                alert("Success! Order placed safely in database. We will contact you shortly.");
                cart = [];
                saveAndRefreshCart();
                document.getElementById('checkout-modal').classList.remove('open');
                document.getElementById('cart-sidebar').classList.remove('open');
                e.target.reset();
                window.location.href = "index.html";
            } else {
                alert("Database Error: " + resData.message);
            }
        });
    });
}
// Dynamic Login Form Submission Handling
const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(loginForm);

        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                loginForm.reset();
                // Redirect user to homepage or catalogue dashboard upon successful login
                window.location.href = "index.html"; 
            }
        })
        .catch(err => console.error("Error submitting login details:", err));
    });
}


