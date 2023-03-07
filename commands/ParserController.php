<?php

namespace app\commands;
require 'vendor/autoload.php';

use app\models\Category;
use app\models\Product;
use app\models\Sheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use yii\base\BaseObject;
use yii\base\Model;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Exception;


class ParserController extends Controller
{

    public function actionIndex($message = 'Parser start!')
    {
        echo $message . "\n";
        $filename = 'table.xlsx';
        $workSheetName = 'MA';

        $url = 'https://docs.google.com/spreadsheets/d/10En6qNTpYNeY_YFTWJ_3txXzvmOA7UxSCrKfKCFfaRw/edit#gid=1428297429';

//        try {
//            if (!$this->downloadXlsx($url, $filename)) {
//                print_r("Файл не загружен!");
//                exit();
//            }
//            print_r("Файл успешно загружен!");
//        } catch (ErrorException $e){
//            print_r("Ошибка загрузки файла! Или закройте таблицу!");
//            exit();
//        }

        $this->parseXlsxFile($filename, $workSheetName);

        return ExitCode::OK;
    }

    private function downloadXlsx($url, $filename)
    {

        $fName = 'table';
        $id = preg_match('/\/d\/(.+?)\//', $url, $matches) ? $matches[1] : null;

        if (!$id) {
            echo 'Не удалось извлечь ID таблицы из ссылки';
            exit;
        }
        $fileUrl = "https://docs.google.com/spreadsheets/d/{$id}/export?format=xlsx&id={$id}";

        if (file_exists($fName . '.xlsx')) {
            unlink($fName . '.xlsx');
        }

        if (file_exists('~$' . $fName . '.xlsx')) {
            unlink('~$' . $fName . '.xlsx');
        }

        file_put_contents($filename, file_get_contents($fileUrl));

        return true;
    }

    /** Запись БД наименований листов
     * @param $sheetNames
     */
    private function saveSheetsNames($sheetNames)
    {
        print_r("Сохранение наименований листов.");
        foreach ($sheetNames as $name) {
            $model = new Sheet();
            $model->name = $name;
            try {
                if (!$model->findOne(['name' => $model->name])) {
                    $model->save();
                }
            } catch (Exception $e) {
                print_r("Ошибка с БД! " . "\n" . $e->getMessage());
                exit();
            }
        }

    }

    private function parseXlsxFile($filename, $workSheetName)
    {

        // загружаем таблицу из файла
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($filename);


        $sheetNames = $spreadsheet->getSheetNames();
        $this->saveSheetsNames($sheetNames); // запись имен листов
        // запись категорий и продуктов

        $worksheet = $spreadsheet->getSheetByName($workSheetName);
        $this->saveCategoryAndProduct($worksheet);

    }

    /**
     * @param Worksheet $spreadsheet
     */
    private function saveCategoryAndProduct(Worksheet $spreadsheet)
    {

        // Поиск категорий и продуктов
        $category = '';
        $id = 0;
        foreach ($spreadsheet->getRowIterator() as $row) {

            $rowIndex = $row->getRowIndex();
            if ($rowIndex < 3) {
                continue;
            }

            $cell = $spreadsheet->getCell('A' . $rowIndex);
            $style = $cell->getStyle();

            if (strlen($cell->getValue()) < 1) {
                continue;
            }

            if ($cell->getValue() === 'CO-OP') {
                print_r("CO-OP");
                break;
            }

            // Проверка, является ли строка категорией
            if ($style->getFont()->getBold() && $cell->getValue() !== 'Total') {
                // Обработка категории
                $category = $cell->getValue();
                $model = new Category();
                $model->name = $category;
                try {
                    if (!$model->findOne(['name' => $model->name])) {
                        $model->save();
                        $id = $model->id;

                    }else{
                        $id = $model->findOne(['name' => $model->name])->id;
                    }
                } catch (Exception $e) {
                    print_r("Ошибка с БД при записи Category! " . "\n" . $e->getMessage());
                    exit();
                }

            }

            // Проверка, является ли строка продуктом
            if ($style->getFont()->getBold() === false && $cell->getValue() !== 'Total') {
                // Обработка продукта
                $productName = $cell->getValue();

                $model = new Product();
                $model->name = $productName;
                try {
                    if (!$model->findOne(['name' => $model->name])) {
                        $model->category_id = $id;
                        $model->save();


                    }
                } catch (Exception $e) {
                    print_r("Ошибка с БД при записи Product! " . "\n" . $e->getMessage());
                    exit();
                }
                // сохраняем каталог, забираем индекс
                // сохраняем продукт, указывая индекс каталога

            }

        }

    }
}
