<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controllerform library
jimport('joomla.application.component.controllerform');

class CustomtablesControllerFields extends JControllerForm
{
	protected $task;

	public function __construct($config = array())
	{
		$this->view_list = 'listoffields'; // safeguard for setting the return view listing to the main view.
		parent::__construct($config);
	}

	protected function allowAdd($data = array())
	{		// In the absense of better information, revert to the component permissions.
		return parent::allowAdd($data);
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
		// get user object.
		$user = JFactory::getUser();
		// get record id.
		$recordId = (int) isset($data[$key]) ? $data[$key] : 0;

		if ($recordId)
		{
			// The record has been set. Check the record permissions.
			$permission = $user->authorise('core.edit', 'com_customtables.fields.' . (int) $recordId);
			if (!$permission)
			{
				if ($user->authorise('core.edit.own', 'com_customtables.fields.' . $recordId))
				{
					// Now test the owner is the user.
					$ownerId = (int) isset($data['created_by']) ? $data['created_by'] : 0;
					if (empty($ownerId))
					{
						// Need to do a lookup from the model.
						$record = $this->getModel()->getItem($recordId);

						if (empty($record))
						{
							return false;
						}
						$ownerId = $record->created_by;
					}

					// If the owner matches 'me' then allow.
					if ($ownerId == $user->id)
					{
						if ($user->authorise('core.edit.own', 'com_customtables'))
						{
							return true;
						}
					}
				}
				return false;
			}
		}
		// Since there is no permission, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$tmpl   = $this->input->get('tmpl');
		$layout = $this->input->get('layout', 'edit', 'string');

		$ref 	= $this->input->get('ref', 0, 'string');
		$refid 	= $this->input->get('refid', 0, 'int');

		$tableid= $this->input->getint('tableid',0);
		// Setup redirect info.

		$append = '';

		if ($refid)
                {
			$append .= '&ref='.(string)$ref.'&refid='.(int)$refid;
		}
		elseif ($ref)
		{
			$append .= '&ref='.(string)$ref;
		}

		if ($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		if ($layout)
		{
			$append .= '&layout=' . $layout;
		}

		if ($recordId)
		{
			$append .= '&' . $urlVar . '=' . $recordId;
		}

		$append .= '&tableid=' . $tableid;

		return $append;
	}

	public function batch($model = null)
	{
		$tableid 	= $this->input->get('tableid', 0, 'int');
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Fields', '', array());

		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_customtables&view=listoffields'. $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	public function cancel($key = null)
	{
		$tableid 	= $this->input->get('tableid', 0, 'int');
		
		$cancel = parent::cancel($key);

		$this->setRedirect(
			JRoute::_(
				'index.php?option=' . $this->option . '&view=listoffields&tableid='.(int)$tableid, false
			)
		);

		return $cancel;
	}

	public function save($key = null, $urlVar = null)
	{
		$tableid 	= $this->input->get('tableid', 0, 'int');

		// get the referal details
		$this->ref 		= $this->input->get('ref', 0, 'word');
		$this->refid 	= $this->input->get('refid', 0, 'int');
		
		$fieldid 	= $this->input->get('id', 0, 'int');
		
	
		if ($this->ref || $this->refid)
		{
			// to make sure the item is checkedin on redirect
			$this->task = 'save';
		}

		$saved = parent::save($key, $urlVar);

		
		$redirect = 'index.php?option=' . $this->option;
		
		if($this->task=='apply' or $this->task=='save2new' or $this->task=='save2copy')
		{
			$redirect.='&view=fields&layout=edit&id='.(int)$fieldid.'&tableid='.(int)$tableid;
		}
		else
			$redirect.='&view=listoffields&tableid='.(int)$tableid;
		
		//Pospone extra task
				
		if($this->input->getCmd('extratask','')!='')
		{
			$redirect.='&extratask='.$this->input->getCmd('extratask','');
			$redirect.='&old_typeparams='.$this->input->get('old_typeparams','','BASE64');
			$redirect.='&new_typeparams='.$this->input->get('new_typeparams','','BASE64');
			$redirect.='&fieldid='.$this->input->getInt('fieldid',0);
		}

		if ($saved)
		{
			// Redirect to the item screen.
			$this->setRedirect(
				JRoute::_(
					$redirect, false
				)
			);
		}
		
		return $saved;
	}

	protected function postSaveHook(JModelLegacy $model, $validData = array())
	{
		return;
	}
}
