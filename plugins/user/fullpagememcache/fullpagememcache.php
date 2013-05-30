<?php
/**
* @copyright Copyright (C) 2005 - 2009 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE.txt
*/

// No direct access
defined('_JEXEC') or die;

/**
* Example User Plugin
*
* @package Joomla.Plugin
* @subpackage User.example
* @since 1.5
*/
class plgUserFullpagememcache extends JPlugin
{
	/**
	* This method should handle any login logic and report back to the subject
	*
	* @param array $user Holds the user data.
	* @param array $options Extra options.
	*
	* @return boolean True on success
	* @since 1.5
	*/
	public function onUserLogin($user, $options)
	{
		// Initialise variables.
		$success = false;

		$cookie_name = 'jfpmc';
		$value = md5($cookie_name.time());
		if (!JRequest::getVar($cookie_name, NULL, 'cookie')) {
			setcookie($cookie_name,$value,time()+2592000,'/');
			JRequest::setVar($cookie_name, $value, 'cookie');
			$success = true;
		}
		return $success;
	}
	/**
	* This method should handle any logout logic and report back to the subject
	*
	* @param array $user Holds the user data.
	* @param array $options Extra options.
	*
	* @return boolean True on success
	* @since 1.5
	*/
	public function onUserLogout($user, $options)
	{
		// Initialise variables.
		$success = false;

		$cookie_name = 'jfpmc';
		if (JRequest::getString($cookie_name, NULL, 'cookie')) {
			setcookie($cookie_name,'',time()-3600,'/');
			$success = true;
		}
		return $success;
	}

}
