<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

// load tooltip behavior
if($this->version < 4)
{
	JHtml::_('behavior.tooltip');
}

JHtml::_('behavior.formvalidator');
$document = JFactory::getDocument();

if($this->version >= 4)
{
	$document->addCustomTag('<script src="'.JURI::root(true).'/media/vendor/jquery/js/jquery.min.js"></script>');
}

$document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/uploadfile.css" rel="stylesheet">');
$document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/style.css" rel="stylesheet">');

	
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.form.js"></script>');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/jquery.uploadfile.min.js"></script>');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/uploader.js"></script>');

    $fileid=$this->generateRandomString();
    $max_file_size=JoomlaBasicMisc::file_upload_max_size();

	echo '<form method="post" action="" id="esFileUploaderForm_Tables">';
	echo '<h2>Import Tables</h2>';

	echo '<p>This may import Table Structure from .txt (json encoded) file.</p>';

	echo '

    
    <div id="ct_uploadedfile_box_file"></div>
	<div id="fileuploader"></div>
	<div id="eventsmessage"></div>
    

	<script>
        UploadFileCount=1;
		var urlstr="'.JURI::root(true).'/administrator/index.php?option=com_customtables&view=fileuploader&tmpl=component&fileid='.$fileid.'";
		ct_getUploader(1,urlstr,'.$max_file_size.',"txt html","esFileUploaderForm_Tables",true,"fileuploader","eventsmessage","'.$fileid.'","filetosubmit","ct_uploadedfile_box_file");//null);

	</script>
    <ul style="list-style: none;">
        <li><input type="checkbox" name="importfields" value="1" checked="checked" /> Import Table Fields</li>
        <li><input type="checkbox" name="importlayouts" value="1" checked="checked" /> Import Layouts</li>
        <li><input type="checkbox" name="importmenu" value="1" checked="checked" /> Import Menu</li>

    </ul>

    <input type="hidden" id="filetosubmit" name="filetosubmit" value="" checked="checked" />
	<input type="hidden" name="fileid" value="'.$fileid.'" />
	<input type="hidden" name="option" value="com_customtables" />
	<!--<input type="hidden" name="controller" value="importtables" />-->
	<input type="hidden" name="task" value="importtables.importtables" />
'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PERMITED_MAX_FILE_SIZE').': '.JoomlaBasicMisc::formatSizeUnits($max_file_size).'
    '.JHtml::_('form.token').'
	</form>
	';
