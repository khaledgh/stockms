<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "invoices".
 *
 * @property int $id
 * @property string $code
 * @property string $type
 * @property int $location_id
 * @property int|null $vendor_id
 * @property int|null $customer_id
 * @property float $sub_total
 * @property float $discount
 * @property float $tax
 * @property float $total
 * @property float $paid
 * @property string $status
 * @property string|null $confirmed_at
 * @property string|null $notes
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_at
 *
 * @property Location $location
 * @property Vendor $vendor
 * @property Customer $customer
 * @property User $createdBy
 * @property InvoiceLine[] $invoiceLines
 * @property Payment[] $payments
 */
class Invoice extends ActiveRecord
{
    const TYPE_SALE = 'sale';
    const TYPE_PURCHASE = 'purchase';

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PAID = 'paid';
    const STATUS_VOID = 'void';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%invoices}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            BlameableBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'type', 'location_id'], 'required'],
            [['code'], 'string', 'max' => 50],
            [['code'], 'unique'],
            [['type'], 'in', 'range' => [self::TYPE_SALE, self::TYPE_PURCHASE]],
            [['location_id', 'vendor_id', 'customer_id'], 'integer'],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
            [['vendor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Vendor::class, 'targetAttribute' => ['vendor_id' => 'id']],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::class, 'targetAttribute' => ['customer_id' => 'id']],
            [['sub_total', 'discount', 'tax', 'total', 'paid'], 'number', 'min' => 0],
            [['sub_total', 'discount', 'tax', 'total', 'paid'], 'default', 'value' => 0],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_CONFIRMED, self::STATUS_PAID, self::STATUS_VOID]],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
            [['confirmed_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['notes'], 'string'],
            // Business rules
            [['vendor_id'], 'required', 'when' => function($model) {
                return $model->type === self::TYPE_PURCHASE;
            }],
            [['customer_id'], 'required', 'when' => function($model) {
                return $model->type === self::TYPE_SALE;
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Invoice Code',
            'type' => 'Type',
            'location_id' => 'Location',
            'vendor_id' => 'Vendor',
            'customer_id' => 'Customer',
            'sub_total' => 'Sub Total',
            'discount' => 'Discount',
            'tax' => 'Tax',
            'total' => 'Total',
            'paid' => 'Paid',
            'status' => 'Status',
            'confirmed_at' => 'Confirmed At',
            'notes' => 'Notes',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Location]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    /**
     * Gets query for [[Vendor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }

    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[InvoiceLines]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceLines()
    {
        return $this->hasMany(InvoiceLine::class, ['invoice_id' => 'id']);
    }

    /**
     * Gets query for [[Payments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(Payment::class, ['invoice_id' => 'id']);
    }

    /**
     * Generate invoice code
     */
    public function generateCode()
    {
        $prefix = $this->type === self::TYPE_SALE ? 'SAL' : 'PUR';
        $date = date('Ymd');
        
        // Find the next number for today
        $lastInvoice = static::find()
            ->where(['like', 'code', $prefix . $date])
            ->orderBy(['code' => SORT_DESC])
            ->one();
            
        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->code, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }
        
        $this->code = $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals from lines
     */
    public function calculateTotals()
    {
        $this->sub_total = $this->getInvoiceLines()->sum('line_total') ?: 0;
        
        // Calculate tax
        $taxRate = Yii::$app->params['company.tax_rate'] ?? 0;
        $this->tax = ($this->sub_total - $this->discount) * ($taxRate / 100);
        
        // Calculate total
        $this->total = $this->sub_total - $this->discount + $this->tax;
    }

    /**
     * Confirm invoice
     */
    public function confirm()
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \Exception('Only draft invoices can be confirmed');
        }

        if (empty($this->invoiceLines)) {
            throw new \Exception('Invoice must have at least one line item');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Update stock and create ledger entries
            foreach ($this->invoiceLines as $line) {
                $stockItem = StockItem::getOrCreate($line->product_id, $this->location_id);
                
                if ($this->type === self::TYPE_PURCHASE) {
                    // Increase stock
                    $stockItem->updateStock($line->qty, $line->unit_cost);
                    
                    // Create purchase ledger entry
                    StockLedger::createPurchaseEntry(
                        $line->product_id,
                        $this->location_id,
                        $line->qty,
                        $line->unit_cost,
                        'invoice',
                        $this->id,
                        'Purchase invoice: ' . $this->code
                    );
                } else {
                    // Decrease stock (sale)
                    $stockItem->updateStock(-$line->qty);
                    
                    // Create sale ledger entry
                    StockLedger::createSaleEntry(
                        $line->product_id,
                        $this->location_id,
                        $line->qty,
                        $line->unit_price,
                        $stockItem->wac,
                        'invoice',
                        $this->id,
                        'Sales invoice: ' . $this->code
                    );
                }
            }

            // Update invoice status
            $this->status = self::STATUS_CONFIRMED;
            $this->confirmed_at = date('Y-m-d H:i:s');
            
            if (!$this->save()) {
                throw new \Exception('Failed to update invoice status');
            }

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Add payment
     */
    public function addPayment($amount, $method, $ref = null, $notes = null)
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            throw new \Exception('Only confirmed invoices can receive payments');
        }

        $balance = $this->getBalance();
        if ($amount > $balance) {
            throw new \Exception('Payment amount cannot exceed outstanding balance');
        }

        $payment = new Payment([
            'invoice_id' => $this->id,
            'amount' => $amount,
            'method' => $method,
            'ref' => $ref,
            'notes' => $notes,
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        if ($payment->save()) {
            $this->paid += $amount;
            
            // Check if fully paid
            if ($this->paid >= $this->total) {
                $this->status = self::STATUS_PAID;
            }
            
            $this->save();
            return $payment;
        }

        return false;
    }

    /**
     * Get balance (remaining amount to pay)
     */
    public function getBalance()
    {
        return $this->total - $this->paid;
    }

    /**
     * Check if invoice is editable
     */
    public function isEditable()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Get status options
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PAID => 'Paid',
            self::STATUS_VOID => 'Void',
        ];
    }

    /**
     * Get type options
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_SALE => 'Sale',
            self::TYPE_PURCHASE => 'Purchase',
        ];
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        $statuses = self::getStatusOptions();
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get type label
     */
    public function getTypeLabel()
    {
        $types = self::getTypeOptions();
        return $types[$this->type] ?? $this->type;
    }

    /**
     * Get party (customer or vendor)
     */
    public function getParty()
    {
        return $this->type === self::TYPE_SALE ? $this->customer : $this->vendor;
    }

    /**
     * Get party name
     */
    public function getPartyName()
    {
        $party = $this->getParty();
        return $party ? $party->name : 'N/A';
    }

    /**
     * Get COGS (Cost of Goods Sold) for sales
     */
    public function getCOGS()
    {
        if ($this->type !== self::TYPE_SALE) {
            return 0;
        }

        return $this->getInvoiceLines()->sum('qty * unit_cost') ?: 0;
    }

    /**
     * Get gross profit for sales
     */
    public function getGrossProfit()
    {
        if ($this->type !== self::TYPE_SALE) {
            return 0;
        }

        return $this->total - $this->getCOGS();
    }
}
