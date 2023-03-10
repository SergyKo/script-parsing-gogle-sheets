<?php

namespace app\commands;
require 'vendor/autoload.php';

use app\models\Budget;
use app\models\Category;
use app\models\Product;
use app\models\Sheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\RowCellIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use yii\base\ErrorException;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Exception;


class ParserController extends Controller
{
    protected $endRow = 0;
    protected $startRow = 3;
    protected $arrayProducts = [];
    protected $arrayMonths = [];

    /**
     * @param string $message
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function actionIndex($message = 'Parser start!')
    {
        print_r("\n" . __FUNCTION__ . "\n");

        echo $message . "\n";
        $filename = 'table.xlsx';
        $workSheetName = 'MA';

        $url = $_ENV['GOOGLE_SHEET_URL'];

        try {
            if (!$this->downloadXlsx($url, $filename)) {
                print_r("Файл не загружен!");
                exit();
            }
            print_r("Файл успешно загружен!");
        } catch (ErrorException $e) {
            print_r("Ошибка загрузки файла! Или закройте таблицу!");
            exit();
        }

        $this->parseXlsxFile($filename, $workSheetName);

        return ExitCode::OK;
    }

    /** Загрузка таьличного файла в локальное пространство
     * @param $url
     * @param $filename
     * @return bool
     */
    private function downloadXlsx($url, $filename)
    {
        print_r("\n" . __FUNCTION__ . "\n");

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
     * @return bool
     */
    private function saveSheetsNames($sheetNames)
    {
        print_r("\n" . __FUNCTION__ . "\n");
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
        return true;
    }

    /** Парсинг файла
     * @param $filename
     * @param $workSheetName
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function parseXlsxFile($filename, $workSheetName)
    {
        print_r("\n" . __FUNCTION__ . "\n");
        print_r("Разбираем файл." . "\n");

        // загружаем таблицу из файла
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($filename);

        $sheetNames = $spreadsheet->getSheetNames();

        // запись имен листов
        if (!$this->saveSheetsNames($sheetNames)) {
            print_r("При записи имен листов произошел сбой");
            exit();
        }

        // запись категорий и продуктов
        $worksheet = $spreadsheet->getSheetByName($workSheetName);

        if (!$this->saveCategoryAndProduct($worksheet)) {
            print_r("При записи категорий и продуктов произошел сбой");
            exit();
        }

        // запись боджета
        $this->readTableBudjet($worksheet);
        print_r("Работа завершена!");
        exit();

    }

    /** Запись в БД Продуктов и Категорий
     * @param Worksheet $spreadsheet
     * @return bool
     */
    private function saveCategoryAndProduct(Worksheet $spreadsheet)
    {
        print_r("\n" . __FUNCTION__ . "\n");

        // Поиск категорий и продуктов
        $id = 0;
        foreach ($spreadsheet->getRowIterator() as $row) {

            $rowIndex = $row->getRowIndex();
            if ($rowIndex < $this->startRow) {
                continue;
            }

            $cell = $spreadsheet->getCell('A' . $rowIndex);
            $style = $cell->getStyle();

            if (strlen($cell->getValue()) < 1) {
                continue;
            }

            if ($cell->getValue() === 'CO-OP') {
                $this->endRow = $rowIndex;
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

                    } else {
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
                $this->arrayProducts += ["$rowIndex" => "$productName"];
                try {
                    if (!$model->findOne(['name' => $model->name])) {
                        $model->category_id = $id;
                        $model->save();

                    }
                } catch (Exception $e) {
                    print_r("Ошибка с БД при записи Product! " . "\n" . $e->getMessage());
                    exit();
                }
            }

        }
        return true;
    }

    private function generatorRows(Worksheet $spreadsheet)
    {
        print_r("\n" . __FUNCTION__ . "\n");

        for ($i = $this->startRow; $i <= $this->endRow; $i++) {
            yield $spreadsheet->getRowIterator($i, $i)->current()->getCellIterator();
        }
    }

    /** Подготовка массива с месяцами в форме [row => months]
     * @param Worksheet $worksheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function preapareMonthsArr(Worksheet $worksheet)
    {
        print_r("\n" . __FUNCTION__ . "\n");

        /* @var $cellIterator RowCellIterator */
        foreach ($this->generatorRows($worksheet) as $cellIterator) {
            foreach ($cellIterator as $cell) {

                $column = $cell->getColumn();
                $row = $cell->getRow();
                $value = $cell->getValue();

                if ($row == $this->startRow) {
                    switch ($column) {
                        case 'B':
                        case 'C':
                        case 'D':
                        case 'E':
                        case 'F':
                        case 'G':
                        case 'H':
                        case 'I':
                        case 'J':
                        case 'K':
                        case 'L':
                        case 'M':
                        case 'N':
                            $this->arrayMonths += ["$column" => "$value"];
                    }
                }
            }
        }
    }

