<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveGroup {

	/**
	 * render frontend group
	 *
	 * @param object $row
	 * @param cbgjCategory $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showGroup( $row, $category, $user, $plugin ) {
		$row->setPathway();

		$main			=	HTML_groupjiveGroupMain::showGroupMain( $row, $category, $user, $plugin );

		$return			=	'<div class="gjGroup">';

		if ( $plugin->params->get( 'general_panes', 1 ) ) {
			$return		.=		'<div class="gjHeader">'
						.			HTML_groupjiveGroupPanes::showGroupPanes( $row, $category, $user, $plugin )
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