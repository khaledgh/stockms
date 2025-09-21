<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%locations}}`.
 */
class m003_000000_000000_create_locations_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%locations}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'type' => "ENUM('warehouse','van') NOT NULL DEFAULT 'warehouse'",
            'sales_user_id' => $this->integer(),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'deleted_at' => $this->integer(),
        ], $this->getTableOptions());

        $this->createIndex('idx-locations-type', '{{%locations}}', 'type');
        $this->createIndex('idx-locations-sales_user_id', '{{%locations}}', 'sales_user_id');
        $this->createIndex('idx-locations-is_active', '{{%locations}}', 'is_active');
        $this->createIndex('idx-locations-deleted_at', '{{%locations}}', 'deleted_at');

        $this->addForeignKey(
            'fk-locations-sales_user_id',
            '{{%locations}}',
            'sales_user_id',
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
        $this->dropForeignKey('fk-locations-sales_user_id', '{{%locations}}');
        $this->dropTable('{{%locations}}');
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
