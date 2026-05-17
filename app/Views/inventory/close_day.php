<?php
/**
 * Night Closing View
 * Loaded inside layout/main.php
 * Available variables:
 *   $pageTitle    - Page title string
 *   $itemsData    - Array of items with pre-filled opening_qty (from InventoryController::closeDayView)
 *   $cashInDrawer - (float) Post-submit result from FinanceController::calculateCashInDrawer
 *   $netProfit    - (float) Post-submit result from FinanceController::calculateNetProfit
 *   $submitted    - (bool) True when form has been submitted and results are available
 */
?>

<div class="alert alert-info">
    🌙 Enter the <strong>closing quantity</strong> for each item left unsold at end of service.
</div>

<?php if (!empty($submitted)): ?>
    <!-- ==============================
         READ-ONLY RESULTS PANEL
         Shown after form submission
    ============================== -->
    <div class="alert alert-success">
        ✅ Closing data saved! Here is your day-end summary.
    </div>

    <p class="section-title">Financial Summary</p>
    <div class="card">
        <div class="result-row">
            <span class="result-label">💵 Cash In Drawer</span>
            <span class="result-value <?= ($cashInDrawer ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                ৳<?= number_format(abs($cashInDrawer ?? 0), 2) ?>
                <?= ($cashInDrawer ?? 0) < 0 ? ' (Short)' : '' ?>
            </span>
        </div>
        <div class="result-row">
            <span class="result-label">📈 Net Profit</span>
            <span class="result-value <?= ($netProfit ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                ৳<?= number_format(abs($netProfit ?? 0), 2) ?>
                <?= ($netProfit ?? 0) < 0 ? ' (Loss)' : '' ?>
            </span>
        </div>
    </div>

    <a href="?url=home" class="btn btn-secondary">← Back to Dashboard</a>

<?php else: ?>
    <!-- ==============================
         CLOSING QTY FORM
    ============================== -->
    <form method="POST" action="?url=inventory/saveCloseDay">
        <input type="hidden" name="log_date" value="<?= date('Y-m-d') ?>">

        <?php if (!empty($itemsData)): ?>

            <p class="section-title">Sellable Items</p>

            <?php foreach ($itemsData as $item): ?>
                <div class="item-row">
                    <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>

                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.85rem;">
                        Opening Stock:
                        <strong style="color: var(--text-primary);">
                            <?= number_format($item['opening_qty'] ?? 0, 2) ?>
                        </strong>
                    </p>

                    <input type="hidden" name="items[<?= $item['item_id'] ?>][item_id]" value="<?= $item['item_id'] ?>">
                    <input type="hidden" name="items[<?= $item['item_id'] ?>][opening_qty]" value="<?= $item['opening_qty'] ?? 0 ?>">

                    <div class="input-row">
                        <!-- Closing Qty (unsold remaining) -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="closing_<?= $item['item_id'] ?>">Closing Qty</label>
                            <input
                                type="number"
                                class="form-input"
                                id="closing_<?= $item['item_id'] ?>"
                                name="items[<?= $item['item_id'] ?>][closing_qty]"
                                value="<?= htmlspecialchars($item['closing_qty'] ?? '') ?>"
                                placeholder="0"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                required
                            >
                        </div>

                        <!-- Complimentary Qty (given free, excluded from sales) -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="comp_<?= $item['item_id'] ?>">Complimentary</label>
                            <input
                                type="number"
                                class="form-input"
                                id="comp_<?= $item['item_id'] ?>"
                                name="items[<?= $item['item_id'] ?>][complimentary_qty]"
                                value="<?= htmlspecialchars($item['complimentary_qty'] ?? '') ?>"
                                placeholder="0"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                            >
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
                🌙 Finalize & Calculate Results
            </button>

        <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ No items loaded. Please ensure stock prep was completed this morning.
            </div>
        <?php endif; ?>

    </form>
<?php endif; ?>
