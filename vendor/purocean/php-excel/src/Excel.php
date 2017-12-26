<?php

namespace YExcel;

use \PHPExcel_IOFactory as IOFactory;
use \PHPExcel_Cell as Cell;
use \PHPExcel as PHPExcel;
use Closure;

/**
* 导入导出
*/
class Excel
{
    /**
     * 从 Excel 获取所有行
     *
     * @param string   $file    xlsx 文件路径
     * @param array    $options 可选项，默认为 [
     *                              'highestColumn' => null, // int 列数，为 null 时候自动检测
     *                              'skipRows'      => [1], // array 跳过的行，默认跳过第一行（表头）
     *                              'skipBlankRow'  => true, // bool 是否跳过空白行，默认为 true
     *                              'type'          => 'Excel2007', // string Excel 文件类型，如 Excel2007 或 Excel5
     *                              'sheetNum'      => 0, // int Sheet index
     *                          ]
     *
     * @return generator 可遍历的生成器
     */
    public static function get($file, $options = [])
    {

        $options = array_merge([
            'highestColumn' => null,
            'skipRows'      => [1],
            'skipBlankRow'  => true,
            'type'          => 'Excel2007',
            'sheetNum'      => 0,
        ], $options);

        $objReader = IOFactory::createReader($options['type']);
        $objPHPExcel = $objReader->load($file);
        $sheet = $objPHPExcel->getSheet($options['sheetNum']);
        $highestRow = $sheet->getHighestRow();
        is_null($options['highestColumn']) and $options['highestColumn'] = Cell::columnIndexFromString($sheet->getHighestColumn());

        for ($row = 1; $row <= $highestRow; ++$row) {
            if (in_array($row, $options['skipRows'])) {
                continue;
            }

            $rowData = [];
            for ($col = 0; $col < $options['highestColumn']; $col++) {
                $value = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($col, $row)->getValue();
                $rowData[] = is_null($value) ? '' : trim((string)$value);
            }

            if ($options['skipBlankRow']) {
                if (!array_filter($rowData)) {
                    continue;
                }
            }

            yield $rowData;
        }
    }

    /**
     * 把数据写入一个文件
     *
     * @param string          $file    文件名
     * @param array|generator $data    数据，可以被 foreach 遍历的数据，数组或者生成器，如果多 sheet ，传入二维数组
     * @param array           $options 可选项，默认为 [
     *                                     'tplFile'    = null, // string 模板文件，以哪个模板填写数据，如果不提供则生成空白 xlsx 文件
     *                                     'skipRow'    = 1, // int 跳过表头的行数，默认为 1
     *                                     'type'       = 'Excel2007', // string Excel 文件类型，如 Excel2007 或 Excel5
     *                                     'multiSheet' = false, // array|bool 是否要写入多 sheet，可以传入数组定义 sheet 名称，[0 => 'sheet1', 1 => 'sheet2']
     *                                 ]
     */
    public static function put($file, $data, $options = [])
    {
        $options = array_merge([
            'tplFile'    => null,
            'skipRow'    => 1,
            'type'       => 'Excel2007',
            'multiSheet' => false,
        ], $options);

        if ($options['tplFile']) {
            if (file_exists($options['tplFile'])) {
                $objReader = IOFactory::createReader($options['type']);
                $objPHPExcel = $objReader->load($options['tplFile']);
            } else {
                throw new \Exception("File `{$options['tplFile']}` not exists");
            }
        } else {
            $objPHPExcel = new PHPExcel();
        }

        $write = function ($data) use ($objPHPExcel, $options) {
            if (! is_array($options['multiSheet'])) {
                $options['multiSheet'] = [];
            }

            foreach ($data as $sheetNum => $sheetData) {
                if ($sheetNum > $objPHPExcel->getSheetCount() - 1) {
                    $objPHPExcel->createSheet($sheetNum);
                }

                $objPHPExcel->setActiveSheetIndex($sheetNum);
                $objSheet = $objPHPExcel->getActiveSheet();
                isset($options['multiSheet'][$sheetNum]) and $objSheet->setTitle($options['multiSheet'][$sheetNum]);

                $rowNum = 1;
                foreach ($sheetData as $row) {
                    $colNum = 0;
                    foreach ($row as $val) {
                        if ($val instanceof Closure) {
                            $val($objSheet, $colNum, $rowNum + $options['skipRow']);
                        } else {
                            $objSheet->setCellValueByColumnAndRow(
                                $colNum,
                                $rowNum + $options['skipRow'],
                                $val
                            );
                        }
                        ++$colNum;
                    }
                    ++$rowNum;
                }
            }
        };

        $write($options['multiSheet'] === false ? [$data] : $data);
        $objPHPExcel->setActiveSheetIndex(0);

        IOFactory::createWriter($objPHPExcel, $options['type'])->save($file);
    }

    /**
     * excel特有时间格式转换
     *
     * @param $time excel特有的时间 例如：43100
     * @param string $format 返回格式 * 可选项 默认直接返回时间戳, 可设定date()方法的format类型
     *
     * @return false|long|string
     */
    public static function formatExcelTime($time, $format = '')
    {
        $time = \PHPExcel_Shared_Date::ExcelToPHP($time);
        if ($format) {
            $time = date($format, $time);
        }
        return $time;
    }
}
