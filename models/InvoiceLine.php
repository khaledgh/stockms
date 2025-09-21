<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "invoice_lines".
 *
 * @property int $id
 * @property int $invoice_id
 * @property int $product_id
 * @property float $qty
 * @property float $unit_price
 * @property float|null $unit_cost
 * @property float $line_total
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Invoice $invoice
 * @property Product $product
 */
class InvoiceLine extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%invoice_lines}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_id', 'product_id', 'qty', 'unit_price'], 'required'],
            [['invoice_id', 'product_id'], 'integer'],
            [['qty', 'unit_price', 'unit_cost', 'line_total'], 'number'],
            [['qty'], 'compare', 'compareValue' => 0, 'operator' => '>'],
            [['unit_price'], 'compare', 'compareValue' => 0, 'operator' => '>='],
            [['unit_cost'], 'compare', 'compareValue' => 0, 'operator' => '>=', 'skipOnEmpty' => true],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
            // Business rules
            [['unit_cost'], 'required', 'when' => function($model) {
                return $model->invoice && $model->invoice->type === Invoice::TYPE_PURCHASE;
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
            'invoice_id' => 'Invoice',
            'product_id' => 'Product',
            'qty' => 'Quantity',
            'unit_price' => 'Unit Price',
            'unit_cost' => 'Unit Cost',
            'line_total' => 'Line Total',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Invoice]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * Calculate line total before save
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->line_total = $this->qty * $this->unit_price;
            
            // Set unit cost from WAC for sales if not provided
            if (!$this->unit_cost && $this->invoice && $this->invoice->type === Invoice::TYPE_SALE) {
                $stockItem = StockItem::findOne([
                    'product_id' => $this->product_id,
                    'location_id' => $this->invoice->location_id
                ]);
                $this->unit_cost = $stockItem ? $stockItem->wac : 0;
            }
            
            return true;
        }
        return false;
    }

    /**
     * Update invoice totals after save/delete
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->invoice) {
            $this->invoice->calculateTotals();
            $this->invoice->save();
        }
    }

    /**
     * Update invoice totals after delete
     */
    public function afterDelete()
    {
        parent::afterDelete();
        if ($this->invoice) {
            $this->invoice->calculateTotals();
            $this->invoice->save();
        }
    }

    /**
     * Get profit for this line (sales only)
     */
    public function getProfit()
    {
        if (!$this->invoice || $this->invoice->type !== Invoice::TYPE_SALE) {
            return 0;
        }
        
        return ($this->unit_price - $this->unit_cost) * $this->qty;
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMargin()
    {
        if (!$this->unit_cost || $this->unit_cost == 0) {
            return 0;
        }
        
        return (($this->unit_price - $this->unit_cost) / $this->unit_cost) * 100;
    }

    /**
     * Check stock availability for sales
     */
    public function validateStockAvailability()
    {
        if (!$this->invoice || $this->invoice->type !== Invoice::TYPE_SALE) {
            return true;
        }

        $stockItem = StockItem::findOne([
            'product_id' => $this->product_id,
            'location_id' => $this->invoice->location_id
        ]);

        $availableQty = $stockItem ? $stockItem->qty : 0;
        
        if ($this->qty > $availableQty) {
            $this->addError('qty', "Insufficient stock. Available: {$availableQty}");
            return false;
        }

        return true;
    }
}
