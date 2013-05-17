<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjivePanelMain {

	/**
	 * render frontend panel main
	 *
	 * @param string $function
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showPanelMain( $function, $user, $plugin ) {
		global $_CB_framework;

		$authorized				=	cbgjClass::getAuthorization( null, null, $user );
		$newCategory			=	( $plugin->params->get( 'panel_new_category', 1 ) && cbgjClass::hasAccess( 'cat_create', $authorized ) );
		$newGroup				=	( $plugin->params->get( 'panel_new_group', 1 ) && cbgjClass::hasAccess( 'grp_create', $authorized ) );

		$return					=	null;

		if ( $newCategory || $newGroup ) {
			$return				.=	'<div class="gjTop gjTopCenter">'
								.		'<div class="btn-group">'
								.			( $newCategory ? '<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'new' ), true, true, false, null, true ) . '" />' : null )
								.			( $newGroup ? '<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'new' ), true, true, false, null, true ) . '" />' : null )
								.		'</div>'
								.	'</div>';
		}

		switch ( $function ) {
			case 'categories':
			default:
				if ( $plugin->params->get( 'panel_category_display', 1 ) ) {
					$title		=	htmlspecialchars( CBTxt::P( 'My [categories]', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ) );

					$_CB_framework->setPageTitle( $title );
					$_CB_framework->appendPathWay( $title, cbgjClass::getPluginURL( array( 'panel', 'categories' ) ) );

					$return		.=	cbgjTab::getCategories( $user, $user, $plugin, false );
					break;
				}
			case 'groups':
				if ( $plugin->params->get( 'panel_group_display', 1 ) ) {
					$title		=	htmlspecialchars( CBTxt::P( 'My [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) );

					$_CB_framework->setPageTitle( $title );
					$_CB_framework->appendPathWay( $title, cbgjClass::getPluginURL( array( 'panel', 'groups' ) ) );

					$return		.=	cbgjTab::getGroups( $user, $user, $plugin, false );
					break;
				}
			case 'joined':
				if ( $plugin->params->get( 'panel_joined_display', 1 ) ) {
					$title		=	htmlspecialchars( CBTxt::P( 'Joined [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) );

					$_CB_framework->setPageTitle( $title );
					$_CB_framework->appendPathWay( $title, cbgjClass::getPluginURL( array( 'panel', 'joined' ) ) );

					$return		.=	cbgjTab::getJoined( $user, $user, $plugin, false );
					break;
				}
			case 'invites':
				if ( $plugin->params->get( 'panel_invites_display', 1 ) ) {
					$title		=	htmlspecialchars( CBTxt::T( 'My Invites' ) );

					$_CB_framework->setPageTitle( $title );
					$_CB_framework->appendPathWay( $title, cbgjClass::getPluginURL( array( 'panel', 'invites' ) ) );

					$return		.=	cbgjTab::getInvites( $user, $user, $plugin, false );
					break;
				}
			case 'invited':
				if ( $plugin->params->get( 'panel_invited_display', 1 ) ) {
					$title		=	htmlspecialchars( CBTxt::T( 'Invited To' ) );

					$_CB_framework->setPageTitle( $title );
					$_CB_framework->appendPathWay( $title, cbgjClass::getPluginURL( array( 'panel', 'invited' ) ) );

					$return		.=	cbgjTab::getInvited( $user, $user, $plugin, false );
					break;
				}
		}

		return $return;
	}
}
?>