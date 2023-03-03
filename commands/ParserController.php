<?php
namespace app\commands;
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use yii\console\Controller;
use yii\console\ExitCode;


class ParserController extends Controller
{

    public function actionIndex($message = 'Parser start!')
    {
        echo $message . "\n";
        $filename = 'table.xlsx';
        $workSheetName = 'MA';

        $url = 'https://docs.google.com/spreadsheets/d/10En6qNTpYNeY_YFTWJ_3txXzvmOA7UxSCrKfKCFfaRw/edit#gid=1428297429';
//        if (!$this->downloadXlsx($url, $filename)){
//            print_r("Файл не загружен!");
//            exit();
//        }
//        print_r("Файл успешно загружен!");

        $this->parseXlsxFile($filename, $workSheetName);

        return ExitCode::OK;
    }

    private function downloadXlsx($url, $filename){

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

    private function parseXlsxFile($filename, $workSheetName )
    {

        // загружаем таблицу из файла
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($filename);

        // получаем нужный лист
        $sheet = $spreadsheet->getSheetByName($workSheetName);

        $data = [];

        // Проходимся по всем строкам до строки COOP
        for ($row = 3; $row <= $sheet->getHighestRow(); $row++) {


            // Пропускаем пустые строки
            if (empty(trim($sheet->getCell('A' . $row)->getValue()))) {
                continue;
            }

            // Проверяем ячейку на наличие серого фона и жирного шрифта
            $cellStyle = $sheet->getStyle('A' . $row);
            $font = $cellStyle->getFont();
            $fill = $cellStyle->getFill();
            $isCategory = $font->getBold() && $fill->getStartColor()->getARGB() === 'FFBFBFBF';

            // Если ячейка является категорией, то сохраняем её в массив
            if ($isCategory) {
                $categoryName = $sheet->getCell('A' . $row)->getValue();
                $data[$categoryName] = [];
            }
            // Иначе, если ячейка является продуктом, то сохраняем её в массив
            else {
                $productName = $sheet->getCell('A' . $row)->getValue();
                $productData = [];

                // Проходимся по всем месяцам и сохраняем бюджет для каждого месяца
                for ($col = 2; $col <= $sheet->getHighestColumn(); $col++) {
                    $month = $sheet->getCellByColumnAndRow($col, 3)->getValue();
                    $budget = $sheet->getCellByColumnAndRow($col, $row)->getValue();

                    $productData[$month] = $budget;
                }

                $data[array_key_last($data)][$productName] = $productData;
            }
        }


        print_r($data);
    }

}


//// Если текущая строка это строка COOP, то прерываем итерацию
//if ($row->getRowIndex() == 109) {
//    break;
//}


//
//
//print_r("qwr");
//
//exit();
//
//// загружаем таблицу из файла
//$reader = IOFactory::createReader('Xlsx');
//$spreadsheet = $reader->load($filename);
//
//// получаем нужный лист
//$worksheet = $spreadsheet->getSheetByName('MA');
//
//$highestRow = $worksheet->getHighestRow();
//$highestColumn = $worksheet->getHighestColumn();
//
//
//
//for ($row = 1; $row <= 14; ++$row) {
//    for ($col = 'A'; $col <= 'N'; ++$col) {
//
//        $cellValue = $worksheet->getCell($col . $row)->getValue();
//        print_r('col ' . $col ."  ".'row  '. $row ."  ". 'cellValue  '. $cellValue. "\n");
////        echo "{$cellValue}\t";
//    }
//    echo "\n";
//}

//if (file_exists($fName . '.xlsx') || file_exists('~$' . $fName . '.xlsx')) {
//    unlink($fName . '.xlsx');
//    unlink('~$' . $fName . '.xlsx');
//}

