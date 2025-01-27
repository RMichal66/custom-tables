<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\TwigProcessor;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'catalogtag.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'catalogtableviewtag.php');

$document = JFactory::getDocument();

$document->addScript(JURI::root(true).'/components/com_customtables/js/base64.js');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/catalog.js" type="text/javascript"></script>');
$document->addCustomTag('<script src="'.JURI::root(true).'/components/com_customtables/js/ajax.js"></script>');
$document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/style.css" type="text/css" rel="stylesheet" >');

$html_format=false;
if($this->ct->Env->frmt=='html' or $this->ct->Env->frmt=='')
    $html_format=true;

if($html_format)
    LayoutProcessor::renderPageHeader($this->ct);

//Process general tags before catalog tags to prepare headers for CSV etc output
if($html_format)
{
	$catalogtablecontent=tagProcessor_CatalogTableView::process($this->ct,$this->pagelayout,$this->catalogtablecode);
	if($catalogtablecontent=='')
	{
		$this->ct->LayoutProc->layout=$this->itemlayout;
		$catalogtablecontent=tagProcessor_Catalog::process($this->ct,$this->pagelayout,$this->catalogtablecode);
	}
	
	$this->ct->LayoutProc->layout=$this->pagelayout;
	$this->pagelayout=$this->ct->LayoutProc->fillLayout();
}
else
{
	$catalogtablecontent=tagProcessor_CatalogTableView::process($this->ct,$this->pagelayout,$this->catalogtablecode);

	if($catalogtablecontent=='')
	{
		$this->ct->LayoutProc->layout=$itemlayout;
		$catalogtablecontent=tagProcessor_Catalog::process($this->ct,$this->pagelayout,$this->catalogtablecode);
	}

	$this->ct->LayoutProc->layout=$this->pagelayout;
	$this->pagelayout=$this->ct->LayoutProc->fillLayout();
}

$twig = new TwigProcessor($this->ct, $this->pagelayout);

$this->pagelayout = $twig->process();

$this->pagelayout=str_replace('&&&&quote&&&&','"',$this->pagelayout); // search boxes may return HTML elemnts that contain placeholders with quotes like this: &&&&quote&&&&
$this->pagelayout=str_replace($this->catalogtablecode,$catalogtablecontent,$this->pagelayout);

if($html_format)
    LayoutProcessor::applyContentPlugins($this->pagelayout);

if($this->ct->Env->frmt=='xml')
{
	if (ob_get_contents()) ob_end_clean();
	
    $filename = JoomlaBasicMisc::makeNewFileName($this->ct->Env->menu_params->get('page_title'),'xml');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Type: text/xml; charset=utf-8');
    header("Pragma: no-cache");
    header("Expires: 0");
	echo $this->pagelayout;
	die;//clean exit
}
elseif($this->ct->Env->clean==1)
{
    if (ob_get_contents()) ob_end_clean();
    echo $this->pagelayout;
	die ;//clean exit
}
else
	echo $this->pagelayout;
