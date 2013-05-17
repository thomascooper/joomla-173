<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

require_once( $plugin->absPath . '/toolbar.' . $plugin->element . '.html.php' );

switch ( $action ) {
	case 'invites':
		switch ( $function ) {
			case 'new':
			case 'edit':
			case 'save':
				cbinvitesMenu::showInviteEdit();
				break;
			case 'show':
			default:
				cbinvitesMenu::showInvites();
				break;
		}
		break;
	case 'config':
		cbinvitesMenu::showConfig();
		break;
	default:
		cbinvitesMenu::showPlugin();
		break;
}
?>