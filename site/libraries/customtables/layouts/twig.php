<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;
 
// no direct access
defined('_JEXEC') or die('Restricted access');

use \LayoutProcessor;
use \JoomlaBasicMisc;
use \Joomla\CMS\Factory;
use \CustomTables\Twig_Field_Tags;
use \CustomTables\Forms;

class TwigProcessor
{
	var $ct;
	var $loaded = false;
	var $twig;
	var $variables = [];
	var $recordBlockFound;
	var $recordBlockreplaceCode;

	public function __construct(&$ct, $htmlresult_)
	{
		$this->ct = $ct;
		
		$tag1 = '{% block record %}';
		$pos1 = strpos($htmlresult_,$tag1);
		
		if($pos1 !== false)
		{
			$this->recordBlockFound = true;
			
			$tag2 = '{% endblock %}';
			
			$pos2 = strpos($htmlresult_,$tag2,$pos1 + strlen($tag1));
			if($pos1 === false)
			{
				Factory::getApplication()->enqueueMessage('{% endblock %} is missing', 'error');
				return '';
			}
			
			$tag1_length = strlen($tag1);
			$record_block = substr($htmlresult_,$pos1+$tag1_length,$pos2-$pos1-$tag1_length);
			$record_block_replace = substr($htmlresult_,$pos1,$pos2-$pos1+strlen($tag2));
			
			$this->recordBlockreplaceCode=JoomlaBasicMisc::generateRandomString();//this is temporary replace place holder. to not parse content result again
			
			$htmlresult = str_replace($record_block_replace,$this->recordBlockreplaceCode,$htmlresult_);
						
			$loader = new \Twig\Loader\ArrayLoader([
				'index' => '{% autoescape false %}'.$htmlresult.'{% endautoescape %}',
				'record' => '{% autoescape false %}'.$record_block.'{% endautoescape %}',
			]);
		}
		else
		{
			$this->recordBlockFound = false;
			$loader = new \Twig\Loader\ArrayLoader([
				'index' => $htmlresult_,
			]);
		}
	
		$this->twig = new \Twig\Environment($loader);
			
		$this->twig->addGlobal('fields', new Twig_Fields_Tags($this->ct) );
		//{{ fields.count() }}
		//{{ fields.json() }}
		
		$this->twig->addGlobal('user', new Twig_User_Tags($this->ct) );
		//{{ user.name() }}
		//{{ user.username() }}
		//{{ user.email() }}
		//{{ user.id() }}
		//{{ user.lastvisitdate() }}
		//{{ user.registerdate() }}
		//{{ user.usergroups() }}
		
		$this->twig->addGlobal('url', new Twig_Url_Tags($this->ct) );
		//{{ url.link() }}
		//{{ url.base64() }}
		//{{ url.root() }}
		//{{ url.getInt() }}
		//{{ url.getString() }}
		//{{ url.getUInt() }}
		//{{ url.getFloat() }}
		//{{ url.getWord() }}
		//{{ url.getAlnum() }}
		//{{ url.getCmd() }}
		//{{ url.getStringAndEncode() }}
		//{{ url.getStringAndDecode() }}
		//{{ url.Itemid() }}
		//{{ url.set() }}
		//{{ url.server() }}
		
		$this->twig->addGlobal('html', new Twig_Html_Tags($this->ct) );
		//{{ html.add() }}
		//{{ html.batch() }}
		//{{ html.button() }}
		//{{ html.captcha() }}
		//{{ html.format() }}
		//{{ html.goback() }}
		//{{ html.importcsv() }}
		//{{ html.layout("InvoicesPage","price>100","name",20) }}
		//{{ html.limit() }}
		//{{ html.message() }}
		//{{ html.navigation() }}
		//{{ html.orderby() }}
		//{{ html.pagination() }}
		//{{ html.print() }}
		//{{ html.recordcount }}
		//{{ html.records("InvoicesPage","price>100","name",20) }}
		//{{ html.search() }}
		//{{ html.searchbutton() }}

		$this->twig->addGlobal('document', new Twig_Document_Tags($this->ct) );
		//{{ document.setMetaKeywords() }}
		//{{ document.setMetaDescription() }}
		//{{ document.setPageTitle() }}
		//{{ document.setHeadTag() }}
		//{{ document.layout() }} ?????
		//{{ document.sitename() }}
		//{{ document.language_postfix() }}
		
		$this->twig->addGlobal('record', new Twig_Record_Tags($this->ct) );
		//{{ record.advancedjoin(function, tablename, field_findwhat, field_lookwhere, field_readvalue, additional_where, order_by_option, value_option_list) }}
		//{{ record.count(join_table) }}
		//{{ record.id }}
		//{{ record.number }}
		//{{ record.published }}
		//{{ record.sum(join_table,value_field_name) }}
		//{{ record.tablejoin("InvoicesPage","_published=1","name") }}
		//{{ record.valuejoin(join_table,value_field_name) }}
		
		$this->twig->addGlobal('records', new Twig_Records_Tags($this->ct) );
		//{{ records.count }}
		//{{ records.list }}
		//{{ records.list("InvoicesItems") }}
		//{{ records.htmltable([['column_1_title','column_1_value'],['column_1_title','column_1_value']]) }}
		
		
		$this->twig->addGlobal('text', new Twig_Text_Tags($this->ct) );
		//{{ text.base64encode() }}
		
		$this->variables = [];
		
		//{{ table.id }}
		//{{ table.name }}
		//{{ table.title }}
		//{{ table.description }}
		//{{ table.records }} same as {{ records.count }}
		//{{ table.fields }} same as {{ fields.count() }}
		if(isset($ct->Table))
		{
			$description = $ct->Table->tablerow['description'.$this->ct->Table->Languages->Postfix];
						
			$this->variables['table'] = [
			'id'=>$this->ct->Table->tableid,
			'name' => $this->ct->Table->tablename,
			'title' => $this->ct->Table->tabletitle,
			'description'=> new \Twig\Markup($description, 'UTF-8' ),
			'records'=>$this->ct->Table->recordcount,
			'fields'=>count($this->ct->Table->fields)
			];
		}

		if(isset($this->ct->Table->fields))
		{
			$index=0;
			foreach($this->ct->Table->fields as $field)
			{
	
				$function = new \Twig\TwigFunction($field['fieldname'], function () use (&$ct, $index) 
				{
					//This function will process record values with field typeparams and with optional arguments
					//Example:
					//{{ price }}  - will return 35896.14 if field type parameter is 2,20 (2 decimals)
					//{{ price(3,",") }}  - will return 35,896.140 if field type parameter is 2,20 (2 decimals) but extra 0 added
					
					$args = func_get_args();	
					
					
					$valueProcessor = new Value($this->ct);
					$vlu = strval($valueProcessor->renderValue($this->ct->Table->fields[$index],$this->ct->Table->record,$args));
					return $vlu;
					//return new \Twig\Markup($vlu, 'UTF-8' ); //doesnt work because it cannot be converted to int or string
				});
				
				$this->twig->addFunction($function);
			
				$this->variables[$field['fieldname']] = new fieldObject($this->ct,$field);
				
				$index++;
			}
		}
	}
	
