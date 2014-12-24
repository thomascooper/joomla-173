<?php
/**
 * @author JoomlaShine.com Team
 * @copyright JoomlaShine.com
 * @link joomlashine.com
 * @package JSN ImageShow
 * @version $Id$
 * @license GNU/GPL v2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die('Restricted access');
class JSNInternalSourceJoomgallery extends JSNImagesSourcesInternal
{
	function getCategories($config = array('showlist_id' => 0))
	{
		$query 	= "SELECT * FROM #__joomgallery_catg WHERE cid = 1";
		$this->_db->setQuery($query);
		$result = $this->_db->loadObject();

		$xmlObj = new JXMLElement('<node></node>');
		$xmlObj->addAttribute('label', 'Image Category(ies)');
		$xmlObj->addAttribute('data', 'images');
		$xmlObj->addAttribute('type', 'root');

		if ($result) {
			$this->drawXMLCategory($xmlObj, $result);
		}

		return $xmlObj->asFormattedXML();
	}

	function drawXMLCategory($xmlObj, $item)
	{
		$query = 'SELECT * FROM #__joomgallery_catg WHERE lft >= '.$item->lft.' AND rgt <= '.$item->rgt.' ORDER BY lft ASC';
		$this->_db->setQuery($query);
		$categories = $this->_db->loadObjectList();

		if (is_array($categories))
		{
			foreach ($categories as $cate)
			{
				if ($item->level + 1 == $cate->level)
				{
					$node = $xmlObj->addChild('node');
					$node->addAttribute('label', $cate->name);
					$node->addAttribute('data', $cate->cid);
					$node->addAttribute('state', (in_array($cate->cid, $this->_syncAlbum)) ? 'checked' : 'unchecked');

					$this->drawXMLCategory($node, $cate);
				}
			}
		}
	}

	function loadImages($config = array())
	{
		$catID 		= $config['album'];

		$query = 'SELECT *
				  FROM #__joomgallery
				  WHERE catid = '.(int) $catID.'
				  AND published = 1
				  ORDER BY ordering ASC';

		$this->_db->setQuery($query);

		$result 	= $this->_db->loadAssocList();
		$catPath 	= $this->_getJoomgaCatPath($catID);
		$config 	= $this->_getJoomgaConfig();
		$arrayImage = array();

		foreach ($result as $item)
		{
			$realPath 					= str_replace('/', DS,  $config->jg_pathoriginalimages.$catPath.$item['imgfilename']);
			$imageObj 					= new stdClass();
			$imageObj->image_title 		= $item['imgtitle'];
			$imageObj->image_alt_text	= $item['imgtitle'];
			$imageObj->image_extid 		= $item['id'];
			$imageObj->album_extid		= $item['catid'];
			$imageObj->image_small 		= $config->jg_pathimages.$catPath.$item['imgfilename']; //$config->jg_paththumbs.$catPath.$item['imgthumbname'];
			$imageObj->image_medium 	= $config->jg_pathimages.$catPath.$item['imgfilename'];
			$imageObj->image_big 		= $config->jg_pathoriginalimages.$catPath.$item['imgfilename'];
			$imageObj->image_link		= JURI::root().'index.php?option=com_joomgallery&view=detail&id='.$item['id'];
			$imageObj->image_description 	= $item['imgtext'];
			$arrayImage[] = $imageObj;
		}
		$data = new stdClass();
		$data->images 			= $arrayImage;

		return $data;
	}

	function _getJoomgaCatPath($catIDs)
	{
		if (is_array($catIDs) and count($catIDs))
		{
			foreach ($catIDs as $catID) {
				$album [] = $catID['album_extid'];
			}

			$catpath = array();

			$query = "SELECT cid, catpath
					  FROM #__joomgallery_catg
					  WHERE cid IN ('.implode(',',$album).')";

			$this->_db->setQuery($query);
			$catObjs = $this->_db->loadAssocList();

			foreach ($catObjs as $catObj)
			{
				if (empty($catObj['catpath'])) {
					$catpath[$catObj['cid']] = '/';
				} else {
					$catpath[$catObj['cid']] = $catObj['catpath'].'/';
				}
			}
			 return $catpath;
		}
		else
		{
			$catpath = array();

			$query = 'SELECT catpath
					  FROM #__joomgallery_catg
					  WHERE cid = '.(int)$catIDs;

			$this->_db->setQuery($query);
			$catObj = $this->_db->loadObject();

			if (empty($catObj->catpath)) {
				@$catpath[$catIDs] = '/';
			} else {
				@$catpath[$catIDs] = $catObj->catpath.'/';
			}

			return @$catpath[$catIDs];
		}

		return false;
	}

	function _getJoomgaConfig()
	{
		$query = 'SELECT * FROM #__joomgallery_config';
		$this->_db->setQuery($query);
		$config = $this->_db->loadObject();
		return $config;
	}

	function saveImages($config = array())
	{
		parent::saveImages($config);
		$config 		= $this->_data['saveImages'];
		$imgExtIDs 		= $config['imgExtID'];
		if (count($imgExtIDs))
		{
			$objJSNImages    = JSNISFactory::getObj('classes.jsn_is_images');
			$objJSNExif		 = JSNISFactory::getObj('classes.jsn_is_exifinternalsource');
			$ordering 	 	 = $objJSNImages->getMaxOrderingByShowlistID($config['showlistID']);
			$imagesTable     = JTable::getInstance('images', 'Table');

			if (count($ordering) < 0 or is_null($ordering)) {
				$ordering = 1;
			} else {
				$ordering = $ordering[0] + 1;
			}

			$result = false;

			foreach ($imgExtIDs as $imgExtID)
			{
				$realPath 						= str_replace('/', DS, @$config['imgBig'][$imgExtID]);
				$imagesTable->showlist_id 		= $config['showlistID'];
				$imagesTable->image_extid 		= $imgExtID;
				$imagesTable->album_extid 		= $config['albumID'][$imgExtID];
				$imagesTable->image_small 		= $config['imgSmall'][$imgExtID];
				$imagesTable->image_medium 		= $config['imgMedium'][$imgExtID];
				$imagesTable->image_big			= $config['imgBig'][$imgExtID];
				$imagesTable->image_title   	= $config['imgTitle'][$imgExtID];
				if (isset($config['imgAltText'][$imgExtID]))
				{	
					$imagesTable->image_alt_text   	= $config['imgAltText'][$imgExtID];
				}
				$imagesTable->image_description = $config['imgDescription'][$imgExtID];
				$imagesTable->image_link 		= $config['imgLink'][$imgExtID];
				$imagesTable->ordering			= $ordering;
				$imagesTable->custom_data 		= $config['customData'][$imgExtID];
				$imagesTable->image_size 		= @$imageSize;
				$imagesTable->exif_data 		= $objJSNExif->renderData($realPath);
				$result = $imagesTable->store(array('encodeURL' => false));
				$imagesTable->image_id = null;

				$ordering ++;
			}

			if ($result) {
				return true;
			}

			return false;
		}
		return false;
	}

	function getSyncImages($config = array())
	{
		$config 		= array_merge(array('limitEdition' => true), $config);
		$objJSNUtils 	= JSNISFactory::getObj('classes.jsn_is_utils');

		$query = 'SELECT j.*
				  FROM #__imageshow_images as i
				  INNER JOIN #__joomgallery as j ON j.catid = i.album_extid
				  INNER JOIN #__imageshow_showlist as sl ON sl.showlist_id = i.showlist_id
				  WHERE i.sync = 1
				  AND sl.published = 1
				  AND i.showlist_id = '.(int)$config['showlist_id'] .'
				  GROUP BY j.id
				  ORDER BY j.catid DESC, j.ordering ASC';

		$this->_db->setQuery($query);

		$result  		= $this->_db->loadObjectList();
		$configJoomga 	= $this->_getJoomgaConfig();
		$images			= array();
		$catID 			= null;
		$limitStatus 	= $objJSNUtils->checkLimit();

		if (count($result) > 0)
		{
			foreach ($result as $item)
			{
				if ($catID != $item->catid)
				{
					$catPath = $this->_getJoomgaCatPath($item->catid);
				}
				$catID = $item->catid;
				$realPath 					= str_replace('/', DS, $configJoomga->jg_pathoriginalimages.$catPath.$item->imgfilename);
				$imageObj 					= new stdClass();
				$imageObj->image_title 		= $item->imgtitle;
				$imageObj->image_alt_text	= $item->imgtitle;
				$imageObj->image_extid 		= $item->id;
				$imageObj->album_extid		= $item->catid;
				$imageObj->image_small 		= $configJoomga->jg_pathimages.$catPath.$item->imgfilename; //$configJoomga->jg_paththumbs.$catPath.$item->imgthumbname;
				$imageObj->image_medium 	= $configJoomga->jg_pathimages.$catPath.$item->imgfilename;
				$imageObj->image_big 		= $configJoomga->jg_pathoriginalimages.$catPath.$item->imgfilename;
				$imageObj->image_link		= JURI::root().'index.php?option=com_joomgallery&view=detail&id='.$item->id;
				$imageObj->image_description = $item->imgtext;
				$imageObj->exif_data 		= '';
				$images[] = $imageObj;

				if (count($images) > 10 && $limitStatus == true && $config['limitEdition'] == true) {
					break;
				}
			}
		}

		$this->_data['images'] = $images;
	}

	function addOriginalInfo($config = array())
	{
		$data = array();

		if (is_array($this->_data['images']))
		{
			foreach ($this->_data['images'] as $img)
			{
				if ($img->custom_data == 1)
				{
					$info = $this->_getInfoPhoto($img->album_extid, $img->image_extid);
					$img->original_title 		= $info[1];
					$img->original_description  = $info[2];
					$img->original_link 		= JURI::root().'index.php?option=com_joomgallery&view=detail&id='.$info[0];
				}
				else
				{
					$img->original_title 		= $img->image_title;
					$img->original_description 	= $img->image_description;
					$img->original_link			= $img->image_link;
				}

				$data[] = $img;
			}
		}

		return $this->_data['images'] = $data;
	}

	function _getInfoPhoto($albumID, $imageID)
	{
		$query = 'SELECT
						id, imgtitle, imgtext,
						imgfilename
				  FROM #__joomgallery
				  WHERE catid = "'.(int) $albumID.'"
				  AND id = "'.$imageID.'"';

		$this->_db->setQuery($query);
		return $this->_db->loadRow();
	}

	function getImages2JSON($config = array())
	{
		parent::getImages2JSON($config);

		$arrayImage = array();

		$configJoomga = $this->_getJoomgaConfig();

		if (count($this->_data['images']))
		{
			foreach ($this->_data['images'] as $image)
			{
				$originalPhotoInfoOfJoomga 			= $this->_getOriginalPhotoInfoByImageID($image->image_extid);
				$imageDetailObj 					= new stdClass();
				$imageDetailObj->{'thumbnail'} 		= $config['URL'].$image->image_small;
				$imageDetailObj->{'image'} 			= $config['URL'].$image->image_big;
				$imageDetailObj->{'title'} 			= ($image->image_title!='') ? $image->image_title:$originalPhotoInfoOfJoomga['imgtitle'];
				if (isset($image->image_alt_text))
				{
					$imageDetailObj->{'alt_text'}	= $image->image_alt_text;
				}	
				else
				{
					$imageDetailObj->{'alt_text'} 	= ($image->image_title!='') ? $image->image_title:$originalPhotoInfoOfJoomga['imgtitle'];
				}
				$imageDetailObj->{'description'} 	= ($image->image_description!='') ? $image->image_description : $originalPhotoInfoOfJoomga['imgtext'];
				$imageDetailObj->{'link'} 			= $image->image_link;
				$imageDetailObj->{'exif_data'}		= $image->exif_data;
				$arrayImage[] 						= $imageDetailObj;
			}
		}
		return $arrayImage;
	}

	function _getOriginalPhotoInfoByImageID($imageID)
	{
		$query = 'SELECT imgtitle, imgtext FROM #__joomgallery WHERE id = "'.$imageID.'"';
		$this->_db->setQuery($query);
		return $this->_db->loadAssoc();
	}

	function getImages($config = array())
	{
		parent::getImages($config);

		if ($this->_syncmode == false) // update information
		{
			$this->_autoUpdateJoomgaImages($this->_showlistTable->showlist_id);

			// new infor has saved to database , so need to get images again
			$objJSNImages 		   = JSNISFactory::getObj('classes.jsn_is_images');
			$this->_data['images'] = $objJSNImages->getImagesByShowlistID($this->_showlistTable->showlist_id);
			$this->addOriginalInfo();
		}

		return $this->_data['images'];
	}

	function _autoUpdateJoomgaImages($showListID)
	{
		$arrayImageIDLocal 	= $this->_getListPhotoID($showListID);
		$uri	        	= JURI::getInstance();
		$base['prefix'] 	= $uri->toString( array('scheme', 'host', 'port'));
		$base['path']   	= rtrim(dirname(str_replace(array('"', '<', '>', "'",'administrator'), '', $_SERVER["PHP_SELF"])), '/\\');
		$realURL 			= $base['prefix'].$base['path'].'/';
		$joomgaConfig 		= $this->_getJoomgaConfig();

		if (count($arrayImageIDLocal))
		{
			$imageIDLocal = implode(',', $arrayImageIDLocal);

			$query 		  = 'SELECT
								img.id AS imgid, img.catid AS catid,
								CONCAT(cat.catpath,"/", img.imgfilename) AS path
							 FROM #__joomgallery img
							 INNER JOIN #__joomgallery_catg cat ON img.catid=cat.cid
							 WHERE img.published = 1 AND img.id IN ('.$imageIDLocal.')';

			$this->_db->setQuery($query);
			$result = $this->_db->loadAssocList();

			if (count($result))
			{
				foreach ($result as $value)
				{
					$queryUpdate = 'UPDATE
										#__imageshow_images
									SET
										album_extid = "'.$value["catid"].'",
										image_small = "'.$joomgaConfig->jg_pathimages.$value['path'].'",
										image_medium = "'.$joomgaConfig->jg_pathimages.$value['path'].'",
										image_big = "'.$joomgaConfig->jg_pathoriginalimages.$value['path'].'"
									WHERE image_extid = '.$value["imgid"].'
									AND showlist_id='.(int)$showListID;

					$this->_db->setQuery($queryUpdate);
					$this->_db->query();

					$imageLink= $realURL.'index.php?option=com_joomgallery&view=detail&id='.$value["imgid"];

					$queryUpdateLink = 'UPDATE #__imageshow_images
										SET image_link = "'.$imageLink.'"
										WHERE custom_data = 0
										AND image_extid ='.$value["imgid"].'
										AND showlist_id='.(int)$showListID;

					$this->_db->setQuery($queryUpdateLink);
					$this->_db->query();
				}
			}
		}

		return true;
	}

	function _getListPhotoID($showListID)
	{
		$arrayID 	= array();
		$query		= 'SELECT image_extid
					   FROM #__imageshow_images
					   WHERE sync = 0 AND showlist_id='.(int)$showListID;

		$this->_db->setQuery($query);

		$items = $this->_db->loadAssocList();

		if (count($items))
		{
			foreach ($items as $value) {
				$arrayID [] = $value['image_extid'];
			}
		}

		return $arrayID;

	}
}