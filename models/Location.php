<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "locations".
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property int|null $sales_user_id
 * @property bool $is_active
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_at
 *
 * @property User $salesUser
 * @property StockItem[] $stockItems
 * @property Invoice[] $invoices
 */
class Location extends ActiveRecord
{
    const TYPE_WAREHOUSE = 'warehouse';
    const TYPE_VAN = 'van';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%locations}}';
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
            [['name'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['type'], 'in', 'range' => [self::TYPE_WAREHOUSE, self::TYPE_VAN]],
            [['sales_user_id'], 'integer'],
            [['sales_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['sales_user_id' => 'id']],
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
            'name' => 'Name',
            'type' => 'Type',
            'sales_user_id' => 'Sales User',
            'is_active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[SalesUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSalesUser()
    {
        return $this->hasOne(User::class, ['id' => 'sales_user_id']);
    }

    /**
     * Gets query for [[StockItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStockItems()
    {
        return $this->hasMany(StockItem::class, ['location_id' => 'id']);
    }

    /**
     * Gets query for [[Invoices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['location_id' => 'id']);
    }

    /**
     * Get type options for dropdown
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_WAREHOUSE => 'Warehouse',
            self::TYPE_VAN => 'Van',
        ];
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
     * Get active locations
     */
    public static function getActiveLocations()
    {
        return static::find()
            ->where(['is_active' => true])
            ->andWhere(['is', 'deleted_at', null])
            ->all();
    }

    /**
     * Get warehouses
     */
    public static function getWarehouses()
    {
        return static::find()
            ->where(['type' => self::TYPE_WAREHOUSE, 'is_active' => true])
            ->andWhere(['is', 'deleted_at', null])
            ->all();
    }

    /**
     * Get vans
     */
    public static function getVans()
    {
        return static::find()
            ->where(['type' => self::TYPE_VAN, 'is_active' => true])
            ->andWhere(['is', 'deleted_at', null])
            ->all();
    }
}
