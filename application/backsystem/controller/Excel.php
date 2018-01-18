<?php
/**
 * Created by PhpStorm.
 * User: ovo
 * Date: 2017/7/12
 * Time: 下午6:56
 */
namespace app\backsystem\controller;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use PHPExcel_Cell_DataType;

class Excel{
    public function toExcel($title,$content,$first){
//        dump($content);exit;
        $PHPExcel = new PHPExcel(); //实例化PHPExcel类，类似于在桌面上新建一个Excel表格
        $PHPSheet = $PHPExcel->getActiveSheet(); //获得当前活动sheet的操作对象
        $PHPSheet->setTitle($title); //给当前活动sheet设置名称
        foreach($first as $k=>$v){
            $PHPSheet->setCellValue($k,$v);
        }

        foreach($content as $k=>$v){
            $v = array_values($v);
            foreach($v as $k1=>$v1){
                $PHPSheet->setCellValue(chr($k1+65).($k+2),$v1);
            }
        }

        $PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel2007');//按照指定格式生成Excel文件，'Excel2007'表示生成2007版本的xlsx，'Excel5'表示生成2003版本Excel文件
        $PHPWriter->save('./uploads/file.xlsx');
    }

    /** 单sheet表格导出   没有每行26个单元格的限制
     * @param $name string 要保存的Excel的名字 $title sheet 名字
     * @param $content 转换为表格的二维数组   标题可以array_unshift插入到$content数组开头
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    function exportExcel($name, $content,$title="sheet1"){
        $objPHPExcel = new PHPExcel();
        //设置表格 属性
        $objPHPExcel->getProperties()->setCreator($name)
                ->setLastModifiedBy($name)
                ->setTitle("Office 2007 XLSX Test Document")
                ->setSubject("Office 2007 XLSX Test Document")
                ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                ->setKeywords("office 2007 openxml php")
                ->setCategory("Test result file");
        //填充数据
        foreach ($content as $key => $row) {
            $num = $key + 1;
            //$row = array_values($row);
            $i=0;
            foreach ($row as $key2 => $value2) {
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).($num), $value2,PHPExcel_Cell_DataType::TYPE_STRING);
                $i++;
            }
        }
        //设置表格并输出
        $objPHPExcel->getActiveSheet()->setTitle($title);
        header('Content-Type: application/vnd.ms-excel');
        // header("Content-Disposition: attachment;filename={$name}.xlsx"); // 高版本
        header("Content-Disposition: attachment;filename={$name}.xls");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public'); // HTTP/1.0
        // $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  //高版本
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

}