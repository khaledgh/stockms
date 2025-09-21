<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%vendors}}`.
 */
class m007_000000_000000_create_vendors_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%vendors}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'phone' => $this->string(20),
            'email' => $this->string(255),
            'address' => $this->text(),
            'tax_no' => $this->string(50),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'deleted_at' => $this->integer(),
        ], $this->getTableOptions());

        $this->createIndex('idx-vendors-name', '{{%vendors}}', 'name');
        $this->createIndex('idx-vendors-phone', '{{%vendors}}', 'phone');
        $this->createIndex('idx-vendors-email', '{{%vendors}}', 'email');
        $this->createIndex('idx-vendors-deleted_at', '{{%vendors}}', 'deleted_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%vendors}}');
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
