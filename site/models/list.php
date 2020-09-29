<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');


jimport( 'joomla.html.html.menu' );

class CustomTablesModelList extends JModel
{

	/** @var object JTable object */
	var $_table = null;

	var $_pagination = null;

	function getItems($noState = false,$bone='<sup>|_</sup>&nbsp;',$custom_spacer='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $connect_with_table='', $connect_with_field='')
	{
		$mainframe = JFactory::getApplication();

		static $items;

		$db = $this->getDBO();

		if($noState)
		{

			$filter_order='m.ordering';
			$filter_order_Dir='ASC';
			$filter_rootparent='';
			$limit=0;
			$limitstart=0;
			$levellimit=10;
			$search='';
		}
		else
		{
			$context= 'com_customtables.list.';

			$filter_order			= $mainframe->getUserStateFromRequest( $context.'filter_order',		'filter_order',		'm.ordering',	'cmd' );
			$filter_order_Dir		= $mainframe->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'ASC',			'word' );

			$filter_rootparent		= $mainframe->getUserStateFromRequest( $context.'filter_rootparent','filter_rootparent','','int' );

			$limit				= $mainframe->getUserStateFromRequest( 'global.list.limit',							'limit',			$mainframe->getCfg( 'list_limit' ),	'int' );
			$limitstart			= $mainframe->getUserStateFromRequest( $context.'limitstart',		'limitstart',		0,				'int' );
			$levellimit			= $mainframe->getUserStateFromRequest( $context.'levellimit',		'levellimit',		10,				'int' );
			$search				= $mainframe->getUserStateFromRequest( $context.'search',			'search',			'',				'string' );
			$search				= JString::strtolower( $search );
		}

		$where = array();

				// just in case filter_order get's messed up
		if ($filter_order) {
			$orderby = ' ORDER BY '.$filter_order .' '. $filter_order_Dir .', m.parentid, m.ordering';
		} else {
			$orderby = ' ORDER BY m.parentid, m.ordering';
		}

		// select the records
		// note, since this is a tree we have to do the limits code-side
		if ($search) {
			$query = 'SELECT m.id' .
					' FROM #__customtables_options AS m' .
					' WHERE ' .
					' LOWER( m.title ) LIKE '.$db->Quote( '%'.$search.'%', false ) .

					//AND
					$and;
			$db->setQuery( $query );
			$search_rows = $db->loadResultArray();
		}


		if($filter_rootparent)
			$where[]=' ( id='.$filter_rootparent.' OR parentid!=0 )';

   		$WhereStr='';
		if(count($where)>0)
			$WhereStr=' WHERE '.implode(' AND ',$where);//$WhereStr;


		$query = 'SELECT m.*, m.optionname AS title, m.parentid AS parent_id';

		if($connect_with_table!='' and $connect_with_field!='')
			$query.=', count(m.id) AS entrycount';


		$query.=' FROM #__customtables_options AS m';

		if($connect_with_table!='' and $connect_with_field!='')
		{
			//",category",
			//$query.=' INNER JOIN #__customtables_table_'.$connect_with_table.' AS c ON INSTR(c.es_'.$connect_with_field.',m.familytreestr)';
			$query.=' INNER JOIN #__customtables_table_'.$connect_with_table.' AS c ON INSTR(c.es_'.$connect_with_field.',m.familytreestr)';
		}

		$query.=$WhereStr;

		if($connect_with_table!='' and $connect_with_field!='')
		{
			$query.=' GROUP BY m.id ';


			require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
			$fieldrow=ESFields::getFieldRowByName($connect_with_field, '',$connect_with_table);

			$typeparams_pair=explode(',',$fieldrow->typeparams);
			$structure_parent_name=$typeparams_pair[0];

			if($structure_parent_name!='')
			{
				require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');

				$parentid=JoomlaBasicMisc::getOptionIdFull($structure_parent_name);
			}
			else
				$parentid=0;
		}
		else
			$parentid=0;

		$query.=$orderby;

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());



		$rows = $db->loadObjectList();

		$children = array();
		// first pass - collect children
		foreach ($rows as $v )
		{
			$pt = $v->parentid;
			$list = @$children[$pt] ? $children[$pt] : array();
			array_push( $list, $v );
			$children[$pt] = $list;
		}

