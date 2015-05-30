<?php
/**
 * Plugin Helper File: Protect
 *
 * @package         Modals
 * @version         5.3.0
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2015 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class plgSystemModalsHelperProtect
{
	var $helpers = array();

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = plgSystemModalsHelpers::getInstance();
		$this->params = $this->helpers->getParams();

		$this->params->protected_tags = array(
			$this->params->tag,
		);
	}

	public function protect(&$string)
	{
		nnProtect::protectFields($string);
		nnProtect::protectSourcerer($string);
	}

	public function protectTags(&$string)
	{
		nnProtect::protectTags($string, $this->params->protected_tags);
	}

	public function unprotectTags(&$string)
	{
		nnProtect::unprotectTags($string, $this->params->protected_tags);
	}
}
