<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveInvites {

	/**
	 * render frontend invites
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param cbgjCategory $category
	 * @param cbgjGroup $group
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showInvites( $rows, $pageNav, $category, $group, $user, $plugin ) {
		global $_CB_framework;

		$groupInvitesSearch			=	( $plugin->params->get( 'group_invites_search', 1 ) && ( $pageNav->searching || $pageNav->total ) );
		$groupInvitesPaging			=	$plugin->params->get( 'group_invites_paging', 1 );
		$groupInvitesLimitbox		=	$plugin->params->get( 'group_invites_limitbox', 1 );
		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user );
		$groupInvitesToggle			=	( $plugin->params->get( 'group_toggle', 3 ) > 1 );
		$data						=	cbgjClass::parseParams( $_POST );

		if ( $plugin->params->get( 'group_invites_list', 0 ) ) {
			$connections			=	cbgjClass::getConnectionsList( $user );

			if ( $connections ) {
				$js					=	"$( '#invites_conn' ).change( function() {"
									.		"$( '#invites_invite' ).attr( 'value', $( this ).val() ).focus().keyup();"
									.		"$( this ).attr( 'value', '' );"
									.	"});";

				$_CB_framework->outputCbJQuery( $js );

				array_unshift( $connections, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Connection -' ) ) );

				$usersList			=	moscomprofilerHTML::selectList( $connections, 'invites_conn', null, 'value', 'text', null, 0, false, false );
			}
		}

		if ( $plugin->params->get( 'group_invites_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
			$captcha				=	cbgjCaptcha::render();
		} else {
			$captcha				=	false;
		}

		$return						=	'<form action="' . cbgjClass::getPluginURL( array( 'invites', 'send', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '" method="post" name="gjForm_invite" id="gjForm_invite" class="gjForm gjToggle form-horizontal">'
									.		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Invite' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				'<input type="text" size="35" class="input-large required" value="' . htmlspecialchars( $data->get( 'invites_invite', null ) ) . '" name="invites_invite" id="invites_invite" />'
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.					cbgjClass::getIcon( CBTxt::P( 'Invite by [invite_types].', array( '[invite_types]' => implode( ', ', cbgjInvite::inviteBy() ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';

		if ( isset( $usersList ) ) {
			$return					.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Connection' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$usersList
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::T( 'Pre-fill invite with a connection.' ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
		}

		if ( $captcha !== false ) {
			$return					.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Captcha' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				'<div style="margin-bottom: 5px;">' . $captcha['code'] . '</div>'
									.				'<div>' . $captcha['input'] . '</div>'
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.				'</span>'
									.			'</div>'
									.		'</div>';
		}

		$return						.=		'<div class="gjButtonWrapper form-actions">'
									.			'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Send Invite' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
									.			( $groupInvitesToggle ? '<a href="#gjInviteToggle" role="button" class="gjButton gjButtonCancel btn btn-mini gjToggleCollapse">' . CBTxt::Th( 'Cancel' ) . '</a>' : null )
									.		'</div>'
									.		cbGetSpoofInputTag( 'plugin' )
									.	'</form>'
									.	'<form action="' . $group->getUrl() . '" method="post" name="gjForm_invites" id="gjForm_invites" class="gjForm">';

		if ( $groupInvitesToggle || $groupInvitesSearch ) {
			$return					.=		'<div class="gjTop row-fluid">'
									.			'<div class="gjTop gjTopLeft span6">'
									.				( $groupInvitesToggle ? '<a href="#gjForm_invite" id="gjInviteToggle" role="button" class="gjButton btn gjToggleExpand">' . CBTxt::Th( 'New Invite' ) . '</a>' : null )
									.			'</div>'
									.			'<div class="gjTop gjTopRight span6">'
									.				( $groupInvitesSearch ? $pageNav->search : null )
									.			'</div>'
									.		'</div>';
		}

		if ( $rows ) {
			$return					.=		'<div class="gjContent">';

			foreach ( $rows as $row ) {
				if ( $row->get( 'user' ) ) {
					$userAvatar		=	$row->getInvitedAvatar( true );
					$userName		=	$row->getInvitedName( true );
					$userOnline		=	$row->getInvitedOnline();
				} else {
					$userAvatar		=	'<img src="' . selectTemplate() . 'images/avatar/tnnophoto_n.png" alt="' . htmlspecialchars( $row->get( 'email' ) ) . '" title="' . htmlspecialchars( $row->get( 'email' ) ) . '" />';
					$userName		=	'<a href="mailto:' . htmlspecialchars( $row->get( 'email' ) ) . '">' . htmlspecialchars( $row->get( 'email' ) ) . '</a>';
					$userOnline		=	null;
				}

				$menuItems			=	cbgjClass::getIntegrations( 'gj_onBeforeGroupInviteMenu', array( $row, $group, $category, $user, $plugin ) )
									.	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'invites', 'delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this invite?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>'
									.	cbgjClass::getIntegrations( 'gj_onAfterGroupInviteMenu', array( $row, $group, $category, $user, $plugin ) );

				$return				.=			'<div class="gjContentBox mini-layout">'
									.				'<div class="gjContentBoxRow">' . $userName . '</div>'
									.				'<div class="gjContentBoxRow">' . $userAvatar . '</div>'
									.				( $userOnline ? '<div class="gjContentBoxRow">' . $userOnline . '</div>' : null )
									.				'<div class="gjContentBoxRow">'
									.					cbgjClass::getIntegrations( 'gj_onBeforeGroupInviteInfo', array( $row, $group, $category, $user, $plugin ) )
									.					'<span title="' . cbFormatDate( $row->get( 'invited' ), 1, false ) . ( $row->isAccepted() ? ' - ' . cbFormatDate( $row->get( 'accepted' ), 1, false ) : null ) . '">' . $row->getStatus() . '</span>'
									.					cbgjClass::getIntegrations( 'gj_onAfterGroupInviteInfo', array( $row, $group, $category, $user, $plugin ) )
									.				'</div>';

				if ( ( ! $row->isAccepted() ) && ( $row->dateDifference() >= 5 ) ) {
					$return			.=				'<div class="gjContentBoxRow">'
									.					'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Resend' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'invites', 'send', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />'
									.				'</div>';
				}

				$return				.=				'<div class="gjContentBoxRow">'
									.					cbgjClass::getDropdown( $menuItems, CBTxt::T( 'Menu' ) )
									.				'</div>';

				$return				.=			'</div>';
			}

			$return					.=		'</div>';
		} else {
			$return					.=		'<div class="gjContent">';

			if ( $groupInvitesSearch && $pageNav->searching ) {
				$return				.=			CBTxt::Th( 'No invite search results found.' );
			} else {
				$return				.=			CBTxt::Th( 'There are no invites available.' );
			}

			$return					.=		'</div>';
		}

		if ( $groupInvitesPaging ) {
			$return					.=		'<div class="gjPaging pagination pagination-centered">'
									.			( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.			( ! $groupInvitesLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.		'</div>';
		}

		$return						.=		cbGetSpoofInputTag( 'plugin' )
									.	'</form>';

		return $return;
	}
}
?>