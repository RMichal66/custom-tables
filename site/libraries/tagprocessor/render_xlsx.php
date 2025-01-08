<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;

defined('_JEXEC') or die();

trait render_xlsx
{

	protected static function get_CatalogTable_XLSX(CT &$ct, $fields)
	{
		$filename = CTMiscHelper::makeNewFileName($ct->Params->pageTitle, 'xlsx');

		if (ob_get_contents()) ob_end_clean();
		/** Include PHPExcel */
		require_once JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'thirdparty' . DIRECTORY_SEPARATOR . 'phpexcel' . DIRECTORY_SEPARATOR . 'Classes/PHPExcel.php';


		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		$fields = str_replace("\n", '', $fields);
		$fields = str_replace("\r", '', $fields);

		$fieldArray = CTMiscHelper::csv_explode(',', $fields, '"', true);

		$sheet_name = $ct->Params->pageTitle;

		// Set document properties
		$objPHPExcel->getProperties()->setCreator("CustomTables")
			->setLastModifiedBy("CustomTables")
			->setTitle($sheet_name)
			->setSubject('')
			->setDescription('')
			->setKeywords("")
			->setCategory("");

		$wizard = new PHPExcel_Helper_HTML;
		if (ob_get_contents()) ob_end_clean();

		$allRecords = array();
		$records = array();
		$column = 0;
		foreach ($fieldArray as $field) {

			$fieldPair = CTMiscHelper::csv_explode(':', $field, '"', false);

			$pos = self::num2alpha($column) . '1';

			$value = $fieldPair[0];

			$value = CTMiscHelper::strip_tags_content($value, '<p><br><i><u><b><span>', FALSE);

			$value = common::ctStripTags($value);
			self::simpleHTMLCorrections($value);

			$richText = $value;
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($pos, $richText);

			// Output Rows

			$recordLine = str_replace('|(', '{', $fieldPair[1]);
			$recordLine = str_replace(')|', '}', $recordLine);
			$recordLine = str_replace('\'', '"', $recordLine);
			$recordLine = str_replace('&&&&quote&&&&', '"', $recordLine);

			$LayoutProc = new LayoutProcessor($ct);
			$LayoutProc->layout = $recordLine;
			$records = array();

			foreach ($ct->Records as $row) {
				$htmlresult = $LayoutProc->fillLayout($row);
				$htmlresult = CTMiscHelper::strip_tags_content($htmlresult, '<a><p><br><i><u><b><span>', FALSE);
				$htmlresult = common::ctStripTags($htmlresult);//, '<center><p><br><i><u><b><span>');
				$records[] = $htmlresult;
			}
			$allRecords[] = $records;
			$column++;
		}

		for ($r = count($records) - 1; $r >= 0; $r--) {
			for ($c = count($allRecords) - 1; $c >= 0; $c--) {
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($c, $r + 2, $allRecords[$c][$r]);
			}
		}

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		// Redirect output to a client’s web browser (Excel2007)

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');
		header('Content-Type: text/html; charset=utf-8');
		// If you're serving to IE over SSL, then the following may be needed
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' . common::currentDate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0


		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		die;//clean exit
	}

	protected static function num2alpha($n): string
	{
		for ($r = ""; $n >= 0; $n = intval($n / 26) - 1)
			$r = chr($n % 26 + 0x41) . $r;
		return $r;
	}

	protected static function simpleHTMLCorrections(&$text): void
	{
		$text = str_ireplace('<' . 'center' . '>', "<p style='text-align: center'>", $text);
		$text = str_ireplace('</' . 'center' . '>', '</p>', $text);
	}
}
