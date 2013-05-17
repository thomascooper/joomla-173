<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $_PLUGINS;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

$_PLUGINS->registerFunction( 'gj_onBeforeGroupTab', 'getVideos', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteGroup', 'deleteVideos', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupEdit', 'getParam', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onConfigIntegrations', 'getConfig', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onPluginFE', 'getPluginFE', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateGroup', 'setParam', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateGroup', 'setParam', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteUser', 'leaveGroup', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'deleteUser', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAuthorization', 'getAuthorization', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupNotifications', 'getNotifications', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onBeforeUpdateNotificationParams', 'setNotifications', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsGeneral', 'getMenu', 'cbgjVideoPlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsSave', 'saveMenu', 'cbgjVideoPlugin' );

class cbgjVideoPlugin extends cbPluginHandler {

	public function getPluginFE( $params, $user, $plugin ) {
		if ( $params[1] && $params[2] ) {
			switch ( $params[0] ) {
				case 'video_publish':
					$this->stateVideo( $params[1], $params[2], $params[3], 1, $user, $plugin );
					break;
				case 'video_unpublish':
					$this->stateVideo( $params[1], $params[2], $params[3], 0, $user, $plugin );
					break;
				case 'video_edit':
					$this->editVideo( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'video_save':
					cbSpoofCheck( 'plugin' );
					$this->saveVideo( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'video_delete':
					$this->deleteVideo( $params[1], $params[2], $params[3], $user, $plugin );
					break;
			}
		} else {
			switch ( $params[0] ) {
				case 'video_approval':
					$this->getVideoApproval( $user, $plugin );
					break;
			}
		}
	}

	public function getVideoApproval( $user, $plugin ) {
		cbgjClass::getTemplate( 'cbgroupjivevideo_approval' );

		$paging				=	new cbgjPaging( 'video_approval' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'video_approval_limit', 2 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'url', 'CONTAINS', $search, array( 'title', 'CONTAINS', $search ) );
		}

		$searching			=	( count( $where ) ? true : false );

		$where[]			=	array( 'published', '=', -1, 'c.params', 'CONTAINS', 'video_approve=1' );

		$total				=	count( cbgjVideoData::getVideos( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjVideoData::getVideos( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where, null, ( $plugin->params->get( 'video_approval_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::T( 'Search Videos...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjivevideoapproval' ) ) {
			HTML_cbgroupjivevideoapproval::showVideosApproval( $rows, $pageNav, $user, $plugin );
		} else {
			$this->showVideosApproval( $rows, $pageNav, $user, $plugin );
		}
	}

	private function showVideosApproval( $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle				=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( CBTxt::T( 'Video Approval' ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( CBTxt::T( 'Video Approval' ), cbgjClass::getPluginURL( array( 'plugin', 'video_approval' ) ) );

		$videoApprovalSearch		=	$plugin->params->get( 'video_approval_search', 1 );
		$videoApprovalPaging		=	$plugin->params->get( 'video_approval_paging', 1 );
		$videoApprovalLimitbox		=	$plugin->params->get( 'video_approval_limitbox', 1 );

		$return						=	'<div class="gjVideoApproval">'
									.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'video_approval' ) ) . '" method="post" name="gjForm" id="gjForm" class="gjForm">'
									.			( $videoApprovalSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) {
			$return					.=			'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$category			=	$row->getCategory();
				$group				=	$row->getGroup();
				$authorized			=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

				$return				.=				'<div class="gjContentBox gjContentBoxXLarge mini-layout">'
									.					'<div class="gjContentBoxRow">'
									.						$row->getTitle( 0, true );

				if ( $row->getCaption() ) {
					$return			.=						cbgjClass::getIcon( $row->getCaption(), null, 'icon-info-sign', true );
				}

				$return				.=					'</div>'
									.					'<div class="gjContentBoxRow">' . $row->getEmbed( true ) . '</div>'
									.					'<div class="gjContentBoxRow">' . cbFormatDate( $row->get( 'date' ), 1, false ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $category->getName( 0, true ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $group->getName( 0, true ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $row->getOwnerName( true ) . '</div>';

				if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return			.=					'<div class="gjContentBoxRow">'
									.						'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'video_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />'
									.					'</div>';
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) ) {
					$menuItems		=	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'video_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'video_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this photo?' ), true, false, null, true ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

					$return			.=					'<div class="gjContentBoxRow">'
									.						cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) )
									.					'</div>';
				}

				$return				.=				'</div>';
			}

			$return					.=			'</div>';
		} else {
			$return					.=			'<div class="gjContent">';

			if ( $videoApprovalSearch && $pageNav->searching ) {
				$return				.=				CBTxt::Th( 'No video search results found.' );
			} else {
				$return				.=				CBTxt::Th( 'There are no videos pending approval.' );
			}

			$return					.=			'</div>';
		}

		if ( $videoApprovalPaging ) {
			$return					.=			'<div class="gjPaging pagination pagination-centered">'
									.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.				( ! $videoApprovalLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.			'</div>';
		}

		$return						.=			cbGetSpoofInputTag( 'plugin' )
									.		'</form>'
									.	'</div>';

		echo $return;
	}

	public function getVideos( $tabs, $group, $category, $user, $plugin ) {
		$authorized			=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( ! cbgjClass::hasAccess( 'video_show', $authorized ) ) {
			return;
		}

		cbgjClass::getTemplate( 'cbgroupjivevideo' );

		if ( $plugin->params->get( 'general_validate', 1 ) ) {
			cbgjClass::getFormValidation( '#gjForm_video' );
		}

		$paging				=	new cbgjPaging( 'video' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'video_limit', 2 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'url', 'CONTAINS', $search, array( 'title', 'CONTAINS', $search ) );
		}

		$searching			=	( count( $where ) ? true : false );

		if ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
			$where[]		=	array( 'published', '=', 1, array( 'user_id', '=', (int) $user->id ) );
		}

		$where[]			=	array( 'group', '=', (int) $group->get( 'id' ) );

		$total				=	count( cbgjVideoData::getVideos( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjVideoData::getVideos( null, $where, null, ( $plugin->params->get( 'video_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm_videos', 'search', CBTxt::T( 'Search Videos...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjivevideo' ) ) {
			return HTML_cbgroupjivevideo::showVideos( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		} else {
			return $this->showVideos( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		}
	}

	private function showVideos( $rows, $pageNav, $tabs, $group, $category, $user, $plugin ) {
		$videoSearch			=	( $plugin->params->get( 'video_search', 1 ) && ( $pageNav->searching || $pageNav->total ) );
		$videoPaging			=	$plugin->params->get( 'video_paging', 1 );
		$videoLimitbox			=	$plugin->params->get( 'video_limitbox', 1 );
		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user );
		$videoToggle			=	( ( $plugin->params->get( 'group_toggle', 3 ) > 1 ) && cbgjClass::hasAccess( 'video_publish', $authorized ) );

		$params					=	$group->getParams();
		$videoApprove			=	$params->get( 'video_approve', $plugin->params->get( 'video_approve', 0 ) );

		$return					=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Video' ) ), 'gjIntegrationsVideo' )
								.	'<div class="gjVideo">';

		if ( cbgjClass::hasAccess( 'video_publish', $authorized ) ) {
			if ( $plugin->params->get( 'video_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha		=	cbgjCaptcha::render();
			} else {
				$captcha		=	false;
			}

			$input				=	array();

			$input['publish']	=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'video_published', ( $params->get( 'video_approve', $plugin->params->get( 'video_approve', 0 ) ) ? 0 : 1 ) ) );
			$input['title']		=	'<input type="text" size="35" class="input-large" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'video_title' ) ) . '" name="video_title" id="video_title" />';
			$input['url']		=	'<input type="text" size="35" class="input-xlarge required url" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'video_url' ) ) . '" name="video_url" id="video_url" />';
			$input['caption']	=	'<textarea id="video_caption" name="video_caption" class="input-xlarge" cols="30" rows="2">' . htmlspecialchars( cbgjClass::getCleanParam( true, 'video_caption' ) ) . '</textarea>';

			$return				.=		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'video_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '" method="post" enctype="multipart/form-data" name="gjForm_video" id="gjForm_video" class="gjForm gjToggle form-horizontal">';

			if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
				$return			.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['publish']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of video. Unpublished videos will not be visible to the public.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>';
			}

			$return				.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Title' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['title']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Optionally input video title. Titles will link directly to video. Only plain text is supported. No HTML please.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'URL' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['url']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
								.						cbgjClass::getIcon( CBTxt::T( 'Input video URL. Only plain text is supported. No HTML please. Please note not all video providers are supported.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Providers' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					CBTxt::Th( 'youtube, veoh, dailymotion, yahoo, vimeo, break, myspace, blip, viddler, flickr, metacafe, liveleak, gametrailers, hulu, cnn, megavideo, blogtv, expotv, g4tv, revver, spike, mtv, stupidvideos, youku' )
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'List of available video providers. Some providers require usage of the EMBED url as direct URL to video is missing necessary information.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Caption' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['caption']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Optionally input video caption. Only plain text is supported. No HTML please.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>';

			if ( $captcha !== false ) {
				$return			.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Captcha' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					'<div style="margin-bottom: 5px;">' . $captcha['code'] . '</div>'
								.					'<div>' . $captcha['input'] . '</div>'
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
								.					'</span>'
								.				'</div>'
								.			'</div>';
			}

			$return				.=			'<div class="gjButtonWrapper form-actions">'
								.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Publish Video' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
								.				( $videoToggle ? '<a href="#gjVideoToggle" role="button" class="gjButton gjButtonCancel btn btn-mini gjToggleCollapse">' . CBTxt::Th( 'Cancel' ) . '</a>' : null )
								.			'</div>'
								.			cbGetSpoofInputTag( 'plugin' )
								.		'</form>';
		}

		$return					.=		'<form action="' . $group->getUrl() . '" method="post" name="gjForm_videos" id="gjForm_videos" class="gjForm">';

		if ( $videoToggle || $videoSearch ) {
			$return				.=			'<div class="gjTop row-fluid">'
								.				'<div class="gjTop gjTopLeft span6">'
								.					( $videoToggle ? '<a href="#gjForm_video" id="gjVideoToggle" role="button" class="gjButton btn gjToggleExpand">' . CBTxt::Th( 'New Video' ) . '</a>' : null )
								.				'</div>'
								.				'<div class="gjTop gjTopRight span6">'
								.					( $videoSearch ? $pageNav->search : null )
								.				'</div>'
								.			'</div>';
		}

		if ( $rows ) {
			$return				.=			'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$authorized		=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

				if ( $row->get( 'published' ) == 1 ) {
					$state		=	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'video_unpublish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to unpublish this video?' ) ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a></div>';
				} else {
					$state		=	'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'video_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a></div>';
				}

				$canApprove		=	( $videoApprove && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) );

				$return			.=				'<div class="gjContentBox gjContentBoxXLarge mini-layout">'
								.					'<div class="gjContentBoxRow">'
								.						$row->getTitle( 0, true );

				if ( $row->getCaption() ) {
					$return		.=						cbgjClass::getIcon( $row->getCaption(), null, 'icon-info-sign', true );
				}

				if ( ! $row->get( 'published' ) ) {
					$return		.=						cbgjClass::getIcon( null, CBTxt::T( 'This video is currently unpublished.' ), 'icon-eye-close' );
				}

				$return			.=					'</div>'
								.					'<div class="gjContentBoxRow">' . $row->getEmbed( true ) . '</div>'
								.					'<div class="gjContentBoxRow">' . cbFormatDate( $row->get( 'date' ), 1, false ) . '</div>'
								.					'<div class="gjContentBoxRow">' . $row->getOwnerName( true ) . '</div>';

				if ( $canApprove ) {
					$return		.=					'<div class="gjContentBoxRow">'
								.						'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'video_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />'
								.					'</div>';
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) ) {
					$menuItems	=	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'video_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
								.	( ( ! $canApprove ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? $state : null )
								.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'video_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this photo?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

					$return		.=					'<div class="gjContentBoxRow">'
								.						cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) )
								.					'</div>';
				}

				$return			.=				'</div>';
			}

			$return				.=			'</div>';
		} else {
			$return				.=			'<div class="gjContent">';

			if ( $videoSearch && $pageNav->searching ) {
				$return			.=				CBTxt::Th( 'No video search results found.' );
			} else {
				$return			.=				CBTxt::Ph( 'This [group] has no videos.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			}

			$return				.=			'</div>';
		}

		if ( $videoPaging ) {
			$return				.=			'<div class="gjPaging pagination pagination-centered">'
								.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
								.				( ! $videoLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
								.			'</div>';
		}

		$return					.=			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>'
								.	$tabs->endTab();

		return $return;
	}

	private function editVideo( $catid, $grpid, $id, $user, $plugin, $message = null ) {
		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row					=	cbgjVideoData::getVideos( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group				=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) ) {
			$params				=	$group->getParams();

			$row->setPathway( CBTxt::T( 'Edit Video' ) );

			cbgjClass::getTemplate( 'cbgroupjivevideo_edit' );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation();
			}

			$input				=	array();

			$input['publish']	=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'video_published', $row->get( 'published', ( $params->get( 'video_approve', $plugin->params->get( 'video_approve', 0 ) ) ? 0 : 1 ) ) ) );
			$input['title']		=	'<input type="text" size="35" class="input-large" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'video_title', $row->get( 'title' ) ) ) . '" name="video_title" id="video_title" />';
			$input['url']		=	'<input type="text" size="35" class="input-xlarge required url" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'video_url', $row->get( 'url' ) ) ) . '" name="video_url" id="video_url" />';
			$input['caption']	=	'<textarea id="video_caption" name="video_caption" class="input-xlarge" cols="30" rows="2">' . htmlspecialchars( cbgjClass::getCleanParam( true, 'video_caption', $row->get( 'caption' ) ) ) . '</textarea>';

			if ( class_exists( 'HTML_cbgroupjivevideoEdit' ) ) {
				$return			=	HTML_cbgroupjivevideoEdit::showVideoEdit( $row, $input, $group, $category, $user, $plugin );
			} else {
				$return			=	'<div class="gjVideoEdit">'
								.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'video_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
								.			'<legend class="gjEditTitle">' . CBTxt::Th( 'Edit Video' ) . '</legend>';

				if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return		.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['publish']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of video. Unpublished videos will not be visible to the public.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>';
				}

				$return			.=			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Title' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['title']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Optionally input video title. Titles will link directly to video. Only plain text is supported. No HTML please.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'URL' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					'<div style="margin-bottom: 10px;">' . $row->getEmbed( true ) . '</div>'
								.					$input['url']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
								.						cbgjClass::getIcon( CBTxt::T( 'Input video URL. Only plain text is supported. No HTML please. Please note not all video providers are supported.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Providers' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					CBTxt::Th( 'youtube, veoh, dailymotion, yahoo, vimeo, break, myspace, blip, viddler, flickr, metacafe, liveleak, gametrailers, hulu, cnn, megavideo, blogtv, expotv, g4tv, revver, spike, mtv, stupidvideos, youku' )
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'List of available video providers. Some providers require usage of the EMBED url as direct URL to video is missing necessary information.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Caption' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['caption']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Optionally input video caption. Only plain text is supported. No HTML please.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjButtonWrapper form-actions">'
								.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Update Video' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
								.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ), true, false, null, false, false, true ) . '" />'
								.			'</div>'
								.			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>';
			}

			cbgjClass::displayMessage( $message );

			echo $return;
		} else {
			if ( $group->get( 'id' ) ) {
				$url			=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url			=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url			=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function saveVideo( $catid, $grpid, $id, $user, $plugin ) {
		$category							=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group								=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row								=	cbgjVideoData::getVideos( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group							=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category						=	$group->getCategory();
		}

		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || cbgjClass::hasAccess( 'video_publish', $authorized ) ) {
			$params							=	$group->getParams();
			$videoApprove					=	$params->get( 'video_approve', $plugin->params->get( 'video_approve', 0 ) );

			$row->set( 'published', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'published', $row->get( 'published', ( $videoApprove  ? -1 : 1 ) ) ) );
			$row->set( 'user_id', (int) $row->get( 'user_id', $user->id ) );
			$row->set( 'group', (int) $row->get( 'group', $group->get( 'id' ) ) );
			$row->set( 'title', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'video_title', $row->get( 'title' ) ) ) );
			$row->set( 'url', cbgjClass::getCleanParam( true, 'video_url', $row->get( 'url' ) ) );
			$row->set( 'caption', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'video_caption', $row->get( 'caption' ) ) ) );
			$row->set( 'date', ( $row->get( 'date' ) ? $row->get( 'date' ) : cbgjClass::getUTCDate() ) );

			$video							=	$row->getEmbed();

			if ( $row->get( 'url' ) == '' ) {
				$row->set( '_error', CBTxt::T( 'URL not specified!' ) );
			} elseif ( ! $row->get( 'user_id' ) ) {
				$row->set( '_error', CBTxt::P( '[user] not specified!', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );
			} elseif ( ! $row->get( 'group' ) ) {
				$row->set( '_error', CBTxt::P( '[group] not specified!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) );
			} elseif ( ! $video ) {
				$row->set( '_error', CBTxt::T( 'Provider not supported!' ) );
			} elseif ( $plugin->params->get( 'video_captcha', 0 ) && ( ! $row->get( 'id' ) ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha					=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$row->set( '_error', CBTxt::T( $captcha ) );
				}
			}

			$new							=	( $row->get( 'id' ) ? false : true );

			if ( $row->getError() || ( ! $row->store() ) ) {
				if ( ! $new ) {
					$this->editVideo( $category->get( 'id' ), $group->get( 'id' ), $row->get( 'id' ), $user, $plugin, $row, CBTxt::P( 'Video failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) );
				} else {
					CBplug_cbgroupjive::showGroup( $category->get( 'id' ), $group->get( 'id' ), $user, $plugin, CBTxt::P( 'Video failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), '#gjForm_video' );
				}
				return;
			}

			if ( $new ) {
				if ( $row->get( 'published' ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'video_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_videonew=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - Video Published!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] published [video_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				} elseif ( $videoApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'video_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_videoapprove=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - Video Published Requires Approval!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] published [video_title_linked] in [group] and requires approval!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				}
			} elseif ( $row->get( 'published' ) ) {
				$notifications				=	cbgjData::getNotifications( array( array( 'video_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_videoupdate=1' ) ) );

				if ( $notifications ) {
					$subject				=	CBTxt::P( '[group_name] - Video Edited!', $row->getSubstitutionExtras( true ) );
					$message				=	CBTxt::P( '[user] edited [video_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}
			}

			if ( $videoApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
				$successMsg					=	CBTxt::T( 'Video saved successfully and awaiting approval!' );
			} else {
				$successMsg					=	CBTxt::T( 'Video saved successfully!' );
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), $successMsg, false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url						=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url						=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url						=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function stateVideo( $catid, $grpid, $id, $state, $user, $plugin ) {
		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row						=	cbgjVideoData::getVideos( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group					=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category				=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$currentState			=	$row->get( 'published' );

			if ( ! $row->storeState( $state ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Video state failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $state && ( $currentState == -1 ) ) {
				$notifications		=	cbgjData::getNotifications( array( array( 'video_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_videonew=1' ) ) );

				if ( $notifications ) {
					$subject		=	CBTxt::P( '[group_name] - Video Published!', $row->getSubstitutionExtras( true ) );
					$message		=	CBTxt::P( '[user] published [video_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}

				if ( $user->id != $row->get( 'user_id' ) ) {
					$subject		=	CBTxt::T( '[group_name] - Video Publish Request Accepted!' );
					$message		=	CBTxt::T( 'Your request to publish [video_title_linked] in [group] has been accepted!' );

					cbgjClass::getNotification( $row->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Video state saved successfully!' ), false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url				=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url				=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url				=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function deleteVideo( $catid, $grpid, $id, $user, $plugin ) {
		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row					=	cbgjVideoData::getVideos( array( array( 'mod_lvl4', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group				=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$published			=	$row->get( 'published' );

			if ( $published ) {
				$notifications	=	cbgjData::getNotifications( array( array( 'video_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_videodelete=1' ) ) );
			} else {
				$notifications	=	null;
			}

			if ( ! $row->delete() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Video failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $notifications ) {
				$subject		=	CBTxt::P( '[group_name] - Video Deleted!', $row->getSubstitutionExtras( true ) );
				$message		=	CBTxt::P( '[user] deleted [video_title] in [group]!', $row->getSubstitutionExtras( true ) );

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Video deleted successfully!' ), false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url			=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url			=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url			=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	public function deleteVideos( $group, $category, $user, $plugin ) {
		$rows	=	cbgjVideoData::getVideos( null, array( 'group', '=', (int) $group->get( 'id' ) ) );

		if ( $rows ) foreach ( $rows as $row ) {
			$row->delete();
		}
	}

	public function getParam( $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user );
		$params						=	$group->getParams();
		$videoShow					=	$plugin->params->get( 'video_show_config', 1 );
		$videoPublic				=	$plugin->params->get( 'video_public_config', 1 );
		$videoPublish				=	$plugin->params->get( 'video_publish_config', 1 );
		$videoApprove				=	$plugin->params->get( 'video_approve_config', 1 );

		$input						=	array();

		$input['video_show']		=	moscomprofilerHTML::yesnoSelectList( 'video_show', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $videoShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'video_show', $params->get( 'video_show', $plugin->params->get( 'video_show', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_public']		=	moscomprofilerHTML::yesnoSelectList( 'video_public', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $videoPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'video_public', $params->get( 'video_public', $plugin->params->get( 'video_public', 1 ) ) ) );

		$listPublish				=	array();
		$listPublish[]				=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listPublish[]				=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listPublish[]				=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listPublish[]				=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['video_publish']		=	moscomprofilerHTML::selectList( $listPublish, 'video_publish', 'class="input-large"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $videoPublish || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'video_publish', $params->get( 'video_publish', $plugin->params->get( 'video_publish', 1 ) ) ), 1, false, false );

		$input['video_approve']		=	moscomprofilerHTML::yesnoSelectList( 'video_approve', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $videoApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'video_approve', $params->get( 'video_approve', $plugin->params->get( 'video_approve', 0 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		if ( $_CB_framework->getUi() == 2 ) {
			$return					=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Video' ) ), 'gjIntegrationsVideo' )
									.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
									.			'<tbody>'
									.				'<tr>'
									.					'<th width="15%">' . CBTxt::Th( 'Display' ) . '</div>'
									.					'<td width="40%">' . $input['video_show'] . '</div>'
									.					'<td>' . CBTxt::Th( 'Select usage of group videos.' ) . '</div>'
									.				'</tr>'
									.				'<tr>'
									.					'<th width="15%">' . CBTxt::Th( 'Public' ) . '</div>'
									.					'<td width="40%">' . $input['video_public'] . '</div>'
									.					'<td>' . CBTxt::Th( 'Select if group video tab is publicly visible.' ) . '</div>'
									.				'</tr>'
									.				'<tr>'
									.					'<th width="15%">' . CBTxt::Th( 'Publish' ) . '</div>'
									.					'<td width="40%">' . $input['video_publish'] . '</div>'
									.					'<td>' . CBTxt::Th( 'Select group publish access. Publish access determines what type of users can publish videos to your group (e.g. Users signify only those a member of your group can publish). The users above the selected will also have access.' ) . '</div>'
									.				'</tr>'
									.				'<tr>'
									.					'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</div>'
									.					'<td width="40%">' . $input['video_approve'] . '</div>'
									.					'<td>' . CBTxt::Th( 'Enable or disable approval of newly published group videos. Videos will require approval by a group moderator, admin, or owner to be published. Group moderators, admins, and owner are exempt from this configuration.' ) . '</div>'
									.				'</tr>'
									.			'</tbody>'
									.		'</table>'
									.	$tabs->endTab();
		} else {
			if ( ( ! $videoShow ) && ( ! $videoPublic ) && ( ! $videoPublish ) && ( ! $videoApprove ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				return;
			}

			cbgjClass::getTemplate( 'cbgroupjivevideo_params' );

			if ( class_exists( 'HTML_cbgroupjivevideoParams' ) ) {
				$return				=	HTML_cbgroupjivevideoParams::showVideoParams( $input, $group, $category, $user, $plugin );
			} else {
				$return				=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Video' ) ), 'gjIntegrationsVideo' );

				if ( $videoShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return			.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Display' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$input['video_show']
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::P( 'Select usage of [group] videos.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
				}

				if ( $videoPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return			.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Public' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$input['video_public']
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::P( 'Select if [group] video tab is publicly visible.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
				}

				if ( $videoPublish || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return			.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Publish' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$input['video_publish']
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::P( 'Select [group] publish access. Publish access determines what type of [users] can publish videos to your [group] (e.g. [users] signify only those a member of your [group] can publish). The [users] above the selected will also have access.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
				}

				if ( $videoApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return			.=		'<div class="gjEditContentInput control-group">'
									.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Approve' ) . '</label>'
									.			'<div class="gjEditContentInputField controls">'
									.				$input['video_approve']
									.				'<span class="gjEditContentInputIcon help-inline">'
									.					cbgjClass::getIcon( CBTxt::P( 'Enable or disable approval of newly published [group] videos. Videos will require approval by a [group] [mod], [admin], or [owner] to be published. [group] [mods], [admins], and [owner] are exempt from this configuration.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[mods]' => cbgjClass::getOverride( 'mod', true ), '[admins]' => cbgjClass::getOverride( 'admin', true ), '[mod]' => cbgjClass::getOverride( 'mod' ), '[admin]' => cbgjClass::getOverride( 'admin' ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
									.				'</span>'
									.			'</div>'
									.		'</div>';
				}

				$return				.=	$tabs->endTab();
			}
		}

		return $return;
	}

	public function setParam( $group, $category, $user, $plugin ) {
		$authorized	=	cbgjClass::getAuthorization( $category, $group, $user );
		$params		=	$group->getParams();

		$params->set( 'video_show', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'video_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'video_show', $params->get( 'video_show', $plugin->params->get( 'video_show', 1 ) ) ) );
		$params->set( 'video_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'video_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'video_public', $params->get( 'video_public', $plugin->params->get( 'video_public', 1 ) ) ) );
		$params->set( 'video_publish', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'video_publish_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'video_publish', $params->get( 'video_publish', $plugin->params->get( 'video_publish', 1 ) ) ) );
		$params->set( 'video_approve', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'video_approve_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'video_approve', $params->get( 'video_approve', $plugin->params->get( 'video_approve', 1 ) ) ) );

		$group->storeParams( $params );
	}

	public function getConfig( $tabs, $user, $plugin ) {
		$input												=	array();

		$input['video_delete']								=	moscomprofilerHTML::yesnoSelectList( 'video_delete', null, $plugin->params->get( 'video_delete', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_notifications']						=	moscomprofilerHTML::yesnoSelectList( 'video_notifications', null, $plugin->params->get( 'video_notifications', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_notifications_group_videonew']		=	moscomprofilerHTML::yesnoSelectList( 'video_notifications_group_videonew', null, $plugin->params->get( 'video_notifications_group_videonew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['video_notifications_group_videoapprove']	=	moscomprofilerHTML::yesnoSelectList( 'video_notifications_group_videoapprove', null, $plugin->params->get( 'video_notifications_group_videoapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['video_notifications_group_videoupdate']		=	moscomprofilerHTML::yesnoSelectList( 'video_notifications_group_videoupdate', null, $plugin->params->get( 'video_notifications_group_videoupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['video_notifications_group_videodelete']		=	moscomprofilerHTML::yesnoSelectList( 'video_notifications_group_videodelete', null, $plugin->params->get( 'video_notifications_group_videodelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['video_unknown']								=	moscomprofilerHTML::yesnoSelectList( 'video_unknown', null, $plugin->params->get( 'video_unknown', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_width']								=	'<input type="text" id="video_width" name="video_width" value="' . htmlspecialchars( $plugin->params->get( 'video_width', 350 ) ) . '" class="inputbox" size="7" />';
		$input['video_height']								=	'<input type="text" id="video_height" name="video_height" value="' . htmlspecialchars( $plugin->params->get( 'video_height', 250 ) ) . '" class="inputbox" size="7" />';
		$input['video_thumbwidth']							=	'<input type="text" id="video_thumbwidth" name="video_thumbwidth" value="' . htmlspecialchars( $plugin->params->get( 'video_thumbwidth', 150 ) ) . '" class="inputbox" size="7" />';
		$input['video_thumbheight']							=	'<input type="text" id="video_thumbheight" name="video_thumbheight" value="' . htmlspecialchars( $plugin->params->get( 'video_thumbheight', 150 ) ) . '" class="inputbox" size="7" />';
		$input['video_captcha']								=	moscomprofilerHTML::yesnoSelectList( 'video_captcha', null, $plugin->params->get( 'video_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_paging']								=	moscomprofilerHTML::yesnoSelectList( 'video_paging', null, $plugin->params->get( 'video_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_limitbox']							=	moscomprofilerHTML::yesnoSelectList( 'video_limitbox', null, $plugin->params->get( 'video_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_limit']								=	'<input type="text" id="video_limit" name="video_limit" value="' . (int) $plugin->params->get( 'video_limit', 2 ) . '" class="inputbox" size="5" />';
		$input['video_search']								=	moscomprofilerHTML::yesnoSelectList( 'video_search', null, $plugin->params->get( 'video_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_approval_paging']						=	moscomprofilerHTML::yesnoSelectList( 'video_approval_paging', null, $plugin->params->get( 'video_approval_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_approval_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'video_approval_limitbox', null, $plugin->params->get( 'video_approval_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_approval_limit']						=	'<input type="text" id="video_approval_limit" name="video_approval_limit" value="' . (int) $plugin->params->get( 'video_approval_limit', 5 ) . '" class="inputbox" size="5" />';
		$input['video_approval_search']						=	moscomprofilerHTML::yesnoSelectList( 'video_approval_search', null, $plugin->params->get( 'video_approval_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_show']								=	moscomprofilerHTML::yesnoSelectList( 'video_show', null, $plugin->params->get( 'video_show', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_show_config']							=	moscomprofilerHTML::yesnoSelectList( 'video_show_config', null, $plugin->params->get( 'video_show_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['video_public']								=	moscomprofilerHTML::yesnoSelectList( 'video_public', null, $plugin->params->get( 'video_public', 1 ) );
		$input['video_public_config']						=	moscomprofilerHTML::yesnoSelectList( 'video_public_config', null, $plugin->params->get( 'video_public_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$listPublish										=	array();
		$listPublish[]										=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listPublish[]										=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listPublish[]										=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listPublish[]										=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['video_publish']								=	moscomprofilerHTML::selectList( $listPublish, 'video_publish', 'class="inputbox"', 'value', 'text', $plugin->params->get( 'video_publish', 1 ), 1, false, false );

		$input['video_publish_config']						=	moscomprofilerHTML::yesnoSelectList( 'video_publish_config', null, $plugin->params->get( 'video_publish_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['video_approve']								=	moscomprofilerHTML::yesnoSelectList( 'video_approve', null, $plugin->params->get( 'video_approve', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['video_approve_config']						=	moscomprofilerHTML::yesnoSelectList( 'video_approve_config', null, $plugin->params->get( 'video_approve_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$return												=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Video' ) ), 'gjIntegrationsVideo' )
															.		$tabs->startPane( 'gjIntegrationsVideoTabs' )
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsVideoGeneral' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Auto Delete' ) . '</th>'
															.							'<td width="40%">' . $input['video_delete'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable deletion of user videos on group leave.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Notifications' ) ), 'gjIntegrationsVideoNotifications' )
															.				$tabs->startPane( 'gjIntegrationsVideoNotificationsTabs' )
															.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsVideoNotificationsGeneral' )
															.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.							'<tbody>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Notifications' ) . '</th>'
															.									'<td width="40%">' . $input['video_notifications'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Enable or disable sending and configuration of video notifications. Moderators are exempt from this configuration.' ) . '</td>'
															.								'</tr>'
															.							'</tbody>'
															.						'</table>'
															.					$tabs->endTab()
															.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsVideoNotificationsDefaults' )
															.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.							'<tbody>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Publish Video' ) . '</th>'
															.									'<td width="40%">' . $input['video_notifications_group_videonew'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for video notification parameter "Publish of new video".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Video Approval' ) . '</th>'
															.									'<td width="40%">' . $input['video_notifications_group_videoapprove'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for video notification parameter "New video requires approval".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Update Video' ) . '</th>'
															.									'<td width="40%">' . $input['video_notifications_group_videoupdate'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for video notification parameter "Update of existing video".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Delete Video' ) . '</th>'
															.									'<td width="40%">' . $input['video_notifications_group_videoupdate'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for video notification parameter "Delete of existing video".' ) . '</td>'
															.								'</tr>'
															.							'</tbody>'
															.						'</table>'
															.					$tabs->endTab()
															.				$tabs->endPane()
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Publish' ) ), 'gjIntegrationsVideoPublish' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Uknown Providers' ) . '</th>'
															.							'<td width="40%">' . $input['video_unknown'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable unknown provider support. If enabled allows users to supply URLs to non-supported providers and will attempt to construct an object embed.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Width' ) . '</th>'
															.							'<td width="600px">' . $input['video_width'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input width of videos embed.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Height' ) . '</th>'
															.							'<td width="600px">' . $input['video_height'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input height of videos embed.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Thumb Width' ) . '</th>'
															.							'<td width="600px">' . $input['video_thumbwidth'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input thumbnail width of videos embed.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Thumb Height' ) . '</th>'
															.							'<td width="600px">' . $input['video_thumbheight'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input thumbnail height of videos embed.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
															.							'<td width="40%">' . $input['video_captcha'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of Captcha on Video tab. Requires latest CB Captcha or integrated Captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjIntegrationsVideoPaging' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
															.							'<td width="40%">' . $input['video_paging'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on videos.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
															.							'<td width="50%">' . $input['video_limitbox'] . '</td>'
															.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on videos. Requires Paging to be Enabled.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
															.							'<td width="50%">' . $input['video_limit'] . '</td>'
															.							'<td>' . CBTxt::T( 'Input default page limit on videos. Page limit determines how many videos are displayed per page.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
															.							'<td width="600px">' . $input['video_search'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on videos.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Approval' ) ), 'gjIntegrationsVideoApproval' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
															.							'<td width="40%">' . $input['video_approval_paging'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on videos.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
															.							'<td width="50%">' . $input['video_approval_limitbox'] . '</td>'
															.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on videos. Requires Paging to be Enabled.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
															.							'<td width="50%">' . $input['video_approval_limit'] . '</td>'
															.							'<td>' . CBTxt::T( 'Input default page limit on videos. Page limit determines how many videos are displayed per page.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
															.							'<td width="600px">' . $input['video_approval_search'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on videos.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsVideoDefaults' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
															.							'<td width="40%">' . $input['video_show'] . ' ' . $input['video_show_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Display". Additionally select the display of the "Display" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Public' ) . '</th>'
															.							'<td width="40%">' . $input['video_public'] . ' ' . $input['video_public_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Public". Additionally select the display of the "Public" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Publish' ) . '</th>'
															.							'<td width="600px">' . $input['video_publish'] . ' ' . $input['video_publish_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Publish". Additionally select the display of the "Publish" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</th>'
															.							'<td width="600px">' . $input['video_approve'] . ' ' . $input['video_approve_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Approve". Additionally select the display of the "Approve" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.		$tabs->endPane()
															.	$tabs->endTab();

		return $return;
	}

	public function leaveGroup( $row, $group, $category, $user, $plugin ) {
		$this->deleteUserVideos( $user, $group );
	}

	public function deleteUser( $user, $deleted ) {
		$this->deleteUserVideos( $user );
	}

	private function deleteUserVideos( $user, $group = null ) {
		$plugin				=	cbgjClass::getPlugin();

		if ( $plugin->params->get( 'video_delete', 0 ) ) {
			$where			=	array();

			if ( $group ) {
				$where[]	=	array( 'group', '=', (int) $group->get( 'id' ) );
			}

			$where[]		=	array( 'user_id', '=', (int) $user->id );

			$rows			=	cbgjVideoData::getVideos( null, $where );

			if ( $rows ) foreach ( $rows as $row ) {
				$row->delete();
			}
		}
	}

	public function getAuthorization( &$access, $category, $group, $user, $owner, $row, $plugin ) {
		if ( isset( $group->id ) && cbgjClass::hasAccess( 'grp_approved', $access ) ) {
			$params					=	$group->getParams();
			$videoShow				=	$params->get( 'video_show', $plugin->params->get( 'video_show', 1 ) );
			$videoPublic			=	$params->get( 'video_public', $plugin->params->get( 'video_public', 1 ) );
			$videoPublish			=	$params->get( 'video_publish', $plugin->params->get( 'video_publish', 1 ) );

			if ( ( $videoPublic || cbgjClass::hasAccess( 'mod_lvl5', $access ) ) && $videoShow ) {
				$access[]			=	'video_show';

				if ( cbgjClass::hasAccess( 'usr_notifications', $access ) && ( $plugin->params->get( 'video_notifications', 1 ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) {
					if ( ! cbgjClass::hasAccess( 'grp_usr_notifications', $access ) ) {
						$access[]	=	'grp_usr_notifications';
					}

					$access[]		=	'video_notifications';
				}
			}

			if ( $videoShow && ( ( ( $videoPublish == 0 ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) || ( ( $videoPublish == 1 ) && cbgjClass::hasAccess( 'mod_lvl4', $access ) ) || ( $videoPublish == 2 ) && cbgjClass::hasAccess( 'mod_lvl3', $access ) || ( $videoPublish == 3 ) && cbgjClass::hasAccess( 'mod_lvl2', $access ) ) ) {
				$access[]			=	'video_publish';
			}
		}
	}

	public function getNotifications( $tabs, $row, $group, $category, $user, $plugin ) {
		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'video_notifications', $authorized ) ) {
			$params							=	$row->getParams();

			$input							=	array();

			$input['group_videonew']		=	moscomprofilerHTML::yesnoSelectList( 'group_videonew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'video_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_videonew', $params->get( 'group_videonew', $plugin->params->get( 'video_notifications_group_videonew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_videoapprove']	=	moscomprofilerHTML::yesnoSelectList( 'group_videoapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'video_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_videoapprove', $params->get( 'group_videoapprove', $plugin->params->get( 'video_notifications_group_videoapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_videoupdate']		=	moscomprofilerHTML::yesnoSelectList( 'group_videoupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'video_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_videoupdate', $params->get( 'group_videoupdate', $plugin->params->get( 'video_notifications_group_videoupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_videodelete']		=	moscomprofilerHTML::yesnoSelectList( 'group_videodelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'video_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_videodelete', $params->get( 'group_videodelete', $plugin->params->get( 'video_notifications_group_videodelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

			$return							=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Video' ) ), 'gjNotificationsGroupVideo' )
											.		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Publish of new video' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_videonew']
											.			'</div>'
											.		'</div>';

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'video_show' ), $authorized, true ) ) {
				$return						.=		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'New video requires approval' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_videoapprove']
											.			'</div>'
											.		'</div>';
			}

			$return							.=		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Update of existing video' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_videoupdate']
											.			'</div>'
											.		'</div>'
											.		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Delete of existing video' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_videodelete']
											.			'</div>'
											.		'</div>'
											.	$tabs->endTab();

			return $return;
		}
	}

	public function setNotifications( &$params, $row, $group, $category, $user, $plugin ) {
		if ( isset( $group->id ) ) {
			$authorized		=	cbgjClass::getAuthorization( $category, $group, $row->getOwner() );

			if ( cbgjClass::hasAccess( 'video_notifications', $authorized ) ) {
				$rowParams	=	$row->getParams();

				$params->set( 'group_videonew', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'video_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_videonew', $rowParams->get( 'group_videonew', $plugin->params->get( 'video_notifications_group_videonew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_videoapprove', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'video_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_videoapprove', $rowParams->get( 'group_videoapprove', $plugin->params->get( 'video_notifications_group_videoapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_videoupdate', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'video_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_videoupdate', $rowParams->get( 'group_videoupdate', $plugin->params->get( 'video_notifications_group_videoupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_videodelete', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'video_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_videodelete', $rowParams->get( 'group_videodelete', $plugin->params->get( 'video_notifications_group_videodelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );
			}
		}
	}

	public function getMenu( $user, $plugin ) {
		$input					=	array();

		$input['approve_video']	=	'<input type="checkbox" id="type_approve_file" name="type[]" class="inputbox" value="approve-video" />';

		$return					=	'<tr>'
								.		'<td width="10%" style="text-align:center;">' . $input['approve_video'] . '</td>'
								.		'<th width="20%">' . CBTxt::Th( 'Video Approval' ) . '</td>'
								.		'<td>' . CBTxt::Th( 'Create menu link to a video approval page.' ) . '</td>'
								.	'</tr>';

		return $return;
	}

	public function saveMenu( $type, $categories, $groups, $user, $plugin ) {
		if ( $type == 'approve-video' ) {
			if ( ! cbgjClass::setMenu( CBTxt::T( 'Video Approval' ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=plugin&func=video_approval', $plugin ) ) {
				cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Video approval menu failed to create!' ), false, true, 'error' );
			}
		}
	}
}

class cbgjVideo extends comprofilerDBTable {
	var $id			=	null;
	var $published	=	null;
	var $user_id	=	null;
	var $group		=	null;
	var $title		=	null;
	var $url		=	null;
	var $caption	=	null;
	var $date		=	null;

	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_plugin_video', 'id', $db );
	}

	public function get( $var, $def = null ) {
		if ( isset( $this->$var ) ) {
			return $this->$var;
		} else {
			return $def;
		}
	}

	public function store( $updateNulls = false ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );
		$new	=	( $this->get( 'id' ) ? false : true );

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onBeforeUpdateVideo', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gjint_onBeforeCreateVideo', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onAfterUpdateVideo', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );

			$_PLUGINS->trigger( 'gjint_onAfterCreateVideo', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		return true;
	}

	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeDeleteVideo', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterDeleteVideo', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function storeState( $state ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateVideoState', array( &$state, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'published', (int) $state );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdateVideoState', array( $this->get( 'published' ), $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function setPathway( $title = null, $url = null ) {
		global $_CB_framework;

		$this->getGroup()->setPathway( false );

		if ( $title !== false ) {
			if ( ! $title ) {
				$title	=	$this->getTitle();
			}

			if ( $title ) {
				$_CB_framework->setPageTitle( htmlspecialchars( $title ) );
			}
		} else {
			$title		=	$this->getTitle();
		}

		if ( ! $url ) {
			$url		=	$this->getUrl();
		}

		if ( $title ) {
			$_CB_framework->appendPathWay( htmlspecialchars( $title ), $url );
		}
	}

	public function getEmbed( $html = true, $thumb = false, $width = null, $height = null ) {
		$urlDomain					=	preg_replace( '/^(?:(?:\w+\.)*)?(\w+)\..+$/', '\1', parse_url( $this->get( 'url' ), PHP_URL_HOST ) );
		$embed						=	null;

		if ( $urlDomain ) {
			$plugin					=	cbgjClass::getPlugin();
			$urlScheme				=	parse_url( $this->get( 'url' ), PHP_URL_SCHEME );

			if ( ! $urlScheme ) {
				$urlScheme			=	( ( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && ( $_SERVER['HTTPS'] != 'off' ) ) ? 'https' : 'http' );
			}

			$canHttps				=	false;

			if ( ( $plugin->scheme == 'https' ) && ( $urlScheme != $plugin->scheme  ) ) {
				$urlScheme			=	'https';
				$forcedHttps		=	true;
			} else {
				$forcedHttps		=	false;
			}

			$width					=	(int) $width;

			if ( ! $width ) {
				if ( $thumb ) {
					$width			=	(int) $plugin->params->get( 'video_thumbwidth', 150 );
				} else {
					$width			=	(int) $plugin->params->get( 'video_width', 350 );
				}
			}

			$height					=	(int) $height;

			if ( ! $height ) {
				if ( $thumb ) {
					$height			=	(int) $plugin->params->get( 'video_thumbheight', 150 );
				} else {
					$height			=	(int) $plugin->params->get( 'video_height', 250 );
				}
			}

			if ( ( ! $width ) || ( $width <= 0 ) ) {
				$width				=	400;
			}

			if ( ( ! $height ) || ( $height <= 0 ) ) {
				$height				=	300;
			}

			$matches				=	null;

			switch ( $urlDomain ) {
				case 'youtube':
				case 'youtu':
					if ( preg_match( '%^.*(?:v=|v/|/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://www.youtube.com/v/' . $matches[1] . '&amp;fs=1&amp;rel=0" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="' . $urlScheme . '://www.youtube.com/v/' . $matches[1] . '&amp;fs=1&amp;rel=0" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'veoh':
					if ( preg_match( '#^.*(?:watch%3D|watch/)([\w-]+).*#', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://www.veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.5.3.1004&amp;permalinkId=' . $matches[1] . '&amp;player=videodetailsembedded&amp;videoAutoPlay=0&amp;id=anonymous" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="' . $urlScheme . '://www.veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.5.3.1004&amp;permalinkId=' . $matches[1] . '&amp;player=videodetailsembedded&amp;videoAutoPlay=0&amp;id=anonymous" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'dailymotion':
					if ( preg_match( '%^.*video/([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.dailymotion.com/swf/video/' . $matches[1] . '?additionalInfos=0" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.dailymotion.com/swf/video/' . $matches[1] . '?additionalInfos=0" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'yahoo':
					if ( preg_match( '%^.*watch/([\w-]+)/([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://d.yimg.com/static.video.yahoo.com/yep/YV_YEP.swf?ver=2.2.46" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="id=' . $matches[2] . 'vid=' . $matches[1] . '&amp;embed=1" />'
									.		'<embed src="http://d.yimg.com/static.video.yahoo.com/yep/YV_YEP.swf?ver=2.2.46" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="id=' . $matches[2] . 'vid=' . $matches[1] . '&amp;embed=1"></embed>'
									.	'</object>';
					}
					break;
				case 'vimeo':
					if ( preg_match( '%^.*(?:clip_id=|/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=' . $matches[1] . '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;fullscreen=1&amp;autoplay=0&amp;loop=0" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://vimeo.com/moogaloop.swf?clip_id=' . $matches[1] . '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;fullscreen=1&amp;autoplay=0&amp;loop=0" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'break':
					if ( preg_match( '%^.*/([\w-=]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">'
									.		'<param name="movie" value="http://embed.break.com/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://embed.break.com/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'myspace':
					if ( preg_match( '/^.*(?:videoid=|m=)([\w-]+).*/', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://mediaservices.myspace.com/services/media/embed.aspx/m=' . $matches[1] . ',t=1,mt=video" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="wmode" value="transparent" />'
									.		'<embed src="http://mediaservices.myspace.com/services/media/embed.aspx/m=' . $matches[1] . ',t=1,mt=video" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" wmode="transparent"></embed>'
									.	'</object>';
					}
					break;
				case 'blip':
					if ( preg_match( '%^.*play/([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://blip.tv/play/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="' . $urlScheme . '://blip.tv/play/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'viddler':
					if ( preg_match( '%^.*player/([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://www.viddler.com/player/' . $matches[1] . '/" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="fake=1" />'
									.		'<embed src="' . $urlScheme . '://www.viddler.com/player/' . $matches[1] . '/" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="fake=1"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'flickr':
					if ( preg_match( '%^.*(?:photo_id=|photos/[\w-]+/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.flickr.com/apps/video/stewart.swf?photo_id=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.flickr.com/apps/video/stewart.swf?photo_id=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'metacafe':
					if ( preg_match( '%^.*(?:watch/|fplayer/)([\w-]+)/([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.metacafe.com/fplayer/' . $matches[1] . '/' . $matches[2] . '.swf" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="playerVars=showStats=yes|autoPlay=no" />'
									.		'<embed src="http://www.metacafe.com/fplayer/' . $matches[1] . '/' . $matches[2] . '.swf" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="playerVars=showStats=yes|autoPlay=no"></embed>'
									.	'</object>';
					}
					break;
				case 'liveleak':
					if ( preg_match( '%^.*(?:i=|e/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.liveleak.com/e/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="wmode" value="transparent" />'
									.		'<embed src="http://www.liveleak.com/e/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" wmode="transparent"></embed>'
									.	'</object>';
					}
					break;
				case 'gametrailers':
					if ( preg_match( '%^.*(?:mid=|video/[\w-]+/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.gametrailers.com/remote_wrap.php?mid=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="quality" value="high" />'
									.		'<embed src="http://www.gametrailers.com/remote_wrap.php?mid=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" quality="high"></embed>'
									.	'</object>';
					}
					break;
				case 'hulu':
					if ( preg_match( '%^.*embed/([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.hulu.com/embed/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.hulu.com/embed/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'cnn':
					if ( preg_match( '%^.*(?:video/|videoId=)([\w-/.]+)%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://i.cdn.turner.com/cnn/.element/apps/cvp/3.0/swf/cnn_416x234_embed.swf?context=embed&amp;videoId=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="wmode" value="transparent" />'
									.		'<embed src="http://i.cdn.turner.com/cnn/.element/apps/cvp/3.0/swf/cnn_416x234_embed.swf?context=embed&amp;videoId=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" wmode="transparent"></embed>'
									.	'</object>';
					}
					break;
				case 'megavideo':
					if ( preg_match( '%^.*v/([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.megavideo.com/v/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.megavideo.com/v/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'blogtv':
					if ( preg_match( '%^.*(?:/|vb/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.blogtv.com/vb/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.blogtv.com/vb/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'expotv':
					if ( preg_match( '%^.*(?:/|embed/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://www.expotv.com/video/embed/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="wmode" value="transparent" />'
									.		'<embed src="' . $urlScheme . '://www.expotv.com/video/embed/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" wmode="transparent"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'g4tv':
					if ( preg_match( '%^.*(?:videos/|lv3/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">'
									.		'<param name="movie" value="http://www.g4tv.com/lv3/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.g4tv.com/lv3/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'revver':
					if ( preg_match( '%^.*(?:video/|mediaId=)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://flash.revver.com/player/1.0/player.swf?mediaId=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="allowFullScreen=true" />'
									.		'<embed src="http://flash.revver.com/player/1.0/player.swf?mediaId=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="allowFullScreen=true"></embed>'
									.	'</object>';
					}
					break;
				case 'spike':
					if ( preg_match( '%^.*(?:spike\.com:|video/[\w-]+/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://media.mtvnservices.com/mgid:ifilm:video:spike.com:' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="quality" value="high" />'
									.		'<param name="flashvars" value="autoPlay=false" />'
									.		'<embed src="http://media.mtvnservices.com/mgid:ifilm:video:spike.com:' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" quality="high" flashvars="autoPlay=false"></embed>'
									.	'</object>';
					}
					break;
				case 'mtv':
					if ( preg_match( '%^.*(?:mtv\.com:|videos/[\w-]+/)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						if ( preg_match( '%^.*(?:videolist:|/playlist).*%', $this->get( 'url' ) ) ) {
							$embed	=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://media.mtvnservices.com/mgid:uma:videolist:mtv.com:' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="configParams=id=' . $matches[1] . '&amp;uri=mgid:uma:videolist:mtv.com:' . $matches[1] . '" />'
									.		'<embed src="http://media.mtvnservices.com/mgid:uma:videolist:mtv.com:' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="configParams=id=' . $matches[1] . '&amp;uri=mgid:uma:videolist:mtv.com:' . $matches[1] . '"></embed>'
									.	'</object>';
						} else {
							$embed	=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://media.mtvnservices.com/mgid:uma:video:mtv.com:' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="configParams=vid=' . $matches[1] . '&amp;uri=mgid:uma:video:mtv.com:' . $matches[1] . '" />'
									.		'<embed src="http://media.mtvnservices.com/mgid:uma:video:mtv.com:' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="configParams=vid=' . $matches[1] . '&amp;uri=mgid:uma:video:mtv.com:' . $matches[1] . '"></embed>'
									.	'</object>';
						}
					}
					break;
				case 'stupidvideos':
					if ( preg_match( '/^.*(?:#|i=)([\w-]+).*/', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://images.stupidvideos.com/2.0.2/swf/video.swf?sa=1&amp;sk=7&amp;si=2&amp;i=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://images.stupidvideos.com/2.0.2/swf/video.swf?sa=1&amp;sk=7&amp;si=2&amp;i=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'youku':
					if ( preg_match( '%^.*(?:sid/|id_)([\w-]+).*%', $this->get( 'url' ), $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://player.youku.com/player.php/sid/' . $matches[1] . '/v.swf" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://player.youku.com/player.php/sid/' . $matches[1] . '/v.swf" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				default:
					if ( $plugin->params->get( 'video_unknown', 0 ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . htmlspecialchars( $this->get( 'url' ) ) . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="' . htmlspecialchars( $this->get( 'url' ) ) . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
			}

			if ( ( ! $html ) && $matches ) {
				$embed				=	( isset( $matches[1] ) ? $matches[1] : null );
			} elseif ( $embed && $forcedHttps && ( ! $canHttps ) ) {
				$embed				=	'<a href="' . htmlspecialchars( $this->get( 'url' ) ) . '" target="_blank">' . CBTxt::T( 'You are viewing this page securely, but this video does not support secure URLs. Please click here to view this video.' ) . '</a>';
			}
		}

		return $embed;
	}

	public function getUrl() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->getCategory()->get( 'id' ), (int) $this->get( 'group' ), null, array( 'tab' => htmlspecialchars( CBTxt::T( 'Video' ) ) ) ) );
		}

		return $cache[$id];
	}

	public function getTitle( $length = 0, $linked = false ) {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$embedCode		=	$this->getEmbed( false );
			$cache[$id]		=	htmlspecialchars( ( $this->get( 'title' ) ? $this->get( 'title' ) : ( $embedCode ? $embedCode : $this->get( 'url' ) ) ) );
		}

		$title				=	$cache[$id];

		if ( $title ) {
			$length			=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( $title ) > $length ) ) {
				$title		=	rtrim( trim( cbIsoUtf_substr( $title, 0, $length ) ), '.' ) . '...';
				$short		=	true;
			} else {
				$short		=	false;
			}

			if ( $linked ) {
				if ( $linked === 'tab' ) {
					$url	=	$this->getUrl();
					$target	=	null;
				} else {
					$url	=	htmlspecialchars( $this->get( 'url' ) );
					$target	=	'_blank';
				}

				$title		=	'<a href="' . $url . '"' . ( $short ? ' title="' . $cache[$id] . '"' : null ) . ( $target ? ' target="' . $target . '"' : null ) . '>' . $title . '</a>';
			}
		}

		return $title;
	}

	public function getCaption( $length = 0 ) {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	htmlspecialchars( $this->get( 'caption' ) );
		}

		$caption			=	$cache[$id];

		if ( $caption ) {
			$length			=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( strip_tags( $caption ) ) > $length ) ) {
				$caption	=	rtrim( trim( cbIsoUtf_substr( strip_tags( $caption ), 0, $length ) ), '.' ) . '...';
			}
		}

		return $caption;
	}

	public function getOwner() {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=&	CBuser::getUserDataInstance( (int) $id );
		}

		return $cache[$id];
	}

	public function getOwnerName( $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cbUser		=&	CBuser::getInstance( (int) $id );

			if ( ! $cbUser ) {
				$cbUser	=&	CBuser::getInstance( null );
			}

			$cache[$id]	=	$cbUser;
		}

		$name			=	null;

		if ( $cache[$id] ) {
			$user		=	$cache[$id];

			if ( $linked ) {
				$name	=	$user->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
			} else {
				$name	=	$user->getField( 'formatname', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $name;
	}

	public function getOwnerAvatar( $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cbUser		=&	CBuser::getInstance( (int) $id );

			if ( ! $cbUser ) {
				$cbUser	=&	CBuser::getInstance( null );
			}

			$cache[$id]	=	$cbUser;
		}

		$name			=	null;

		if ( $cache[$id] ) {
			$user		=	$cache[$id];

			if ( $linked ) {
				$name	=	$user->getField( 'avatar', null, 'html', 'none', 'list', 0, true );
			} else {
				$name	=	$user->getField( 'avatar', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $name;
	}

	public function getOwnerOnline( $html = true ) {
		static $cache	=	array();

		$id				=	$this->get( 'user_id' );

		if ( ! isset( $cache[$id] ) ) {
			$cbUser		=&	CBuser::getInstance( (int) $id );

			if ( ! $cbUser ) {
				$cbUser	=&	CBuser::getInstance( null );
			}

			$cache[$id]	=	$cbUser;
		}

		$status			=	null;

		if ( $cache[$id] ) {
			$user		=	$cache[$id];

			if ( ! $html ) {
				$status	=	$user->getField( 'onlinestatus', null, 'csv', 'none', 'profile', 0, true );
			} else {
				$status	=	$user->getField( 'onlinestatus', null, 'html', 'none', 'profile', 0, true );
			}
		}

		return $status;
	}

	public function getCategory() {
		static $cache	=	array();

		$id				=	$this->get( 'group' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	$this->getGroup()->getCategory();
		}

		return $cache[$id];
	}

	public function getGroup() {
		static $cache	=	array();

		$id				=	$this->get( 'group' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );
		}

		return $cache[$id];
	}

	public function getSubstitutionExtras( $cbtxt = false ) {
		$extras				=	array(	'video_id' => $this->get( 'id' ),
										'video_title' => $this->getTitle(),
										'video_title_linked' => $this->getTitle( 0, 'tab' ),
										'video_caption' => $this->getCaption(),
										'video_url' => $this->getUrl(),
										'video_embed' => $this->getEmbed( true ),
										'video_embed_code' => $this->getEmbed( false ),
										'video_embed_thumb' => $this->getEmbed( true, true ),
										'video_embed_url' => $this->get( 'url' ),
										'video_date' => cbFormatDate( $this->get( 'date' ), 1, false ),
										'video_owner' => $this->getOwnerName(),
										'video_owner_linked' => $this->getOwnerName( true ),
										'video_published' => $this->get( 'published' ),
										'video_user_id' => $this->get( 'user_id' ),
										'video_group' => $this->get( 'group' )
									);

		if ( $cbtxt ) foreach ( $extras as $k => $v ) {
			$extras["[$k]"]	=	$v;

			unset( $extras[$k] );
		}

		return $extras;
	}
}

class cbgjVideoData {

    static public function getVideos( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
        global $_CB_database;

        static $cache		=	array();

        if ( ! $access ) {
            $access			=	array();
        }

        if ( ! $filtering ) {
            $filtering		=	array();
        }

        if ( ! $ordering ) {
            $ordering		=	array();
        }

        $id					=	cbgjClass::getStaticID( array( $filtering, $ordering ) );

        if ( ( ! isset( $cache[$id] ) ) || cbgjClass::resetCache() ) {
            $where			=	array();
            $join			=	array();

            if ( $filtering ) {
                cbgjData::where( $where, $join, $filtering, 'a' );
            }

            $orderBy		=	array();

            if ( $ordering ) {
                cbgjData::order( $orderBy, $join, $ordering, 'a' );
            }

            $query			=	'SELECT a.*'
                .	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_plugin_video' ) . " AS a";

            if ( count( $join ) ) {
                if ( in_array( 'b', $join ) ) {
                    $query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_users' ) . " AS b"
                        .	' ON b.' . $_CB_database->NameQuote( 'group' ) . ' = a.' . $_CB_database->NameQuote( 'group' )
                        .	' AND b.' . $_CB_database->NameQuote( 'user_id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
                }

                if ( array_intersect( array( 'c', 'd' ), $join ) ) {
                    $query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_groups' ) . " AS c"
                        .	' ON c.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'group' );
                }

                if ( in_array( 'd', $join ) ) {
                    $query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__groupjive_categories' ) . " AS d"
                        .	' ON d.' . $_CB_database->NameQuote( 'id' ) . ' = c.' . $_CB_database->NameQuote( 'category' );
                }

                if ( in_array( 'e', $join ) ) {
                    $query	.=	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS e"
                        .	' ON e.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' );
                }
            }

            $query			.=	( count( $where ) ? "\n WHERE " . implode( "\n AND ", $where ) : null )
                .	"\n ORDER BY " . ( count( $orderBy ) ? implode( ', ', $orderBy ) : "a." . $_CB_database->NameQuote( 'date' ) . " DESC" );
            $_CB_database->setQuery( $query );
            $cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjVideo', array( & $_CB_database ) );
        }

        $rows				=	$cache[$id];

        if ( $rows ) {
            if ( $access ) {
                cbgjData::access( $rows, $access );
            }

            if ( $limits ) {
                cbgjData::limit( $rows, $limits );
            }
        }

        if ( ! $rows ) {
            $rows			=	array();
        }

        if ( $list ) {
            return $rows;
        } else {
            $rows			=	array_shift( $rows );

            if ( ! $rows ) {
                $rows		=	new cbgjVideo( $_CB_database );
            }

            return $rows;
        }
    }
}
?>