<?php

use yii\db\Migration;

/**
 * Class m230306_074053_crate_sheet_table
 */
class m230306_074053_crate_sheet_table extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%sheet%}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%sheet%}}');
    }

}
