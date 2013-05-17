<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjivePanel {

	/**
	 * render frontend panel
	 *
	 * @param string $function
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showPanel( $function, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle	=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( cbgjClass::getOverride( 'panel' ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( cbgjClass::getOverride( 'category', true ) . ' ' . cbgjClass::getOverride( 'overview' ), cbgjClass::getPluginURL( array( 'overview' ) ) );
		$_CB_framework->appendPathWay( cbgjClass::getOverride( 'panel' ), cbgjClass::getPluginURL( array( 'panel' ) ) );

		$main			=	HTML_groupjivePanelMain::showPanelMain( $function, $user, $plugin );

		$return			=	'<div class="gjPanel">';

		if ( $plugin->params->get( 'general_panes', 1 ) ) {
			$return		.=		'<div class="gjHeader">'
						.			HTML_groupjivePanelPanes::showPanelPanes( $user, $plugin )
						.		'</div>'
						.		'<div class="gjBody">'
						.			$main
						.		'</div>';
		} else {
			$return		.=		$main;
		}

		$return			.=	'</div>';

		echo $return;
	}
}
?>