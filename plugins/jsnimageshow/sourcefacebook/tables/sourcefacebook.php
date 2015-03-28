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
class TableSourceFacebook extends JTable
{
	var $external_source_id = null;
	var $external_source_profile_title = null;
	var $facebook_access_token = null;
	var $facebook_app_id = null;
	var $facebook_app_secret = null;
	var $facebook_user_id = null;
	var $facebook_thumbnail_size = null;
	var $facebook_image_size = null;

	function __construct(& $db) {
		parent::__construct('#__imageshow_external_source_facebook', 'external_source_id', $db);
	}
	function store($updateNulls = false)
	{
		$query = 'SELECT * FROM #__imageshow_external_source_facebook WHERE external_source_id ='.(int)$this->external_source_id;
		$this->_db->setQuery($query);
		$current = $this->_db->loadObject();
		$updateThumbnailSize = false;
		$updateImageSize 	 = false;

		if ($current)
		{
			$updateThumbnailSize	= '';
			if ($this->facebook_thumbnail_size && $this->facebook_thumbnail_size != $current->facebook_thumbnail_size) {
				$updateThumbnailSize = $this->facebook_thumbnail_size;
			}
			$updateImageSize	= '';
			if ($this->facebook_image_size && $this->facebook_image_size != $current->facebook_image_size) {
				$updateImageSize = $this->facebook_image_size;
			}
		}

		if (parent::store($updateNulls = false))
		{
			$this->updateImageSize($this->external_source_id, $updateThumbnailSize,$updateImageSize);
		} else {
			return false;
		}
		return true;
	}
	function getFacebookImageUrl($imageInfo,$size){
		$imgSrc		= $imageInfo[0]['source'];
		foreach ($imageInfo as $info)
		{
			if($info['width'] <= $size)
			{
				$imgSrc		= $info['source'];
				break;
			}
		}
		return $imgSrc;

	}
	function updateImageSize($externalSourceId, $updateThumbnailSize, $updateImageSize)
	{
		if ((!$updateThumbnailSize && !$updateImageSize) || !$externalSourceId) return false;

		if (!class_exists('Facebook'))
			include_once JPath::clean(dirname(dirname(__FILE__)).DS.'libs'.DS.'facebook.php');
		Facebook::$CURL_OPTS[CURLOPT_CAINFO] =  dirname(dirname(__FILE__)) . DS . 'libs'. DS .'fb_ca_chain_bundle.crt';
		$facebook = new Facebook(array(
			'appId'  => $this->facebook_app_id,
			'secret' => $this->facebook_app_secret
		));
		$objJSNShowlist = JSNISFactory::getObj('classes.jsn_is_showlist');
		$objJSNImages	= JSNISFactory::getObj('classes.jsn_is_images');
		$showlists 		= $objJSNShowlist->getListShowlistBySource($externalSourceId, 'facebook');
		$db = JFactory::getDBO();
		foreach ($showlists as $showlist)
		{
			$images = $objJSNImages->getImagesByShowlistID($showlist->showlist_id);

			if ($images)
			{
				foreach ($images as $image)
				{
					$imageInfo	= $facebook->api('/'.$image->image_extid.'?access_token='.$this->facebook_access_token);
					if(count(@$imageInfo['images'])>0)
					{
						if($updateThumbnailSize)
						{
							$imageSource = $this->getFacebookImageUrl($imageInfo['images'],$updateThumbnailSize);
							$query = 'UPDATE #__imageshow_images
									  SET image_small = '.$this->_db->quote($imageSource).'
									  WHERE showlist_id ='. (int)$showlist->showlist_id .'
									  AND image_id = '.$this->_db->quote($image->image_id).'LIMIT 1';
							$db->setQuery($query);
							$db->query();
						}
						if($updateImageSize)
						{
							$imageSource = $this->getFacebookImageUrl($imageInfo['images'],$updateImageSize);
							$query = 'UPDATE #__imageshow_images
									  SET image_big = '.$this->_db->quote($imageSource).'
									  WHERE showlist_id ='. (int)$showlist->showlist_id .'
									  AND image_id = '.$this->_db->quote($image->image_id).'LIMIT 1';
							$db->setQuery($query);
							$db->query();
						}
					}
				}
			}
		}
	}
}
?>