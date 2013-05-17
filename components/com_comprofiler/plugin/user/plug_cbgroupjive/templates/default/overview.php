<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveOverview {

	/**
	 * render frontend overview
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showOverview( $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle	=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( cbgjClass::getOverride( 'category', true ) . ' ' . cbgjClass::getOverride( 'overview' ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( cbgjClass::getOverride( 'category', true ) . ' ' . cbgjClass::getOverride( 'overview' ), cbgjClass::getPluginURL( array( 'overview' ) ) );

		$main			=	HTML_groupjiveOverviewMain::showOverviewMain( $rows, $pageNav, $user, $plugin );

		$return			=	'<div class="gjOverview">';

		if ( $plugin->params->get( 'general_panes', 1 ) ) {
			$return		.=		'<div class="gjHeader">'
						.			HTML_groupjiveOverviewPanes::showOverviewPanes( $user, $plugin )
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