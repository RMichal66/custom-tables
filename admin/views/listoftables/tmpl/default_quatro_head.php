<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file access');
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\String\PunycodeHelper;

HTMLHelper::_('behavior.multiselect');

?>
<tr>
	<?php if ($this->canEdit && $this->canState): ?>
		<th width="20" class="nowrap center">
			<?php echo JHtml::_('grid.checkall'); ?>
		</th>
	<?php endif; ?>
	
	<th scope="col">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_TABLENAME_LABEL', 'a.tablename', $this->listDirn, $this->listOrder);
		//a.tablename but not tablename is important to make the sort by box have the same selection as pressed on table head field name
		?>
	</th>
	
	<th scope="col">
		<?php echo JText::_('COM_CUSTOMTABLES_TABLES_TABLETITLE_LABEL'); ?>
	</th>
	
	<th scope="col" class="text-center">
			<?php echo JText::_('COM_CUSTOMTABLES_TABLES_FIELDS_LABEL'); ?>
	</th>
	<th scope="col" class="text-center">
			<?php echo JText::_('COM_CUSTOMTABLES_TABLES_RECORDS_LABEL'); ?>
	</th>

	<th scope="col">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_TABLECATEGORY_LABEL', 'a.tablecategory', $this->listDirn, $this->listOrder); ?>
	</th>
	
	<th scope="col" class="text-center d-none d-md-table-cell" >
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
	</th>
	
	<th scope="col" class="w-12 d-none d-xl-table-cell" >
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_TABLES_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
	</th>
</tr>
