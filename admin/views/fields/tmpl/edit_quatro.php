<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage views/fields/tmpl/edit.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$document = JFactory::getDocument();

$document->addCustomTag('<link href="'.JURI::root(true).'/administrator/components/com_customtables/css/style.css" rel="stylesheet">');
$document->addCustomTag('<link href="'.JURI::root(true).'/administrator/components/com_customtables/css/fieldtypes.css" rel="stylesheet">');
$document->addCustomTag('<link href="'.JURI::root(true).'/administrator/components/com_customtables/css/modal.css" rel="stylesheet">');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/ajax.js"></script>');
$document->addCustomTag('<script src="'.JURI::root(true).'/administrator/components/com_customtables/js/typeparams_j4.js"></script>');
$document->addCustomTag('<link rel="stylesheet" href="'.JURI::root(true).'/media/system/css/fields/switcher.css">');

$input	= JFactory::getApplication()->input;

if($input->getCmd('extratask','')=='updateimages')
{
	extraTasks::prepareJS();
}

?>
<div id="fieldsData" style="display:none;"><?php echo json_encode(Fields::getFields($this->tableid,true)); ?></div>
<script type="text/javascript">
	//websiteroot="<?php echo JURI::root(true); ?>";

	<?php

	if($this->ct->Env->advancedtagprocessor)
	{
		echo '
		proversion=true;
';
	}
	;
	
	echo 'all_tables='.$this->getAllTables().';';
	?>

	var json=JSON.parse(document.getElementById("fieldsData").innerHTML);
	wizardFields=Array.from(json);

</script>
<!--<div id="customtables_loader" style="display: none;"> -->

<form action="<?php echo JRoute::_('index.php?option=com_customtables&layout=edit&id='.(int)($this->item->id).$this->referral); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

