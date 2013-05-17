<?php

/**
 * Joomla Community Builder CB Gallery Module: mod_cbgallery
 * @version $Id$
 * @package mod_cbgallery
 * @subpackage mod_cbgallery.php
 * @author Nant, JoomlaJoe and Beat
 * @copyright (C) Nant, JoomlaJoe and Beat, www.joomlapolis.com
 * @license Limited  http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @final 1.2.2
 */

/** ensure this file is being included by a parent file */
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

/**
 * CB framework
 * @global CBframework $_CB_framework
 */
global $_CB_framework, $_CB_database, $ueConfig, $mainframe, $_SERVER;
if ( defined( 'JPATH_ADMINISTRATOR' ) ) {
	if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) {
		echo 'CB not installed';
		return;
	}
	include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );
} else {
	if ( ! file_exists( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' ) ) {
		echo 'CB not installed';
		return;
	}
	include_once( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' );
}
cbimport( 'cb.database' );
cbimport( 'language.front' );
cbimport( 'language.cbteamplugins' );

$pg_live_site			=	$_CB_framework->getCfg( 'live_site' );

$current_userid			=	(int) $_CB_framework->myId();

$htmltext0 = $htmltext1 = $htmltext2 = $mod_body = "";

$PGImagesAbsolutePath	= $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/';
$PGImagesPath			= 'components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/';
$PGItemAbsolutePath		= $_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/plug_profilegallery/';
$PGItemPath				= 'images/comprofiler/plug_profilegallery/';

// get moduleclass suffix (not used yet)
$class_sfx										=	$params->get( 'moduleclass_sfx', "" );

// get display mode parameter setting
// 0 = Static
// 1 = JS Image Scroller
$pgm_display_mode								=	intval( $params->get( 'pgm_display_mode', 0 ) );

// get search parameters
$pgm_gallery_search_order						=	intval( $params->get( 'pgm_gallery_search_order', 0 ) );

// get date filter parameters
$pgm_date_filter								=	intval( $params->get( 'pgm_date_filter', 0 ) );
$pgm_number_of_days								=	intval( $params->get( 'pgm_number_of_days', 10 ) );

// get filter parameters
$pgm_gallery_filter_option						=	intval( $params->get( 'pgm_gallery_filter_option', 0 ) );
$pgm_filter_userids								=	trim( $params->get( 'pgm_filter_userids', "" ) );

$pgm_item_count									=	intval( $params->get( 'pgm_item_count', 1 ) );
$pgm_item_types									=	trim( $params->get( 'pgm_item_types', "jpg,gif,png" ) );

// get global presentation parameters
$pgm_item_width									=	intval( $params->get( 'pgm_item_width', 100 ) );
$pgm_item_height								=	intval( $params->get( 'pgm_item_height', 100 ) );

// get static presentation parameters
$pgm_static_box_style							=	$params->get( 'pgm_static_box_style', "border:1px solid;padding:5px;" );

$pgm_static_layout_option						=	intval( $params->get( 'pgm_static_layout_option', 0 ) );

$pgm_static_gallery_item_header				=	intval( $params->get( 'pgm_static_gallery_item_header', 0 ) );
$pgm_static_gallery_item_header_parms			=	$params->get( 'pgm_static_gallery_item_header_parms', '<,10,...' );
if ( substr_count( $pgm_static_gallery_item_header_parms, ',' ) != 3 ) {
	$pgm_static_gallery_item_header_parms		=	"[,10,...,]";
}
$hparm											=	explode( ",", $pgm_static_gallery_item_header_parms );
$pgm_static_gallery_header_link				=	intval( $params->get( 'pgm_static_gallery_header_link', 0 ) );
$pgm_static_gallery_item_header_style			=	$params->get( 'pgm_static_gallery_item_header_style', "background-color:blue;color:white;" );

$pgm_static_gallery_item_link					=	intval( $params->get( 'pgm_static_gallery_item_link', 0 ) );

$pgm_static_gallery_item_footer				=	intval( $params->get( 'pgm_static_gallery_item_footer', 0 ) );
$pgm_static_gallery_item_footer_parms			=	$params->get( 'pgm_static_gallery_item_footer_parms', '<,10,...' );
if ( substr_count( $pgm_static_gallery_item_footer_parms, ',' ) != 3 ) {
	$pgm_static_gallery_item_footer_parms		=	"[,10,...,]";
}
$fparm											=	explode( ",", $pgm_static_gallery_item_footer_parms );
$pgm_static_gallery_footer_link				=	intval( $params->get( 'pgm_static_gallery_footer_link', 0 ) );
$pgm_static_gallery_item_footer_style			=	$params->get( 'pgm_static_gallery_item_footer_style', "background-color:blue;color:white;" );

$pgm_static_gallery_item_display_parms			=	$params->get( 'pgm_static_gallery_item_display_parms', "70,5" );
if ( substr_count( $pgm_static_gallery_item_display_parms, ',' ) != 1 ) {
	$pgm_static_gallery_item_display_parms		=	"40,5";
}
$dparm											=	explode( ",", $pgm_static_gallery_item_display_parms );

$pgm_static_gallery_date_style					=	$params->get( 'pgm_static_gallery_date_style', "%d %M %Y" );

$pgm_scroller_width								=	intval( $params->get( 'pgm_scroller_width', 140 ) );
$pgm_scroller_height							=	intval( $params->get( 'pgm_scroller_height', 140 ) );
$pgm_scroller_speed								=	intval( $params->get( 'pgm_scroller_speed', 3 ) );
$pgm_scroller_background_color					=	$params->get( 'pgm_scroller_background_color', "#EAEAEA" );
$pgm_scroller_imagegap							=	$params->get( 'pgm_scroller_imagegap', "" );
$pgm_scroller_slideshowgap						=	intval( $params->get( 'pgm_scroller_slideshowgap', 5 ) );

$gallery_orderby_clause			=	"";
switch ( $pgm_gallery_search_order ) {
	case 0 : // Random Gallery Item
		$gallery_orderby_clause	=	"\n ORDER BY RAND()";
	break;
	case 1 : // Newest Gallery Item
		$gallery_orderby_clause	=	"\n ORDER BY pgitemdate desc";
	break;
	case 2 : // Oldest Gallery Item
		$gallery_orderby_clause	=	"\n ORDER BY pgitemdate asc";
	break;
	case 3 : // Largest (in bytes) Gallery Item
		$gallery_orderby_clause	=	"\n ORDER BY pgitemsize desc";
	break;
	case 4 : // Smallest (in bytes) Gallery Item
		$gallery_orderby_clause	=	"\n ORDER BY pgitemsize asc";
	break;
	default :
		$gallery_orderby_clause	=	"";
	break;
}

switch ( $pgm_date_filter ) {
	case 0 : // None
		$date_filter_where_clause	=	"";
	break;
	case 1 : // Last X days
		$date_filter_where_clause	=	"\n AND (pgitemdate >= DATE_SUB(CURRENT_DATE(), INTERVAL " . (int) $pgm_number_of_days . ' DAY))';
	break;
	case 2 : // This week
		$date_filter_where_clause	=	"\n AND (YEARWEEK(pgitemdate) = YEARWEEK(CURRENT_DATE()))";
	break;
	case 3 : // This month
		$date_filter_where_clause	=	"\n AND (LEFT(pgitemdate,7) = LEFT(CURRENT_DATE(),7))";
	break;
	case 4 : // Since previous visit
		$date_filter_where_clause	=	"";
	break;
	default :
		$date_filter_where_clause	=	"";
}

if ( $pgm_filter_userids != '' ) {
	$filter_uids					=	explode( ',', $pgm_filter_userids );
	foreach ($filter_uids as $ku => $uu ) {
		$filter_uids[$ku]			=	(int) $uu;
	}
	$userids_filter					=	"\n AND userid in (" . implode( ',', $filter_uids ) . ')';
} else {
	$userids_filter					=	'';
}
if ( $pgm_item_types != '' ) {
	$filter_types					=	explode( ',', $pgm_item_types );
	foreach ($filter_types as $ku => $uu ) {
		$filter_types[$ku]			=	$_CB_database->Quote( trim( $uu ) );
	}
	$gallery_itm_type_filter		=	"\n AND pgitemtype in ( " . implode( ',', $filter_types ) . ')';
} else {
	$gallery_itm_type_filter		=	'';
}

$gallery_where_clause				=	"\n WHERE pgitempublished=1 AND pgitemapproved=1' . $gallery_itm_type_filter . $userids_filter $date_filter_where_clause";

// if needed get cb gallery tab parameters
if ( $pgm_gallery_filter_option != 0 ) {
	$tabparms_query					=	"SELECT params" . "\n FROM #__comprofiler_tabs" . "\n WHERE pluginclass='getProfileGalleryTab'";
	$_CB_database->setQuery( $tabparms_query );
	$cbgallerytabparms				=	$_CB_database->loadResult();
	$num_cbgallerytabparms			=	substr_count( $cbgallerytabparms, chr( 10 ) );
	$cbgallerytabparm				=	explode( chr( 10 ), $cbgallerytabparms );
	for ( $i = 0 ; $i < $num_cbgallerytabparms + 1 ; $i++ ) {
		$cbtabparmpair				=	explode( "=", $cbgallerytabparm[$i] );
		$cbtabparmvalue[$cbtabparmpair[0]]	=	$cbtabparmpair[1];
	}
	//print_r($cbtabparmvalue);
}

$mod_no_query						=	0;
$connection_userids_filter			=	'';
switch ( $pgm_gallery_filter_option ) {
	case 0 : // None (all approved and published items)
		$mod_select					=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery PG";
		$gallery_where_clause		=	"\n WHERE pgitempublished=1 AND pgitemapproved=1";
		break;
	case 1 : // Public view (all items items viewable by public)
		if ( $cbtabparmvalue["pgAccessMode"] == "PUB" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 0 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery PG";
		}
		if ( $cbtabparmvalue["pgAccessMode"] != "PUB" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 1 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery AS PG" . "\n INNER JOIN #__comprofiler AS CP ON PG.userid = CP.id";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1" . "\n AND (cb_pgaccessmode='PUB')";
		}
		if ( $cbtabparmvalue["pgAccessMode"] == "PUB" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 1 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery AS PG" . "\n INNER JOIN #__comprofiler AS CP ON PG.userid = CP.id";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1" . "\n AND (cb_pgaccessmode IS NULL OR cb_pgaccessmode='PUB')";
		}
		if ( $cbtabparmvalue["pgAccessMode"] != "PUB" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 0 ) {
			// no way for any images to be displayed
			$mod_no_query			=	1;
		}
		break;
	case 2 : // Registered and Public view (all items viewable by registered members and public)
		if ( $cbtabparmvalue["pgAccessMode"] != "CON" && $cbtabparmvalue["pgAccessMode"] != "CON-S" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 0 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery PG";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1";
		}
		if ( ( $cbtabparmvalue["pgAccessMode"] == "CON" || $cbtabparmvalue["pgAccessMode"] == "CON-S" ) && $cbtabparmvalue["pgAllowAccessModeOverride"] == 0 ) {
			// no way for any images to be displayed
			$mod_no_query			=	1;
		}
		if ( ( $cbtabparmvalue["pgAccessMode"] == "PUB" || $cbtabparmvalue["pgAccessMode"] == "REG" || $cbtabparmvalue["pgAccessMode"] == "REG-S" ) && $cbtabparmvalue["pgAllowAccessModeOverride"] == 1 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery AS PG" . "\n INNER JOIN #__comprofiler AS CP ON PG.userid = CP.id";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1" . "\n AND (cb_pgaccessmode IS NULL OR cb_pgaccessmode='PUB' OR cb_pgaccessmode='REG' OR cb_pgaccessmode='REG-S')";
		}
		if ( ( $cbtabparmvalue["pgAccessMode"] == "CON" || $cbtabparmvalue["pgAccessMode"] == "CON-S" ) && $cbtabparmvalue["pgAllowAccessModeOverride"] == 1 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery AS PG" . "\n INNER JOIN #__comprofiler AS CP ON PG.userid = CP.id";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1" . "\n AND (cb_pgaccessmode='PUB' OR cb_pgaccessmode='REG' OR cb_pgaccessmode='REG-S')";
		}
		break;
	case 3 : // Registered view only (only items marked as viewable by registered members)
		if ( ( $cbtabparmvalue["pgAccessMode"] != "REG" || $cbtabparmvalue["pgAccessMode"] == "REG-S" ) && $cbtabparmvalue["pgAllowAccessModeOverride"] == 0 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery PG";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1";
		}
		if ( $cbtabparmvalue["pgAccessMode"] != "REG" && $cbtabparmvalue["pgAccessMode"] != "REG-S" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 0 ) {
			// no way for any images to be displayed
			$mod_no_query			=	1;
		}
		if ( ( $cbtabparmvalue["pgAccessMode"] == "REG" || $cbtabparmvalue["pgAccessMode"] == "REG-S" ) && $cbtabparmvalue["pgAllowAccessModeOverride"] == 1 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery AS PG" . "\n INNER JOIN #__comprofiler AS CP ON PG.userid = CP.id";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1" . "\n AND (cb_pgaccessmode IS NULL OR cb_pgaccessmode='REG' OR cb_pgaccessmode='REG-S')";
		}
		if ( $cbtabparmvalue["pgAccessMode"] != "REG" && $cbtabparmvalue["pgAccessMode"] != "REG-S" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 1 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery AS PG" . "\n INNER JOIN #__comprofiler AS CP ON PG.userid = CP.id";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1" . "\n AND (cb_pgaccessmode='REG' OR cb_pgaccessmode='REG-S')";
		}
		break;
	case 4 : // Connected view only (only items marked as viewable by connection)
		if ( $cbtabparmvalue["pgAccessMode"] != "CON" && $cbtabparmvalue["pgAccessMode"] != "CON-S" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 0 ) {
			// no way for any images to be displayed
			$mod_no_query			=	1;
		}
		if ( ( $cbtabparmvalue["pgAccessMode"] == "CON" || $cbtabparmvalue["pgAccessMode"] == "CON-S" ) && $cbtabparmvalue["pgAllowAccessModeOverride"] == 1 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery AS PG" . "\n INNER JOIN #__comprofiler AS CP ON PG.userid = CP.id";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1" . "\n AND (cb_pgaccessmode IS NULL OR cb_pgaccessmode='CON' OR cb_pgaccessmode='CON-S')";
		}
		if ( $cbtabparmvalue["pgAccessMode"] != "CON" && $cbtabparmvalue["pgAccessMode"] != "CON-S" && $cbtabparmvalue["pgAllowAccessModeOverride"] == 1 ) {
			$mod_select				=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery AS PG" . "\n INNER JOIN #__comprofiler AS CP ON PG.userid = CP.id";
			$gallery_where_clause	=	"\n WHERE pgitempublished=1 AND pgitemapproved=1" . "\n AND (cb_pgaccessmode='CON' OR cb_pgaccessmode='CON-S')";
		}
		break;
	case 5 : // User Connected items only (only connected items to current user)
		if ( $current_userid == 0 ) {
			echo "<p>" . CBTxt::T("Not logged on")  . "</p>"; // this string must be added to cbteamplugins_language.php
			return;
		}
		$find_connections_select	=	"SELECT referenceid" . "\n FROM #__comprofiler_members" . "\n WHERE memberid=" . (int) $current_userid . ' AND accepted=1 AND pending=0';
		$_CB_database->setQuery( $find_connections_select );
		$current_connections		=	$_CB_database->loadResultArray();
		if ( count( $current_connections ) == 0 ) {
			echo "<p>" . CBTxt::T("No connected items")  . "</p>"; // this string must be added to cbteamplugins_language.php
			return;
		}
		$connection_userids_filter	=	"\n AND userid in ( " . implode( ',', $current_connections ) . ')';
		$mod_select					=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery PG";
		$gallery_where_clause		=	"\n WHERE pgitempublished=1 AND pgitemapproved=1";
		break;
	case 6 : // All non-approved published items
		$mod_select					=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery PG";
		$gallery_where_clause		=	"\n WHERE pgitempublished=1 AND pgitemapproved=0";
		break;
	case 7 : // All non-approved non-published items
		$mod_select					=	"SELECT PG.id AS pgitemid, PG.userid AS profileid, PG.*" . "\n FROM #__comprofiler_plug_profilegallery PG";
		$gallery_where_clause		=	"\n WHERE pgitempublished=0 AND pgitemapproved=0";
		break;
}


$gallery_where_clause				.=	"\n" . $gallery_itm_type_filter . $userids_filter . $date_filter_where_clause . $connection_userids_filter;

if ( $mod_no_query ) {
	echo "<p>" . CBTxt::T('No Viewable Items') . "</p>";
	return;
}

// construct module display header and footer
switch ( $pgm_display_mode ) {
	case 0 : // Static
		switch ( $pgm_static_layout_option ) {
			case 0 :
				$mod_header		=	"<div style=\"width:100%;margin:0px;padding:0px;border:0px;text-align:center;\">";
				$mod_footer		=	"</div>";
				$mod_body		=	"";
				$mod_static_div	=	"<div style=\"" . $pgm_static_box_style . "margin:auto;height:" . ( $pgm_item_height + $dparm[0] ) . "px;width:" . ( $pgm_item_width + $dparm[1] ) . "px;text-align:center;\">";
			break;
			case 1 :
				$mod_header		=	"<div style=\"width:100%;margin:0px;padding:0px;border:0px;text-align:left;\">";
				$mod_footer		=	"</div>";
				$mod_body		=	"";
				$mod_static_div	=	"<div style=\"" . $pgm_static_box_style . "margin:1px;height:" . ( $pgm_item_height + $dparm[0] ) . "px;width:" . ( $pgm_item_width + $dparm[1] ) . "px;text-align:center;\">";
			
			break;
			case 2 :
				$mod_header		=	"<div style=\"width:100%;margin:0px;padding:0px;border:0px;text-align:left;float:left;\">";
				$mod_footer		=	"</div>";
				$mod_static_div	=	"<div style=\"" . $pgm_static_box_style . "margin:1px;height:" . ( $pgm_item_height + $dparm[0] ) . "px;width:" . ( $pgm_item_width + $dparm[1] ) . "px;text-align:center;float:left;\">";
				$mod_body		=	"";
			break;
		}
	break;
	case 1 : // JS Image Scroller 1 (dynamicindex14.js)
		$mod_js_scroller		=	"var pgmjs1_sliderwidth=\"" . $pgm_scroller_width . "px\"\n"
								.	"var pgmjs1_sliderheight=\"" . $pgm_scroller_height . "px\"\n"
								.	"var pgmjs1_slidespeed=$pgm_scroller_speed\n"
								.	"var pgmjs1_slidebgcolor=\"$pgm_scroller_background_color\"\n"
								.	"var pgmjs1_leftrightslide=new Array()\n"
								.	"var pgmjs1_finalslide=''\n"
								.	"var pgmjs1_imagegap=\"$pgm_scroller_imagegap\"\n"
								.	"var pgmjs1_slideshowgap=$pgm_scroller_slideshowgap\n"
								;
		$mod_header				=	"";
		$mod_footer				=	"";
	break;
	default :
	break;
}
if ( $pgm_item_count <= 0 ) {
	$gallery_limit_clause		=	"";
} else {
	$gallery_limit_clause		=	"\n LIMIT $pgm_item_count";
}

// construct final query statement
$mod_query						=	$mod_select . $gallery_where_clause . $gallery_orderby_clause . $gallery_limit_clause;

$_CB_database->setQuery( $mod_query );
$pgitems						=	$_CB_database->loadObjectList();

//print $_CB_database->getQuery();
$pgtotal						=	count( $pgitems );
if ( $pgtotal == 0 ) {
	echo "<p>" . CBTxt::T('No items rendered') . "</p>";
	return;
}

$k								=	0;

foreach ( $pgitems as $pgitem ) {
	//print_r($pgitem);
	$pgitemfilename				=	$pgitem->pgitemfilename;
	$pgitemtype					=	$pgitem->pgitemtype;
	$pgitemtitle				=	$pgitem->pgitemtitle;
	$pgitemuserid				=	$pgitem->userid;
	$js_pgitemtitle				=	str_replace( array( '"', '<', '>', "\\", "'", "&#039;" ), array( "&quot;", "&lt;", "&gt;", "\\\\", "\\'", "\\'" ), $pgitemtitle );
	$html_pgitemtitle			=	htmlspecialchars( $pgitemtitle );
	$html_pgitemtitle_header	=	htmlspecialchars( ( $hparm[1] && strlen( $pgitemtitle ) > $hparm[1] ) ? $hparm[0] . substr( $pgitemtitle, 0, $hparm[1] ) . $hparm[2] . $hparm[3] : $hparm[0] . $pgitemtitle . $hparm[3] );
	$html_pgitemtitle_footer	=	htmlspecialchars( ( $fparm[1] && strlen( $pgitemtitle ) > $fparm[1] ) ? $fparm[0] . substr( $pgitemtitle, 0, $fparm[1] ) . $fparm[2] . $fparm[3] : $fparm[0] . $pgitemtitle . $fparm[3] );
	$pgitemdescription			=	$pgitem->pgitemdescription;
	$js_pgitemdescription		=	str_replace( array( '"', '<', '>', "\\", "'", "&#039;" ), array( "&quot;", "&lt;", "&gt;", "\\\\", "\\'", "\\'" ), $pgitemdescription );
	$html_pgitemdescription		=	htmlspecialchars( $pgitemdescription );
	$html_pgitemdescription_header	=	htmlspecialchars( ( $hparm[1] && strlen( $pgitemdescription ) > $hparm[1] ) ? $hparm[0] . substr( $pgitemdescription, 0, $hparm[1] ) . $hparm[2] . $hparm[3] : $hparm[0] . $pgitemdescription . $hparm[3] );
	$html_pgitemdescription_footer	=	htmlspecialchars( ( $fparm[1] && strlen( $pgitemdescription ) > $fparm[1] ) ? $fparm[0] . substr( $pgitemdescription, 0, $fparm[1] ) . $fparm[2] . $fparm[3] : $fparm[0] . $pgitemdescription . $fparm[3] );

	$regs						=	array();
	if ( $pgitem->pgitemdate && preg_match( "/^([0-9]{4})-([0-9]{2})-([0-9]{2})[ ]([0-9]{2}):([0-9]{2}):([0-9]{2})$/", $pgitem->pgitemdate, $regs ) ) {
		$itmTime				=	mktime( $regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1] );
		$pgitemdate				=	( $itmTime > -1 ? strftime( $pgm_static_gallery_date_style, $itmTime + ( $_CB_framework->getCfg( 'offset' ) * 60 * 60 ) ) : '-' );
	} else {
		$pgitemdate				=	'-';
	}

	$html_pgitemdate_header	=	htmlspecialchars( ( $hparm[1] && strlen( $pgitemdate ) > $hparm[1] ) ? $hparm[0] . substr( $pgitemdate, 0, $hparm[1] ) . $hparm[2] . $hparm[3] : $hparm[0] . $pgitemdate . $hparm[3] );
	$html_pgitemdate_footer	=	htmlspecialchars( ( $fparm[1] && strlen( $pgitemdate ) > $fparm[1] ) ? $fparm[0] . substr( $pgitemdate, 0, $fparm[1] ) . $fparm[2] . $fparm[3] : $fparm[0] . $pgitemdate . $fparm[3] );
	
	$pgitemsize					=	$pgitem->pgitemsize;
	$html_pgitemsize_header	=	htmlspecialchars( ( $hparm[1] && strlen( $pgitemsize ) > $hparm[1] ) ? $hparm[0] . substr( $pgitemsize, 0, $hparm[1] ) . $hparm[2] . $hparm[3] : $hparm[0] . $pgitemsize . $hparm[3] );
	$html_pgitemsize_footer	=	htmlspecialchars( ( $fparm[1] && strlen( $pgitemsize ) > $fparm[1] ) ? $fparm[0] . substr( $pgitemsize, 0, $fparm[1] ) . $fparm[2] . $fparm[3] : $fparm[0] . $pgitemsize . $fparm[3] );
	
	$pgitempublished			=	$pgitem->pgitempublished;
	$pgitemapproved				=	$pgitem->pgitemapproved;
	$pgitemuserprofileid		=	$pgitem->profileid;
	
	$PGItemAbsoluteUserPath	=	$PGItemAbsolutePath . $pgitemuserprofileid . "/";
	$PGItemUserPath				=	$PGItemPath . $pgitemuserprofileid . "/";
	
	$pgitemfilenameuserpath	=	$PGItemUserPath . $pgitemfilename;
	$pgitemthumbuserpath		=	$PGItemUserPath . "tn" . $pgitemfilename;
	$pgitemfilenameuserabsolutepath		=	$PGItemAbsoluteUserPath . $pgitemfilename;
	$pgitemthumbuserabsolutepath			=	$PGItemAbsoluteUserPath . "tn" . $pgitemfilename;
	if ( ! file_exists( $pgitemthumbuserabsolutepath ) ) {
		$pgitemthumbuserabsolutepath		=	$PGImagesAbsolutePath . "pgtn_" . $pgitemtype . "item.gif";
		$pgitemthumbuserpath				=	$PGImagesPath . "pgtn_" . $pgitemtype . "item.gif";
		if ( ! file_exists( $pgitemthumbuserabsolutepath ) ) {
			$pgitemthumbuserabsolutepath	=	$PGImagesAbsolutePath . "pgtn_nonimageitem.gif";
			$pgitemthumbuserpath			=	$PGImagesPath . "pgtn_nonimageitem.gif";
		}
	}
	
	list( $pgitem_width, $pgitem_height, $pgitem_type, $pgitem_attr )		=	getimagesize( $pgitemthumbuserabsolutepath );
	if ( $pgitem_width <= $pgm_item_width ) {
		$needs_new_width		=	false;
		$resize_width_factor	=	1;
	} else {
		$needs_new_width		=	true;
		$resize_width_factor	=	$pgm_item_width / $pgitem_width;
	}
	if ( $pgitem_height <= $pgm_item_height ) {
		$needs_new_height		=	false;
		$resize_height_factor	=	1;
	} else {
		$needs_new_height		=	true;
		$resize_height_factor	=	$pgm_item_height / $pgitem_height;
	}
	
	$resize_factor				=	min( $resize_width_factor, $resize_height_factor );
	$new_height					=	floor( $pgitem_height * $resize_factor );
	$new_width					=	floor( $pgitem_width * $resize_factor );
	
	$pgitemurl					=	"<a href=\"" . $pgitemfilenameuserpath . "\">" . $pgitemfilename . "</a>";
	$pglivelink					=	$pg_live_site . "/" . $pgitemfilenameuserpath;
	$pglivelink_thumbnail		=	$pg_live_site . "/" . $pgitemthumbuserpath;
	$pgitemtitle_url			=	"<a href=\"" . cbSef( $pgitemfilenameuserpath ) . "\" target=\"_blank\"><b>" . $html_pgitemtitle . "</b></a><br />";
	
	$pgitemthumbnail			=	"<div style=\"height:" . $pgm_item_height . "px;\">" . "<img src=\"$pgitemthumbuserpath\" border=\"0\" height=\"$new_height\" width=\"$new_width\" title=\"$html_pgitemtitle\" alt=\"$html_pgitemtitle\" />" . "</div>";
	
	// $cbgallery_sefaddress		=	cbSef( "index.php?option=com_comprofiler&amp;task=userProfile&amp;user=$pgitemuserprofileid&amp;tab=getProfileGalleryTab" );
	
	$cbgallery_sefaddress = $_CB_framework->userProfileUrl( $pgitemuserprofileid, false, 'getProfileGalleryTab' );
	
	$mod_js_scroller_url		=	"<a href=\"$cbgallery_sefaddress\">";
	$mod_js_scroller_image		=	"<img src=\"$pgitemthumbuserpath\" border=\"0\" height=\"$new_height\" width=\"$new_width\" title=\"$js_pgitemtitle\" alt=\"$js_pgitemtitle\" />";
	
	$pgitemthumbnail_cbgallery_link	=	"<div style=\"height:" . $pgm_item_height . "px;\">"
								.	"<a href=\"$cbgallery_sefaddress\">"
								.	"<img src=\"$pgitemthumbuserpath\" border=\"0\" height=\"$new_height\" width=\"$new_width\" title=\"$html_pgitemtitle\" alt=\"$html_pgitemtitle\" />"
								.	"</a>"
								.	"</div>"
								;
	
	if ( $pgm_display_mode == 0 )
		$mod_body	.=	$mod_static_div;
	
	switch ( $pgm_static_gallery_item_header ) {
		case 0 : // none
		break;
		case 1 : // Gallery item title
			if ( $pgm_static_gallery_header_link == 1 ) {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . "<a href=\"$cbgallery_sefaddress\">" . $html_pgitemtitle_header . "</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . $html_pgitemtitle_header . "</div>" . "<br />";
			}
		break;
		case 2 : // Gallery item description
			if ( $pgm_static_gallery_header_link == 1 ) {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . "<a href=\"$cbgallery_sefaddress\">" . $html_pgitemdescription_header . "</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . $html_pgitemdescription_header . "</div>" . "<br />";
			}
		break;
		case 3 : // Gallery item size
			if ( $pgm_static_gallery_header_link == 1 ) {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . "<a href=\"$cbgallery_sefaddress\">$html_pgitemsize_header</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . $html_pgitemsize_header . "</div>" . "<br />";
			}
		break;
		case 4 : // Gallery item date format
			if ( $pgm_static_gallery_header_link == 1 ) {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . "<a href=\"$cbgallery_sefaddress\">$html_pgitemdate_header</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . $html_pgitemdate_header . "</div>" . "<br />";
			}
		break;
		
		case 5 : // Gallery username
			$get_username_select			=	"SELECT username" . "\n FROM #__users" . "\n WHERE id='$pgitemuserid'";
			$_CB_database->setQuery( $get_username_select );
			$pgitemusername					=	$_CB_database->loadResult();
			$html_pgitemusername			=	htmlspecialchars( $pgitemusername );
			$html_pgitemusername_header	=	htmlspecialchars( ( $hparm[1] && strlen( $pgitemusername ) > $hparm[1] ) ? $hparm[0] . substr( $pgitemusername, 0, $hparm[1] ) . $hparm[2] . $hparm[3] : $hparm[0] . $pgitemusername . $hparm[3] );
			$html_pgitemusername_footer	=	htmlspecialchars( ( $fparm[1] && strlen( $pgitemusername ) > $fparm[1] ) ? $fparm[0] . substr( $pgitemusername, 0, $fparm[1] ) . $fparm[2] . $fparm[3] : $fparm[0] . $pgitemusername . $fparm[3] );
			
			if ( $pgm_static_gallery_header_link == 1 ) {
				
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . "<a href=\"$cbgallery_sefaddress\">" . $html_pgitemusername_header . "</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_header_style\">" . $html_pgitemusername_header . "</div>" . "<br />";
			}
		break;
	}
	
	switch ( $pgm_static_gallery_item_link ) {
		case 0 : // no link on image
			$mod_body	.=	$pgitemthumbnail;
			$mod_body	.=	"<br />";
		break;
		case 1 : // link image back to user's profile gallery tab
			$mod_body	.=	$pgitemthumbnail_cbgallery_link;
			$mod_body	.=	"<br />";
		break;
	}
	
	switch ( $pgm_static_gallery_item_footer ) {
		case 0 : // none
		break;
		case 1 : // Gallery item title
			if ( $pgm_static_gallery_footer_link == 1 ) {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . "<a href=\"$cbgallery_sefaddress\">$html_pgitemtitle_footer</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . $html_pgitemtitle_footer . "</div>" . "<br />";
			}
		break;
		case 2 : // Gallery item description
			if ( $pgm_static_gallery_footer_link == 1 ) {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . "<a href=\"$cbgallery_sefaddress\">$html_pgitemdescription_footer</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . $html_pgitemdescription_footer . "</div>" . "<br />";
			}
		break;
		case 3 : // Gallery item size
			if ( $pgm_static_gallery_footer_link == 1 ) {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . "<a href=\"$cbgallery_sefaddress\">$html_pgitemsize_footer</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . $html_pgitemsize_footer . "</div>" . "<br />";
			}
		break;
		case 4 : // Gallery item date format
			if ( $pgm_static_gallery_footer_link == 1 ) {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . "<a href=\"$cbgallery_sefaddress\">$html_pgitemdate_footer</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . $html_pgitemdate_footer . "</div>" . "<br />";
			}
		break;
		case 5 : // Gallery username
			$get_username_select			=	"SELECT username" . "\n FROM #__users" . "\n WHERE id='$pgitemuserid'";
			$_CB_database->setQuery( $get_username_select );
			$pgitemusername					=	$_CB_database->loadResult();
			$html_pgitemusername			=	htmlspecialchars( $pgitemusername );
			$html_pgitemusername_header	=	htmlspecialchars( ( $hparm[1] && strlen( $pgitemusername ) > $hparm[1] ) ? $hparm[0] . substr( $pgitemusername, 0, $hparm[1] ) . $hparm[2] . $hparm[3] : $hparm[0] . $pgitemusername . $hparm[3] );
			$html_pgitemusername_footer	=	htmlspecialchars( ( $fparm[1] && strlen( $pgitemusername ) > $fparm[1] ) ? $fparm[0] . substr( $pgitemusername, 0, $fparm[1] ) . $fparm[2] . $fparm[3] : $fparm[0] . $pgitemusername . $fparm[3] );
			
			if ( $pgm_static_gallery_footer_link == 1 ) {
				
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . "<a href=\"$cbgallery_sefaddress\">" . $html_pgitemusername_footer . "</a>" . "</div>" . "<br />";
			} else {
				$mod_body	.=	"<div style=\"$pgm_static_gallery_item_footer_style\">" . $html_pgitemusername_footer . "</div>" . "<br />";
			}
		break;
	
	}
	switch ( $pgm_static_layout_option ) {
		case 0 :
			$mod_body	.=	"</div><br />";
		break;
		case 1 :
			$mod_body	.=	"</div>";
		break;
		case 2 :
			$mod_body	.=	"</div>";
		break;
	}
	
	if ( $pgm_display_mode == 1 ) {
		
		$mod_js_scroller	.=	"pgmjs1_leftrightslide[$k]='$mod_js_scroller_url $mod_js_scroller_image</a>'\n";
	
	}
	
	$k++;
}
$mod_footer	.=	"<div style=\"clear:both;\">&nbsp;</div>";

if ( $pgm_display_mode == 1 ) {
	echo "<script language=\"JavaScript\" type=\"text/javascript\">\n\n";
	echo $mod_js_scroller . "\n\n";
	echo "</script>\n\n";
    echo "<script language=\"JavaScript\" type=\"text/javascript\" src=\"" . $pg_live_site . "/modules/" . ( checkJversion() > 0 ? "mod_cbgallery/" : "") . "mod_cbgallery/cbgallery1.js\"></script>";
	//echo "<script language=\"JavaScript\" type=\"text/javascript\" src=\"" . $pg_live_site . "/modules/mod_cbgallery/cbgallery1.js\"></script>";
	echo $mod_footer;
}

if ( $pgm_display_mode == 0 ) {
	echo $mod_header . $mod_body . $mod_footer;
}

?>