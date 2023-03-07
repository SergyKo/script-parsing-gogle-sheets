<?php

namespace app\models;

use yii\db\ActiveRecord;

class Sheet extends ActiveRecord
{
    public static function tableName()
    {
        return 'sheet';
    }


    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string']

        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name'
        ];
    }

}