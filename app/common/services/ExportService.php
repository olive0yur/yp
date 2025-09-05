<?php

namespace app\common\services;

use app\common\repositories\system\ExportFileRepository;
use think\exception\ValidateException;
use think\facade\View;

class ExportService
{
    /**
     * 导出excel
     *
     * @param string $filePath 存储目录
     * @param array $title 内容标题
     * @param array $data 内容数据
     * @param string $fileName 文件名
     * @param array|string $sheetTitle 表格名
     * @param array $colStyle 列样式
     * @param array $rowStyle 行样式
     * @return string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function exportExcel($filePath, $title, $data, $fileName = '', $sheetTitle = '', $colStyle = [], $rowStyle = [])
    {
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
        $cellLetter = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

        $objExcel = new \PHPExcel();
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $saveFilePath = $filePath . '/' . $fileName;

        if (!is_array($sheetTitle)) {
            $sheetTitle = [$sheetTitle];
            $data = [$data];
            $title = [$title];
            $colStyle = [$colStyle];
            $rowStyle = [$rowStyle];
        }
        $num = count($cellLetter);
        if ($num < count($title[0])) {
            for ($i = 0; $i < $num; $i++) {
                for ($j = 0; $j < $num; $j++) {
                    $cellLetter[] = $cellLetter[$i] . $cellLetter[$j];
                }
            }
        }
        foreach ($sheetTitle as $k2 => $v2) {
            $objExcel->createSheet($k2);
            $objExcel->setActiveSheetIndex($k2);
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setTitle($v2); //设置excel的标题

            // 设置内容标题
            foreach (($title[$k2] ?? []) as $k => $v) {
                $objActSheet->setCellValue(($cellLetter[$k] ?? '') . '1', $v);
            }

            // 设置内容
            $baseRow = 2; //数据从N-1行开始往下输出 这里是避免头信息被覆盖
            foreach (($data[$k2] ?? []) as $key => $value) {
                $i = $baseRow + $key;
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        if (is_numeric($v['text']) && strlen($v['text']) > 10) {
                            $objActSheet->setCellValueExplicit(($cellLetter[$k] ?? '') . $i, $v['text'], \PHPExcel_Cell_DataType::TYPE_STRING);
                        } else {
                            $objActSheet->setCellValue(($cellLetter[$k] ?? '') . $i, $v['text']);
                        }
                        // 设置字体样式
                        if (isset($v['font'])) {
                            $objActSheet->getStyle(($cellLetter[$k] ?? '') . $i)->applyFromArray(['font' => $v['font']]);
                        }
                        // 设置背景色
                        if (isset($v['bgcolor']) && $v['bgcolor']) {
                            $objActSheet->getStyle(($cellLetter[$k] ?? '') . $i)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                            $objActSheet->getStyle(($cellLetter[$k] ?? '') . $i)->getFill()->getStartColor()->setARGB('FF' . $v['bgcolor']);
                        }
                    } else {
                        if (is_numeric($v) && strlen($v) > 10) {
                            $objActSheet->setCellValueExplicit(($cellLetter[$k] ?? '') . $i, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
                        } else {
                            $objActSheet->setCellValue(($cellLetter[$k] ?? '') . $i, $v);
                        }
                    }
                }
            }

            // 设置列样式
            foreach (($colStyle[$k2] ?? []) as $k => $v) {
                $objActSheet->getColumnDimension(($cellLetter[$k] ?? ''))->setWidth($v['width']);
            }
            // 设置行样式
            foreach (($rowStyle[$k2] ?? []) as $k => $v) {
                $objActSheet->getRowDimension($k)->setRowHeight($v['height']);
            }
        }
        $objWriter->save($saveFilePath);

        return $saveFilePath;
    }

    /**
     * 根据导出文件ID生成文件
     *
     * @param $fileId
     * @param $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function generateFileByFileId($fileId, $data)
    {
        /** @var ExportFileRepository $exportFileRepository */
        $exportFileRepository = app()->make(ExportFileRepository::class);
        $info = $exportFileRepository->getSearch([
            'status' => 0
        ])->where('id', $fileId)
            ->find();
        if ($info) {
            $res = null;
            $failDesc = '';
            try {
            } catch (ValidateException $e) {
                $failDesc = $e->getMessage();
            } catch (\Exception $e) {
                exception_log('导出文件生成失败', $e);
                $failDesc = '系统错误';
            }
            if ($res !== null) {
                return $exportFileRepository->update($info['id'], [
                    'status' => 1,
                    'file_path' => $res
                ]);
            } else {
                return $exportFileRepository->update($info['id'], [
                    'status' => 2,
                    'fail_desc' => $failDesc
                ]);
            }
        }
        return false;
    }

}