	public function process($row = null)
	{
		if($row !== null)
			$this->ct->Table->record = $row;
		
		$result = @$this->twig->render('index', $this->variables);
		
		if($this->recordBlockFound)
		{
			$number = 0;
			$record_result = '';
			foreach($this->ct->Records as $row)
			{
				$row['_number'] = $number;
				$this->ct->Table->record = $row;
				$record_result .= @$this->twig->render('record', $this->variables);
				$number++;
			}
		
			return str_replace($this->recordBlockreplaceCode,$record_result,$result);
		}
		
		return $result;
	}
}

class fieldObject
{
	var $ct;
	var $field;

	function __construct(&$ct, &$field)
	{
		$this->ct = $ct;
		$this->field = $field;
	}
	
	public function __toString()
    {
		//$args = func_get_args();
		$valueProcessor = new Value($this->ct);
		$vlu = $valueProcessor->renderValue($this->field,$this->ct->Table->record,[]);
		//return $vlu;
		return strval($vlu);
		//return new \Twig\Markup($vlu, 'UTF-8' ); //doesnt work because it cannot be converted to int or string
		//return strval(new \Twig\Markup($vlu, 'UTF-8'));
    }
	
	public function __call($name, $arguments)
    {
		//if($name == 'title')
			//return $this->title();
		
		if($name == 'edit')
		{
			return 'object:'.$name.':['.$arguments[0].']';
		}
		
		//for jsl join fields
        return 'unknown';
    }
	
