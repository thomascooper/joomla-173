<?php
/**
 * @author JoomlaShine.com Team
 * @copyright JoomlaShine.com
 * @link joomlashine.com
 * @package JSN ImageShow - Image Source Flickr
 * @version $Id$
 * @license GNU/GPL v2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die('Restricted access');
$jsnImageSourceJoomga = array(
	'name' => 'JoomGallery',
	'identified_name' => 'joomgallery',
	'type' => 'internal',
	'description' => 'JoomGallery Description',
	'component' => 'com_joomgallery',
	'thumb' => 'plugins/jsnimageshow/sourcejoomgallery/assets/images/thumb-joomgallery.png',
	'component_link' => 'http://www.joomgallery.net/',
	'sync'	=> true,
	'pagination' => false
);
define('JSN_IS_SOURCEJOOMGALLERY', json_encode($jsnImageSourceJoomga));
