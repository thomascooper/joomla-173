<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class CBplug_cbactivity extends cbPluginHandler {

	public function getCBpluginComponent( $tab, $user, $ui, $postdata ) {
		global $_CB_framework;

		$plugin						=	cbactivityClass::getPlugin();
		$exclude					=	$plugin->params->get( 'general_exclude', null );
		$display					=	$plugin->params->get( 'recent_display', 1 );
		$access						=	$plugin->params->get( 'recent_access', -1 );
		$ajaxPaging					=	$plugin->params->get( 'recent_paging_jquery', 1 );
		$cutOff						=	$plugin->params->get( 'recent_cut_off', 5 );
		$ajax						=	( (int) cbGetParam( $_POST, 'recent_activity_ajax', 0 ) ? true : false );
		$last						=	(int) cbGetParam( $_REQUEST, 'recent_activity_last', 0 );
		$user						=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$now						=	cbactivityClass::getUTCNow();

		if ( $access != -1 ) {
			if ( ! in_array( $access, CBuser::getMyInstance()->getAuthorisedViewLevelsIds( ( checkJversion() >= 2 ? false : true ) ) ) ) {
				cbactivityClass::getCBURL( 'login', CBTxt::T( 'Login or Register to view activity.' ), false, true, 'error', true );
			}
		}

		if ( ! $ajax ) {
			outputCbJs( 1 );
			outputCbTemplate( 1 );

			cbactivityClass::getTemplate( array( 'jquery', 'recent', 'activity' ) );
			HTML_cbactivityJquery::loadJquery( 'recent', $user, $plugin );
		} else {
			cbactivityClass::getTemplate( array( 'recent', 'activity' ), true, false );
		}

		$paging						=	new cbactivityPaging( 'recent_activity' );

		$limit						=	(int) $plugin->params->get( 'recent_limit', 7 );
		$limitstart					=	$paging->getLimistart();

		if ( $ajaxPaging && ( ! $ajax ) ) {
			$limitstart				=	0;
		}

		switch( $display ) {
			case 2: // Connections Only
				$where				=	array( 'b.referenceid', '=', (int) $user->get( 'id' ), 'b.accepted', '=', 1, 'b.pending', '=', 0 );
				break;
			case 3: // Self Only
				$where				=	array( 'user_id', '=', (int) $user->get( 'id' ) );
				break;
			case 4: // Connections and Self
				$where				=	array( 'user_id', '=', (int) $user->get( 'id' ), array( 'b.referenceid', '=', (int) $user->get( 'id' ), 'b.accepted', '=', 1, 'b.pending', '=', 0 ) );
				break;
			default: // Everyone
				$where				=	array();
				break;
		}

		switch( $cutOff ) {
			case 2: // 1 Day
				$cutOffTimestamp	=	cbactivityClass::getUTCTimestamp( '-1 DAY', $now );
				break;
			case 3: // 1 Week (7 Days)
				$cutOffTimestamp	=	cbactivityClass::getUTCTimestamp( '-1 WEEK', $now );
				break;
			case 4: // 2 Weeks
				$cutOffTimestamp	=	cbactivityClass::getUTCTimestamp( '-2 WEEK', $now );
				break;
			case 5: // 1 Month (30 Days)
				$cutOffTimestamp	=	cbactivityClass::getUTCTimestamp( '-1 MONTH', $now );
				break;
			case 6: // 3 Months
				$cutOffTimestamp	=	cbactivityClass::getUTCTimestamp( '-3 MONTH', $now );
				break;
			case 7: // 6 Months
				$cutOffTimestamp	=	cbactivityClass::getUTCTimestamp( '-6 MONTH', $now );
				break;
			case 8: // 1 Year (365 Days)
				$cutOffTimestamp	=	cbactivityClass::getUTCTimestamp( '-1 YEAR', $now );
				break;
			default: // No Limit
				$cutOffTimestamp	=	false;
				break;
		}

		if ( $cutOffTimestamp ) {
			array_unshift( $where, 'date', '>=', cbactivityClass::getUTCDate( 'Y-m-d H:i:s', $cutOffTimestamp ) );
		}

		array_unshift( $where, 'f.id', '>', 0 );

		if ( $exclude ) {
			$exclude				=	explode( ',', $exclude );

			cbArrayToInts( $exclude );

			if ( $exclude ) {
				array_unshift( $where, 'user_id', '!IN', $exclude );
			}
		}

		$total						=	cbactivityData::getActivity( $where );

		if ( $last ) {
			$position				=	0;

			foreach ( array_keys( $total ) as $i => $k ) {
				if ( $k == $last ) {
					$position		=	$i;
				}
			}

			cbactivityData::limit( $total, array( 0, $position ) );

			if ( ! $total ) {
				return;
			} else {
				$limit				=	count( $total );
			}
		}

		$total						=	count( $total );

		if ( $total <= $limitstart ) {
			$limitstart				=	0;
		}

		$pageNav					=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows						=	cbactivityData::getActivity( $where, null, ( $plugin->params->get( 'recent_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		if ( $ajaxPaging ) {
			$pageNav->limitstart	=	(int) ( ceil( ( $pageNav->limitstart + 1 ) / $pageNav->limit ) * $pageNav->limit );
		}

		$pageNav->pagelinks			=	$paging->getPagesLinks( $pageNav );

		$return						=	HTML_cbactivityRecent::showActivityRecent( $rows, $ajax, $pageNav, $user, $plugin );

		if ( ! $ajax ) {
			$return					=	'<div id="cbActivity" class="cbActivity' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . ' cb_template_' . selectTemplate( 'dir' ) . '">'
									.		'<div id="cbActivityInner" class="cbActivityInner">'
									.			$return
									.		'</div>'
									.	'</div>';
		}

		echo $return;
	}
}
?>