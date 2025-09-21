<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%invoice_lines}}`.
 */
class m012_000000_000000_create_invoice_lines_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%invoice_lines}}', [
            'id' => $this->primaryKey(),
            'invoice_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'qty' => $this->decimal(12, 3)->notNull(),
            'unit_price' => $this->decimal(10, 2)->notNull(),
            'unit_cost' => $this->decimal(10, 2),
            'line_total' => $this->decimal(12, 2)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $this->getTableOptions());

        $this->createIndex('idx-invoice_lines-invoice_id', '{{%invoice_lines}}', 'invoice_id');
        $this->createIndex('idx-invoice_lines-product_id', '{{%invoice_lines}}', 'product_id');

        $this->addForeignKey(
            'fk-invoice_lines-invoice_id',
            '{{%invoice_lines}}',
            'invoice_id',
            '{{%invoices}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-invoice_lines-product_id',
            '{{%invoice_lines}}',
            'product_id',
            '{{%products}}',
            'id',
            'RESTRICT'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-invoice_lines-product_id', '{{%invoice_lines}}');
        $this->dropForeignKey('fk-invoice_lines-invoice_id', '{{%invoice_lines}}');
        $this->dropTable('{{%invoice_lines}}');
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
