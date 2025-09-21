<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "templates".
 *
 * @property int $id
 * @property string $name
 * @property string $kind
 * @property string|null $header_html
 * @property string|null $footer_html
 * @property string|null $body_html
 * @property string|null $css
 * @property bool $is_default
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_at
 */
class Template extends ActiveRecord
{
    const KIND_POS_58 = 'pos_58';
    const KIND_POS_80 = 'pos_80';
    const KIND_A4 = 'a4';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%templates}}';
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
            [['name', 'kind'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['kind'], 'in', 'range' => [self::KIND_POS_58, self::KIND_POS_80, self::KIND_A4]],
            [['header_html', 'footer_html', 'body_html', 'css'], 'string'],
            [['is_default'], 'boolean'],
            [['is_default'], 'default', 'value' => false],
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
            'kind' => 'Kind',
            'header_html' => 'Header HTML',
            'footer_html' => 'Footer HTML',
            'body_html' => 'Body HTML',
            'css' => 'CSS',
            'is_default' => 'Default Template',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get kind options
     */
    public static function getKindOptions()
    {
        return [
            self::KIND_POS_58 => 'POS 58mm',
            self::KIND_POS_80 => 'POS 80mm',
            self::KIND_A4 => 'A4 PDF',
        ];
    }

    /**
     * Get kind label
     */
    public function getKindLabel()
    {
        $kinds = self::getKindOptions();
        return $kinds[$this->kind] ?? $this->kind;
    }

    /**
     * Get default template for kind
     */
    public static function getDefault($kind)
    {
        return static::find()
            ->where(['kind' => $kind, 'is_default' => true])
            ->andWhere(['is', 'deleted_at', null])
            ->one();
    }

    /**
     * Set as default template
     */
    public function setAsDefault()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Remove default from other templates of same kind
            static::updateAll(
                ['is_default' => false],
                ['kind' => $this->kind]
            );
            
            // Set this as default
            $this->is_default = true;
            if (!$this->save()) {
                throw new \Exception('Failed to set template as default');
            }
            
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Render template with data
     */
    public function render($data = [])
    {
        $html = $this->header_html . $this->body_html . $this->footer_html;
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $html = str_replace($placeholder, $value, $html);
        }
        
        return $html;
    }

    /**
     * Get full HTML with CSS
     */
    public function getFullHtml($data = [])
    {
        $html = $this->render($data);
        
        if ($this->css) {
            $html = '<style>' . $this->css . '</style>' . $html;
        }
        
        return $html;
    }

    /**
     * Get sample data for preview
     */
    public static function getSampleData()
    {
        return [
            'company_name' => 'Your Company Name',
            'company_address' => '123 Business Street, City, State 12345',
            'company_phone' => '+1 (555) 123-4567',
            'company_email' => 'info@company.com',
            'company_logo' => '',
            'invoice_code' => 'SAL20241201001',
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'customer_name' => 'John Doe',
            'customer_phone' => '+1 (555) 987-6543',
            'customer_address' => '456 Customer Ave, City, State 54321',
            'vendor_name' => 'ABC Supplier',
            'vendor_phone' => '+1 (555) 111-2222',
            'lines' => [
                [
                    'product_name' => 'Sample Product 1',
                    'qty' => 2,
                    'unit_price' => 25.00,
                    'line_total' => 50.00,
                ],
                [
                    'product_name' => 'Sample Product 2',
                    'qty' => 1,
                    'unit_price' => 15.50,
                    'line_total' => 15.50,
                ],
            ],
            'sub_total' => 65.50,
            'discount' => 5.50,
            'tax' => 6.00,
            'total' => 66.00,
            'paid' => 66.00,
            'balance' => 0.00,
            'change' => 4.00,
        ];
    }

    /**
     * Get templates for dropdown
     */
    public static function getDropdownOptions($kind = null)
    {
        $query = static::find()
            ->select(['name', 'id'])
            ->where(['is', 'deleted_at', null])
            ->orderBy('is_default DESC, name');
            
        if ($kind) {
            $query->andWhere(['kind' => $kind]);
        }
        
        return $query->indexBy('id')->column();
    }
}
