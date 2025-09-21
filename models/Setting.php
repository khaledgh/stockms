<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Json;

/**
 * This is the model class for table "settings".
 *
 * @property string $key
 * @property mixed $value
 * @property int $created_at
 * @property int $updated_at
 */
class Setting extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%settings}}';
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
            [['key'], 'required'],
            [['key'], 'string', 'max' => 100],
            [['key'], 'unique'],
            [['value'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'key' => 'Key',
            'value' => 'Value',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get setting value
     */
    public static function get($key, $default = null)
    {
        $setting = static::findOne($key);
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value
     */
    public static function set($key, $value)
    {
        $setting = static::findOne($key);
        if (!$setting) {
            $setting = new static(['key' => $key]);
        }
        
        $setting->value = $value;
        return $setting->save();
    }

    /**
     * Get multiple settings
     */
    public static function getMultiple($keys)
    {
        $settings = static::find()->where(['key' => $keys])->indexBy('key')->all();
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = isset($settings[$key]) ? $settings[$key]->value : null;
        }
        
        return $result;
    }

    /**
     * Set multiple settings
     */
    public static function setMultiple($data)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($data as $key => $value) {
                if (!static::set($key, $value)) {
                    throw new \Exception("Failed to save setting: {$key}");
                }
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Get company settings
     */
    public static function getCompanySettings()
    {
        $keys = [
            'company.name',
            'company.address',
            'company.phone',
            'company.email',
            'company.tax_no',
            'company.logo',
            'company.currency',
            'company.currency_symbol',
            'company.tax_rate',
        ];
        
        $settings = static::getMultiple($keys);
        
        // Set defaults from params if not set
        foreach ($keys as $key) {
            if ($settings[$key] === null && isset(Yii::$app->params[$key])) {
                $settings[$key] = Yii::$app->params[$key];
            }
        }
        
        return $settings;
    }

    /**
     * Get number format settings
     */
    public static function getNumberFormatSettings()
    {
        return static::getMultiple([
            'number.decimal_places',
            'number.decimal_separator',
            'number.thousands_separator',
        ]);
    }

    /**
     * Get invoice numbering settings
     */
    public static function getInvoiceNumberingSettings()
    {
        return static::getMultiple([
            'invoice.sale_prefix',
            'invoice.purchase_prefix',
            'invoice.number_length',
            'invoice.reset_yearly',
        ]);
    }

    /**
     * Get print settings
     */
    public static function getPrintSettings()
    {
        return static::getMultiple([
            'print.default_pos_template',
            'print.default_a4_template',
            'print.auto_print',
            'print.copies',
        ]);
    }

    /**
     * Clear settings cache
     */
    public static function clearCache()
    {
        if (Yii::$app->cache) {
            Yii::$app->cache->delete('settings');
        }
    }

    /**
     * After save, clear cache
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        static::clearCache();
    }

    /**
     * After delete, clear cache
     */
    public function afterDelete()
    {
        parent::afterDelete();
        static::clearCache();
    }

    /**
     * JSON encode value before save
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (!is_string($this->value)) {
                $this->value = Json::encode($this->value);
            }
            return true;
        }
        return false;
    }

    /**
     * JSON decode value after find
     */
    public function afterFind()
    {
        parent::afterFind();
        if (is_string($this->value)) {
            $decoded = Json::decode($this->value, false);
            if ($decoded !== null) {
                $this->value = $decoded;
            }
        }
    }
}
