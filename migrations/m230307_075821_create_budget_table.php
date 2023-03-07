<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%budget}}`.
 */
class m230307_075821_create_budget_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%budget}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'january' => $this->float()->defaultValue(0),
            'february' => $this->float()->defaultValue(0),
            'march' => $this->float()->defaultValue(0),
            'april' => $this->float()->defaultValue(0),
            'may' => $this->float()->defaultValue(0),
            'june' => $this->float()->defaultValue(0),
            'july' => $this->float()->defaultValue(0),
            'august' => $this->float()->defaultValue(0),
            'september' => $this->float()->defaultValue(0),
            'october' => $this->float()->defaultValue(0),
            'november' => $this->float()->defaultValue(0),
            'december' => $this->float()->defaultValue(0),
            'total' => $this->float()->defaultValue(0),
        ]);

        $this->addForeignKey('fk_budget_category', 'budget', 'category_id', 'category', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_budget_product', 'budget', 'product_id', 'product', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    { 
        $this->dropForeignKey('fk_budget_category', 'budget');
        $this->dropForeignKey('fk_budget_product', 'budget');
        $this->dropTable('{{%budget}}');
    }
}
