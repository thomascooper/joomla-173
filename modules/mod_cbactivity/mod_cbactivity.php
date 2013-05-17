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

if ( ! file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbactivity/cbactivity.class.php' ) ) {
	return;
}

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbactivity/cbactivity.class.php' );

$plugin						=	cbactivityClass::getPlugin();
$exclude					=	$plugin->params->get( 'general_exclude', null );
$display					=	(int) $params->get( 'activity_display', 1 );
$avatar						=	(int) $params->get( 'activity_avatar', 0 );
$cutOff						=	(int) $params->get( 'activity_cut_off', 5 );
$limit						=	(int) $params->get( 'activity_limit', 10 );
$titleLimit					=	(int) $params->get( 'activity_title_length', 100 );
$descLimit					=	(int) $params->get( 'activity_desc_length', 100 );
$imgThumbnails				=	(int) $params->get( 'activity_img_thumbnails', 1 );
$user						=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
$now						=	cbactivityClass::getUTCNow();

outputCbJs( 1 );
outputCbTemplate( 1 );

cbactivityClass::getTemplate( array( 'jquery', 'activity' ) );
HTML_cbactivityJquery::loadJquery( 'module', $user, $plugin );

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

if ( $exclude ) {
	$exclude				=	explode( ',', $exclude );

	cbArrayToInts( $exclude );

	if ( $exclude ) {
		array_unshift( $where, 'user_id', '!IN', $exclude );
	}
}

$rows						=	cbactivityData::getActivity( $where, null, $limit );

if ( $rows ) {
	static $cache			=	array();

	$cache['pb']			=	array(); // CB ProfileBook Posts
	$cache['pg']			=	array(); // CB ProfileGallery Uploads
	$cache['gjLoaded']		=	0; // GJ Loaded
	$cache['gjCategories']	=	array(); // CB GroupJive Categories
	$cache['gjGroups']		=	array(); // CB GroupJive Groups
	$cache['gjEvents']		=	array(); // CB GroupJive Group Events
	$cache['gjFiles']		=	array(); // CB GroupJive Group Files
	$cache['gjPhotos']		=	array(); // CB GroupJive Group Photos
	$cache['gjVideos']		=	array(); // CB GroupJive Group Videos
	$cache['gjPosts']		=	array(); // CB GroupJive Group Wall Posts
	$cache['blogsLoaded']	=	0; // CB Blogs Loaded
	$cache['blogs']			=	array(); // CB Blogs Posts
	$cache['messages']		=	array(); // Kunena Posts

	$return					=	'<div class="cbActivity' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . ' cb_template_' . selectTemplate( 'dir' ) . '">'
							.		'<div class="cbActivityInner">'
							.			'<div class="activityModule">';

	foreach ( $rows as $row ) {
		$message			=	HTML_cbactivityActivity::getMessageDisplay( $row, $cache, $user, $plugin, array( $titleLimit, $descLimit, $imgThumbnails ) );

		$return				.=				'<div class="activityContent' . htmlspecialchars( $row->get( 'class' ) ) . ( $avatar ? ' row-fluid' : null ) . '">'
							.					( $avatar ? '<div class="activityContentLogo span2">' . $row->getOwnerAvatar( true ) . '</div>' : null )
							.					'<div class="activityContentBody mini-layout' . ( $avatar ? ' span10' : null ) . '">'
							.						'<div class="activityContentBodyHeader">'
							.							'<div class="activityContentBodyTitle"><h5>' . ( $row->get( 'icon' ) ? $row->getIcon() . ' ' : null ) . $row->getOwnerName( true ) . ( $row->get( 'title' ) ? ' <small>' . $row->getTitle() . '</small>' : null ) . '</h5></div>'
							.						'</div>'
							.						'<div class="activityContentBodyInfo">';

		if ( $message || $row->get( 'from' ) ) {
			$return			.=							'<div class="well well-small">'
							.								$message
							.								( $row->get( 'from' ) ? '<div class="activityContentBodyInfoRow">' . ( $row->get( 'to' ) ? CBTxt::P( '[from] to [to]', array( '[from]' => $row->getFrom(), '[to]' => $row->getTo() ) ) : CBTxt::P( 'removed [from]', array( '[from]' => $row->getFrom() ) ) ) . '</div>' : null )
							.							'</div>';
		}

		$return				.=						'</div>'
							.						'<div class="activityContentBodyFooter">'
							.							'<div class="activityContentBodyTime muted"><small>' . $row->getTimeAgo( true ) . '</small></div>'
							.						'</div>'
							.					'</div>'
							.				'</div>';
	}

	$return					.=			'</div>'
							.		'</div>'
							.	'</div>';

	echo $return;
}
?>