<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use CustomTables\RecordToolbar;
use CustomTables\CTUser;

/* Not all tags are implemented using Twig

Implemented:

{id} - {{ record.id }}
{number} - {{ record.number }}
{_value:published} - {{ published }}
{published:number} - {{ published }}
{published:boolean} - {{ published('bool') }} or {{ published('boolean') }}
{published} - {{ published('yesno') }}
new: {{ published('Yes','No') }} Example: {{ published('Record is published','Not published') }}
{link} - {{ record.link(add_returnto = true, menu_item_alias='', returnto='') }}
{linknoreturn} - {{ record.link(add_returnto = false, menu_item_alias='') }}
{server} - {{ url.server() }}}

Not yet implemented:

{sqljoin} - not yet

shopping cart
tree


tagProcessor_Item::GetSQLJoin($ct,$htmlresult,$row['listing_id']);
tagProcessor_Item::GetCustomToolBar($ct,$htmlresult,$row);
CT_FieldTypeTag_ct::ResolveStructure($ct,$htmlresult);

*/
 
use \CustomTables\Twig_Record_Tags;

class tagProcessor_Item
{
    public static function process(&$ct,&$row,&$htmlresult,$aLink,$add_label=false)
	{
		if($ct->Table == null)
			return false;
		
		if($row !== null)
			$ct->Table->record = $row;
		
		$ct_record = new Twig_Record_Tags($ct);
		
        tagProcessor_Item::processLink($ct_record,$row,$htmlresult); //Twig version added - original replaced
		tagProcessor_Item::processNoReturnLink($ct_record,$row,$htmlresult); //Twig version added - original replaced

		tagProcessor_Field::process($ct,$htmlresult,$add_label); //Twig version added - original not changed

		if($ct->Env->advancedtagprocessor)
			tagProcessor_Server::process($ct_url, $htmlresult); //Twig version added - original not changed

		tagProcessor_Shopping::getShoppingCartLink($ct,$htmlresult,$row);

		//Listing ID
		$listing_id = 0;

		if(isset($row) and isset($row['listing_id']))
			$listing_id = (int)$row['listing_id'];
			
		$htmlresult=str_replace('{id}',$listing_id,$htmlresult); //Twig version added - original not changed
		$htmlresult=str_replace('{number}',(isset($row['_number']) ? $row['_number'] : ''),$htmlresult); //Twig version added - original not changed

		if(isset($row) and isset($row['listing_published']))
			tagProcessor_Item::processPublishStatus($row,$htmlresult); //Twig version added - original not changed

		if(isset($row) and isset($row['listing_published']))
			tagProcessor_Item::GetSQLJoin($ct_record,$htmlresult);

		if(isset($row) and isset($row['listing_published']))
			tagProcessor_Item::GetCustomToolBar($ct,$htmlresult,$row);

		CT_FieldTypeTag_ct::ResolveStructure($ct,$htmlresult);
	}

    protected static function GetSQLJoin($ct_record,&$htmlresult)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('sqljoin',$options,$htmlresult,'{}');
		if(count($fList)==0)
			return;

