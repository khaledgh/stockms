<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%templates}}`.
 */
class m014_000000_000000_create_templates_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%templates}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'kind' => "ENUM('pos_58','pos_80','a4') NOT NULL",
            'header_html' => $this->mediumText(),
            'footer_html' => $this->mediumText(),
            'body_html' => $this->mediumText(),
            'css' => $this->mediumText(),
            'is_default' => $this->boolean()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'deleted_at' => $this->integer(),
        ], $this->getTableOptions());

        $this->createIndex('idx-templates-kind', '{{%templates}}', 'kind');
        $this->createIndex('idx-templates-is_default', '{{%templates}}', 'is_default');
        $this->createIndex('idx-templates-deleted_at', '{{%templates}}', 'deleted_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%templates}}');
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
