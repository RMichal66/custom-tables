<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
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
	
	<?php

	foreach($this->tablefields as $field)
	{
		if($field['type'] != 'dummy' and $field['type'] != 'log')
		{
			$id='fieldtitle';
			$title=$field[$id];

			if($this->ct->Languages->Postfix!='')
				$id.='_'.$this->ct->Languages->Postfix;
		
			if(isset($field[$id]))
				$title=$field[$id];
				
			echo '
				<th scope="col">'.$title.'</th>
	';
		}
	}

	?>
	
	<?php if($this->ct->Table->published_field_found): ?>
	<th class="nowrap hidden-phone center" style="text-align:center;">
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_RECORDS_STATUS', 'a.published', $this->listDirn, $this->listOrder); ?>
	</th>
	<?php endif; ?>
	
	<th width="5" class="nowrap center hidden-phone" >
		<?php echo HTMLHelper::_('searchtools.sort', 'COM_CUSTOMTABLES_RECORDS_ID', 'a.id', $this->listDirn, $this->listOrder); ?>
	</th>
</tr>