		$db = JFactory::getDBO();
		$i=0;
		foreach($fList as $fItem)
		{
			$opts=JoomlaBasicMisc::csv_explode(',', $options[$i], '"', false);

			if(count($opts)>=5) //dont even try if less than 5 parameters
			{
				$field2_type='';
				$order_by_option='';

				$isOk=true;

				$sj_function=$opts[0];
				$sj_tablename=$opts[1];
				$field1_findwhat=$opts[2];
				$field2_lookwhere=$opts[3];
				
				$opt4_pair=JoomlaBasicMisc::csv_explode(':', $opts[4], '"', false);
				$FieldName=$opt4_pair[0]; //The field to get value from
				if(isset($opt4_pair[1])) //Custom parameters
				{
					$field_option=$opt4_pair[1];
					$value_option_list=explode(',',$field_option);
				}
				else
				{
					$field_option = '';
					$value_option_list = [];
				}

				$field3_readvalue=$FieldName;
				
				$additional_where = $opts[5] ?? '';
				$order_by_option = $opts[6] ?? '';
				
				$vlu = $ct_record->join($sj_function, $sj_tablename, $field1_findwhat, $field2_lookwhere, $field3_readvalue, $additional_where, $order_by_option, $value_option_list);

				$htmlresult=str_replace($fItem,$vlu,$htmlresult);
				$i++;
			}//if(count($opts)=5)
		}//foreach($fList as $fItem)
	}//function GetSQLJoin(&$htmlresult)

	protected static function processPublishStatus(&$row,&$htmlresult)
	{
		$htmlresult=str_replace('{_value:published}',$row['listing_published']==1,$htmlresult);

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('published',$options,$htmlresult,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
			$vlu='';
			if($options[$i]=='number')
				$vlu = (int)$row['listing_published'];
			elseif($options[$i]=='boolean')
				$vlu = $row['listing_published']==1 ? 'true' : 'false';
			else
				$vlu = $row['listing_published']==1 ? JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES') : JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');
			
			$htmlresult=str_replace($fItem,$vlu,$htmlresult);
			
			$i++;
		}
	}

    protected static function GetCustomToolBar(&$ct,&$htmlresult,&$row)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('toolbar',$options,$htmlresult,'{}');
		
		if(count($fList) == 0)
			return;
		
		
		$edit_userGroup=(int)$ct->Env->menu_params->get( 'editusergroups' );
		$publish_userGroup=(int)$ct->Env->menu_params->get( 'publishusergroups' );
		if($publish_userGroup==0)
			$publish_userGroup=$edit_userGroup;

		$delete_userGroup=(int)$ct->Env->menu_params->get( 'deleteusergroups' );
		if($delete_userGroup==0)
			$delete_userGroup=$edit_userGroup;
		
		$isEditable=CTUser::checkIfRecordBelongsToUser($ct,$edit_userGroup);
		$isPublishable=CTUser::checkIfRecordBelongsToUser($ct,$publish_userGroup);
		$isDeletable=CTUser::checkIfRecordBelongsToUser($ct,$delete_userGroup);
		
		$RecordToolbar = new RecordToolbar($ct,$isEditable, $isPublishable, $isDeletable, $ct->Env->Itemid);

		$i=0;
		foreach($fList as $fItem)
		{
			if($ct->Env->print==1)
			{
				$htmlresult=str_replace($fItem,'',$htmlresult);
			}
			else
			{
				$modes = explode(',',$options[$i]);
				if(count($modes)==0 or $options[$i] == '')
					$modes = ['edit','refresh','publish','delete'];

				$icons=[];
				foreach($modes as $mode)
					$icons[] = $RecordToolbar->render($row,$mode);
				
				$vlu = implode('',$icons);
				$htmlresult=str_replace($fItem,$vlu,$htmlresult);
			}
			
			$i++;
		}
	}

    protected static function processNoReturnLink(&$ct_record,&$row,&$pagelayout)
	{
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('linknoreturn',$options,$pagelayout,'{}',':','"');

		$i=0;

		foreach($fList as $fItem)
		{
			$vlu = $ct_record->link(false,$options[$i]);

            $pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
        }
    }
	
	protected static function processLink(&$ct_record,&$row,&$pagelayout)
	{
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace('link',$options,$pagelayout,'{}',':','"');

		$i=0;

		foreach($fList as $fItem)
		{
			$vlu = $ct_record->link(true,$options[$i]);

            $pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
        }
    }
	
    public static function RenderResultLine(&$ct, &$twig, &$row)
    {
		if($ct->Env->print)
			$viewlink='';
		else
		{
            $returnto = $ct->Env->current_url.'#a'.$row['listing_id'];
			
			if($row !== null)
				$ct->Table->record = $row;
		
			$ct_record = new Twig_Record_Tags($ct);
			
			$viewlink = $ct_record->link(true,'',$returnto);
			
			if($ct->Env->jinput->getCmd('tmpl')!='')
				$viewlink.='&amp;tmpl='.$ct->Env->jinput->getCmd('tmpl');
		}

		$layout='';
		
		$htmlresult = '';

		if($ct->LayoutProc->layoutType==2)
        {
            require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'edittags.php');

			$htmlresult = $twig->process($row);

            $prefix='table_'.$ct->Table->tablename.'_'.$row['listing_id'].'_';
            tagProcessor_Edit::process($ct,$htmlresult,$row,$prefix);//Process edit form layout
            			
			
            $ct->LayoutProc->layout=$htmlresult;//Temporary replace original layout with processed result
			
			$htmlresult=$ct->LayoutProc->fillLayout($row,null,'||',false,true);//Process field values
        }
        else
		{	
			$htmlresult = $twig->process($row);
			$ct->LayoutProc->layout=$htmlresult;//Layout was modified by Twig
	
            $htmlresult = $ct->LayoutProc->fillLayout($row,$viewlink,'[]',false);
		}

		return $htmlresult;
    }
}
