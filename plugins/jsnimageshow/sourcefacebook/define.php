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
$jsnImageSourceFacebook = array(
	'name' => 'Facebook',
	'identified_name' => 'facebook',
	'type' => 'external',
	'description' => 'Facebook Description',
	'thumb' => 'plugins/jsnimageshow/sourcefacebook/assets/images/thumb-facebook.png',
	'sync'	=> false,
	'pagination' => true
);
define('JSN_IS_SOURCEFACEBOOK', json_encode($jsnImageSourceFacebook));