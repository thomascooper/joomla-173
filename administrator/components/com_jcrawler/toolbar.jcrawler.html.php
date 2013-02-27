<?php defined( '_JEXEC' ) or die( 'Restricted access' );
/**
* @version 		$Id: toolbar.jcrawler.html.php 2011-06-18 14:32:00Z zanardi $
* @package 		JCrawler
* @copyright 	Copyright (C) 2011 Patrick Winkler
* @license 		GNU/GPL, see http://www.gnu.org/licenses/gpl-2.0.html
*/
class TOOLBAR_jcrawler 
{
	function _DEFAULT() 
	{
		JToolBarHelper::title( JText::_( 'JCrawler' ),'component' );
		JToolBarHelper::cancel();
	}

	function _UPDATECHECK() 
	{
		JToolBarHelper::title( JText::_( 'JCrawler' ),'component' );
		JToolBarHelper::back();
	}
}
?>
