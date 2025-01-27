<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;

use \Joomla\CMS\Component\ComponentHelper;

jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'tables');

class CustomTablesModelEditPhotos extends JModelLegacy
{
	var $ct;

	var $imagemethods;

	var $listing_id;
	var $Listing_Title;

	var $galleryname;

	var $GalleryTitle;
	var $GalleryParams;

	var $imagefolderword;
	var $imagefolder;
	var $imagefolderweb;
	var $imagemainprefix;
	var $maxfilesize;

	var $phototablename;

	function __construct()
	{
		$this->ct = new CT;
		
		$params = ComponentHelper::getParams( 'com_customtables' );

		$this->maxfilesize=JoomlaBasicMisc::file_upload_max_size();

		$this->imagefolderword='esimages';
		$this->imagefolderweb='images/esimages';
		$this->imagefolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'esimages';

		$this->imagemainprefix='g';
		$this->imagemethods=new CustomTablesImageMethods;

		$this->ct->getTable($this->getParam_safe( 'establename' ), $this->useridfield);
				
		if($this->ct->Table->tablename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected (62).', 'error');
			return;
		}

		$this->listing_id=JFactory::getApplication()->input->getInt('listing_id', 0);
		if(!JFactory::getApplication()->input->getCmd('galleryname'))
			return false;

		$this->galleryname=JFactory::getApplication()->input->getCmd('galleryname');

		if(!$this->getGallery())
			return false;

		$this->getObject();

		$this->phototablename='#__customtables_gallery_'.$this->ct->Table->tablename.'_'.$this->galleryname;

		parent::__construct();
	}


	function getPhotoList()
	{
		// get database handle
		$db = JFactory::getDBO();

		$query = 'SELECT ordering, photoid,  title'.$this->ct->Languages->Postfix.' AS title, photo_ext FROM '.$this->phototablename
			.' WHERE listingid='.$this->listing_id.' ORDER BY ordering, photoid';

		$db->setQuery($query);

		$rows=$db->loadObjectList();

		return $rows;
	}

	function getGallery()
	{
		$db = JFactory::getDBO();
		$query = 'SELECT id, fieldtitle'.$this->ct->Languages->Postfix.' AS title,typeparams FROM #__customtables_fields WHERE published=1 AND tableid='
			.$this->ct->Table->tableid.' AND  fieldname="'.$this->galleryname.'" AND type="imagegallery" LIMIT 1';

		$db->setQuery($query);

		$rows=$db->loadObjectList();

		if(count($rows)!=1)
			return false;

		$row=$rows[0];

		$this->GalleryTitle=$row->title;

		$this->imagefolderword=CustomTablesImageMethods::getImageFolder($row->typeparams);

		$this->imagefolderweb=$this->imagefolderword;
		
		$this->imagefolder=JPATH_SITE;
		if($this->imagefolder[strlen($this->imagefolder)-1]!='/' and $this->imagefolderword[0]!='/')
		$this->imagefolder.='/';
		$this->imagefolder.=str_replace('/',DIRECTORY_SEPARATOR,$this->imagefolderword);
		//Create folder if not exists
		if (!file_exists($this->imagefolder))
		{
			JFactory::getApplication()->enqueueMessage('Path '.$this->imagefolder.' not found.', 'error');
			mkdir($this->imagefolder, 0755, true);
		}
			
		$this->GalleryParams=$row->typeparams;

		return true;
	}

	function getObject()
	{
		$db = JFactory::getDBO();
		$query = 'SELECT * FROM #__customtables_table_'.$this->ct->Table->tablename.' WHERE id='.$this->listing_id.' LIMIT 1';

		$db->setQuery($query);

		$rows = $db->loadAssocList();

		if(count($rows)!=1)
			return false;

		$row=$rows[0];

		$this->Listing_Title='';

		foreach($this->ct->Table->fields as $mFld)
		{
			$titlefield=$mFld['realfieldname'];
			if(!(strpos($mFld['type'],'multi')===false))
				$titlefield.=$this->ct->Languages->Postfix;

			if($row[$titlefield]!='')
			{
				$this->Listing_Title=$row[$titlefield];
				break;
			}
		}
	}

	function reorder()
	{
		$images=$this->getPhotoList();

		//Set order

		//Apply Main Photo
		//Get New Ordering
		$Mainphoto= JFactory::getApplication()->input->getInt('esphotomain');

		foreach($images as $image)
		{
			$image->ordering=abs(JFactory::getApplication()->input->getInt( 'esphotoorder'.$image->photoid,0));
			if($Mainphoto==$image->photoid)
				$image->ordering=-1;
		}

		//Increase all if main
		$nonegative=true;
		do
		{
			$nonegative=true;
			foreach($images as $image)
			{
				if($image->ordering==-1)
					$nonegative=false;
			}

			if(!$nonegative)
			{
				foreach($images as $image)
					$image->ordering++;
			}

		}while(!$nonegative);

		$db = JFactory::getDBO();

		asort  (  $images );
		$i=0;
		foreach($images as $image)
		{
			$safetitle=JFactory::getApplication()->input->getString('esphototitle'.$image->photoid);
			$safetitle=str_replace('"',"",$safetitle);

			$query = 'UPDATE '.$this->phototablename.' SET ordering='.$i.', title'.$this->ct->Languages->Postfix.'="'.$safetitle.'" WHERE listingid='
				.$this->listing_id.' AND photoid='.$image->photoid;

			$db->setQuery($query);
			$db->execute();	

			$i++;
		}
		return true;
	}
	function AutoReorderPhotos()
	{
		$images=$this->getPhotoList();

		$db = JFactory::getDBO();

		asort  (  $images );
		$i=0;
		foreach($images as $image)
		{
			$safetitle=JFactory::getApplication()->input->getString('esphototitle'.$image->photoid);
			$safetitle=str_replace('"',"",$safetitle);

			$query = 'UPDATE '.$this->phototablename.' SET ordering='.$i.', title'.$this->ct->Languages->Postfix.'="'.$safetitle.'" WHERE listingid='
				.$this->listing_id.' AND photoid='.$image->photoid;
				
			$db->setQuery($query);
			$db->execute();	
			$i++;
		}


		return true;
	}

