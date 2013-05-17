<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $mainframe;

if ( defined( 'JPATH_ADMINISTRATOR' ) ) {
	if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) {
		return;
	}

	require_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );
} else {
	if ( ! file_exists( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' ) ) {
		return;
	}

	require_once( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' );
}

cbimport( 'cb.html' );

if ( ! file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' ) ) {
	return;
}

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

cbgjClass::getTemplate();

if ( checkJversion() < 1 ) {
	$_CB_framework->document->addHeadStyleSheet( $_CB_framework->getCfg( 'live_site' ) . '/modules/mod_cbgroupjive.css' );
} else {
	$_CB_framework->document->addHeadStyleSheet( $_CB_framework->getCfg( 'live_site' ) . '/modules/mod_cbgroupjive/mod_cbgroupjive.css' );
}

$layout							=	(int) $params->get( 'gj_layout', 0 );
$display						=	(int) $params->get( 'gj_display', 5 );
$mode							=	(int) $params->get( 'gj_mode', 2 );
$nameLength						=	(int) $params->get( 'gj_length', 15 );
$include						=	$params->get( 'gj_include', null );
$exclude						=	$params->get( 'gj_exclude', null );
$includeCat						=	$params->get( 'gj_includecat', null );
$excludeCat						=	$params->get( 'gj_excludecat', null );
$includeGrp						=	$params->get( 'gj_includegrp', null );
$excludeGrp						=	$params->get( 'gj_excludegrp', null );
$plugin							=	cbgjClass::getPlugin();
$user							=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
$classLayout					=	( $layout ? 'H' : 'V' );
$return							=	'<div class="cbGjExternal cbGroupJiveModule">'
								.		'<div class="cbGjExternalInner">';

$include_exclude				=	array();

if ( in_array( $mode, array( 0, 1, 2, 3 ) ) ) {
	if ( $include ) {
		$include				=	explode( ',', $include );

		cbArrayToInts( $include );

		$include_exclude[]		=	array( 'id', 'IN', $include );
	}

	if ( $exclude ) {
		$exclude				=	explode( ',', $exclude );

		cbArrayToInts( $exclude );

		$include_exclude[]		=	array( 'id', '!IN', $exclude );
	}
}

if ( in_array( $mode, array( 2, 3, 8, 9 ) ) ) {
	if ( $includeCat ) {
		$includeCat				=	explode( ',', $includeCat );

		cbArrayToInts( $includeCat );

		$include_exclude[]		=	array( 'category', 'IN', $includeCat );
	}

	if ( $excludeCat ) {
		$excludeCat				=	explode( ',', $excludeCat );

		cbArrayToInts( $excludeCat );

		$include_exclude[]		=	array( 'category', '!IN', $excludeCat );
	}
}

if ( in_array( $mode, array( 11, 12, 13, 14, 15 ) ) ) {
	if ( $includeGrp ) {
		$includeGrp				=	explode( ',', $includeGrp );

		cbArrayToInts( $includeGrp );

		$include_exclude[]		=	array( 'group', 'IN', $includeGrp );
	}

	if ( $excludeGrp ) {
		$excludeGrp				=	explode( ',', $excludeGrp );

		cbArrayToInts( $excludeGrp );

		$include_exclude[]		=	array( 'group', '!IN', $excludeGrp );
	}
}

if ( $mode == 0 ) {
	$rows						=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $include_exclude, array( 'date', 'DESC' ), $display );

	$return						.=			'<div class="gjLatestCategories' . $classLayout . '">';

	if ( $rows ) foreach ( $rows as $row ) {
		$authorized				=	cbgjClass::getAuthorization( $row, null, $user );

		$nestedCount			=	( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() );

		if ( $layout ) {
			$return				.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getName( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getLogo( true, true, true ) . '</div>'
								.					( $nestedCount ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'category', $row->nestedCount() ) . '</div>' : null )
								.					( $row->groupCount() ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'group', $row->groupCount() ) . '</div>' : null )
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
		} else {
			$return				.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getName( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">'
								.							( $nestedCount ? cbgjClass::getOverride( 'category', $row->nestedCount() ) : null )
								.							( $row->groupCount() ? ( $nestedCount ? ' | ' : null ) . cbgjClass::getOverride( 'group', $row->groupCount() ) : null )
								.						'</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
		}
	} else {
		$return					.=				CBTxt::P( 'There are no [categories] available.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) );
	}

	$return						.=			'</div>';
} elseif ( $mode == 1 ) {
	$rows						=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $include_exclude, 'group_count', $display );

	$return						.=			'<div class="gjPopularCategories' . $classLayout . '">';

	if ( $rows ) foreach ( $rows as $row ) {
		$authorized				=	cbgjClass::getAuthorization( $row, null, $user );

		$nestedCount			=	( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() );

		if ( $layout ) {
			$return				.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getName( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getLogo( true, true, true ) . '</div>'
								.					( $nestedCount ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'category', $row->nestedCount() ) . '</div>' : null )
								.					( $row->groupCount() ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'group', $row->groupCount() ) . '</div>' : null )
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
		} else {
			$return				.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getName( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">'
								.							( $nestedCount ? cbgjClass::getOverride( 'category', $row->nestedCount() ) : null )
								.							( $row->groupCount() ? ( $nestedCount ? ' | ' : null ) . cbgjClass::getOverride( 'group', $row->groupCount() ) : null )
								.						'</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
		}
	} else {
		$return					.=				CBTxt::P( 'There are no [categories] available.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) );
	}

	$return						.=			'</div>';
} elseif ( $mode == 2 ) {
	$rows						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $include_exclude, array( 'date', 'DESC' ), $display );

	$return						.=			'<div class="gjLatestGroups' . $classLayout . '">';

	if ( $rows ) foreach ( $rows as $row ) {
		$authorized				=	cbgjClass::getAuthorization( null, $row, $user );

		$nestedCount			=	( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() );

		if ( $layout ) {
			$return				.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getName( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getLogo( true, true, true ) . '</div>'
								.					( $nestedCount ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'group', $row->nestedCount() ) . '</div>' : null )
								.					( $row->userCount() ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'user', $row->userCount() ) . '</div>' : null )
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
		} else {
			$return				.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getName( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">'
								.							( $nestedCount ? cbgjClass::getOverride( 'group', $row->nestedCount() ) : null )
								.							( $row->userCount() ? ( $nestedCount ? ' | ' : null ) . cbgjClass::getOverride( 'user', $row->userCount() ) : null )
								.						'</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
		}
	} else {
		$return					.=				CBTxt::P( 'There are no [groups] available.', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
	}

	$return						.=			'</div>';
} elseif ( $mode == 3 ) {
	$rows						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $include_exclude, 'user_count', $display );

	$return						.=			'<div class="gjPopularGroups' . $classLayout . '">';

	if ( $rows ) foreach ( $rows as $row ) {
		$authorized				=	cbgjClass::getAuthorization( null, $row, $user );

		$nestedCount			=	( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() );

		if ( $layout ) {
			$return				.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getName( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getLogo( true, true, true ) . '</div>'
								.					( $nestedCount ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'group', $row->nestedCount() ) . '</div>' : null )
								.					( $row->userCount() ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'user', $row->userCount() ) . '</div>' : null )
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
		} else {
			$return				.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getName( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">'
								.							( $nestedCount ? cbgjClass::getOverride( 'group', $row->nestedCount() ) : null )
								.							( $row->userCount() ? ( $nestedCount ? ' | ' : null ) . cbgjClass::getOverride( 'user', $row->userCount() ) : null )
								.						'</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
		}
	} else {
		$return					.=				CBTxt::P( 'There are no [groups] available.', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
	}

	$return						.=			'</div>';
} elseif ( $mode == 4 ) {
	$groups						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ) );

	$userCount					=	0;

	if ( $groups ) foreach ( $groups as $group ) {
		$userCount				+=	$group->userCount();
	}

	$return						.=			'<div class="gjStatistics">'
								.				'<div class="gjStatisticsRow">' . cbgjClass::getOverride( 'category', count( cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ) ) ) ) . '</div>'
								.				'<div class="gjStatisticsRow">' . cbgjClass::getOverride( 'group', count( $groups ) ) . '</div>'
								.				'<div class="gjStatisticsRow">' . cbgjClass::getOverride( 'user', $userCount ) . '</div>'
								.			'</div>';
} elseif ( $mode == 5 ) {
	$inputJs					=	"var pane = $( '.gjHeader' ).detach();"
								.	"if ( pane ) {"
								.		"pane.appendTo( '#gjMenuJQ' );"
								.		"$( '.gjHeader' ).width( '100%' );"
								.		"$( '.gjBody' ).width( '100%' );"
								.	"}";

	$_CB_framework->outputCbJQuery( $inputJs );

	$return						.=			'<div id="gjMenuJQ" class="gjMenuJQ"></div>';
} elseif ( $mode == 6 ) {
	$location					=	cbGetParam( $_REQUEST, 'plugin', null );
	$return						.=			'<div class="gjMenuAPI">';

	if ( $location == 'cbgroupjive' ) {
		$action					=	cbGetParam( $_REQUEST, 'action', null );
		$catid					=	cbGetParam( $_REQUEST, 'cat', null );
		$grpid					=	cbGetParam( $_REQUEST, 'grp', null );

		switch ( $action ) {
			case 'panel':
				$authorized		=	cbgjClass::getAuthorization( null, null, $user );

				if ( ( $plugin->params->get( 'overview_panel', 1 ) && in_array( 'usr_me', $authorized ) ) || in_array( 'usr_mod', $authorized ) ) {
					cbgjClass::getTemplate( array( 'panel', 'panel_panes' ) );

					$return		.=				HTML_groupjivePanelPanes::showPanelPanes( $user, $plugin );
				}
				break;
			case 'categories':
				$row			=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', $catid ), null, null, false );

				if ( $row->id ) {
					cbgjClass::getTemplate( array( 'category', 'category_panes' ) );

					$return		.=				HTML_groupjiveCategoryPanes::showCategoryPanes( $row, $user, $plugin );
				}
				break;
			case 'groups':
				$category		=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', $catid ), null, null, false );
				$row			=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', $grpid ), null, null, false );

				if ( ( ! $category->id ) && $row->id ) {
					$category	=	( isset( $row->_category ) ? $row->_category : null );
				}

				if ( $row->id ) {
					cbgjClass::getTemplate( array( 'group', 'group_panes' ) );

					$return		.=				HTML_groupjiveGroupPanes::showGroupPanes( $row, $category, $user, $plugin );
				}
				break;
			case 'overview':
			default:
				cbgjClass::getTemplate( array( 'overview', 'overview_panes' ) );

				$return			.=				HTML_groupjiveOverviewPanes::showOverviewPanes( $user, $plugin );
				break;
		}
	}

	$return						.=			'</div>';
} elseif ( $mode == 7 ) {
	$rows						=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'user_id', '=', $user->id ), null, $display );

	$return						.=			'<div class="gjMyCategories' . $classLayout . '">';

	if ( $rows ) foreach ( $rows as $row ) {
		$authorized				=	cbgjClass::getAuthorization( $row, null, $user );

		$nestedCount			=	( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() );

		if ( $layout ) {
			$return				.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getName( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getLogo( true, true, true ) . '</div>'
								.					( $nestedCount ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'category', $row->nestedCount() ) . '</div>' : null )
								.					( $row->groupCount() ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'group', $row->groupCount() ) . '</div>' : null )
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
		} else {
			$return				.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getName( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">'
								.							( $nestedCount ? cbgjClass::getOverride( 'category', $row->nestedCount() ) : null )
								.							( $row->groupCount() ? ( $nestedCount ? ' | ' : null ) . cbgjClass::getOverride( 'group', $row->groupCount() ) : null )
								.						'</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
		}
	} else {
		$return					.=				CBTxt::P( 'There are no [categories] available.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) );
	}

	$return						.=			'</div>';
} elseif ( $mode == 8 ) {
	$where						=	$include_exclude;

	if ( $plugin->params->get( 'group_tab_joined', 0 ) ) {
		$where[]				=	array( 'user_id', '=', $user->id, array( 'e.user_id', '=', $user->id, 'e.status', '!IN', array( -1, 0, 4 ) ) );
	} else {
		$where[]				=	array( 'user_id', '=', $user->id );
	}

	$rows						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, null, $display );

	$return						.=			'<div class="gjMyGroups' . $classLayout . '">';

	if ( $rows ) foreach ( $rows as $row ) {
		$authorized				=	cbgjClass::getAuthorization( null, $row, $user );

		$nestedCount			=	( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() );

		if ( $layout ) {
			$return				.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getName( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getLogo( true, true, true ) . '</div>'
								.					( $nestedCount ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'group', $row->nestedCount() ) . '</div>' : null )
								.					( $row->userCount() ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'user', $row->userCount() ) . '</div>' : null )
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
		} else {
			$return				.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getName( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">'
								.							( $nestedCount ? cbgjClass::getOverride( 'group', $row->nestedCount() ) : null )
								.							( $row->userCount() ? ( $nestedCount ? ' | ' : null ) . cbgjClass::getOverride( 'user', $row->userCount() ) : null )
								.						'</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
		}
	} else {
		$return					.=				CBTxt::P( 'There are no [groups] available.', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
	}

	$return						.=			'</div>';
} elseif ( $mode == 9 ) {
	$where						=	$include_exclude;

	if ( $plugin->params->get( 'joined_tab_owned', 0 ) ) {
		$where[]				=	array( 'user_id', '=', $user->id, array( 'e.user_id', '=', $user->id, 'e.status', '!IN', array( -1, 0, 4 ) ) );
	} else {
		$where[]				=	array( 'e.user_id', '=', $user->id, 'e.status', '!IN', array( -1, 0, 4 ) );
	}

	$rows						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, null, ( $display ? array( 0, $display ) : null ) );

	$return						.=			'<div class="gjJoinedGroups' . $classLayout . '">';

	if ( $rows ) foreach ( $rows as $row ) {
		$authorized				=	cbgjClass::getAuthorization( null, $row, $user );

		$nestedCount			=	( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() );

		if ( $layout ) {
			$return				.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getName( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getLogo( true, true, true ) . '</div>'
								.					( $nestedCount ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'group', $row->nestedCount() ) . '</div>' : null )
								.					( $row->userCount() ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'user', $row->userCount() ) . '</div>' : null )
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
		} else {
			$return				.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getName( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">'
								.							( $nestedCount ? cbgjClass::getOverride( 'group', $row->nestedCount() ) : null )
								.							( $row->userCount() ? ( $nestedCount ? ' | ' : null ) . cbgjClass::getOverride( 'user', $row->userCount() ) : null )
								.						'</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
		}
	} else {
		$return					.=				CBTxt::P( 'There are no [groups] available.', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
	}

	$return						.=			'</div>';
} elseif ( $mode == 10 ) {
	$return						.=			'<div class="gjApproval' . $classLayout . '">';

	$needs_approval				=	false;

	if ( $plugin->params->get( 'category_approve', 0 ) ) {
		$categories				=	count( cbgjData::getCategories( array( array( 'cat_can_publish' ), $user ), array( 'published', '=', 0 ) ) );

		if ( $categories ) {
			$return				.=				'<div><a href="' . cbgjClass::getPluginURL( array( 'categories', 'approval' ) ) . '">' . CBTxt::P( '[categories] pending approval.', array( '[categories]' => cbgjClass::getOverride( 'category', $categories ) ) ) . '</a></div>';

			$needs_approval		=	true;
		}
	}

	if ( $plugin->params->get( 'group_approve', 0 ) ) {
		$groups					=	count( cbgjData::getGroups( array( array( 'grp_can_publish', 'cat_approved' ), $user, null, true ), array( 'published', '=', 0 ) ) );

		if ( $groups ) {
			$return				.=				'<div><a href="' . cbgjClass::getPluginURL( array( 'groups', 'approval' ) ) . '">' . CBTxt::P( '[groups] pending approval.', array( '[groups]' => cbgjClass::getOverride( 'group', $groups ) ) ) . '</a></div>';

			$needs_approval		=	true;
		}
	}

	$users						=	count( cbgjData::getUsers( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'status', '=', 0, 'b.type', '=', 2 ) ) );

	if ( $users ) {
		$return					.=				'<div><a href="' . cbgjClass::getPluginURL( array( 'users', 'approval' ) ) . '">' . CBTxt::P( '[users] pending approval.', array( '[users]' => cbgjClass::getOverride( 'user', $users ) ) ) . '</a></div>';

		$needs_approval			=	true;
	}

	if ( class_exists( 'cbgjEventsData' ) ) {
		$events					=	count( cbgjEventsData::getEvents( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'published', '=', 0, 'c.params', 'CONTAINS', 'events_approve=1' ) ) );

		if ( $events ) {
			$return				.=				'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'events_approval' ) ) . '">' . CBTxt::P( '[events] pending approval.', array( '[events]' => $events . ' ' . ( $events == 1 ? CBTxt::T( 'Event' ) : CBTxt::T( 'Events' ) ) ) ) . '</a></div>';

			$needs_approval		=	true;
		}
	}

	if ( class_exists( 'cbgjFileData' ) ) {
		$files					=	count( cbgjFileData::getFiles( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'published', '=', 0, 'c.params', 'CONTAINS', 'file_approve=1' ) ) );

		if ( $files ) {
			$return				.=				'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'file_approval' ) ) . '">' . CBTxt::P( '[files] pending approval.', array( '[files]' => $files . ' ' . ( $files == 1 ? CBTxt::T( 'File' ) : CBTxt::T( 'Files' ) ) ) ) . '</a></div>';

			$needs_approval		=	true;
		}
	}

	if ( class_exists( 'cbgjPhotoData' ) ) {
		$photos					=	count( cbgjPhotoData::getPhotos( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'published', '=', 0, 'c.params', 'CONTAINS', 'photo_approve=1' ) ) );

		if ( $photos ) {
			$return				.=				'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_approval' ) ) . '">' . CBTxt::P( '[photos] pending approval.', array( '[photos]' => $photos . ' ' . ( $photos == 1 ? CBTxt::T( 'Photo' ) : CBTxt::T( 'Photos' ) ) ) ) . '</a></div>';

			$needs_approval		=	true;
		}
	}

	if ( class_exists( 'cbgjVideoData' ) ) {
		$videos					=	count( cbgjVideoData::getVideos( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'published', '=', 0, 'c.params', 'CONTAINS', 'video_approve=1' ) ) );

		if ( $videos ) {
			$return				.=				'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'video_approval' ) ) . '">' . CBTxt::P( '[videos] pending approval.', array( '[videos]' => $videos . ' ' . ( $videos == 1 ? CBTxt::T( 'Video' ) : CBTxt::T( 'Videos' ) ) ) ) . '</a></div>';

			$needs_approval		=	true;
		}
	}

	if ( class_exists( 'cbgjWallData' ) ) {
		$posts					=	count( cbgjWallData::getPosts( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'published', '=', 0, 'c.params', 'CONTAINS', 'wall_approve=1' ) ) );

		if ( $posts ) {
			$return				.=				'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_approval' ) ) . '">' . CBTxt::P( '[posts] pending approval.', array( '[posts]' => $posts . ' ' . ( $posts == 1 ? CBTxt::T( 'Post' ) : CBTxt::T( 'Posts' ) ) ) ) . '</a></div>';

			$needs_approval		=	true;
		}
	}

	if ( ! $needs_approval ) {
		$return					.=				'<div>' . CBTxt::T( 'There are no pending approvals.' ) . '</div>';
	}

	$return						.=			'</div>';
} elseif ( $mode == 11 ) {
	if ( class_exists( 'cbgjWallData' ) ) {
		$rows					=	cbgjWallData::getPosts( array( array( 'grp_access', 'wall_show' ), $user, null, true ), $include_exclude, null, $display );

		$return					.=			'<div class="gjIntWallPosts' . $classLayout . '">';

		if ( $rows ) foreach ( $rows as $row ) {
			if ( $layout ) {
				$return			.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow"><a href="' . $row->getUrl() . '">' . $row->getPost( $nameLength ) . '</a></div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getOwnerAvatar( true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getGroup()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getCategory()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date ) . '</div>'
								.				'</div>';
			} else {
				$return			.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getOwnerAvatar( true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle"><a href="' . $row->getUrl() . '">' . $row->getPost( $nameLength ) . '</a></div>'
								.						'<div class="gjModuleContentBodyInfo">' . $row->getGroup()->getName( 0, true ) . ' - ' . $row->getCategory()->getName( 0, true ) . '</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
			}
		} else {
			$return				.=				CBTxt::T( 'There are no wall posts.' );
		}

		$return					.=			'</div>';
	} else {
		$return					.=			'<div>' . CBTxt::T( 'Wall integration not installed!' ) . '</div>';
	}
} elseif ( $mode == 12 ) {
	if ( class_exists( 'cbgjEventsData' ) ) {
		$rows					=	cbgjEventsData::getEvents( array( array( 'grp_access', 'events_show' ), $user, null, true ), $include_exclude, null, $display );

		$return					.=			'<div class="gjIntEvents' . $classLayout . '">';

		if ( $rows ) foreach ( $rows as $row ) {
			if ( $layout ) {
				$return			.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getTitle( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getOwnerAvatar( true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getGroup()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getCategory()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow muted">' . $row->getDate() . '</div>'
								.				'</div>';
			} else {
				$return			.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getOwnerAvatar( true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getTitle( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">' . $row->getGroup()->getName( 0, true ) . ' - ' . $row->getCategory()->getName( 0, true ) . '</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . $row->getDate() . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
			}
		} else {
			$return				.=				CBTxt::T( 'There are no events scheduled.' );
		}

		$return					.=			'</div>';
	} else {
		$return					.=			'<div>' . CBTxt::T( 'Event integration not installed!' ) . '</div>';
	}
} elseif ( $mode == 13 ) {
	if ( class_exists( 'cbgjPhotoData' ) ) {
		$displayLightbox		=	$plugin->params->get( 'photo_lightbox', 1 );

		if ( $displayLightbox ) {
			$_CB_framework->outputCbJQuery( "$( '.cbgjPhotoMod" . (int) $module->id . "Lightbox' ).slimbox( { counterText: '" . addslashes( CBTxt::T( 'Image {x} of {y}' ) ) . "' } );", 'slimbox2' );
		}

		$rows					=	cbgjPhotoData::getPhotos( array( array( 'grp_access', 'photo_show' ), $user, null, true ), $include_exclude, null, $display );

		$return					.=			'<div class="gjIntPhotos' . $classLayout . '">';

		if ( $rows ) foreach ( $rows as $row ) {
			if ( $layout ) {
				$return			.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getTitle( $nameLength, 'tab' ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getImage( true, ( $displayLightbox ? 2 : true ), true, 'cbgjPhotoMod' . (int) $module->id ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getGroup()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getCategory()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
			} else {
				$return			.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getImage( true, ( $displayLightbox ? 2 : true ), true, 'cbgjPhotoMod' . (int) $module->id ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getTitle( $nameLength, 'tab' ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">' . $row->getGroup()->getName( 0, true ) . ' - ' . $row->getCategory()->getName( 0, true ) . '</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
			}
		} else {
			$return				.=				CBTxt::T( 'There are no photos uploaded.' );
		}

		$return					.=			'</div>';
	} else {
		$return					.=			'<div>' . CBTxt::T( 'Photo integration not installed!' ) . '</div>';
	}
} elseif ( $mode == 14 ) {
	if ( class_exists( 'cbgjVideoData' ) ) {
		$rows					=	cbgjVideoData::getVideos( array( array( 'grp_access', 'video_show' ), $user, null, true ), $include_exclude, null, $display );

		$return					.=			'<div class="gjIntVideos' . $classLayout . '">';

		if ( $rows ) foreach ( $rows as $row ) {
			$return				.=				'<div class="gjModuleContentBox' . ( $layout ? ' mini-layout' : null ) . '">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getTitle( $nameLength, 'tab' ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getEmbed( true, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getGroup()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getCategory()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
		} else {
			$return				.=				CBTxt::T( 'There are no videos published.' );
		}

		$return					.=			'</div>';
	} else {
		$return					.=			'<div>' . CBTxt::T( 'Video integration not installed!' ) . '</div>';
	}
} elseif ( $mode == 15 ) {
	if ( class_exists( 'cbgjFileData' ) ) {
		$rows					=	cbgjFileData::getFiles( array( array( 'grp_access', 'file_show' ), $user, null, true ), $include_exclude, null, $display );

		$return					.=			'<div class="gjIntFiles' . $classLayout . '">';

		if ( $rows ) foreach ( $rows as $row ) {
			if ( $layout ) {
				$return			.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getTitle( $nameLength, 'tab' ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getIcon( true, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getGroup()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getCategory()->getName( 0, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.				'</div>';
			} else {
				$return			.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getIcon( true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getTitle( $nameLength, 'tab' ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">' . $row->getGroup()->getName( 0, true ) . ' - ' . $row->getCategory()->getName( 0, true ) . '</div>'
								.						'<div class="gjModuleContentBodyFooter muted">' . cbFormatDate( $row->date, 1, false ) . '</div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
			}
		} else {
			$return				.=				CBTxt::T( 'There are no files uploaded.' );
		}

		$return					.=			'</div>';
	} else {
		$return					.=			'<div>' . CBTxt::T( 'File integration not installed!' ) . '</div>';
	}
} elseif ( $mode == 16 ) {
	$where						=	$include_exclude;

	$where[]					=	array( 'f.user', '=', (int) $user->id, array( 'f.email', '=', $user->email ) );
	$where[]					=	array( 'f.accepted', 'IN', array( '0000-00-00', '0000-00-00 00:00:00', '', null ) );

	$rows						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, null, $display );

	$return						.=			'<div class="gjInvitedTo' . $classLayout . '">';

	if ( $rows ) foreach ( $rows as $row ) {
		$authorized				=	cbgjClass::getAuthorization( $row, null, $user );

		$nestedCount			=	( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() );

		if ( $layout ) {
			$return				.=				'<div class="gjModuleContentBox mini-layout">'
								.					'<div class="gjModuleContentBoxRow">' . $row->getName( $nameLength, true ) . '</div>'
								.					'<div class="gjModuleContentBoxRow">' . $row->getLogo( true, true, true ) . '</div>'
								.					( $nestedCount ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'group', $row->nestedCount() ) . '</div>' : null )
								.					( $row->userCount() ? '<div class="gjModuleContentBoxRow">' . cbgjClass::getOverride( 'user', $row->userCount() ) . '</div>' : null )
								.					'<div class="gjModuleContentBoxRow"><input type="button" value="' . htmlspecialchars( CBTxt::T( 'Join' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'join', (int) $row->get( 'category' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" /></div>'
								.				'</div>';
		} else {
			$return				.=				'<div class="gjModuleContent row-fluid">'
								.					'<div class="gjModuleContentLogo span2">' . $row->getLogo( true, true, true ) . '</div>'
								.					'<div class="gjModuleContentBody span10">'
								.						'<div class="gjModuleContentBodyTitle">' . $row->getName( $nameLength, true ) . '</div>'
								.						'<div class="gjModuleContentBodyInfo">'
								.							( $nestedCount ? cbgjClass::getOverride( 'group', $row->nestedCount() ) : null )
								.							( $row->userCount() ? ( $nestedCount ? ' | ' : null ) . cbgjClass::getOverride( 'user', $row->userCount() ) : null )
								.						'</div>'
								.						'<div class="gjModuleContentBodyFooter"><input type="button" value="' . htmlspecialchars( CBTxt::T( 'Join' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'join', (int) $row->get( 'category' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" /></div>'
								.					'</div>'
								.				'</div>'
								.				'<div class="gjModuleContentDivider"></div>';
		}
	} else {
		$return					.=				CBTxt::P( 'You are not invited to any [groups].', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
	}

	$return						.=			'</div>';
}

$return							.=		'</div>'
								.	'</div>';

echo $return;
?>