<div class="form-horizontal">

	<?php //echo JHtml::_('bootstrap.startTabSet', 'fieldsTab', array('active' => 'general')); ?>
	<?php echo HTMLHelper::_('uitab.startTabSet', 'fieldsTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'fieldsTab', 'general', Text::_('COM_CUSTOMTABLES_FIELDS_GENERAL')); ?>
	<?php //echo JHtml::_('bootstrap.addTab', 'fieldsTab', 'general', JText::_('COM_CUSTOMTABLES_FIELDS_GENERAL', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('tableid'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('tableid',null,$this->tableid); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('fieldname'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('fieldname'); ?></div>
				</div>

				<?php if($this->table_row->customtablename!=''): ?>
				<hr/>
				<p><?php echo JText::_('COM_CUSTOMTABLES_FIELDS_THIS_IS_THIRDPARTY_FIELD', true).': "'.$this->table_row->customtablename.'"';?></p>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('customfieldname'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('customfieldname'); ?></div>
				</div>
				
				<?php endif; ?>
				
				<hr/>

				<?php
				
				$morethanonelang=false;
				foreach($this->ct->Languages->LanguageList as $lang)
				{
					$id='fieldtitle';
					if($morethanonelang)
					{
						$id.='_'.$lang->sef;

						$cssclass='form-control valid form-control-success';
						$att='';
					}
					else
					{
						$cssclass='form-control required valid form-control-success';
						$att=' required';
					}

					$item_array=(array)$this->item;
					$vlu='';

					if(isset($item_array[$id]))
						$vlu=$item_array[$id];

					if($morethanonelang)
						$field_label = JText::_('COM_CUSTOMTABLES_FIELDS_FIELDTITLE', true);
					else
						$field_label = $this->form->getLabel('fieldtitle');

					echo '
					<div class="control-group">
						<div class="control-label">'.$field_label.'</div>
						<div class="controls">
							<input type="text" name="jform['.$id.']" id="jform_'.$id.'"  value="'.$vlu.'" class="'.$cssclass.'" placeholder="Field Title"   maxlength="255" '.$att.'  />
							<b>'.$lang->title.'</b>
						</div>

					</div>
					';

					$morethanonelang=true; //More than one language installed
				}
				?>

				<hr/>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('type'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('typeparams'); ?></div>
					<div class="controls"><div class="typeparams_box" id="typeparams_box"></div></div>
				</div>

				<div class="control-group">
					<div class="control-label"></div>
					<div class="controls"><?php echo $this->form->getInput('typeparams'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('parent'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('parent'); ?></div>
				</div>
			</div>
		</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php //echo JHtml::_('bootstrap.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'fieldsTab', 'optional', Text::_('COM_CUSTOMTABLES_FIELDS_OPTIONAL')); ?>
	<?php //echo JHtml::_('bootstrap.addTab', 'fieldsTab', 'optional', JText::_('COM_CUSTOMTABLES_FIELDS_OPTIONAL', true)); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span12">
				
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('isrequired'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('isrequired'); ?></div>
				</div>
				
				<div class="control-group<?php echo (!$this->ct->Env->advancedtagprocessor ? ' ct_pro' : ''); ?>">
					<div class="control-label"><?php echo $this->form->getLabel('defaultvalue'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('defaultvalue'); ?></div>
				</div>
				
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('allowordering'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('allowordering'); ?></div>
				</div>
				
				<div class="control-group<?php echo (!$this->ct->Env->advancedtagprocessor ? ' ct_pro' : ''); ?>">
					<div class="control-label"><?php echo $this->form->getLabel('valuerule'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('valuerule'); ?></div>
				</div>
				
				<div class="control-group<?php echo (!$this->ct->Env->advancedtagprocessor ? ' ct_pro' : ''); ?>">
					<div class="control-label"><?php echo $this->form->getLabel('valuerulecaption'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('valuerulecaption'); ?></div>
				</div>
				
			</div>
		</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php //echo JHtml::_('bootstrap.endTab'); ?>

	<?php 

		$morethanonelang=false;
		foreach($this->ct->Languages->LanguageList as $lang)
		{
			$id='description';
			if($morethanonelang)
				$id.='_'.$lang->sef;
				
			echo HTMLHelper::_('uitab.addTab', 'fieldsTab', $id, $lang->title);

			echo '
			
			
			<div id="'.$id.'" class="tab-pane">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span12">
					
						<h3>'.Text::_('COM_CUSTOMTABLES_FIELDS_DESCRIPTION').' -  <b>'.$lang->title.'</b></h3>';
					
			

			$editor_name = Factory::getApplication()->get('editor');
			$editor = Editor::getInstance($editor_name);

			$item_array=(array)$this->item;
			$vlu='';

			if(isset($item_array[$id]))
				$vlu=$item_array[$id];

				echo '<textarea rows="10" cols="20" name="jform['.$id.']" id="jform_'.$id.'" style="width:100%;height:100%;"
				class="text_area"  placeholder="Field Description" >'.$vlu.'</textarea>';

			echo '
					</div>
				</div>
			</div>';
			$morethanonelang=true; //More than one language installed
			
			echo HTMLHelper::_('uitab.endTab');
		}
	?>
	
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<div>
		<input type="hidden" name="task" value="fields.edit" />
		<input type="hidden" name="tableid" value="<?php echo $this->tableid; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>



	<script>
		updateTypeParams("jform_type","jform_typeparams","typeparams_box");
		<?php if(!$this->ct->Env->advancedtagprocessor): ?>
			disableProField("jform_defaultvalue");
			disableProField("jform_valuerule");
			disableProField("jform_valuerulecaption");
		<?php endif; ?>
	</script>


</div>

<div id="ct_fieldtypeeditor_box" style="display: none;"><?php
	$attributes=array('name'=>'ct_fieldtypeeditor','id'=>'ct_fieldtypeeditor','directory'=>'images','recursive'=>true, 'label'=>'Select Folder','readonly'=>false);
	echo CTTypes::getField('folderlist', $attributes,null)->input;
?></div>

</form>
<!--</div>-->

  <!-- Modal content -->
  <div id="ctModal" class="ctModal">
  <div id="ctModal_box" class="ctModal_content">
    <span id="ctModal_close" class="ctModal_close">&times;</span>
	<div id="ctModal_content"></div>
  </div>
	</div>
  <!-- end of the modal -->