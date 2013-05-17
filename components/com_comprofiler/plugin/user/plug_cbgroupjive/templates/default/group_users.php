<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveUsers {

	/**
	 * render frontend users
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param cbgjCategory $category
	 * @param cbgjGroup $group
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showUsers( $rows, $pageNav, $category, $group, $user, $plugin ) {
		$groupUsersSearch		=	$plugin->params->get( 'group_users_search', 1 );
		$groupUsersPaging		=	$plugin->params->get( 'group_users_paging', 1 );
		$groupUsersLimitbox		=	$plugin->params->get( 'group_users_limitbox', 1 );

		$return					=	'<form action="' . $group->getUrl() . '" method="post" name="gjForm_users" id="gjForm_users" class="gjUsers_form">'
								.		( $groupUsersSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) {
			$return				.=		'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$authorized		=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );
				$adminUrl		=	cbgjClass::getPluginURL( array( 'users', 'admin', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to set this [user] to [admin]?', array( '[user]' => cbgjClass::getOverride( 'user' ), '[admin]' => cbgjClass::getOverride( 'admin' ) ) ) );
				$modUrl			=	cbgjClass::getPluginURL( array( 'users', 'mod', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to set this [user] to [mod]?', array( '[user]' => cbgjClass::getOverride( 'user' ), '[mod]' => cbgjClass::getOverride( 'moderator' ) ) ) );
				$activeUrl		=	cbgjClass::getPluginURL( array( 'users', 'active', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to set this [user] as Active?', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );
				$inactiveUrl	=	cbgjClass::getPluginURL( array( 'users', 'inactive', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to set this [user] as Inactive?', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );
				$banUrl			=	cbgjClass::getPluginURL( array( 'users', 'ban', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to ban this [user]?', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );
				$deleteUrl		=	cbgjClass::getPluginURL( array( 'users', 'delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to delete this [user]?', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );

				if ( $row->get( 'status' ) == 0 ) {
					$typeClass	=	'gjUserTypePENDING';
					$type		=	CBTxt::Th( 'Pending' );
				} elseif ( $row->get( 'status' ) == -1 ) {
					$typeClass	=	'gjUserTypeBANNED';
					$type		=	CBTxt::Th( 'Banned' );
				} elseif ( $row->get( 'status' ) == 2 ) {
					$typeClass	=	'gjUserTypeMOD';
					$type		=	cbgjClass::getOverride( 'moderator' );
				} elseif ( $row->get( 'status' ) == 3 ) {
					$typeClass	=	'gjUserTypeADMIN';
					$type		=	cbgjClass::getOverride( 'admin' );
				} elseif ( $row->get( 'status' ) == 4 ) {
					$typeClass	=	'gjUserTypeOWNER';
					$type		=	cbgjClass::getOverride( 'owner' );
				} else {
					$typeClass	=	'gjUserTypeUSER';
					$type		=	cbgjClass::getOverride( 'user' );
				}

				if ( ( ! in_array( $row->get( 'status' ), array( -1, 4 ) ) ) && ( ( $row->get( 'status' ) != 3 ) || cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) && cbgjClass::hasAccess( 'mod_lvl3', $authorized ) ) {
					$ban		=	'<div><a href="javascript: void(0);" onclick="' . $banUrl . '"><i class="icon-lock"></i> ' . CBTxt::T( 'Ban' ) . '</a></div>';
				} elseif ( ( $row->get( 'status' ) == -1 ) && cbgjClass::hasAccess( 'mod_lvl3', $authorized ) ) {
					$ban		=	'<div><a href="javascript: void(0);" onclick="' . $activeUrl . '"><i class="icon-ok"></i> ' . CBTxt::T( 'Unban' ) . '</a></div>';
				} else {
					$ban		=	null;
				}

				if ( ( $row->get( 'status' ) == 1 ) && cbgjClass::hasAccess( 'mod_lvl3', $authorized ) ) {
					$promote	=	'<div><a href="javascript: void(0);" onclick="' . $modUrl . '"><i class="icon-thumbs-up"></i> ' . CBTxt::T( 'Promote' ) . '</a></div>';
				} elseif ( ( $row->get( 'status' ) == 2 ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) {
					$promote	=	'<div><a href="javascript: void(0);" onclick="' . $adminUrl . '"><i class="icon-thumbs-up"></i> ' . CBTxt::T( 'Promote' ) . '</a></div>';
				} else {
					$promote	=	null;
				}

				if ( ( $row->get( 'status' ) == 2 ) && cbgjClass::hasAccess( 'mod_lvl3', $authorized ) ) {
					$demote		=	'<div><a href="javascript: void(0);" onclick="' . $activeUrl . '"><i class="icon-thumbs-down"></i> ' . CBTxt::T( 'Demote' ) . '</a></div>';
				} elseif ( ( $row->get( 'status' ) == 3 ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) {
					$demote		=	'<div><a href="javascript: void(0);" onclick="' . $modUrl . '"><i class="icon-thumbs-down"></i> ' . CBTxt::T( 'Demote' ) . '</a></div>';
				} elseif ( ( $row->get( 'status' ) == 1 ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$demote		=	'<div><a href="javascript: void(0);" onclick="' . $inactiveUrl . '"><i class="icon-thumbs-down"></i> ' . CBTxt::T( 'Demote' ) . '</a></div>';
				} else {
					$demote		=	null;
				}

				if ( ( $row->get( 'status' ) != 4 ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) {
					$delete		=	'<div><a href="javascript: void(0);" onclick="' . $deleteUrl . '"><i class="icon-remove"></i> ' . CBTxt::T( 'Delete' ) . '</a></div>';
				} else {
					$delete		=	null;
				}

				$beforeMenu		=	cbgjClass::getIntegrations( 'gj_onBeforeGroupUserMenu', array( $row, $group, $category, $user, $plugin ) );
				$afterMenu		=	cbgjClass::getIntegrations( 'gj_onAfterGroupUserMenu', array( $row, $group, $category, $user, $plugin ) );

				$return			.=			'<div class="gjContentBox mini-layout">'
								.				'<div class="gjContentBoxRow">' . $row->getOwnerName( true ) . '</div>'
								.				'<div class="gjContentBoxRow">' . $row->getOwnerAvatar( true ) . '</div>'
								.				'<div class="gjContentBoxRow">' . $row->getOwnerOnline() . '</div>'
								.				'<div class="gjContentBoxRow">'
								.					cbgjClass::getIntegrations( 'gj_onBeforeGroupUserInfo', array( $row, $group, $category, $user, $plugin ) )
								.					'<span class="' . $typeClass . '" title="' . cbFormatDate( $row->get( 'date' ), 1, false ) . '">' . $type . '</span>'
								.					cbgjClass::getIntegrations( 'gj_onAfterGroupUserInfo', array( $row, $group, $category, $user, $plugin ) )
								.				'</div>';

				if ( ( $row->get( 'status' ) == 0 ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return		.=				'<div class="gjContentBoxRow">'
								.					'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'users', 'active', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to set this [user] as Active?', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ), true ) . '" />'
								.				'</div>';
				}

				if ( $beforeMenu || $ban || $delete || $promote || $demote || $afterMenu ) {
					$return		.=				'<div class="gjContentBoxRow">'
								.					cbgjClass::getDropdown( ( $beforeMenu . $ban . $delete . $promote . $demote . $afterMenu ), CBTxt::T( 'Menu' ) )
								.				'</div>';
				}

				$return			.=			'</div>';
			}

			$return				.=		'</div>';
		} else {
			$return				.=		'<div class="gjContent">';

			if ( $groupUsersSearch && $pageNav->searching ) {
				$return			.=			CBTxt::Ph( 'No [user] search results found.', array( '[user]' => cbgjClass::getOverride( 'user' ) ) );
			} else {
				$return			.=			CBTxt::Ph( 'There are no [users] available.', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) );
			}

			$return				.=		'</div>';
		}

		if ( $groupUsersPaging ) {
			$return				.=		'<div class="gjPaging pagination pagination-centered">'
								.			( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
								.			( ! $groupUsersLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
								.		'</div>';
		}

		$return					.=		cbGetSpoofInputTag( 'plugin' )
								.	'</form>';

		return $return;
	}
}
?>