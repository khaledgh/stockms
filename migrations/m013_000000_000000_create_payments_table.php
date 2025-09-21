<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%payments}}`.
 */
class m013_000000_000000_create_payments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%payments}}', [
            'id' => $this->primaryKey(),
            'invoice_id' => $this->integer()->notNull(),
            'method' => "ENUM('cash','card','transfer','cod') NOT NULL DEFAULT 'cash'",
            'amount' => $this->decimal(12, 2)->notNull(),
            'paid_at' => $this->dateTime()->notNull(),
            'ref' => $this->string(100),
            'notes' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
        ], $this->getTableOptions());

        $this->createIndex('idx-payments-invoice_id', '{{%payments}}', 'invoice_id');
        $this->createIndex('idx-payments-method', '{{%payments}}', 'method');
        $this->createIndex('idx-payments-paid_at', '{{%payments}}', 'paid_at');
        $this->createIndex('idx-payments-ref', '{{%payments}}', 'ref');

        $this->addForeignKey(
            'fk-payments-invoice_id',
            '{{%payments}}',
            'invoice_id',
            '{{%invoices}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-payments-invoice_id', '{{%payments}}');
        $this->dropTable('{{%payments}}');
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
