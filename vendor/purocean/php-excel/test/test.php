<?php

require '../vendor/autoload.php';

use YExcel\Excel;

$data = [
    ["a1", "a2", "a3", "a4", "a5", "a6", "a7", "a8", "a9", "a10"],
    ["b1", "b2", "b3", "b4", "b5", "b6", "b7", "b8", "b9", "b10"],
    ["c1", "c2", "c3", "c4", "c5", "c6", "c7", "c8", "c9", "c10"],
    [
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d1");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d2");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d3");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d4");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d5");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d6");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d7");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d8");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d9");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        },
        function ($sheet, $colNum, $rowNum) {
            $sheet->setCellValueByColumnAndRow($colNum, $rowNum, "d10");
            $fill = $sheet->getStyleByColumnAndRow($colNum, $rowNum)->getFill();
            $fill->setFillType('solid');
            $fill->getStartColor()->setARGB('FFFFEB18');
        }
    ],
];

$data2 = [
    1 => $data
];

$generator = function ($data) {
    foreach ($data as $row) {
        yield $row;
    }
};


Excel::put('test1.xlsx', $data, ['skipRow' => 0]);

Excel::put('test2.xlsx', $generator($data), ['tplFile' => './data/template.xlsx', 'skipRow' => 2]);

Excel::put('test3.xlsx', $data, ['skipRow' => 5]);

Excel::put('test3.xls', $data, ['skipRow' => 5, 'type' => 'Excel5']);

Excel::put('test4.xls', $data2, ['skipRow' => 5, 'type' => 'Excel5', 'multiSheet' => true]);

Excel::put('test5.xls', $data2, ['skipRow' => 5, 'type' => 'Excel5', 'multiSheet' => [1 => 'test11']]);

$tmp = [];
foreach (Excel::get('test3.xlsx', ['skipRows' => [1, 2], 'skipBlankRow' => false]) as $row) {
    $tmp[] = $row;
}

echo expectTrue(count($tmp) === count($data) + 3, 'skip blank row 0');

$tmp = [];
foreach (Excel::get('test3.xlsx') as $row) {
    $tmp[] = $row;
}

echo expectTrue(count($tmp) === count($data), 'skip blank row 1');

$tmp = [];
foreach (Excel::get('test3.xls', ['type' => 'Excel5']) as $row) {
    $tmp[] = $row;
}

echo expectTrue(count($tmp) === count($data), 'skip blank row 1 (xls)');

echo "\n";

$tmp = [];
foreach (Excel::get('test4.xls', ['type' => 'Excel5', 'sheetNum' => 1]) as $row) {
    $tmp[] = $row;
}

echo expectTrue(count($tmp) === count($data), 'skip blank row 1 (xls) | sheet index 1');

echo "\n";

$tmp = [];
foreach (Excel::get('test5.xls', ['type' => 'Excel5', 'sheetNum' => 0]) as $row) {
    $tmp[] = $row;
}

echo expectTrue(count($tmp) === 0, 'skip blank row 1 (xls) | sheet index 0');

echo "\n";

foreach (Excel::get('test2.xlsx', ['skipRows' => []]) as $row) {
    echo "\n".implode("\t", $row);
}

echo "\n";

foreach (Excel::get('./data/excel_time_format.xls', ['type' => 'Excel5', 'skipRows' => []]) as $row) {
    echo Excel::formatExcelTime($row[0], 'Y-m-d') . "\t";
    echo Excel::formatExcelTime($row[1], 'Y-m-d H:i:s') . "\t";
    echo Excel::formatExcelTime($row[2]) . "\t";

    echo "\n";

    echo expectTrue(Excel::formatExcelTime($row[2]) === 1501632000, 'excel time format test');
}

//过滤数据前后空格检测
echo "\n";

foreach (Excel::get('./data/excel_data_trim.xls', ['type' => 'Excel5', 'skipRows' => []]) as $row) {

    echo "'" . $row[0] . "'" . "\t";
    echo expectTrue($row[0] === '17A10SCCD0104661', 'data space trim test index 0');
    echo "\n";

    echo "'" . $row[1] . "'" . "\t";
    echo expectTrue($row[1] === '510184500000000428', 'data space trim test index 1');
    echo "\n";

    echo "'" . $row[2] . "'" . "\t";
    echo expectTrue($row[2] === '中文检测', 'data space trim test index 2');
    echo "\n";
}

echo "\n";

function expectTrue($flag, $name)
{
    return "\n".($flag ? '√' : '×').' '.$name;
}
