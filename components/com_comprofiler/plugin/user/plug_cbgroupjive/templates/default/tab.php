<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveTab {

	/**
	 * render frontend tab
	 *
	 * @param mixed $categories
	 * @param mixed $groups
	 * @param mixed $joined
	 * @param mixed $invites
	 * @param mixed $invited
	 * @param moscomprofilerUser $displayed
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return mixed
	 */
	static function showTab( $categories, $groups, $joined, $invites, $invited, $displayed, $user, $plugin ) {
		$authorized		=	cbgjClass::getAuthorization( null, null, $user );

		$tabs			=	new cbTabs( 1, 1 );

		$newCategory	=	( $plugin->params->get( 'tab_new_category', 1 ) && cbgjClass::hasAccess( 'cat_create', $authorized ) );
		$newGroup		=	( $plugin->params->get( 'tab_new_group', 1 ) && cbgjClass::hasAccess( 'grp_create', $authorized ) );

		$return			=	null;

		if ( $newCategory || $newGroup ) {
			$return		.=	'<div class="gjTop gjTopCenter">'
						.		'<div class="btn-group">'
						.			( $newCategory ? '<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'new' ), true, true, false, null, true ) . '" />' : null )
						.			( $newGroup ? '<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'new' ), true, true, false, null, true ) . '" />' : null )
						.		'</div>'
						.	'</div>';
		}

		$return			.=	$tabs->startPane( 'gjTabs' )
						.		cbgjClass::getIntegrations( 'gj_onBeforeProfileTab', array( $categories, $groups, $joined, $invites, $invited, $displayed, $user, $plugin ), null, null );

		if ( $plugin->params->get( 'category_tab_display', 1 ) ) {
			$return		.=		$tabs->startTab( null, htmlspecialchars( CBTxt::P( 'My [categories]', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ) ), 'gjTabCategories' )
						.			$categories
						.		$tabs->endTab();
		}

		if ( $plugin->params->get( 'group_tab_display', 1 ) ) {
			$return		.=		$tabs->startTab( null, htmlspecialchars( CBTxt::P( 'My [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) ), 'gjTabGroups' )
						.			$groups
						.		$tabs->endTab();
		}

		if ( $plugin->params->get( 'joined_tab_display', 1 ) ) {
			$return		.=		$tabs->startTab( null, htmlspecialchars( CBTxt::P( 'Joined [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) ), 'gjTabJoined' )
						.			$joined
						.		$tabs->endTab();
		}

		if ( $plugin->params->get( 'invites_tab_display', 1 ) && ( $displayed->id == $user->id ) ) {
			$return		.=		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'My Invites' ) ), 'gjTabInvites' )
						.			$invites
						.		$tabs->endTab();
		}

		if ( $plugin->params->get( 'invited_tab_display', 1 ) && ( $displayed->id == $user->id ) ) {
			$return		.=		$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invited To' ) ), 'gjTabInvited' )
						.			$invited
						.		$tabs->endTab();
		}

		$return			.=		cbgjClass::getIntegrations( 'gj_onAfterProfileTab', array( $categories, $groups, $joined, $invites, $invited, $displayed, $user, $plugin ), null, null )
						.	$tabs->endPane();

		echo $return;
	}
}
?>