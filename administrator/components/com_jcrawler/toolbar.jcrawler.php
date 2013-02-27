<?php defined( '_JEXEC' ) or die( 'Restricted access' );
/**
* @version 		$Id: toolbar.jcrawler.php 2011-06-18 14:32:00Z zanardi $
* @package 		JCrawler
* @copyright 	Copyright (C) 2011 Patrick Winkler
* @license 		GNU/GPL, see http://www.gnu.org/licenses/gpl-2.0.html
*/

require_once( JApplicationHelper::getPath( 'toolbar_html' ) );

switch ( $task ) {
	
	case 'updatecheck':
		TOOLBAR_jcrawler::_UPDATECHECK();
		break;
	
	default:
		TOOLBAR_jcrawler::_DEFAULT();
		break;
}
?>
