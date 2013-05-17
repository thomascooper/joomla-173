<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveTabInvites {

	/**
	 * render frontend tab invites
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param moscomprofilerUser $displayed
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param boolean $tabbed
	 * @return mixed
	 */
	static function showInvites( $rows, $pageNav, $displayed, $user, $plugin, $tabbed ) {
		global $_CB_framework;

		$invitesTabSearch			=	$plugin->params->get( 'invites_tab_search', 1 );
		$invitesTabPaging			=	$plugin->params->get( 'invites_tab_paging', 1 );
		$invitesTabLimitbox			=	$plugin->params->get( 'invites_tab_limitbox', 1 );

		if ( ! $tabbed ) {
			$formUrl				=	cbgjClass::getPluginURL( array( 'panel', 'invites' ) );
		} else {
			$formUrl				=	$_CB_framework->userProfileUrl( $displayed->id, true, $plugin->tab->tabid );
		}

		$return						=	'<div class="gjTabInvites">'
									.		'<form action="' . $formUrl . '" method="post" name="gjTabForm_invites" id="gjTabForm_invites" class="gjForm">'
									.			( $invitesTabSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) {
			$return					.=		'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$group				=	$row->getGroup();
				$category			=	$group->getCategory();

				if ( $row->get( 'user' ) ) {
					$userAvatar		=	$row->getInvitedAvatar( true );
					$userName		=	$row->getInvitedName( true );
					$userOnline		=	$row->getInvitedOnline();
				} else {
					$userAvatar		=	'<img src="' . selectTemplate() . 'images/avatar/tnnophoto_n.png" alt="' . htmlspecialchars( $row->get( 'email' ) ) . '" title="' . htmlspecialchars( $row->get( 'email' ) ) . '" />';
					$userName		=	'<a href="mailto:' . htmlspecialchars( $row->get( 'email' ) ) . '">' . htmlspecialchars( $row->get( 'email' ) ) . '</a>';
					$userOnline		=	null;
				}

				$menuItems			=	cbgjClass::getIntegrations( 'gj_onBeforeProfileGroupInviteMenu', array( $row, $group, $category, $displayed, $user, $plugin ) )
									.	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'invites', 'delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this invite?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>'
									.	cbgjClass::getIntegrations( 'gj_onAfterProfileGroupInviteMenu', array( $row, $group, $category, $displayed, $user, $plugin ) );

				$return				.=			'<div class="gjContentBox mini-layout">'
									.				'<div class="gjContentBoxRow">' . $userName . '</div>'
									.				'<div class="gjContentBoxRow">' . $userAvatar . '</div>'
									.				( $userOnline ? '<div class="gjContentBoxRow">' . $userOnline . '</div>' : null )
									.				'<div class="gjContentBoxRow">' . $group->getName( 0, true ) . '</div>'
									.				'<div class="gjContentBoxRow">' . $category->getName( 0, true ) . '</div>'
									.				'<div class="gjContentBoxRow">'
									.					cbgjClass::getIntegrations( 'gj_onBeforeProfileGroupInviteInfo', array( $row, $group, $category, $displayed, $user, $plugin ) )
									.					'<span title="' . cbFormatDate( $row->get( 'invited' ), 1, false ) . ( $row->isAccepted() ? ' - ' . cbFormatDate( $row->get( 'accepted' ), 1, false ) : null ) . '">' . $row->getStatus() . '</span>'
									.					cbgjClass::getIntegrations( 'gj_onAfterProfileGroupInviteInfo', array( $row, $group, $category, $displayed, $user, $plugin ) )
									.				'</div>';

				if ( ( ! $row->isAccepted() ) && ( $row->dateDifference() >= 5 ) ) {
					$return			.=				'<div class="gjContentBoxRow">'
									.					'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Resend' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'invites', 'send', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />'
									.				'</div>';
				}

				$return				.=				'<div class="gjContentBoxRow">'
									.					cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) )
									.				'</div>';

				$return				.=			'</div>';
			}

			$return					.=		'</div>';
		} else {
			$return					.=			'<div class="gjContent">';

			if ( $invitesTabSearch && $pageNav->searching ) {
				$return				.=				CBTxt::Th( 'No invite search results found.' );
			} else {
				if ( $displayed->id == $user->id ) {
					$return			.=				CBTxt::Th( 'You have no invites.' );
				} else {
					$return			.=				CBTxt::Th( 'This user has no invites.' );
				}
			}

			$return					.=			'</div>';
		}

		if ( $invitesTabPaging ) {
			$return					.=			'<div class="gjPaging pagination pagination-centered">'
									.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.				( ! $invitesTabLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.			'</div>';
		}

		$return						.=			cbGetSpoofInputTag( 'plugin' )
									.		'</form>'
									.	'</div>';

		return $return;
	}
}
?>