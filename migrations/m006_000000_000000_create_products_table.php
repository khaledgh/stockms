<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%products}}`.
 */
class m006_000000_000000_create_products_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%products}}', [
            'id' => $this->primaryKey(),
            'sku' => $this->string(64)->notNull()->unique(),
            'barcode' => $this->string(100),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'type_id' => $this->integer(),
            'category_id' => $this->integer(),
            'cost_price' => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'sell_price' => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'reorder_level' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'deleted_at' => $this->integer(),
        ], $this->getTableOptions());

        $this->createIndex('idx-products-sku', '{{%products}}', 'sku');
        $this->createIndex('idx-products-barcode', '{{%products}}', 'barcode');
        $this->createIndex('idx-products-type_id', '{{%products}}', 'type_id');
        $this->createIndex('idx-products-category_id', '{{%products}}', 'category_id');
        $this->createIndex('idx-products-is_active', '{{%products}}', 'is_active');
        $this->createIndex('idx-products-deleted_at', '{{%products}}', 'deleted_at');

        $this->addForeignKey(
            'fk-products-type_id',
            '{{%products}}',
            'type_id',
            '{{%types}}',
            'id',
            'SET NULL'
        );

        $this->addForeignKey(
            'fk-products-category_id',
            '{{%products}}',
            'category_id',
            '{{%categories}}',
            'id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-products-category_id', '{{%products}}');
        $this->dropForeignKey('fk-products-type_id', '{{%products}}');
        $this->dropTable('{{%products}}');
    }

    protected function getTableOptions()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        return $tableOptions;
    }
}