	public function fieldname()
    {
        return $this->field['fieldname'];
    }
	
	public function v()
    {
		return $this->value();
	}
	
	public function value()
    {
		$rfn = $this->field['realfieldname'];
		return $this->ct->Table->record[$rfn];
	}
	
	public function t()
    {
		return $this->title();
	}
	
	public function title()
    {
		if(!array_key_exists('fieldtitle'.$this->ct->Languages->Postfix,$this->field))
		{
			Factory::getApplication()->enqueueMessage(
					JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
                                        
            return '*fieldtitle'.$this->ct->Languages->Postfix.' - not found*';
		}
        else
			return $this->field['fieldtitle'.$this->ct->Languages->Postfix];
    }
	
	public function label()
    {
		$forms = new Forms($this->ct);
        $vlu = $forms->renderFieldLabel($this->field);
		return new \Twig\Markup($vlu, 'UTF-8' );
    }

	public function description()
    {
		if(!array_key_exists('description'.$this->ct->Languages->Postfix,$this->field))
			$vlu = $this->field['description'];
        else
			$vlu = $this->field['description'.$this->ct->Languages->Postfix];
		
		return new \Twig\Markup($vlu, 'UTF-8' );
    }
	
	public function type()
    {
        return $this->field['type'];
    }
	
	public function params()
    {
        return $this->field['typeparams'];
    }
	
	/*
	public function count()
    {
        return 123;
    }
	*/
	
	public function edit()
    {
		$args = func_get_args();
		
		$value = '';
		if($this->field['type']!='multilangstring' and $this->field['type']!='multilangtext' and $this->field['type']!='multilangarticle')
		{
			$rfn = $this->field['realfieldname'];
			$value = isset($this->ct->Table->record[$rfn]) ? $this->ct->Table->record[$rfn] : null;
		}
		
		if($this->ct->isEditForm)
		{
			$Inputbox = new Inputbox($this->ct, $this->field, $args);
			return new \Twig\Markup($Inputbox->render($value, $this->ct->Table->record), 'UTF-8' );
		}
		else
		{
			$postfix='';
            $ajax_prefix = 'com_'.$this->ct->Table->record['listing_id'].'_';//example: com_153_es_fieldname or com_153_ct_fieldname

			if($this->field['type']=='multilangstring')
			{
				if(isset($args[4]))
				{
					//multilang field specific language
                    $firstlanguage=true;
                    foreach($this->ct->Languages->LanguageList as $lang)
					{
						if($lang->sef==$value_option_list[4])
                        {
							$postfix=$lang->sef;
                            break;
						}
                    }
				}
			}
			
			//Deafult style (borderless)
			if(isset($args[0]) or $args[0] != '')
			{
				$class_str = $args[0];
				
				if(strpos($class_str,':')!==false)//its a style, change it to attribute
					$div_arg=' style="'.$class_str.'"';
				else
					$div_arg=' class="'.$class_str.'"';
			}
			else
				$div_arg = '';

			// Default attribute - action to save the value
			$args[0] = 'border:none !important;width:auto;box-shadow:none;';
			
			$onchange='ct_UpdateSingleValue(\''.$this->ct->Env->WebsiteRoot.'\','.$this->ct->Env->Itemid.',\''
				.$this->field['fieldname'].'\','.$this->ct->Table->record['listing_id'].',\''.$postfix.'\');';

            //$attributes='onchange="'.$onchange.'"'.$style;

			if(isset($value_option_list[1]))
				$args[1] .= $value_option_list[1];//' '.$attributes;
			//else
				//$args[1] = $attributes;

			$Inputbox = new Inputbox($this->ct, $this->field, $args, true, $onchange);
			
			$edit_box = '<div'.$div_arg.' id="'.$ajax_prefix.$this->field['fieldname'].$postfix.'_div">'
                            .$Inputbox->render($value, $this->ct->Table->record)
						.'</div>';
			
			return new \Twig\Markup($edit_box, 'UTF-8' );
		}
    }
}
