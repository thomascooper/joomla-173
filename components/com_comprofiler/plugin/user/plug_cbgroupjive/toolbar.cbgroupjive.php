<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

require_once( $plugin->absPath . '/toolbar.' . $plugin->element . '.html.php' );

switch ( $action ) {
	case 'categories':
		switch ( $function ) {
			case 'new':
			case 'edit':
			case 'save':
				cbgjMenu::showCategoryEdit();
				break;
			case 'show':
			default:
				cbgjMenu::showCategories( $function, $user, $plugin );
				break;
		}
		break;
	case 'groups':
		switch ( $function ) {
			case 'new':
			case 'edit':
			case 'save':
				cbgjMenu::showGroupEdit();
				break;
			default:
				cbgjMenu::showGroups( $function, $user, $plugin );
				break;
		}
		break;
	case 'users':
		switch ( $function ) {
			case 'new':
			case 'edit':
			case 'save':
				cbgjMenu::showUserEdit();
				break;
			case 'show':
			default:
				cbgjMenu::showUsers( $function, $user, $plugin );
				break;
		}
		break;
	case 'invites':
		cbgjMenu::showInvites( $function, $user, $plugin );
		break;
	case 'config':
		cbgjMenu::showConfig( $function, $user, $plugin );
		break;
	case 'tools':
		cbgjMenu::showTools( $function, $user, $plugin );
		break;
	case 'integrations':
		cbgjMenu::showIntegrations( $function, $user, $plugin );
		break;
	case 'menus':
		cbgjMenu::showMenus( $function, $user, $plugin );
		break;
	case 'plugin':
		$_PLUGINS->trigger( 'gj_onPluginBEToolbar', array( $function, $user, $plugin ) );
		break;
	default:
		cbgjMenu::showPlugin();
		break;
}
?>