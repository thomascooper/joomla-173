<?php
/**
 *  @package AdminTools
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 *  @version $Id$
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AdmintoolsControllerIpautobans extends FOFController
{
	protected function onBeforeBrowse()
	{
		return $this->checkACL('admintools.security');
	}
	
	protected function onBeforeApply()
	{
		return $this->checkACL('admintools.security');
	}
	
	protected function onBeforeSave()
	{
		return $this->checkACL('admintools.security');
	}
	
	protected function onBeforePublish()
	{
		return $this->checkACL('admintools.security');
	}
	
	protected function onBeforeUnpublish()
	{
		return $this->checkACL('admintools.security');
	}

	protected function onBeforeRemove()
	{
		return $this->checkACL('admintools.security');
	}
	
	protected function onBeforeSavenew()
	{
		return $this->checkACL('admintools.security');
	}

}
