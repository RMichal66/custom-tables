<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Component\ComponentHelper;
use CustomTables\DataTypes\Tree;

jimport('joomla.application.component.model');

class CustomTablesModelStructure extends JModel
{
	var $ct;

	var $record_count=0;
	
	var $optionname;
	var $parentid;
		
	var $linkable;
	var $image_prefix;

	var $row_break;
		
	var $esTable;
	var $establename;
	var $estableid;
	var $fieldname;
	var $fieldtype;

	var $ListingJoin;

	function __construct()
	{
		$this->ct = new CT;
			
		$this->esTable=new ESTables;
				
	    parent::__construct();
			
		$params = ComponentHelper::getParams( 'com_customtables' );
		$this->ct->Env->menu_params = $params
		
		if($this->ct->Env->jinput->get('establename','','CMD'))
			$this->establename=$this->ct->Env->jinput->get('establename','','CMD');
		else
			$this->establename=$params->get( 'establename' );
				
		if($this->ct->Env->jinput->get('esfieldname','','CMD'))
		{
			$esfn = $this->ct->Env->jinput->get('esfieldname','','CMD');
			$this->fieldname=strtolower(trim(preg_replace("/[^a-zA-Z]/", "",$esfn )));
		}
		else
		{
			$esfn=$params->get( 'esfieldname' );
			$this->fieldname=strtolower(trim(preg_replace("/[^a-zA-Z]/", "",$esfn )));
		}
				
		$tablerow = $this->esTable->getTableRowByName($this->establename);
		$this->estableid=$tablerow->id;

		// Get pagination request variables
		$mainframe = JFactory::getApplication('site');
		$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		$limitstart = $this->ct->Env->jinput->get('limitstart',0,'INT');
 
		// In case limit has been changed, adjust it
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);
 
		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);
				
		//get field
		$row=$this->esTable-> getFieldRowByName($this->esfieldname,$this->estableid);
		$this->fieldtype=$row->type;				
				
		if($params->get('optionname')!='')
			$this->optionname=$params->get('optionname');
		else
		{
			//get OptionName by FieldName
			$typeparams=explode(',',$row->typeparams);
			$this->optionname=$typeparams[0];
		}

		if($this->ct->Env->jinput->getString('image_prefix'))
			$this->image_prefix=$this->ct->Env->jinput->getString('image_prefix');
		else
			$this->image_prefix=$params->get('image_prefix');
			
		if($this->ct->Env->jinput->getInt('row_break',0))
			$this->row_break=$this->ct->Env->jinput->getInt('row_break',0);
		else
			$this->row_break=$params->get('row_break');
			
		if($this->ct->Env->jinput->getInt('linkable',0))
			$this->linkable=$this->ct->Env->jinput->getInt('linkable',0);
		else
			$this->linkable=(int)$params->get('linkable');
					
		if($this->ct->Env->jinput->getInt('listingjoin',0))
			$this->ListingJoin=$this->ct->Env->jinput->getInt('listingjoin',0);
		else
			$this->ListingJoin=(int)$params->get('listingjoin');
	}
		
	function getPagination()
	{
		// Load the content if it doesn't already exist
		if (empty($this->_pagination))
		{
		    jimport('joomla.html.pagination');
			$a= new JPagination($this->record_count, $this->getState('limitstart'), $this->getState('limit') );
			return $a;
		}
		return $this->_pagination;
	}

	function getStructure()
	{
		if(!$this->fieldtype=='customtables')
			return array();
				
		$wherearr=array();
		
		if($this->ct->Env->jinput->getString('alpha'))
		{
			$parentid=Tree::getOptionIdFull($this->optionname);
			$wherearr[]='INSTR(familytree,"-'.$parentid.'-") AND SUBSTRING(title'.$this->ct->Languages->Postfix.',1,1)="'
				.$this->ct->Env->jinput->getString('alpha').'"';
		}
		else
		{
			$this->parentid=$es->getOptionIdFull($this->optionname);
			$wherearr[]='parentid='.(int)$this->parentid;
		}
		
		$db = JFactory::getDBO();
		
		$where='';
		if(count($wherearr)>0)
			$where = ' WHERE '.implode(" AND ",$wherearr);

		if($this->ListingJoin)
		{
			$query = 'SELECT optionname, '
					.'CONCAT("",familytreestr,".",optionname) as theoptionname, '
					.'CONCAT( title'.$this->ct->Languages->Postfix.'," (",COUNT(#__customtables_table_'.$this->establename.'.id),")") AS optiontitle, '
					.'image, '
					.'imageparams '
					
					.'FROM #__customtables_options '
					.' INNER JOIN #__customtables_table_'.$this->establename
					.' ON INSTR(es_'.$this->esfieldname.', CONCAT(familytreestr,".",optionname))'
					.' '.$where
					.' GROUP BY #__customtables_options.id'
					.' ORDER BY title'.$this->ct->Languages->Postfix;
		}
		else
		{
			$query = 'SELECT optionname, '
					.'CONCAT("",familytreestr,".",optionname) as theoptionname, '
					.'title'.$this->ct->Languages->Postfix.' AS optiontitle, '
					.'image, '
					.'imageparams '
					
					.'FROM #__customtables_options '
					.' '.$where
					.' ORDER BY title'.$this->ct->Languages->Postfix;
		}
       
		$db->setQuery($query);
	    $db->execute();
		
		$this->record_count = $db->getNumRows();
		
		$db->setQuery($query, $this->getState('limitstart') , $this->getState('limit'));
        			
		$rows=$db->loadAssocList();
		$newrows=array();
		foreach($rows as $row)
			$newrows[]=$row;
		
		return $newrows;
	}
}
