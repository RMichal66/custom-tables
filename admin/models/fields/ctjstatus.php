<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

//https://docs.joomla.org/Creating_a_custom_form_field_type
class JFormFieldCTJStatus extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	public
	 * @var		string
	 *  
	 */
	public $type = 'ctjstatus';
	
	public function getOptions()//$name, $value, &$node, $control_name)$add_empty_option = true
	{
		$options = array();
		
		$options[] = JHtml::_('select.option', -2, JText::_('JTRASHED'));
		$options[] = JHtml::_('select.option', 0, JText::_('JUNPUBLISHED'));
		$options[] = JHtml::_('select.option', 1, JText::_('JPUBLISHED'));
		
        return $options;
	}
}
