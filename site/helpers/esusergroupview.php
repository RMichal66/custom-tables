<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class JHTMLESUserGroupView
{
    public static function render($value,$field='')
    {
		
		$db = JFactory::getDBO();
				
		$query = $db->getQuery(true);
		$query->select('#__usergroups.title AS name');
		$query->from('#__usergroups');
		$query->where('id='.(int)$value);
		$query->limit('1');
				
		$db->setQuery($query);
				
		$options=$db->loadObjectList();
				
		if(count($options)==0)
			return '';
				
		return $options[0]->name;
    }
}
