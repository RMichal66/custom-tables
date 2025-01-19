<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

if (!defined('CUSTOMTABLES_LIBRARIES_PATH'))
	define('CUSTOMTABLES_LIBRARIES_PATH', JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_customtables' . DIRECTORY_SEPARATOR . 'libraries');

$version = new Version();
if (!defined('CUSTOMTABLES_JOOMLA_MIN_4')) {
	if (version_compare($version->getShortVersion(), '4.0', '>='))
		define('CUSTOMTABLES_JOOMLA_MIN_4', true);
	else
		define('CUSTOMTABLES_JOOMLA_MIN_4', false);
}

trait JFormFieldCTTableCommon
{
	protected static function getOptionList(string $returnValue = 'id'): array
	{
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ct-common-joomla.php');
		require_once(CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'ct-database-joomla.php');
		$whereClause = new MySQLWhereClause();

		$categoryId = common::inputGetInt('categoryid');
		if ($categoryId !== null)
			$whereClause->addCondition('tablecategory', $categoryId);

		$whereClause->addCondition('published', 1);

		$tables = database::loadObjectList('#__customtables_tables',
			['id', 'tablename'], $whereClause, 'tablename');

		$options = ['' => ' - ' . Text::_('COM_CUSTOMTABLES_SELECT')];

		if ($tables) {
			foreach ($tables as $table) {
				if ($returnValue == 'id')
					$options[] = HTMLHelper::_('select.option', $table->id, $table->tablename);
				elseif ($returnValue == 'tablename')
					$options[] = HTMLHelper::_('select.option', $table->tablename, $table->tablename);
			}
		}
		return $options;
	}
}

if (!CUSTOMTABLES_JOOMLA_MIN_4) {

	JFormHelper::loadFieldClass('list');

	class JFormFieldCTTable extends JFormFieldList
	{
		use JFormFieldCTTableCommon;

		protected $type = 'CTTable';

		protected function getOptions()
		{
			$returnValue = $this->element['returnvalue'] ?? 'id';
			return self::getOptionList($returnValue);
		}
	}
} else {
	class JFormFieldCTTable extends FormField
	{
		use JFormFieldCTTableCommon;

		public $type = 'CTTable';
		protected $layout = 'joomla.form.field.list'; //Needed for Joomla 5

		protected function getInput()
		{
			$returnValue = $this->element['returnvalue'] ?? 'id';
			$data = $this->getLayoutData();
			$data['options'] = self::getOptionList($returnValue);
			return $this->getRenderer($this->layout)->render($data);
		}
	}
}