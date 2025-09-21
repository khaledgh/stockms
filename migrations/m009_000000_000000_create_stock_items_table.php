<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%stock_items}}`.
 */
class m009_000000_000000_create_stock_items_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%stock_items}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'location_id' => $this->integer()->notNull(),
            'qty' => $this->decimal(12, 3)->notNull()->defaultValue(0),
            'wac' => $this->decimal(10, 2)->notNull()->defaultValue(0), // Weighted Average Cost
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $this->getTableOptions());

        $this->createIndex('idx-stock_items-product_id', '{{%stock_items}}', 'product_id');
        $this->createIndex('idx-stock_items-location_id', '{{%stock_items}}', 'location_id');
        $this->createIndex('idx-stock_items-product_location', '{{%stock_items}}', ['product_id', 'location_id'], true);

        $this->addForeignKey(
            'fk-stock_items-product_id',
            '{{%stock_items}}',
            'product_id',
            '{{%products}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-stock_items-location_id',
            '{{%stock_items}}',
            'location_id',
            '{{%locations}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-stock_items-location_id', '{{%stock_items}}');
        $this->dropForeignKey('fk-stock_items-product_id', '{{%stock_items}}');
        $this->dropTable('{{%stock_items}}');
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