		// second pass - get an indent list of the items
		
		$list = $this->treerecurse($parentid, '', array(), $children, max( 0, $levellimit-1 ),0,1,$bone,$custom_spacer );

		// eventually only pick out the searched items.
		if ($search) {
			$list1 = array();

			foreach ($search_rows as $sid )
			{
				foreach ($list as $item)
				{
					if ($item->id == $sid) {
						$list1[] = $item;
					}
				}
			}
			// replace full list with found items
			$list = $list1;
		}
		//echo '3';
		$total = count( $list );

		if(!$noState)
		{
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination( $total, $limitstart, $limit );
			$list = array_slice( $list, $this->_pagination->limitstart, $this->_pagination->limit );
		}
		//echo '4';
		// slice out elements based on limits

		//echo '5';
		$items = $list;

		return $items;
	}

	function treerecurse($id, $indent, $list, &$children, $maxlevel=9999, $level=0, $type=1,$bone,$custom_spacer,$parentname='')
	{


        if (@$children[$id] && $level <= $maxlevel)
        {


                foreach ($children[$id] as $v)
                {

                        $id = $v->id;


                        if ($type) {
                                $pre    = $bone;
                                $spacer = $custom_spacer;
                        } else {
                                $pre    = '- ';
                                $spacer = '&nbsp;&nbsp;';
                        }


                        if ($level == 0) {
								$parentname='';
                                $txt    = $v->optionname;
								$pretext='';

                        } else {
                                $txt    = $pre . $v->optionname;
								$pretext=$pre;
                        }
                        $pt = $v->parentid;
                        $list[$id] = $v;
						$list[$id]->pre = "$indent$pretext";
                        $list[$id]->treename = "$indent$txt";

						if($parentname!='')
							$parentname_new=$parentname.'.'.$v->optionname;
						else
							$parentname_new=$v->optionname;

						$list[$id]->calculatedtree = $parentname_new;
                        $list[$id]->children = count(@$children[$id]);
                        $list = $this->treerecurse($id, $indent . $spacer, $list, $children, $maxlevel, $level+1, 1, $bone,$custom_spacer,$parentname_new);
                }
        }
        return $list;
	}


	function &getPagination()
	{
		if ($this->_pagination == null) {
			$this->getItems();
		}
		return $this->_pagination;
	}




	function orderItem($item, $movement)
	{


		$row = JTable::getInstance('List', 'Table');
		$row->load( $item );

		if (!$row->move( $movement, ' parentid = '.(int) $row->parentid )) {
			$this->setError($row->getError());
			return false;
		}


		return true;
	}


	function setOrder($items)
	{
		$jinput = JFactory::getApplication()->input;

		$total		= count( $items );

		$row 		= JTable::getInstance('List', 'Table');
		//echo $row;

		$groupings	= array();




		$order		= JFactory::getApplication()->input->post->get('order',array(),'array');
		JArrayHelper::toInteger($order);


		// update ordering values

		for( $i=0; $i < $total; $i++ ) {
			//echo '<br/>'.$items[$i].'<br/>';
			$row->load( $items[$i] );
			//echo '<br/>puchka<br/>';
			//return true;
			// track parents
			$groupings[] = $row->parentid;

			if ($row->ordering != $order[$i]) {
				$row->ordering = $order[$i];
				if (!$row->store()) {
					$this->setError($row->getError());
					return false;
				}
			} // if
		} // for



		// execute updateOrder for each parentid group
		$groupings = array_unique( $groupings );
		foreach ($groupings as $group){
			$row->reorder(' parentid = '.(int) $group.' ');
		}

		// clean cache
		//MenusHelper::cleanCache();

		return true;
	}

	/**
	 * Delete one or more menu items
	 * @param mixed int or array of id values
	 */
	function delete( $ids )
	{
		JArrayHelper::toInteger($ids);

		if (!empty( $ids )) {

			// Add all children to the list
			foreach ($ids as $id)
			{
				$this->_addChildren((int)$id, $ids);
			}

			$db = $this->getDBO();


			// Delete the menu items
			$where = 'WHERE id = ' . implode( ' OR id = ', $ids );

			$query = 'DELETE FROM #__customtables_options ' . $where;
			$db->setQuery( $query );
			$db->execute();
			//if (!$db->query()) {
			//	$this->setError( $db->getErrorMsg() );
			//	return false;
//			}
		}


		return true;
	}


	function _addChildren($id, &$list)
	{
		// Initialize variables
		$return = true;

		// Get all rows with parentid of $id
		$db = $this->getDBO();
		$query = 'SELECT id' .
				' FROM #__customtables_options' .
				' WHERE parentid = '.(int) $id;
		$db->setQuery( $query );
		$rows = $db->loadObjectList();

		// Make sure there aren't any errors
		if ($db->getErrorNum()) {
			$this->setError($db->getErrorMsg());
			return false;
		}

		// Recursively iterate through all children... kinda messy
		// TODO: Cleanup this method
		foreach ($rows as $row)
		{
			$found = false;
			foreach ($list as $idx)
			{
				if ($idx == $row->id) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$list[] = $row->id;
			}
			$return = $this->_addChildren($row->id, $list);
		}
		return $return;
	}
	/*
	 * Rebuild the sublevel field for items in the menu (if called with 2nd param = 0 or no params, it will rebuild entire menu tree's sublevel
	 * @param array of menu item ids to change level to
	 * @param int level to set the menu items to (based on parentid
	 */
	function _rebuildSubLevel($cid = array(0), $level = 0)
	{
		JArrayHelper::toInteger($cid, array(0));
		$db = $this->getDBO();
		$ids = implode( ',', $cid );
		$cids = array();
		if($level == 0) {
			$query 	= 'UPDATE #__customtables_options SET sublevel = 0 WHERE parentid = 0';
			$db->setQuery($query);
			$db->query();
			$query 	= 'SELECT id FROM #__customtables_options WHERE parentid = 0';
			$db->setQuery($query);
			$cids 	= $db->loadResultArray(0);
		} else {
			$query	= 'UPDATE #__customtables_options SET sublevel = '.(int) $level
					.' WHERE parentid IN ('.$ids.')';
			$db->setQuery( $query );
			$db->query();
			$query	= 'SELECT id FROM #__customtables_options WHERE parentid IN ('.$ids.')';
			$db->setQuery( $query );
			$cids 	= $db->loadResultArray( 0 );
		}
		if (!empty( $cids )) {
			$this->_rebuildSubLevel( $cids, $level + 1 );
		}
	}




	function GetNewParentID($parentid,&$AssociatedTable)
	{
		foreach($AssociatedTable as $Ass)
		{
			echo $Ass[0].':'.$Ass[1].'  looking for: '.$parentid.'<br/>';
			if($Ass[0]==$parentid)
				return $Ass[1];
		}
		return -1;
	}



	function getAllRootParents()
	{
		$db = JFactory::getDBO();

		$query = "SELECT id, optionname FROM #__customtables_options WHERE parentid=0 ORDER BY optionname";
		$db->setQuery( $query );
		$available_rootparents = $db->loadObjectList();
		$this->array_insert($available_rootparents,array("id" => 0, "optionname" => '-'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT_PARENT' )),0);
		return $available_rootparents;

	}
	function array_insert(&$array, $insert, $position = -1)
	{
	    $position = ($position == -1) ? (count($array)) : $position ;
	    if($position != (count($array)))
		{
			$ta = $array;
			for($i = $position; $i < (count($array)); $i++)
			{
               if(!isset($array[$i]))
			   {
                    die("Invalid array: All keys must be numerical and in sequence.");
               }
               $tmp[$i+1] = $array[$i];
               unset($ta[$i]);
			}
			$ta[$position] = $insert;
			$array = $ta + $tmp;

	    }
		else
		{
	         $array[$position] = $insert;
	    }
	    ksort($array);
	    return true;
	}

	function copyItem($cid)
	{

	    $item = $this->getTable();


	    foreach( $cid as $id )
	    {

		$item->load( $id );
		$item->id 	= NULL;
		$item->optionname 	= 'Copy of '.$item->optionname;



		if (!$item->check()) {
			JFactory::getApplication()->enqueueMessage($item->getError(), 'error');
		}

		if (!$item->store()) {
			JFactory::getApplication()->enqueueMessage($item->getError(), 'error');
		}
		$item->checkin();

	    }
	    return true;
	}


}
