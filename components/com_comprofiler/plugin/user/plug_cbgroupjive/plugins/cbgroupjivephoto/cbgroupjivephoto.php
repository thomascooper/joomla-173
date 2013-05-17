<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $_PLUGINS;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

$_PLUGINS->registerFunction( 'gj_onBeforeGroupTab', 'getPhotos', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteGroup', 'deletePhotos', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupEdit', 'getParam', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onConfigIntegrations', 'getConfig', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onPluginFE', 'getPluginFE', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateGroup', 'setParam', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateGroup', 'setParam', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteUser', 'leaveGroup', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'deleteUser', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onAuthorization', 'getAuthorization', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupNotifications', 'getNotifications', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onBeforeUpdateNotificationParams', 'setNotifications', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsGeneral', 'getMenu', 'cbgjPhotoPlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsSave', 'saveMenu', 'cbgjPhotoPlugin' );

class cbgjPhotoPlugin extends cbPluginHandler {

	public function getPluginFE( $params, $user, $plugin ) {
		if ( $params[1] && $params[2] ) {
			switch ( $params[0] ) {
				case 'photo_publish':
					$this->statePhoto( $params[1], $params[2], $params[3], 1, $user, $plugin );
					break;
				case 'photo_unpublish':
					$this->statePhoto( $params[1], $params[2], $params[3], 0, $user, $plugin );
					break;
				case 'photo_edit':
					$this->editPhoto( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'photo_save':
					cbSpoofCheck( 'plugin' );
					$this->savePhoto( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'photo_delete':
					$this->deletePhoto( $params[1], $params[2], $params[3], $user, $plugin );
					break;
			}
		} else {
			switch ( $params[0] ) {
				case 'photo_approval':
					$this->getPhotoApproval( $user, $plugin );
					break;
			}
		}
	}

	public function getPhotoApproval( $user, $plugin ) {
		$paging				=	new cbgjPaging( 'photo_approval' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'photo_approval_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'title', 'CONTAINS', $search );
		}

		$searching			=	( count( $where ) ? true : false );

		$where[]			=	array( 'published', '=', -1, 'c.params', 'CONTAINS', 'photo_approve=1' );

		$total				=	count( cbgjPhotoData::getPhotos( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjPhotoData::getPhotos( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where, null, ( $plugin->params->get( 'photo_approval_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::T( 'Search Photos...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjivephotoapproval' ) ) {
			HTML_cbgroupjivephotoapproval::showPhotosApproval( $rows, $pageNav, $user, $plugin );
		} else {
			$this->showPhotosApproval( $rows, $pageNav, $user, $plugin );
		}
	}

	private function showPhotosApproval( $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle				=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( CBTxt::T( 'Photo Approval' ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( CBTxt::T( 'Photo Approval' ), cbgjClass::getPluginURL( array( 'plugin', 'photo_approval' ) ) );


		$photoApprovalSearch		=	$plugin->params->get( 'photo_approval_search', 1 );
		$photoApprovalPaging		=	$plugin->params->get( 'photo_approval_paging', 1 );
		$photoApprovalLimitbox		=	$plugin->params->get( 'photo_approval_limitbox', 1 );
		$displayLightbox			=	$plugin->params->get( 'photo_lightbox', 1 );

		if ( $displayLightbox ) {
			$lightboxJs				=	"$( '.cbgjPhotoLightbox' ).slimbox( { counterText: '" . addslashes( CBTxt::T( 'Image {x} of {y}' ) ) . "' } );"
									.	"$( '.gjPhotoApproval .gjPhotoTitle a' ).click( function() {"
									.		"$( this ).parents( '.gjContentBox' ).find( '.cbgjPhotoLightbox' ).trigger( 'click' );return false;"
									.	"});";

			$_CB_framework->outputCbJQuery( $lightboxJs, 'slimbox2' );
		}

		$return						=	'<div class="gjPhotoApproval">'
									.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_approval' ) ) . '" method="post" name="gjForm" id="gjForm" class="gjForm">'
									.			( $photoApprovalSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) {
			$return					.=			'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$category			=	$row->getCategory();
				$group				=	$row->getGroup();
				$authorized			=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

				$return				.=				'<div class="gjContentBox mini-layout">'
									.					'<div class="gjContentBoxRow gjPhotoTitle">'
									.						$row->getTitle( 0, true );

				if ( $row->getDescription() ) {
					$return			.=						cbgjClass::getIcon( $row->getDescription(), null, 'icon-info-sign', true );
				}

				$return				.=					'</div>'
									.					'<div class="gjContentBoxRow"><span title="' . $row->getDescription() . '">' . $row->getImage( true, ( $displayLightbox ? 2 : true ), true ) . '</span></div>'
									.					'<div class="gjContentBoxRow">' . cbFormatDate( $row->get( 'date' ), 1, false ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $category->getName( 0, true ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $group->getName( 0, true ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $row->getOwnerName( true ) . '</div>';

				if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return			.=					'<div class="gjContentBoxRow">'
									.						'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />'
									.					'</div>';
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) ) {
					$menuItems		=	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this photo?' ), true, false, null, true ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

					$return			.=					'<div class="gjContentBoxRow">'
									.						cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) )
									.					'</div>';
				}

				$return				.=				'</div>';
			}

			$return					.=			'</div>';
		} else {
			$return					.=			'<div class="gjContent">';

			if ( $photoApprovalSearch && $pageNav->searching ) {
				$return				.=				CBTxt::Th( 'No photo search results found.' );
			} else {
				$return				.=				CBTxt::Th( 'There are no photos pending approval.' );
			}

			$return					.=			'</div>';
		}

		if ( $photoApprovalPaging ) {
			$return					.=			'<div class="gjPaging pagination pagination-centered">'
									.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.				( ! $photoApprovalLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.			'</div>';
		}

		$return						.=			cbGetSpoofInputTag( 'plugin' )
									.		'</form>'
									.	'</div>';

		echo $return;
	}

	public function getPhotos( $tabs, $group, $category, $user, $plugin ) {
		$authorized			=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( ! cbgjClass::hasAccess( 'photo_show', $authorized ) ) {
			return;
		}

		cbgjClass::getTemplate( 'cbgroupjivephoto' );

		if ( $plugin->params->get( 'general_validate', 1 ) ) {
			cbgjClass::getFormValidation( '#gjForm_photo', "rules: { photo_image: { accept: 'jpg|jpeg|gif|png' } }" );
		}

		$paging				=	new cbgjPaging( 'photo' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'photo_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'title', 'CONTAINS', $search );
		}

		$searching			=	( count( $where ) ? true : false );

		if ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
			$where[]		=	array( 'published', '=', 1, array( 'user_id', '=', (int) $user->id ) );
		}

		$where[]			=	array( 'group', '=', (int) $group->get( 'id' ) );

		$total				=	count( cbgjPhotoData::getPhotos( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjPhotoData::getPhotos( null, $where, null, ( $plugin->params->get( 'photo_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm_photos', 'search', CBTxt::T( 'Search Photos...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjivephoto' ) ) {
			return HTML_cbgroupjivephoto::showPhotos( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		} else {
			return $this->showPhotos( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		}
	}

	private function showPhotos( $rows, $pageNav, $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework, $ueConfig;

		$photoSearch				=	( $plugin->params->get( 'photo_search', 1 ) && ( $pageNav->searching || $pageNav->total ) );
		$photoPaging				=	$plugin->params->get( 'photo_paging', 1 );
		$photoLimitbox				=	$plugin->params->get( 'photo_limitbox', 1 );
		$photoMaxsize				=	$plugin->params->get( 'photo_maxsize', 2000 );
		$photoMaxwidth				=	$plugin->params->get( 'photo_maxwidth', 200 );
		$photoMaxheight				=	$plugin->params->get( 'photo_maxheight', 500 );
		$displayLightbox			=	$plugin->params->get( 'photo_lightbox', 1 );
		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user );
		$photoToggle				=	( ( $plugin->params->get( 'group_toggle', 3 ) > 1 ) && cbgjClass::hasAccess( 'photo_upload', $authorized ) );

		$params						=	$group->getParams();
		$photoApprove				=	$params->get( 'photo_approve', $plugin->params->get( 'photo_approve', 0 ) );

		if ( $displayLightbox ) {
			$lightboxJs				=	"$( '.cbgjPhotoLightbox' ).slimbox( { counterText: '" . addslashes( CBTxt::T( 'Image {x} of {y}' ) ) . "' } );"
									.	"$( '.gjPhoto .gjPhotoTitle a' ).click( function() {"
									.		"$( this ).parents( '.gjContentBox' ).find( '.cbgjPhotoLightbox' ).trigger( 'click' );return false;"
									.	"});";

			$_CB_framework->outputCbJQuery( $lightboxJs, 'slimbox2' );
		}

		$return						=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Photo' ) ), 'gjIntegrationsPhoto' )
									.	'<div class="gjPhoto">';

		if ( cbgjClass::hasAccess( 'photo_upload', $authorized ) ) {
			if ( $plugin->params->get( 'photo_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha			=	cbgjCaptcha::render();
			} else {
				$captcha			=	false;
			}

			$maxsize				=	( $photoMaxsize ? $photoMaxsize : $ueConfig['avatarSize'] );
			$maxwidth				=	( $photoMaxwidth ? $photoMaxwidth : $ueConfig['avatarWidth'] );
			$maxheight				=	( $photoMaxheight ? $photoMaxheight : $ueConfig['avatarHeight'] );

			$input					=	array();

			$input['publish']		=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'photo_published', ( $params->get( 'photo_approve', $plugin->params->get( 'photo_approve', 0 ) ) ? 0 : 1 ) ) );
			$input['title']			=	'<input type="text" size="35" class="input-large" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'photo_title' ) ) . '" name="photo_title" id="photo_title" />';
			$input['image']			=	'<input type="file" size="25" class="input-large required" value="" name="photo_image" id="photo_image" />';
			$input['description']	=	'<textarea id="photo_description" name="photo_description" class="input-xlarge" cols="30" rows="2">' . htmlspecialchars( cbgjClass::getCleanParam( true, 'photo_description' ) ) . '</textarea>';

			$return					.=		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '" method="post" enctype="multipart/form-data" name="gjForm_photo" id="gjForm_photo" class="gjForm gjToggle form-horizontal">';

			if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
				$return				.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['publish']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of photo. Unpublished photos will not be visible to the public.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';
			}

			$return					.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Title' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['title']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Optionally input photo title. Titles will link directly to photo. Only plain text is supported. No HTML please.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Image' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['image']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.						cbgjClass::getIcon( CBTxt::P( 'Select photo image. Images will be resized if needed to a maximum dimension of [width] x [height] (width x height) automatically, but your image file should not exceed [size] KB and be of jpg, jpeg, gif, or png supported file type.', array( '[width]' => $maxwidth, '[height]' => $maxheight, '[size]' => $maxsize ) ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Description' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['description']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Optionally input photo description. Only plain text is supported. No HTML please.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';

			if ( $captcha !== false ) {
				$return				.=			'<div class="gjEditContentInput control-group">'
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

			$return					.=			'<div class="gjButtonWrapper form-actions">'
									.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Upload Photo' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
									.				( $photoToggle ? '<a href="#gjPhotoToggle" role="button" class="gjButton gjButtonCancel btn btn-mini gjToggleCollapse">' . CBTxt::Th( 'Cancel' ) . '</a>' : null )
									.			'</div>'
									.			cbGetSpoofInputTag( 'plugin' )
									.		'</form>';
		}

		$return						.=		'<form action="' . $group->getUrl() . '" method="post" name="gjForm_photos" id="gjForm_photos" class="gjForm">';

		if ( $photoToggle || $photoSearch ) {
			$return					.=			'<div class="gjTop row-fluid">'
									.				'<div class="gjTop gjTopLeft span6">'
									.					( $photoToggle ? '<a href="#gjForm_photo" id="gjPhotoToggle" role="button" class="gjButton btn gjToggleExpand">' . CBTxt::Th( 'New Photo' ) . '</a>' : null )
									.				'</div>'
									.				'<div class="gjTop gjTopRight span6">'
									.					( $photoSearch ? $pageNav->search : null )
									.				'</div>'
									.			'</div>';
		}

		if ( $rows ) {
			$return					.=			'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$authorized			=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

				if ( $row->get( 'published' ) == 1 ) {
					$state			=	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_unpublish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to unpublish this photo?' ) ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a></div>';
				} else {
					$state			=	'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a></div>';
				}

				$canApprove			=	( $photoApprove && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) );

				$return				.=				'<div class="gjContentBox mini-layout">'
									.					'<div class="gjContentBoxRow gjPhotoTitle">'
									.						$row->getTitle( 0, true );

				if ( $row->getDescription() ) {
					$return			.=						cbgjClass::getIcon( $row->getDescription(), null, 'icon-info-sign', true );
				}

				if ( ! $row->get( 'published' ) ) {
					$return			.=						cbgjClass::getIcon( null, CBTxt::T( 'This photo is currently unpublished.' ), 'icon-eye-close' );
				}

				$return				.=					'</div>'
									.					'<div class="gjContentBoxRow">' . $row->getImage( true, ( $displayLightbox ? 2 : true ), true ) . '</div>'
									.					'<div class="gjContentBoxRow">' . cbFormatDate( $row->get( 'date' ), 1, false ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $row->getOwnerName( true ) . '</div>';

				if ( $canApprove ) {
					$return			.=					'<div class="gjContentBoxRow">'
									.						'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />'
									.					'</div>';
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) ) {
					$menuItems		=	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
									.	( ( ! $canApprove ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? $state : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this photo?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

					$return			.=					'<div class="gjContentBoxRow">'
									.						cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) )
									.					'</div>';
				}

				$return				.=				'</div>';
			}

			$return					.=			'</div>';
		} else {
			$return					.=			'<div class="gjContent">';

			if ( $photoSearch && $pageNav->searching ) {
				$return				.=				CBTxt::Th( 'No photo search results found.' );
			} else {
				$return				.=				CBTxt::Ph( 'This [group] has no photos.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			}

			$return					.=			'</div>';
		}

		if ( $photoPaging ) {
			$return					.=			'<div class="gjPaging pagination pagination-centered">'
									.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.				( ! $photoLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.			'</div>';
		}

		$return						.=			cbGetSpoofInputTag( 'plugin' )
									.		'</form>'
									.	'</div>'
									.	$tabs->endTab();

		return $return;
	}

	private function editPhoto( $catid, $grpid, $id, $user, $plugin, $message = null ) {
		global $ueConfig;

		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row						=	cbgjPhotoData::getPhotos( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group					=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category				=	$group->getCategory();
		}

		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) ) {
			$params					=	$group->getParams();
			$photoMaxsize			=	$plugin->params->get( 'photo_maxsize', 2000 );
			$photoMaxwidth			=	$plugin->params->get( 'photo_maxwidth', 200 );
			$photoMaxheight			=	$plugin->params->get( 'photo_maxheight', 500 );

			$row->setPathway( CBTxt::T( 'Edit Photo' ) );

			cbgjClass::getTemplate( 'cbgroupjivephoto_edit' );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation( null, "rules: { photo_image: { accept: 'jpg|jpeg|gif|png' } }" );
			}

			$maxsize				=	( $photoMaxsize ? $photoMaxsize : $ueConfig['avatarSize'] );
			$maxwidth				=	( $photoMaxwidth ? $photoMaxwidth : $ueConfig['avatarWidth'] );
			$maxheight				=	( $photoMaxheight ? $photoMaxheight : $ueConfig['avatarHeight'] );

			$input					=	array();

			$input['publish']		=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'photo_published', $row->get( 'published', ( $params->get( 'photo_approve', $plugin->params->get( 'photo_approve', 0 ) ) ? 0 : 1 ) ) ) );
			$input['title']			=	'<input type="text" size="35" class="input-large" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'photo_title', $row->get( 'title' ) ) ) . '" name="photo_title" id="photo_title" />';
			$input['image']			=	'<input type="file" size="25" class="input-large" value="" name="photo_image" id="photo_image" />';
			$input['description']	=	'<textarea id="photo_description" name="photo_description" class="input-xlarge" cols="30" rows="2">' . htmlspecialchars( cbgjClass::getCleanParam( true, 'photo_description', $row->get( 'description' ) ) ) . '</textarea>';

			if ( class_exists( 'HTML_cbgroupjivephotoEdit' ) ) {
				$return				=	HTML_cbgroupjivephotoEdit::showPhotoEdit( $row, $input, $group, $category, $user, $plugin );
			} else {
				$return				=	'<div class="gjPhotoEdit">'
									.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'photo_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
									.			'<legend class="gjEditTitle">' . CBTxt::Th( 'Edit Photo' ) . '</legend>';

				if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return			.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['publish']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of photo. Unpublished photos will not be visible to the public.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';
				}

				$return				.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Title' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['title']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Optionally input photo title. Titles will link directly to photo. Only plain text is supported. No HTML please.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Image' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					'<div style="margin-bottom: 10px;">' . $row->getImage( true ) . '</div>'
									.					$input['image']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.						cbgjClass::getIcon( CBTxt::P( 'Select photo image. Images will be resized if needed to a maximum dimension of [width] x [height] (width x height) automatically, but your image file should not exceed [size] KB and be of jpg, jpeg, gif, or png supported file type.', array( '[width]' => $maxwidth, '[height]' => $maxheight, '[size]' => $maxsize ) ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Description' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['description']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Optionally input photo description. Only plain text is supported. No HTML please.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjButtonWrapper form-actions">'
									.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Update Photo' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
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
				$url				=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url				=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url				=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function savePhoto( $catid, $grpid, $id, $user, $plugin ) {
		$category							=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group								=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row								=	cbgjPhotoData::getPhotos( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group							=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category						=	$group->getCategory();
		}

		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || cbgjClass::hasAccess( 'photo_upload', $authorized ) ) {
			$params							=	$group->getParams();
			$photoApprove					=	$params->get( 'photo_approve', $plugin->params->get( 'photo_approve', 0 ) );

			$row->set( 'published', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'published', $row->get( 'published', ( $photoApprove ? -1 : 1 ) ) ) );
			$row->set( 'user_id', (int) $row->get( 'user_id', $user->id ) );
			$row->set( 'group', (int) $row->get( 'group', $group->get( 'id' ) ) );
			$row->set( 'title', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'photo_title', $row->get( 'title' ) ) ) );
			$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'photo_description', $row->get( 'description' ) ) ) );
			$row->set( 'date', $row->get( 'date', cbgjClass::getUTCDate() ) );

			if ( ! $row->get( 'user_id' ) ) {
				$row->set( '_error', CBTxt::P( '[user] not specified!', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );
			} elseif ( ! $row->get( 'group' ) ) {
				$row->set( '_error', CBTxt::P( '[group] not specified!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) );
			} elseif ( $plugin->params->get( 'photo_captcha', 0 ) && ( ! $row->get( 'id' ) ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha					=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$row->set( '_error', CBTxt::T( $captcha ) );
				}
			}

			if ( ! $row->getError() ) {
				$row->setPhoto( 'photo_image' );
			}

			$new							=	( $row->get( 'id' ) ? false : true );

			if ( $row->getError() || ( ! $row->store() ) ) {
				if ( ! $new ) {
					$this->editPhoto( $category->get( 'id' ), $group->get( 'id' ), $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Photo failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) );
				} else {
					CBplug_cbgroupjive::showGroup( $category->get( 'id' ), $group->get( 'id' ), $user, $plugin, CBTxt::P( 'Photo failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), '#gjForm_photo' );
				}
				return;
			}

			if ( $new ) {
				if ( $row->get( 'published' ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'photo_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_photonew=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - Photo Uploaded!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] uploaded [photo_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				} elseif ( $photoApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'photo_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_photoapprove=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - Photo Uploaded Requires Approval!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] uploaded [photo_title_linked] in [group] and requires approval!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				}
			} elseif ( $row->get( 'published' ) ) {
				$notifications				=	cbgjData::getNotifications( array( array( 'photo_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_photoupdate=1' ) ) );

				if ( $notifications ) {
					$subject				=	CBTxt::P( '[group_name] - Photo Edited!', $row->getSubstitutionExtras( true ) );
					$message				=	CBTxt::P( '[user] edited [photo_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}
			}

			if ( $photoApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
				$successMsg					=	CBTxt::T( 'Photo saved successfully and awaiting approval!' );
			} else {
				$successMsg					=	CBTxt::T( 'Photo saved successfully!' );
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

	private function statePhoto( $catid, $grpid, $id, $state, $user, $plugin ) {
		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row						=	cbgjPhotoData::getPhotos( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group					=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category				=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$currentState			=	$row->get( 'published' );

			if ( ! $row->storeState( $state ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Photo state failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $state && ( $currentState == -1 ) ) {
				$notifications		=	cbgjData::getNotifications( array( array( 'photo_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_photonew=1' ) ) );

				if ( $notifications ) {
					$subject		=	CBTxt::P( '[group_name] - Photo Uploaded!', $row->getSubstitutionExtras( true ) );
					$message		=	CBTxt::P( '[user] uploaded [photo_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}

				if ( $user->id != $row->get( 'user_id' ) ) {
					$subject		=	CBTxt::T( '[group_name] - Photo Upload Request Accepted!' );
					$message		=	CBTxt::T( 'Your request to upload [photo_title_linked] in [group] has been accepted!' );

					cbgjClass::getNotification( $row->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Photo state saved successfully!' ), false, true, null, false, false, true );
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

	private function deletePhoto( $catid, $grpid, $id, $user, $plugin ) {
		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row					=	cbgjPhotoData::getPhotos( array( array( 'mod_lvl4', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group				=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$published			=	$row->get( 'published' );

			if ( $published ) {
				$notifications	=	cbgjData::getNotifications( array( array( 'photo_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_photodelete=1' ) ) );
			} else {
				$notifications	=	null;
			}

			if ( ! $row->deleteAll() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Photo failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $notifications ) {
				$subject		=	CBTxt::P( '[group_name] - Photo Deleted!', $row->getSubstitutionExtras( true ) );
				$message		=	CBTxt::P( '[user] deleted [photo_title] in [group]!', $row->getSubstitutionExtras( true ) );

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Photo deleted successfully!' ), false, true, null, false, false, true );
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

	public function deletePhotos( $group, $category, $user, $plugin ) {
		$rows	=	cbgjPhotoData::getPhotos( null, array( 'group', '=', $group->get( 'id' ) ) );

		if ( $rows ) foreach ( $rows as $row ) {
			$row->deleteAll();
		}
	}

	public function getParam( $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user );
		$params					=	$group->getParams();
		$photoShow				=	$plugin->params->get( 'photo_show_config', 1 );
		$photoPublic			=	$plugin->params->get( 'photo_public_config', 1 );
		$photoUpload			=	$plugin->params->get( 'photo_upload_config', 1 );
		$photoApprove			=	$plugin->params->get( 'photo_approve_config', 1 );

		$input					=	array();

		$input['photo_show']	=	moscomprofilerHTML::yesnoSelectList( 'photo_show', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $photoShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'photo_show', $params->get( 'photo_show', $plugin->params->get( 'photo_show', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_public']	=	moscomprofilerHTML::yesnoSelectList( 'photo_public', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $photoPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'photo_public', $params->get( 'photo_public', $plugin->params->get( 'photo_public', 1 ) ) ) );

		$listUpload				=	array();
		$listUpload[]			=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listUpload[]			=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listUpload[]			=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listUpload[]			=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['photo_upload']	=	moscomprofilerHTML::selectList( $listUpload, 'photo_upload', 'class="input-large"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $photoUpload || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'photo_upload', $params->get( 'photo_upload', $plugin->params->get( 'photo_upload', 1 ) ) ), 1, false, false );

		$input['photo_approve']	=	moscomprofilerHTML::yesnoSelectList( 'photo_approve', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $photoApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'photo_approve', $params->get( 'photo_approve', $plugin->params->get( 'photo_approve', 0 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		if ( $_CB_framework->getUi() == 2 ) {
			$return				=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Photo' ) ), 'gjIntegrationsPhoto' )
								.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
								.			'<tbody>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Display' ) . '</div>'
								.					'<td width="40%">' . $input['photo_show'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select usage of group photos.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Public' ) . '</div>'
								.					'<td width="40%">' . $input['photo_public'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select if group photo tab is publicly visible.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Upload' ) . '</div>'
								.					'<td width="40%">' . $input['photo_upload'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select group upload access. Upload access determines what type of users can upload photos to your group (e.g. Users signify only those a member of your group can upload). The users above the selected will also have access.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</div>'
								.					'<td width="40%">' . $input['photo_approve'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Enable or disable approval of newly uploaded group files. Files will require approval by a group moderator, admin, or owner to be published. Group moderators, admins, and owner are exempt from this configuration.' ) . '</div>'
								.				'</tr>'
								.			'</tbody>'
								.		'</table>'
								.	$tabs->endTab();
		} else {
			if ( ( ! $photoShow ) && ( ! $photoPublic ) && ( ! $photoUpload ) && ( ! $photoApprove ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				return;
			}

			cbgjClass::getTemplate( 'cbgroupjivephoto_params' );

			if ( class_exists( 'HTML_cbgroupjivephotoParams' ) ) {
				$return			=	HTML_cbgroupjivephotoParams::showPhotoParams( $input, $group, $category, $user, $plugin );
			} else {
				$return			=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Photo' ) ), 'gjIntegrationsPhoto' );

				if ( $photoShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Display' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['photo_show']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select usage of [group] photos.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $photoPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Public' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['photo_public']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select if [group] photo tab is publicly visible.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $photoUpload || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Upload' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['photo_upload']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select [group] upload access. Upload access determines what type of [users] can upload photos to your [group] (e.g. [users] signify only those a member of your [group] can upload). The [users] above the selected will also have access.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $photoApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Approve' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['photo_approve']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Enable or disable approval of newly uploaded [group] photos. Photos will require approval by a [group] [mod], [admin], or [owner] to be published. [group] [mods], [admins], and [owner] are exempt from this configuration.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[mods]' => cbgjClass::getOverride( 'mod', true ), '[admins]' => cbgjClass::getOverride( 'admin', true ), '[mod]' => cbgjClass::getOverride( 'mod' ), '[admin]' => cbgjClass::getOverride( 'admin' ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				$return			.=	$tabs->endTab();
			}
		}

		return $return;
	}

	public function setParam( $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user );
		$params					=	$group->getParams();

		$params->set( 'photo_show', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'photo_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'photo_show', $params->get( 'photo_show', $plugin->params->get( 'photo_show', 1 ) ) ) );
		$params->set( 'photo_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'photo_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'photo_public', $params->get( 'photo_public', $plugin->params->get( 'photo_public', 1 ) ) ) );
		$params->set( 'photo_upload', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'photo_upload_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'photo_upload', $params->get( 'photo_upload', $plugin->params->get( 'photo_upload', 1 ) ) ) );
		$params->set( 'photo_approve', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'photo_approve_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'photo_approve', $params->get( 'photo_approve', $plugin->params->get( 'photo_approve', 0 ) ) ) );

		$group->storeParams( $params );

		if ( isset( $group->id ) && isset( $group->_previousCategory ) && ( $group->_previousCategory != $group->get( 'category' ) ) ) {
			$imagePath			=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/cbgroupjivephoto';
			$categoryPath		=	$imagePath . '/' . (int) $group->get( 'category' );
			$groupPath			=	$categoryPath . '/' . (int) $group->get( 'id' );
			$oldCategoryPath	=	$imagePath . '/' . (int) $group->_previousCategory;
			$oldGroupPath		=	$oldCategoryPath . '/' . (int) $group->get( 'id' );

			if ( file_exists( $oldGroupPath ) ) {
				cbgjClass::createFolderPath( $imagePath, $categoryPath, $groupPath );
				cbgjClass::copyDirectory( $oldGroupPath, $groupPath );
				cbgjClass::deleteDirectory( $oldGroupPath );
			}
		}
	}

	public function getConfig( $tabs, $user, $plugin ) {
		$input												=	array();

		$input['photo_lightbox']							=	moscomprofilerHTML::yesnoSelectList( 'photo_lightbox', null, $plugin->params->get( 'photo_lightbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_delete']								=	moscomprofilerHTML::yesnoSelectList( 'photo_delete', null, $plugin->params->get( 'photo_delete', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_notifications']						=	moscomprofilerHTML::yesnoSelectList( 'photo_notifications', null, $plugin->params->get( 'photo_notifications', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_notifications_group_photonew']		=	moscomprofilerHTML::yesnoSelectList( 'photo_notifications_group_photonew', null, $plugin->params->get( 'photo_notifications_group_photonew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['photo_notifications_group_photoapprove']	=	moscomprofilerHTML::yesnoSelectList( 'photo_notifications_group_photoapprove', null, $plugin->params->get( 'photo_notifications_group_photoapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['photo_notifications_group_photoupdate']		=	moscomprofilerHTML::yesnoSelectList( 'photo_notifications_group_photoupdate', null, $plugin->params->get( 'photo_notifications_group_photoupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['photo_notifications_group_photodelete']		=	moscomprofilerHTML::yesnoSelectList( 'photo_notifications_group_photodelete', null, $plugin->params->get( 'photo_notifications_group_photodelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['photo_maxsize']								=	'<input type="text" id="photo_maxsize" name="photo_maxsize" value="' . htmlspecialchars( $plugin->params->get( 'photo_maxsize', 2000 ) ) . '" class="inputbox" size="5" />';
		$input['photo_maxwidth']							=	'<input type="text" id="photo_maxwidth" name="photo_maxwidth" value="' . htmlspecialchars( $plugin->params->get( 'photo_maxwidth', 200 ) ) . '" class="inputbox" size="5" />';
		$input['photo_maxheight']							=	'<input type="text" id="photo_maxheight" name="photo_maxheight" value="' . htmlspecialchars( $plugin->params->get( 'photo_maxheight', 500 ) ) . '" class="inputbox" size="5" />';
		$input['photo_thumbwidth']							=	'<input type="text" id="photo_thumbwidth" name="photo_thumbwidth" value="' . htmlspecialchars( $plugin->params->get( 'photo_thumbwidth', 60 ) ) . '" class="inputbox" size="5" />';
		$input['photo_thumbheight']							=	'<input type="text" id="photo_thumbheight" name="photo_thumbheight" value="' . htmlspecialchars( $plugin->params->get( 'photo_thumbheight', 86 ) ) . '" class="inputbox" size="5" />';
		$input['photo_captcha']								=	moscomprofilerHTML::yesnoSelectList( 'photo_captcha', null, $plugin->params->get( 'photo_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_paging']								=	moscomprofilerHTML::yesnoSelectList( 'photo_paging', null, $plugin->params->get( 'photo_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_limitbox']							=	moscomprofilerHTML::yesnoSelectList( 'photo_limitbox', null, $plugin->params->get( 'photo_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_limit']								=	'<input type="text" id="photo_limit" name="photo_limit" value="' . (int) $plugin->params->get( 'photo_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['photo_search']								=	moscomprofilerHTML::yesnoSelectList( 'photo_search', null, $plugin->params->get( 'photo_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_approval_paging']						=	moscomprofilerHTML::yesnoSelectList( 'photo_approval_paging', null, $plugin->params->get( 'photo_approval_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_approval_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'photo_approval_limitbox', null, $plugin->params->get( 'photo_approval_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_approval_limit']						=	'<input type="text" id="photo_approval_limit" name="photo_approval_limit" value="' . (int) $plugin->params->get( 'photo_approval_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['photo_approval_search']						=	moscomprofilerHTML::yesnoSelectList( 'photo_approval_search', null, $plugin->params->get( 'photo_approval_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_show']								=	moscomprofilerHTML::yesnoSelectList( 'photo_show', null, $plugin->params->get( 'photo_show', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_show_config']							=	moscomprofilerHTML::yesnoSelectList( 'photo_show_config', null, $plugin->params->get( 'photo_show_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['photo_public']								=	moscomprofilerHTML::yesnoSelectList( 'photo_public', null, $plugin->params->get( 'photo_public', 1 ) );
		$input['photo_public_config']						=	moscomprofilerHTML::yesnoSelectList( 'photo_public_config', null, $plugin->params->get( 'photo_public_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$listUpload											=	array();
		$listUpload[]										=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listUpload[]										=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listUpload[]										=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listUpload[]										=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['photo_upload']								=	moscomprofilerHTML::selectList( $listUpload, 'photo_upload', 'class="inputbox"', 'value', 'text', $plugin->params->get( 'photo_upload', 1 ), 1, false, false );

		$input['photo_upload_config']						=	moscomprofilerHTML::yesnoSelectList( 'photo_upload_config', null, $plugin->params->get( 'photo_upload_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['photo_approve']								=	moscomprofilerHTML::yesnoSelectList( 'photo_approve', null, $plugin->params->get( 'photo_approve', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['photo_approve_config']						=	moscomprofilerHTML::yesnoSelectList( 'photo_approve_config', null, $plugin->params->get( 'photo_approve_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$return												=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Photo' ) ), 'gjIntegrationsPhoto' )
															.		$tabs->startPane( 'gjIntegrationsPhotoTabs' )
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsPhotoGeneral' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Lightbox' ) . '</th>'
															.							'<td width="40%">' . $input['photo_lightbox'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of lightbox photo display.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Auto Delete' ) . '</th>'
															.							'<td width="40%">' . $input['photo_delete'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable deletion of user photos on group leave.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Notifications' ) ), 'gjIntegrationsPhotoNotifications' )
															.				$tabs->startPane( 'gjIntegrationsPhotoNotificationsTabs' )
															.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsPhotoNotificationsGeneral' )
															.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.							'<tbody>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Notifications' ) . '</th>'
															.									'<td width="40%">' . $input['photo_notifications'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Enable or disable sending and configuration of photo notifications. Moderators are exempt from this configuration.' ) . '</td>'
															.								'</tr>'
															.							'</tbody>'
															.						'</table>'
															.					$tabs->endTab()
															.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsPhotoNotificationsDefaults' )
															.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.							'<tbody>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Upload Photo' ) . '</th>'
															.									'<td width="40%">' . $input['photo_notifications_group_photonew'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for photo notification parameter "Upload of new photo".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Photo Approval' ) . '</th>'
															.									'<td width="40%">' . $input['photo_notifications_group_photoapprove'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for photo notification parameter "New photo requires approval".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Update Photo' ) . '</th>'
															.									'<td width="40%">' . $input['photo_notifications_group_photoupdate'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for photo notification parameter "Update of existing photo".' ) . '</td>'
															.								'</tr>'
															.								'<tr>'
															.									'<th width="15%">' . CBTxt::Th( 'Delete Photo' ) . '</th>'
															.									'<td width="40%">' . $input['photo_notifications_group_photoupdate'] . '</td>'
															.									'<td>' . CBTxt::Th( 'Select default value for photo notification parameter "Delete of existing photo".' ) . '</td>'
															.								'</tr>'
															.							'</tbody>'
															.						'</table>'
															.					$tabs->endTab()
															.				$tabs->endPane()
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Upload' ) ), 'gjIntegrationsPhotoUpload' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Size' ) . '</th>'
															.							'<td width="600px">' . $input['photo_maxsize'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input maximum file size for photos. If blank Community Builder avatar size will be used.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Width' ) . '</th>'
															.							'<td width="600px">' . $input['photo_maxwidth'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input maximum photo width. If blank Community Builder avatar width will be used.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Height' ) . '</th>'
															.							'<td width="600px">' . $input['photo_maxheight'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input maximum photo height. If blank Community Builder avatar height will be used.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Thumb Width' ) . '</th>'
															.							'<td width="600px">' . $input['photo_thumbwidth'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input maximum photo thumbnail width. If blank Community Builder avatar thumbnail width will be used.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Thumb Height' ) . '</th>'
															.							'<td width="600px">' . $input['photo_thumbheight'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Input maximum photo thumbnail height. If blank Community Builder avatar thumbnail height will be used.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
															.							'<td width="40%">' . $input['photo_captcha'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of Captcha on Photo tab. Requires latest CB Captcha or integrated Captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjIntegrationsPhotoPaging' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
															.							'<td width="40%">' . $input['photo_paging'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on photos.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
															.							'<td width="50%">' . $input['photo_limitbox'] . '</td>'
															.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on photos. Requires Paging to be Enabled.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
															.							'<td width="50%">' . $input['photo_limit'] . '</td>'
															.							'<td>' . CBTxt::T( 'Input default page limit on photos. Page limit determines how many photos are displayed per page.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
															.							'<td width="600px">' . $input['photo_search'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on photos.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Approval' ) ), 'gjIntegrationsPhotoApproval' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
															.							'<td width="40%">' . $input['photo_approval_paging'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on photos.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
															.							'<td width="50%">' . $input['photo_approval_limitbox'] . '</td>'
															.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on photos. Requires Paging to be Enabled.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
															.							'<td width="50%">' . $input['photo_approval_limit'] . '</td>'
															.							'<td>' . CBTxt::T( 'Input default page limit on photos. Page limit determines how many photos are displayed per page.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
															.							'<td width="600px">' . $input['photo_approval_search'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on photos.' ) . '</td>'
															.						'</tr>'
															.					'</tbody>'
															.				'</table>'
															.			$tabs->endTab()
															.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsPhotoDefaults' )
															.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
															.					'<tbody>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
															.							'<td width="40%">' . $input['photo_show'] . ' ' . $input['photo_show_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Display". Additionally select the display of the "Display" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Public' ) . '</th>'
															.							'<td width="40%">' . $input['photo_public'] . ' ' . $input['photo_public_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Public". Additionally select the display of the "Public" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Upload' ) . '</th>'
															.							'<td width="600px">' . $input['photo_upload'] . ' ' . $input['photo_upload_config'] . '</td>'
															.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Upload". Additionally select the display of the "Upload" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
															.						'</tr>'
															.						'<tr>'
															.							'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</th>'
															.							'<td width="600px">' . $input['photo_approve'] . ' ' . $input['photo_approve_config'] . '</td>'
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
		$this->deleteUserPhotos( $user, $group );
	}

	public function deleteUser( $user, $deleted ) {
		$this->deleteUserPhotos( $user );
	}

	private function deleteUserPhotos( $user, $group = null ) {
		$plugin				=	cbgjClass::getPlugin();

		if ( $plugin->params->get( 'photo_delete', 0 ) ) {
			$where			=	array();

			if ( $group ) {
				$where[]	=	array( 'group', '=', (int) $group->get( 'id' ) );
			}

			$where[]		=	array( 'user_id', '=', (int) $user->id );

			$rows			=	cbgjPhotoData::getPhotos( null, $where );

			if ( $rows ) foreach ( $rows as $row ) {
				$row->deleteAll();
			}
		}
	}

	public function getAuthorization( &$access, $category, $group, $user, $owner, $row, $plugin ) {
		if ( isset( $group->id ) && cbgjClass::hasAccess( 'grp_approved', $access ) ) {
			$params					=	$group->getParams();
			$photoShow				=	$params->get( 'photo_show', $plugin->params->get( 'photo_show', 1 ) );
			$photoPublic			=	$params->get( 'photo_public', $plugin->params->get( 'photo_public', 1 ) );
			$photoUpload			=	$params->get( 'photo_upload', $plugin->params->get( 'photo_upload', 1 ) );

			if ( ( $photoPublic || cbgjClass::hasAccess( 'mod_lvl5', $access ) ) && $photoShow ) {
				$access[]			=	'photo_show';

				if ( cbgjClass::hasAccess( 'usr_notifications', $access ) && ( $plugin->params->get( 'photo_notifications', 1 ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) {
					if ( ! cbgjClass::hasAccess( 'grp_usr_notifications', $access ) ) {
						$access[]	=	'grp_usr_notifications';
					}

					$access[]		=	'photo_notifications';
				}
			}

			if ( $photoShow && ( ( ( $photoUpload == 0 ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) || ( ( $photoUpload == 1 ) && cbgjClass::hasAccess( 'mod_lvl4', $access ) ) || ( $photoUpload == 2 ) && cbgjClass::hasAccess( 'mod_lvl3', $access ) || ( $photoUpload == 3 ) && cbgjClass::hasAccess( 'mod_lvl2', $access ) ) ) {
				$access[]			=	'photo_upload';
			}
		}
	}

	public function getNotifications( $tabs, $row, $group, $category, $user, $plugin ) {
		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'photo_notifications', $authorized ) ) {
			$params							=	$row->getParams();

			$input							=	array();

			$input['group_photonew']		=	moscomprofilerHTML::yesnoSelectList( 'group_photonew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'photo_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_photonew', $params->get( 'group_photonew', $plugin->params->get( 'photo_notifications_group_photonew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_photoapprove']	=	moscomprofilerHTML::yesnoSelectList( 'group_photoapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'photo_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_photoapprove', $params->get( 'group_photoapprove', $plugin->params->get( 'photo_notifications_group_photoapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_photoupdate']		=	moscomprofilerHTML::yesnoSelectList( 'group_photoupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'photo_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_photoupdate', $params->get( 'group_photoupdate', $plugin->params->get( 'photo_notifications_group_photoupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_photodelete']		=	moscomprofilerHTML::yesnoSelectList( 'group_photodelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'photo_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_photodelete', $params->get( 'group_photodelete', $plugin->params->get( 'photo_notifications_group_photodelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

			$return							=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Photo' ) ), 'gjNotificationsGroupPhoto' )
											.		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Upload of new photo' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_photonew']
											.			'</div>'
											.		'</div>';

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'photo_show' ), $authorized, true ) ) {
				$return						.=		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'New photo requires approval' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_photoapprove']
											.			'</div>'
											.		'</div>';
			}

			$return							.=		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Update of existing photo' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_photoupdate']
											.			'</div>'
											.		'</div>'
											.		'<div class="gjEditContentInput control-group">'
											.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Delete of existing photo' ) . '</label>'
											.			'<div class="gjEditContentInputField controls">'
											.				$input['group_photodelete']
											.			'</div>'
											.		'</div>'
											.	$tabs->endTab();

			return $return;
		}
	}

	public function setNotifications( &$params, $row, $group, $category, $user, $plugin ) {
		if ( isset( $group->id ) ) {
			$authorized		=	cbgjClass::getAuthorization( $category, $group, $row->getOwner() );

			if ( cbgjClass::hasAccess( 'photo_notifications', $authorized ) ) {
				$row_params	=	$row->getParams();

				$params->set( 'group_photonew', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'photo_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_photonew', $row_params->get( 'group_photonew', $plugin->params->get( 'photo_notifications_group_photonew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_photoapprove', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'photo_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_photoapprove', $row_params->get( 'group_photoapprove', $plugin->params->get( 'photo_notifications_group_photoapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_photoupdate', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'photo_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_photoupdate', $row_params->get( 'group_photoupdate', $plugin->params->get( 'photo_notifications_group_photoupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_photodelete', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'photo_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_photodelete', $row_params->get( 'group_photodelete', $plugin->params->get( 'photo_notifications_group_photodelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );
			}
		}
	}

	public function getMenu( $user, $plugin ) {
		$input					=	array();

		$input['approve_photo']	=	'<input type="checkbox" id="type_approve_photo" name="type[]" class="inputbox" value="approve-photo" />';

		$return					=	'<tr>'
								.		'<td width="10%" style="text-align:center;">' . $input['approve_photo'] . '</td>'
								.		'<th width="20%">' . CBTxt::Th( 'Photo Approval' ) . '</td>'
								.		'<td>' . CBTxt::Th( 'Create menu link to a photo approval page.' ) . '</td>'
								.	'</tr>';

		return $return;
	}

	public function saveMenu( $type, $categories, $groups, $user, $plugin ) {
		if ( $type == 'approve-photo' ) {
			if ( ! cbgjClass::setMenu( CBTxt::T( 'Photo Approval' ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=plugin&func=photo_approval', $plugin ) ) {
				cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Photo approval menu failed to create!' ), false, true, 'error' );
			}
		}
	}
}

class cbgjPhoto extends comprofilerDBTable {
	var $id				=	null;
	var $published		=	null;
	var $user_id		=	null;
	var $group			=	null;
	var $title			=	null;
	var $image			=	null;
	var $description	=	null;
	var $date			=	null;

	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_plugin_photo', 'id', $db );
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
			$_PLUGINS->trigger( 'gjint_onBeforeUpdatePhoto', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gjint_onBeforeCreatePhoto', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onAfterUpdatePhoto', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );

			$_PLUGINS->trigger( 'gjint_onAfterCreatePhoto', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		return true;
	}

	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeDeletePhoto', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterDeletePhoto', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function storeState( $state ) {
		global $_CB_framework, $_PLUGINS;

		$plugin				=	cbgjClass::getPlugin();
		$user				=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdatePhotoState', array( &$state, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'published', (int) $state );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdatePhotoState', array( $this->get( 'published' ), $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function deleteAll() {
		global $_CB_framework;

		$imagePath		=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/cbgroupjivephoto';
		$categoryPath	=	$imagePath . '/' . (int) $this->getCategory()->get( 'id' );
		$groupPath		=	$categoryPath . '/' . (int) $this->get( 'group' );

		if ( $this->get( 'image' ) ) {
			if ( file_exists( $groupPath . '/' . $this->getCleanPhoto() ) ) {
				@unlink( $groupPath . '/' . $this->getCleanPhoto() );
			}

			if ( file_exists( $groupPath . '/tn' . $this->getCleanPhoto() ) ) {
				@unlink( $groupPath . '/tn' . $this->getCleanPhoto() );
			}
		}

		if ( ( $grp = @scandir( $groupPath ) ) && count( $grp ) <= 3 ) {
			cbgjClass::deleteDirectory( $groupPath );
		}

		if ( ( $cat = @scandir( $categoryPath ) ) && count( $cat ) <= 3 ) {
			cbgjClass::deleteDirectory( $categoryPath );
		}

		if ( ( $plg = @scandir( $imagePath ) ) && count( $plg ) <= 3 ) {
			cbgjClass::deleteDirectory( $imagePath );
		}

		if ( ! $this->delete() ) {
			return false;
		}

		return true;
	}

	public function setPhoto( $file ) {
		global $_CB_framework, $ueConfig;

		if ( isset( $_FILES[$file]['tmp_name'] ) && ! empty( $_FILES[$file]['tmp_name'] ) && ( $_FILES[$file]['error'] == 0 ) && ( is_uploaded_file( $_FILES[$file]['tmp_name'] ) ) ) {
			$plugin							=	cbgjClass::getPlugin();
			$maxsize						=	$plugin->params->get( 'photo_maxsize', 2000 );
			$maxwidth						=	$plugin->params->get( 'photo_maxwidth', 200 );
			$maxheight						=	$plugin->params->get( 'photo_maxheight', 500 );
			$thumbwidth						=	$plugin->params->get( 'photo_thumbwidth', 60 );
			$thumbheight					=	$plugin->params->get( 'photo_thumbheight', 86 );
			$imagePath						=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/cbgroupjivephoto';
			$categoryPath					=	$imagePath . '/' . (int) $this->getCategory()->get( 'id' );
			$groupPath						=	$categoryPath . '/' . (int) $this->get( 'group' );
			$mode							=	cbgjClass::getFilePerms();

			cbgjClass::createFolderPath( $imagePath, $categoryPath, $groupPath );

			$allwaysResize					=	( isset( $ueConfig['avatarResizeAlways'] ) ? $ueConfig['avatarResizeAlways'] : 1 );
			$fileNameInDir					=	uniqid( $this->get( 'user_id' ) . '_' );

			$imgToolBox						=	new imgToolBox();
			$imgToolBox->_conversiontype	=	$ueConfig['conversiontype'];
			$imgToolBox->_IM_path			=	$ueConfig['im_path'];
			$imgToolBox->_NETPBM_path		=	$ueConfig['netpbm_path'];
			$imgToolBox->_maxsize			=	( $maxsize ? $maxsize : $ueConfig['avatarSize'] );
			$imgToolBox->_maxwidth			=	( $maxwidth ? $maxwidth : $ueConfig['avatarWidth'] );
			$imgToolBox->_maxheight			=	( $maxheight ? $maxheight : $ueConfig['avatarHeight'] );
			$imgToolBox->_thumbwidth		=	( $thumbwidth ? $thumbwidth : $ueConfig['thumbWidth'] );
			$imgToolBox->_thumbheight		=	( $thumbheight ? $thumbheight : $ueConfig['thumbHeight'] );
			$imgToolBox->_debug				=	0;

			$newFileName					=	$imgToolBox->processImage( $_FILES[$file], $fileNameInDir, $groupPath . '/', 0, 0, 1, $allwaysResize );

			if ( $newFileName ) {
				if ( $this->get( 'image' ) ) {
					if ( file_exists( $groupPath . '/' . $this->getCleanPhoto() ) ) {
						@unlink( $groupPath . '/' . $this->getCleanPhoto() );
					}

					if ( file_exists( $groupPath . '/tn' . $this->getCleanPhoto() ) ) {
						@unlink( $groupPath . '/tn' . $this->getCleanPhoto() );
					}
				}

				$this->set( 'image', $newFileName );

				@chmod( $groupPath . '/' . $this->getCleanPhoto(), $mode );
				@chmod( $groupPath . '/tn' . $this->getCleanPhoto(), $mode );
			} else {
				$this->set( '_error', CBTxt::T( str_replace( 'Error: ', '', $imgToolBox->_errMSG ) ) );
			}
		}

		if ( ( ! $this->get( 'image' ) ) && ( ! $this->getError() ) ) {
			$this->set( '_error', CBTxt::T( 'Image not specified!' ) );
		}
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

	public function getCleanPhoto() {
		return preg_replace( '/[^-a-zA-Z0-9_.]/', '', $this->get( 'image' ) );
	}

	public function getImage( $html = false, $linked = false, $thumb = false, $rel = null ) {
		global $_CB_framework;

		static $cache			=	array();

		$id						=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]			=	( $this->get( 'image' ) ? $_CB_framework->getCfg( 'live_site' ) . '/images/comprofiler/cbgroupjivephoto/' . (int) $this->getCategory()->get( 'id' ) . '/' . (int) $this->get( 'group' ) . '/' . $this->getCleanPhoto() : null );
		}

		$logo					=	$cache[$id];

		if ( $logo ) {
			if ( $thumb ) {
				$logo			=	( $this->get( 'image' ) ? str_replace( $this->getCleanPhoto(), 'tn' . $this->getCleanPhoto(), $logo ) : $logo );
			}

			if ( $html ) {
				$logo			=	'<img alt="' . htmlspecialchars( CBTxt::T( 'Photo' ) ) . '" title="' . $this->getTitle() . '" src="' . htmlspecialchars( $logo ) . '" class="img-polaroid" />';
			}

			if ( $linked ) {
				if ( $linked == 2 ) {
					if ( ! $rel ) {
						$rel	=	'cbgjPhoto';
					}

					$lightbox	=	' rel="lightbox-' . htmlspecialchars( $rel ) . '" class="' . htmlspecialchars( $rel ) . 'Lightbox"';
				} else {
					$lightbox	=	null;
				}

				if ( $linked === 'tab' ) {
					$url		=	$this->getUrl();
				} else {
					$url		=	$cache[$id];
				}

				$logo			=	'<a href="' . $url . '" target="_blank"' . $lightbox . '>' . $logo . '</a>';
			}
		}

		return $logo;
	}

	public function getUrl() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->getCategory()->get( 'id' ), (int) $this->get( 'group' ), null, array( 'tab' => htmlspecialchars( CBTxt::T( 'Photo' ) ) ) ) );
		}

		return $cache[$id];
	}

	public function getTitle( $length = 0, $linked = false ) {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	( $this->get( 'title' ) ? htmlspecialchars( $this->get( 'title' ) ) : $this->getCleanPhoto() );
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
				} else {
					$url	=	$this->getImage();
				}

				$title		=	'<a href="' . $url . '"' . ( $short ? ' title="' . $cache[$id] . '"' : null ) . '>' . $title . '</a>';
			}
		}

		return $title;
	}

	public function getDescription( $length = 0 ) {
		static $cache			=	array();

		$id						=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]			=	htmlspecialchars( $this->get( 'description' ) );
		}

		$description			=	$cache[$id];

		if ( $description ) {
			$length				=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( strip_tags( $description ) ) > $length ) ) {
				$description	=	rtrim( trim( cbIsoUtf_substr( strip_tags( $description ), 0, $length ) ), '.' ) . '...';
			}
		}

		return $description;
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
		$extras				=	array(	'photo_id' => $this->get( 'id' ),
										'photo_title' => $this->getTitle(),
										'photo_title_linked' => $this->getTitle( 0, 'tab' ),
										'photo_description' => $this->getDescription(),
										'photo_url' => $this->getUrl(),
										'photo_image' => $this->getImage( true ),
										'photo_image_linked' => $this->getImage( true, true ),
										'photo_image_url' => $this->getImage( false, true ),
										'photo_thumb' => $this->getImage( true, false, true ),
										'photo_thumb_linked' => $this->getImage( true, true, true ),
										'photo_thumb_url' => $this->getImage( false, true, true ),
										'photo_date' => cbFormatDate( $this->get( 'date' ), 1, false ),
										'photo_owner' => $this->getOwnerName(),
										'photo_owner_linked' => $this->getOwnerName( true ),
										'photo_published' => $this->get( 'published' ),
										'photo_user_id' => $this->get( 'user_id' ),
										'photo_group' => $this->get( 'group' )
									);

		if ( $cbtxt ) foreach ( $extras as $k => $v ) {
			$extras["[$k]"]	=	$v;

			unset( $extras[$k] );
		}

		return $extras;
	}
}

class cbgjPhotoData {

    static public function getPhotos( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
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
                .	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_plugin_photo' ) . " AS a";

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
            $cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjPhoto', array( & $_CB_database ) );
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
                $rows		=	new cbgjPhoto( $_CB_database );
            }

            return $rows;
        }
    }
}
?>