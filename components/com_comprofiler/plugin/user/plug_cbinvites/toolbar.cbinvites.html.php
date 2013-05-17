<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/administrator/components/' . $plugin->option . '/toolbar.comprofiler.html.php' );

class cbinvitesMenu {

	static public function showPlugin() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::cancel( 'cancelPlugin', CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	static public function showInvites() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::linkAction( 'cbinvites-plugin', cbinvitesClass::getPluginURL(), CBTxt::T( 'Plugin' ) );
		CBtoolmenuBar::linkAction( 'cbinvites-config', cbinvitesClass::getPluginURL( array( 'config' ) ), CBTxt::T( 'Config' ) );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::linkAction( 'upload', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'invites.send', 'id' )", CBTxt::T( 'Resend' ) );
		CBtoolmenuBar::linkAction( 'delete', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'invites.delete', 'id' )", CBTxt::T( 'Delete' ) );
		CBtoolmenuBar::linkAction( 'edit', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'invites.edit', 'id' )", CBTxt::T( 'Edit' ) );
		CBtoolmenuBar::linkAction( 'new', cbinvitesClass::getPluginURL( array( 'invites', 'new' ) ), CBTxt::T( 'New' ) );
		CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbinvitesClass::getPluginURL() );
		CBtoolmenuBar::endTable();
	}

	static public function showInviteEdit() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbinvitesClass::getPluginURL( array( 'invites' ) ), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	static public function showConfig() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::linkAction( 'cbinvites-plugin', cbinvitesClass::getPluginURL(), CBTxt::T( 'Plugin' ) );
		CBtoolmenuBar::linkAction( 'cbinvites-invites', cbinvitesClass::getPluginURL( array( 'invites' ) ), CBTxt::T( 'Invites' ) );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbinvitesClass::getPluginURL(), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}
}
?>