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

$objJSNFacebook 	= JSNISFactory::getObj('sourcefacebook.classes.jsn_is_facebook', null, null, 'jsnplugin');
$facebookParamsStr	= $objJSNFacebook->getSourceParameters();
$facebookParams		= json_decode($facebookParamsStr);
?>
<div class="control-group">
	<label class="control-label">
		<?php echo JText::_('MAINTENANCE_SOURCE_PARAMETER_NUMBER_OF_IMAGES_ON_LOADING');?>
		<a class="hint-icon jsn-link-action" href="javascript:void(0);">(?)</a>
	</label>
	<div class="controls">
		<div class="jsn-preview-hint-text">
			<div class="jsn-preview-hint-text-content clearafter">
				<?php echo JText::_('MAINTENANCE_SOURCE_DESC_NUMBER_OF_IMAGES_ON_LOADING');?>
				<a href="javascript:void(0);" class="jsn-preview-hint-close jsn-link-action">[x]</a>
			</div>
		</div>
		<input class="jsn-master jsn-input-xxlarge-fluid" type="text" name ="number_of_images_on_loading" id ="number_of_images_on_loading" value = "<?php echo (isset($facebookParams->number_of_images_on_loading) && $facebookParams->number_of_images_on_loading !='')? $facebookParams->number_of_images_on_loading: '30';?>"/>
	</div>
</div>

<input type="hidden" name="option" value="com_imageshow" />
<input type="hidden" name="controller" value="maintenance" />
<input type="hidden" name="task" value="saveProfileParameter" id="task" />
<input type="hidden" name="image_source" value="sourcefacebook"/>
<input type="hidden" name="profile_parameter" value="<?php echo htmlspecialchars ($facebookParamsStr);?>" />
<?php echo JHTML::_('form.token'); ?>