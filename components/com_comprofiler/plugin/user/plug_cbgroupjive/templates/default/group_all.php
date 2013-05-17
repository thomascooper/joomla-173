<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveGroupAll {

	/**
	 * render frontend all groups
	 *
	 * @param boolean $self
	 * @param object $rows
	 * @param object $pageNav
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return mixed
	 */
	static function showGroupAll( $self, $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle			=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( cbgjClass::getOverride( 'group', true ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( cbgjClass::getOverride( 'group', true ), cbgjClass::getPluginURL( array( 'groups', ( $self ? 'allmy' : 'all' ) ) ) );

		$groupAllSearch			=	$plugin->params->get( 'group_all_search', 1 );
		$groupAllPaging			=	$plugin->params->get( 'group_all_paging', 1 );
		$groupAllLimitbox		=	$plugin->params->get( 'group_all_limitbox', 1 );
		$groupAllDescLimit		=	(int) $plugin->params->get( 'group_all_desc_limit', 150 );
		$groupApprove			=	$plugin->params->get( 'group_approve', 0 );
		$authorized				=	cbgjClass::getAuthorization( null, null, $user );
		$return					=	'<div class="gjGroupAll">';

		if ( cbgjClass::hasAccess( 'grp_create', $authorized ) ) {
			$return				.=		'<div class="gjTop gjTopCenter">'
								.			'<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'new' ), true, true, false, null, true ) . '" />'
								.		'</div>';
		}

		$return					.=		'<form action="' . cbgjClass::getPluginURL( array( 'groups', ( $self ? 'allmy' : 'all' ) ) ) . '" method="post" name="gjForm" id="gjForm" class="gjForm">'
								.			( $groupAllSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) foreach ( $rows as $row ) {
			$category			=	$row->getCategory();
			$authorized			=	cbgjClass::getAuthorization( $category, $row, $user );

			if ( $row->get( 'published' ) == 1 ) {
				$state			=	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'unpublish', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to unpublish this [group]?', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), true, false, null, true ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a></div>';
			} else {
				$state			=	'<div><a href="' . cbgjClass::getPluginURL( array( 'groups', 'publish', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a></div>';
			}

			$canApprove			=	( $groupApprove && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'grp_can_publish', $authorized ) );

			$beforeMenu			=	cbgjClass::getIntegrations( 'gj_onBeforeCategoryGroupMenu', array( $row, $category, $user, $plugin ) );
			$afterMenu			=	cbgjClass::getIntegrations( 'gj_onAfterCategoryGroupMenu', array( $row, $category, $user, $plugin ) );

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
					$return		.=							' <input type="button" value="' . htmlspecialchars( ( cbgjClass::hasAccess( 'grp_invited', $authorized ) ? CBTxt::T( 'Accept Invite' ) : CBTxt::T( 'Join' ) ) ) . '" class="gjButton gjButtonCancel btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'join', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />';
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
								.					'<div class="gjContentBodyInfo">' . ( $row->getDescription( $groupAllDescLimit ) ? '<div class="well well-small">' . $row->getDescription( $groupAllDescLimit ) . '</div>' : null ) . '</div>'
								.					'<div class="gjContentDivider"></div>'
								.					'<div class="gjContentBodyFooter">'
								.						cbgjClass::getIntegrations( 'gj_onBeforeCategoryGroupInfo', array( $row, $category, $user, $plugin ), null, 'span' )
								.						( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() ? cbgjClass::getOverride( 'group', $row->nestedCount() ) . ' | ' : null )
								.						( $row->userCount() ? cbgjClass::getOverride( 'user', $row->userCount() ) . ' | ' : null )
								.						$row->getType()
								.						cbgjClass::getIntegrations( 'gj_onAfterCategoryGroupInfo', array( $row, $category, $user, $plugin ), null, 'span' )
								.					'</div>'
								.				'</div>'
								.			'</div>';
		} else {
			$return				.=			'<div class="gjContent">';

			if ( $groupAllSearch && $pageNav->searching ) {
				$return			.=				CBTxt::Ph( 'No [group] search results found.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			} else {
				$return			.=				CBTxt::Ph( 'There are no [groups] available.', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
			}

			$return				.=			'</div>';
		}

		if ( $groupAllPaging ) {
			$return				.=			'<div class="gjPaging pagination pagination-centered">'
								.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
								.				( ! $groupAllLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
								.			'</div>';
		}

		$return					.=			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>';

		echo $return;
	}
}
?>