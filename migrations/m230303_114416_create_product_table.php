<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product}}`.
 */
class m230303_114416_create_product_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%product}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'category_id' => $this->integer()->notNull(),
            'total' => $this->decimal(10, 2)->defaultValue(null),
            'january' => $this->decimal(10, 2)->defaultValue(null),
            'february' => $this->decimal(10, 2)->defaultValue(null),
            'march' => $this->decimal(10, 2)->defaultValue(null),
            'april' => $this->decimal(10, 2)->defaultValue(null),
            'may' => $this->decimal(10, 2)->defaultValue(null),
            'june' => $this->decimal(10, 2)->defaultValue(null),
            'july' => $this->decimal(10, 2)->defaultValue(null),
            'august' => $this->decimal(10, 2)->defaultValue(null),
            'september' => $this->decimal(10, 2)->defaultValue(null),
            'october' => $this->decimal(10, 2)->defaultValue(null),
            'november' => $this->decimal(10, 2)->defaultValue(null),
            'december' => $this->decimal(10, 2)->defaultValue(null),
            'year' => $this->integer()->notNull()
        ]);

        $this->addForeignKey(
            'fk-product-category_id',
            'product',
            'category_id',
            'category',
            'id',
            'CASCADE',
            'CASCADE'
        );

    }


    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-product-category_id', 'product');
        $this->dropTable('{{%product}}');
    }
}
