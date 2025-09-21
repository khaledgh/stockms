<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "payments".
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $method
 * @property float $amount
 * @property string $paid_at
 * @property string|null $ref
 * @property string|null $notes
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Invoice $invoice
 */
class Payment extends ActiveRecord
{
    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';
    const METHOD_TRANSFER = 'transfer';
    const METHOD_COD = 'cod';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%payments}}';
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
            [['invoice_id', 'amount', 'paid_at'], 'required'],
            [['invoice_id'], 'integer'],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
            [['amount'], 'number', 'min' => 0.01],
            [['method'], 'in', 'range' => [self::METHOD_CASH, self::METHOD_CARD, self::METHOD_TRANSFER, self::METHOD_COD]],
            [['method'], 'default', 'value' => self::METHOD_CASH],
            [['paid_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['paid_at'], 'default', 'value' => function() { return date('Y-m-d H:i:s'); }],
            [['ref'], 'string', 'max' => 100],
            [['notes'], 'string'],
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
            'method' => 'Payment Method',
            'amount' => 'Amount',
            'paid_at' => 'Paid At',
            'ref' => 'Reference',
            'notes' => 'Notes',
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
     * Get payment method options
     */
    public static function getMethodOptions()
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_CARD => 'Card',
            self::METHOD_TRANSFER => 'Bank Transfer',
            self::METHOD_COD => 'Cash on Delivery',
        ];
    }

    /**
     * Get method label
     */
    public function getMethodLabel()
    {
        $methods = self::getMethodOptions();
        return $methods[$this->method] ?? $this->method;
    }

    /**
     * Validate payment amount against invoice balance
     */
    public function validatePaymentAmount()
    {
        if ($this->invoice) {
            $balance = $this->invoice->getBalance();
            if ($this->amount > $balance) {
                $this->addError('amount', "Payment amount cannot exceed outstanding balance of " . Yii::$app->formatter->asCurrency($balance));
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            return $this->validatePaymentAmount();
        }
        return false;
    }
}
