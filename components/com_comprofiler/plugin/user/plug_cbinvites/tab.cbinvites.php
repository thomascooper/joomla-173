<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class cbinvitesTab extends cbTabHandler {

	public function getDisplayTab( $tab, $user, $ui ) {
		global $_CB_framework;

		$viewer					=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		if ( $viewer->id == $user->id ) {
			outputCbJs( 1 );
			outputCbTemplate( 1 );

			$plugin				=	cbinvitesClass::getPlugin();

			cbinvitesClass::getTemplate( 'tab' );

			$paging				=	new cbinvitesPaging( 'tab_invites' );

			$limit				=	(int) $plugin->params->get( 'tab_limit', 15 );
			$limitstart			=	$paging->getLimistart();
			$filter_search		=	$paging->getFilter( 'search' );
			$where				=	array();

			if ( isset( $filter_search ) && ( $filter_search != '' ) ) {
				$where[]		=	array( 'to', 'CONTAINS', $filter_search, array( 'b.id', '=', $filter_search ), array( 'b.username', 'CONTAINS', $filter_search ), array( 'b.name', 'CONTAINS', $filter_search ) );
			}

			$searching			=	( count( $where ) ? true : false );

			$where[]			=	array( 'user_id', '=', $user->id );

			$total				=	count( cbinvitesData::getInvites( array( array( 'mod_lvl1' ), $viewer ), $where ) );

			if ( $total <= $limitstart ) {
				$limitstart		=	0;
			}

			$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

			$rows				=	cbinvitesData::getInvites( array( array( 'mod_lvl1' ), $viewer ), $where, null, ( $plugin->params->get( 'tab_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

			$pageNav->search	=	$paging->getInputSearch( 'inviteForm', 'search', CBTxt::T( 'Search Invites...' ), $filter_search );
			$pageNav->searching	=	$searching;
			$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
			$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

			$return				=	'<div id="cbInvites" class="cbInvites' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . '">'
								.		'<div id="cbInvitesInner">'
								.			HTML_cbinvitesTab::showTab( $rows, $pageNav, $viewer, $user, $plugin )
								.		'</div>'
								.	'</div>';

			return $return;
		}
	}
}
?>