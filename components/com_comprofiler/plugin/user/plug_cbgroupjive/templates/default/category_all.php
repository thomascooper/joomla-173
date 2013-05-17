<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveCategoryAll {

	/**
	 * render frontend all categories
	 *
	 * @param boolean $self
	 * @param object $rows
	 * @param object $pageNav
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showCategoryAll( $self, $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle			=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( cbgjClass::getOverride( 'category', true ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( cbgjClass::getOverride( 'category', true ), cbgjClass::getPluginURL( array( 'categories', ( $self ? 'allmy' : 'all' ) ) ) );

		$categoryAllSearch		=	$plugin->params->get( 'category_all_search', 1 );
		$categoryAllPaging		=	$plugin->params->get( 'category_all_paging', 1 );
		$categoryAllLimitbox	=	$plugin->params->get( 'category_all_limitbox', 1 );
		$categoryAllDescLimit	=	(int) $plugin->params->get( 'category_all_desc_limit', 150 );
		$categoryApprove		=	$plugin->params->get( 'category_approve', 0 );
		$authorized				=	cbgjClass::getAuthorization( null, null, $user );
		$return					=	'<div class="gjCategoryAll">';

		if ( cbgjClass::hasAccess( 'cat_create', $authorized ) ) {
			$return				.=		'<div class="gjTop gjTopCenter">'
								.			'<input type="button" value="' . htmlspecialchars( CBTxt::P( 'New [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) ) . '" class="gjButton btn" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'new' ), true, true, false, null, true ) . '" />'
								.		'</div>';
		}

		$return					.=		'<form action="' . cbgjClass::getPluginURL( array( 'categories', ( $self ? 'allmy' : 'all' ) ) ) . '" method="post" name="gjForm" id="gjForm" class="gjForm">'
								.			( $categoryAllSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) foreach ( $rows as $row ) {
			$authorized			=	cbgjClass::getAuthorization( $row, null, $user );

			if ( $row->get( 'published' ) == 1 ) {
				$state			=	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'unpublish', (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to unpublish this [category]?', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), true, false, null, true ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a></div>';
			} else {
				$state			=	'<div><a href="' . cbgjClass::getPluginURL( array( 'categories', 'publish', (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a></div>';
			}

			$canApprove			=	( $categoryApprove && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'cat_can_publish', $authorized ) );

			$beforeMenu			=	cbgjClass::getIntegrations( 'gj_onBeforeOverviewCategoryMenu', array( $row, $user, $plugin ) );
			$afterMenu			=	cbgjClass::getIntegrations( 'gj_onAfterOverviewCategoryMenu', array( $row, $user, $plugin ) );

			$return				.=			'<div class="gjContent row-fluid">'
								.				'<div class="gjContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.				'<div class="gjContentBody mini-layout span10">'
								.					'<div class="gjContentBodyHeader row-fluid">'
								.						'<div class="gjContentBodyTitle span9"><h5>' . $row->getName( 0, true ) . '<small> ' . cbFormatDate( $row->get( 'date' ), 1, false ) . ( $row->get( 'parent' ) ? ' - ' . $row->getParent()->getName( 0, true ) : null ) . '</small></h5></div>'
								.						'<div class="gjContentBodyMenu span3">';

			if ( $canApprove ) {
				$return			.=							'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'publish', (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />';
			} else {
				if ( ( $row->get( 'published' ) == 0 ) || ( ( $row->get( 'published' ) == 1 ) && ( ! cbgjClass::hasAccess( 'cat_approved', $authorized ) ) ) ) {
					$return		.=							cbgjClass::getIcon( null, CBTxt::P( 'This [category] is currently unpublished.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), 'icon-eye-close' );
				}
			}

			if ( $beforeMenu || cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_can_publish' ), $authorized ) || $afterMenu ) {
				$menuItems		=	$beforeMenu
								.	( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'categories', 'edit', (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
								.	( ( ! $canApprove ) && cbgjClass::hasAccess( 'cat_can_publish', $authorized ) ? $state : null )
								.	( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'delete', (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to delete this [category] and all its associated [groups]?', array( '[category]' => cbgjClass::getOverride( 'category' ), '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), true, false, null, true ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null )
								.	$afterMenu;

				$return			.=							cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) );
			}

			$return				.=						'</div>'
								.					'</div>'
								.					'<div class="gjContentBodyInfo">' . ( $row->getDescription( $categoryAllDescLimit ) ? '<div class="well well-small">' . $row->getDescription( $categoryAllDescLimit ) . '</div>' : null ) . '</div>'
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
			$return				.=			'<div class="gjContent">';

			if ( $categoryAllSearch && $pageNav->searching ) {
				$return			.=			CBTxt::Ph( 'No [category] search results found.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) );
			} else {
				$return			.=			CBTxt::Ph( 'There are no [categories] available.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) );
			}

			$return				.=			'</div>';
		}

		if ( $categoryAllPaging ) {
			$return				.=			'<div class="gjPaging pagination pagination-centered">'
								.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
								.				( ! $categoryAllLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
								.			'</div>';
		}

		$return					.=			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>';

		echo $return;
	}
}
?>