<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveTabJoined {

	/**
	 * render frontend tab joined groups
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param moscomprofilerUser $displayed
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param boolean $tabbed
	 * @return mixed
	 */
	static function showJoined( $rows, $pageNav, $displayed, $user, $plugin, $tabbed ) {
		global $_CB_framework;

		$joinedTabSearch		=	$plugin->params->get( 'joined_tab_search', 1 );
		$joinedTabPaging		=	$plugin->params->get( 'joined_tab_paging', 1 );
		$joinedTabLimitbox		=	$plugin->params->get( 'joined_tab_limitbox', 1 );
		$joinedTabDescLimit		=	(int) $plugin->params->get( 'joined_tab_desc_limit', 150 );
		$groupApprove			=	$plugin->params->get( 'group_approve', 0 );

		if ( ! $tabbed ) {
			$formUrl			=	cbgjClass::getPluginURL( array( 'panel', 'joined' ) );
		} else {
			$formUrl			=	$_CB_framework->userProfileUrl( $displayed->id, true, $plugin->tab->tabid );
		}

		$return					=	'<div class="gjTabJoined">'
								.		'<form action="' . $formUrl . '" method="post" name="gjTabForm_joined" id="gjTabForm_joined" class="gjForm">'
								.			( $joinedTabSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) foreach ( $rows as $row ) {
			$category			=	$row->getCategory();
			$authorized			=	cbgjClass::getAuthorization( $category, $row, $user );

			if ( $row->get( 'published' ) == 1 ) {
				$state			=	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'unpublish', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to unpublish this [group]?', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), true, false, null, true ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a></div>';
			} else {
				$state			=	'<div><a href="' . cbgjClass::getPluginURL( array( 'groups', 'publish', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a></div>';
			}

			$canApprove			=	( $groupApprove && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'grp_can_publish', $authorized ) );

			$beforeMenu			=	cbgjClass::getIntegrations( 'gj_onBeforeProfileCategoryGroupMenu', array( $row, $category, $displayed, $user, $plugin ) );
			$afterMenu			=	cbgjClass::getIntegrations( 'gj_onAfterProfileCategoryGroupMenu', array( $row, $category, $displayed, $user, $plugin ) );

			$return				.=			'<div class="gjContent row-fluid">'
								.				'<div class="gjContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.				'<div class="gjContentBody mini-layout span10">'
								.					'<div class="gjContentBodyHeader row-fluid">'
								.						'<div class="gjContentBodyTitle span9"><h5>' . $row->getName( 0, true ) . '<small> ' . cbFormatDate( $row->get( 'date' ), 1, false ) . ' - ' . $category->getName( 0, true ) . ( $row->get( 'parent' ) ? ' - ' . $row->getParent()->getName( 0, true ) : null ) . '</small></h5></div>'
								.						'<div class="gjContentBodyMenu span3">';

			if ( $canApprove ) {
				$return			.=							'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'publish', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />';
			} else {
				if ( ( $row->get( 'published' ) == 0 ) || ( ( $row->get( 'published' ) == 1 ) && ( ! cbgjClass::hasAccess( 'grp_approved', $authorized ) ) ) ) {
					$return		.=							cbgjClass::getIcon( null, CBTxt::P( 'This [group] is currently unpublished.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), 'icon-eye-close' );
				}

				if ( cbgjClass::hasAccess( 'grp_join', $authorized ) ) {
					$return		.=							' <input type="button" value="' . htmlspecialchars( ( cbgjClass::hasAccess( 'grp_invited', $authorized ) ? CBTxt::T( 'Accept Invite' ) : CBTxt::T( 'Join' ) ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'join', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />';
				}
			}

			if ( $beforeMenu || cbgjClass::hasAccess( array( 'grp_leave', 'mod_lvl2', 'mod_lvl3' ), $authorized ) || $afterMenu || ( ( ! $canApprove ) && cbgjClass::hasAccess( 'grp_can_publish', $authorized ) ) || ( $canApprove && cbgjClass::hasAccess( 'grp_join', $authorized ) ) ) {
				$menuItems		=	$beforeMenu
								.	( $canApprove && cbgjClass::hasAccess( 'grp_join', $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'join', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-plus"></i> ' . CBTxt::Th( 'Join' ) . '</a></div>' : null )
								.	( cbgjClass::hasAccess( 'grp_leave', $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'leave', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to leave this [group]?', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), true, false, null, true ) . '"><i class="icon-minus"></i> ' . CBTxt::Th( 'Leave' ) . '</a></div>' : null )
								.	( cbgjClass::hasAccess( 'mod_lvl3', $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'groups', 'edit', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
								.	( ( ! $canApprove ) && cbgjClass::hasAccess( 'grp_can_publish', $authorized ) ? $state : null )
								.	( cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'delete', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to delete this [group] and all its associated [users]?', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ), true, false, null, true ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null )
								.	$afterMenu;

				$return			.=							cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) );
			}

			$return				.=						'</div>'
								.					'</div>'
								.					'<div class="gjContentBodyInfo">' . ( $row->getDescription( $joinedTabDescLimit ) ? '<div class="well well-small">' . $row->getDescription( $joinedTabDescLimit ) . '</div>' : null ) . '</div>'
								.					'<div class="gjContentDivider"></div>'
								.					'<div class="gjContentBodyFooter">'
								.						cbgjClass::getIntegrations( 'gj_onBeforeProfileCategoryGroupInfo', array( $row, $category, $displayed, $user, $plugin ), null, 'span' )
								.						( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() ? cbgjClass::getOverride( 'group', $row->nestedCount() ) . ' | ' : null )
								.						( $row->userCount() ? cbgjClass::getOverride( 'user', $row->userCount() ) . ' | ' : null )
								.						$row->getType()
								.						cbgjClass::getIntegrations( 'gj_onAfterProfileCategoryGroupInfo', array( $row, $category, $displayed, $user, $plugin ), null, 'span' )
								.					'</div>'
								.				'</div>'
								.			'</div>';
		} else {
			$return				.=			'<div class="gjContent">';

			if ( $joinedTabSearch && $pageNav->searching ) {
				$return			.=				CBTxt::Ph( 'No [group] search results found.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			} else {
				if ( $displayed->id == $user->id ) {
					$return		.=				CBTxt::Ph( 'You have not joined any [groups].', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
				} else {
					$return		.=				CBTxt::Ph( 'This user has not joined any [groups].', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
				}
			}

			$return				.=			'</div>';
		}

		if ( $joinedTabPaging ) {
			$return				.=			'<div class="gjPaging pagination pagination-centered">'
								.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
								.				( ! $joinedTabLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
								.			'</div>';
		}

		$return					.=			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>';

		return $return;
	}
}
?>