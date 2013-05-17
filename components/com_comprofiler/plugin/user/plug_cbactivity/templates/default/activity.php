<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_cbactivityActivity {

	static public function getMessageDisplay( $row, &$cache, $user, $plugin, $module = false ) {
		global $_CB_database, $_CB_framework, $_PLUGINS;

		$titleLength										=	(int) $plugin->params->get( 'activity_title_length', 0 );
		$descLength											=	(int) $plugin->params->get( 'activity_desc_length', 0 );
		$imgThumbnails										=	(bool) $plugin->params->get( 'activity_img_thumbnails', 0 );

		if ( $module ) {
			if ( is_array( $module ) ) {
				$titleLength								=	( isset( $module[0] ) ? (int) $module[0] : $titleLength );
				$descLength									=	( isset( $module[1] ) ? (int) $module[1] : $descLength );
				$imgThumbnails								=	( isset( $module[2] ) ? (bool) $module[2] : $imgThumbnails );
				$module										=	true;
			}
		}

		$return												=	null;

		$_PLUGINS->trigger( 'activity_onBeforeMessageDisplay', array( &$return, $row, &$cache, &$titleLength, &$descLength, &$imgThumbnails, &$module, $user, $plugin ) );

		switch ( $row->get( 'type' ) ) {
			case 'groupjive':
				if ( ! $cache['gjLoaded']++ ) {
					require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );
				}

				$itemId										=	(int) $row->get( 'item' );

				switch ( $row->get( 'subtype' ) ) {
					case 'category':
						if ( ! isset( $cache['gjCategories'][$itemId] ) ) {
							$cache['gjCategories'][$itemId]	=	cbgjData::getGroups( null, array( 'id', '=', $itemId ), null, null, false );
						}

						$item								=	$cache['gjCategories'][$itemId];
						$return								=	HTML_cbactivityActivity::getItemDisplay( $item->getLogo( true, true, $imgThumbnails ), $item->getName( $titleLength, true ), $item->getDescription( $descLength ), null, $module );
						break;
					case 'group':
						if ( ! isset( $cache['gjGroups'][$itemId] ) ) {
							$cache['gjGroups'][$itemId]		=	cbgjData::getGroups( null, array( 'id', '=', $itemId ), null, null, false );
						}

						$item								=	$cache['gjGroups'][$itemId];
						$return								=	HTML_cbactivityActivity::getItemDisplay( $item->getLogo( true, true, $imgThumbnails ), $item->getName( $titleLength, true ), $item->getDescription( $descLength ), null, $module );
						break;
					case 'event':
						if ( ! isset( $cache['gjEvents'][$itemId] ) ) {
							$cache['gjEvents'][$itemId]		=	cbgjEventsData::getEvents( null, array( 'id', '=', $itemId ), null, null, false );
						}

						$item								=	$cache['gjEvents'][$itemId];
						$title								=	$item->getTitle( $titleLength, true ) . '<br /><small>'.$item->getDate().'</small>';

						if ( $item->get( 'location' ) ) {
							$location						=	'<div><i class="icon-map-marker"></i> ' . htmlspecialchars( $item->get( 'location' ) ) . '</div>';
						} else {
							$location						=	null;
						}

						$event								=	( $location ? '<div class="activityItemContentBodyInfoRow">' . $item->getEvent( $descLength ) . '</div>' . $location : $item->getEvent( $descLength ) );
						$return								=	HTML_cbactivityActivity::getItemDisplay( null, $title, $event, $item->getGroup()->getName( $titleLength, true ), $module );
						break;
					case 'file':
						if ( ! isset( $cache['gjFiles'][$itemId] ) ) {
							$cache['gjFiles'][$itemId]		=	cbgjFileData::getFiles( null, array( 'id', '=', $itemId ), null, null, false );
						}

						$item								=	$cache['gjFiles'][$itemId];
						$file								=	( $item->getDescription( $descLength ) ? '<div class="activityItemContentBodyInfoRow">' . $item->getFileSize() . ' | ' . $item->getFileExt() . '</div>' . $item->getDescription( $descLength ) : $item->getFileSize() . ' | ' . $item->getFileExt() );
						$return								=	HTML_cbactivityActivity::getItemDisplay( $item->getIcon( true, 'tab' ), $item->getTitle( $titleLength, 'tab' ), $file, $item->getGroup()->getName( $titleLength, true ), $module );
						break;
					case 'photo':
						if ( ! isset( $cache['gjPhotos'][$itemId] ) ) {
							$cache['gjPhotos'][$itemId]		=	cbgjPhotoData::getPhotos( null, array( 'id', '=', $itemId ), null, null, false );
						}

						$item								=	$cache['gjPhotos'][$itemId];
						$return								=	HTML_cbactivityActivity::getItemDisplay( $item->getImage( true, 'tab', $imgThumbnails ), $item->getTitle( $titleLength, 'tab' ), $item->getDescription( $descLength ), $item->getGroup()->getName( $titleLength, true ), $module );
						break;
					case 'video':
						if ( ! isset( $cache['gjVideos'][$itemId] ) ) {
							$cache['gjVideos'][$itemId]		=	cbgjVideoData::getVideos( null, array( 'id', '=', $itemId ), null, null, false );
						}

						$item								=	$cache['gjVideos'][$itemId];
						$return								=	HTML_cbactivityActivity::getItemDisplay( $item->getEmbed( true, $imgThumbnails ), $item->getTitle( $titleLength, 'tab' ), $item->getCaption( $descLength ), $item->getGroup()->getName( $titleLength, true ), $module );
						break;
					case 'wall':
						if ( ! isset( $cache['gjPosts'][$itemId] ) ) {
							$cache['gjPosts'][$itemId]		=	cbgjWallData::getPosts( null, array( 'id', '=', $itemId ), null, null, false );
						}

						$item								=	$cache['gjPosts'][$itemId];
						$return								=	HTML_cbactivityActivity::getItemDisplay( null, null, $item->getPost( $descLength ), $item->getGroup()->getName( $titleLength, true  ), $module );
						break;
				}
				break;
			case 'kunena':
				KunenaForum::setup();

				switch ( $row->get( 'subtype' ) ) {
					case 'message':
						$itemId								=	(int) $row->get( 'item' );

						if ( ! isset( $cache['messages'][$itemId] ) ) {
							$cache['messages'][$itemId]		=	KunenaForumMessage::getInstance( $itemId );
						}

						$item								=	$cache['messages'][$itemId];
						$title								=	'<a href="' . $item->getUrl() . '">' . cbactivityClass::cleanBBCode( $item->subject ) . '</a>';
						$return								=	HTML_cbactivityActivity::getItemDisplay( null, $title, cbactivityClass::cleanBBCode( $item->message ), null, $module );
						break;
				}
				break;
			case 'profilebook':
					$itemId									=	(int) $row->get( 'item' );

					if ( ! isset( $cache['pb'][$itemId] ) ) {
						$query								=	'SELECT *'
															.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plug_profilebook' )
															.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . $itemId;
						$_CB_database->setQuery( $query );
						$cache['pb'][$itemId]				=	null;
						$_CB_database->loadObject( $cache['pb'][$itemId] );
					}

					$item									=	$cache['pb'][$itemId];
					$tab									=	( $item->mode == 'g' ? 'getprofilebooktab' : ( $item->mode == 'w' ? 'getprofilebookwalltab' : ( $item->mode == 'b' ? 'getprofilebookblogtab' : null ) ) );
					$title									=	( $item->postertitle ? '<a href="' . $_CB_framework->userProfileUrl( $item->userid, true, $tab ) . '">' . htmlspecialchars( $item->postertitle ) . '</a>' : null );
					$return									=	HTML_cbactivityActivity::getItemDisplay( null, $title, cbactivityClass::cleanBBCode( $item->postercomment ), null, $module );
				break;
			case 'profilegallery':
					$itemId									=	(int) $row->get( 'item' );

					if ( ! isset( $cache['pg'][$itemId] ) ) {
						$query								=	'SELECT *'
															.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plug_profilegallery' )
															.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . $itemId;
						$_CB_database->setQuery( $query );
						$cache['pg'][$itemId]				=	null;
						$_CB_database->loadObject( $cache['pg'][$itemId] );
					}

					$item									=	$cache['pg'][$itemId];
					$tabUrl									=	$_CB_framework->userProfileUrl( $item->userid, true, 'getProfileGalleryTab' );

					if ( in_array( $item->pgitemtype, array( 'jpg', 'jpeg', 'gif', 'png', 'bmp' ) ) ) {
						$logo								=	'<a href="' . $tabUrl . '"><img src="' . $_CB_framework->getCfg( 'live_site' ) . '/images/comprofiler/plug_profilegallery/' . (int) $item->userid . '/' . ( $imgThumbnails ? 'tn' : null ) . $item->pgitemfilename . '" /></a>';
					} else {
						if ( file_exists( $_CB_framework->GetCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/' . ( $imgThumbnails ? 'pgtn_' : 'pg_' ) . $item->pgitemtype . 'item.gif' ) ) {
							$img							=	'pgtn_' . $item->pgitemtype . 'item.gif';
						} else {
							$img							=	'pgtn_nonimageitem.gif';
						}

						$logo								=	'<a href="' . $tabUrl . '"><img src="' . $_CB_framework->getCfg( 'live_site' ) . '/components/com_comprofiler/plugin/user/plug_cbprofilegallery/images/' . $img . '" /></a>';
					}

					$title									=	'<a href="' . $tabUrl . '">' . htmlspecialchars( $item->pgitemtitle ) . '</a>';
					$return									=	HTML_cbactivityActivity::getItemDisplay( $logo, $title, cbactivityClass::cleanBBCode( $item->pgitemdescription ), null, $module );
				break;
			case 'cbblogs':
				if ( ! $cache['blogsLoaded']++ ) {
					require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbblogs/cbblogs.class.php' );
				}

				$itemId										=	(int) $row->get( 'item' );

				if ( ! isset( $cache['blogs'][$itemId] ) ) {
					$cache['blogs'][$itemId]				=	cbblogsData::getBlogs( null, array( 'id', '=', $itemId ), null, null, false );
				}

				$item										=	$cache['blogs'][$itemId];
				$return										=	HTML_cbactivityActivity::getItemDisplay( null, $item->getTitle( 0, $item->getBlog()->published ), cbactivityClass::cleanBBCode( $item->getBlog()->blog_text ), null, $module );
				break;
			default:
				$return										=	HTML_cbactivityActivity::getItemDisplay( null, null, $row->getMessage(), null, $module );
				break;
		}

		$_PLUGINS->trigger( 'activity_onAfterMessageDisplay', array( &$return, $row, &$cache, $titleLength, $descLength, $imgThumbnails, $module, $user, $plugin ) );

		return $return;
	}

	static public function getItemDisplay( $logo = null, $title = null, $description = null, $footer = null, $module = false ) {
		global $_PLUGINS;

		$return				=	null;

		$_PLUGINS->trigger( 'activity_onBeforeItemDisplay', array( &$return, &$logo, &$title, &$description, &$footer, $module ) );

		if ( $logo || $title || $description || $footer ) {
			$return			.=	'<div class="activityItemContent">'
							.		'<div class="activityItemContentBody">';

			if ( $title ) {
				$return		.=			'<div class="activityItemContentTitle"><h5>' . $title . '</h5></div>';
			}

			if ( $logo || $description ) {
				$return		.=			'<div class="activityItemContentBodyInfo">'
							.				( $logo && $description ? '<div class="activityItemContentBodyInfoRow">' . $logo . '</div>' : ( $logo ? $logo : null ) )
							.				$description
							.			'</div>';
			}

			$return			.=		'</div>';

			if ( $footer ) {
				$return		.=		'<div class="activityItemContentDivider"></div>'
							.		'<div class="activityItemContentFooter">'
							.			$footer
							.		'</div>';
			}

			$return			.=	'</div>';
		}

		$_PLUGINS->trigger( 'activity_onAfterItemDisplay', array( &$return, $logo, $title, $description, $footer, $module ) );

		return $return;
	}
}
?>