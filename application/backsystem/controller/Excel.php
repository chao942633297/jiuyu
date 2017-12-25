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

}