<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "stock_ledgers".
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $from_location_id
 * @property int|null $to_location_id
 * @property float $qty
 * @property float|null $unit_cost
 * @property float|null $unit_price
 * @property string $reason
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $moved_at
 * @property string|null $note
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Product $product
 * @property Location $fromLocation
 * @property Location $toLocation
 */
class StockLedger extends ActiveRecord
{
    const REASON_PURCHASE = 'purchase';
    const REASON_SALE = 'sale';
    const REASON_TRANSFER_IN = 'transfer_in';
    const REASON_TRANSFER_OUT = 'transfer_out';
    const REASON_ADJUSTMENT = 'adjustment';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%stock_ledgers}}';
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
            [['product_id', 'qty', 'reason', 'moved_at'], 'required'],
            [['product_id', 'from_location_id', 'to_location_id', 'reference_id'], 'integer'],
            [['qty', 'unit_cost', 'unit_price'], 'number'],
            [['reason'], 'in', 'range' => [
                self::REASON_PURCHASE,
                self::REASON_SALE,
                self::REASON_TRANSFER_IN,
                self::REASON_TRANSFER_OUT,
                self::REASON_ADJUSTMENT,
            ]],
            [['reference_type'], 'string', 'max' => 40],
            [['moved_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['note'], 'string'],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
            [['from_location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['from_location_id' => 'id']],
            [['to_location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['to_location_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product',
            'from_location_id' => 'From Location',
            'to_location_id' => 'To Location',
            'qty' => 'Quantity',
            'unit_cost' => 'Unit Cost',
            'unit_price' => 'Unit Price',
            'reason' => 'Reason',
            'reference_type' => 'Reference Type',
            'reference_id' => 'Reference ID',
            'moved_at' => 'Moved At',
            'note' => 'Note',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
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
     * Gets query for [[FromLocation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFromLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'from_location_id']);
    }

    /**
     * Gets query for [[ToLocation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getToLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'to_location_id']);
    }

    /**
     * Create ledger entry for purchase
     */
    public static function createPurchaseEntry($productId, $locationId, $qty, $unitCost, $referenceType, $referenceId, $note = null)
    {
        $ledger = new static([
            'product_id' => $productId,
            'to_location_id' => $locationId,
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'reason' => self::REASON_PURCHASE,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'moved_at' => date('Y-m-d H:i:s'),
            'note' => $note,
        ]);
        
        return $ledger->save();
    }

    /**
     * Create ledger entry for sale
     */
    public static function createSaleEntry($productId, $locationId, $qty, $unitPrice, $unitCost, $referenceType, $referenceId, $note = null)
    {
        $ledger = new static([
            'product_id' => $productId,
            'from_location_id' => $locationId,
            'qty' => -$qty, // Negative for outgoing
            'unit_price' => $unitPrice,
            'unit_cost' => $unitCost,
            'reason' => self::REASON_SALE,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'moved_at' => date('Y-m-d H:i:s'),
            'note' => $note,
        ]);
        
        return $ledger->save();
    }

    /**
     * Create ledger entries for transfer
     */
    public static function createTransferEntries($productId, $fromLocationId, $toLocationId, $qty, $referenceType, $referenceId, $note = null)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Transfer out
            $outLedger = new static([
                'product_id' => $productId,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'qty' => -$qty,
                'reason' => self::REASON_TRANSFER_OUT,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'moved_at' => date('Y-m-d H:i:s'),
                'note' => $note,
            ]);
            
            if (!$outLedger->save()) {
                throw new \Exception('Failed to create transfer out entry');
            }
            
            // Transfer in
            $inLedger = new static([
                'product_id' => $productId,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'qty' => $qty,
                'reason' => self::REASON_TRANSFER_IN,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'moved_at' => date('Y-m-d H:i:s'),
                'note' => $note,
            ]);
            
            if (!$inLedger->save()) {
                throw new \Exception('Failed to create transfer in entry');
            }
            
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Create ledger entry for adjustment
     */
    public static function createAdjustmentEntry($productId, $locationId, $qty, $note = null)
    {
        $ledger = new static([
            'product_id' => $productId,
            'to_location_id' => $qty > 0 ? $locationId : null,
            'from_location_id' => $qty < 0 ? $locationId : null,
            'qty' => $qty,
            'reason' => self::REASON_ADJUSTMENT,
            'reference_type' => 'adjustment',
            'moved_at' => date('Y-m-d H:i:s'),
            'note' => $note,
        ]);
        
        return $ledger->save();
    }

    /**
     * Get reason options
     */
    public static function getReasonOptions()
    {
        return [
            self::REASON_PURCHASE => 'Purchase',
            self::REASON_SALE => 'Sale',
            self::REASON_TRANSFER_IN => 'Transfer In',
            self::REASON_TRANSFER_OUT => 'Transfer Out',
            self::REASON_ADJUSTMENT => 'Adjustment',
        ];
    }

    /**
     * Get reason label
     */
    public function getReasonLabel()
    {
        $reasons = self::getReasonOptions();
        return $reasons[$this->reason] ?? $this->reason;
    }

    /**
     * Get formatted quantity with direction
     */
    public function getFormattedQty()
    {
        return ($this->qty >= 0 ? '+' : '') . number_format($this->qty, 3);
    }
}
