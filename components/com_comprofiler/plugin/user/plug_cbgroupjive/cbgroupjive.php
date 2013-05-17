<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

if ( $_CB_framework->getUi() == 2 ) {
	require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/admin.cbgroupjive.php' );
}

if ( $_CB_framework->getUi() == 1 ) {
	require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/component.cbgroupjive.php' );
	require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/tab.cbgroupjive.php' );
}

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/plugin.cbgroupjive.php' );
?>