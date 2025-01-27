<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\DataTypes\Tree;
    
JHTML::stylesheet("default.css", JURI::root(true)."/components/com_customtables/views/catalog/tmpl/");

$catalogresult='<table width="100%" align="center">';
		
$tr=0;
$number_of_columns = 3;
$content_width=100;
$column_width=floor($content_width/$number_of_columns);
		
$imagemethods=new CustomTablesImageMethods;
		
$image_prefix='_esthumb';
$imageparams='';
if($this->image_prefix=='_original')
{
	$image_prefix='_original';
}
else
{
	if(count($this->rows)>0)
	{
		$row=$this->rows[0];
		$imageparams=Tree::getHeritageInfo($row['parentid'], 'imageparams');
				
		$cleanOptions=$imagemethods->getCustomImageOptions($imageparams);
				
		if(count($cleanOptions)>0)
		{
			foreach($cleanOptions as $imgSize)
			{
				if($this->image_prefix==$imgSize[0])
					$image_prefix=$imgSize[0];
			}
		}
	}
}

foreach($this->rows as $row)
{
	if($tr==0)
		$catalogresult.='<tr>';
		
	$imagefile_='images/esoptimages/'.$image_prefix.'_'.$row['image'];
					
	if(file_exists($imagefile_.'.jpg'))
		$imagefile=$imagefile_.'.jpg';
	elseif(file_exists($imagefile_.'.png'))
		$imagefile=$imagefile_.'.png';
	elseif(file_exists($imagefile_.'.webp'))
		$imagefile=$imagefile_.'.webp';
	else
		$imagefile='';
		
	if($imagefile!='')
	{
		$catalogresult.='<td width="'.$column_width.'%" valign="top" align="center">';

		if($this->esfieldname!='')
		{
			$aLink='index.php?option=com_customtables&view=catalog&';
								
			if($ct->Env->menu_params->get( 'layout' )!='')
				$aLink.='layout='.$ct->Env->menu_params->get( 'layout' ).'&';
								
								
			if($ct->Env->menu_params->get( 'itemid' )!='')
				$aLink.='Itemid='.$ct->Env->menu_params->get( 'itemid' ).'&';
			else
				$aLink.='Itemid='.$ct->Env->jinput->getInt('Itemid',  0).'&';
							
			$aLink.='&establename='.$this->ct->Table->tablename;
			$aLink.='&filter='.$this->esfieldname.urlencode('=').$this->optionname;
								
			if($row['optionname']!='')
				$aLink.='.'.$row['optionname'];
							
			$catalogresult.='<a href="'.$aLink.'"><img src="'.$imagefile.'" border="0" /></a>';
		}
		else
			$catalogresult.='<img src="'.$imagefile.'" border="0" />';
        
		$catalogresult.='</td>';
				
		$tr++;
						
		if($tr==$number_of_columns)
		{
			$catalogresult.='</tr>';
						
			if($this->row_break)
				$catalogresult.='<tr><td colspan="'.$number_of_columns.'"><hr /></td></tr>';

			$tr	=0;
		}
	}
}
  
$catalogresult.='</tbody>
</table>';
		
echo LayoutProcessor::applyContentPlugins($catalogresult);
 