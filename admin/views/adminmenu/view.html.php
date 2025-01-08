<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die();

use CustomTables\common;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * CustomTables Category Menu View class
 * @since 3.6.7
 */
class CustomTablesViewAdminMenu extends HtmlView
{
	/**
	 * View display method
	 * @param null $tpl
	 * @return void
	 * @throws Exception
	 * @since 3.6.7
	 */
	function display($tpl = null): void
	{
		parent::display($tpl);

		// Set the document
		$document = Factory::getDocument();
		$this->setDocument($document);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @param Document $document
	 * @return void
	 * @since 3.2.9
	 */
	public function setDocument(Joomla\CMS\Document\Document $document): void
	{
		// add dashboard style sheets
		$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/dashboard.css" type="text/css" rel="stylesheet" >');

		// set page title
		$document->setTitle(common::translate('COM_CUSTOMTABLES_DASHBOARD'));
	}
}
