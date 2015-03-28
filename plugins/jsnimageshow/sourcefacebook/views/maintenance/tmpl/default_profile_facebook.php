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
 *
 */
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

$externalSourceID 		= JRequest::getInt('external_source_id');
$controller				= JRequest::getVar('caller','maintenance');
$expiredToken			= false;
$objJSNFacebookSelect 	= JSNISFactory::getObj('sourcefacebook.classes.jsn_is_facebookselect', null, null, 'jsnplugin');
$objJSNFacebook 		= JSNISFactory::getObj('sourcefacebook.classes.jsn_is_facebook', null, null, 'jsnplugin');
$facebookInfo 			= $objJSNFacebook->getFacebookInfoFromExternalSource($externalSourceID);
$params 				= JSNUtilsLanguage::getTranslated(array(
							'JSN_IMAGESHOW_SAVE',
							'JSN_IMAGESHOW_CLOSE',
							'JSN_IMAGESHOW_CONFIRM'));
?>

<link rel="stylesheet" href="../plugins/jsnimageshow/sourcefacebook/assets/css/jsn_is_facebook.css" type="text/css" />
<div id="fb-root"></div>
<script type="text/javascript" src="../plugins/jsnimageshow/sourcefacebook/assets/js/jsn_is_facebook.js"></script>
<script type="text/javascript">

	var objISMaintenance 	= null;
	var facebookLogin		= false;
	var expiredToken		= true;
	var facebookConnected 	= false;
	var caller 				= "<?php echo $controller;?>";

	window.fbAsyncInit = function() {
		JSNISFacebook.initialize(<?php echo "'" . @$this->sourceInfo->facebook_app_id . "'";?>);
		JSNISFacebook.checkLogin(caller);
		JSNISFacebook.synchronize(caller);
	};
	JSNISFacebook.loadSDK();
	require(['imageshow/joomlashine/maintenance'], function (JSNISMaintenance) {
		objISMaintenance = new JSNISMaintenance({
			language: <?php echo json_encode($params); ?>
		});
	});

	require(['jquery'], function ($) {
		$(function () {
			setTimeout('JSNISFacebook.notConnected()', 5000);
			if (caller == "showlist")
			{
				$('#profileFacebook').css('display', 'none');
			}

			function onSubmit(ciframe, imageSourceLink)
			{
				var form 				= $('#frm-edit-source-profile');
				var params 				= {};
				params.profile_title	= $('input[name="external_source_profile_title"]', form).val();

				if (params.profile_title == '')
				{
					alert("<?php echo JText::_('FACEBOOK_MAINTENANCE_REQUIRED_FIELD_PROFILE_CANNOT_BE_LEFT_BLANK', true); ?>");
					return false;
				}
				else
				{
					if (facebookLogin || !expiredToken)
					{
						objISMaintenance.submitForm(ciframe, imageSourceLink);
					}
				}
				return false;
			}

			function submitForm ()
			{
				var form = $('#frm-edit-source-profile');
					form.submit();
			}

			parent.gIframeOnSubmitFunc = onSubmit;
			gIframeSubmitFunc =submitForm;
		});
	});
</script>
<div id="facebookWarning" class="alert alert-error">
	<p id="facebookWarningText"><?php echo JText::_('FACEBOOK_WARNING');?></p>
	<div class="jsn-source-icon-loading" id="loadingConnect"><?php echo JText::_('FACEBOOK_CONNECTION');?></div>
	<div id="loginButton">
		<div class="fb-login-button" scope="user_photos, manage_pages"><?php echo JText::_('FACEBOOK_LOGIN_BUTTON');?></div>
	</div>
	<div id="facebookNotMatch">
		<?php echo JText::_('FACEBOOK_URL_NOT_MATCH');?>
	</div>
</div>
<div id="profileFacebook">
<div class="control-group">
	<label class="control-label"><?php echo JText::_('FACEBOOK_MAINTENANCE_TITLE_PROFILE_TITLE');?> <a class="hint-icon jsn-link-action" href="javascript:void(0);">(?)</a></label>
	<div class="controls">
		<div class="jsn-preview-hint-text">
			<div class="jsn-preview-hint-text-content clearafter">
				<?php echo JText::_('FACEBOOK_MAINTENANCE_DES_PROFILE_TITLE');?>
				<a href="javascript:void(0);" class="jsn-preview-hint-close jsn-link-action">[x]</a>
			</div>
		</div>
		<input type="text" name ="external_source_profile_title" id ="external_source_profile_title" class="jsn-master jsn-input-xxlarge-fluid" value = "<?php echo @$this->sourceInfo->external_source_profile_title;?>"/>
	</div>
</div>
	<div class="control-group">
		<label class="control-label">
			<?php echo JText::_('FACEBOOK_MAINTENANCE_TITLE_APP_ID');?>
			<a class="jsn-link-action hint-icon" href="javascript:void(0);">(?)</a>
		</label>
		<div class="controls">
			<div class="jsn-preview-hint-text">
				<div class="jsn-preview-hint-text-content clearafter">
					<?php echo JText::_('FACEBOOK_MAINTENANCE_DES_APP_ID');?>
					<a href="javascript:void(0);" class="jsn-preview-hint-close jsn-link-action">[x]</a>
				</div>
			</div>
			<input type="text" disabled="disabled" class="jsn-readonly jsn-master jsn-input-xxlarge-fluid" value="<?php echo @$this->sourceInfo->facebook_app_id;?>" name="facebook_app_id" />
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
			<input type="text" disabled="disabled" name ="facebook_app_secret" id ="facebook_app_secret" class="jsn-master jsn-input-xxlarge-fluid" value = "<?php echo @$this->sourceInfo->facebook_app_secret;?>"/>
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
			$thumbSize = $objJSNFacebookSelect->getThumbnailSizeOptions();
			echo JHTML::_('select.genericList', $thumbSize, 'facebook_thumbnail_size', 'class="jsn-master jsn-input-xxlarge-fluid"', 'value', 'text', $this->sourceInfo->facebook_thumbnail_size);
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
			<?php
				$imageSize = $objJSNFacebookSelect->getImageSizeOptions();
				echo JHTML::_('select.genericList', $imageSize, 'facebook_image_size', 'class="jsn-master jsn-input-xxlarge-fluid"', 'value', 'text', $this->sourceInfo->facebook_image_size);
			?>
		</div>
	</div>
</div>
<input type="hidden" id="access_token" name="facebook_access_token" value=""/>
<input type="hidden" id="facebook_userid" name="facebook_user_id" value="" />
<input type="hidden" name="option" value="com_imageshow" />
<input type="hidden" name="controller" value="maintenance" />
<input type="hidden" name="task" value="saveprofile" id="task" />
<input type="hidden" name="source" value="facebook" />
<input type="hidden" name="external_source_id" value="<?php echo $externalSourceID; ?>" id="external_source_id" />
<?php echo JHTML::_('form.token'); ?>