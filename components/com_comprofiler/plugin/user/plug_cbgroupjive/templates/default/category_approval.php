<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveCategoryApproval {

	/**
	 * render frontend categories approval
	 *
	 * @param object $rows
	 * @param object $pageNav
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showCategoryApproval( $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle				=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( CBTxt::P( '[category] Approval', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( CBTxt::P( '[category] Approval', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), cbgjClass::getPluginURL( array( 'categories', 'approval' ) ) );

		$categoryApprovalSearch		=	$plugin->params->get( 'category_approval_search', 1 );
		$categoryApprovalPaging		=	$plugin->params->get( 'category_approval_paging', 1 );
		$categoryApprovalLimitbox	=	$plugin->params->get( 'category_approval_limitbox', 1 );
		$categoryApprovalDescLimit	=	(int) $plugin->params->get( 'category_approval_desc_limit', 150 );

		$return						=	'<div class="gjCategoryApproval">'
									.		'<form action="' . cbgjClass::getPluginURL( array( 'categories', 'approval' ) ) . '" method="post" name="gjForm" id="gjForm" class="gjForm">'
									.			( $categoryApprovalSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) foreach ( $rows as $row ) {
			$authorized				=	cbgjClass::getAuthorization( $row, null, $user );
			$beforeMenu				=	cbgjClass::getIntegrations( 'gj_onBeforeOverviewCategoryMenu', array( $row, $user, $plugin ) );
			$afterMenu				=	cbgjClass::getIntegrations( 'gj_onAfterOverviewCategoryMenu', array( $row, $user, $plugin ) );

			$return					.=			'<div class="gjContent row-fluid">'
									.				'<div class="gjContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
									.				'<div class="gjContentBody mini-layout span10">'
									.					'<div class="gjContentBodyHeader row-fluid">'
									.						'<div class="gjContentBodyTitle span9"><h5>' . $row->getName( 0, true ) . '<small> ' . cbFormatDate( $row->get( 'date' ), 1, false ) . ( $row->get( 'parent' ) ? ' - ' . $row->getParent()->getName( 0, true ) : null ) . '</small></h5></div>'
									.						'<div class="gjContentBodyMenu span3">';

			if ( cbgjClass::hasAccess( 'cat_can_publish', $authorized ) ) {
				$return				.=						'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'publish', (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />';
			}

			if ( $beforeMenu || cbgjClass::hasAccess( 'mod_lvl1', $authorized ) || $afterMenu ) {
				$menuItems			=	$beforeMenu
									.	( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'categories', 'edit', (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
									.	( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'delete', (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to delete this [category] and all its associated [groups]?', array( '[category]' => cbgjClass::getOverride( 'category' ), '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), true, false, null, true ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null )
									.	$afterMenu;

				$return				.=							cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) );
			}

			$return					.=						'</div>'
									.					'</div>'
									.					'<div class="gjContentBodyInfo">' . ( $row->getDescription( $categoryApprovalDescLimit ) ? '<div class="well well-small">' . $row->getDescription( $categoryApprovalDescLimit ) . '</div>' : null ) . '</div>'
									.					'<div class="gjContentDivider"></div>'
									.					'<div class="gjContentBodyFooter">'
									.						cbgjClass::getIntegrations( 'gj_onBeforeOverviewCategoryInfo', array( $row, $user, $plugin ), null, 'span' )
									.						( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() ? cbgjClass::getOverride( 'category', $row->nestedCount() ) . ' | ' : null )
									.						( $row->groupCount() ? cbgjClass::getOverride( 'group', $row->groupCount() ) . ' | ' : null )
									.						implode( ', ', $row->getTypes() )
									.						cbgjClass::getIntegrations( 'gj_onAfterOverviewCategoryInfo', array( $row, $user, $plugin ), null, 'span' )
									.					'</div>'
									.				'</div>'
									.			'</div>';
		} else {
			$return					.=			'<div class="gjContent">';

			if ( $categoryApprovalSearch && $pageNav->searching ) {
				$return				.=				CBTxt::Ph( 'No [category] search results found.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) );
			} else {
				$return				.=				CBTxt::Ph( 'There are no [categories] pending approval.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) );
			}

			$return					.=			'</div>';
		}

		if ( $categoryApprovalPaging ) {
			$return					.=			'<div class="gjPaging pagination pagination-centered">'
									.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.				( ! $categoryApprovalLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.			'</div>';
		}

		$return						.=		'</form>'
									.	'</div>';

		echo $return;
	}
}
?>