    /** Работа с таблицей бюджет
     * @param Worksheet $worksheet
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function readTableBudjet(Worksheet $worksheet)
    {
        print_r("\n" . __FUNCTION__ . "\n");

        $this->preapareMonthsArr($worksheet);

        /* @var $cellIterator RowCellIterator */
        foreach ($this->generatorRows($worksheet) as $cellIterator) {
            foreach ($cellIterator as $cell) {

                $column = $cell->getColumn();
                $row = $cell->getRow();
                $cV = $cell->getCalculatedValue();
                if (strlen($cV) < 1) {
                    $cV = 0;
                }

                if ($row >= $this->startRow && $column >= 'B' && $column <= 'M') {

                    $productName = $this->arrayProducts[$row];
                    $month = strtolower($this->arrayMonths[$column]);

                    // проверка на наличие в БД
                    $modelProduct = new Product();
                    $modelProduct->name = $productName;

                    try {
                        $products = $modelProduct->find()->select(['id', 'category_id'])->where(['name' => $productName])->one();
                        $productId = $products->id;
                        print_r("\n" . ' $productId' . $productId . "\n" . "\n" . "\n");

                        $categoryId = $products->category_id;

                    } catch (Exception $e) {
                        print_r("Ошибка с БД! " . "\n" . $e->getMessage());
                        exit();
                    }

                    try {
                        $modelBudget = new Budget();
                        $modelBudget->$month = $cV;

                        $modelBudget = Budget::find()->where(['category_id' => $categoryId, 'product_id' => $productId])->one();

                        if (!$modelBudget) {
                            // Если запись не найдена, создаем новый объект модели
                            $modelBudget = new Budget();
                            $modelBudget->category_id = $categoryId;
                            $modelBudget->product_id = $productId;
                        }

                        // Обновляем поле $month и сохраняем запись
                        $modelBudget->$month = $cV;
                        $modelBudget->save();
//
//                        print_r('$modelBudget->$month ' . $month . "\n");
//                        print_r('$modelBudget->category_id  ' . $categoryId . "\n");
//                        print_r('$modelBudget->product_id  ' . $productId . "\n");

                    } catch (Exception $e) {
                        print_r("Ошибка с БД при внесении данных в Budget! " . "\n" . $e->getMessage());
                        exit();
                    }
                }
            }
        }

        // пересчитаем значения в total раз он там есть
        $budgetItems = Budget::find()->all();

        try {
            foreach ($budgetItems as $budgetItem) {
                $total = 0;
                $total += $budgetItem->january +
                    $budgetItem->february +
                    $budgetItem->march +
                    $budgetItem->april +
                    $budgetItem->may +
                    $budgetItem->june +
                    $budgetItem->july +
                    $budgetItem->august +
                    $budgetItem->september +
                    $budgetItem->october +
                    $budgetItem->november +
                    $budgetItem->december;
                $budgetItem->total = $total;
                $budgetItem->save();
            }
        } catch (Exception $e) {
            print_r("Ошибка с БД при пересчете Total! " . "\n" . $e->getMessage());
            exit();
        }
    }
}
