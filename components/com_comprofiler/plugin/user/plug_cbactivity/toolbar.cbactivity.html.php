<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/administrator/components/' . $plugin->option . '/toolbar.comprofiler.html.php' );

class cbactivityMenu {

	static public function showPlugin() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::cancel( 'cancelPlugin', CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	static public function showActivity() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::linkAction( 'cbactivity-plugin', cbactivityClass::getPluginURL(), CBTxt::T( 'Plugin' ) );
		CBtoolmenuBar::linkAction( 'cbactivity-config', cbactivityClass::getPluginURL( array( 'config' ) ), CBTxt::T( 'Config' ) );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::linkAction( 'delete', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'activity.delete', 'id' )", CBTxt::T( 'Delete' ) );
		CBtoolmenuBar::linkAction( 'edit', "javascript:cbDoListTask( this, 'editPlugin', 'action', 'activity.edit', 'id' )", CBTxt::T( 'Edit' ) );
		CBtoolmenuBar::linkAction( 'new', cbactivityClass::getPluginURL( array( 'activity', 'new' ) ), CBTxt::T( 'New' ) );
		CBtoolmenuBar::back( CBTxt::T( 'Back' ), cbactivityClass::getPluginURL() );
		CBtoolmenuBar::endTable();
	}

	static public function showActivityEdit() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::apply( 'applyPlugin', CBTxt::T( 'Apply' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbactivityClass::getPluginURL( array( 'activity' ) ), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}

	static public function showConfig() {
		CBtoolmenuBar::startTable();
		CBtoolmenuBar::linkAction( 'cbactivity-plugin', cbactivityClass::getPluginURL(), CBTxt::T( 'Plugin' ) );
		CBtoolmenuBar::linkAction( 'cbactivity-activity', cbactivityClass::getPluginURL( array( 'activity' ) ), CBTxt::T( 'Activity' ) );
		CBtoolmenuBar::spacer( '50px' );
		CBtoolmenuBar::save( 'savePlugin', CBTxt::T( 'Save' ) );
		CBtoolmenuBar::linkAction( 'cancel', cbactivityClass::getPluginURL(), CBTxt::T( 'Cancel' ) );
		CBtoolmenuBar::endTable();
	}
}
?>