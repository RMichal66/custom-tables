<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\database;
use CustomTables\Field;
use CustomTables\FileUtils;
use CustomTables\MySQLWhereClause;

class updateFiles
{
    /**
     * @throws Exception
     * @since 3.2.2
     */
    public static function process(int $tableId): array
    {
        $stepSize = (int)common::inputGetInt('stepsize', 10);
        $startIndex = (int)common::inputGetInt('startindex', 0);

        $old_typeparams = base64_decode(common::inputGetBase64('old_typeparams', ''));
        if ($old_typeparams == '')
            return array('error' => 'old_typeparams not set');

        $old_params = CTMiscHelper::csv_explode(',', $old_typeparams);

        $new_typeparams = base64_decode(common::inputGetBase64('new_typeparams', ''));
        if ($new_typeparams == '')
            return array('error' => 'new_typeparams not set');

        $new_params = CTMiscHelper::csv_explode(',', $new_typeparams);

        $fieldid = (int)common::inputGetInt('fieldid', 0);
        if ($fieldid == 0)
            return array('error' => 'fieldid not set');

        $ct = new CT;
        $ct->getTable($tableId);
        $fieldRow = $ct->Table->getFieldById($fieldid);
        if ($fieldRow === null) {
            return array('error' => 'field id set but field not found');
        } else {
            $count = 0;
            if ($startIndex == 0) {
                $count = updateFiles::countFiles($ct->Table->realtablename, $fieldRow->realfieldname);
                if ($stepSize > $count)
                    $stepSize = $count;
            }

            $status = updateFiles::processFiles($ct, $fieldRow, $old_params, $new_params);
            return array('count' => $count, 'success' => (int)($status === null), 'startindex' => $startIndex, 'stepsize' => $stepSize, 'error' => $status);
        }
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected static function countFiles($realtablename, $realfieldname): int
    {
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition($realfieldname, null, 'NOT NULL');
        $whereClause->addCondition($realfieldname, '', '!=');

        $rows = database::loadAssocList($realtablename, ['COUNT_ROWS'], $whereClause);
        return (int)$rows[0]['record_count'];
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected static function processFiles(CT &$ct, $fieldrow, array $old_params, array $new_params): ?string
    {
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition($fieldrow->realfieldname, null, 'NOT NULL');
        $whereClause->addCondition($fieldrow->realfieldname, '', '!=');

        $rows = database::loadAssocList($ct->Table->realtablename, $ct->Table->selects, $whereClause);

        $old_FileFolder = null;

        foreach ($rows as $file) {
            $field_row_old = (array)$fieldrow;
            $field_row_old['params'] = $old_params;

            $field_old = new Field($ct, $field_row_old, $file);
            $field_old->params = $old_params;
            $field_old->parseParams($file, $field_old->type);

            $old_FileFolder = FileUtils::getOrCreateDirectoryPath($field_old->params[1]);

            $old_FileFolder = str_replace('/', DIRECTORY_SEPARATOR, $old_FileFolder);

            $field_row_new = (array)$fieldrow;

            $field_new = new Field($ct, $field_row_new, $file);
            $field_new->params = $new_params;
            $field_new->parseParams($file, $field_old->type);

            $new_FileFolder = FileUtils::getOrCreateDirectoryPath($field_new->params[1]);

            $new_FileFolder = str_replace('/', DIRECTORY_SEPARATOR, $new_FileFolder);

            $status = updateFiles::processFile($file[$fieldrow->realfieldname], $old_FileFolder, $new_FileFolder);
            //if $status is null then all good, status is a text string with error message if any
            if ($status !== null)
                return $status;
        }

        CTMiscHelper::deleteFolderIfEmpty($old_FileFolder);
        return null;
    }

    protected static function processFile($filename, $old_FileFolder, $new_FileFolder): ?string
    {
        $filepath_old = JPATH_SITE . $old_FileFolder . DIRECTORY_SEPARATOR . $filename;
        $filepath_new = JPATH_SITE . $new_FileFolder . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($filepath_old)) {
            if ($filepath_old != $filepath_new) {
                if (!@rename($filepath_old, $filepath_new))
                    return 'cannot move file to ' . $filepath_new;
            }
        } else
            return 'file "' . $old_FileFolder . DIRECTORY_SEPARATOR . $filename . '" not found';

        return null;
    }
}