	function delete()
	{
		$db = JFactory::getDBO();

		$photoids=JFactory::getApplication()->input->getString('photoids','');
		$photo_arr=explode('*',$photoids);

		foreach($photo_arr as $photoid)
		{
			if($photoid!='')
			{
				$this->imagemethods->DeleteExistingGalleryImage($this->imagefolder,$this->imagemainprefix, $this->ct->Table->tableid, $this->galleryname,
					$photoid,$this->GalleryParams,true);

				$query = 'DELETE FROM '.$this->phototablename.' WHERE listingid='.$this->listing_id.' AND photoid='.$photoid;
				$db->setQuery($query);
				$db->execute();
			}
		}

		$this->ct->Table->saveLog($this->listing_id,7);
		return true;
	}

	function add()
	{
		$jinputfile = JFactory::getApplication()->input->files;
		$file = $jinputfile->files->get('uploadedfile');

		$uploadedfile= "tmp/".basename( $file['name']);
		if(!move_uploaded_file($file['tmp_name'], $uploadedfile))
			return false;

		if(JFactory::getApplication()->input->getCmd( 'base64ecnoded', '')=="true")
		{
			$src = $uploadedfile;
			$dst = "tmp/decoded_".basename( $file['name']);
			$this->base64file_decode( $src, $dst );
			$uploadedfile=$dst;
		}

		//Check file
		if(!$this->imagemethods->CheckImage($uploadedfile,JoomlaBasicMisc::file_upload_max_size()))//$this->maxfilesize
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_BROKEN_IMAGE'), 'error');
			unlink($uploadedfile);
			return false;
		}

		//Save to DB
		$photo_ext=$this->imagemethods->FileExtenssion($uploadedfile);
		$photoid=$this->addPhotoRecord($photo_ext);

		$isOk=true;

		//es Thumb
		$newfilename=$this->imagefolder.DIRECTORY_SEPARATOR.$this->imagemainprefix.$this->ct->Table->tableid.'_'.$this->galleryname.'__esthumb_'.$photoid.".jpg";
		$r=$this->imagemethods->ProportionalResize($uploadedfile,$newfilename, 150, 150,1,true, -1, '');

		if($r!=1)
			$isOk=false;

		$customsizes=$this->imagemethods->getCustomImageOptions($this->GalleryParams);

		foreach($customsizes as $imagesize)
		{
			$prefix=$imagesize[0];
			$width=(int)$imagesize[1];
			$height=(int)$imagesize[2];
			$color=(int)$imagesize[3];

			//save as extention
			if($imagesize[4]!='')
				$ext=$imagesize[4];
			else
				$ext=$photo_ext;

			$newfilename=$this->imagefolder.DIRECTORY_SEPARATOR.$this->imagemainprefix.$this->ct->Table->tableid.'_'.$this->galleryname.'_'.$prefix.'_'.$photoid.".".$ext;
			$r=$this->imagemethods->ProportionalResize($uploadedfile,$newfilename, $width, $height,1,true, $color, '');

			if($r!=1)
				$isOk=false;

		}

		if($isOk)
		{
			$originalname=$this->imagemainprefix.$this->ct->Table->tableid.'_'.$this->galleryname.'__original_'.$photoid.".".$photo_ext;

			if (!copy($uploadedfile, $this->imagefolder.DIRECTORY_SEPARATOR.$originalname))
			{
				unlink($uploadedfile);
				return false;
			}
		}
		else
		{
			unlink($uploadedfile);
			return false;
		}

		unlink($uploadedfile);

		$this->ct->Table->saveLog($this->listing_id,6);

		return true;
	}

	function base64file_decode( $inputfile, $outputfile ) {
  /* read data (binary) */
  $ifp = fopen( $inputfile, "rb" );
  $srcData = fread( $ifp, filesize( $inputfile ) );
  fclose( $ifp );
  /* encode & write data (binary) */
  $ifp = fopen( $outputfile, "wb" );
  fwrite( $ifp, base64_decode( $srcData ) );
  fclose( $ifp );
  /* return output filename */
  return( $outputfile );
}

	function addPhotoRecord($photo_ext)
	{
		$db = JFactory::getDBO();

		$query = 'INSERT '.$this->phototablename.' SET '
				.'ordering=100, '
				.'photo_ext="'.$photo_ext.'", '
				.'listingid='.$this->listing_id;

		$db->setQuery( $query );
		$db->execute();

		$this->AutoReorderPhotos();


		$query =' SELECT photoid FROM '.$this->phototablename.' WHERE listingid='.$this->listing_id.' ORDER BY photoid DESC LIMIT 1';
		$db->setQuery( $query );

		$espropertytype= $db->loadObjectList();
		if(count($espropertytype)==1)
		{
			return $espropertytype[0]->photoid;
		}

		return -1;
	}

	function DoAutoResize($uploadedfile,$folder_resized,$image_width,$image_height,$photoid,$fileext)
	{
		if(!file_exists($uploadedfile))
			return false;

		$newfilename=$folder_resized.$photoid.'.'.$fileext;

		//hexdec ("#323131")
		$r=$this->imagemethods->ProportionalResize($uploadedfile, $newfilename, $image_width, $image_height,1,true, -1, '');
		if($r!=1)
			return false;

		return true;
	}
}
