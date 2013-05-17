<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $_PLUGINS;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

$_PLUGINS->registerFunction( 'gj_onBeforeGroupTab', 'getFiles', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteGroup', 'deleteFiles', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupEdit', 'getParam', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onConfigIntegrations', 'getConfig', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onPluginFE', 'getPluginFE', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateGroup', 'setParam', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateGroup', 'setParam', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteUser', 'leaveGroup', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'deleteUser', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onAuthorization', 'getAuthorization', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupNotifications', 'getNotifications', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onBeforeUpdateNotificationParams', 'setNotifications', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsGeneral', 'getMenu', 'cbgjFilePlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsSave', 'saveMenu', 'cbgjFilePlugin' );

class cbgjFilePlugin extends cbPluginHandler {

	public function getPluginFE( $params, $user, $plugin ) {
		if ( $params[1] && $params[2] ) {
			switch ( $params[0] ) {
				case 'file_publish':
					$this->stateFile( $params[1], $params[2], $params[3], 1, $user, $plugin );
					break;
				case 'file_unpublish':
					$this->stateFile( $params[1], $params[2], $params[3], 0, $user, $plugin );
					break;
				case 'file_download':
					$this->downloadFile( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'file_edit':
					$this->editFile( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'file_save':
					cbSpoofCheck( 'plugin' );
					$this->saveFile( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'file_delete':
					$this->deleteFile( $params[1], $params[2], $params[3], $user, $plugin );
					break;
			}
		} else {
			switch ( $params[0] ) {
				case 'file_approval':
					$this->getFileApproval( $user, $plugin );
					break;
			}
		}
	}

	public function getFileApproval( $user, $plugin ) {
		cbgjClass::getTemplate( 'cbgroupjivefile_approval' );

		$paging				=	new cbgjPaging( 'file_approval' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'file_approval_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'title', 'CONTAINS', $search );
		}

		$searching			=	( count( $where ) ? true : false );

		$where[]			=	array( 'published', '=', -1, 'c.params', 'CONTAINS', 'file_approve=1' );

		$total				=	count( cbgjFileData::getFiles( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjFileData::getFiles( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where, null, ( $plugin->params->get( 'file_approval_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::T( 'Search Files...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjivefileapproval' ) ) {
			HTML_cbgroupjivefileapproval::showFilesApproval( $rows, $pageNav, $user, $plugin );
		} else {
			$this->showFilesApproval( $rows, $pageNav, $user, $plugin );
		}
	}

	private function showFilesApproval( $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle				=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( CBTxt::T( 'File Approval' ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( CBTxt::T( 'File Approval' ), cbgjClass::getPluginURL( array( 'plugin', 'file_approval' ) ) );

		$fileApprovalSearch			=	$plugin->params->get( 'file_approval_search', 1 );
		$fileApprovalPaging			=	$plugin->params->get( 'file_approval_paging', 1 );
		$fileApprovalLimitbox		=	$plugin->params->get( 'file_approval_limitbox', 1 );

		$return						=	'<div class="gjFileApproval">'
									.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'file_approval' ) ) . '" method="post" name="gjForm" id="gjForm" class="gjForm">'
									.			( $fileApprovalSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) {
			$return					.=			'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$category			=	$row->getCategory();
				$group				=	$row->getGroup();
				$authorized			=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

				$return				.=				'<div class="gjContentBox mini-layout">'
									.					'<div class="gjContentBoxRow">'
									.						$row->getTitle( 0, true );

				if ( $row->getDescription() ) {
					$return			.=						cbgjClass::getIcon( $row->getDescription(), null, 'icon-info-sign', true );
				}

				$return				.=					'</div>'
									.					'<div class="gjContentBoxRow">' . $row->getIcon( true ) . '</div>'
									.					'<div class="gjContentBoxRow"><span title="' . cbFormatDate( $row->get( 'date' ), 1, false ) . '">' . $row->getFileSize() . ' | ' . $row->getFileExt() . '</span></div>'
									.					'<div class="gjContentBoxRow">' . $category->getName( 0, true ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $group->getName( 0, true ) . '</div>'
									.					'<div class="gjContentBoxRow">' . $row->getOwnerName( true ) . '</div>';

				if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return			.=					'<div class="gjContentBoxRow">'
									.						'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'file_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />'
									.					'</div>';
				}

				if ( $row->get( 'file' ) || cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized  ) ) {
					$menuItems		=	( $row->get( 'file' ) ? '<div><a href="' . $row->getDownloadUrl() . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Download' ) . '</a></div>' : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'file_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'file_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this file?' ), true, false, null, true ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

					$return			.=					'<div class="gjContentBoxRow">'
									.						cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) )
									.					'</div>';
				}

				$return				.=				'</div>';
			}

			$return					.=			'</div>';
		} else {
			$return					.=			'<div class="gjContent">';

			if ( $fileApprovalSearch && $pageNav->searching ) {
				$return				.=				CBTxt::Th( 'No file search results found.' );
			} else {
				$return				.=				CBTxt::Th( 'There are no files pending approval.' );
			}

			$return					.=			'</div>';
		}

		if ( $fileApprovalPaging ) {
			$return					.=			'<div class="gjPaging pagination pagination-centered">'
									.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.				( ! $fileApprovalLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.			'</div>';
		}

		$return						.=			cbGetSpoofInputTag( 'plugin' )
									.		'</form>'
									.	'</div>';

		echo $return;
	}

	public function getFiles( $tabs, $group, $category, $user, $plugin ) {
		$authorized			=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( ! cbgjClass::hasAccess( 'file_show', $authorized ) ) {
			return;
		}

		cbgjClass::getTemplate( 'cbgroupjivefile' );

		if ( $plugin->params->get( 'general_validate', 1 ) ) {
			cbgjClass::getFormValidation( '#gjForm_file', "rules: { file_upload: { accept: '" . addslashes( implode( '|', explode( ',', $plugin->params->get( 'file_types', 'zip,rar,doc,pdf,txt,xls' ) ) ) ) . "' } }"  );
		}

		$paging				=	new cbgjPaging( 'file' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'file_limit', 15 ) );
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

		$total				=	count( cbgjFileData::getFiles( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjFileData::getFiles( null, $where, null, ( $plugin->params->get( 'file_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm_files', 'search', CBTxt::T( 'Search Files...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjivefile' ) ) {
			return HTML_cbgroupjivefile::showFiles( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		} else {
			return $this->showFiles( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		}
	}

	private function showFiles( $rows, $pageNav, $tabs, $group, $category, $user, $plugin ) {
		global $ueConfig;

		$fileSearch					=	( $plugin->params->get( 'file_search', 1 ) && ( $pageNav->searching || $pageNav->total ) );
		$filePaging					=	$plugin->params->get( 'file_paging', 1 );
		$fileLimitbox				=	$plugin->params->get( 'file_limitbox', 1 );
		$fileMaxsize				=	$plugin->params->get( 'file_maxsize', 2000 );
		$fileTypes					=	$plugin->params->get( 'file_types', 'zip,rar,doc,pdf,txt,xls' );
		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user );
		$fileToggle					=	( ( $plugin->params->get( 'group_toggle', 3 ) > 1 ) && cbgjClass::hasAccess( 'file_upload', $authorized ) );

		$params						=	$group->getParams();
		$fileApprove				=	$params->get( 'file_approve', $plugin->params->get( 'file_approve', 0 ) );

		$return						=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'File' ) ), 'gjIntegrationsFile' )
									.	'<div class="gjFile">';

		if ( cbgjClass::hasAccess( 'file_upload', $authorized ) ) {
			if ( $plugin->params->get( 'file_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha			=	cbgjCaptcha::render();
			} else {
				$captcha			=	false;
			}

			$input					=	array();

			$input['publish']		=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'file_published', ( $params->get( 'file_approve', $plugin->params->get( 'file_approve', 0 ) ) ? 0 : 1 ) ) );
			$input['title']			=	'<input type="text" size="35" class="input-large" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'file_title' ) ) . '" name="file_title" id="file_title" />';
			$input['file']			=	'<input type="file" size="25" class="input-large required" value="" name="file_upload" id="file_upload" />';
			$input['description']	=	'<textarea id="file_description" name="file_description" class="input-xlarge" cols="30" rows="2">' . htmlspecialchars( cbgjClass::getCleanParam( true, 'file_description' ) ) . '</textarea>';

			$return					.=		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'file_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '" method="post" enctype="multipart/form-data" name="gjForm_file" id="gjForm_file" class="gjForm gjToggle form-horizontal">';

			if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
				$return				.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['publish']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of file. Unpublished files will not be visible to the public.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';
			}

			$return					.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Title' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['title']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Optionally input file title. Titles will link directly to file. Only plain text is supported. No HTML please.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'File' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['file']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.						cbgjClass::getIcon( CBTxt::P( 'Select file to upload. Your file should not exceed [size] KB and be of [types] supported file type.', array( '[size]' => ( $fileMaxsize ? $fileMaxsize : $ueConfig['avatarSize'] ), '[types]' => ( $fileTypes ? $fileTypes : 'zip,rar,doc,pdf,txt,xls' ) ) ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Description' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['description']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Optionally input file description. Only plain text is supported. No HTML please.' ) )
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
									.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Upload File' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
									.				( $fileToggle ? '<a href="#gjFileToggle" role="button" class="gjButton gjButtonCancel btn btn-mini gjToggleCollapse">' . CBTxt::Th( 'Cancel' ) . '</a>' : null )
									.			'</div>'
									.			cbGetSpoofInputTag( 'plugin' )
									.		'</form>';
		}

		$return						.=		'<form action="' . $group->getUrl() . '" method="post" name="gjForm_files" id="gjForm_files" class="gjForm">';

		if ( $fileToggle || $fileSearch ) {
			$return					.=			'<div class="gjTop row-fluid">'
									.				'<div class="gjTop gjTopLeft span6">'
									.					( $fileToggle ? '<a href="#gjForm_file" id="gjFileToggle" role="button" class="gjButton btn gjToggleExpand">' . CBTxt::Th( 'New File' ) . '</a>' : null )
									.				'</div>'
									.				'<div class="gjTop gjTopRight span6">'
									.					( $fileSearch ? $pageNav->search : null )
									.				'</div>'
									.			'</div>';
		}

		if ( $rows ) {
			$return					.=			'<div class="gjContent">';

			foreach ( $rows as $row ) {
				$authorized			=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

				if ( $row->get( 'published' ) == 1 ) {
					$state			=	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'file_unpublish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to unpublish this file?' ) ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a></div>';
				} else {
					$state			=	'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'file_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a></div>';
				}

				$canApprove			=	( $fileApprove && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) );

				$return				.=				'<div class="gjContentBox mini-layout">'
									.					'<div class="gjContentBoxRow">'
									.						$row->getTitle( 0, true );

				if ( $row->getDescription() ) {
					$return			.=						cbgjClass::getIcon( $row->getDescription(), null, 'icon-info-sign', true );
				}

				if ( ! $row->get( 'published' ) ) {
					$return			.=						cbgjClass::getIcon( null, CBTxt::T( 'This file is currently unpublished.' ), 'icon-eye-close' );
				}

				$return				.=					'</div>'
									.					'<div class="gjContentBoxRow">' . $row->getIcon( true ) . '</div>'
									.					'<div class="gjContentBoxRow"><span title="' . cbFormatDate( $row->get( 'date' ), 1, false ) . '">' . $row->getFileSize() . ' | ' . $row->getFileExt() . '</span></div>'
									.					'<div class="gjContentBoxRow">'
									.						$row->getOwnerName( true )
									.					'</div>';

				if ( ( ! $canApprove ) && $row->get( 'file' ) ) {
					$return			.=					'<div class="gjContentBoxRow">'
									.						'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Download' ) ) . '" class="gjButton btn-mini btn btn-success" onclick="' . $row->getDownloadUrl( true ) . '" />'
									.					'</div>';
				} elseif ( $canApprove ) {
					$return			.=					'<div class="gjContentBoxRow">'
									.						'<input type="button" value="' . htmlspecialchars( CBTxt::Th( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'file_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />'
									.					'</div>';
				}

				if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized  ) ) {
					$menuItems		=	( $canApprove && $row->get( 'file' ) ? '<div><a href="' . $row->getDownloadUrl() . '"><i class="icon-download-alt"></i> ' . CBTxt::Th( 'Download' ) . '</a></div>' : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'file_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
									.	( ( ! $canApprove ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? $state : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'file_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this file?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

					$return			.=					'<div class="gjContentBoxRow">'
									.						cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) )
									.					'</div>';
				}

				$return				.=				'</div>';
			}

			$return					.=			'</div>';
		} else {
			$return					.=			'<div class="gjContent">';

			if ( $fileSearch && $pageNav->searching ) {
				$return				.=				CBTxt::Th( 'No file search results found.' );
			} else {
				$return				.=				CBTxt::Ph( 'This [group] has no files.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			}

			$return					.=			'</div>';
		}

		if ( $filePaging ) {
			$return					.=			'<div class="gjPaging pagination pagination-centered">'
									.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.				( ! $fileLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.			'</div>';
		}

		$return						.=			cbGetSpoofInputTag( 'plugin' )
									.		'</form>'
									.	'</div>'
									.	$tabs->endTab();

		return $return;
	}

	private function downloadFile( $catid, $grpid, $id, $user, $plugin ) {
		$category			=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group				=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row				=	cbgjFileData::getFiles( array( 'file_show', $user ), array( 'id', '=', $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group			=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category		=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$filePath		=	$row->getFilePath( true );

			if ( ! file_exists( $filePath ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'File failed to download! Error: File not found' ), false, true, 'error' );
			}

			$fileExt		=	$row->getFileExt();

			if ( ! $fileExt ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'File failed to download! Error: Unknown extension' ), false, true, 'error' );
			}

			$fileMime		=	$row->getFileMime();

			if ( ! $fileMime ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'File failed to download! Error: Unknown MIME' ), false, true, 'error' );
			}

			$fileSize		=	@filesize( $filePath );
			$fileModified	=	cbgjClass::getUTCDate( 'r', filemtime( $filePath ) );

			while ( @ob_end_clean() );

			if ( ini_get( 'zlib.output_compression' ) ) {
				ini_set( 'zlib.output_compression', 'Off' );
			}

			if ( function_exists( 'apache_setenv' ) ) {
				apache_setenv( 'no-gzip', '1' );
			}

			header( "Content-Type: $fileMime" );
			header( 'Content-Disposition: attachment; filename="' . $row->getFileName() . '"; modification-date="' . $fileModified . '"; size=' . $fileSize .';' );
			header( "Content-Transfer-Encoding: binary" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Pragma: public" );
			header( "Content-Length: $fileSize" );

			if ( ! ini_get( 'safe_mode' ) ) {
				@set_time_limit( 0 );
			}

			$handle			=	fopen( $filePath, 'rb' );

			if ( $handle === false ) {
				exit();
			}

			$chunksize		=	( 1 * ( 1024 * 1024 ) );
			$buffer			=	'';

			while ( ! feof( $handle ) ) {
				$buffer		=	fread( $handle, $chunksize );
				echo $buffer;
				@ob_flush();
				flush();
			}

			fclose( $handle );
			exit();
		} else {
			if ( $group->get( 'id' ) ) {
				$url		=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url		=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url		=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function editFile( $catid, $grpid, $id, $user, $plugin, $message = null ) {
		global $ueConfig;

		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row						=	cbgjFileData::getFiles( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group					=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category				=	$group->getCategory();
		}

		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) ) {
			$params					=	$group->getParams();
			$fileMaxsize			=	$plugin->params->get( 'file_maxsize', 2000 );
			$fileTypes				=	$plugin->params->get( 'file_types', 'zip,rar,doc,pdf,txt,xls' );

			$row->setPathway( CBTxt::T( 'Edit File' ) );

			cbgjClass::getTemplate( 'cbgroupjivefile_edit' );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation( null, "rules: { file_upload: { accept: '" . addslashes( implode( '|', explode( ',', $fileTypes ) ) ) . "' } }" );
			}

			$input					=	array();

			$input['publish']		=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'file_published', $row->get( 'published', ( $params->get( 'file_approve', $plugin->params->get( 'file_approve', 0 ) ) ? 0 : 1 ) ) ) );
			$input['title']			=	'<input type="text" size="35" class="input-large" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'file_title', $row->get( 'title' ) ) ) . '" name="file_title" id="file_title" />';
			$input['file']			=	'<input type="file" size="25" class="input-large" value="" name="file_upload" id="file_upload" />';
			$input['description']	=	'<textarea id="file_description" name="file_description" class="input-xlarge" cols="30" rows="2">' . htmlspecialchars( cbgjClass::getCleanParam( true, 'file_description', $row->get( 'description' ) ) ) . '</textarea>';

			if ( class_exists( 'HTML_cbgroupjivefileEdit' ) ) {
				$return				=	HTML_cbgroupjivefileEdit::showFileEdit( $row, $input, $group, $category, $user, $plugin );
			} else {
				$return				=	'<div class="gjFileEdit">'
									.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'file_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
									.			'<legend class="gjEditTitle">' . CBTxt::Th( 'Edit File' ) . '</legend>';

				if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return			.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['publish']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of file. Unpublished files will not be visible to the public.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';
				}

				$return				.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Title' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['title']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Optionally input file title. Titles will link directly to file. Only plain text is supported. No HTML please.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'File' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					'<div style="margin-bottom: 10px;">' . ( $row->get( 'file' ) ? '<a href="' . $row->getDownloadUrl() . '">' . $row->getFileName() . '</a>' : null ) . '</div>'
									.					$input['file']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.						cbgjClass::getIcon( CBTxt::P( 'Select file to upload. Your file should not exceed [size] KB and be of [types] supported file type.', array( '[size]' => ( $fileMaxsize ? $fileMaxsize : $ueConfig['avatarSize'] ), '[types]' => ( $fileTypes ? $fileTypes : 'zip,rar,doc,pdf,txt,xls' ) ) ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Description' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['description']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Optionally input file description. Only plain text is supported. No HTML please.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>'
									.			'<div class="gjButtonWrapper form-actions">'
									.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Update File' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
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

	private function saveFile( $catid, $grpid, $id, $user, $plugin ) {
		$category							=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group								=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row								=	cbgjFileData::getFiles( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group							=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category						=	$group->getCategory();
		}

		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || cbgjClass::hasAccess( 'file_upload', $authorized ) ) {
			$params							=	$group->getParams();
			$fileApprove					=	$params->get( 'file_approve', $plugin->params->get( 'file_approve', 0 ) );

			$row->set( 'published', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'published', $row->get( 'published', ( $fileApprove ? -1 : 1 ) ) ) );
			$row->set( 'user_id', (int) $row->get( 'user_id', $user->id ) );
			$row->set( 'group', (int) $row->get( 'group', $group->get( 'id' ) ) );
			$row->set( 'title', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'file_title', $row->get( 'title' ) ) ) );
			$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'file_description', $row->get( 'description' ) ) ) );
			$row->set( 'date', $row->get( 'date', cbgjClass::getUTCDate() ) );

			if ( ! $row->get( 'user_id' ) ) {
				$row->set( '_error', CBTxt::P( '[user] not specified!', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );
			} elseif ( ! $row->get( 'group' ) ) {
				$row->set( '_error', CBTxt::P( '[group] not specified!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) );
			} elseif ( $plugin->params->get( 'file_captcha', 0 ) && ( ! $row->get( 'id' ) ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha					=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$row->set( '_error', CBTxt::T( $captcha ) );
				}
			}

			if ( ! $row->getError() ) {
				$row->setFile( 'file_upload' );
			}

			$new							=	( $row->get( 'id' ) ? false : true );

			if ( $row->getError() || ( ! $row->store() ) ) {
				if ( ! $new ) {
					$this->editFile( $category->get( 'id' ), $group->get( 'id' ), $row->get( 'id' ), $user, $plugin, CBTxt::P( 'File failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) );
				} else {
					CBplug_cbgroupjive::showGroup( $category->get( 'id' ), $group->get( 'id' ), $user, $plugin, CBTxt::P( 'File failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), '#gjForm_file' );
				}
				return;
			}

			if ( $new ) {
				if ( $row->get( 'published' ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'file_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', $group->id ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_filenew=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - File Uploaded!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] uploaded [file_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				} elseif ( $fileApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'file_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', $group->id ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_fileapprove=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - File Uploaded Requires Approval!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] uploaded [file_title_linked] in [group] and requires approval!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				}
			} elseif ( $row->get( 'published' ) ) {
				$notifications				=	cbgjData::getNotifications( array( array( 'file_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', $group->id ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_fileupdate=1' ) ) );

				if ( $notifications ) {
					$subject				=	CBTxt::P( '[group_name] - File Edited!', $row->getSubstitutionExtras( true ) );
					$message				=	CBTxt::P( '[user] edited [file_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}
			}

			if ( $fileApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
				$successMsg					=	CBTxt::T( 'File saved successfully and awaiting approval!' );
			} else {
				$successMsg					=	CBTxt::T( 'File saved successfully!' );
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

	private function stateFile( $catid, $grpid, $id, $state, $user, $plugin ) {
		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row						=	cbgjFileData::getFiles( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group					=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category				=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$currentState			=	$row->get( 'published' );

			if ( ! $row->storeState( $state ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'File state failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $state && ( $currentState == -1 ) ) {
				$notifications		=	cbgjData::getNotifications( array( array( 'file_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', $group->id ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_filenew=1' ) ) );

				if ( $notifications ) {
					$subject		=	CBTxt::P( '[group_name] - File Uploaded!', $row->getSubstitutionExtras( true ) );
					$message		=	CBTxt::P( '[user] uploaded [file_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}

				if ( $user->id != $row->get( 'user_id' ) ) {
					$subject		=	CBTxt::T( '[group_name] - File Upload Request Accepted!' );
					$message		=	CBTxt::T( 'Your request to upload [file_title_linked] in [group] has been accepted!' );

					cbgjClass::getNotification( $row->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'File state saved successfully!' ), false, true, null, false, false, true );
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

	private function deleteFile( $catid, $grpid, $id, $user, $plugin ) {
		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row					=	cbgjFileData::getFiles( array( array( 'mod_lvl4', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group				=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$published			=	$row->get( 'published' );

			if ( $published ) {
				$notifications	=	cbgjData::getNotifications( array( array( 'file_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_filedelete=1' ) ) );
			} else {
				$notifications	=	null;
			}

			if ( ! $row->deleteAll() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'File failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $notifications ) {
				$subject		=	CBTxt::P( '[group_name] - File Deleted!', $row->getSubstitutionExtras( true ) );
				$message		=	CBTxt::P( '[user] deleted [file_title] in [group]!', $row->getSubstitutionExtras( true ) );

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'File deleted successfully!' ), false, true, null, false, false, true );
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

	public function deleteFiles( $group, $category, $user, $plugin ) {
		$rows	=	cbgjFileData::getFiles( null, array( 'group', '=', (int) $group->get( 'id' ) ) );

		if ( $rows ) foreach ( $rows as $row ) {
			$row->deleteAll();
		}
	}

	public function getParam( $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user );
		$params					=	$group->getParams();
		$fileShow				=	$plugin->params->get( 'file_show_config', 1 );
		$filePublic				=	$plugin->params->get( 'file_public_config', 1 );
		$fileUpload				=	$plugin->params->get( 'file_upload_config', 1 );
		$fileApprove			=	$plugin->params->get( 'file_approve_config', 1 );

		$input					=	array();

		$input['file_show']		=	moscomprofilerHTML::yesnoSelectList( 'file_show', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $fileShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'file_show', $params->get( 'file_show', $plugin->params->get( 'file_show', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_public']	=	moscomprofilerHTML::yesnoSelectList( 'file_public', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $filePublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'file_public', $params->get( 'file_public', $plugin->params->get( 'file_public', 1 ) ) ) );

		$listUpload				=	array();
		$listUpload[]			=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listUpload[]			=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listUpload[]			=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listUpload[]			=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['file_upload']	=	moscomprofilerHTML::selectList( $listUpload, 'file_upload', 'class="input-large"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $fileUpload || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'file_upload', $params->get( 'file_upload', $plugin->params->get( 'file_upload', 1 ) ) ), 1, false, false );

		$input['file_approve']	=	moscomprofilerHTML::yesnoSelectList( 'file_approve', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $fileApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'file_approve', $params->get( 'file_approve', $plugin->params->get( 'file_approve', 0 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		if ( $_CB_framework->getUi() == 2 ) {
			$return				=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'File' ) ), 'gjIntegrationsFile' )
								.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
								.			'<tbody>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Display' ) . '</div>'
								.					'<td width="40%">' . $input['file_show'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select usage of group files.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Public' ) . '</div>'
								.					'<td width="40%">' . $input['file_public'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select if group file tab is publicly visible.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Upload' ) . '</div>'
								.					'<td width="40%">' . $input['file_upload'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select group upload access. Upload access determines what type of users can upload files to your group (e.g. Users signify only those a member of your group can upload). The users above the selected will also have access.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</div>'
								.					'<td width="40%">' . $input['file_approve'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Enable or disable approval of newly uploaded group files. Files will require approval by a group moderator, admin, or owner to be published. Group moderators, admins, and owner are exempt from this configuration.' ) . '</div>'
								.				'</tr>'
								.			'</tbody>'
								.		'</table>'
								.	$tabs->endTab();
		} else {
			if ( ( ! $fileShow ) && ( ! $filePublic ) && ( ! $fileUpload ) && ( ! $fileApprove ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				return;
			}

			cbgjClass::getTemplate( 'cbgroupjivefile_params' );

			if ( class_exists( 'HTML_cbgroupjivefileParams' ) ) {
				$return			=	HTML_cbgroupjivefileParams::showFileParams( $input, $group, $category, $user, $plugin );
			} else {
				$return			=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'File' ) ), 'gjIntegrationsFile' );

				if ( $fileShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Display' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['file_show']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select usage of [group] files.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $filePublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Public' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['file_public']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select if [group] file tab is publicly visible.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $fileUpload || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Upload' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['file_upload']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select [group] upload access. Upload access determines what type of [users] can upload files to your [group] (e.g. [users] signify only those a member of your [group] can upload). The [users] above the selected will also have access.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $fileApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Approve' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['file_approve']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Enable or disable approval of newly uploaded [group] files. Files will require approval by a [group] [mod], [admin], or [owner] to be published. [group] [mods], [admins], and [owner] are exempt from this configuration.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[mods]' => cbgjClass::getOverride( 'mod', true ), '[admins]' => cbgjClass::getOverride( 'admin', true ), '[mod]' => cbgjClass::getOverride( 'mod' ), '[admin]' => cbgjClass::getOverride( 'admin' ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
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

		$params->set( 'file_show', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'file_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'file_show', $params->get( 'file_show', $plugin->params->get( 'file_show', 1 ) ) ) );
		$params->set( 'file_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'file_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'file_public', $params->get( 'file_public', $plugin->params->get( 'file_public', 1 ) ) ) );
		$params->set( 'file_upload', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'file_upload_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'file_upload', $params->get( 'file_upload', $plugin->params->get( 'file_upload', 1 ) ) ) );
		$params->set( 'file_approve', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'file_approve_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'file_approve', $params->get( 'file_approve', $plugin->params->get( 'file_approve', 0 ) ) ) );

		$group->storeParams( $params );

		if ( isset( $group->id ) && isset( $group->_previousCategory ) && ( $group->_previousCategory != $group->get( 'category' ) ) ) {
			$imagePath			=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/cbgroupjivefile';
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
		$input											=	array();

		$input['file_delete']							=	moscomprofilerHTML::yesnoSelectList( 'file_delete', null, $plugin->params->get( 'file_delete', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_notifications']					=	moscomprofilerHTML::yesnoSelectList( 'file_notifications', null, $plugin->params->get( 'file_notifications', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_notifications_group_filenew']		=	moscomprofilerHTML::yesnoSelectList( 'file_notifications_group_filenew', null, $plugin->params->get( 'file_notifications_group_filenew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['file_notifications_group_fileapprove']	=	moscomprofilerHTML::yesnoSelectList( 'file_notifications_group_fileapprove', null, $plugin->params->get( 'file_notifications_group_fileapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['file_notifications_group_fileupdate']	=	moscomprofilerHTML::yesnoSelectList( 'file_notifications_group_fileupdate', null, $plugin->params->get( 'file_notifications_group_fileupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['file_notifications_group_filedelete']	=	moscomprofilerHTML::yesnoSelectList( 'file_notifications_group_filedelete', null, $plugin->params->get( 'file_notifications_group_filedelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['file_maxsize']							=	'<input type="text" id="file_maxsize" name="file_maxsize" value="' . htmlspecialchars( $plugin->params->get( 'file_maxsize', 2000 ) ) . '" class="inputbox" size="5" />';
		$input['file_types']							=	'<input type="text" id="file_types" name="file_types" value="' . htmlspecialchars( $plugin->params->get( 'file_types', 'zip,rar,doc,pdf,txt,xls' ) ) . '" class="inputbox" size="30" />';
		$input['file_captcha']							=	moscomprofilerHTML::yesnoSelectList( 'file_captcha', null, $plugin->params->get( 'file_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_paging']							=	moscomprofilerHTML::yesnoSelectList( 'file_paging', null, $plugin->params->get( 'file_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_limitbox']							=	moscomprofilerHTML::yesnoSelectList( 'file_limitbox', null, $plugin->params->get( 'file_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_limit']							=	'<input type="text" id="file_limit" name="file_limit" value="' . (int) $plugin->params->get( 'file_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['file_search']							=	moscomprofilerHTML::yesnoSelectList( 'file_search', null, $plugin->params->get( 'file_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_approval_paging']					=	moscomprofilerHTML::yesnoSelectList( 'file_approval_paging', null, $plugin->params->get( 'file_approval_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_approval_limitbox']				=	moscomprofilerHTML::yesnoSelectList( 'file_approval_limitbox', null, $plugin->params->get( 'file_approval_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_approval_limit']					=	'<input type="text" id="file_approval_limit" name="file_approval_limit" value="' . (int) $plugin->params->get( 'file_approval_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['file_approval_search']					=	moscomprofilerHTML::yesnoSelectList( 'file_approval_search', null, $plugin->params->get( 'file_approval_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_show']								=	moscomprofilerHTML::yesnoSelectList( 'file_show', null, $plugin->params->get( 'file_show', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_show_config']						=	moscomprofilerHTML::yesnoSelectList( 'file_show_config', null, $plugin->params->get( 'file_show_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['file_public']							=	moscomprofilerHTML::yesnoSelectList( 'file_public', null, $plugin->params->get( 'file_public', 1 ) );
		$input['file_public_config']					=	moscomprofilerHTML::yesnoSelectList( 'file_public_config', null, $plugin->params->get( 'file_public_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$listUpload										=	array();
		$listUpload[]									=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listUpload[]									=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listUpload[]									=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listUpload[]									=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['file_upload']							=	moscomprofilerHTML::selectList( $listUpload, 'file_upload', 'class="inputbox"', 'value', 'text', $plugin->params->get( 'file_upload', 1 ), 1, false, false );

		$input['file_upload_config']					=	moscomprofilerHTML::yesnoSelectList( 'file_upload_config', null, $plugin->params->get( 'file_upload_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['file_approve']							=	moscomprofilerHTML::yesnoSelectList( 'file_approve', null, $plugin->params->get( 'file_approve', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['file_approve_config']					=	moscomprofilerHTML::yesnoSelectList( 'file_approve_config', null, $plugin->params->get( 'file_approve_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$return											=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'File' ) ), 'gjIntegrationsFile' )
														.		$tabs->startPane( 'gjIntegrationsFileTabs' )
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsFileGeneral' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Auto Delete' ) . '</th>'
														.							'<td width="40%">' . $input['file_delete'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable deletion of user files on group leave.' ) . '</td>'
														.						'</tr>'
														.					'</tbody>'
														.				'</table>'
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Notifications' ) ), 'gjIntegrationsFileNotifications' )
														.				$tabs->startPane( 'gjIntegrationsFileNotificationsTabs' )
														.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsFileNotificationsGeneral' )
														.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.							'<tbody>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Notifications' ) . '</th>'
														.									'<td width="40%">' . $input['file_notifications'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Enable or disable sending and configuration of file notifications. Moderators are exempt from this configuration.' ) . '</td>'
														.								'</tr>'
														.							'</tbody>'
														.						'</table>'
														.					$tabs->endTab()
														.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsFileNotificationsDefaults' )
														.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.							'<tbody>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Upload File' ) . '</th>'
														.									'<td width="40%">' . $input['file_notifications_group_filenew'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for file notification parameter "Upload of new file".' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'File Approval' ) . '</th>'
														.									'<td width="40%">' . $input['file_notifications_group_fileapprove'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for file notification parameter "New file requires approval".' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Update File' ) . '</th>'
														.									'<td width="40%">' . $input['file_notifications_group_fileupdate'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for file notification parameter "Update of existing file".' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Delete File' ) . '</th>'
														.									'<td width="40%">' . $input['file_notifications_group_fileupdate'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for file notification parameter "Delete of existing file".' ) . '</td>'
														.								'</tr>'
														.							'</tbody>'
														.						'</table>'
														.					$tabs->endTab()
														.				$tabs->endPane()
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Upload' ) ), 'gjIntegrationsFileUpload' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Size' ) . '</th>'
														.							'<td width="600px">' . $input['file_maxsize'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Input maximum file size for files. If blank Community Builder avatar size will be used.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Types' ) . '</th>'
														.							'<td width="600px">' . $input['file_types'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Input supported file types. If blank default of zip,rar,doc,pdf,txt,xls will be used. Separate multiple types with a comma (e.g. zip,rar).' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
														.							'<td width="40%">' . $input['file_captcha'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of Captcha on File tab. Requires latest CB Captcha or integrated Captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.					'</tbody>'
														.				'</table>'
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjIntegrationsFilePaging' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
														.							'<td width="40%">' . $input['file_paging'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on files.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
														.							'<td width="50%">' . $input['file_limitbox'] . '</td>'
														.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on files. Requires Paging to be Enabled.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
														.							'<td width="50%">' . $input['file_limit'] . '</td>'
														.							'<td>' . CBTxt::T( 'Input default page limit on files. Page limit determines how many files are displayed per page.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
														.							'<td width="600px">' . $input['file_search'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on files.' ) . '</td>'
														.						'</tr>'
														.					'</tbody>'
														.				'</table>'
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Approval' ) ), 'gjIntegrationsFileApproval' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
														.							'<td width="40%">' . $input['file_approval_paging'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on files.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
														.							'<td width="50%">' . $input['file_approval_limitbox'] . '</td>'
														.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on files. Requires Paging to be Enabled.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
														.							'<td width="50%">' . $input['file_approval_limit'] . '</td>'
														.							'<td>' . CBTxt::T( 'Input default page limit on files. Page limit determines how many files are displayed per page.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
														.							'<td width="600px">' . $input['file_approval_search'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on files.' ) . '</td>'
														.						'</tr>'
														.					'</tbody>'
														.				'</table>'
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsFileDefaults' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
														.							'<td width="40%">' . $input['file_show'] . ' ' . $input['file_show_config'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Display". Additionally select the display of the "Display" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Public' ) . '</th>'
														.							'<td width="40%">' . $input['file_public'] . ' ' . $input['file_public_config'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Public". Additionally select the display of the "Public" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Upload' ) . '</th>'
														.							'<td width="600px">' . $input['file_upload'] . ' ' . $input['file_upload_config'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Upload". Additionally select the display of the "Upload" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</th>'
														.							'<td width="600px">' . $input['file_approve'] . ' ' . $input['file_approve_config'] . '</td>'
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
		$this->deleteUserFiles( $user, $group );
	}

	public function deleteUser( $user, $deleted ) {
		$this->deleteUserFiles( $user );
	}

	private function deleteUserFiles( $user, $group = null ) {
		$plugin				=	cbgjClass::getPlugin();

		if ( $plugin->params->get( 'file_delete', 0 ) ) {
			$where			=	array();

			if ( $group ) {
				$where[]	=	array( 'group', '=', (int) $group->get( 'id' ) );
			}

			$where[]		=	array( 'user_id', '=', (int) $user->id );

			$rows			=	cbgjFileData::getFiles( null, $where );

			if ( $rows ) foreach ( $rows as $row ) {
				$row->deleteAll();
			}
		}
	}

	public function getAuthorization( &$access, $category, $group, $user, $owner, $row, $plugin ) {
		if ( isset( $group->id ) && cbgjClass::hasAccess( 'grp_approved', $access ) ) {
			$params					=	$group->getParams();
			$fileShow				=	$params->get( 'file_show', $plugin->params->get( 'file_show', 1 ) );
			$filePublic				=	$params->get( 'file_public', $plugin->params->get( 'file_public', 1 ) );
			$fileUpload				=	$params->get( 'file_upload', $plugin->params->get( 'file_upload', 1 ) );

			if ( ( $filePublic || cbgjClass::hasAccess( 'mod_lvl5', $access ) ) && $fileShow ) {
				$access[]			=	'file_show';

				if ( cbgjClass::hasAccess( 'usr_notifications', $access ) && ( $plugin->params->get( 'file_notifications', 1 ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) {
					if ( ! cbgjClass::hasAccess( 'grp_usr_notifications', $access ) ) {
						$access[]	=	'grp_usr_notifications';
					}

					$access[]		=	'file_notifications';
				}
			}

			if ( $fileShow && ( ( ( $fileUpload == 0 ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) || ( ( $fileUpload == 1 ) && cbgjClass::hasAccess( 'mod_lvl4', $access ) ) || ( $fileUpload == 2 ) && cbgjClass::hasAccess( 'mod_lvl3', $access ) || ( $fileUpload == 3 ) && cbgjClass::hasAccess( 'mod_lvl2', $access ) ) ) {
				$access[]			=	'file_upload';
			}
		}
	}

	public function getNotifications( $tabs, $row, $group, $category, $user, $plugin ) {
		$authorized						=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'file_notifications', $authorized ) ) {
			$params						=	$row->getParams();

			$input						=	array();

			$input['group_filenew']		=	moscomprofilerHTML::yesnoSelectList( 'group_filenew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'file_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_filenew', $params->get( 'group_filenew', $plugin->params->get( 'file_notifications_group_filenew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_fileapprove']	=	moscomprofilerHTML::yesnoSelectList( 'group_fileapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'file_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_fileapprove', $params->get( 'group_fileapprove', $plugin->params->get( 'file_notifications_group_fileapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_fileupdate']	=	moscomprofilerHTML::yesnoSelectList( 'group_fileupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'file_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_fileupdate', $params->get( 'group_fileupdate', $plugin->params->get( 'file_notifications_group_fileupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_filedelete']	=	moscomprofilerHTML::yesnoSelectList( 'group_filedelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'file_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_filedelete', $params->get( 'group_filedelete', $plugin->params->get( 'file_notifications_group_filedelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

			$return						=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'File' ) ), 'gjNotificationsGroupFile' )
										.		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Upload of new file' ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_filenew']
										.			'</div>'
										.		'</div>';

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'file_show' ), $authorized, true ) ) {
				$return					.=		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'New file requires approval' ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_fileapprove']
										.			'</div>'
										.		'</div>';
			}

			$return						.=		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Update of existing file' ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_fileupdate']
										.			'</div>'
										.		'</div>'
										.		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Delete of existing file' ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_filedelete']
										.			'</div>'
										.		'</div>'
										.	$tabs->endTab();

			return $return;
		}
	}

	public function setNotifications( &$params, $row, $group, $category, $user, $plugin ) {
		if ( isset( $group->id ) ) {
			$authorized		=	cbgjClass::getAuthorization( $category, $group, $row->getOwner() );

			if ( cbgjClass::hasAccess( 'file_notifications', $authorized ) ) {
				$row_params	=	$row->getParams();

				$params->set( 'group_filenew', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'file_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_filenew', $row_params->get( 'group_filenew', $plugin->params->get( 'file_notifications_group_filenew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_fileapprove', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'file_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_fileapprove', $row_params->get( 'group_fileapprove', $plugin->params->get( 'file_notifications_group_fileapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_fileupdate', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'file_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_fileupdate', $row_params->get( 'group_fileupdate', $plugin->params->get( 'file_notifications_group_fileupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_filedelete', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'file_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_filedelete', $row_params->get( 'group_filedelete', $plugin->params->get( 'file_notifications_group_filedelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );
			}
		}
	}

	public function getMenu( $user, $plugin ) {
		$input					=	array();

		$input['approve_file']	=	'<input type="checkbox" id="type_approve_file" name="type[]" class="inputbox" value="approve-file" />';

		$return					=	'<tr>'
								.		'<td width="10%" style="text-align:center;">' . $input['approve_file'] . '</td>'
								.		'<th width="20%">' . CBTxt::Th( 'File Approval' ) . '</td>'
								.		'<td>' . CBTxt::Th( 'Create menu link to a file approval page.' ) . '</td>'
								.	'</tr>';

		return $return;
	}

	public function saveMenu( $type, $categories, $groups, $user, $plugin ) {
		if ( $type == 'approve-file' ) {
			if ( ! cbgjClass::setMenu( CBTxt::T( 'File Approval' ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=plugin&func=file_approval', $plugin ) ) {
				cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'File approval menu failed to create!' ), false, true, 'error' );
			}
		}
	}

	static public function getMimeMap() {
		$mimemap	=	array(	'3ds' => 'image/x-3ds',
								'BLEND' => 'application/x-blender',
								'C' => 'text/x-c++src',
								'CSSL' => 'text/css',
								'NSV' => 'video/x-nsv',
								'XM' => 'audio/x-mod',
								'Z' => 'application/x-compress',
								'a' => 'application/x-archive',
								'abw' => 'application/x-abiword',
								'abw.gz' => 'application/x-abiword',
								'ac3' => 'audio/ac3',
								'adb' => 'text/x-adasrc',
								'ads' => 'text/x-adasrc',
								'afm' => 'application/x-font-afm',
								'ag' => 'image/x-applix-graphics',
								'ai' => 'application/illustrator',
								'aif' => 'audio/x-aiff',
								'aifc' => 'audio/x-aiff',
								'aiff' => 'audio/x-aiff',
								'al' => 'application/x-perl',
								'arj' => 'application/x-arj',
								'as' => 'application/x-applix-spreadsheet',
								'asc' => 'text/plain',
								'asf' => 'video/x-ms-asf',
								'asp' => 'application/x-asp',
								'asx' => 'video/x-ms-asf',
								'au' => 'audio/basic',
								'avi' => 'video/x-msvideo',
								'aw' => 'application/x-applix-word',
								'bak' => 'application/x-trash',
								'bcpio' => 'application/x-bcpio',
								'bdf' => 'application/x-font-bdf',
								'bib' => 'text/x-bibtex',
								'bin' => 'application/octet-stream',
								'blend' => 'application/x-blender',
								'blender' => 'application/x-blender',
								'bmp' => 'image/bmp',
								'bz' => 'application/x-bzip',
								'bz2' => 'application/x-bzip',
								'c' => 'text/x-csrc',
								'c++' => 'text/x-c++src',
								'cc' => 'text/x-c++src',
								'cdf' => 'application/x-netcdf',
								'cdr' => 'application/vnd.corel-draw',
								'cer' => 'application/x-x509-ca-cert',
								'cert' => 'application/x-x509-ca-cert',
								'cgi' => 'application/x-cgi',
								'cgm' => 'image/cgm',
								'chrt' => 'application/x-kchart',
								'class' => 'application/x-java',
								'cls' => 'text/x-tex',
								'cpio' => 'application/x-cpio',
								'cpio.gz' => 'application/x-cpio-compressed',
								'cpp' => 'text/x-c++src',
								'cpt' => 'application/mac-compactpro',
								'crt' => 'application/x-x509-ca-cert',
								'cs' => 'text/x-csharp',
								'csh' => 'application/x-shellscript',
								'css' => 'text/css',
								'csv' => 'text/x-comma-separated-values',
								'cur' => 'image/x-win-bitmap',
								'cxx' => 'text/x-c++src',
								'dat' => 'video/mpeg',
								'dbf' => 'application/x-dbase',
								'dc' => 'application/x-dc-rom',
								'dcl' => 'text/x-dcl',
								'dcm' => 'image/x-dcm',
								'dcr' => 'application/x-director',
								'deb' => 'application/x-deb',
								'der' => 'application/x-x509-ca-cert',
								'desktop' => 'application/x-desktop',
								'dia' => 'application/x-dia-diagram',
								'diff' => 'text/x-patch',
								'dir' => 'application/x-director',
								'djv' => 'image/vnd.djvu',
								'djvu' => 'image/vnd.djvu',
								'dll' => 'application/octet-stream',
								'dms' => 'application/octet-stream',
								'doc' => 'application/msword',
								'dsl' => 'text/x-dsl',
								'dtd' => 'text/x-dtd',
								'dvi' => 'application/x-dvi',
								'dwg' => 'image/vnd.dwg',
								'dxf' => 'image/vnd.dxf',
								'dxr' => 'application/x-director',
								'egon' => 'application/x-egon',
								'el' => 'text/x-emacs-lisp',
								'eps' => 'image/x-eps',
								'epsf' => 'image/x-eps',
								'epsi' => 'image/x-eps',
								'etheme' => 'application/x-e-theme',
								'etx' => 'text/x-setext',
								'exe' => 'application/x-executable',
								'ez' => 'application/andrew-inset',
								'f' => 'text/x-fortran',
								'fig' => 'image/x-xfig',
								'fits' => 'image/x-fits',
								'flac' => 'audio/x-flac',
								'flc' => 'video/x-flic',
								'fli' => 'video/x-flic',
								'flw' => 'application/x-kivio',
								'fo' => 'text/x-xslfo',
								'g3' => 'image/fax-g3',
								'gb' => 'application/x-gameboy-rom',
								'gcrd' => 'text/x-vcard',
								'gen' => 'application/x-genesis-rom',
								'gg' => 'application/x-sms-rom',
								'gif' => 'image/gif',
								'glade' => 'application/x-glade',
								'gmo' => 'application/x-gettext-translation',
								'gnc' => 'application/x-gnucash',
								'gnucash' => 'application/x-gnucash',
								'gnumeric' => 'application/x-gnumeric',
								'gra' => 'application/x-graphite',
								'gsf' => 'application/x-font-type1',
								'gtar' => 'application/x-gtar',
								'gz' => 'application/x-gzip',
								'h' => 'text/x-chdr',
								'h++' => 'text/x-chdr',
								'hdf' => 'application/x-hdf',
								'hh' => 'text/x-c++hdr',
								'hp' => 'text/x-chdr',
								'hpgl' => 'application/vnd.hp-hpgl',
								'hqx' => 'application/mac-binhex40',
								'hs' => 'text/x-haskell',
								'htm' => 'text/html',
								'html' => 'text/html',
								'icb' => 'image/x-icb',
								'ice' => 'x-conference/x-cooltalk',
								'ico' => 'image/x-ico',
								'ics' => 'text/calendar',
								'idl' => 'text/x-idl',
								'ief' => 'image/ief',
								'ifb' => 'text/calendar',
								'iff' => 'image/x-iff',
								'iges' => 'model/iges',
								'igs' => 'model/iges',
								'ilbm' => 'image/x-ilbm',
								'iso' => 'application/x-cd-image',
								'it' => 'audio/x-it',
								'jar' => 'application/x-jar',
								'java' => 'text/x-java',
								'jng' => 'image/x-jng',
								'jp2' => 'image/jpeg2000',
								'jpg' => 'image/jpeg',
								'jpe' => 'image/jpeg',
								'jpeg' => 'image/jpeg',
								'jpr' => 'application/x-jbuilder-project',
								'jpx' => 'application/x-jbuilder-project',
								'js' => 'application/x-javascript',
								'kar' => 'audio/midi',
								'karbon' => 'application/x-karbon',
								'kdelnk' => 'application/x-desktop',
								'kfo' => 'application/x-kformula',
								'kil' => 'application/x-killustrator',
								'kon' => 'application/x-kontour',
								'kpm' => 'application/x-kpovmodeler',
								'kpr' => 'application/x-kpresenter',
								'kpt' => 'application/x-kpresenter',
								'kra' => 'application/x-krita',
								'ksp' => 'application/x-kspread',
								'kud' => 'application/x-kugar',
								'kwd' => 'application/x-kword',
								'kwt' => 'application/x-kword',
								'la' => 'application/x-shared-library-la',
								'latex' => 'application/x-latex',
								'lha' => 'application/x-lha',
								'lhs' => 'text/x-literate-haskell',
								'lhz' => 'application/x-lhz',
								'log' => 'text/x-log',
								'ltx' => 'text/x-tex',
								'lwo' => 'image/x-lwo',
								'lwob' => 'image/x-lwo',
								'lws' => 'image/x-lws',
								'lyx' => 'application/x-lyx',
								'lzh' => 'application/x-lha',
								'lzo' => 'application/x-lzop',
								'm' => 'text/x-objcsrc',
								'm15' => 'audio/x-mod',
								'm3u' => 'audio/x-mpegurl',
								'man' => 'application/x-troff-man',
								'md' => 'application/x-genesis-rom',
								'me' => 'text/x-troff-me',
								'mesh' => 'model/mesh',
								'mgp' => 'application/x-magicpoint',
								'mid' => 'audio/midi',
								'midi' => 'audio/midi',
								'mif' => 'application/x-mif',
								'mkv' => 'application/x-matroska',
								'mm' => 'text/x-troff-mm',
								'mml' => 'text/mathml',
								'mng' => 'video/x-mng',
								'moc' => 'text/x-moc',
								'mod' => 'audio/x-mod',
								'moov' => 'video/quicktime',
								'mov' => 'video/quicktime',
								'movie' => 'video/x-sgi-movie',
								'mp2' => 'video/mpeg',
								'mp3' => 'audio/x-mp3',
								'mpe' => 'video/mpeg',
								'mpeg' => 'video/mpeg',
								'mpg' => 'video/mpeg',
								'mpga' => 'audio/mpeg',
								'ms' => 'text/x-troff-ms',
								'msh' => 'model/mesh',
								'msod' => 'image/x-msod',
								'msx' => 'application/x-msx-rom',
								'mtm' => 'audio/x-mod',
								'mxu' => 'video/vnd.mpegurl',
								'n64' => 'application/x-n64-rom',
								'nc' => 'application/x-netcdf',
								'nes' => 'application/x-nes-rom',
								'nsv' => 'video/x-nsv',
								'o' => 'application/x-object',
								'obj' => 'application/x-tgif',
								'oda' => 'application/oda',
								'odb' => 'application/vnd.oasis.opendocument.database',
								'odc' => 'application/vnd.oasis.opendocument.chart',
								'odf' => 'application/vnd.oasis.opendocument.formula',
								'odg' => 'application/vnd.oasis.opendocument.graphics',
								'odi' => 'application/vnd.oasis.opendocument.image',
								'odm' => 'application/vnd.oasis.opendocument.text-master',
								'odp' => 'application/vnd.oasis.opendocument.presentation',
								'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
								'odt' => 'application/vnd.oasis.opendocument.text',
								'ogg' => 'application/ogg',
								'old' => 'application/x-trash',
								'oleo' => 'application/x-oleo',
								'otg' => 'application/vnd.oasis.opendocument.graphics-template',
								'oth' => 'application/vnd.oasis.opendocument.text-web',
								'otp' => 'application/vnd.oasis.opendocument.presentation-template',
								'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
								'ott' => 'application/vnd.oasis.opendocument.text-template',
								'p' => 'text/x-pascal',
								'p12' => 'application/x-pkcs12',
								'p7s' => 'application/pkcs7-signature',
								'pas' => 'text/x-pascal',
								'patch' => 'text/x-patch',
								'pbm' => 'image/x-portable-bitmap',
								'pcd' => 'image/x-photo-cd',
								'pcf' => 'application/x-font-pcf',
								'pcf.Z' => 'application/x-font-type1',
								'pcl' => 'application/vnd.hp-pcl',
								'pdb' => 'application/vnd.palm',
								'pdf' => 'application/pdf',
								'pem' => 'application/x-x509-ca-cert',
								'perl' => 'application/x-perl',
								'pfa' => 'application/x-font-type1',
								'pfb' => 'application/x-font-type1',
								'pfx' => 'application/x-pkcs12',
								'pgm' => 'image/x-portable-graymap',
								'pgn' => 'application/x-chess-pgn',
								'pgp' => 'application/pgp',
								'php' => 'application/x-php',
								'php3' => 'application/x-php',
								'php4' => 'application/x-php',
								'pict' => 'image/x-pict',
								'pict1' => 'image/x-pict',
								'pict2' => 'image/x-pict',
								'pl' => 'application/x-perl',
								'pls' => 'audio/x-scpls',
								'pm' => 'application/x-perl',
								'png' => 'image/png',
								'pnm' => 'image/x-portable-anymap',
								'po' => 'text/x-gettext-translation',
								'pot' => 'application/vnd.ms-powerpoint',
								'ppm' => 'image/x-portable-pixmap',
								'pps' => 'application/vnd.ms-powerpoint',
								'ppt' => 'application/vnd.ms-powerpoint',
								'ppz' => 'application/vnd.ms-powerpoint',
								'ps' => 'application/postscript',
								'ps.gz' => 'application/x-gzpostscript',
								'psd' => 'image/x-psd',
								'psf' => 'application/x-font-linux-psf',
								'psid' => 'audio/prs.sid',
								'pw' => 'application/x-pw',
								'py' => 'application/x-python',
								'pyc' => 'application/x-python-bytecode',
								'pyo' => 'application/x-python-bytecode',
								'qif' => 'application/x-qw',
								'qt' => 'video/quicktime',
								'qtvr' => 'video/quicktime',
								'ra' => 'audio/x-pn-realaudio',
								'ram' => 'audio/x-pn-realaudio',
								'rar' => 'application/x-rar',
								'ras' => 'image/x-cmu-raster',
								'rdf' => 'text/rdf',
								'rej' => 'application/x-reject',
								'rgb' => 'image/x-rgb',
								'rle' => 'image/rle',
								'rm' => 'audio/x-pn-realaudio',
								'roff' => 'application/x-troff',
								'rpm' => 'application/x-rpm',
								'rss' => 'text/rss',
								'rtf' => 'application/rtf',
								'rtx' => 'text/richtext',
								's3m' => 'audio/x-s3m',
								'sam' => 'application/x-amipro',
								'scm' => 'text/x-scheme',
								'sda' => 'application/vnd.stardivision.draw',
								'sdc' => 'application/vnd.stardivision.calc',
								'sdd' => 'application/vnd.stardivision.impress',
								'sdp' => 'application/vnd.stardivision.impress',
								'sds' => 'application/vnd.stardivision.chart',
								'sdw' => 'application/vnd.stardivision.writer',
								'sgi' => 'image/x-sgi',
								'sgl' => 'application/vnd.stardivision.writer',
								'sgm' => 'text/sgml',
								'sgml' => 'text/sgml',
								'sh' => 'application/x-shellscript',
								'shar' => 'application/x-shar',
								'shtml' => 'text/html',
								'siag' => 'application/x-siag',
								'sid' => 'audio/prs.sid',
								'sik' => 'application/x-trash',
								'silo' => 'model/mesh',
								'sit' => 'application/x-stuffit',
								'skd' => 'application/x-koan',
								'skm' => 'application/x-koan',
								'skp' => 'application/x-koan',
								'skt' => 'application/x-koan',
								'slk' => 'text/spreadsheet',
								'smd' => 'application/vnd.stardivision.mail',
								'smf' => 'application/vnd.stardivision.math',
								'smi' => 'application/smil',
								'smil' => 'application/smil',
								'sml' => 'application/smil',
								'sms' => 'application/x-sms-rom',
								'snd' => 'audio/basic',
								'so' => 'application/x-sharedlib',
								'spd' => 'application/x-font-speedo',
								'spl' => 'application/x-futuresplash',
								'sql' => 'text/x-sql',
								'src' => 'application/x-wais-source',
								'stc' => 'application/vnd.sun.xml.calc.template',
								'std' => 'application/vnd.sun.xml.draw.template',
								'sti' => 'application/vnd.sun.xml.impress.template',
								'stm' => 'audio/x-stm',
								'stw' => 'application/vnd.sun.xml.writer.template',
								'sty' => 'text/x-tex',
								'sun' => 'image/x-sun-raster',
								'sv4cpio' => 'application/x-sv4cpio',
								'sv4crc' => 'application/x-sv4crc',
								'svg' => 'image/svg+xml',
								'swf' => 'application/x-shockwave-flash',
								'sxc' => 'application/vnd.sun.xml.calc',
								'sxd' => 'application/vnd.sun.xml.draw',
								'sxg' => 'application/vnd.sun.xml.writer.global',
								'sxi' => 'application/vnd.sun.xml.impress',
								'sxm' => 'application/vnd.sun.xml.math',
								'sxw' => 'application/vnd.sun.xml.writer',
								'sylk' => 'text/spreadsheet',
								't' => 'application/x-troff',
								'tar' => 'application/x-tar',
								'tar.Z' => 'application/x-tarz',
								'tar.bz' => 'application/x-bzip-compressed-tar',
								'tar.bz2' => 'application/x-bzip-compressed-tar',
								'tar.gz' => 'application/x-compressed-tar',
								'tar.lzo' => 'application/x-tzo',
								'tcl' => 'text/x-tcl',
								'tex' => 'text/x-tex',
								'texi' => 'text/x-texinfo',
								'texinfo' => 'text/x-texinfo',
								'tga' => 'image/x-tga',
								'tgz' => 'application/x-compressed-tar',
								'theme' => 'application/x-theme',
								'tif' => 'image/tiff',
								'tiff' => 'image/tiff',
								'tk' => 'text/x-tcl',
								'torrent' => 'application/x-bittorrent',
								'tr' => 'application/x-troff',
								'ts' => 'application/x-linguist',
								'tsv' => 'text/tab-separated-values',
								'ttf' => 'application/x-font-ttf',
								'txt' => 'text/plain',
								'tzo' => 'application/x-tzo',
								'ui' => 'application/x-designer',
								'uil' => 'text/x-uil',
								'ult' => 'audio/x-mod',
								'uni' => 'audio/x-mod',
								'uri' => 'text/x-uri',
								'url' => 'text/x-uri',
								'ustar' => 'application/x-ustar',
								'vcd' => 'application/x-cdlink',
								'vcf' => 'text/x-vcalendar',
								'vcs' => 'text/x-vcalendar',
								'vct' => 'text/x-vcard',
								'vfb' => 'text/calendar',
								'vob' => 'video/mpeg',
								'voc' => 'audio/x-voc',
								'vor' => 'application/vnd.stardivision.writer',
								'vrml' => 'model/vrml',
								'vsd' => 'application/vnd.visio',
								'wav' => 'audio/x-wav',
								'wax' => 'audio/x-ms-wax',
								'wb1' => 'application/x-quattropro',
								'wb2' => 'application/x-quattropro',
								'wb3' => 'application/x-quattropro',
								'wbmp' => 'image/vnd.wap.wbmp',
								'wbxml' => 'application/vnd.wap.wbxml',
								'wk1' => 'application/vnd.lotus-1-2-3',
								'wk3' => 'application/vnd.lotus-1-2-3',
								'wk4' => 'application/vnd.lotus-1-2-3',
								'wks' => 'application/vnd.lotus-1-2-3',
								'wm' => 'video/x-ms-wm',
								'wma' => 'audio/x-ms-wma',
								'wmd' => 'application/x-ms-wmd',
								'wmf' => 'image/x-wmf',
								'wml' => 'text/vnd.wap.wml',
								'wmlc' => 'application/vnd.wap.wmlc',
								'wmls' => 'text/vnd.wap.wmlscript',
								'wmlsc' => 'application/vnd.wap.wmlscriptc',
								'wmv' => 'video/x-ms-wmv',
								'wmx' => 'video/x-ms-wmx',
								'wmz' => 'application/x-ms-wmz',
								'wpd' => 'application/wordperfect',
								'wpg' => 'application/x-wpg',
								'wri' => 'application/x-mswrite',
								'wrl' => 'model/vrml',
								'wvx' => 'video/x-ms-wvx',
								'xac' => 'application/x-gnucash',
								'xbel' => 'application/x-xbel',
								'xbm' => 'image/x-xbitmap',
								'xcf' => 'image/x-xcf',
								'xcf.bz2' => 'image/x-compressed-xcf',
								'xcf.gz' => 'image/x-compressed-xcf',
								'xht' => 'application/xhtml+xml',
								'xhtml' => 'application/xhtml+xml',
								'xi' => 'audio/x-xi',
								'xls' => 'application/vnd.ms-excel',
								'xla' => 'application/vnd.ms-excel',
								'xlc' => 'application/vnd.ms-excel',
								'xld' => 'application/vnd.ms-excel',
								'xll' => 'application/vnd.ms-excel',
								'xlm' => 'application/vnd.ms-excel',
								'xlt' => 'application/vnd.ms-excel',
								'xlw' => 'application/vnd.ms-excel',
								'xm' => 'audio/x-xm',
								'xml' => 'text/xml',
								'xpm' => 'image/x-xpixmap',
								'xsl' => 'text/x-xslt',
								'xslfo' => 'text/x-xslfo',
								'xslt' => 'text/x-xslt',
								'xwd' => 'image/x-xwindowdump',
								'xyz' => 'chemical/x-xyz',
								'zabw' => 'application/x-abiword',
								'zip' => 'application/zip',
								'zoo' => 'application/x-zoo',
								'123' => 'application/vnd.lotus-1-2-3',
								'669' => 'audio/x-mod'
							);

		return $mimemap;
	}
}

class cbgjFile extends comprofilerDBTable {
	var $id				=	null;
	var $published		=	null;
	var $user_id		=	null;
	var $group			=	null;
	var $title			=	null;
	var $file			=	null;
	var $description	=	null;
	var $date			=	null;

	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_plugin_file', 'id', $db );
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
			$_PLUGINS->trigger( 'gjint_onBeforeUpdateFile', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gjint_onBeforeCreateFile', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onAfterUpdateFile', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );

			$_PLUGINS->trigger( 'gjint_onAfterCreateFile', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		return true;
	}

	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeDeleteFile', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterDeleteFile', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function storeState( $state ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateFileState', array( &$state, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'published', (int) $state );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdateFileState', array( $this->get( 'published' ), $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function deleteAll() {
		global $_CB_framework;

		$imagePath		=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/cbgroupjivefile';
		$categoryPath	=	$imagePath . '/' . (int) $this->getCategory()->get( 'id' );
		$groupPath		=	$categoryPath . '/' . (int) $this->get( 'group' );

		if ( $this->get( 'file' ) ) {
			if ( file_exists( $groupPath . '/' . $this->getCleanFile() ) ) {
				@unlink( $groupPath . '/' . $this->getCleanFile() );
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

	public function setFile( $file ) {
		global $_CB_framework, $ueConfig;

		if ( isset( $_FILES[$file]['tmp_name'] ) && ! empty( $_FILES[$file]['tmp_name'] ) && ( $_FILES[$file]['error'] == 0 ) && ( is_uploaded_file( $_FILES[$file]['tmp_name'] ) ) ) {
			$plugin										=	cbgjClass::getPlugin();
			$fileMaxsize								=	$plugin->params->get( 'file_maxsize', 2000 );
			$fileTypes									=	$plugin->params->get( 'file_types', 'zip,rar,doc,pdf,txt,xls' );
			$imagePath									=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/cbgroupjivefile';
			$categoryPath								=	$imagePath . '/' . (int) $this->getCategory()->get( 'id' );
			$groupPath									=	$categoryPath . '/' . (int) $this->get( 'group' );
			$mode										=	cbgjClass::getFilePerms();

			cbgjClass::createFolderPath( $imagePath, $categoryPath, $groupPath );

			$maxsize									=	( $fileMaxsize ? $fileMaxsize : $ueConfig['avatarSize'] );
			$types										=	explode( ',', ( $fileTypes ? $fileTypes : 'zip,rar,doc,pdf,txt,xls' ) );
			$matches									=	null;
			$uploadedFile								=	preg_match( '/(.+)\.([a-zA-Z0-9]+)$/i', $_FILES[$file]['name'], $matches );

			if ( $uploadedFile ) {
				if ( isset( $matches[1] ) ) {
					$uploadedName						=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $matches[1] );
				} else {
					$this->set( '_error', CBTxt::T( 'Please select a file before uploading' ) );
				}

				if ( isset( $matches[2] ) ) {
					$uploadedExt						=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $matches[2] );
				} else {
					$this->set( '_error', CBTxt::P( 'Please upload only [types]', array( '[types]' => implode( ',', $types ) ) ) );
				}
			} else {
				$this->set( '_error', CBTxt::T( 'Please select a file before uploading' ) );
			}

			if ( ! $this->getError() ) {
				if ( ( $_FILES[$file]['size'] / 1024 ) > $maxsize ) {
					$this->set( '_error', CBTxt::P( 'The file size exceeds the maximum of [size] KB', array( '[size]' => $maxsize ) ) );
				} elseif ( ! in_array( $uploadedExt, $types ) ) {
					$this->set( '_error', CBTxt::P( 'Please upload only [types]', array( '[types]' => implode( ',', $types ) ) ) );
				} else {
					if ( in_array( $uploadedExt, array( 'php', 'js' ) ) ) {
						$uploadedExt					=	$uploadedExt . '.txt';
					}

					$newFileName						=	preg_replace( '/[^-a-zA-Z0-9_]/', '', uniqid( $uploadedName . '_' ) ) . '.' . $uploadedExt;

					if ( ! $newFileName ) {
						$this->set( '_error', CBTxt::T( 'Please select a file before uploading' ) );
					}
				}

				if ( ! $this->getError() ) {
					if ( $this->get( 'file' ) ) {
						if ( file_exists( $groupPath . '/' . $this->getCleanFile() ) ) {
							@unlink( $groupPath . '/' . $this->getCleanFile() );
						}
					}

					if ( in_array( $uploadedExt, array( 'jpg', 'jpeg', 'gif', 'png' ) ) ) {
						$allwaysResize					=	( isset( $ueConfig['avatarResizeAlways'] ) ? $ueConfig['avatarResizeAlways'] : 1 );
						$fileNameInDir					=	preg_replace( '/[^-a-zA-Z0-9_]/', '', uniqid( $uploadedName . '_' ) );

						$imgToolBox						=	new imgToolBox();
						$imgToolBox->_conversiontype	=	$ueConfig['conversiontype'];
						$imgToolBox->_IM_path			=	$ueConfig['im_path'];
						$imgToolBox->_NETPBM_path		=	$ueConfig['netpbm_path'];
						$imgToolBox->_maxsize			=	$maxsize;
						$imgToolBox->_maxwidth			=	32000;
						$imgToolBox->_maxheight			=	32000;
						$imgToolBox->_thumbwidth		=	100;
						$imgToolBox->_thumbheight		=	100;
						$imgToolBox->_debug				=	0;

						$newFileName					=	$imgToolBox->processImage( $_FILES[$file], $fileNameInDir, $groupPath . '/', 0, 0, 1, $allwaysResize );

						if ( $newFileName ) {
							if ( file_exists( $groupPath . '/tn' . $newFileName ) ) {
								@unlink( $groupPath . '/tn' . $newFileName );
							}

							$this->set( 'file', $newFileName );

							@chmod( $groupPath . '/' . $this->getCleanFile(), $mode );
						} else {
							$this->set( '_error', CBTxt::T( str_replace( 'Error: ', '', $imgToolBox->_errMSG ) ) );
						}
					} else {
						if ( ! @move_uploaded_file( $_FILES[$file]['tmp_name'], $groupPath . '/' . $newFileName ) ) {
							$this->set( '_error', CBTxt::T( 'The file failed to upload' ) );
						} else {
							$this->set( 'file', $newFileName );

							@chmod( $groupPath . '/' . $this->getCleanFile(), $mode );
						}
					}
				}
			}
		}

		if ( ( ! $this->get( 'file' ) ) && ( ! $this->getError() ) ) {
			$this->set( '_error', CBTxt::T( 'File not specified!' ) );
		}
	}

	public function setPathway( $title, $url = null ) {
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

	public function getCleanFile() {
		return preg_replace( '/[^-a-zA-Z0-9_.]/', '', $this->file );
	}

	public function getFilePath( $absolute = false ) {
		global $_CB_framework;

		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	( $this->get( 'file' ) ? '/images/comprofiler/cbgroupjivefile/' . (int) $this->getCategory()->get( 'id' ) . '/' . (int) $this->get( 'group' ) . '/' . $this->getCleanFile() : null );
		}

		$path			=	$cache[$id];

		if ( $path ) {
			if ( $absolute ) {
				$path	=	$_CB_framework->getCfg( 'absolute_path' ) . $path;
			} else {
				$path	=	$_CB_framework->getCfg( 'live_site' ) . $path;
			}
		}

		return $path;
	}

	public function getIcon( $html = false, $linked = false ) {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$plugin			=	cbgjClass::getPlugin();
			$iconUrl		=	$plugin->livePath . '/plugins/cbgroupjivefile/images/' . $this->getFileExt() . '.png';

			if ( ! file_exists( $plugin->absPath . '/plugins/cbgroupjivefile/images/' . $this->getFileExt() . '.png' ) ) {
				$iconUrl	=	$plugin->livePath . '/plugins/cbgroupjivefile/images/default.png';
			}

			$cache[$id]		=	$iconUrl;
		}

		$icon				=	$cache[$id];

		if ( $icon ) {
			if ( $html ) {
				$icon		=	'<img alt="' . $this->getFileExt() . '" src="' . htmlspecialchars( $icon ) . '" class="img-polaroid" />';
			}

			if ( $linked ) {
				if ( $linked === 'tab' ) {
					$url	=	$this->getUrl();
				} else {
					$url	=	$this->getDownloadUrl();
				}

				$icon		=	'<a href="' . $url . '">' . $icon . '</a>';
			}
		}

		return $icon;
	}

	public function getFileName() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	( $this->get( 'file' ) ? cbIsoUtf_substr( rtrim( pathinfo( $this->getCleanFile(), PATHINFO_BASENAME ), '.' . $this->getFileExt() ), 0, -14 ) . '.' . $this->getFileExt() : null );
		}

		return $cache[$id];
	}

	public function getFileExt() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	( $this->get( 'file' ) ? strtolower( pathinfo( $this->getCleanFile(), PATHINFO_EXTENSION ) ) : null );
		}

		return $cache[$id];
	}

	public function getFileMime() {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$file_ext		=	$this->getFileExt();

			if ( $file_ext ) {
				$mimemap	=	cbgjFilePlugin::getMimeMap();
				$mime		=	( array_key_exists( $file_ext, $mimemap ) ? $mimemap[$file_ext] : 'x-extension/' . $file_ext );
			} else {
				$mime		=	'application/octet-stream';
			}

			$cache[$id]		=	$mime;
		}

		return $cache[$id];
	}

	public function getFileSize() {
		static $cache			=	array();

		$id						=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$size				=	CBTxt::Th( 'Unknown' );

			if ( $this->get( 'file' ) ) {
				$file			=	$this->getFilePath( true );

				if ( file_exists( $file ) ) {
					$size_b		=	filesize( $file );
					$size_kb	=	( $size_b / 1024 );
					$size_mb	=	( $size_kb / 1024 );
					$size_gb	=	( $size_mb / 1024 );

					if ( floor( $size_gb ) > 0 ) {
						$size	=	CBTxt::Ph( '[size] GB', array( '[size]' => round( $size_gb, 2 ) ) );
					} elseif ( floor( $size_mb ) > 0 ) {
						$size	=	CBTxt::Ph( '[size] MB', array( '[size]' => round( $size_mb, 2 ) ) );
					} elseif ( floor( $size_kb ) > 0 ) {
						$size	=	CBTxt::Ph( '[size] KB', array( '[size]' => round( $size_kb, 2 ) ) );
					} else {
						$size	=	CBTxt::Ph( '[size] B', array( '[size]' => round( $size_b, 2 ) ) );
					}
				}
			}

			$cache[$id]			=	$size;
		}

		return $cache[$id];
	}

	public function getDownloadUrl( $js = false ) {
		static $cache	=	array();

		$id				=	cbgjClass::getStaticID( array( $this->get( 'id' ), $js ) );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::getPluginURL( array( 'plugin', 'file_download', (int) $this->getCategory()->get( 'id' ), (int) $this->get( 'group' ), (int) $this->get( 'id' ) ), ( $js ? true : null ) );
		}

		return $cache[$id];
	}

	public function getUrl() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->getCategory()->get( 'id' ), (int) $this->get( 'group' ), null, array( 'tab' => htmlspecialchars( CBTxt::T( 'File' ) ) ) ) );
		}

		return $cache[$id];
	}

	public function getTitle( $length = 0, $linked = false ) {
		static $cache		=	array();

		$id					=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]		=	( $this->get( 'title' ) ? htmlspecialchars( $this->get( 'title' ) ) : $this->getFileName() );
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
					$url	=	$this->getDownloadUrl();
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
		$extras				=	array(	'file_id' => $this->get( 'id' ),
										'file_name' => $this->getFileName(),
										'file_extension' => $this->getFileExt(),
										'file_mime' => $this->getFileMime(),
										'file_size' => $this->getFileSize(),
										'file_icon' => $this->getIcon( true ),
										'file_icon_linked' => $this->getIcon( true, 'tab' ),
										'file_title' => $this->getTitle(),
										'file_title_linked' => $this->getTitle( 0, 'tab' ),
										'file_description' => $this->getDescription(),
										'file_url' => $this->getUrl(),
										'file_download_url' => $this->getDownloadUrl(),
										'file_date' => cbFormatDate( $this->get( 'date' ), 1, false ),
										'file_owner' => $this->getOwnerName(),
										'file_owner_linked' => $this->getOwnerName( true ),
										'file_published' => $this->get( 'published' ),
										'file_user_id' => $this->get( 'user_id' ),
										'file_group' => $this->get( 'group' )
									);

		if ( $cbtxt ) foreach ( $extras as $k => $v ) {
			$extras["[$k]"]	=	$v;

			unset( $extras[$k] );
		}

		return $extras;
	}
}

class cbgjFileData {

    /**
     * prepare array of user objects
     *
     * @param array $access
     * @param array $filtering
     * @param array $ordering
     * @param int $limits
     * @param bool $list
     * @return array|cbgjFile
     */
    static public function getFiles( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
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

        $id					=	cbgjClass::getStaticID( array( $filtering, $ordering, $limits ) );

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
                .	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_plugin_file' ) . " AS a";

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
            $cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjFile', array( & $_CB_database ) );
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
                $rows		=	new cbgjFile( $_CB_database );
            }

            return $rows;
        }
    }
}
?>