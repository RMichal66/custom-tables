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
defined('_JEXEC') or die();

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
	. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php');

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
	. DIRECTORY_SEPARATOR . 'value.php');

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
	. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'image.php');

require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
	. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'imagegallery.php');

$log_path = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR;

if (file_exists($log_path . 'log.php'))
	require_once($log_path . 'log.php');

defined('_JEXEC') or die();

use CustomTables\Field;
use CustomTables\InputBox_filebox;
use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\CTUser;
use CustomTables\Value_file;
use CustomTables\Value_image;
use CustomTables\Value_imagegallery;

class tagProcessor_Value
{
	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	public static function processValues(CT &$ct, string &$pageLayout, ?array $row, string $tag_chars = '[]'): void
	{
		$items_to_replace = array();
		$isGalleryLoaded = array();
		$getGalleryRows = array();
		$isFileBoxLoaded = array();
		$getFileBoxRows = array();

		if (!$ct->isRecordNull($row) and !is_null($ct->Table->fields)) {
			foreach ($ct->Table->fields as $fieldRow) {
				$field = new Field($ct, $fieldRow, $row);

				$replaceItCode = md5(common::generateRandomString() . ($row[$ct->Table->realidfieldname] ?? '') . $field->fieldname);

				$temp_items_to_replace = tagProcessor_Value::processPureValues($ct, $pageLayout, $row, $isGalleryLoaded, $getGalleryRows, $isFileBoxLoaded, $getFileBoxRows, $tag_chars);
				if (count($temp_items_to_replace) != 0)
					$items_to_replace = array_merge($items_to_replace, $temp_items_to_replace);

				$temp_items_to_replace = tagProcessor_Value::processEditValues($ct, $pageLayout, $row, $tag_chars);
				if (count($temp_items_to_replace) != 0)
					$items_to_replace = array_merge($items_to_replace, $temp_items_to_replace);

				$ValueOptions = array();
				$ValueList = CTMiscHelper::getListToReplace($field->fieldname, $ValueOptions, $pageLayout, $tag_chars);

				$fieldType = $field->type;
				$fieldname = $field->fieldname;

				if ($fieldType == 'imagegallery') {
					if (!isset($isGalleryLoaded[$fieldname]) or !$isGalleryLoaded[$fieldname]) {
						$isGalleryLoaded[$fieldname] = true;
						$r = Value_imagegallery::getGalleryRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
						$getGalleryRows[$fieldname] = $r;
					}

					if (count($getGalleryRows[$fieldname]) == 0)
						$isEmpty = true;
					else
						$isEmpty = false;

				} elseif ($fieldType == 'filebox') {
					require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
						. 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . 'filebox.php');

					$isFileBoxLoaded[$fieldname] = true;
					$getFileBoxRows[$fieldname] = InputBox_filebox::getFileBoxRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);

					if (count($getFileBoxRows[$fieldname]) == 0)
						$isEmpty = true;
					else
						$isEmpty = false;

				} else {
					//isEmpty
					$isEmpty = tagProcessor_Value::isEmpty($row[$field->realfieldname], $field);
				}

				// IF
				tagProcessor_If::IFStatment('[_if:' . $field->fieldname . ']', '[_endif:' . $field->fieldname . ']', $pageLayout, $isEmpty);

				// IF NOT
				tagProcessor_If::IFStatment('[_ifnot:' . $field->fieldname . ']', '[_endifnot:' . $field->fieldname . ']', $pageLayout, !$isEmpty);

				if ($isEmpty) {
					foreach ($ValueList as $ValueListItem)
						$pageLayout = str_replace($ValueListItem, '', $pageLayout);
				} else {
					$i = 0;
					foreach ($ValueOptions as $ValueOption) {
						$value_option_list = CTMiscHelper::csv_explode(',', $ValueOption);

						$vlu = tagProcessor_Value::getValueByType($ct, $fieldRow, $row, $value_option_list);

						//this is temporary replace string - part of the mechanism to avoid getting values of another fields
						$new_replaceitecode = $replaceItCode . str_pad($field->id, 9, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT);

						$items_to_replace[] = array($new_replaceitecode, $vlu);
						$pageLayout = str_replace($ValueList[$i], $new_replaceitecode, $pageLayout);

						$i++;
					}
				}
				//process field names

			}
		}//isset

		//replace temporary items with values
		foreach ($items_to_replace as $item)
			$pageLayout = str_replace($item[0], $item[1], $pageLayout);

	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	public static function processPureValues(CT &$ct, string &$htmlresult, ?array $row, array &$isGalleryLoaded, array &$getGalleryRows
		, array                                 &$isFileBoxLoaded, array &$getFileBoxRows, string $tag_chars = '[]'): array
	{
		$listing_id = ($row[$ct->Table->realidfieldname] ?? 0);

		$items_to_replace = array();

		$pureValueOptions = array();
		$pureValueList = CTMiscHelper::getListToReplace('_value', $pureValueOptions, $htmlresult, $tag_chars);
		$p = 0;
		foreach ($pureValueOptions as $pureValueOption) {
			$pureValueOptionArr = explode(':', $pureValueOption);
			if (count($pureValueOptionArr) == 1)
				$pureValueOptionArr[1] = '';

			$i = 0;
			foreach ($ct->Table->fields as $fieldRow) {
				$field = new Field($ct, $fieldRow, $row);

				$replaceItCode = md5(common::generateRandomString() . ($row[$ct->Table->realidfieldname] ?? '') . $field->fieldname);

				if ($pureValueOptionArr[0] == $field->fieldname) {

					$fieldType = $field->type;
					$fieldname = $field->fieldname;

					if ($fieldType == 'imagegallery') {
						if (count($isGalleryLoaded) > 0) {
							if (!isset($isGalleryLoaded[$fieldname]) or !$isGalleryLoaded[$fieldname]) {
								//load if not loaded
								$isGalleryLoaded[$fieldname] = true;
								$getGalleryRows[$fieldname] = Value_imagegallery::getGalleryRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
							}
						} else {
							//load if not loaded
							$isGalleryLoaded[$fieldname] = true;
							$getGalleryRows[$fieldname] = Value_imagegallery::getGalleryRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
						}

						if (count($getGalleryRows[$fieldname]) == 0)
							$isEmpty = true;
						else
							$isEmpty = false;
					} elseif ($fieldType == 'filebox') {

						require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
							. 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . 'filebox.php');

						if (count($isFileBoxLoaded) > 0) {
							if (!$isFileBoxLoaded[$fieldname]) {
								//load if not loaded
								$isFileBoxLoaded[$fieldname] = true;

								$getFileBoxRows[$fieldname] = InputBox_filebox::getFileBoxRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
							}

						} else {
							//load if not loaded
							$isFileBoxLoaded[$fieldname] = true;
							$getFileBoxRows[$fieldname] = InputBox_filebox::getFileBoxRows($ct->Table->tablename, $fieldname, $row[$ct->Table->realidfieldname]);
						}

						if (count($getFileBoxRows[$fieldname]) == 0)
							$isEmpty = true;
						else
							$isEmpty = false;
					} elseif ($fieldType == 'checkbox') {
						$isEmpty = false;
					} else {
						$isEmpty = tagProcessor_Value::isEmpty($row[$field->realfieldname], $field);
					}

					$ifname = '[_if:_value:' . $field->fieldname . ']';
					$endifname = '[_endif:_value:' . $field->fieldname . ']';

					if ($isEmpty) {
						do {
							$textlength = strlen($htmlresult);

							$startif_ = strpos($htmlresult, $ifname);
							if ($startif_ === false)
								break;
							else {

								$endif_ = strpos($htmlresult, $endifname);
								if (!($endif_ === false)) {
									$p = $endif_ + strlen($endifname);
									$htmlresult = substr($htmlresult, 0, $startif_) . substr($htmlresult, $p);
								}
							}

						} while (true);

						$htmlresult = str_replace($pureValueList[$p], '', $htmlresult);
					} else {
						$htmlresult = str_replace($ifname, '', $htmlresult);
						$htmlresult = str_replace($endifname, '', $htmlresult);

						$vlu = '';

						if ($fieldType == 'image') {
							$imagesrc = '';
							$imagetag = '';

							$new_array = array();

							if (count($pureValueOptionArr) > 1) {
								for ($i = 1; $i < count($pureValueOptionArr); $i++)
									$new_array[] = $pureValueOptionArr[$i];
							}

							$image = Value_image::getImageSRCLayoutView($new_array, $row[$field->realfieldname], $field->params);
							if ($image !== null)
								$vlu = $image['src'];

						} elseif ($fieldType == 'imagegallery') {

							$new_array = array();

							if (count($pureValueOptionArr) > 1) {
								for ($i = 1; $i < count($pureValueOptionArr); $i++)
									$new_array[] = $pureValueOptionArr[$i];
							}

							if (count($new_array) > 0) {
								$option = $new_array[0];
								$imageSRCList = Value_imagegallery::getImageGallerySRC($getGalleryRows[$fieldname],
									$option, $fieldname, $field->params, $ct->Table->tableid);

								$vlu = implode(',', $imageSRCList);
							}

						} elseif ($fieldType == 'filebox') {

							require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR
								. 'customtables' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'inputbox' . DIRECTORY_SEPARATOR . 'filebox.php');

							$vlu = InputBox_filebox::process($getFileBoxRows[$fieldname], $field, $row[$ct->Table->realidfieldname], ['', 'link', '32', '_blank', ';']);
						} elseif ($fieldType == 'records') {
							$a = explode(",", $row[$field->realfieldname]);
							$b = array();
							foreach ($a as $c) {
								if ($c != "")
									$b[] = $c;
							}
							$vlu = implode(',', $b);
						} elseif ($fieldType == 'file') {
							if (isset($pureValueOptionArr[1]) and $pureValueOptionArr[1] != '') {

								$processor_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR . 'html'
									. DIRECTORY_SEPARATOR . 'value' . DIRECTORY_SEPARATOR . 'file.php';
								require_once($processor_file);

								$new_array = array();

								if (count($pureValueOptionArr) > 1) {
									for ($i = 1; $i < count($pureValueOptionArr); $i++)
										$new_array[] = $pureValueOptionArr[$i];
								}

								$vlu = Value_file::process($row[$field->realfieldname], $field, $new_array, $row[$ct->Table->realidfieldname], 0);
							} else
								$vlu = $row[$field->realfieldname];
						} else {
							$vlu = $row[$field->realfieldname];
						}

						//this is temporary replace string - part of the mechanism to avoid getting values of another fields
						$new_replaceitecode = $replaceItCode . str_pad($field->id, 9, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT);

						$items_to_replace[] = array($new_replaceitecode, $vlu);
						$htmlresult = str_replace($pureValueList[$p], $new_replaceitecode, $htmlresult);
					}
				}
				$i++;
			}
			$p++;
		}

		return $items_to_replace;

	}//function

	public static function isEmpty($rowValue, Field $field): bool
	{
		$fieldType = $field->type;

		if ($fieldType == 'int' or $fieldType == 'user' or $fieldType == 'userid' or $fieldType == 'usergroup') {
			$v = (int)$rowValue;
			if ($v == 0)
				return true;
			else
				return false;
		} elseif ($fieldType == 'float') {
			$v = (float)$rowValue;
			if ($v == 0)
				return true;
			else
				return false;
		} elseif ($fieldType == 'checkbox') {
			$v = (int)$rowValue;
			if ($v == 0)
				return true;
			else
				return false;
		} elseif ($fieldType == 'records' or $fieldType == 'usergroups') {
			if ($rowValue == '' or $rowValue == ',' or $rowValue == ',,')
				return true;
			else
				return false;
		} elseif ($fieldType == 'date') {
			if ($rowValue == '' or $rowValue == '0000-00-00')
				return true;
			else
				return false;
		} elseif ($fieldType == 'time') {
			if ($rowValue == '' or $rowValue == '0')
				return true;
			else
				return false;
		} elseif ($fieldType == 'image') {
			if ($rowValue == '' or $rowValue == '-1' or $rowValue == '0')
				return true;
			else {
				//check if file exists
				$ImageFolderArray = CustomTablesImageMethods::getImageFolder($field->params);
				//$ImageFolder = str_replace('/', DIRECTORY_SEPARATOR, $ImageFolder_);
				$image_prefix = '_esthumb';

				$img = $rowValue;
				if (str_contains($img, '-')) {
					$img = str_replace('-', '', $img);
				}

				$imageFile_ext = 'jpg';
				$imageFile = $ImageFolderArray['path'] . DIRECTORY_SEPARATOR . $image_prefix . '_' . $img . '.' . $imageFile_ext;

				if (file_exists($imageFile))
					return false;
				else
					return true;
			}
		} elseif ($fieldType == 'sqljoin') {
			if ($rowValue == 0)
				return true;
			else
				return false;
		} else {
			if ($rowValue == '')
				return true;
			else
				return false;
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function processEditValues(CT &$ct, string &$htmlResult, ?array $row, string $tag_chars = '[]'): array
	{
		$items_to_replace = array();
		$pureValueOptions = array();
		$pureValueList = CTMiscHelper::getListToReplace('_edit', $pureValueOptions, $htmlResult, $tag_chars);

		if (count($pureValueList) > 0) {
			require_once(JPATH_SITE
				. DIRECTORY_SEPARATOR . 'components'
				. DIRECTORY_SEPARATOR . 'com_customtables'
				. DIRECTORY_SEPARATOR . 'libraries'
				. DIRECTORY_SEPARATOR . 'esinputbox.php');

			$esinputbox = new ESInputBox($ct);
			if ($ct->Params->requiredLabel != '')
				$esinputbox->requiredLabel = $ct->Params->requiredLabel;

			require_once(JPATH_SITE
				. DIRECTORY_SEPARATOR . 'components'
				. DIRECTORY_SEPARATOR . 'com_customtables'
				. DIRECTORY_SEPARATOR . 'libraries'
				. DIRECTORY_SEPARATOR . 'tagprocessor'
				. DIRECTORY_SEPARATOR . 'itemtags.php');

			$edit_userGroup = (int)$ct->Params->editUserGroups;
			$isEditable = CTUser::checkIfRecordBelongsToUser($ct, $edit_userGroup);
		} else
			$isEditable = false;

		$p = 0;
		foreach ($pureValueOptions as $pureValueOption) {
			$pureValueOptionArr = explode(':', $pureValueOption);

			$style = '';
			if (!isset($pureValueOptionArr[1]))
				$style = ' style="width:auto; !important;border:none !important;box-shadow:none;"';

			$i = 0;
			foreach ($ct->Table->fields as $fieldRow) {
				$replaceItCode = md5(common::generateRandomString() . ($row[$ct->Table->realidfieldname] ?? '') . $fieldRow['fieldname']);

				if ($pureValueOptionArr[0] == $fieldRow['fieldname']) {
					//this is temporary replace string - part of the mechanism to avoid getting values of another fields
					$newReplaceItCode = $replaceItCode . str_pad($fieldRow['id'], 9, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT);

					if ($isEditable) {
						$postfix = '';
						$ajax_prefix = $ct->Table->fieldInputPrefix . $row[$ct->Table->realidfieldname] . '_';//example: com_153_es_fieldname or com_153_ct_fieldname

						$value_option_list = array();
						if (isset($pureValueOptionArr[1]))
							$value_option_list = CTMiscHelper::csv_explode(',', $pureValueOptionArr[1]);

						if ($fieldRow['type'] == 'multilangstring') {
							if (isset($value_option_list[4])) {
								//multilingual field specific language
								foreach ($ct->Languages->LanguageList as $lang) {
									if ($lang->sef == $value_option_list[4]) {
										$postfix = $lang->sef;
										break;
									}
								}
								$newReplaceItCode .= $postfix;
							}
						}

						$onchange = 'ct_UpdateSingleValue(\'' . $ct->Env->WebsiteRoot . '\',' . $ct->Params->ItemId . ',\''
							. $fieldRow['fieldname'] . '\',' . $row[$ct->Table->realidfieldname] . ',\''
							. $postfix . '\',' . (int)$ct->Params->ModuleId . ');';

						$attributes = $style;

						if (isset($value_option_list[1]))
							$value_option_list[1] .= ' ' . $attributes;
						else
							$value_option_list[1] = $attributes;

						$vlu = '<div class="" id="' . $ajax_prefix . $fieldRow['fieldname'] . $postfix . '_div">'
							. $esinputbox->renderFieldBox($fieldRow, $row, $value_option_list, $onchange);
						$vlu .= '</div>';
					} else {
						$vlu = $row[$fieldRow['realfieldname']];
					}

					$items_to_replace[] = array($newReplaceItCode, $vlu);
					$htmlResult = str_replace($pureValueList[$p], $newReplaceItCode, $htmlResult);
				}
				$i++;
			}
			$p++;
		}

		return $items_to_replace;
	}

	/**
	 * @throws Exception
	 * @since 3.0.0
	 */
	static public function getValueByType(CT &$ct, array $fieldRow, ?array $row, array $option_list)
	{
		$valueProcessor = new CustomTables\Value($ct);

		return $valueProcessor->renderValue($fieldRow, $row, $option_list);
	}
}
