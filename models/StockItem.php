<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "stock_items".
 *
 * @property int $id
 * @property int $product_id
 * @property int $location_id
 * @property float $qty
 * @property float $wac Weighted Average Cost
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Product $product
 * @property Location $location
 */
class StockItem extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%stock_items}}';
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
            [['product_id', 'location_id'], 'required'],
            [['product_id', 'location_id'], 'integer'],
            [['qty', 'wac'], 'number'],
            [['qty', 'wac'], 'default', 'value' => 0],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => Location::class, 'targetAttribute' => ['location_id' => 'id']],
            [['product_id', 'location_id'], 'unique', 'targetAttribute' => ['product_id', 'location_id']],
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
            'location_id' => 'Location',
            'qty' => 'Quantity',
            'wac' => 'Weighted Average Cost',
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
     * Gets query for [[Location]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLocation()
    {
        return $this->hasOne(Location::class, ['id' => 'location_id']);
    }

    /**
     * Update stock quantity and WAC
     */
    public function updateStock($qtyChange, $unitCost = null)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Calculate new WAC if unit cost is provided (for purchases)
            if ($unitCost !== null && $qtyChange > 0) {
                $totalValue = ($this->qty * $this->wac) + ($qtyChange * $unitCost);
                $totalQty = $this->qty + $qtyChange;
                $this->wac = $totalQty > 0 ? $totalValue / $totalQty : $unitCost;
            }
            
            // Update quantity
            $this->qty += $qtyChange;
            
            // Ensure quantity doesn't go negative
            if ($this->qty < 0) {
                throw new \Exception('Insufficient stock. Available: ' . ($this->qty - $qtyChange));
            }
            
            if (!$this->save()) {
                throw new \Exception('Failed to update stock: ' . implode(', ', $this->getFirstErrors()));
            }
            
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Get or create stock item for product and location
     */
    public static function getOrCreate($productId, $locationId)
    {
        $stockItem = static::findOne(['product_id' => $productId, 'location_id' => $locationId]);
        
        if (!$stockItem) {
            $stockItem = new static([
                'product_id' => $productId,
                'location_id' => $locationId,
                'qty' => 0,
                'wac' => 0,
            ]);
            $stockItem->save();
        }
        
        return $stockItem;
    }

    /**
     * Get low stock items
     */
    public static function getLowStockItems($locationId = null)
    {
        $query = static::find()
            ->joinWith('product')
            ->where(['<=', 'stock_items.qty', new \yii\db\Expression('products.reorder_level')])
            ->andWhere(['products.is_active' => true])
            ->andWhere(['is', 'products.deleted_at', null]);
            
        if ($locationId) {
            $query->andWhere(['stock_items.location_id' => $locationId]);
        }
        
        return $query->all();
    }

    /**
     * Get stock value
     */
    public function getStockValue()
    {
        return $this->qty * $this->wac;
    }
}
