<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveCategoryMain {

	/**
	 * render frontend category main
	 *
	 * @param cbgjCategory $row
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showCategoryMain( $row, $user, $plugin ) {
		global $_CB_framework;

		$componentClass			=	new CBplug_cbgroupjive();
		$authorized				=	cbgjClass::getAuthorization( $row, null, $user );

		$tabs					=	new cbTabs( 1, 1 );

		$newCategory			=	( $plugin->params->get( 'category_new_category', 0 ) && cbgjClass::hasAccess( 'cat_nested_create', $authorized ) );
		$newGroup				=	( $plugin->params->get( 'category_new_group', 0 ) && cbgjClass::hasAccess( 'cat_grp_create', $authorized ) );
		$hasNested				=	( $row->nestedCount() && cbgjClass::hasAccess( 'cat_approved', $authorized ) );
		$beforeTab				=	cbgjClass::getIntegrations( 'gj_onBeforeCategoryTab', array( $tabs, $row, $user, $plugin ), null, null );
		$afterTab				=	cbgjClass::getIntegrations( 'gj_onAfterCategoryTab', array( $tabs, $row, $user, $plugin ), null, null );

		$return					=	null;

		if ( ( $row->get( 'published' ) == -1 ) && $plugin->params->get( 'category_approve', 0 ) ) {
			$return				.=	'<div class="alert alert-error">' . CBTxt::P( 'This [category] is currently pending approval.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</div>';
		} elseif ( ( $row->get( 'published' ) == 0 ) || ( ( $row->get( 'published' ) == 1 ) && ( ! cbgjClass::hasAccess( 'cat_approved', $authorized ) ) ) ) {
			$return				.=	'<div class="alert alert-error">' . CBTxt::P( 'This [category] is currently unpublished.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</div>';
		}

		if ( $newCategory || $newGroup ) {
			$return				.=	'<div class="gjTop gjTopCenter">'
								.		'<div class="btn-group">'
								.			( $newCategory ? '<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'new', (int) $row->get( 'id' ) ), true ) . '" />' : null )
								.			( $newGroup ? '<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'new', (int) $row->get( 'id' ) ), true ) . '" />' : null )
								.		'</div>'
								.	'</div>';
		}

		if ( $beforeTab || $afterTab || $hasNested ) {
			$return				.=	$tabs->startPane( 'getCategoryTabs' );

			if ( $hasNested && ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) || $row->get( 'nested' ) ) ) {
				$return			.=		$tabs->startTab( null, cbgjClass::getOverride( 'category', true ), 'gjCategories' )
								.			'<div class="gjNestedCategories">'
								.				$componentClass->showNestedCategories( $row, $user, $plugin )
								.			'</div>'
								.		$tabs->endTab();
			}

			$return				.=		$beforeTab;

			if ( cbgjClass::hasAccess( 'cat_approved', $authorized ) ) {
				$return			.=		$tabs->startTab( null, cbgjClass::getOverride( 'group', true ), 'gjGroups' );
			}
		}

		if ( cbgjClass::hasAccess( 'cat_approved', $authorized ) ) {
			$return				.=			'<div class="gjCategoryGroups">'
								.				$componentClass->showCategoryGroups( $row, $user, $plugin )
								.			'</div>';
		}

		if ( $beforeTab || $afterTab || $hasNested ) {
			if ( cbgjClass::hasAccess( 'cat_approved', $authorized ) ) {
				$return			.=		$tabs->endTab();
			}

			$return				.=		$afterTab
								.	$tabs->endPane();
		}

		if ( isset( $_GET['tab'] ) ) {
			$tab				=	urldecode( stripslashes( cbGetParam( $_GET, 'tab' ) ) );
		} elseif ( isset( $_POST['tab'] ) ) {
			$tab				=	stripslashes( cbGetParam( $_POST, 'tab' ) );
		} else {
			$tab				=	null;
		}

		if ( $tab ) {
			$js					=	"$( '#getCategoryTabs .tab-row .tab a' ).each( function() {"
								.		"if ( $( this ).text().toLowerCase() == '" . addslashes( strtolower( $tab ) ) . "' ) {"
								.			"$( this ).parent( '.tab' ).trigger( 'click' );"
								.		"}"
								.	"});";

			$_CB_framework->outputCbJQuery( $js );
		}

		return $return;
	}
}
?>