<?php
$receipt = $receipt ?? [];
$items = $receipt['items'] ?? [];
$total = $receipt['total'] ?? 0;
$receiptId = $receipt['receipt_id'] ?? 'N/A';
$date = $receipt['date'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= $receiptId ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #fff; color: #000; font-family: 'Courier New', monospace; padding: 1rem; }
        .receipt { max-width: 280px; margin: 0 auto; border: 1px dashed #333; padding: 1rem; }
        .header { text-align: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px dashed #333; }
        .logo-img { max-width: 60%; border-radius: 50%; margin-bottom: 0.5rem; filter: grayscale(100%); }
        .brand { font-size: 1.4rem; font-weight: 900; letter-spacing: -0.02em; }
        .brand span { color: #e94560; }
        .address { font-size: 0.65rem; color: #666; margin-top: 0.25rem; }
        .divider { border-bottom: 1px dashed #333; margin: 0.75rem 0; }
        .meta { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.75rem; }
        .items { margin-bottom: 0.75rem; }
        .item { display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.35rem; }
        .item-name { flex: 1; }
        .item-qty { color: #666; margin: 0 0.5rem; }
        .item-price { text-align: right; }
        .total-row { display: flex; justify-content: space-between; font-size: 1rem; font-weight: 700; margin-top: 0.75rem; padding-top: 0.5rem; border-top: 1px dashed #333; }
        .total-label { font-weight: 700; }
        .total-amount { font-size: 1.2rem; font-weight: 900; }
        .footer { text-align: center; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed #333; font-size: 0.7rem; color: #666; }
        .thankyou { font-weight: 700; margin-bottom: 0.25rem; }
        .back-btn { display: block; width: 100%; padding: 0.85rem; background: #1a1a2e; color: #fff; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 700; cursor: pointer; margin-top: 1rem; text-decoration: none; text-align: center; }
        .back-btn:active { opacity: 0.8; }

        @media print {
            @page { margin: 0; size: 80mm; }
            body { background: #fff; padding: 0; }
            .receipt { max-width: 80mm; border: none; padding: 2mm; margin: 0; }
            .back-btn, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <img src="assets/img/logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
            <div class="brand">Bondor<span>.</span>Bati</div>
            <div class="address">Agargaon Market, Dhaka</div>
        </div>

        <div class="meta">
            <span>Receipt: <?= $receiptId ?></span>
            <span><?= $date ?></span>
        </div>

        <div class="divider"></div>

        <div class="items">
            <?php foreach ($items as $item): ?>
                <div class="item">
                    <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                    <span class="item-qty">×<?= $item['qty'] ?></span>
                    <span class="item-price">৳<?= number_format($item['subtotal'], 0) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="divider"></div>

        <div class="total-row">
            <span class="total-label">TOTAL</span>
            <span class="total-amount">৳<?= number_format($total, 0) ?></span>
        </div>

        <div class="footer">
            <div class="thankyou">Thank You!</div>
            <div>Come back tomorrow</div>
        </div>
    </div>

    <a href="?url=pos" class="back-btn no-print">← Back to POS</a>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };

        window.onafterprint = function() {
            window.location.href = '?url=pos';
        };
    </script>
</body>
</html>