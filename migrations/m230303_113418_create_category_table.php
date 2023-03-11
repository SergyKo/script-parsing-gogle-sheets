<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%category}}`.
 */
class m230303_113418_create_category_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%category}}');
    }
             /*

            запрос на всякий случай для выгрузки тоталь помесячно

              SELECT c.name AS category_name,
               ROUND(SUM(b.january),2) AS january,
               ROUND(SUM(b.february),2) AS february,
                ROUND(SUM(b.march),2) AS march,
               ROUND(SUM(b.april),2) AS april,
               ROUND(SUM(b.may),2) AS may,
              ROUND(SUM(b.june),2) AS june,
               ROUND(SUM(b.july),2) AS july,
               ROUND(SUM(b.august),2) AS august,
               ROUND(SUM(b.september),2) AS september,
               ROUND(SUM(b.october),2) AS october,
               ROUND(SUM(b.november),2) AS november,
               ROUND(SUM(b.december),2) AS december,
               ROUND(SUM(b.total),2) AS category
         FROM budget b
        JOIN category c ON c.id = b.category_id
        GROUP BY  c.name

            */
}
