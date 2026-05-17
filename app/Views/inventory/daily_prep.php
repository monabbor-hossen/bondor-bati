<?php
/**
 * Morning Prep / Daily Stock View
 * Loaded inside layout/main.php
 * Available variables: $pageTitle, $rawInventory (array from InventoryModel->getRawInventory())
 */
?>

<!-- Section Header -->
<div class="alert alert-info">
    📋 Log today's <strong>wastage</strong> and <strong>fresh processed</strong> stock before service begins.
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>

<!-- Prep Form: POSTs to InventoryController@saveDailyPrep -->
<form method="POST" action="?url=inventory/saveDailyPrep">

    <!-- Hidden field to log the date -->
    <input type="hidden" name="log_date" value="<?= date('Y-m-d') ?>">

    <?php if (!empty($rawInventory)): ?>

        <p class="section-title">Raw Inventory Items</p>

        <?php foreach ($rawInventory as $item): ?>
            <div class="item-row">
                <!-- Item Name as a heading -->
                <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                    Current Stock: <strong style="color: var(--text-primary);"><?= number_format($item['current_qty'], 2) ?></strong>
                    @ avg ৳<?= number_format($item['avg_unit_price'], 2) ?>
                </p>

                <!-- Hidden item ID -->
                <input type="hidden" name="items[<?= $item['id'] ?>][id]" value="<?= $item['id'] ?>">

                <div class="input-row">
                    <!-- Wastage Input -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="wastage_<?= $item['id'] ?>">Wastage</label>
                        <input
                            type="number"
                            class="form-input"
                            id="wastage_<?= $item['id'] ?>"
                            name="items[<?= $item['id'] ?>][wastage_qty]"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                        >
                    </div>

                    <!-- Fresh Processed Input -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="fresh_<?= $item['id'] ?>">Fresh Processed</label>
                        <input
                            type="number"
                            class="form-input"
                            id="fresh_<?= $item['id'] ?>"
                            name="items[<?= $item['id'] ?>][fresh_processed_qty]"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                        >
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
            ✅ Save Morning Prep
        </button>

    <?php else: ?>
        <div class="alert alert-warning">
            ⚠️ No raw inventory items found. Please add items first.
        </div>
    <?php endif; ?>

</form>
