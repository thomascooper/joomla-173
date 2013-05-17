<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjivePanelPanes {

	/**
	 * render frontend panel panes
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showPanelPanes( $user, $plugin ) {
		$panelDesc				=	CBTxt::Th( $plugin->params->get( 'panel_desc', null ) );
		$panelCategoryDisplay	=	$plugin->params->get( 'panel_category_display', 1 );
		$panelGroupDisplay		=	$plugin->params->get( 'panel_group_display', 1 );
		$panelJoinedDisplay		=	$plugin->params->get( 'panel_joined_display', 1 );
		$panelInvitesDisplay	=	$plugin->params->get( 'panel_invites_display', 1 );
		$panelInvitedDisplay	=	$plugin->params->get( 'panel_invited_display', 1 );
		$authorized				=	cbgjClass::getAuthorization( null, null, $user );
		$ownedCategories		=	count( cbgjData::getCategories( null, array( 'user_id', '=', $user->id ) ) );

		if ( $plugin->params->get( 'group_tab_joined', 0 ) ) {
			$access				=	array( 'user_id', '=', (int) $user->id, array( 'e.user_id', '=', (int) $user->id, 'e.status', '!IN', array( -1, 0, 4 ) ) );
		} else {
			$access				=	array( 'user_id', '=', (int) $user->id );
		}

		$ownedGroups			=	count( cbgjData::getGroups( null, $access ) );

		if ( $plugin->params->get( 'joined_tab_owned', 0 ) ) {
			$access				=	array( 'user_id', '=', (int) $user->id, array( 'e.user_id', '=', (int) $user->id, 'e.status', '!IN', array( -1, 0, 4 ) ) );
		} else {
			$access				=	array( 'e.user_id', '=', (int) $user->id, 'e.status', '!IN', array( -1, 0, 4 ) );
		}

		$joinedGroups			=	count( cbgjData::getGroups( null, $access ) );
		$ownedInvites			=	count( cbgjData::getInvites( null, array( 'user_id', '=', (int) $user->id ) ) );
		$invitedTo				=	count( cbgjData::getInvites( null, array( 'user', '=', (int) $user->id, array( 'email', '=', $user->email ) ) ) );

		$return					=	'<legend class="gjHeaderTitle">' . cbgjClass::getOverride( 'panel' ) . '</legend>'
								.	'<div class="gjGrid row-fluid">'
								.		'<div class="gjGridLeft span9">'
								.			'<div class="gjGridLeftLogo span4">'
								.				'<img alt="' . htmlspecialchars( CBTxt::Th( 'Logo' ) ) . '" src="' . $plugin->livePath . '/images/' . $plugin->params->get( 'panel_logo', 'default_panel.png' ) . '" class="gjLogoDefault img-polaroid" />'
								.			'</div>'
								.			'<div class="gjGridLeftInfo span8">'
								.				cbgjClass::getIntegrations( 'gj_onBeforePanelInfo', array( $user, $plugin ) )
								.				( $panelCategoryDisplay && $ownedCategories ? '<div>' . cbgjClass::getOverride( 'category', true ) . ': ' . $ownedCategories . '</div>' : null )
								.				( $panelGroupDisplay && $ownedGroups ? '<div>' . cbgjClass::getOverride( 'group', true ) . ': ' . $ownedGroups . '</div>' : null )
								.				( $panelJoinedDisplay && $joinedGroups ? '<div>' . CBTxt::Ph( 'Joined: [grp_joined_count]', array( '[grp_joined_count]' => $joinedGroups ) ) . '</div>' : null )
								.				( $panelInvitesDisplay && $ownedInvites ? '<div>' . CBTxt::Ph( 'Invites: [grp_invite_count]', array( '[grp_invite_count]' => $ownedInvites ) ) . '</div>' : null )
								.				( $panelInvitedDisplay && $invitedTo ? '<div>' . CBTxt::Ph( 'Invited: [grp_invited_count]', array( '[grp_invited_count]' => $invitedTo ) ) . '</div>' : null )
								.				cbgjClass::getIntegrations( 'gj_onAfterPanelInfo', array( $user, $plugin ) )
								.			'</div>';

		if ( $panelDesc ) {
			if ( $plugin->params->get( 'panel_desc_content', 0 ) ) {
				$panelDesc		=	cbgjClass::prepareContentPlugins( $panelDesc );
			}

			$return				.=			'<div class="gjGridLeftDesc span12 well well-small">' . $panelDesc . '</div>';
		}

		$return					.=		'</div>'
								.		'<div class="gjGridRight span3">'
								.			cbgjClass::getIntegrations( 'gj_onBeforePanelMenu', array( $user, $plugin ) )
								.			( $panelCategoryDisplay && cbgjClass::hasAccess( 'usr_reg', $authorized ) ? '<div><i class="icon-home"></i> <a href="' . cbgjClass::getPluginURL( array( 'panel', 'categories' ) ) . '">' . CBTxt::Ph( 'My [categories]', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ) . '</a></div>' : null )
								.			( $panelGroupDisplay && cbgjClass::hasAccess( 'usr_reg', $authorized ) ? '<div><i class="icon-user"></i> <a href="' . cbgjClass::getPluginURL( array( 'panel', 'groups' ) ) . '">' . CBTxt::Ph( 'My [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) . '</a></div>' : null )
								.			( $panelJoinedDisplay && cbgjClass::hasAccess( 'usr_reg', $authorized ) ? '<div><i class="icon-user"></i> <a href="' . cbgjClass::getPluginURL( array( 'panel', 'joined' ) ) . '">' . CBTxt::Ph( 'Joined [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) . '</a></div>' : null )
								.			( $panelInvitesDisplay && cbgjClass::hasAccess( 'usr_reg', $authorized ) ? '<div><i class="icon-inbox"></i> <a href="' . cbgjClass::getPluginURL( array( 'panel', 'invites' ) ) . '">' . CBTxt::Th( 'My Invites' ) . '</a></div>' : null )
								.			( $panelInvitedDisplay && cbgjClass::hasAccess( 'usr_reg', $authorized ) ? '<div><i class="icon-inbox"></i> <a href="' . cbgjClass::getPluginURL( array( 'panel', 'invited' ) ) . '">' . CBTxt::Th( 'Invited To' ) . '</a></div>' : null )
								.			cbgjClass::getIntegrations( 'gj_onAfterPanelMenu', array( $user, $plugin ) )
								.			( cbgjClass::hasAccess( 'gen_usr_notifications', $authorized ) ? '<div><i class="icon-info-sign"></i> <a href="' . cbgjClass::getPluginURL( array( 'notifications', 'show' ) ) . '">' . CBTxt::Th( 'Notifications' ) . '</a></div>' : null )
								.			'<div><i class="icon-share-alt"></i> <a href="' . cbgjClass::getPluginURL( array( 'overview' ) ) . '">' . CBTxt::Ph( 'Back to [overview]', array( '[overview]' => cbgjClass::getOverride( 'overview' ) ) ) . '</a></div>'
								.		'</div>'
								.	'</div>';

		return $return;
	}
}
?>