<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "products".
 *
 * @property int $id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name
 * @property string|null $description
 * @property int|null $type_id
 * @property int|null $category_id
 * @property float $cost_price
 * @property float $sell_price
 * @property int $reorder_level
 * @property bool $is_active
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_at
 *
 * @property Category $category
 * @property Type $type
 * @property StockItem[] $stockItems
 * @property InvoiceLine[] $invoiceLines
 * @property StockLedger[] $stockLedgers
 */
class Product extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%products}}';
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
            [['sku', 'name'], 'required'],
            [['sku'], 'string', 'max' => 64],
            [['sku'], 'unique'],
            [['barcode'], 'string', 'max' => 100],
            [['barcode'], 'unique', 'skipOnEmpty' => true],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['type_id', 'category_id'], 'integer'],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => Type::class, 'targetAttribute' => ['type_id' => 'id']],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
            [['cost_price', 'sell_price'], 'number', 'min' => 0],
            [['cost_price', 'sell_price'], 'default', 'value' => 0],
            [['reorder_level'], 'integer', 'min' => 0],
            [['reorder_level'], 'default', 'value' => 0],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sku' => 'SKU',
            'barcode' => 'Barcode',
            'name' => 'Name',
            'description' => 'Description',
            'type_id' => 'Type',
            'category_id' => 'Category',
            'cost_price' => 'Cost Price',
            'sell_price' => 'Sell Price',
            'reorder_level' => 'Reorder Level',
            'is_active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * Gets query for [[Type]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(Type::class, ['id' => 'type_id']);
    }

    /**
     * Gets query for [[StockItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStockItems()
    {
        return $this->hasMany(StockItem::class, ['product_id' => 'id']);
    }

    /**
     * Gets query for [[InvoiceLines]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceLines()
    {
        return $this->hasMany(InvoiceLine::class, ['product_id' => 'id']);
    }

    /**
     * Gets query for [[StockLedgers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStockLedgers()
    {
        return $this->hasMany(StockLedger::class, ['product_id' => 'id']);
    }

    /**
     * Get total stock across all locations
     */
    public function getTotalStock()
    {
        return $this->getStockItems()->sum('qty') ?: 0;
    }

    /**
     * Get stock for specific location
     */
    public function getStockForLocation($locationId)
    {
        $stockItem = $this->getStockItems()->where(['location_id' => $locationId])->one();
        return $stockItem ? $stockItem->qty : 0;
    }

    /**
     * Get weighted average cost for specific location
     */
    public function getWACForLocation($locationId)
    {
        $stockItem = $this->getStockItems()->where(['location_id' => $locationId])->one();
        return $stockItem ? $stockItem->wac : $this->cost_price;
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock()
    {
        return $this->getTotalStock() <= $this->reorder_level;
    }

    /**
     * Search products by SKU or barcode
     */
    public static function findBySKUOrBarcode($query)
    {
        return static::find()
            ->where(['sku' => $query])
            ->orWhere(['barcode' => $query])
            ->andWhere(['is_active' => true])
            ->andWhere(['is', 'deleted_at', null])
            ->one();
    }

    /**
     * Search products by name, SKU, or barcode
     */
    public static function search($query)
    {
        return static::find()
            ->where(['like', 'name', $query])
            ->orWhere(['like', 'sku', $query])
            ->orWhere(['like', 'barcode', $query])
            ->andWhere(['is_active' => true])
            ->andWhere(['is', 'deleted_at', null])
            ->limit(20)
            ->all();
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMargin()
    {
        if ($this->cost_price == 0) {
            return 0;
        }
        return (($this->sell_price - $this->cost_price) / $this->cost_price) * 100;
    }
}
