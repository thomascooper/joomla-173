<?php
/**
 * @author JoomlaShine.com Team
 * @copyright JoomlaShine.com
 * @link joomlashine.com
 * @package JSN ImageShow
 * @version $Id$
 * @license GNU/GPL v2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die( 'Restricted access' );
class JSNISFacebook
{
	//get facebook user id and facebook access token
	public function getFacbookInfo($showlistId){
		$query = 'SELECT f.external_source_id,f.facebook_app_id,f.facebook_user_id,f.facebook_access_token FROM #__imageshow_external_source_facebook as f
				INNER JOIN #__imageshow_source_profile as p ON f.external_source_id=p.external_source_id
				INNER JOIN #__imageshow_showlist as s ON p.external_source_profile_id=s.image_source_profile_id where s.showlist_id ='.$showlistId;
		$db = JFactory::getDBO();
		$db->setQuery($query);
		$db->query();
		return $db->loadObject();
	}
	public function getFacebookInfoFromExternalSource($externalSourceID){
		$query = 'SELECT facebook_user_id,facebook_access_token FROM #__imageshow_external_source_facebook where external_source_id='.$externalSourceID;
		$db = JFactory::getDBO();
		$db->setQuery($query);
		$db->query();
		return $db->loadObject();
	}
	public function verifyFacebookSession(){
		$showlistID 	  = JRequest::getVar('cid', array(0));
		$showlistID 	  = $showlistID[0];
		if($showlistID!=''){
			//JHTML::script('jsn_is_facebook.js','plugins/jsnimageshow/sourcefacebook/assets/js/');
			echo '<script type="text/javascript" src="../plugins/jsnimageshow/sourcefacebook/assets/js/jsn_is_facebook.js"></script>';
			$facebookInfo = $this->getFacbookInfo($showlistID);
			echo '<div id="fb-root"></div>';
			echo '<script type="text/javascript">
			window.fbAsyncInit = function() {
				JSNISFacebook.initialize("'.$facebookInfo->facebook_app_id.'");
				JSNISFacebook.checkExpiration("'.$facebookInfo->external_source_id.'","'.$facebookInfo->facebook_user_id.'","'.$facebookInfo->facebook_access_token.'");
			};
			JSNISFacebook.loadSDK();
			</script>';
			echo '<div id="tmp_facebook_id_auto_modal_window"></div>';

		}
	}
	public function checkApplicationExist($appId){
		$query = 'SELECT COUNT(*) FROM #__imageshow_external_source_facebook WHERE facebook_app_id = '.$appId;
		$db = JFactory::getDBO();
		$db->setQuery($query);
		$db->query();
		if($db->loadResult())
			return true;
		return false;
	}
	public function getSourceParameters(){
		$query = 'SELECT params FROM #__extensions WHERE element = "sourcefacebook" AND folder = "jsnimageshow"';
		$db = JFactory::getDBO();
		$db->setQuery($query);
		$db->query();
		return $db->loadResult();
	}
}