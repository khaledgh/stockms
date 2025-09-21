<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%stock_ledgers}}`.
 */
class m010_000000_000000_create_stock_ledgers_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%stock_ledgers}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'from_location_id' => $this->integer(),
            'to_location_id' => $this->integer(),
            'qty' => $this->decimal(12, 3)->notNull(),
            'unit_cost' => $this->decimal(10, 2),
            'unit_price' => $this->decimal(10, 2),
            'reason' => "ENUM('purchase','sale','transfer_in','transfer_out','adjustment') NOT NULL",
            'reference_type' => $this->string(40),
            'reference_id' => $this->integer(),
            'moved_at' => $this->dateTime()->notNull(),
            'note' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
        ], $this->getTableOptions());

        $this->createIndex('idx-stock_ledgers-product_id', '{{%stock_ledgers}}', 'product_id');
        $this->createIndex('idx-stock_ledgers-from_location_id', '{{%stock_ledgers}}', 'from_location_id');
        $this->createIndex('idx-stock_ledgers-to_location_id', '{{%stock_ledgers}}', 'to_location_id');
        $this->createIndex('idx-stock_ledgers-reason', '{{%stock_ledgers}}', 'reason');
        $this->createIndex('idx-stock_ledgers-reference', '{{%stock_ledgers}}', ['reference_type', 'reference_id']);
        $this->createIndex('idx-stock_ledgers-moved_at', '{{%stock_ledgers}}', 'moved_at');

        $this->addForeignKey(
            'fk-stock_ledgers-product_id',
            '{{%stock_ledgers}}',
            'product_id',
            '{{%products}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-stock_ledgers-from_location_id',
            '{{%stock_ledgers}}',
            'from_location_id',
            '{{%locations}}',
            'id',
            'SET NULL'
        );

        $this->addForeignKey(
            'fk-stock_ledgers-to_location_id',
            '{{%stock_ledgers}}',
            'to_location_id',
            '{{%locations}}',
            'id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-stock_ledgers-to_location_id', '{{%stock_ledgers}}');
        $this->dropForeignKey('fk-stock_ledgers-from_location_id', '{{%stock_ledgers}}');
        $this->dropForeignKey('fk-stock_ledgers-product_id', '{{%stock_ledgers}}');
        $this->dropTable('{{%stock_ledgers}}');
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
