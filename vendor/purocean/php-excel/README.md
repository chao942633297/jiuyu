# php-excel
    方便快捷的 Excel 导出导入类，就俩静态方法

## 简单上手

### 导出
```php
use YExcel\Excel;

// 从数据库之类得到数据，数组或可遍历的生成器，支持使用闭包灵活配置每一个单元格
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

$template = 'template.xlsx'; // 模板文件，如果不提供则新建一个 xlsx 文件

// 给浏览器发送下载头
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename=sites_export.xlsx');
header('Cache-Control: max-age=0');

// 默认跳过第一行表头
// 更多使用请查看 src\Excel 注释
Excel::put('php://output', $data, ['tplFile' => $template]);

// 多个 sheet 导出
Excel::put('php://output', [1 => $data], [multiSheet => [1 => 'test11']]);
```

### 导入
```php
use YExcel\Excel;

// 默认跳过第一行表头
// 更多使用请查看 src\Excel 注释
foreach (Excel::get('file.xlsx') as $row) {
    var_dump($row);
    // 写入数据库之类
}
```

## 测试
```bash
cd test
php test.php
```

