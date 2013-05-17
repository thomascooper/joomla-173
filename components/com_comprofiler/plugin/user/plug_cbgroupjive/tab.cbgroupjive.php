<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class cbgjTab extends cbTabHandler {

	/**
	 * prepare frontend tab categories render
	 *
	 * @param  moscomprofilerUser $displayed
	 * @param  moscomprofilerUser $user
	 * @param  object             $plugin
	 * @param  boolean            $tabbed
	 * @return mixed
	 */
	public function getCategories( $displayed, $user, $plugin, $tabbed = true ) {
		cbgjClass::getTemplate( 'tab_categories' );

		$paging					=	new cbgjPaging( 'tab_categories' );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'category_tab_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		$where[]				=	array( 'user_id', '=', (int) $displayed->id );

		switch( $plugin->params->get( 'category_tab_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'group_count_asc';
				break;
			case 8:
				$orderBy		=	'group_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy, ( $plugin->params->get( 'category_tab_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjTabForm_categories', 'search', CBTxt::P( 'Search [categories]...', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveTabCategories::showCategories( $rows, $pageNav, $displayed, $user, $plugin, $tabbed );
	}

	/**
	 * prepare frontend tab groups render
	 *
	 * @param  moscomprofilerUser $displayed
	 * @param  moscomprofilerUser $user
	 * @param  object             $plugin
	 * @param  boolean            $tabbed
	 * @return mixed
	 */
	public function getGroups( $displayed, $user, $plugin, $tabbed = true ) {
		cbgjClass::getTemplate( 'tab_groups' );

		$paging					=	new cbgjPaging( 'tab_groups' );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'group_tab_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		if ( $plugin->params->get( 'group_tab_joined', 0 ) ) {
			$where[]			=	array( 'user_id', '=', (int) $displayed->id, array( 'e.user_id', '=', (int) $displayed->id, 'e.status', '!IN', array( -1, 0, 4 ) ) );
		} else {
			$where[]			=	array( 'user_id', '=', (int) $displayed->id );
		}

		switch( $plugin->params->get( 'group_tab_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'user_count_asc';
				break;
			case 8:
				$orderBy		=	'user_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy, ( $plugin->params->get( 'group_tab_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjTabForm_groups', 'search', CBTxt::P( 'Search [groups]...', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveTabGroups::showGroups( $rows, $pageNav, $displayed, $user, $plugin, $tabbed );
	}

	/**
	 * prepare frontend tab joined groups render
	 *
	 * @param  moscomprofilerUser $displayed
	 * @param  moscomprofilerUser $user
	 * @param  object             $plugin
	 * @param  boolean            $tabbed
	 * @return mixed
	 */
	public function getJoined( $displayed, $user, $plugin, $tabbed = true ) {
		cbgjClass::getTemplate( 'tab_joined' );

		$paging					=	new cbgjPaging( 'tab_joined' );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'joined_tab_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		if ( $plugin->params->get( 'joined_tab_owned', 0 ) ) {
			$where[]			=	array( 'user_id', '=', (int) $displayed->id, array( 'e.user_id', '=', (int) $displayed->id, 'e.status', '!IN', array( -1, 0, 4 ) ) );
		} else {
			$where[]			=	array( 'e.user_id', '=', (int) $displayed->id, 'e.status', '!IN', array( -1, 0, 4 ) );
		}

		switch( $plugin->params->get( 'joined_tab_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'user_count_asc';
				break;
			case 8:
				$orderBy		=	'user_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy, ( $plugin->params->get( 'joined_tab_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjTabForm_joined', 'search', CBTxt::T( 'Search Joined...' ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveTabJoined::showJoined( $rows, $pageNav, $displayed, $user, $plugin, $tabbed );
	}

	/**
	 * prepare frontend tab invites render
	 *
	 * @param  moscomprofilerUser $displayed
	 * @param  moscomprofilerUser $user
	 * @param  object             $plugin
	 * @param  boolean            $tabbed
	 * @return mixed
	 */
	public function getInvites( $displayed, $user, $plugin, $tabbed = true ) {
		cbgjClass::getTemplate( 'tab_invites' );

		$paging				=	new cbgjPaging( 'tab_invites' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'invites_tab_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'email', 'CONTAINS', $search, array( 'f.id', '=', (int) $search ), array( 'f.username', 'CONTAINS', $search ), array( 'f.name', 'CONTAINS', $search ) );
		}

		$searching			=	( count( $where ) ? true : false );

		$where[]			=	array( 'user_id', '=', (int) $displayed->id );

		$total				=	count( cbgjData::getInvites( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjData::getInvites( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, null, ( $plugin->params->get( 'invites_tab_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjTabForm_invites', 'search', CBTxt::T( 'Search Invites...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveTabInvites::showInvites( $rows, $pageNav, $displayed, $user, $plugin, $tabbed );
	}

	/**
	 * prepare frontend tab invited render
	 *
	 * @param  moscomprofilerUser $displayed
	 * @param  moscomprofilerUser $user
	 * @param  object             $plugin
	 * @param  boolean            $tabbed
	 * @return mixed
	 */
	public function getInvited( $displayed, $user, $plugin, $tabbed = true ) {
		cbgjClass::getTemplate( 'tab_invited' );

		$paging					=	new cbgjPaging( 'tab_invited' );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'invited_tab_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		$where[]				=	array( 'f.user', '=', (int) $displayed->id, array( 'f.email', '=', $displayed->email ) );
		$where[]				=	array( 'f.accepted', 'IN', array( '0000-00-00', '0000-00-00 00:00:00', '', null ) );

		switch( $plugin->params->get( 'invited_tab_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'user_count_asc';
				break;
			case 8:
				$orderBy		=	'user_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy, ( $plugin->params->get( 'invited_tab_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjTabForm_invited', 'search', CBTxt::T( 'Search Invited...' ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveTabInvited::showInvited( $rows, $pageNav, $displayed, $user, $plugin, $tabbed );
	}

	/**
	 * prepare frontend tab render
	 *
	 * @param  object             $tab
	 * @param  moscomprofilerUser $user
	 * @param  int                $ui
	 * @return mixed
	 */
	public function getDisplayTab( $tab, $user, $ui ) {
		global $_CB_framework;

		outputCbJs( 1 );
		outputCbTemplate( 1 );

		cbgjClass::getTemplate( 'tab' );

		$plugin		=	cbgjClass::getPlugin();
		$viewer		=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$categories	=	$this->getCategories( $user, $viewer, $plugin );
		$groups		=	$this->getGroups( $user, $viewer, $plugin );
		$joined		=	$this->getJoined( $user, $viewer, $plugin );
		$invites	=	$this->getInvites( $user, $viewer, $plugin );
		$invited	=	$this->getInvited( $user, $viewer, $plugin );

		ob_start();
		HTML_groupjiveTab::showTab( $categories, $groups, $joined, $invites, $invited, $user, $viewer, $plugin );

		$html		=	ob_get_contents();
		ob_end_clean();

		$return		=	'<div id="cbGj" class="cbGroupJive' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . '">'
					.		'<div id="cbGjInner" class="cbGroupJiveInner">'
					.			$html
					.		'</div>'
					.	'</div>';

		return $return;
	}
}
?>