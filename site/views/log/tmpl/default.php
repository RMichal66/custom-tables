<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

$currenturl=JoomlaBasicMisc::curPageURL();
$cleanurl=JoomlaBasicMisc::deleteURLQueryOption($currenturl, 'action');
$cleanurl=JoomlaBasicMisc::deleteURLQueryOption($cleanurl, 'user');

if(strpos($cleanurl,"?")===false)
	$cleanurl.='?';
else
	$cleanurl.='&';

echo '
<script>
function ActionFilterChanged(o)
{
	var action=o.value;
	location.href="'.$cleanurl.'user='.$this->userid.'&action="+action;
}

function UserFilterChanged(o)
{
	location.href="'.$cleanurl.'action='.$this->action.'&user="+o.value;
}
</script>';


echo $this->actionSelector;
echo $this->userSelector;

echo '<div class="datagrid">'
		.'<table>'
			.'<thead>'
				.'<tr>'
					.'<th>A</th>'
					.'<th>User</th>'
					.'<th>Time</th>'
					.'<th>Table</th>'
					.'<th>Record</th>'
					.'<th>Action</th>'
				.'</tr>'
			.'</thead>'
			.'<tbody>';
//Content
foreach($this->records as $rec)
{
	echo $this->renderLogLine($rec);
}
echo '</tbody></table>'
.'</div>';



