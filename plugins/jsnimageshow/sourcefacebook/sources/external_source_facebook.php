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
@ini_set('max_execution_time', 300);
@ini_set('allow_url_fopen', 1);
class JSNExternalSourceFacebook extends JSNImagesSourcesExternal
{
	protected $_serviceFacebook	= false;

	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->loadFacebookClasses();

		if (isset($this->_source['sourceTable']))
		{
			$this->getService();
		}
	}

	private function loadFacebookClasses()
	{
		if (!class_exists('Facebook'))
			include_once JPath::clean(dirname(dirname(__FILE__)).DS.'libs'.DS.'facebook.php');
		Facebook::$CURL_OPTS[CURLOPT_CAINFO] =  dirname(dirname(__FILE__)) . DS . 'libs'. DS .'fb_ca_chain_bundle.crt';
	}

	private function getService()
	{
		if ($this->_serviceFacebook == false) {
			$this->_serviceFacebook = new Facebook(array(
				'appId'  => $this->_source['sourceTable']->facebook_app_id,
				'secret' => $this->_source['sourceTable']->facebook_app_secret
			));
		}
		return $this->_serviceFacebook;
	}

	public function getValidation($config = array())
	{
		if (!isset($config['facebook_app_id'])||!isset($config['facebook_app_secret'])) {
			$this->_errorMsg = JText::_('FACEBOOK_MAINTENANCE_REQUIRED_FIELD_PROFILE_CANNOT_BE_LEFT_BLANK');
			return false;
		}
		$checkValid	= true;
		$objJSNFacebook = JSNISFactory::getObj('sourcefacebook.classes.jsn_is_facebook', null, null, 'jsnplugin');
		if($objJSNFacebook->checkApplicationExist($config['facebook_app_id']))
		{
			$checkValid = false;
			$this->_errorMsg = JText::_('FACEBOOK_APP_ID_EXIST', true);
		}else{
			try {
				$facebook = new Facebook(array(
					'appId'  => $config['facebook_app_id'],
					'secret' => $config['facebook_app_secret']
				));
				$facebookAppId	= '/'.$facebook->getAppId();
				$facebook->api($facebookAppId,'GET');
			} catch(FacebookApiException $e) {
				$this->_errorMsg = JText::_('FACEBOOK_APP_INVALID');
				$checkValid = false;
			}
		}
		return $checkValid;
	}

	protected function feedContentFile($url)
	{
		$objJSNHTTPRequest = JSNISFactory::getObj('classes.jsn_is_httprequest', null, $url);
		return $objJSNHTTPRequest->DownloadToString();
	}

	public function getCategories($config = array())
	{
		/*$multiQuery = '{
			"query1":"SELECT object_id, name from album WHERE owner=me()",
			"query2":"SELECT page_id, name from page WHERE page_id IN (SELECT page_id from page_admin WHERE uid=me() AND type!=\'APPLICATION\')",
			"query3":"SELECT object_id, name,owner from album WHERE owner IN (SELECT page_id from page_admin WHERE uid=me() AND type!=\'APPLICATION\')"
         }';

		$param = array(
		    'method' => 'fql.multiquery',
		    'queries' => $multiQuery,
		    'callback' => '',
			'access_token' => $this->_source['sourceTable']->facebook_access_token

		);*/
		
		//$albumsOfProfile	= $this->_serviceFacebook->api('/'.$this->_source['sourceTable']->facebook_app_id . '/albums', array('access_token' => $this->_source['sourceTable']->facebook_access_token));
		
		try{
			//$queryresults	= $this->_serviceFacebook->api($param);
			$profileAlbums	= $this->_serviceFacebook->api('/me/albums', array('access_token' => $this->_source['sourceTable']->facebook_access_token));
			$profilePages	= $this->_serviceFacebook->api('/me/accounts', array('access_token' => $this->_source['sourceTable']->facebook_access_token));
		}catch(FacebookApiException $e) {			
			return false;
		}
		
		if (isset($profileAlbums['data']))
		{
			$albumsOfProfile	= $profileAlbums['data'];
		}
		else 
		{
			$albumsOfProfile	= array();
		}
		
		if (isset($profilePages['data']	))
		{	
			$pages	= $profilePages['data'];
		}
		else 
		{
			$pages	= array();
		}	

		$xml .= "<node label='Facebook Album(s)' data=''>\n";
		if(count($albumsOfProfile)){
			$xml .= "<node label='User' data=''>\n";
			foreach($albumsOfProfile as $album){
				if (strtolower(trim($album['name'])) != 'timeline photos')
				{
					$title 		= htmlspecialchars ($album['name'], ENT_QUOTES);
					$albumId 	= htmlspecialchars ($album['id'], ENT_QUOTES);
					$xml .= "<node label='{$title}' data='{$albumId}'></node>\n";
				}
			}
			$xml .= "</node>\n";
		}
		if(count($pages)){
			foreach ($pages as $page){
				$xml .= "<node label='Page \"{$page['name']}\"' data=''>\n";
				try 
				{
					$pageAlbums		= $this->_serviceFacebook->api('/' . $page['id'] . '/albums', array('access_token' => $this->_source['sourceTable']->facebook_access_token));

					if (isset($pageAlbums['data']))
					{	
						$albumsOfPages = $pageAlbums['data'];
						foreach($albumsOfPages as $album) 
						{
							$title 		= htmlspecialchars ($album['name'], ENT_QUOTES);
							$albumId 	= htmlspecialchars ($album['id'], ENT_QUOTES);
							$xml .= "<node label='{$title}' data='{$albumId}'></node>\n";
						}
					}
				}
				catch(FacebookApiException $e) {
					
				}
				$xml .= "</node>\n";
			}
		}
		$xml .= "</node>\n";
		return $xml;
	}

	protected function getTagContent($src, $tag)
	{
		return false;
	}
	public function getFacebookImage($images,$imageSize){
		$imgSrc='';
		arsort($images);
		$countImages = count($images)-1;
		for($i=0;$i<$countImages;$i++){
			$imgSrc = $images[$i]['source'];
			if($images[$i]['width']<=$imageSize)
				break;
		}
		return $imgSrc;
	}
	public function loadImages($config = array())
	{
		if($config['album']==""){
			return false;
		}
		$objJSNFacebook = JSNISFactory::getObj('sourcefacebook.classes.jsn_is_facebook', null, null, 'jsnplugin');
		$facebookParams	= json_decode($objJSNFacebook->getSourceParameters());
		$num = (isset($facebookParams->number_of_images_on_loading) && (is_int($facebookParams->number_of_images_on_loading)||ctype_digit($facebookParams->number_of_images_on_loading)))? $facebookParams->number_of_images_on_loading: '30';
		$offset = ($config['offset']=='')?0:$config['offset'];
		try{
			$photos = $this->_serviceFacebook->api('/'.$config['album'].'/photos', array('access_token' => $this->_source['sourceTable']->facebook_access_token,'limit' => $num,'offset'=>$offset));
		}catch(FacebookApiException $e) {
			return false;
		}
		$thumbnail_size = $this->_source['sourceTable']->facebook_thumbnail_size;
		$imageSize = $this->_source['sourceTable']->facebook_image_size;
		$photosList = array();
		if(count(@$photos['data'])>0){
			foreach($photos['data'] as $p){
				$images = $p['images'];
				$imageBigSrc = $this->getFacebookImage($images,$imageSize);
				$imageSmallSrc = $this->getFacebookImage($images,$thumbnail_size);
				$photo['image_title'] 		= '';
				$photo['image_alt_text'] 	= '';
				$photo['image_extid'] 		= $p['id'];
				$photo['image_small']   	= $imageSmallSrc;
				$photo['image_medium']   	= $imageSmallSrc;
				$photo['image_big']			= $imageBigSrc;
				$photo['album_extid']		= $config['album'];
				$photo['image_link']		= $p['link'];
				$photo['image_description'] = $p['name'];
				array_push($photosList, $photo);
			}
		}
		$data = new stdClass();
		$data->images = $photosList;
			return $data;
	}
	public function countImages($albumId){
		$num = 0;
		try{
			$album	= $this->_serviceFacebook->api('/'.$albumId, array('access_token' => $this->_source['sourceTable']->facebook_access_token));
			$num	= $album['count'];
		}catch(FacebookApiException $e) {
			$num	= 0;
		}
		return $num;
	}

	public function getOriginalInfoImages($config = array())
	{
		$arrayImageInfo = array();

		if (isset($config['image_extid']) && is_array($config['image_extid']))
		{
			foreach ($config['image_extid'] as $imgExtID)
			{
				$photoInfoOriginal 		= $this->getInfoPhoto($imgExtID);

				$imageObj 				= new stdClass();
				$imageObj->album_extid	= (string)$config['album_extid'];
				$imageObj->image_extid 	= (string)$imgExtID;
				$imageObj->title 		= '';
				$imageObj->description 	= ($photoInfoOriginal['description']) ? $photoInfoOriginal['description'] : '';
				$imageObj->link			= ($photoInfoOriginal['url']) ? $photoInfoOriginal['url'] : '';
				$arrayImageInfo[] 		= $imageObj;
			}
		}
		return $arrayImageInfo;
	}

	protected function getInfoPhoto($photoID)
	{
		$url = '';
		$description = '';
		try{
			$image	= $this->_serviceFacebook->api('/'.$photoID, array('access_token' => $this->_source['sourceTable']->facebook_access_token));
			$description  = '';
			if (array_key_exists('name',$image))
				$description = $image['name'];
			$url	= $image['link'];
		}catch(FacebookApiException $e) {
		}
		$photo 					= array();
		$photo['description'] 	= $description;
		$photo['url'] 			= $url;
	}

	public function saveImages($config = array())
	{
		parent::saveImages($config);

		$config 	= $this->_data['saveImages'];
		$imgExtID 	= $config['imgExtID'];

		if (count($imgExtID))
		{
			$objJSNImages 	= JSNISFactory::getObj('classes.jsn_is_images');
			$ordering 		= $objJSNImages->getMaxOrderingByShowlistID($config['showlistID']);

			if (count($ordering) < 0 || is_null($ordering)) {
				$ordering = 1;
			} else {
				$ordering = $ordering[0] + 1;
			}

			$imagesTable = JTable::getInstance('images', 'Table');
			$countImgExtID = count($imgExtID);
			for ($i = 0 ; $i < $countImgExtID; $i++)
			{
				$imagesTable->showlist_id 		= $config['showlistID'];
				$imagesTable->image_extid 		= $imgExtID[$i];
				$imagesTable->album_extid 		= $config['albumID'][$imgExtID[$i]];
				$imagesTable->image_small 		= $config['imgSmall'][$imgExtID[$i]];
				$imagesTable->image_medium 		= $config['imgMedium'][$imgExtID[$i]];
				$imagesTable->image_big			= $config['imgBig'][$imgExtID[$i]];
				$imagesTable->image_title   	= $config['imgTitle'][$imgExtID[$i]];
				if (isset($config['imgAltText'][$imgExtID[$i]]))
				{	
					$imagesTable->image_alt_text   	= $config['imgAltText'][$imgExtID[$i]];
				}
				$imagesTable->ordering			= $ordering;
				$imagesTable->image_description = $config['imgDescription'][$imgExtID[$i]];
				$imagesTable->image_link 		= $config['imgLink'][$imgExtID[$i]];
				$imagesTable->custom_data 		= $config['customData'][$imgExtID[$i]];
				$imagesTable->exif_data 		= '';
				$result = $imagesTable->store(array('replcaceSpace' => false));
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

	public function addOriginalInfo()
	{
		$data = array();

		if (is_array($this->_data['images']) && is_array($this->_data['images']))
		{
			foreach ($this->_data['images'] as $img)
			{
				if ($img->custom_data == 1)
				{
					$info 	= $this->getInfoPhoto($img->album_extid, $img->image_extid);
					$img->original_title 		= '';
					$img->original_description 	= (is_array($info['description'])) ? '' : trim($info['description']);
					$img->original_link 		= (is_array($info['url'])) ? '' : trim($info['url']);
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

		return $data;
	}

	public function getImages2JSON($config = array())
	{
		parent::getImages2JSON($config);

		$arrayImage = array();

		if (count($this->_data['images']))
		{
			foreach ($this->_data['images'] as $image)
			{
				$imageDetailObj 					= new stdClass();
				$imageDetailObj->{'thumbnail'} 		= $image->image_small;
				$imageDetailObj->{'image'} 			= $image->image_big;
				$imageDetailObj->{'title'} 			= $image->image_title;
				if (isset($image->image_alt_text))
				{	
					$imageDetailObj->{'alt_text'} 		= $image->image_alt_text;
				}
				else 
				{
					$imageDetailObj->{'alt_text'} 		= $image->image_title;
				}
				$imageDetailObj->{'description'} 	= (!is_null($image->image_description)) ? $image->image_description : '';
				$imageDetailObj->{'link'} 			= $image->image_link;
				$imageDetailObj->exif_data			= '';
				$arrayImage[]		 				= $imageDetailObj;
			}
		}

		return $arrayImage;
	}

	public function getImagesLocalInfo($config = array())
	{
		if (count( $config['imageIDs'] ))
		{
			$imageTable 	= JTable::getInstance('images','Table');
			$imageRevert  	= array();

			foreach ($config['imageIDs'] as $ID)
			{
				if ($imageTable->load((int)$ID))
				{
					$info 	= $this->getInfoPhoto($imageTable->album_extid, $imageTable->image_extid);
					$imgObj = new stdClass();
					$imgObj->image_id			= $imageTable->image_id;
					$imgObj->image_extid 		= $imageTable->image_extid;
					$imgObj->album_extid 		= $imageTable->album_extid;
					$imgObj->image_title 		= (is_array($info['title'])) ? '' : trim($info['title']);
					$imgObj->image_description 	= (is_array($info['description'])) ? '' : trim($info['description']);
					$imgObj->image_link 		= (is_array($info['url'])) ? '' : trim($info['url']);
					$imgObj->custom_data 		= 0;
					$imageRevert[] = $imgObj;
				}
			}
			return $imageRevert;
		}
		return false;
	}

	public function getImageSrc($config = array('image_big' => '', 'URL' => '')) {
		return $config['image_big'];
	}
}