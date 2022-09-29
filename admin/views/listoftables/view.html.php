<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
\defined('_JEXEC') or die;

use CustomTables\CT;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

/**
 * Customtables View class for the Listoftables
 */
class CustomtablesViewListoftables extends JViewLegacy
{
    /**
     * Listoftables view display method
     * @return void
     */
    var CT $ct;

    var $languages;

    function display($tpl = null)
    {
        if ($this->getLayout() !== 'modal') {
            // Include helper submenu
            CustomtablesHelper::addSubmenu('listoftables');
        }

        $model = $this->getModel();
        $this->ct = $model->ct;

        // Assign data to the view
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        $this->user = Factory::getUser();

        if ($this->ct->Env->version >= 4) {
            $this->filterForm = $this->get('FilterForm');
            $this->activeFilters = $this->get('ActiveFilters');
        }

        $this->listOrder = $this->state->get('list.ordering');
        $this->listDirn = $this->escape($this->state->get('list.direction'));

        // get global action permissions

        $this->canDo = ContentHelper::getActions('com_customtables', 'tables');
        $this->canCreate = $this->canDo->get('tables.create');
        $this->canEdit = $this->canDo->get('tables.edit');
        $this->canState = $this->canDo->get('tables.edit.state');
        $this->canDelete = $this->canDo->get('tables.delete');

        $this->isEmptyState = $this->get('IsEmptyState');
        //$this->canBatch = false;//$this->canDo->get('core.batch');

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal') {
            if ($this->ct->Env->version < 4) {
                $this->addToolbar_3();
                $this->sidebar = JHtmlSidebar::render();
            } else
                $this->addToolbar_4();

            // load the batch html
            //if ($this->canCreate && $this->canEdit && $this->canState)
            //{
            //$this->batchDisplay = JHtmlBatch_::render();
            //}
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        $this->languages = $this->ct->Languages->LanguageList;

        // Display the template
        if ($this->ct->Env->version < 4)
            parent::display($tpl);
        else
            parent::display('quatro');

        // Set the document
        $this->setDocument();
    }

    /**
     * Escapes a value for output in a view script.
     *
     * @param mixed $var The output to escape.
     *
     * @return  mixed  The escaped value.
     */
    public function escape($var)
    {
        if (strlen($var) > 50) {
            // use the helper htmlEscape method instead and shorten the string
            return CustomtablesHelper::htmlEscape($var, $this->_charset, true);
        }
        // use the helper htmlEscape method instead.
        return CustomtablesHelper::htmlEscape($var, $this->_charset);
    }

    protected function addToolBar_3()
    {
        JToolBarHelper::title(Text::_('COM_CUSTOMTABLES_LISTOFTABLES'), 'joomla');
        JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoftables');
        JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

        if ($this->canCreate) {
            JToolBarHelper::addNew('tables.add');
        }

        // Only load if there are items
        if (CustomtablesHelper::checkArray($this->items)) {
            if ($this->canEdit) {
                JToolBarHelper::editList('tables.edit');
            }

            if ($this->canState) {
                JToolBarHelper::publishList('listoftables.publish');
                JToolBarHelper::unpublishList('listoftables.unpublish');
            }

            if ($this->canDo->get('core.admin')) {
                JToolBarHelper::checkin('listoftables.checkin');
            }

            if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete)) {
                JToolbarHelper::deleteList('', 'listoftables.delete', 'JTOOLBAR_EMPTY_TRASH');
            } elseif ($this->canState && $this->canDelete) {
                JToolbarHelper::trash('listoftables.trash');
            }
        }

        if ($this->ct->Env->advancedtagprocessor)
            if (!$this->isEmptyState and $this->state->get('filter.published') != -2 and $this->ct->Env->advancedtagprocessor)
                JToolBarHelper::custom('listoftables.export', 'download.png', '', 'Export');

        if ($this->canState) {

            $options = JHtml::_('jgrid.publishedOptions');
            $newOptions = [];
            foreach ($options as $option) {

                if ($option->value != 2)
                    $newOptions[] = $option;
            }

            JHtmlSidebar::addFilter(
                Text::_('JOPTION_SELECT_PUBLISHED'),
                'filter_published',
                JHtml::_('select.options', $newOptions, 'value', 'text', $this->state->get('filter.published'), true)
            );
        }

        $CTCategory = JFormHelper::loadFieldType('CTCategory', false);
        $CTCategoryOptions = $CTCategory->getOptions(false); // works only if you set your field getOptions on public!!

        JHtmlSidebar::addFilter(
            Text::_('COM_CUSTOMTABLES_TABLES_CATEGORY_SELECT'),
            'filter_tablecategory',
            JHtml::_('select.options', $CTCategoryOptions, 'value', 'text', $this->state->get('filter.tablecategory'))
        );
    }

    protected function addToolbar_4()
    {
        $user = Factory::getUser();

        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_CUSTOMTABLES_LISTOFTABLES'), 'joomla');

        if ($this->canCreate)
            $toolbar->addNew('tables.add');

        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);

        $childBar = $dropdown->getChildToolbar();

        if ($this->canState) {
            $childBar->publish('listoftables.publish')->listCheck(true);
            $childBar->unpublish('listoftables.unpublish')->listCheck(true);
        }

        if ($this->canDo->get('core.admin')) {
            $childBar->checkin('listoftables.checkin');
        }

        if (($this->canState && $this->canDelete)) {
            if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED) {
                $childBar->trash('listoftables.trash')->listCheck(true);
            }
        }

        if (!$this->isEmptyState and $this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED and $this->ct->Env->advancedtagprocessor)
            $toolbar->appendButton('Standard', 'download', 'Export', 'listoftables.export', $listSelect = true, $formId = null);

        if (($this->canState && $this->canDelete)) {
            if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete) {
                $toolbar->delete('listoftables.delete')
                    ->text('JTOOLBAR_EMPTY_TRASH')
                    ->message('JGLOBAL_CONFIRM_DELETE')
                    ->listCheck(true);
            }
        }
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument()
    {
        if (!isset($this->document)) {
            $this->document = Factory::getDocument();
        }
        $this->document->setTitle(Text::_('COM_CUSTOMTABLES_LISTOFTABLES'));
    }

    /**
     * Returns an array of fields the table can be sorted by
     *
     * @return  array  Array containing the field name to sort by as the key and display text as value
     */
    protected function getSortFields()
    {
        return array(
            'a.published' => Text::_('JSTATUS'),
            'a.tablename' => Text::_('COM_CUSTOMTABLES_TABLES_TABLENAME_LABEL'),
            'a.tablecategory' => Text::_('COM_CUSTOMTABLES_TABLES_TABLECATEGORY_LABEL'),
            'a.id' => Text::_('JGRID_HEADING_ID')
        );
    }

    protected function getNumberOfRecords($realtablename, $realidfield)
    {
        $db = Factory::getDBO();
        $query = 'SELECT COUNT(' . $realidfield . ') AS count FROM ' . $realtablename . ' LIMIT 1';

        try {
            $db->setQuery($query);
            $rows = $db->loadObjectList();
        } catch (Exception $e) {
            echo $e->getMessage();

            $app = Factory::getApplication();
            $app->enqueueMessage('Table "' . $realtablename . '" - ' . $e->getMessage(), 'error');
            return 0;
        }


        return $rows[0]->count;
    }
}
