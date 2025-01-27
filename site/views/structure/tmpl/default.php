<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');


	echo '<h5>'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FOUND').': '.$this->record_count.' '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RESULT_S').'</h5>';

	echo '<form action="" method="post" name="escatalogform" id="escatalogform">
		<input type="hidden" name="option" value="com_customtables" />
		<input type="hidden" name="view" value="structure" />
        <input type="hidden" name="task" id="task" value="" />
        ';

		if($this->record_count > 5)
		{
		echo '
	<table cellpadding=0 cellspacing=0 width="100%" >
        <tr height=30>
                <td width="140" valign="top">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SHOW').': '.$this->pagination->getLimitBox("").'</td>
                <td align="center" valign="top">'.$this->pagination->getPagesLinks("").'<br/></td>
                <td width="140" valign="top"></td>
        </tr>
    </table>
	<hr>
		';
		}
		
	$catalogresult='<table width="100%">';
	$Itemid=$this->ct->Env->jinput->getInt('Itemid',  0);

    $tr=0;
	$number_of_columns=3;

	$content_width=100;
	$column_width=floor($content_width/$number_of_columns);
	$aLink='index.php?option=com_customtables&view=catalog&Itemid='.$Itemid.'&essearchbar=true&establename='.$this->ct->Table->tablename;

    foreach($this->rows as $row)
    {
		if($tr==0)
		$catalogresult.='<tr>';

        $catalogresult.='<td width="'.$column_width.'%" valign="top" align="left">';

		if($this->linkable)
			$catalogresult.='<a href="'.$aLink.'&es_'.$this->esfieldname.'_1='.$row['optionname'].'">'.$row['optiontitle'].'</a>';
		else
			$catalogresult.=$row[optiontitle].'';

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

    $catalogresult.='</tbody>

    </table>';

	echo LayoutProcessor::applyContentPlugins($catalogresult);

	if($this->record_count > 5)
	{
		echo '<p></p>
		<hr>
			<table cellpadding="0" cellspacing="0" width="100%" >
				<tbody>
					<tr height="30">
						<td align="center" valign="top">'.$this->pagination->getPagesLinks("").'<br/></td>
					</tr>
				</tbody>
			</table>
		';
	}

    echo '</form>';
