<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/layouts/tmpl/edit.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

//----------------

//$wa = $this->document->getWebAssetManager();
//$wa->useScript('keepalive')
	//->useScript('form.validate');
//-----------------	

$document = JFactory::getDocument();
$document->addCustomTag('<link href="'.JURI::root(true).'/administrator/components/com_customtables/css/fieldtypes.css" rel="stylesheet">');
$document->addCustomTag('<link href="'.JURI::root(true).'/administrator/components/com_customtables/css/modal.css" rel="stylesheet">');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/ajax.js"></script>');

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/typeparams.js"></script>');

HTMLHelper::_('behavior.keepalive');

require_once(JPATH_SITE . DIRECTORY_SEPARATOR . 'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR
	.'libraries'.DIRECTORY_SEPARATOR .'customtables'. DIRECTORY_SEPARATOR . 'layouteditor' .DIRECTORY_SEPARATOR.'layouteditor.php');

$onPageLoads=array();
$typeboxid="jform_layouttype";


?>
<script type="text/javascript">
	<?php echo 'all_tables='.$this->getAllTables().';'; ?>
</script>


	<form action="<?php echo JRoute::_('index.php?option=com_customtables&layout=edit&id='.(int) $this->item->id.$this->referral); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
	
		<?php
			$active_tab = 'general';
			if($this->item->layoutcode != '')
				$active_tab = 'layoutcode-tab';
			elseif($this->item->layoutmobile != '')
				$active_tab = 'layoutmobile-tab';
			elseif($this->item->layoutcss != '')
				$active_tab = 'layoutcss-tab';
			elseif($this->item->layoutjs != '')
				$active_tab = 'layoutjs-tab';
			
			echo JHtml::_('bootstrap.startTabSet', 'layoutsTab', array('active' => $active_tab));
		?>
		
		<?php echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'general', JText::_('COM_CUSTOMTABLES_LAYOUTS_GENERAL', true)); ?>
	
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('layoutname'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('layoutname'); ?></div>
				</div>
				
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('layouttype'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('layouttype'); ?></div>
				</div>
				
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('tableid'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('tableid'); ?></div>
				</div>
			</div>
		</div>

		<?php echo JHtml::_('bootstrap.endTab');
		
		echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'layoutcode-tab', JText::_('COM_CUSTOMTABLES_LAYOUTS_HTML', true));
		echo $this->renderTextArea($this->item->layoutcode,'layoutcode',$typeboxid,$onPageLoads);
		echo JHtml::_('bootstrap.endTab');
		
		echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'layoutmobile-tab', JText::_('COM_CUSTOMTABLES_LAYOUTS_HTML_MOBILE', true));
		if($this->ct->Env->advancedtagprocessor)
			echo $this->renderTextArea($this->item->layoutmobile,'layoutmobile',$typeboxid,$onPageLoads);
		else
			echo Text::_('COM_CUSTOMTABLES_AVAILABLE');
		echo JHtml::_('bootstrap.endTab');
		
		echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'layoutcss-tab', JText::_('COM_CUSTOMTABLES_LAYOUTS_CSS', true));
		if($this->ct->Env->advancedtagprocessor)
			echo $this->renderTextArea($this->item->layoutcss,'layoutcss',$typeboxid,$onPageLoads);
		else
			echo Text::_('COM_CUSTOMTABLES_AVAILABLE');
		echo JHtml::_('bootstrap.endTab');
		
		echo JHtml::_('bootstrap.addTab', 'layoutsTab', 'layoutjs-tab', JText::_('COM_CUSTOMTABLES_LAYOUTS_JS', true));
		if($this->ct->Env->advancedtagprocessor)
			echo $this->renderTextArea($this->item->layoutjs,'layoutjs',$typeboxid,$onPageLoads); 
		else
			echo Text::_('COM_CUSTOMTABLES_AVAILABLE');
		echo JHtml::_('bootstrap.endTab');
		
		echo JHtml::_('bootstrap.endTabSet');

		echo JHtml::_('form.token');
		
		//echo JLayoutHelper::render('layouts.details_under', $this);
		echo render_onPageLoads($onPageLoads,$this->item->layouttype);
		
		$this->getMenuItems();
		?>
		<input type="hidden" name="task" value="layouts.edit" />
		
		<div id="allLayoutRaw" style="display:none;"><?php echo json_encode($this->getLayouts()); ?></div>
		<div id="dependencies_content" style="display:none;">
			<h3><?php echo JText::_('COM_CUSTOMTABLES_LAYOUTS_WHAT_IS_USING_IT', true); ?></h3>
			<div id="layouteditor_tagsContent0" class="dynamic_values_list dynamic_values">
				<?php 
				require('dependencies.php');
				echo renderDependencies($this->item); // this will be shown upon the click in the toolbar
			?>
			</div>
		</div>
	</form>
