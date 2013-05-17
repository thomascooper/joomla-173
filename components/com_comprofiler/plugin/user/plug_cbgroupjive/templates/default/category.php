<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveCategory {

	/**
	 * render frontend category
	 *
	 * @param object $row
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showCategory( $row, $user, $plugin ) {
		$row->setPathway();

		$main			=	HTML_groupjiveCategoryMain::showCategoryMain( $row, $user, $plugin );

		$return			=	'<div class="gjCategory">';

		if ( $plugin->params->get( 'general_panes', 1 ) ) {
			$return		.=		'<div class="gjHeader">'
						.			HTML_groupjiveCategoryPanes::showCategoryPanes( $row, $user, $plugin )
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