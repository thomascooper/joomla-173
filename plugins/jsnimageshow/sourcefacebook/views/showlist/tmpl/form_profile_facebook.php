<?php
/**
 * @version    $Id$
 * @package    JSN.ImageShow
 * @author     JoomlaShine Team <support@joomlashine.com>
 * @copyright  Copyright (C) 2012 JoomlaShine.com. All Rights Reserved.
 * @license    GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Websites: http://www.joomlashine.com
 * Technical Support:  Feedback - http://www.joomlashine.com/contact-us/get-support.html
 */

defined('_JEXEC') or die('Restricted access');

$objJSNFacebookSelect = JSNISFactory::getObj('sourcefacebook.classes.jsn_is_facebookselect', null, null, 'jsnplugin');
$appId			= JRequest::getVar('f_appid');
$appSecret		= JRequest::getVar('f_appsecret');
$title 			= JRequest::getVar('f_title');
$imgSize 		= JRequest::getVar('f_imgsize');
$tmbSize 		= JRequest::getVar('f_tmbSize');
$initFacebook 	= false;
?>
<link rel="stylesheet" href="../plugins/jsnimageshow/sourcefacebook/assets/css/jsn_is_facebook.css" type="text/css" />
<script type="text/javascript" src="../plugins/jsnimageshow/sourcefacebook/assets/js/jsn_is_facebook_flow.js"></script>
<script type="text/javascript">
	var facebookLogin		= false;
	var facebookInit		= false;
	var facebookConnected 	= false;
	<?php
		if($appId != '' && $title != '' && $appSecret !='')
		{
			$initFacebook = true;
	?>
</script>
<div id="fb-root"></div>
<script type="text/javascript" src="../plugins/jsnimageshow/sourcefacebook/assets/js/jsn_is_facebook.js"></script>
<script type="text/javascript">

	window.fbAsyncInit = function() {
		JSNISFacebook.initialize(<?php echo "'".$appId."'";?>);
		JSNISFacebook.checkLogin("newProfile");
		setTimeout(function() {
			JSNISFacebook.synchronize("newProfile", objISShowlist);
		}, 500)
		facebookInit = true;
	};

	JSNISFacebook.loadSDK();
	<?php } ?>

	function submitFormProfile()
	{
		var form 						= jQuery('#frm-edit-source-profile');
		var params 						= {};
		params.profile_title			= jQuery('input[name="external_source_profile_title"]', form).val();
		params.facebook_access_token	= jQuery('input[name="facebook_access_token"]', form).val();
		params.facebook_user_id			= jQuery('input[name="facebook_user_id"]', form).val();
		params.facebook_app_id			= jQuery('input[name="facebook_app_id"]', form).val();
		params.facebook_app_secret		= jQuery('input[name="facebook_app_secret"]', form).val();

		params.facebook_thumbnail_size	= jQuery('option:selected', jQuery('#facebook_thumbnail_size')).val();
		params.facebook_image_size		= jQuery('option:selected', jQuery('#facebook_image_size')).val();

		if (params.config_title == '' || params.facebook_app_id == '' || params.facebook_app_secret == '' )
		{
			alert("<?php echo JText::_('FACEBOOK_MAINTENANCE_REQUIRED_FIELD_PROFILE_CANNOT_BE_LEFT_BLANK', true); ?>");
			return false;
		}
		else
		{

			if(facebookLogin)
			{
				var url  				= 'index.php?option=com_imageshow&controller=maintenance&task=checkEditProfileExist&source=facebook&external_source_profile_title=' + params.profile_title + '&external_source_id=0&rand=' + Math.random();
				params.validate_url 	= 'index.php?option=com_imageshow&controller=maintenance&task=validateProfile&source=facebook&facebook_app_id=' + params.facebook_app_id+'&facebook_app_secret=' + params.facebook_app_secret + '&rand='+ Math.random();
				objISShowlist.checkEditedProfile(url, params);
				facebookLogin = false;
			}
			else
			{
				JSNISFacebookFlow.connectFacebook(params.facebook_app_id,params.facebook_app_secret,params.profile_title,params.facebook_image_size,params.facebook_thumbnail_size, objISShowlist);
			}
		}
		return false;
	}

	window.addEvent('domready', function()
	{
		if(facebookInit){
			setTimeout('JSNISFacebook.notConnected()', 5000);
		}
	});
	<?php if($initFacebook) { ?>
	(function($){
	   $(document).ready(function () {
		   $( "#accordion" ).accordion({ active: 1 });
		});
	})(jQuery);
	<?php }?>
