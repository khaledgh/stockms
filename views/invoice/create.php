<?php

/** @var yii\web\View $this */
/** @var app\models\Invoice $model */
/** @var array $locations */
/** @var array $customers */
/** @var array $vendors */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap5\ActiveForm;
use app\models\Invoice;

$this->title = 'Create ' . ucfirst($model->type) . ' Invoice';
$this->params['breadcrumbs'][] = ['label' => 'Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div x-data="invoiceForm()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Create a new <?= $model->type ?> invoice</p>
        </div>
        <div>
            <?= Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], [
                'class' => 'btn btn-outline-secondary'
            ]) ?>
        </div>
    </div>

    <?php $form = ActiveForm::begin([
        'id' => 'invoice-form',
        'options' => ['x-ref' => 'invoiceForm']
    ]); ?>

    <div class="row">
        <!-- Invoice Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Invoice Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'location_id')->dropDownList($locations, [
                                'prompt' => 'Select Location',
                                'x-on:change' => 'locationChanged($event.target.value)'
                            ]) ?>
                        </div>
                        
                        <div class="col-md-6">
                            <?php if ($model->type === Invoice::TYPE_SALE): ?>
                                <?= $form->field($model, 'customer_id')->dropDownList($customers, [
                                    'prompt' => 'Select Customer',
                                    'x-on:change' => 'partyChanged($event.target.value, "customer")'
                                ]) ?>
                            <?php else: ?>
                                <?= $form->field($model, 'vendor_id')->dropDownList($vendors, [
                                    'prompt' => 'Select Vendor',
                                    'x-on:change' => 'partyChanged($event.target.value, "vendor")'
                                ]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <?= $form->field($model, 'notes')->textarea(['rows' => 3, 'placeholder' => 'Invoice notes (optional)']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Lines -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Items</h5>
                    <button type="button" class="btn btn-sm btn-primary" x-on:click="addLine()">
                        <i class="fas fa-plus me-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <!-- Product Search -->
                    <div class="mb-3">
                        <input type="text" 
                               class="form-control barcode-input" 
                               placeholder="Search products by name, SKU, or scan barcode..."
                               x-on:input.debounce.300ms="searchProducts($event.target.value)"
                               x-ref="productSearch">
                        
                        <!-- Search Results -->
                        <div class="list-group mt-2" x-show="searchResults.length > 0" x-transition>
                            <template x-for="product in searchResults" :key="product.id">
                                <button type="button" 
                                        class="list-group-item list-group-item-action"
                                        x-on:click="addProductToInvoice(product)">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong x-text="product.name"></strong>
                                            <br>
                                            <small class="text-muted">
                                                SKU: <span x-text="product.sku"></span> | 
                                                Stock: <span x-text="product.stock"></span>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div x-text="'$' + product.sell_price"></div>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Invoice Lines Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Product</th>
                                    <th style="width: 15%">Qty</th>
                                    <th style="width: 15%">Unit Price</th>
                                    <th style="width: 15%">Total</th>
                                    <th style="width: 10%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, index) in lines" :key="index">
                                    <tr>
                                        <td>
                                            <input type="hidden" :name="'InvoiceLine[' + index + '][product_id]'" :value="line.product_id">
                                            <div x-text="line.product_name"></div>
                                            <small class="text-muted" x-text="'SKU: ' + line.product_sku"></small>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   :name="'InvoiceLine[' + index + '][qty]'"
                                                   class="form-control" 
                                                   step="0.001"
                                                   min="0.001"
                                                   x-model="line.qty"
                                                   x-on:input="calculateLineTotal(index)">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   :name="'InvoiceLine[' + index + '][unit_price]'"
                                                   class="form-control" 
                                                   step="0.01"
                                                   min="0"
                                                   x-model="line.unit_price"
                                                   x-on:input="calculateLineTotal(index)">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   :name="'InvoiceLine[' + index + '][line_total]'"
                                                   class="form-control" 
                                                   step="0.01"
                                                   readonly
                                                   x-model="line.line_total">
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    x-on:click="removeLine(index)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                
                                <tr x-show="lines.length === 0">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No items added. Search and add products above.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Summary -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">Subtotal:</div>
                        <div class="col-6 text-end">
                            <strong x-text="'$' + subtotal.toFixed(2)"></strong>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Discount:</label>
                        </div>
                        <div class="col-6">
                            <?= $form->field($model, 'discount', ['template' => '{input}'])->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'x-model' => 'discount',
                                'x-on:input' => 'calculateTotals()',
                                'class' => 'form-control text-end'
                            ]) ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Tax:</label>
                        </div>
                        <div class="col-6">
                            <?= $form->field($model, 'tax', ['template' => '{input}'])->textInput([
                                'type' => 'number',
                                'step' => '0.01',
                                'min' => '0',
                                'x-model' => 'tax',
                                'x-on:input' => 'calculateTotals()',
                                'class' => 'form-control text-end'
                            ]) ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row mb-3">
                        <div class="col-6"><strong>Total:</strong></div>
                        <div class="col-6 text-end">
                            <strong x-text="'$' + total.toFixed(2)" class="fs-5 text-primary"></strong>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Invoice
                        </button>
                        
                        <button type="button" class="btn btn-outline-secondary" x-on:click="saveDraft()">
                            <i class="fas fa-file me-1"></i> Save as Draft
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
function invoiceForm() {
    return {
        lines: [],
        searchResults: [],
        subtotal: 0,
        discount: 0,
        tax: 0,
        total: 0,
        
        init() {
            // Listen for barcode scanned events
            document.addEventListener('barcodeScanned', (e) => {
                this.searchProducts(e.detail.barcode);
            });
        },
        
        async searchProducts(query) {
            if (!query || query.length < 2) {
                this.searchResults = [];
                return;
            }
            
            try {
                const response = await fetch(`<?= Url::to(['search-products']) ?>?q=${encodeURIComponent(query)}&location_id=${this.getLocationId()}`);
                const data = await response.json();
                this.searchResults = data.results || [];
            } catch (error) {
                console.error('Search failed:', error);
                this.searchResults = [];
            }
        },
        
        addProductToInvoice(product) {
            // Check if product already exists
            const existingIndex = this.lines.findIndex(line => line.product_id === product.id);
            
            if (existingIndex >= 0) {
                // Increase quantity
                this.lines[existingIndex].qty = parseFloat(this.lines[existingIndex].qty) + 1;
                this.calculateLineTotal(existingIndex);
            } else {
                // Add new line
                this.lines.push({
                    product_id: product.id,
                    product_name: product.name,
                    product_sku: product.sku,
                    qty: 1,
                    unit_price: product.sell_price,
                    line_total: product.sell_price
                });
            }
            
            this.calculateTotals();
            this.searchResults = [];
            this.$refs.productSearch.value = '';
        },
        
        addLine() {
            this.lines.push({
                product_id: '',
                product_name: '',
                product_sku: '',
                qty: 1,
                unit_price: 0,
                line_total: 0
            });
        },
        
        removeLine(index) {
            this.lines.splice(index, 1);
            this.calculateTotals();
        },
        
        calculateLineTotal(index) {
            const line = this.lines[index];
            line.line_total = (parseFloat(line.qty) || 0) * (parseFloat(line.unit_price) || 0);
            this.calculateTotals();
        },
        
        calculateTotals() {
            this.subtotal = this.lines.reduce((sum, line) => sum + (parseFloat(line.line_total) || 0), 0);
            this.total = this.subtotal - (parseFloat(this.discount) || 0) + (parseFloat(this.tax) || 0);
        },
        
        getLocationId() {
            const locationSelect = document.querySelector('[name="Invoice[location_id]"]');
            return locationSelect ? locationSelect.value : '';
        },
        
        locationChanged(locationId) {
            // Clear search results when location changes
            this.searchResults = [];
        },
        
        partyChanged(partyId, type) {
            // Handle party selection
            console.log(`${type} selected:`, partyId);
        },
        
        saveDraft() {
            // Set status to draft and submit
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'Invoice[status]';
            statusInput.value = 'draft';
            this.$refs.invoiceForm.appendChild(statusInput);
            this.$refs.invoiceForm.submit();
        }
    };
}

// Global function for barcode integration
window.addProductByBarcode = function(barcode) {
    // This will be called by the barcode scanner
    const invoiceForm = Alpine.$data(document.querySelector('[x-data*="invoiceForm"]'));
    if (invoiceForm) {
        invoiceForm.searchProducts(barcode);
    }
};
</script>
