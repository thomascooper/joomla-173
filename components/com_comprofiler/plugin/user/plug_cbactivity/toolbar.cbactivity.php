<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

require_once( $plugin->absPath . '/toolbar.' . $plugin->element . '.html.php' );

switch ( $action ) {
	case 'activity':
		switch ( $function ) {
			case 'new':
			case 'edit':
			case 'save':
				cbactivityMenu::showActivityEdit();
				break;
			case 'show':
			default:
				cbactivityMenu::showActivity();
				break;
		}
		break;
	case 'config':
		cbactivityMenu::showConfig();
		break;
	default:
		cbactivityMenu::showPlugin();
		break;
}
?>