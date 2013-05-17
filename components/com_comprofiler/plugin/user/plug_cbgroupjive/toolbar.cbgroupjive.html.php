<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/administrator/components/' . $plugin->option . '/toolbar.comprofiler.html.php' );

class cbgjMenu {

	/**
	 * renders commonly used default navigation bar
	 */
	static public function getDefaults( $action, $function, $user, $plugin ) {
		CBtoolmenuBar::linkAction( 'gjplugin', cbgjClass::getPluginURL(), CBTxt::T( 'Plugin' ) );

		if ( $action != 'categories' ) {
			CBtoolmenuBar::linkAction( 'gjcategories', cbgjClass::getPluginURL( array( 'categories' ) ), CBTxt::T( 'Categories' ) );
		}

		if ( $action != 'groups' ) {
			CBtoolmenuBar::linkAction( 'gjgroups', cbgjClass::getPluginURL( array( 'groups' ) ), CBTxt::T( 'Groups' ) );
		}

		if ( $action != 'users' ) {
			CBtoolmenuBar::linkAction( 'gjusers', cbgjClass::getPluginURL( array( 'users' ) ), CBTxt::T( 'Users' ) );
		}

		if ( $action != 'invites' ) {
			CBtoolmenuBar::linkAction( 'gjinvites', cbgjClass::getPluginURL( array( 'invites' ) ), CBTxt::T( 'Invites' ) );
		}

		if ( $action != 'config' ) {
			CBtoolmenuBar::linkAction( 'gjconfig', cbgjClass::getPluginURL( array( 'config' ) ), CBTxt::T( 'Config' ) );
		}

		if ( $action != 'tools' ) {
			CBtoolmenuBar::linkAction( 'gjtools', cbgjClass::getPluginURL( array( 'tools' ) ), CBTxt::T( 'Tools' ) );
		}

		if ( $action != 'integrations' ) {
			CBtoolmenuBar::linkAction( 'gjintegrations', cbgjClass::getPluginURL( array( 'integrations' ) ), CBTxt::T( 'Integrations' ) );
		}

		if ( $action != 'menus' ) {
			CBtoolmenuBar::linkAction( 'gjmenus', cbgjClass::getPluginURL( array( 'menus' ) ), CBTxt::T( 'Menus' ) );
		}

		cbgjClass::getIntegrations( 'gj_onToolbarBE', array( $function, $user, $plugin ), null, 'raw' );
	}

	/**
	 * render backend plugin menu
	 */
	static public function showPlugin() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::cancel( 'cancelPlugin', CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend categories menu
	 */
	static public function showCategories( $function, $user, $plugin ) {
		CBtoolmenuBar::startTable();
		cbgjMenu::getDefaults( 'categories', $function, $user, $plugin );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::linkAction( 'publish', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'categories.publish', 'id' )", CBTxt::T( 'Publish' ) );
		CBtoolmenuBar::linkAction( 'unpublish', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'categories.unpublish', 'id' )", CBTxt::T( 'Unpublish' ) );
		CBtoolmenuBar::linkAction( 'copy', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'categories.copy', 'id' )", CBTxt::T( 'Copy' ) );
		CBtoolmenuBar::linkAction( 'delete', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'categories.delete', 'id' )", CBTxt::T( 'Delete' ) );
		CBtoolmenuBar::linkAction( 'edit', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'categories.edit', 'id' )", CBTxt::T( 'Edit' ) );
		CBtoolmenuBar::linkAction( 'new', cbgjClass::getPluginURL( array( 'categories', 'new' ) ), CBTxt::T( 'New' ) );
		CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbgjClass::getPluginURL() );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend category edit menu
	 */
	static public function showCategoryEdit() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::apply( 'applyPlugin', CBTxt::T( 'Apply' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbgjClass::getPluginURL( array( 'categories' ) ), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend groups menu
	 */
	static public function showGroups( $function, $user, $plugin ) {
		CBtoolmenuBar::startTable();
		cbgjMenu::getDefaults( 'groups', $function, $user, $plugin );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::linkAction( 'publish', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'groups.publish', 'id' )", CBTxt::T( 'Publish' ) );
		CBtoolmenuBar::linkAction( 'unpublish', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'groups.unpublish', 'id' )", CBTxt::T( 'Unpublish' ) );
		CBtoolmenuBar::linkAction( 'copy', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'groups.copy', 'id' )", CBTxt::T( 'Copy' ) );
		CBtoolmenuBar::linkAction( 'delete', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'groups.delete', 'id' )", CBTxt::T( 'Delete' ) );
		CBtoolmenuBar::linkAction( 'edit', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'groups.edit', 'id' )", CBTxt::T( 'Edit' ) );
		CBtoolmenuBar::linkAction( 'new', cbgjClass::getPluginURL( array( 'groups', 'new' ) ), CBTxt::T( 'New' ) );
		CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbgjClass::getPluginURL() );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend group edit menu
	 */
	static public function showGroupEdit() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::apply( 'applyPlugin', CBTxt::T( 'Apply' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbgjClass::getPluginURL( array( 'groups' ) ), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend users menu
	 */
	static public function showUsers( $function, $user, $plugin ) {
		CBtoolmenuBar::startTable();
		cbgjMenu::getDefaults( 'users', $function, $user, $plugin );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::linkAction( 'delete', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'users.delete', 'id' )", CBTxt::T( 'Delete' ) );
		CBtoolmenuBar::linkAction( 'edit', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'users.edit', 'id' )", CBTxt::T( 'Edit' ) );
		CBtoolmenuBar::linkAction( 'new', cbgjClass::getPluginURL( array( 'users', 'new' ) ), CBTxt::T( 'New' ) );
		CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbgjClass::getPluginURL() );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend user edit menu
	 */
	static public function showUserEdit() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::apply( 'applyPlugin', CBTxt::T( 'Apply' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbgjClass::getPluginURL( array( 'users' ) ), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend invites menu
	 */
	static public function showInvites( $function, $user, $plugin ) {
		CBtoolmenuBar::startTable();
		cbgjMenu::getDefaults( 'invites', $function, $user, $plugin );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::linkAction( 'delete', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'invites.delete', 'id' )", CBTxt::T( 'Delete' ) );
		CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbgjClass::getPluginURL() );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend config menu
	 */
	static public function showConfig( $function, $user, $plugin ) {
		CBtoolmenuBar::startTable();
		cbgjMenu::getDefaults( 'config', $function, $user, $plugin );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbgjClass::getPluginURL(), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend tools menu
	 */
	static public function showTools( $function, $user, $plugin ) {
		CBtoolmenuBar::startTable();
		cbgjMenu::getDefaults( 'tools', $function, $user, $plugin );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbgjClass::getPluginURL() );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend integrations menu
	 */
	static public function showIntegrations( $function, $user, $plugin ) {
		CBtoolmenuBar::startTable();
		cbgjMenu::getDefaults( 'integrations', $function, $user, $plugin );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbgjClass::getPluginURL() );
		CBtoolmenuBar::endTable();
	}

	/**
	 * render backend menus menu
	 */
	static public function showMenus( $function, $user, $plugin ) {
		CBtoolmenuBar::startTable();
		cbgjMenu::getDefaults( 'menus', $function, $user, $plugin );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbgjClass::getPluginURL(), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}
}
?>