</script>
<input type="hidden" id="access_token" name="facebook_access_token" value=""/>
<input type="hidden" id="facebook_userid" name="facebook_user_id" value="" />
<div class="control-group">
	<label class="control-label"><?php echo JText::_('FACEBOOK_MAINTENANCE_TITLE_PROFILE_TITLE');?> <a class="hint-icon jsn-link-action" href="javascript:void(0);">(?)</a></label>
	<div class="controls">
		<div class="jsn-preview-hint-text">
			<div class="jsn-preview-hint-text-content clearafter">
				<?php echo JText::_('FACEBOOK_MAINTENANCE_DES_PROFILE_TITLE');?>
				<a href="javascript:void(0);" class="jsn-preview-hint-close jsn-link-action">[x]</a>
			</div>
		</div>
		<input class="jsn-master jsn-input-xxlarge-fluid" type="text" name ="external_source_profile_title" id ="external_source_profile_title" value = "<?php if($initFacebook){echo $title;}else echo @$this->sourceInfo->external_source_profile_title;?>"/>
	</div>
</div>
<div class="control-group">
	<label id="facebookAppIdTitle" class="control-label"><?php echo JText::_('FACEBOOK_MAINTENANCE_TITLE_APP_ID');?> <a class="hint-icon jsn-link-action" href="javascript:void(0);">(?)</a></label>
	<div class="controls">
		<div id="facebookAppIdDes" class="jsn-preview-hint-text">
			<div class="jsn-preview-hint-text-content clearafter">
				<?php echo JText::_('FACEBOOK_MAINTENANCE_DES_APP_ID');?>
				<a href="javascript:void(0);" class="jsn-preview-hint-close jsn-link-action">[x]</a>
			</div>
		</div>
		<input class="jsn-master jsn-input-xxlarge-fluid" type="text" name ="facebook_app_id" id ="facebook_app_id" value = "<?php echo ($initFacebook)?$appId.'" readonly="readonly':@$this->sourceInfo->facebook_app_id;?>"/>
	</div>
</div>
<div class="control-group">
	<label class="control-label"><?php echo JText::_('FACEBOOK_MAINTENANCE_TITLE_APP_SECRET');?> <a class="hint-icon jsn-link-action" href="javascript:void(0);">(?)</a></label>
	<div class="controls">
		<div class="jsn-preview-hint-text">
			<div class="jsn-preview-hint-text-content clearafter">
				<?php echo JText::_('FACEBOOK_MAINTENANCE_DES_APP_SECRET');?>
				<a href="javascript:void(0);" class="jsn-preview-hint-close jsn-link-action">[x]</a>
			</div>
		</div>
		<input class="jsn-master jsn-input-xxlarge-fluid" type="text" name ="facebook_app_secret" id ="facebook_app_secret" value = "<?php echo ($initFacebook)?$appSecret.'" readonly="readonly':@$this->sourceInfo->facebook_app_secret;?>"/>
	</div>
</div>
<div class="control-group">
	<label class="control-label"><?php echo JText::_('FACEBOOK_MAINTENANCE_TITLE_THUMBNAIL_MAX_SIZE');?> <a class="hint-icon jsn-link-action" href="javascript:void(0);">(?)</a></label>
	<div class="controls">
		<div class="jsn-preview-hint-text">
			<div class="jsn-preview-hint-text-content clearafter">
				<?php echo JText::_('FACEBOOK_MAINTENANCE_THUMBNAIL_MAX_SIZE_DESC');?>
				<a href="javascript:void(0);" class="jsn-preview-hint-close jsn-link-action">[x]</a>
			</div>
		</div>
		<?php
		echo $objJSNFacebookSelect->getSelectBoxThumbnailSize($tmbSize);
		?>
	</div>
</div>
<div class="control-group">
	<label class="control-label"><?php echo JText::_('FACEBOOK_MAINTENANCE_TITLE_IMAGE_MAX_SIZE');?> <a class="hint-icon jsn-link-action" href="javascript:void(0);">(?)</a></label>
	<div class="controls">
		<div class="jsn-preview-hint-text">
			<div class="jsn-preview-hint-text-content clearafter">
				<?php echo JText::_('FACEBOOK_MAINTENANCE_IMAGE_MAX_SIZE_DESC');?>
				<a href="javascript:void(0);" class="jsn-preview-hint-close jsn-link-action">[x]</a>
			</div>
		</div>
		<?php echo $objJSNFacebookSelect->getSelectBoxImageSize($imgSize); ?>
	</div>
</div>
<?php
	 if($initFacebook){
?>
<div id="loginFacebook">
	<div class="jsn-source-icon-loading" id="loadingConnect"><?php echo JText::_('FACEBOOK_CONNECTION');?></div>
	<div id="loginButton">
		<div class="fb-login-button" scope="user_photos, manage_pages"><?php echo JText::_('FACEBOOK_LOGIN_BUTTON');?></div>
	</div>
</div>
<div id="facebookNotMatch" class="alert alert-error">
	<?php echo JText::_('FACEBOOK_URL_NOT_MATCH');?>
</div>
<?php }?>