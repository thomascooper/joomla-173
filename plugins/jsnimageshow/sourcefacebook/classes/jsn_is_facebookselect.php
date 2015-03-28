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
require_once JPATH_LIBRARIES.DS.'joomla'.DS.'html'.DS.'html'.DS.'select.php';
class JSNISFacebookSelect extends JHtmlSelect
{
	function getSelectBoxThumbnailSize($default = 130)
	{
		$thumbSize = $this->getThumbnailSizeOptions();
		return JHTML::_('select.genericList', $thumbSize, 'facebook_thumbnail_size', 'class="jsn-master jsn-input-xxlarge-fluid"', 'value', 'text', $default);
	}

	function getSelectBoxImageSize($default = 960)
	{
		$default = ($default=="")?960:$default;
		$imageSize = $this->getImageSizeOptions();
		return JHTML::_('select.genericList', $imageSize, 'facebook_image_size', 'class="jsn-master jsn-input-xxlarge-fluid"', 'value', 'text', $default);
	}

	function getImageSizeOptions()
	{
		$imageSize = array(
			array('value' => 180, 'text' => JText::_('180')),
			array('value' => 320, 'text' => JText::_('320')),
			array('value' => 480, 'text' => JText::_('480')),
			array('value' => 720, 'text' => JText::_('720')),
			array('value' => 960, 'text' => JText::_('960')),
			array('value' => 1024, 'text' => JText::_('1024')),
			array('value' => 2048, 'text' => JText::_('2048'))
		);

		return $imageSize;
	}

	function getThumbnailSizeOptions()
	{
		$thumbSize = array(
			array('value' => 130, 'text' => JText::_('130')),
			array('value' => 180, 'text' => JText::_('180'))
		);

		return $thumbSize;
	}
}