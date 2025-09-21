<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "vendors".
 *
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $tax_no
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_at
 *
 * @property Invoice[] $invoices
 */
class Vendor extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%vendors}}';
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
            [['name'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
            [['address'], 'string'],
            [['tax_no'], 'string', 'max' => 50],
            // Unique combination of name and phone if both provided
            [['name', 'phone'], 'unique', 'targetAttribute' => ['name', 'phone'], 'when' => function($model) {
                return !empty($model->phone);
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
            'name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'address' => 'Address',
            'tax_no' => 'Tax Number',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Invoices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['vendor_id' => 'id']);
    }

    /**
     * Get purchase invoices
     */
    public function getPurchaseInvoices()
    {
        return $this->getInvoices()->where(['type' => Invoice::TYPE_PURCHASE]);
    }

    /**
     * Get total purchases amount
     */
    public function getTotalPurchases()
    {
        return $this->getPurchaseInvoices()
            ->where(['status' => Invoice::STATUS_CONFIRMED])
            ->sum('total') ?: 0;
    }

    /**
     * Get outstanding balance (amount we owe)
     */
    public function getOutstandingBalance()
    {
        return $this->getPurchaseInvoices()
            ->where(['status' => Invoice::STATUS_CONFIRMED])
            ->sum('total - paid') ?: 0;
    }

    /**
     * Get vendors for dropdown
     */
    public static function getDropdownOptions()
    {
        return static::find()
            ->select(['name', 'id'])
            ->where(['is', 'deleted_at', null])
            ->orderBy('name')
            ->indexBy('id')
            ->column();
    }

    /**
     * Search vendors by name, phone, or email
     */
    public static function search($query)
    {
        return static::find()
            ->where(['like', 'name', $query])
            ->orWhere(['like', 'phone', $query])
            ->orWhere(['like', 'email', $query])
            ->andWhere(['is', 'deleted_at', null])
            ->limit(20)
            ->all();
    }

    /**
     * Get display name with phone
     */
    public function getDisplayName()
    {
        return $this->name . ($this->phone ? ' (' . $this->phone . ')' : '');
    }

    /**
     * Quick create vendor
     */
    public static function quickCreate($name, $phone = null, $email = null)
    {
        $vendor = new static([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]);
        
        if ($vendor->save()) {
            return $vendor;
        }
        
        return null;
    }
}
