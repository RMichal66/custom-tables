<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

use Joomla\Utilities\ArrayHelper;


/**
 * Listoffields Controller
 */
class CustomtablesControllerListofRecords extends JControllerAdmin
{
	protected $text_prefix = 'COM_CUSTOMTABLES_LISTOFRECORDS';
	/**
	 * Proxy for getModel.
	 * @since	2.5
	 */
	public function getModel($name = 'Records', $prefix = 'CustomtablesModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}
	
	public function publish()
	{
		$status = (int)($this->task == 'publish');
				
		$tableid 	= $this->input->get('tableid', 0, 'int');
		
		if($tableid!=0)
		{
			$table=ESTables::getTableRowByID($tableid);
			if(!is_object($table) and $table==0)
			{
				JFactory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			}
			else
			{
				$tablename=$table->tablename;
			}
		}
		
		$cid	= JFactory::getApplication()->input->post->get('cid',array(),'array');
		//$cid = ArrayHelper::toInteger($cid);
		
		//Get Edit model
		$paramsArray=$this->getRecordParams($tableid,$tablename,0);
		
		$_params= new JRegistry;
		$_params->loadArray($paramsArray);
		
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'edititem.php');
		$editModel = JModelLegacy::getInstance('EditItem', 'CustomTablesModel', $_params);
		$editModel->load($_params,true);
		
		$ok=true;
		
		foreach($cid as $id)
		{
			if($id != '')
			{
				if($editModel->setPublishStatusSingleRecord($id,$status) == -1)
				{
					$ok=false;
					break;
				}
			}
		}
		
		$redirect = 'index.php?option=' . $this->option;
		$redirect.='&view=listofrecords&tableid='.(int)$tableid;
		
		$msg = $this->task == 'publish' ? 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_PUBLISHED' : 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_UNPUBLISHED';
		
		if(count($cid) == 1)
			$msg.='_1';
		
		JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended($msg,count($cid)),'success');

		// Redirect to the item screen.
		$this->setRedirect(
			JRoute::_(
				$redirect, false
			)
		);
	}
	
	public function delete()
	{
		$tableid 	= $this->input->get('tableid', 0, 'int');
		
		if($tableid!=0)
		{
			$table=ESTables::getTableRowByID($tableid);
			if(!is_object($table) and $table==0)
			{
				JFactory::getApplication()->enqueueMessage('Table not found', 'error');
				return;
			}
			else
			{
				$tablename=$table->tablename;
			}
		}
		
		$cid	= JFactory::getApplication()->input->post->get('cid',array(),'array');
		//$cid = ArrayHelper::toInteger($cid);
		
		//Get Edit model
		$paramsArray=$this->getRecordParams($tableid,$tablename,0);
		
		$_params= new JRegistry;
		$_params->loadArray($paramsArray);
		
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'edititem.php');
		$editModel = JModelLegacy::getInstance('EditItem', 'CustomTablesModel', $_params);
		$editModel->load($_params,true);
		
		$ok=true;
		
		foreach($cid as $id)
		{
			if($id != '')
			{
				$isok=$editModel->deleteSingleRecord($id);
				if(!$isok)
				{
					$ok=false;
					break;
				}
			}
		}
		
		$redirect = 'index.php?option=' . $this->option;
		$redirect.='&view=listofrecords&tableid='.(int)$tableid;
		
		$msg = 'COM_CUSTOMTABLES_LISTOFRECORDS_N_ITEMS_DELETED';
			
		if(count($cid) == 1)
			$msg.='_1';
		
		JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended($msg,count($cid)),'success');
		
		// Redirect to the item screen.
		$this->setRedirect(
			JRoute::_(
				$redirect, false
			)
		);
	}
	
	protected function getRecordParams($tableid,$tablename,$recordid)
	{
		$paramsArray=array();

		$paramsArray['listingid']=$recordid;
		$paramsArray['estableid']=$tableid;
		$paramsArray['establename']=$tablename;

		return $paramsArray;
	}
}
