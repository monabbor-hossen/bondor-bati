<?php
$items = $menuItems ?? [];
?>

<style>
    .pos-container { display: flex; gap: 1rem; height: calc(100vh - 180px); }
    .menu-section { flex: 1; overflow-y: auto; }
    .cart-section { width: 280px; background: var(--bg-card); border-radius: var(--radius); padding: 1rem; display: flex; flex-direction: column; }
    .menu-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
    .menu-item { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 0.75rem; text-align: center; cursor: pointer; transition: all 0.15s; }
    .menu-item:active { transform: scale(0.96); border-color: var(--accent); }
    .menu-item .thumb { width: 100%; height: 70px; object-fit: cover; border-radius: 8px; margin-bottom: 0.5rem; background: var(--bg-surface); }
    .menu-item .name { font-weight: 700; font-size: 0.9rem; margin-bottom: 0.25rem; }
    .menu-item .price { color: var(--accent); font-weight: 800; font-size: 1rem; }
    .menu-item .stock { font-size: 0.7rem; color: var(--text-muted); margin-top: 0.35rem; }
    .cart-items { flex: 1; overflow-y: auto; margin-bottom: 0.75rem; }
    .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--border); }
    .cart-item:last-child { border-bottom: none; }
    .cart-item-info { flex: 1; }
    .cart-item-name { font-size: 0.85rem; font-weight: 600; }
    .cart-item-price { font-size: 0.75rem; color: var(--text-muted); }
    .cart-item-qty { display: flex; align-items: center; gap: 0.5rem; }
    .qty-btn { width: 26px; height: 26px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--text-primary); font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .cart-total { border-top: 2px solid var(--accent); padding-top: 0.75rem; margin-bottom: 0.75rem; }
    .cart-total .label { font-size: 0.8rem; color: var(--text-muted); }
    .cart-total .amount { font-size: 1.5rem; font-weight: 800; color: var(--accent); }
    .checkout-btn { width: 100%; padding: 1rem; background: var(--success); color: #fff; border: none; border-radius: var(--radius); font-size: 1rem; font-weight: 800; cursor: pointer; text-transform: uppercase; letter-spacing: 0.05em; }
    .checkout-btn:active { transform: scale(0.97); opacity: 0.9; }
    .empty-cart { text-align: center; color: var(--text-muted); font-size: 0.9rem; padding: 2rem 0; }
    @media (max-width: 600px) {
        .pos-container { flex-direction: column; height: auto; }
        .cart-section { width: 100%; }
        .menu-grid { grid-template-columns: repeat(3, 1fr); }
    }
</style>

<p class="section-title">🛒 Point of Sale</p>

<div class="pos-container">
    <div class="menu-section">
        <div class="menu-grid">
            <?php foreach ($items as $item): ?>
                <div class="menu-item" onclick="addToCart(<?= $item['id'] ?>, '<?= htmlspecialchars($item['item_name']) ?>', <?= $item['selling_price'] ?>)">
                    <img src="assets/img/menu/<?= $item['id'] ?>.jpg" alt="<?= htmlspecialchars($item['item_name']) ?>" class="thumb" onerror="this.style.display='none'">
                    <div class="name"><?= htmlspecialchars($item['item_name']) ?></div>
                    <div class="price">৳<?= number_format($item['selling_price'], 0) ?></div>
                    <div class="stock">Stock: <?= $item['available_qty'] > 0 ? $item['available_qty'] : '—' ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="cart-section">
        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem;">Current Order</div>
        <div class="cart-items" id="cartItems">
            <div class="empty-cart">Tap items to add</div>
        </div>
        <div class="cart-total">
            <div class="label">Total Payable</div>
            <div class="amount">৳<span id="cartTotal">0</span></div>
        </div>
        <button class="checkout-btn" onclick="checkout()">Checkout (Cash)</button>
    </div>
</div>

<script>
    let cart = [];

    function addToCart(id, name, price) {
        const existing = cart.find(item => item.item_id === id);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({ item_id: id, name: name, price: price, qty: 1 });
        }
        renderCart();
    }

    function updateQty(id, change) {
        const item = cart.find(i => i.item_id === id);
        if (item) {
            item.qty += change;
            if (item.qty <= 0) {
                cart = cart.filter(i => i.item_id !== id);
            }
        }
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cartItems');
        const totalEl = document.getElementById('cartTotal');

        if (cart.length === 0) {
            container.innerHTML = '<div class="empty-cart">Tap items to add</div>';
            totalEl.textContent = '0';
            return;
        }

        let html = '';
        let total = 0;

        cart.forEach(item => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            html += `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">৳${item.price} × ${item.qty}</div>
                    </div>
                    <div class="cart-item-qty">
                        <button class="qty-btn" onclick="updateQty(${item.item_id}, -1)">−</button>
                        <span style="font-weight:700; min-width:20px; text-align:center;">${item.qty}</span>
                        <button class="qty-btn" onclick="updateQty(${item.item_id}, 1)">+</button>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        totalEl.textContent = total.toLocaleString();
    }

    async function checkout() {
        if (cart.length === 0) {
            alert('Cart is empty!');
            return;
        }

        const btn = document.querySelector('.checkout-btn');
        btn.textContent = 'Processing...';
        btn.disabled = true;

        try {
            const response = await fetch('?url=pos/checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart: cart })
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = result.redirect;
            } else {
                alert('Error: ' + (result.error || 'Unknown error'));
                btn.textContent = 'Checkout (Cash)';
                btn.disabled = false;
            }
        } catch (err) {
            alert('Network error. Try again.');
            btn.textContent = 'Checkout (Cash)';
            btn.disabled = false;
        }
    }
</script>