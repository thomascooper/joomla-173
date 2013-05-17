<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveGroupMain {

	/**
	 * render frontend group main
	 *
	 * @param cbgjGroup $row
	 * @param cbgjCategory $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showGroupMain( $row, $category, $user, $plugin ) {
		global $_CB_framework;

		$componentClass			=	new CBplug_cbgroupjive();
		$authorized				=	cbgjClass::getAuthorization( $category, $row, $user );

		$tabs					=	new cbTabs( 1, 1 );

		$beforeTab				=	cbgjClass::getIntegrations( 'gj_onBeforeGroupTab', array( $tabs, $row, $category, $user, $plugin ), null, null );
		$afterTab				=	cbgjClass::getIntegrations( 'gj_onAfterGroupTab', array( $tabs, $row, $category, $user, $plugin ), null, null );

		$return					=	null;

		if ( ( $row->get( 'published' ) == -1 ) && $plugin->params->get( 'group_approve', 0 ) ) {
			$return				.=	'<div class="alert alert-error">' . CBTxt::P( 'This [group] is currently pending approval.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</div>';
		} elseif ( ( $row->get( 'published' ) == 0 ) || ( ( $row->get( 'published' ) == 1 ) && ( ! cbgjClass::hasAccess( 'grp_approved', $authorized ) ) ) ) {
			$return				.=	'<div class="alert alert-error">' . CBTxt::P( 'This [group] is currently unpublished.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</div>';
		} else {
			if ( $user->id ) {
				if ( ( $row->get( 'type' ) == 2 ) && cbgjClass::hasAccess( 'grp_usr_inactive', $authorized ) ) {
					$return		.=	'<div class="alert alert-error">' . CBTxt::P( '[group] join request awaiting approval.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</div>';
				}
			}
		}

		if ( $plugin->params->get( 'group_new_group', 0 ) && cbgjClass::hasAccess( 'grp_nested_create', $authorized ) ) {
			$return				.=	'<div class="gjTop gjTopCenter">'
								.		'<div class="btn-group">'
								.			'<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'new', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />'
								.		'</div>'
								.	'</div>';
		}

		$return					.=	$tabs->startPane( 'gjGroupTabs' );

		$tabContent				=	false;

		if ( cbgjClass::hasAccess( 'grp_approved', $authorized ) && $row->nestedCount() && ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) || $row->get( 'nested' ) ) ) {
			$return				.=		$tabs->startTab( null, cbgjClass::getOverride( 'group', true ), 'gjGroups' )
								.			'<div class="gjNestedGroups">'
								.				$componentClass->showNestedGroups( $row, $category, $user, $plugin )
								.			'</div>'
								.		$tabs->endTab();

			$tabContent			=	true;
		}

		$return					.=		$beforeTab;

		if ( cbgjClass::hasAccess( 'grp_approved', $authorized ) && ( ( ( ! $row->get( 'users' ) ) && cbgjClass::hasAccess( 'mod_lvl5', $authorized ) ) || $row->users ) ) {
			$return				.=		$tabs->startTab( null, cbgjClass::getOverride( 'user', true ), 'gjUsers' )
								.			'<div class="gjUsers">'
								.				$componentClass->showUsers( $row, $category, $user, $plugin )
								.			'</div>'
								.		$tabs->endTab();

			$tabContent			=	true;
		}

		if ( cbgjClass::hasAccess( array( 'grp_approved', 'grp_invite' ), $authorized, true ) ) {
			$return				.=		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invites' ) ), 'gjInvites' )
								.			'<div class="gjInvites">'
								.				$componentClass->showInvites( $category, $row, $user, $plugin )
								.			'</div>'
								.		$tabs->endTab();

			$tabContent			=	true;
		}

		if ( ( ! $tabContent ) && ( ! $beforeTab ) && ( ! $afterTab ) ) {
			if ( $user->id ) {
				if ( cbgjClass::hasAccess( 'grp_approved', $authorized ) && ( ! ( $row->get( 'type' ) == 2 ) && cbgjClass::hasAccess( 'grp_usr_inactive', $authorized ) ) ) {
					$return		.=		'<div>'
								.			CBTxt::Ph( 'Please join the [group] to view its content.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) )
								.		'</div>';
				}
			} else {
				$return			.=		'<div>'
								.			CBTxt::Ph( 'Please login or register to view [group] content.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) )
								.		'</div>';
			}
		}

		$return					.=		$afterTab
								.	$tabs->endPane();

		if ( isset( $_GET['tab'] ) ) {
			$tab				=	urldecode( stripslashes( cbGetParam( $_GET, 'tab' ) ) );
		} elseif ( isset( $_POST['tab'] ) ) {
			$tab				=	stripslashes( cbGetParam( $_POST, 'tab' ) );
		} else {
			$tab				=	null;
		}

		if ( $tab ) {
			$js					=	"$( '#gjGroupTabs .tab-row .tab a' ).each( function() {"
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