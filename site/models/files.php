<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;

jimport('joomla.application.component.model');

class CustomTablesModelFiles extends JModelLegacy
{
	var $ct;
	var $tableid;
	var $fieldid;
	var $fieldrow;
	var $security;
	var $key;

	function __construct()
	{
		$path = JPATH_COMPONENT_SITE . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
		require_once($path.'loader.php');
		CTLoader();
		
		$this->ct = new CT;

		parent::__construct();

		$app		= JFactory::getApplication();
		$this->params=$app->getParams();

		$jinput=JFactory::getApplication()->input;
		$listing_id = $jinput->getInt('listing_id', 0);

		$this->tableid = $jinput->getInt('tableid',0);
		$this->fieldid = $jinput->getInt('fieldid',0);
		
		
		$this->security = $jinput->getCmd('security', 'd');
		$this->key = $jinput->getCmd('key','');

		if($listing_id==0 or $listing_id=='' or $this->fieldid==0)
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');

			$this->_id=0;
			return false;
		}

		$this->load($listing_id);

	}

	function load($listing_id)
	{
		if($listing_id==0)
			return false;

		$jinput=JFactory::getApplication()->input;

		$this->tableid = $jinput->getInt('tableid',0);
		$this->ct->getTable($this->tableid, null);
				
		if($this->ct->Table->tablename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected (79).', 'error');
			return;
		}

		$this->setId($listing_id);

		foreach($this->ct->Table->fields as $f)
		{
			if($f['id']==$this->fieldid)
			{
				$this->fieldrow=$f;
				break;
			}
		}
	}

	function setId($listing_id)
	{
		$this->_id	= $listing_id;
		$this->_data	= null;
	}

	function & getData()
	{
		if($this->_id==0)
		{
			$row=array();
			return $row;
		}
		
		return $this->ct->Table->loadRecord($this->_id);
	}


	function getTypeFieldName($type)
	{
		foreach($this->ct->Table->fields as $ESField)
		{
				if($ESField['type']==$type)
					return $ESField['realfieldname'];
		}

		return '';
	}
}
