<?php

final class PhabricatorExcelExportFormat
  extends PhabricatorExportFormat {

  const EXPORTKEY = 'excel';

  private $workbook;
  private $sheet;
  private $rowCursor;

  public function getExportFormatName() {
    return pht('Excel (.xlsx)');
  }

  public function isExportFormatEnabled() {
    return true;
  }

  public function getFileExtension() {
    return 'xlsx';
  }

  public function getMIMEContentType() {
    return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  }

  /**
   * @phutil-external-symbol class PHPExcel_Cell_DataType
   */
  public function addHeaders(array $fields) {
    $sheet = $this->getSheet();

    $header_format = array(
      'font'  => array(
        'bold' => true,
      ),
    );

    $row = 1;
    $col = 0;
    foreach ($fields as $field) {
      $cell_value = $field->getLabel();

      $cell_name = $this->getCellName($col, $row);

      $cell = $sheet->setCellValue(
        $cell_name,
        $cell_value,
        $return_cell = true);

      $sheet->getStyle($cell_name)->applyFromArray($header_format);
      $cell->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);

      $width = $field->getCharacterWidth();
      if ($width !== null) {
        $col_name = $this->getCellName($col);
        $sheet->getColumnDimension($col_name)
          ->setWidth($width);
      }

      $col++;
    }
  }

  public function addObject($object, array $fields, array $map) {
    $sheet = $this->getSheet();

    $col = 0;
    foreach ($fields as $key => $field) {
      $cell_value = $map[$key];
      $cell_value = $field->getPHPExcelValue($cell_value);

      $cell_name = $this->getCellName($col, $this->rowCursor);

      $cell = $sheet->setCellValue(
        $cell_name,
        $cell_value,
        $return_cell = true);

      $style = $sheet->getStyle($cell_name);
      $field->formatPHPExcelCell($cell, $style);

      $col++;
    }

    $this->rowCursor++;
  }

  /**
   * @phutil-external-symbol class PHPExcel_IOFactory
   */
  public function newFileData() {
    $workbook = $this->getWorkbook();
    $writer = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');

    ob_start();
    $writer->save('php://output');
    $data = ob_get_clean();

    return $data;
  }

  private function getWorkbook() {
    if (!$this->workbook) {
      $this->workbook = $this->newWorkbook();
    }
    return $this->workbook;
  }

  /**
   * @phutil-external-symbol class PHPExcel
   */
  private function newWorkbook() {
    include_once 'PHPExcel.php';
    return new PHPExcel();
  }

  private function getSheet() {
    if (!$this->sheet) {
      $workbook = $this->getWorkbook();

      $sheet = $workbook->setActiveSheetIndex(0);
      $sheet->setTitle($this->getTitle());

      $this->sheet = $sheet;

      // The row cursor starts on the second row, after the header row.
      $this->rowCursor = 2;
    }

    return $this->sheet;
  }

  private function getCellName($col, $row = null) {
    $col_name = chr(ord('A') + $col);

    if ($row === null) {
      return $col_name;
    }

    return $col_name.$row;
  }

}
