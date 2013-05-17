<?php
/**
* Joomla Community Builder User Plugin: plug_cbprofilebook
* @version $Id: uninstall.profilebook.php 2650 2012-10-25 14:06:13Z kyle $
* @package plug_cbprofilebook
* @subpackage uninstall.profilebook.php
* @author JoomlaJoe and Beat
* @copyright (C) JoomlaJoe and Beat, www.joomlapolis.com
* @license Limited  http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @final 1.2 
*/

/** ensure this file is being included by a parent file */
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

function plug_cb_profilebook_uninstall(){
	global $_CB_database;

	$html_return			=	'';
	
	// if needed get cb profilebook plugin parameters
	$plugparms_query		=	"SELECT params"
							.	"\n FROM #__comprofiler_plugin"
							.	"\n WHERE element='cb.profilebook'";
	$_CB_database->setQuery( $plugparms_query );
	$cbpbplugparms			=	$_CB_database->loadResult();

	$params					=	new cbParamsBase( $cbpbplugparms );

	if ( $params->get( 'pbUnistallMode' ) ) {			// if full unistall mode parameter selected then purge everything
		$drop_table_query	=	'DROP TABLE `#__comprofiler_plug_profilebook`';
		$_CB_database->setQuery( $drop_table_query );
		$ret				=	$_CB_database->query();
		if( ! $ret ) {
			$html_return	.=	'<font color="red">Failed to drop table #__comprofiler_plug_profilebook</font><br />';
		} else {
			$html_return	.=	'<font color="green">Table #__comprofiler_plug_profilebook deleted (all items lost)</font><br />';
		}
		$drop_fields_query	=	"ALTER TABLE `#__comprofiler` DROP COLUMN `cb_pb_enable`,"
							.	"\n DROP COLUMN `cb_pb_autopublish`,"
							.	"\n DROP COLUMN `cb_pb_notifyme`";	
		$_CB_database->setQuery( $drop_fields_query );
		$ret				=	$_CB_database->query();
		if( ! $ret ) {
			$html_return	.=	'<font color="red">Failed to delete Plugin fields from #__comprofiler table</font><br />';
		} else {
			$html_return	.=	'<font color="green">Plugin fields deleted from #__comprofiler table (all personalization lost)</font><br />';
		}
	} else {
		// just unistall plugin code - keep all data
		$html_return		.=	'<font color="green">The profilebook plugin has been deleted but data remains so upgrade is possible</font><br />';
	}
	# Show installation result to user
	echo 'Plugin successfully uninstalled. See bellow for extra status messages';
	return $html_return;
}
?>
