<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveOverviewPanes {

	/**
	 * render frontend overview panes
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showOverviewPanes( $user, $plugin ) {
		$overviewDesc			=	CBTxt::Th( $plugin->params->get( 'overview_desc', null ) );
		$authorized				=	cbgjClass::getAuthorization( null, null, $user );
		$categoryCount			=	count( cbgjData::getCategories( array( 'cat_access', 'mod_lvl1' ), array( 'parent', '=', 0 ) ) );

		$return					=	'<legend class="gjHeaderTitle">' . cbgjClass::getOverride( 'category', true ) . ' ' . cbgjClass::getOverride( 'overview' ) . '</legend>'
								.	'<div class="gjGrid row-fluid">'
								.		'<div class="gjGridLeft span9">'
								.			'<div class="gjGridLeftLogo span4">'
								.				'<img alt="' . htmlspecialchars( CBTxt::T( 'Logo' ) ) . '" src="' . $plugin->livePath . '/images/' . $plugin->params->get( 'overview_logo', 'default_overview.png' ) . '" class="gjLogoDefault img-polaroid" />'
								.			'</div>'
								.			'<div class="gjGridLeftInfo span8">'
								.				cbgjClass::getIntegrations( 'gj_onBeforeOverviewInfo', array( $user, $plugin ) )
								.				( $categoryCount ? '<div>' . cbgjClass::getOverride( 'category', true ) . ': ' . $categoryCount . '</div>' : null )
								.				cbgjClass::getIntegrations( 'gj_onAfterOverviewInfo', array( $user, $plugin ) )
								.			'</div>';

		if ( $overviewDesc ) {
			if ( $plugin->params->get( 'overview_desc_content', 0 ) ) {
				$overviewDesc	=	cbgjClass::prepareContentPlugins( $overviewDesc );
			}

			$return				.=			'<div class="gjGridLeftDesc span12 well well-small">' . $overviewDesc . '</div>';
		}

		$return					.=		'</div>'
								.		'<div class="gjGridRight span3">'
								.			cbgjClass::getIntegrations( 'gj_onBeforeOverviewMenu', array( $user, $plugin ), null, null )
								.			( cbgjClass::hasAccess( 'cat_create', $authorized ) ? '<div><i class="icon-plus"></i> <a href="' . cbgjClass::getPluginURL( array( 'categories', 'new' ) ) . '">' . CBTxt::Ph( 'New [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div>' : null )
								.			( cbgjClass::hasAccess( 'usr_mod', $authorized ) && $categoryCount ? '<div><i class="icon-envelope"></i> <a href="' . cbgjClass::getPluginURL( array( 'overview', 'message' ) ) . '">' . CBTxt::Ph( 'Message [categories]', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ) . '</a></div>' : null )
								.			( cbgjClass::hasAccess( 'usr_panel', $authorized ) ? '<div><i class="icon-home"></i> <a href="' . cbgjClass::getPluginURL( array( 'panel' ) ) . '">' . CBTxt::Ph( 'My [panel]', array( '[panel]' => cbgjClass::getOverride( 'panel' ) ) ) . '</a></div>' : null )
								.			cbgjClass::getIntegrations( 'gj_onAfterOverviewMenu', array( $user, $plugin ), null, null )
								.			( cbgjClass::hasAccess( 'gen_usr_notifications', $authorized ) ? '<div><i class="icon-info-sign"></i> <a href="' . cbgjClass::getPluginURL( array( 'notifications', 'show' ) ) . '">' . CBTxt::Th( 'Notifications' ) . '</a></div>' : null )
								.		'</div>'
								.	'</div>';

		return $return;
	}
}
?>