<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTUser;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die();

$ct = new CT;

$model = $this->getModel('edititem');
$model->load($ct);
$model->params = Factory::getApplication()->getParams();
$model->listing_id = common::inputGetCmd('listing_id');
$user = new CTUser();

if (!$ct->CheckAuthorization(5)) {
	//not authorized
	Factory::getApplication()->enqueueMessage(common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');

	$returnToEncoded = common::makeReturnToURL();
	$link = Route::_('index.php?option=com_users&view=login&return=' . $returnToEncoded);
	$this->setRedirect($link, common::translate('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
	return;
} else {
	switch (common::inputGetCmd('task')) {

		case 'add' :

			$model = $this->getModel('editfiles');

			if ($model->add()) {
				$msg = common::translate('COM_CUSTOMTABLES_FILE_ADDED');
			} else {
				$msg = common::translate('COM_CUSTOMTABLES_FILE_NOT_ADDED');
			}

			$fileBoxName = common::inputGetCmd('fileboxname');
			$listing_id = common::inputGet("listing_id", 0, 'INT');
			$returntoEncoded = common::getReturnToURL(false);
			$Itemid = common::inputGet('Itemid', 0, 'INT');

			$link = 'index.php?option=com_customtables&view=editfiles'

				. '&fileboxname=' . $fileBoxName
				. '&listing_id=' . $listing_id
				. '&returnto=' . $returntoEncoded //base64 encoded url in Joomla and Sessions ReturnTo variable reference in WP
				. '&Itemid=' . $Itemid;

			$this->setRedirect($link, $msg);

			break;

		case 'delete' :

			$model = $this->getModel('editfiles');

			if ($model->delete()) {
				$msg = common::translate('COM_CUSTOMTABLES_FILE_DELETED');
			} else {
				$msg = common::translate('COM_CUSTOMTABLES_FILE_NOT_DELETED');
			}

			$fileBoxName = common::inputGetCmd('fileboxname');
			$listing_id = common::inputGet("listing_id", 0, 'INT');
			$returnToEncoded = common::getReturnToURL(false);
			$Itemid = common::inputGet('Itemid', 0, 'INT');

			$link = 'index.php?option=com_customtables&view=editfiles'

				. '&fileboxname=' . $fileBoxName
				. '&listing_id=' . $listing_id
				. '&returnto=' . $returnToEncoded
				. '&Itemid=' . $Itemid;

			$this->setRedirect($link, $msg);

			break;

		case 'saveorder' :

			$model = $this->getModel('editfiles');


			if ($model->reorder()) {
				$msg = common::translate('COM_CUSTOMTABLES_FILE_ORDER_SAVED');
			} else {
				$msg = common::translate('COM_CUSTOMTABLES_FILE_ORDER_NOT_SAVED');
			}

			$returnto = common::getReturnToURL();
			$this->setRedirect($returnto, $msg);
			break;

		case 'cancel' :
			$msg = common::translate('COM_CUSTOMTABLES_EDIT_CANCELED');
			$returnto = common::getReturnToURL();
			$this->setRedirect($returnto, $msg);
			break;
		default:

			parent::display();
	}
}