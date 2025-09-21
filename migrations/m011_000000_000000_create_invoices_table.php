<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%invoices}}`.
 */
class m011_000000_000000_create_invoices_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%invoices}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(50)->notNull()->unique(),
            'type' => "ENUM('sale','purchase') NOT NULL",
            'location_id' => $this->integer()->notNull(),
            'vendor_id' => $this->integer(),
            'customer_id' => $this->integer(),
            'sub_total' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'discount' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'tax' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'total' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'paid' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'status' => "ENUM('draft','confirmed','paid','void') NOT NULL DEFAULT 'draft'",
            'confirmed_at' => $this->dateTime(),
            'notes' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'deleted_at' => $this->integer(),
        ], $this->getTableOptions());

        $this->createIndex('idx-invoices-code', '{{%invoices}}', 'code');
        $this->createIndex('idx-invoices-type', '{{%invoices}}', 'type');
        $this->createIndex('idx-invoices-location_id', '{{%invoices}}', 'location_id');
        $this->createIndex('idx-invoices-vendor_id', '{{%invoices}}', 'vendor_id');
        $this->createIndex('idx-invoices-customer_id', '{{%invoices}}', 'customer_id');
        $this->createIndex('idx-invoices-status', '{{%invoices}}', 'status');
        $this->createIndex('idx-invoices-confirmed_at', '{{%invoices}}', 'confirmed_at');
        $this->createIndex('idx-invoices-created_by', '{{%invoices}}', 'created_by');
        $this->createIndex('idx-invoices-deleted_at', '{{%invoices}}', 'deleted_at');

        $this->addForeignKey(
            'fk-invoices-location_id',
            '{{%invoices}}',
            'location_id',
            '{{%locations}}',
            'id',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk-invoices-vendor_id',
            '{{%invoices}}',
            'vendor_id',
            '{{%vendors}}',
            'id',
            'SET NULL'
        );

        $this->addForeignKey(
            'fk-invoices-customer_id',
            '{{%invoices}}',
            'customer_id',
            '{{%customers}}',
            'id',
            'SET NULL'
        );

        $this->addForeignKey(
            'fk-invoices-created_by',
            '{{%invoices}}',
            'created_by',
            '{{%users}}',
            'id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-invoices-created_by', '{{%invoices}}');
        $this->dropForeignKey('fk-invoices-customer_id', '{{%invoices}}');
        $this->dropForeignKey('fk-invoices-vendor_id', '{{%invoices}}');
        $this->dropForeignKey('fk-invoices-location_id', '{{%invoices}}');
        $this->dropTable('{{%invoices}}');
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
