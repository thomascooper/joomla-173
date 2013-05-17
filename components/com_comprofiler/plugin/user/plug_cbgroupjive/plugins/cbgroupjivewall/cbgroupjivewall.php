<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $_PLUGINS;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

$_PLUGINS->registerFunction( 'gj_onBeforeGroupTab', 'getWall', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteGroup', 'deletePosts', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupEdit', 'getParam', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onConfigIntegrations', 'getConfig', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onPluginFE', 'getPluginFE', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateGroup', 'setParam', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateGroup', 'setParam', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteUser', 'leaveGroup', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'deleteUser', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onAuthorization', 'getAuthorization', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupNotifications', 'getNotifications', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onBeforeUpdateNotificationParams', 'setNotifications', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsGeneral', 'getMenu', 'cbgjWallPlugin' );
$_PLUGINS->registerFunction( 'gj_onMenusIntegrationsSave', 'saveMenu', 'cbgjWallPlugin' );

class cbgjWallPlugin extends cbPluginHandler {

	public function getPluginFE( $params, $user, $plugin ) {
		if ( $params[1] && $params[2] ) {
			switch ( $params[0] ) {
				case 'wall_publish':
					$this->stateWall( $params[1], $params[2], $params[3], 1, $user, $plugin );
					break;
				case 'wall_unpublish':
					$this->stateWall( $params[1], $params[2], $params[3], 0, $user, $plugin );
					break;
				case 'wall_edit':
					$this->editWall( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'wall_save':
					cbSpoofCheck( 'plugin' );
					$this->saveWall( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'wall_delete':
					$this->deleteWall( $params[1], $params[2], $params[3], $user, $plugin );
					break;
			}
		} else {
			switch ( $params[0] ) {
				case 'wall_approval':
					$this->getWallApproval( $user, $plugin );
					break;
			}
		}
	}

	public function getWallApproval( $user, $plugin ) {
		cbgjClass::getTemplate( 'cbgroupjivewall_approval' );

		$paging				=	new cbgjPaging( 'wall_approval' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'wall_approval_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'post', 'CONTAINS', $search );
		}

		$searching			=	( count( $where ) ? true : false );

		$where[]			=	array( 'published', '=', -1, 'c.params', 'CONTAINS', 'wall_approve=1' );

		$total				=	count( cbgjWallData::getPosts( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjWallData::getPosts( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where, null, ( $plugin->params->get( 'wall_approval_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::T( 'Search Wall...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjivewallapproval' ) ) {
			HTML_cbgroupjivewallapproval::showWallApproval( $rows, $pageNav, $user, $plugin );
		} else {
			$this->showWallApproval( $rows, $pageNav, $user, $plugin );
		}
	}

	private function showWallApproval( $rows, $pageNav, $user, $plugin ) {
		global $_CB_framework;

		$generalTitle			=	$plugin->params->get( 'general_title', $plugin->name );

		$_CB_framework->setPageTitle( CBTxt::T( 'Wall Approval' ) );

		if ( $generalTitle != '' ) {
			$_CB_framework->appendPathWay( htmlspecialchars( CBTxt::T( $generalTitle ) ), cbgjClass::getPluginURL() );
		}

		$_CB_framework->appendPathWay( CBTxt::T( 'Wall Approval' ), cbgjClass::getPluginURL( array( 'plugin', 'wall_approval' ) ) );

		$wallApprovalSearch		=	$plugin->params->get( 'wall_approval_search', 1 );
		$wallApprovalPaging		=	$plugin->params->get( 'wall_approval_paging', 1 );
		$wallApprovalLimitbox	=	$plugin->params->get( 'wall_approval_limitbox', 1 );

		$return					=	'<div class="gjWallApproval">'
								.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_approval' ) ) . '" method="post" name="gjForm_wall" id="gjForm_wall" class="gjForm">'
								.			( $wallApprovalSearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		if ( $rows ) foreach ( $rows as $row ) {
			$category			=	$row->getCategory();
			$group				=	$row->getGroup();
			$authorized			=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

			$return				.=			'<div class="gjContent row-fluid">'
								.				'<div class="gjContentLogo span2">'
								.					'<div class="gjContentLogoRow">' . $row->getOwnerName( true ) . '</div>'
								.					'<div class="gjContentLogoRow">' . $row->getOwnerAvatar( true ) . '</div>'
								.					'<div class="gjContentLogoRow">' . $row->getOwnerOnline() . '</div>'
								.				'</div>'
								.				'<div class="gjContentBody mini-layout span10">'
								.					'<div class="gjContentBodyHeader row-fluid">'
								.						'<div class="gjContentBodyTitle span9"><h5>#' . (int) $row->get( 'id' ) . '<small> ' . cbFormatDate( $row->get( 'date' ) ) . ' - ' . $category->getName( 0, true ) . ' - ' . $group->getName( 0, true ) . '</small></h5></div>'
								.						'<div class="gjContentBodyMenu span3">';

			if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
				$return			.=							'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true, true, false, null, true ) . '" />';
			}

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) ) {
				$menuItems		=	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, true ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
								.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this wall?' ), true, false, null, true ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

				$return			.=							cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) );
			}

			$return				.=						'</div>'
								.					'</div>'
								.					'<div class="gjContentBodyInfo">' . ( $row->getPost() ? '<div class="well well-small">' . $row->getPost() . '</div>' : null ) . '</div>'
								.				'</div>'
								.			'</div>';
		} else {
			$return				.=			'<div class="gjContent">';

			if ( $wallApprovalSearch && $pageNav->searching ) {
				$return			.=				CBTxt::Th( 'No wall search results found.' );
			} else {
				$return			.=				CBTxt::Th( 'There are no wall posts pending approval.' );
			}

			$return				.=			'</div>';
		}

		if ( $wallApprovalPaging ) {
			$return				.=			'<div class="gjPaging pagination pagination-centered">'
								.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
								.				( ! $wallApprovalLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
								.			'</div>';
		}

		$return					.=			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>';

		echo $return;
	}

	public function getWall( $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$authorized			=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( ! cbgjClass::hasAccess( 'wall_show', $authorized ) ) {
			return;
		}

		cbgjClass::getTemplate( array( 'cbgroupjivewall', 'jquery_inputlimit' ) );

		if ( $plugin->params->get( 'general_validate', 1 ) ) {
			cbgjClass::getFormValidation( '#gjForm_wallnew' );
		}

		$paging				=	new cbgjPaging( 'wall' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'wall_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'post', 'CONTAINS', $search );
		}

		$searching			=	( count( $where ) ? true : false );

		if ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
			$where[]		=	array( 'published', '=', 1, array( 'user_id', '=', (int) $user->id ) );
		}

		$where[]			=	array( 'group', '=', (int) $group->get( 'id' ) );
		$where[]			=	array( 'reply', '=', 0 );

		$total				=	count( cbgjWallData::getPosts( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjWallData::getPosts( null, $where, null, ( $plugin->params->get( 'wall_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm_wall', 'search', CBTxt::T( 'Search Wall...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( $plugin->params->get( 'wall_reply', 1 ) ) {
			$cancelReply	=	'<span class="gjWallReplyCancel">&nbsp;<a href="#gjWallToggle" role="button" class="gjButton gjButtonCancel btn btn-mini gjToggleCollapse">' . addslashes( CBTxt::T( 'Cancel Reply' ) ) . '</a></span>';

			$replyJs		=	"$( '.gjWallReply' ).click( function() {"
							.		"$( '#wall_reply' ).attr( 'value', $( this ).attr( 'rel' ) );"
							.		"$( '#gjForm_wallnew .gjButtonWrapper > .gjToggleCollapse' ).hide();"
							.		"$( '#gjWallToggle:visible' ).trigger( 'click' );"
							.		"$( '#gjForm_wallnew #wall_post' ).focus();"
							.		"if ( ! $( '.gjWallReplyCancel' ).length ) {"
							.			"$( '#gjForm_wallnew .gjButtonSubmit' ).after( '$cancelReply' );"
							.		"}"
							.	"});"
							.	"$( '.gjWallReplyCancel a' ).live( 'click', function() {"
							.		"$( '.gjWallReplyCancel' ).remove();"
							.		"$( '#gjForm_wallnew .gjToggleCollapse' ).show();"
							.		"$( '#wall_reply' ).attr( 'value', 0 );"
							.	"});";

			$_CB_framework->outputCbJQuery( $replyJs );
		}

		if ( class_exists( 'HTML_cbgroupjivewall' ) ) {
			return HTML_cbgroupjivewall::showWall( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		} else {
			return $this->showWall( $rows, $pageNav, $tabs, $group, $category, $user, $plugin );
		}
	}

	private function showWall( $rows, $pageNav, $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$wallSearch					=	( $plugin->params->get( 'wall_search', 1 ) && ( $pageNav->searching || $pageNav->total ) );
		$wallPaging					=	$plugin->params->get( 'wall_paging', 1 );
		$wallLimitbox				=	$plugin->params->get( 'wall_limitbox', 1 );
		$wallEditor					=	$plugin->params->get( 'wall_editor', 1 );
		$wallReply					=	$plugin->params->get( 'wall_reply', 1 );
		$wallReplytoggle			=	$plugin->params->get( 'wall_replytoggle', 2 );
		$inputLimit					=	$plugin->params->get( 'wall_inputlimit', 150 );
		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user );
		$wallToggle					=	( ( $plugin->params->get( 'group_toggle', 3 ) > 1 ) && cbgjClass::hasAccess( 'wall_post', $authorized ) );

		$params						=	$group->getParams();
		$wallApprove				=	$params->get( 'wall_approve', $plugin->params->get( 'wall_approve', 0 ) );

		$return						=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Wall' ) ), 'gjIntegrationsWall' )
									.	'<div class="gjWall">';

		if ( cbgjClass::hasAccess( 'wall_post', $authorized ) ) {
			if ( $plugin->params->get( 'wall_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha			=	cbgjCaptcha::render();
			} else {
				$captcha			=	false;
			}

			if ( $inputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				HTML_groupjiveInputLimit::loadJquery( '#wall_post', '#wall_inputlimit', $inputLimit );
			}

			$input					=	array();

			$input['publish']		=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'wall_published', ( $params->get( 'wall_approve', $plugin->params->get( 'wall_approve', 0 ) ) ? 0 : 1 ) ) );

			if ( $wallEditor >= 2 ) {
				$post				=	cbgjClass::getHTMLCleanParam( true, 'wall_post' );
			} else {
				$post				=	cbgjClass::getCleanParam( true, 'wall_post' );
			}

			if ( $wallEditor == 3 ) {
				$input['post']		=	$_CB_framework->displayCmsEditor( 'wall_post', $post, 400, 200, 40, 6 );
			} else {
				$input['post']		=	'<textarea id="wall_post" name="wall_post" class="input-xlarge required" cols="40" rows="6">' . htmlspecialchars( $post ) . '</textarea>';
			}

			$return					.=		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '" method="post" name="gjForm_wallnew" id="gjForm_wallnew" class="gjForm gjToggle form-horizontal">';

			if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
				$return				.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['publish']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of wall post. Unpublished wall posts will not be visible to the public.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';
			}

			$return					.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Post' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['post']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.						cbgjClass::getIcon( CBTxt::T( 'Input wall post.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';

			if ( $inputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$return				.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Input Limit' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					'<input type="text" id="wall_inputlimit" name="wall_inputlimit" value="' . (int) $inputLimit . '" class="input-small" size="7" disabled="disabled" />'
									.				'</div>'
									.			'</div>';
			}

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
									.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Create Post' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
									.				( $wallToggle ? '<a href="#gjWallToggle" role="button" class="gjButton gjButtonCancel btn btn-mini gjToggleCollapse">' . CBTxt::Th( 'Cancel' ) . '</a>' : null )
									.			'</div>'
									.			'<input type="hidden" id="wall_reply" name="wall_reply" value="' . (int) cbgjClass::getCleanParam( true, 'wall_reply', null, 0 ) . '" />'
									.			cbGetSpoofInputTag( 'plugin' )
									.		'</form>';
		}

		if ( $wallReplytoggle > 1 ) {
			$replytoggleJs			=	"$( '.gjWallRepliesCollapse' ).click( function() {"
									.		"var tab = $( this );"
									.		"tab.parents( '.gjContent' ).next( '.gjWallReplies' ).toggle( 'slow', function() {"
									.			"tab.parent().children( '.gjWallRepliesCollapse' ).hide();"
									.			"tab.parent().children( '.gjWallRepliesExpand' ).show();"
									.		"});"
									.	"});"
									.	"$( '.gjWallRepliesExpand' ).click( function() {"
									.		"var tab = $( this );"
									.		"tab.parents( '.gjContent' ).next( '.gjWallReplies' ).toggle( 'slow', function() {"
									.			"tab.parent().children( '.gjWallRepliesExpand' ).hide();"
									.			"tab.parent().children( '.gjWallRepliesCollapse' ).show();"
									.		"});"
									.	"});";

			if ( $wallReplytoggle == 3 ) {
				$replytoggleJs		.=	"$( '.gjWallReplies' ).hide();"
									.	"$( '.gjWallRepliesCollapse' ).hide();";
			} else {
				$replytoggleJs		.=	"$( '.gjWallReplies' ).show();"
									.	"$( '.gjWallRepliesExpand' ).hide();";
			}

			$_CB_framework->outputCbJQuery( $replytoggleJs );
		}

		$return						.=		'<form action="' . $group->getUrl() . '" method="post" name="gjForm_wall" id="gjForm_wall" class="gjForm">';

		if ( $wallToggle || $wallSearch ) {
			$return					.=			'<div class="gjTop row-fluid">'
									.				'<div class="gjTop gjTopLeft span6">'
									.					( $wallToggle ? '<a href="#gjForm_wallnew" id="gjWallToggle" role="button" class="gjButton btn gjToggleExpand">' . CBTxt::Th( 'New Post' ) . '</a>' : null )
									.				'</div>'
									.				'<div class="gjTop gjTopRight span6">'
									.					( $wallSearch ? $pageNav->search : null )
									.				'</div>'
									.			'</div>';
		}

		if ( $rows ) foreach ( $rows as $row ) {
			$authorized				=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

			if ( $row->get( 'published' ) == 1 ) {
				$state				=	'<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_unpublish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to unpublish this wall?' ) ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a></div>';
			} else {
				$state				=	'<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a></div>';
			}

			$canApprove				=	( $wallApprove && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) );
			$canReply				=	( $wallReply && cbgjClass::hasAccess( 'wall_post', $authorized ) );

			$return					.=			'<div class="gjContent row-fluid">'
									.				'<div class="gjContentLogo span2">'
									.					'<div class="gjContentLogoRow">' . $row->getOwnerName( true ) . '</div>'
									.					'<div class="gjContentLogoRow">' . $row->getOwnerAvatar( true ) . '</div>'
									.					'<div class="gjContentLogoRow">' . $row->getOwnerOnline() . '</div>'
									.				'</div>'
									.				'<div class="gjContentBody mini-layout span10">'
									.					'<div class="gjContentBodyHeader row-fluid">'
									.						'<div class="gjContentBodyTitle span9"><h5><a href="' . cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ), null, ( $wallPaging ? array( 'tab' => htmlspecialchars( CBTxt::T( 'Wall' ) ), 'wall_limit' => (int) $pageNav->limit, 'wall_limitstart' => (int) $pageNav->limitstart ) : array( 'tab' => htmlspecialchars( CBTxt::T( 'Wall' ) ) ) ) ) ) . '#post' . (int) $row->get( 'id' ) . '" name="post' . (int) $row->get( 'id' ) . '" rel="nofollow">#' . (int) $row->get( 'id' ) . '</a><small> ' . cbFormatDate( $row->get( 'date' ) ) . '</small></h5></div>'
									.						'<div class="gjContentBodyMenu span3">';

			if ( $canApprove ) {
				$return				.=							'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Approve' ) ) . '" class="gjButton btn btn-mini btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />';
			} else {
				if ( ! $row->get( 'published' ) ) {
					$return			.=							cbgjClass::getIcon( null, CBTxt::T( 'This wall is currently unpublished.' ), 'icon-eye-close' );
				}

				if ( $canReply ) {
					$return			.=							' <a href="javascript: void(0);" role="button" class="gjButton btn btn-mini gjWallReply" rel="' . (int) $row->get( 'id' ) . '">' . CBTxt::Th( 'Reply' ) . '</a>';
				}
			}

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) || ( $canApprove && $canReply ) ) {
				$menuItems			=	( $canApprove && $canReply ? '<div><a href="javascript: void(0);" class="gjWallReply" rel="' . (int) $row->get( 'id' ) . '"><i class="icon-share-alt"></i> ' . CBTxt::Th( 'Reply' ) . '</a></div>' : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
									.	( ( ! $canApprove ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? $state : null )
									.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this wall?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

				$return				.=							cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) );
			}

			$return					.=						'</div>'
									.					'</div>'
									.					'<div class="gjContentBodyInfo">' . ( $row->getPost() ? '<div class="well well-small">' . $row->getPost() . '</div>' : null ) . '</div>';

			if ( ( $wallReplytoggle > 1 ) && $row->repliesCount() ) {
			$return					.=					'<div class="gjContentDivider"></div>'
									.					'<div class="gjContentBodyFooter">'
									.						'<div class="gjWallRepliesToggle">'
									.							'<a href="javascript: void(0);" class="gjWallRepliesExpand">' . CBTxt::Th( 'Show Replies' ) . '</a>'
									.							'<a href="javascript: void(0);" class="gjWallRepliesCollapse">' . CBTxt::Th( 'Hide Replies' ) . '</a>'
									.						'</div>'
									.					'</div>';
			}

			$return					.=				'</div>'
									.			'</div>'
									.			( $row->repliesCount() ? $this->getReplies( $row, $pageNav, $tabs, $group, $category, $user, $plugin ) : null );
		} else {
			$return					.=			'<div class="gjContent">';

			if ( $wallSearch && $pageNav->searching ) {
				$return				.=				CBTxt::Th( 'No wall search results found.' );
			} else {
				$return				.=				CBTxt::Ph( 'This [group] has no wall posts.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			}

			$return					.=			'</div>';
		}

		if ( $wallPaging ) {
			$return					.=			'<div class="gjPaging pagination pagination-centered">'
									.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
									.				( ! $wallLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
									.			'</div>';
		}

		$return						.=			cbGetSpoofInputTag( 'plugin' )
									.		'</form>'
									.	'</div>'
									.	$tabs->endTab();

		return $return;
	}

	public function getReplies( $row, $page, $tabs, $group, $category, $user, $plugin ) {
		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( ( ! $row ) || ( ! cbgjClass::hasAccess( 'wall_show', $authorized ) ) ) {
			return;
		}

		cbgjClass::getTemplate( 'cbgroupjivewall_replies' );

		$paging					=	new cbgjPaging( 'wall' . (int) $row->id );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'wall_replylimit', 5 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search_reply' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'post', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		if ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
			$where[]			=	array( 'published', '=', 1, array( 'user_id', '=', (int) $user->id ) );
		}

		$where[]				=	array( 'group', '=', (int) $group->get( 'id' ) );
		$where[]				=	array( 'reply', '=', (int) $row->get( 'id' ) );

		$total					=	count( cbgjWallData::getPosts( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjWallData::getPosts( null, $where, null, ( $plugin->params->get( 'wall_replypaging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->parent_start	=	$page->limitstart;
		$pageNav->parent_end	=	$page->limit;
		$pageNav->search		=	$paging->getInputSearch( 'gjForm_wall', 'search_reply', CBTxt::T( 'Search Reply...' ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjivewallReplies' ) ) {
			return HTML_cbgroupjivewallReplies::showWallReplies( $rows, $row, $pageNav, $tabs, $group, $category, $user, $plugin );
		} else {
			return $this->showWallReplies( $rows, $row, $pageNav, $tabs, $group, $category, $user, $plugin );
		}
	}

	private function showWallReplies( $rows, $wall, $pageNav, $tabs, $group, $category, $user, $plugin ) {
		$wallPaging						=	$plugin->params->get( 'wall_paging', 1 );
		$wallReplysearch				=	( $plugin->params->get( 'wall_replysearch', 1 ) && ( $pageNav->searching || $pageNav->total ) );
		$wallReplypaging				=	$plugin->params->get( 'wall_replypaging', 1 );
		$wallReplylimitbox				=	$plugin->params->get( 'wall_replylimitbox', 1 );

		$params							=	$group->getParams();
		$wallApproval					=	$params->get( 'wall_approve', $plugin->params->get( 'wall_approve', 0 ) );

		$return							=	'<div class="gjWallReplies row-fluid">'
										.		'<div class="span2"></div>'
										.		'<div class="span10">'
										.			( $wallReplysearch && ( $pageNav->searching || $pageNav->total ) ? '<div class="gjTop gjTopRight">' . $pageNav->search . '</div>' : null );

		$url							=	array( 'tab' => htmlspecialchars( CBTxt::T( 'Wall' ) ) );

		if ( $wallPaging ) {
			$url['wall_limitstart']		=	(int) $pageNav->parent_start;
			$url['wall_limit']			=	(int) $pageNav->parent_end;
		}

		if ( $wallReplypaging ) {
			$name						=	 'wall' . (int) $wall->get( 'id' );

			$url[$name . '_limitstart']	=	(int) $pageNav->limitstart;
			$url[$name . '_limit']		=	(int) $pageNav->limit;
		}

		if ( $rows ) foreach ( $rows as $row ) {
			$authorized					=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

			if ( $row->get( 'published' ) == 1 ) {
				$state					=	'<a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_unpublish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to unpublish this wall?' ) ) . '"><i class="icon-ban-circle"></i> ' . CBTxt::Th( 'Unpublish' ) . '</a>';
			} else {
				$state					=	'<a href="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-ok"></i> ' . CBTxt::Th( 'Publish' ) . '</a>';
			}

			$canApprove					=	( $wallApproval && ( $row->get( 'published' ) == -1 ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) );

			$return						.=			'<div class="gjContent row-fluid">'
										.				'<div class="gjContentLogo span2">'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerName( true ) . '</div>'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerAvatar( true ) . '</div>'
										.					'<div class="gjContentLogoRow">' . $row->getOwnerOnline() . '</div>'
										.				'</div>'
										.				'<div class="gjContentBody mini-layout span10">'
										.					'<div class="gjContentBodyHeader row-fluid">'
										.						'<div class="gjContentBodyTitle span9"><h5><a href="' . cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ), null, $url ) ) . '#post' . (int) $row->get( 'id' ) . '" name="post' . (int) $row->get( 'id' ) . '" rel="nofollow">#' . (int) $row->get( 'id' ) . '</a><small> ' . cbFormatDate( $row->get( 'date' ) ) . '</small></h5></div>'
										.						'<div class="gjContentBodyMenu span3">';

			if ( $canApprove ) {
				$return				.=								'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Approve' ) ) . '" class="gjButton btn btn-success" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_publish', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), true ) . '" />';
			} else {
				if ( ! $row->get( 'published' ) ) {
					$return			.=							cbgjClass::getIcon( null, CBTxt::T( 'This wall is currently unpublished.' ), 'icon-eye-close' );
				}
			}

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'mod_lvl3', 'usr_me' ), $authorized ) ) {
				$menuItems				=	( cbgjClass::hasAccess( array( 'mod_lvl3', 'usr_me' ), $authorized ) ? '<div><a href="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ) ) . '"><i class="icon-pencil"></i> ' . CBTxt::Th( 'Edit' ) . '</a></div>' : null )
										.	( ( ! $canApprove ) && cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? $state : null )
										.	( cbgjClass::hasAccess( array( 'mod_lvl4', 'usr_me' ), $authorized ) ? '<div><a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_delete', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to delete this wall?' ) ) . '"><i class="icon-remove"></i> ' . CBTxt::Th( 'Delete' ) . '</a></div>' : null );

				$return					.=							cbgjClass::getDropdown( $menuItems, CBTxt::Th( 'Menu' ) );
			}

			$return						.=						'</div>'
										.					'</div>'
										.					'<div class="gjContentBodyInfo">' . ( $row->getPost() ? '<div class="well well-small">' . $row->getPost() . '</div>' : null ) . '</div>'
										.				'</div>'
										.			'</div>';
		} else {
			$return						.=			'<div class="gjContent">' . CBTxt::Th( 'This wall has no replies.' ) . '</div>';
		}

		if ( ( $wallReplypaging && $pageNav->total > $pageNav->limit ) || $wallReplylimitbox ) {
			$return						.=			'<div class="gjPaging pagination pagination-centered">'
										.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
										.				( ! $wallReplylimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
										.			'</div>';
		} elseif ( $wallReplysearch ) {
			$return						.=			$pageNav->getLimitBox( false );
		}

		$return							.=		'</div>'
										.	'</div>';

		return $return;
	}

	private function editWall( $catid, $grpid, $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group						=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row						=	cbgjWallData::getPosts( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group					=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category				=	$group->getCategory();
		}

		$authorized					=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) ) {
			$params					=	$group->getParams();
			$wallEditor				=	$plugin->params->get( 'wall_editor', 1 );
			$inputLimit				=	$plugin->params->get( 'wall_inputlimit', 150 );

			$row->setPathway( CBTxt::T( 'Edit Wall' ) );

			cbgjClass::getTemplate( array( 'cbgroupjivewall_edit', 'jquery_inputlimit' ) );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation();
			}

			if ( $inputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				HTML_groupjiveInputLimit::loadJquery( '#wall_post', '#wall_inputlimit', $inputLimit );
			}

			$input					=	array();

			$input['publish']		=	moscomprofilerHTML::yesnoSelectList( 'published', ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'wall_published', $row->get( 'published', ( $params->get( 'wall_approve', $plugin->params->get( 'wall_approve', 0 ) ) ? 0 : 1 ) ) ) );

			if ( $wallEditor >= 2 ) {
				$post				=	cbgjClass::getHTMLCleanParam( true, 'wall_post', $row->get( 'post' ) );
			} else {
				$post				=	cbgjClass::getCleanParam( true, 'wall_post', $row->get( 'post' ) );
			}

			if ( $wallEditor == 3 ) {
				$input['post']		=	$_CB_framework->displayCmsEditor( 'wall_post', $post, 400, 200, 40, 6 );
			} else {
				$input['post']		=	'<textarea id="wall_post" name="wall_post" class="input-xlarge required" cols="40" rows="6">' . htmlspecialchars( $post ) . '</textarea>';
			}

			if ( class_exists( 'HTML_cbgroupjivewallEdit' ) ) {
				$return				=	HTML_cbgroupjivewallEdit::showWallEdit( $row, $input, $group, $category, $user, $plugin );
			} else {
				$return				=	'<div class="gjWallEdit">'
									.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'wall_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
									.			'<legend class="gjEditTitle">' . CBTxt::Th( 'Edit Wall' ) . '</legend>';

				if ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
					$return			.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['publish']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( CBTxt::T( 'Select publish status of wall post. Unpublished wall posts will not be visible to the public.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';
				}

				$return				.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Post' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['post']
									.					'<span class="gjEditContentInputIcon help-inline">'
									.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
									.						cbgjClass::getIcon( CBTxt::T( 'Input wall post.' ) )
									.					'</span>'
									.				'</div>'
									.			'</div>';

				if ( $inputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
					$charCount		=	( $inputLimit - cbIsoUtf_strlen( $row->get( 'post' ) ) );
					$limitCount		=	( $charCount > 0 ? $charCount : 0 );

					$input['limit']	=	'<input type="text" id="wall_inputlimit" name="wall_inputlimit" value="' . (int) $limitCount . '" class="input-small" size="7" disabled="disabled" />';

					$return			.=			'<div class="gjEditContentInput control-group">'
									.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Input Limit' ) . '</label>'
									.				'<div class="gjEditContentInputField controls">'
									.					$input['limit']
									.				'</div>'
									.			'</div>';
				}

				$return				.=			'<div class="gjButtonWrapper form-actions">'
									.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Update Post' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
									.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ), true, false, null, false, false, true ) . '" />'
									.			'</div>'
									.			'<input type="hidden" id="wall_reply" name="wall_reply" value="' . (int) cbgjClass::getCleanParam( true, 'wall_reply', $row->get( 'reply', 0 ) ) . '" />'
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

	private function saveWall( $catid, $grpid, $id, $user, $plugin ) {
		$category							=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group								=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row								=	cbgjWallData::getPosts( array( array( 'mod_lvl3', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group							=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category						=	$group->getCategory();
		}

		$authorized							=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || cbgjClass::hasAccess( 'wall_post', $authorized ) ) {
			$inputLimit						=	$plugin->params->get( 'wall_inputlimit', 150 );

			$params							=	$group->getParams();
			$wallApprove					=	$params->get( 'wall_approve', $plugin->params->get( 'wall_approve', 0 ) );

			$row->set( 'published', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'mod_lvl4', $authorized ), 'published', $row->get( 'published', ( $wallApprove ? -1 : 1 ) ) ) );
			$row->set( 'user_id', (int) $row->get( 'user_id', $user->id ) );
			$row->set( 'group', (int) $row->get( 'group', $group->get( 'id' ) ) );

			if ( $plugin->params->get( 'wall_editor', 1 ) >= 2 ) {
				$row->set( 'post', cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'wall_post', $row->get( 'post' ) ) ) );
			} else {
				$row->set( 'post', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'wall_post', $row->get( 'post' ) ) ) );
			}

			if ( $inputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) && ( cbIsoUtf_strlen( $row->get( 'post' ) ) > $inputLimit ) ) {
				$row->set( 'post', trim( cbIsoUtf_substr( $row->get( 'post' ), 0, ( $inputLimit - 3 ) ) ) . '...' );
			}

			if ( $plugin->params->get( 'wall_reply', 1 ) ) {
				$row->set( 'reply', (int) cbgjClass::getCleanParam( true, 'wall_reply', $row->get( 'reply', 0 ) ) );
			}

			$row->set( 'date', ( $row->get( 'date' ) ? $row->get( 'date' ) : cbgjClass::getUTCDate() ) );

			if ( $row->get( 'post' ) == '' ) {
				$row->set( '_error', CBTxt::T( 'Post not specified!' ) );
			} elseif ( ! $row->get( 'user_id' ) ) {
				$row->set( '_error', CBTxt::P( '[user] not specified!', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) );
			} elseif ( ! $row->get( 'group' ) ) {
				$row->set( '_error', CBTxt::P( '[group] not specified!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) );
			} elseif ( $plugin->params->get( 'wall_captcha', 0 ) && ( ! $row->get( 'id' ) ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha					=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$row->set( '_error', CBTxt::T( $captcha ) );
				}
			}

			$new							=	( $row->get( 'id' ) ? false : true );

			if ( $row->getError() || ( ! $row->store() ) ) {
				if ( ! $new ) {
					$this->editWall( $category->get( 'id' ), $group->get( 'id' ), $row->get( 'id' ), $user, $plugin, $row, CBTxt::P( 'Wall failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) );
				} else {
					CBplug_cbgroupjive::showGroup( $category->get( 'id' ), $group->get( 'id' ), $user, $plugin, CBTxt::P( 'Wall failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), '#gjForm_wallnew' );
				}
				return;
			}

			if ( $new ) {
				if ( $row->get( 'published' ) ) {
					$replyNotify			=	false;

					if ( $row->get( 'reply' ) ) {
						$notification		=	cbgjData::getNotifications( array( array( 'wall_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $row->getReply()->get( 'user_id' ) ), array( 'params', 'CONTAINS', 'group_wallreply=1' ) ), null, null, false );

						if ( $notification->get( 'id' ) ) {
							$subject		=	CBTxt::P( '[group_name] - Wall Post Reply Created!', $row->getSubstitutionExtras( true ) );
							$message		=	CBTxt::P( '[user] created [wall_title_linked] as reply to [wall_reply_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );

							$replyNotify	=	true;
						} elseif ( $row->get( 'user_id' ) == $row->getReply()->get( 'user_id' ) ) {
							$replyNotify	=	true;
						}
					}

					$notifications			=	cbgjData::getNotifications( array( array( 'wall_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', ( $replyNotify ? array( (int) $user->id, (int) $row->get( 'user_id' ), (int) $row->getReply()->get( 'user_id' ) ) : array( (int) $user->id, (int) $row->get( 'user_id' ) ) ) ), array( 'params', 'CONTAINS', 'group_wallnew=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - Wall Post Created!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] created [wall_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				} elseif ( $wallApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'wall_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_wallapprove=1' ) ) );

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[group_name] - Wall Post Requires Approval!', $row->getSubstitutionExtras( true ) );
						$message			=	CBTxt::P( '[user] created [wall_title_linked] in [group] and requires approval!', $row->getSubstitutionExtras( true ) );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
						}
					}
				}
			} elseif ( $row->get( 'published' ) ) {
				$notifications				=	cbgjData::getNotifications( array( array( 'wall_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_wallupdate=1' ) ) );

				if ( $notifications ) {
					$subject				=	CBTxt::P( '[group_name] - Wall Post Edited!', $row->getSubstitutionExtras( true ) );
					$message				=	CBTxt::P( '[user] edited [wall_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}
			}

			if ( $wallApprove && ( $row->get( 'published' ) == -1 ) && ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) ) {
				$successMsg					=	CBTxt::T( 'Wall saved successfully and awaiting approval!' );
			} else {
				$successMsg					=	CBTxt::T( 'Wall saved successfully!' );
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

	private function stateWall( $catid, $grpid, $id, $state, $user, $plugin ) {
		$category						=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group							=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row							=	cbgjWallData::getPosts( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group						=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category					=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$currentState				=	$row->get( 'published' );

			if ( ! $row->storeState( $state ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Wall state failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $state && ( $currentState == -1 ) ) {
				$replyNotify			=	false;

				if ( $row->get( 'reply' ) ) {
					$notification		=	cbgjData::getNotifications( array( array( 'wall_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $row->getReply()->get( 'user_id' ) ), array( 'params', 'CONTAINS', 'group_wallreply=1' ) ), null, null, false );

					if ( $notification->get( 'id' ) ) {
						$subject		=	CBTxt::P( '[group_name] - Wall Post Reply Created!', $row->getSubstitutionExtras( true ) );
						$message		=	CBTxt::P( '[user] created [wall_title_linked] as reply to [wall_reply_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );

						$replyNotify	=	true;
					} elseif ( $row->get( 'user_id' ) == $row->getReply()->get( 'user_id' ) ) {
						$replyNotify	=	true;
					}
				}

				$notifications			=	cbgjData::getNotifications( array( array( 'wall_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', ( $replyNotify ? array( (int) $user->id, (int) $row->get( 'user_id' ), (int) $row->getReply()->get( 'user_id' ) ) : array( (int) $user->id, (int) $row->get( 'user_id' ) ) ) ), array( 'params', 'CONTAINS', 'group_wallnew=1' ) ) );

				if ( $notifications ) {
					$subject			=	CBTxt::P( '[group_name] - Wall Post Created!', $row->getSubstitutionExtras( true ) );
					$message			=	CBTxt::P( '[user] created [wall_title_linked] in [group]!', $row->getSubstitutionExtras( true ) );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}

				if ( $user->id != $row->get( 'user_id' ) ) {
					$subject		=	CBTxt::T( '[group_name] - Wall Post Request Accepted!' );
					$message		=	CBTxt::T( 'Your request to post [wall_title_linked] in [group] has been accepted!' );

					cbgjClass::getNotification( $row->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Wall state saved successfully!' ), false, true, null, false, false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url					=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url					=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url					=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function deleteWall( $catid, $grpid, $id, $user, $plugin ) {
		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row					=	cbgjWallData::getPosts( array( array( 'mod_lvl4', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group				=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$published			=	$row->get( 'published' );

			if ( $published ) {
				$notifications	=	cbgjData::getNotifications( array( array( 'wall_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_walldelete=1' ) ) );
			} else {
				$notifications	=	null;
			}

			if ( ! $row->deleteAll() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Wall failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $notifications ) {
				$subject		=	CBTxt::P( '[group_name] - Wall Post Deleted!', $row->getSubstitutionExtras( true ) );
				$message		=	CBTxt::P( '[user] deleted [wall_title] in [group]!', $row->getSubstitutionExtras( true ) );

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Wall deleted successfully!' ), false, true, null, false, false, true );
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

	public function deletePosts( $group, $category, $user, $plugin ) {
		$rows	=	cbgjWallData::getPosts( null, array( 'group', '=', (int) $group->get( 'id' ) ) );

		if ( $rows ) foreach ( $rows as $row ) {
			$row->deleteAll();
		}
	}

	public function getParam( $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user );
		$params					=	$group->getParams();
		$wallShow				=	$plugin->params->get( 'wall_show_config', 1 );
		$wallPublic				=	$plugin->params->get( 'wall_public_config', 1 );
		$wallPost				=	$plugin->params->get( 'wall_post_config', 1 );
		$wallApprove			=	$plugin->params->get( 'wall_approve_config', 1 );

		$input					=	array();

		$input['wall_show']		=	moscomprofilerHTML::yesnoSelectList( 'wall_show', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $wallShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'wall_show', $params->get( 'wall_show', $plugin->params->get( 'wall_show', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_public']	=	moscomprofilerHTML::yesnoSelectList( 'wall_public', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $wallPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'wall_public', $params->get( 'wall_public', $plugin->params->get( 'wall_public', 1 ) ) ) );

		$listPost				=	array();
		$listPost[]				=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listPost[]				=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listPost[]				=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listPost[]				=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['wall_post']		=	moscomprofilerHTML::selectList( $listPost, 'wall_post', 'class="input-large"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $wallPost || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'wall_post', $params->get( 'wall_post', $plugin->params->get( 'wall_post', 0 ) ) ), 1, false, false );

		$input['wall_approve']	=	moscomprofilerHTML::yesnoSelectList( 'wall_approve', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $wallApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'wall_approve', $params->get( 'wall_approve', $plugin->params->get( 'wall_approve', 0 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		if ( $_CB_framework->getUi() == 2 ) {
			$return				=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Wall' ) ), 'gjIntegrationsWall' )
								.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
								.			'<tbody>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Display' ) . '</div>'
								.					'<td width="40%">' . $input['wall_show'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select usage of group wall.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Public' ) . '</div>'
								.					'<td width="40%">' . $input['wall_public'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select if group wall tab is publicly visible.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Post' ) . '</div>'
								.					'<td width="40%">' . $input['wall_post'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select group post access. Post access determines what type of users can post to your group wall (e.g. Users signify only those a member of your group can post). The users above the selected will also have access.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</div>'
								.					'<td width="40%">' . $input['wall_approve'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Enable or disable approval of new group wall posts. Wall posts will require approval by a group moderator, admin, or owner to be published. Group moderators, admins, and owner are exempt from this configuration.' ) . '</div>'
								.				'</tr>'
								.			'</tbody>'
								.		'</table>'
								.	$tabs->endTab();
		} else {
			if ( ( ! $wallShow ) && ( ! $wallPublic ) && ( ! $wallPost ) && ( ! $wallApprove ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				return;
			}

			cbgjClass::getTemplate( 'cbgroupjivewall_params' );

			if ( class_exists( 'HTML_cbgroupjivewallParams' ) ) {
				$return			=	HTML_cbgroupjivewallParams::showWallParams( $input, $group, $category, $user, $plugin );
			} else {
				$return			=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Wall' ) ), 'gjIntegrationsWall' );

				if ( $wallShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Display' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['wall_show']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select usage of [group] wall.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $wallPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Public' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['wall_public']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select if [group] wall tab is publicly visible.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $wallPost || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Post' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['wall_post']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select [group] post access. Post access determines what type of [users] can post to your [group] wall (e.g. [users] signify only those a member of your [group] can post). The [users] above the selected will also have access.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $wallApprove || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Approve' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['wall_approve']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Enable or disable approval of new [group] wall posts. Wall posts will require approval by a [group] [mod], [admin], or [owner] to be published. [group] [mods], [admins], and [owner] are exempt from this configuration.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[mods]' => cbgjClass::getOverride( 'mod', true ), '[admins]' => cbgjClass::getOverride( 'admin', true ), '[mod]' => cbgjClass::getOverride( 'mod' ), '[admin]' => cbgjClass::getOverride( 'admin' ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
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
		$authorized	=	cbgjClass::getAuthorization( $category, $group, $user );
		$params		=	$group->getParams();

		$params->set( 'wall_show', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'wall_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'wall_show', $params->get( 'wall_show', $plugin->params->get( 'wall_show', 1 ) ) ) );
		$params->set( 'wall_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'wall_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'wall_public', $params->get( 'wall_public', $plugin->params->get( 'wall_public', 1 ) ) ) );
		$params->set( 'wall_post', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'wall_post_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'wall_post', $params->get( 'wall_post', $plugin->params->get( 'wall_post', 0 ) ) ) );
		$params->set( 'wall_approve', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'wall_approve_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'wall_approve', $params->get( 'wall_approve', $plugin->params->get( 'wall_approve', 0 ) ) ) );

		$group->storeParams( $params );
	}

	public function getConfig( $tabs, $user, $plugin ) {
		$input											=	array();

		$input['wall_reply']							=	moscomprofilerHTML::yesnoSelectList( 'wall_reply', null, $plugin->params->get( 'wall_reply', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_delete']							=	moscomprofilerHTML::yesnoSelectList( 'wall_delete', null, $plugin->params->get( 'wall_delete', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_notifications']					=	moscomprofilerHTML::yesnoSelectList( 'wall_notifications', null, $plugin->params->get( 'wall_notifications', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_notifications_group_wallnew']		=	moscomprofilerHTML::yesnoSelectList( 'wall_notifications_group_wallnew', null, $plugin->params->get( 'wall_notifications_group_wallnew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['wall_notifications_group_wallapprove']	=	moscomprofilerHTML::yesnoSelectList( 'wall_notifications_group_wallapprove', null, $plugin->params->get( 'wall_notifications_group_wallapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['wall_notifications_group_wallreply']	=	moscomprofilerHTML::yesnoSelectList( 'wall_notifications_group_wallreply', null, $plugin->params->get( 'wall_notifications_group_wallreply', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['wall_notifications_group_wallupdate']	=	moscomprofilerHTML::yesnoSelectList( 'wall_notifications_group_wallupdate', null, $plugin->params->get( 'wall_notifications_group_wallupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['wall_notifications_group_walldelete']	=	moscomprofilerHTML::yesnoSelectList( 'wall_notifications_group_walldelete', null, $plugin->params->get( 'wall_notifications_group_walldelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['wall_inputlimit']						=	'<input type="text" id="wall_inputlimit" name="wall_inputlimit" value="' . htmlspecialchars( $plugin->params->get( 'wall_inputlimit', 150 ) ) . '" class="inputbox" size="7" />';

		$listEditor										=	array();
		$listEditor[]									=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Plain Text' ) );
		$listEditor[]									=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'HTML Text' ) );
		$listEditor[]									=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'WYSIWYG' ) );
		$input['wall_editor']							=	moscomprofilerHTML::selectList( $listEditor, 'wall_editor', null, 'value', 'text', $plugin->params->get( 'wall_editor', 1 ), 1, false, false );

		$input['wall_post_content']						=	moscomprofilerHTML::yesnoSelectList( 'wall_post_content', null, $plugin->params->get( 'wall_post_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_captcha']							=	moscomprofilerHTML::yesnoSelectList( 'wall_captcha', null, $plugin->params->get( 'wall_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_paging']							=	moscomprofilerHTML::yesnoSelectList( 'wall_paging', null, $plugin->params->get( 'wall_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_limitbox']							=	moscomprofilerHTML::yesnoSelectList( 'wall_limitbox', null, $plugin->params->get( 'wall_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_limit']							=	'<input type="text" id="wall_limit" name="wall_limit" value="' . (int) $plugin->params->get( 'wall_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['wall_search']							=	moscomprofilerHTML::yesnoSelectList( 'wall_search', null, $plugin->params->get( 'wall_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_approval_paging']					=	moscomprofilerHTML::yesnoSelectList( 'wall_approval_paging', null, $plugin->params->get( 'wall_approval_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_approval_limitbox']				=	moscomprofilerHTML::yesnoSelectList( 'wall_approval_limitbox', null, $plugin->params->get( 'wall_approval_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_approval_limit']					=	'<input type="text" id="wall_approval_limit" name="wall_approval_limit" value="' . (int) $plugin->params->get( 'wall_approval_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['wall_approval_search']					=	moscomprofilerHTML::yesnoSelectList( 'wall_approval_search', null, $plugin->params->get( 'wall_approval_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$listWallToggle									=	array();
		$listWallToggle[]								=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Disabled' ) );
		$listWallToggle[]								=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Expanded' ) );
		$listWallToggle[]								=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Collapsed' ) );
		$input['wall_replytoggle']						=	moscomprofilerHTML::selectList( $listWallToggle, 'wall_replytoggle', null, 'value', 'text', $plugin->params->get( 'wall_replytoggle', 2 ), 1, false, false );

		$input['wall_replypaging']						=	moscomprofilerHTML::yesnoSelectList( 'wall_replypaging', null, $plugin->params->get( 'wall_replypaging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_replylimitbox']					=	moscomprofilerHTML::yesnoSelectList( 'wall_replylimitbox', null, $plugin->params->get( 'wall_replylimitbox', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_replylimit']						=	'<input type="text" id="wall_replylimit" name="wall_replylimit" value="' . (int) $plugin->params->get( 'wall_replylimit', 5 ) . '" class="inputbox" size="5" />';
		$input['wall_replysearch']						=	moscomprofilerHTML::yesnoSelectList( 'wall_replysearch', null, $plugin->params->get( 'wall_replysearch', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_show']								=	moscomprofilerHTML::yesnoSelectList( 'wall_show', null, $plugin->params->get( 'wall_show', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_show_config']						=	moscomprofilerHTML::yesnoSelectList( 'wall_show_config', null, $plugin->params->get( 'wall_show_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['wall_public']							=	moscomprofilerHTML::yesnoSelectList( 'wall_public', null, $plugin->params->get( 'wall_public', 1 ) );
		$input['wall_public_config']					=	moscomprofilerHTML::yesnoSelectList( 'wall_public_config', null, $plugin->params->get( 'wall_public_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$listPost										=	array();
		$listPost[]										=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
		$listPost[]										=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
		$listPost[]										=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
		$listPost[]										=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
		$input['wall_post']								=	moscomprofilerHTML::selectList( $listPost, 'wall_post', 'class="inputbox"', 'value', 'text', $plugin->params->get( 'wall_post', 0 ), 1, false, false );

		$input['wall_post_config']						=	moscomprofilerHTML::yesnoSelectList( 'wall_post_config', null, $plugin->params->get( 'wall_post_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['wall_approve']							=	moscomprofilerHTML::yesnoSelectList( 'wall_approve', null, $plugin->params->get( 'wall_approve', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['wall_approve_config']					=	moscomprofilerHTML::yesnoSelectList( 'wall_approve_config', null, $plugin->params->get( 'wall_approve_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$return											=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Wall' ) ), 'gjIntegrationsWall' )
														.		$tabs->startPane( 'gjIntegrationsWallTabs' )
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsWallGeneral' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Reply' ) . '</th>'
														.							'<td width="40%">' . $input['wall_reply'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of replies on wall tab. Note replies have no searching or paging.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Auto Delete' ) . '</th>'
														.							'<td width="40%">' . $input['wall_delete'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable deletion of user posts on group leave.' ) . '</td>'
														.						'</tr>'
														.					'</tbody>'
														.				'</table>'
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Notifications' ) ), 'gjIntegrationsWallNotifications' )
														.				$tabs->startPane( 'gjIntegrationsWallNotificationsTabs' )
														.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsWallNotificationsGeneral' )
														.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.							'<tbody>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Notifications' ) . '</th>'
														.									'<td width="40%">' . $input['wall_notifications'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Enable or disable sending and configuration of wall notifications. Moderators are exempt from this configuration.' ) . '</td>'
														.								'</tr>'
														.							'</tbody>'
														.						'</table>'
														.					$tabs->endTab()
														.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsWallNotificationsDefaults' )
														.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.							'<tbody>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Create Post' ) . '</th>'
														.									'<td width="40%">' . $input['wall_notifications_group_wallnew'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for wall notification parameter "Create of new post".' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Post Approval' ) . '</th>'
														.									'<td width="40%">' . $input['wall_notifications_group_wallapprove'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for wall notification parameter "New post requires approval".' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Create Reply' ) . '</th>'
														.									'<td width="40%">' . $input['wall_notifications_group_wallreply'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for wall notification parameter "User reply to my existing posts".' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Update Post' ) . '</th>'
														.									'<td width="40%">' . $input['wall_notifications_group_wallupdate'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for wall notification parameter "Update of existing post".' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Delete Post' ) . '</th>'
														.									'<td width="40%">' . $input['wall_notifications_group_walldelete'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select default value for wall notification parameter "Delete of existing post".' ) . '</td>'
														.								'</tr>'
														.							'</tbody>'
														.						'</table>'
														.					$tabs->endTab()
														.				$tabs->endPane()
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Post' ) ), 'gjIntegrationsWallPost' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Input Limit' ) . '</th>'
														.							'<td width="40%">' . $input['wall_inputlimit'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Input wall post character count limit. If left blank or zero no limit will be applied. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Editor' ) . '</th>'
														.							'<td width="40%">' . $input['wall_editor'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Select method for wall editing.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
														.							'<td width="40%">' . $input['wall_post_content'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on wall posts.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Captcha' ) . '</th>'
														.							'<td width="40%">' . $input['wall_captcha'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of captcha on wall tab. Requires latest CB Captcha or integrated captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.					'</tbody>'
														.				'</table>'
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjIntegrationsWallPaging' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
														.							'<td width="40%">' . $input['wall_paging'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on wall.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
														.							'<td width="50%">' . $input['wall_limitbox'] . '</td>'
														.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on wall. Requires Paging to be Enabled.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
														.							'<td width="50%">' . $input['wall_limit'] . '</td>'
														.							'<td>' . CBTxt::T( 'Input default page limit on wall. Page limit determines how many posts are displayed per page.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
														.							'<td width="600px">' . $input['wall_search'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on wall.' ) . '</td>'
														.						'</tr>'
														.					'</tbody>'
														.				'</table>'
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Approval' ) ), 'gjIntegrationsWallApproval' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
														.							'<td width="40%">' . $input['wall_approval_paging'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on wall.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
														.							'<td width="50%">' . $input['wall_approval_limitbox'] . '</td>'
														.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on wall. Requires Paging to be Enabled.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
														.							'<td width="50%">' . $input['wall_approval_limit'] . '</td>'
														.							'<td>' . CBTxt::T( 'Input default page limit on wall. Page limit determines how many posts are displayed per page.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
														.							'<td width="600px">' . $input['wall_approval_search'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on wall.' ) . '</td>'
														.						'</tr>'
														.					'</tbody>'
														.				'</table>'
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Replies' ) ), 'gjIntegrationsWallReplies' )
														.				$tabs->startPane( 'gjIntegrationsWallRepliesTabs' )
														.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsWallRepliesGeneral' )
														.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.							'<tbody>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Toggle' ) . '</th>'
														.									'<td width="40%">' . $input['wall_replytoggle'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Select if replies content display is toggled or always shown.' ) . '</td>'
														.								'</tr>'
														.							'</tbody>'
														.						'</table>'
														.					$tabs->endTab()
														.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjIntegrationsWallRepliesPaging' )
														.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.							'<tbody>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
														.									'<td width="40%">' . $input['wall_replypaging'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Enable or disable usage of paging on wall replies.' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
														.									'<td width="50%">' . $input['wall_replylimitbox'] . '</td>'
														.									'<td>' . CBTxt::T( 'Enable or disable usage of page limit on wall replies. Requires Paging to be enabled.' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
														.									'<td width="50%">' . $input['wall_replylimit'] . '</td>'
														.									'<td>' . CBTxt::T( 'Input default page limit on wall replies. Page limit determines how many posts are displayed per page.' ) . '</td>'
														.								'</tr>'
														.								'<tr>'
														.									'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
														.									'<td width="600px">' . $input['wall_replysearch'] . '</td>'
														.									'<td>' . CBTxt::Th( 'Enable or disable usage of searching on wall replies.' ) . '</td>'
														.								'</tr>'
														.							'</tbody>'
														.						'</table>'
														.					$tabs->endTab()
														.				$tabs->endPane()
														.			$tabs->endTab()
														.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsWallDefaults' )
														.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
														.					'<tbody>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
														.							'<td width="40%">' . $input['wall_show'] . ' ' . $input['wall_show_config'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Display". Additionally select the display of the "Display" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Public' ) . '</th>'
														.							'<td width="40%">' . $input['wall_public'] . ' ' . $input['wall_public_config'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Public". Additionally select the display of the "Public" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Post' ) . '</th>'
														.							'<td width="600px">' . $input['wall_post'] . ' ' . $input['wall_post_config'] . '</td>'
														.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Post". Additionally select the display of the "Post" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
														.						'</tr>'
														.						'<tr>'
														.							'<th width="15%">' . CBTxt::Th( 'Approve' ) . '</th>'
														.							'<td width="600px">' . $input['wall_approve'] . ' ' . $input['wall_approve_config'] . '</td>'
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
		$this->deleteUserPosts( $user, $group );
	}

	public function deleteUser( $user, $deleted ) {
		$this->deleteUserPosts( $user );
	}

	private function deleteUserPosts( $user, $group = null ) {
		$plugin				=	cbgjClass::getPlugin();

		if ( $plugin->params->get( 'wall_delete', 0 ) ) {
			$where			=	array();

			if ( $group ) {
				$where[]	=	array( 'group', '=', (int) $group->get( 'id' ) );
			}

			$where[]		=	array( 'user_id', '=', (int) $user->id );

			$rows			=	cbgjWallData::getPosts( null, $where );

			if ( $rows ) foreach ( $rows as $row ) {
				$row->deleteAll();
			}
		}
	}

	public function getAuthorization( &$access, $category, $group, $user, $owner, $row, $plugin ) {
		if ( isset( $group->id ) && cbgjClass::hasAccess( 'grp_approved', $access ) ) {
			$params					=	$group->getParams();
			$wallShow				=	$params->get( 'wall_show', $plugin->params->get( 'wall_show', 1 ) );
			$wallPublic				=	$params->get( 'wall_public', $plugin->params->get( 'wall_public', 1 ) );
			$wallPost				=	$params->get( 'wall_post', $plugin->params->get( 'wall_post', 0 ) );

			if ( ( $wallPublic || cbgjClass::hasAccess( 'mod_lvl5', $access ) ) && $wallShow ) {
				$access[]			=	'wall_show';

				if ( cbgjClass::hasAccess( 'usr_notifications', $access ) && ( $plugin->params->get( 'wall_notifications', 1 ) || cbgjClass::hasAccess( 'usr_mod', $access ) ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) {
					if ( ! cbgjClass::hasAccess( 'grp_usr_notifications', $access ) ) {
						$access[]	=	'grp_usr_notifications';
					}

					$access[]		=	'wall_notifications';
				}
			}

			if ( $wallShow && ( ( ( $wallPost == 0 ) && cbgjClass::hasAccess( 'mod_lvl5', $access ) ) || ( ( $wallPost == 1 ) && cbgjClass::hasAccess( 'mod_lvl4', $access ) ) || ( $wallPost == 2 ) && cbgjClass::hasAccess( 'mod_lvl3', $access ) || ( $wallPost == 3 ) && cbgjClass::hasAccess( 'mod_lvl2', $access ) ) ) {
				$access[]			=	'wall_post';
			}
		}
	}

	public function getNotifications( $tabs, $row, $group, $category, $user, $plugin ) {
		$authorized						=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'wall_notifications', $authorized ) ) {
			$params						=	$row->getParams();

			$input						=	array();

			$input['group_wallnew']		=	moscomprofilerHTML::yesnoSelectList( 'group_wallnew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_wallnew', $params->get( 'group_wallnew', $plugin->params->get( 'wall_notifications_group_wallnew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_wallapprove']	=	moscomprofilerHTML::yesnoSelectList( 'group_wallapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'wall_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_wallapprove', $params->get( 'group_wallapprove', $plugin->params->get( 'wall_notifications_group_wallapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_wallreply']	=	moscomprofilerHTML::yesnoSelectList( 'group_wallreply', 'class="input-medium"', ( (int) ( $plugin->params->get( 'wall_reply', 1 ) && cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_post' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_wallreply', $params->get( 'group_wallreply', $plugin->params->get( 'wall_notifications_group_wallreply', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_wallupdate']	=	moscomprofilerHTML::yesnoSelectList( 'group_wallupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_wallupdate', $params->get( 'group_wallupdate', $plugin->params->get( 'wall_notifications_group_wallupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_walldelete']	=	moscomprofilerHTML::yesnoSelectList( 'group_walldelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_walldelete', $params->get( 'group_walldelete', $plugin->params->get( 'wall_notifications_group_walldelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

			$return						=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Wall' ) ), 'gjNotificationsGroupWall' )
										.		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Create of new post' ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_wallnew']
										.			'</div>'
										.		'</div>';

			if ( cbgjClass::hasAccess( array( 'mod_lvl4', 'wall_show' ), $authorized, true ) ) {
				$return					.=		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'New post requires approval' ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_wallapprove']
										.			'</div>'
										.		'</div>';
			}

			if ( $plugin->params->get( 'wall_reply', 1 ) && cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_post' ), $authorized, true ) ) {
				$return					.=		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Ph( '[user] reply to my existing posts', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_wallreply']
										.			'</div>'
										.		'</div>';
			}

			$return						.=		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Update of existing post' ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_wallupdate']
										.			'</div>'
										.		'</div>'
										.		'<div class="gjEditContentInput control-group">'
										.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Delete of existing post' ) . '</label>'
										.			'<div class="gjEditContentInputField controls">'
										.				$input['group_walldelete']
										.			'</div>'
										.		'</div>'
										.	$tabs->endTab();

			return $return;
		}
	}

	public function setNotifications( &$params, $row, $group, $category, $user, $plugin ) {
		if ( isset( $group->id ) ) {
			$authorized		=	cbgjClass::getAuthorization( $category, $group, $row->getOwner() );

			if ( cbgjClass::hasAccess( 'wall_notifications', $authorized ) ) {
				$rowParams	=	$row->getParams();

				$params->set( 'group_wallnew', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_wallnew', $rowParams->get( 'group_wallnew', $plugin->params->get( 'wall_notifications_group_wallnew', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_wallapprove', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl4', 'wall_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_wallapprove', $rowParams->get( 'group_wallapprove', $plugin->params->get( 'wall_notifications_group_wallapprove', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_wallreply', ( (int) ( $plugin->params->get( 'wall_reply', 1 ) && cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_post' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_wallreply', $rowParams->get( 'group_wallreply', $plugin->params->get( 'wall_notifications_group_wallreply', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_wallupdate', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_wallupdate', $rowParams->get( 'group_wallupdate', $plugin->params->get( 'wall_notifications_group_wallupdate', 0 ) ) ) : 0 ) ? 1 : 0 ) );
				$params->set( 'group_walldelete', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl5', 'wall_show' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_walldelete', $rowParams->get( 'group_walldelete', $plugin->params->get( 'wall_notifications_group_walldelete', 0 ) ) ) : 0 ) ? 1 : 0 ) );
			}
		}
	}

	public function getMenu( $user, $plugin ) {
		$input					=	array();

		$input['approve_wall']	=	'<input type="checkbox" id="type_approve_file" name="type[]" class="inputbox" value="approve-wall" />';

		$return					=	'<tr>'
								.		'<td width="10%" style="text-align:center;">' . $input['approve_wall'] . '</td>'
								.		'<th width="20%">' . CBTxt::Th( 'Wall Approval' ) . '</td>'
								.		'<td>' . CBTxt::Th( 'Create menu link to a wall approval page.' ) . '</td>'
								.	'</tr>';

		return $return;
	}

	public function saveMenu( $type, $categories, $groups, $user, $plugin ) {
		if ( $type == 'approve-wall' ) {
			if ( ! cbgjClass::setMenu( CBTxt::T( 'Wall Approval' ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=plugin&func=wall_approval', $plugin ) ) {
				cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Wall approval menu failed to create!' ), false, true, 'error' );
			}
		}
	}
}

class cbgjWall extends comprofilerDBTable {
	var $id			=	null;
	var $published	=	null;
	var $user_id	=	null;
	var $group		=	null;
	var $reply		=	null;
	var $post		=	null;
	var $date		=	null;

	public function __construct( $db ) {
		$this->comprofilerDBTable( '#__groupjive_plugin_wall', 'id', $db );
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
			$_PLUGINS->trigger( 'gjint_onBeforeUpdateWall', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			$_PLUGINS->trigger( 'gjint_onBeforeCreateWall', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		if ( ! parent::store( $updateNulls ) ) {
			return false;
		}

		if ( ! $new ) {
			$_PLUGINS->trigger( 'gjint_onAfterUpdateWall', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		} else {
			cbgjClass::resetCache( true );

			$_PLUGINS->trigger( 'gjint_onAfterCreateWall', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );
		}

		return true;
	}

	public function delete( $id = null ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeDeleteWall', array( &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		if ( ! parent::delete( $id ) ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterDeleteWall', array( $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function storeState( $state ) {
		global $_CB_framework, $_PLUGINS;

		$plugin	=	cbgjClass::getPlugin();
		$user	=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		$_PLUGINS->trigger( 'gjint_onBeforeUpdateWallState', array( &$state, &$this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		$this->set( 'published', (int) $state );

		if ( ! parent::store() ) {
			return false;
		}

		$_PLUGINS->trigger( 'gjint_onAfterUpdateWallState', array( $this->get( 'published' ), $this, $this->getGroup(), $this->getCategory(), $user, $plugin ) );

		return true;
	}

	public function deleteAll() {
		$replies	=	$this->getReplies();

		if ( $replies ) foreach ( $replies as $reply ) {
			if ( ! $reply->deleteAll() ) {
				return false;
			}
		}

		if ( ! $this->delete() ) {
			return false;
		}

		return true;
	}

	public function setPathway( $title = null, $url = null ) {
		global $_CB_framework;

		$this->getGroup()->setPathway( false );

		if ( $title !== false ) {
			if ( ! $title ) {
				$title	=	CBTxt::T( 'Wall' );
			}

			if ( $title ) {
				$_CB_framework->setPageTitle( htmlspecialchars( $title ) );
			}
		} else {
			$title		=	CBTxt::T( 'Wall' );
		}

		if ( ! $url ) {
			$url		=	$this->getUrl();
		}

		if ( $title ) {
			$_CB_framework->appendPathWay( htmlspecialchars( $title ), $url );
		}
	}

	public function getUrl() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjClass::getPluginURL( array( 'groups', 'show', (int) $this->getCategory()->get( 'id' ), (int) $this->get( 'group' ), null, array( 'tab' => htmlspecialchars( CBTxt::T( 'Wall' ) ) ) ) ) . '#post' . (int) $id;
		}

		return $cache[$id];
	}

	public function getTitle( $length = 0, $linked = false ) {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	'#' . (int) $id;
		}

		$title			=	$cache[$id];

		if ( $title ) {
			$length		=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( $title ) > $length ) ) {
				$title	=	rtrim( trim( cbIsoUtf_substr( $title, 0, $length ) ), '.' ) . '...';
				$short	=	true;
			} else {
				$short	=	false;
			}

			if ( $linked ) {
				$title	=	'<a href="' . $this->getUrl() . '"' . ( $short ? ' title="' . $cache[$id] . '"' : null ) . '>' . $title . '</a>';
			}
		}

		return $title;
	}

	public function getPost( $length = 0 ) {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$plugin		=	cbgjClass::getPlugin();
			$post		=	$this->get( 'post' );

			if ( $plugin->params->get( 'wall_post_content', 0 ) ) {
				$post	=	cbgjClass::prepareContentPlugins( $post );
			}

			$cache[$id]	=	( $plugin->params->get( 'wall_editor', 1 ) >= 2 ? $post : htmlspecialchars( $post ) );
		}

		$post			=	$cache[$id];

		if ( $post ) {
			$length		=	(int) $length;

			if ( $length && ( cbIsoUtf_strlen( strip_tags( $post ) ) > $length ) ) {
				$post	=	rtrim( trim( cbIsoUtf_substr( strip_tags( $post ), 0, $length ) ), '.' ) . '...';
			}
		}

		return $post;
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

	public function getReplies() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjWallData::getPosts( null, array( 'reply', '=', (int) $id ) );
		}

		return $cache[$id];
	}

	public function getReply() {
		static $cache	=	array();

		$id				=	$this->get( 'reply' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	cbgjWallData::getPosts( null, array( 'id', '=', (int) $id ), null, null, false );
		}

		return $cache[$id];
	}

	public function repliesCount() {
		static $cache	=	array();

		$id				=	$this->get( 'id' );

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]	=	count( $this->getReplies() );
		}

		return $cache[$id];
	}

	public function getSubstitutionExtras( $cbtxt = false ) {
		$extras				=	array(	'wall_id' => $this->get( 'id' ),
										'wall_title' => $this->getTitle(),
										'wall_title_linked' => $this->getTitle( 0, true ),
										'wall_post' => $this->getPost(),
										'wall_url' => $this->getUrl(),
										'wall_date' => cbFormatDate( $this->get( 'date' ), 1, false ),
										'wall_owner' => $this->getOwnerName(),
										'wall_owner_linked' => $this->getOwnerName( true ),
										'wall_published' => $this->get( 'published' ),
										'wall_user_id' => $this->get( 'user_id' ),
										'wall_group' => $this->get( 'group' ),
										'wall_replies_count' => $this->repliesCount()
									);

		if ( $this->get( 'reply' ) ) {
			$reply			=	array(	'wall_reply_id' => $this->getReply()->get( 'id' ),
										'wall_reply_title' => $this->getReply()->getTitle(),
										'wall_reply_title_linked' => $this->getReply()->getTitle( 0, true ),
										'wall_reply_post' => $this->getReply()->getPost(),
										'wall_reply_url' => $this->getReply()->getUrl(),
										'wall_reply_date' => cbFormatDate( $this->getReply()->get( 'date' ), 1, false ),
										'wall_reply_owner' => $this->getReply()->getOwnerName(),
										'wall_reply_owner_linked' => $this->getReply()->getOwnerName( true ),
										'wall_reply_published' => $this->getReply()->get( 'published' ),
										'wall_reply_user_id' => $this->getReply()->get( 'user_id' ),
										'wall_reply_group' => $this->getReply()->get( 'group' ),
										'wall_reply_replies_count' => $this->getReply()->repliesCount()
									);
		} else {
			$reply			=	array(	'wall_reply_id' => null,
										'wall_reply_title' => null,
										'wall_reply_title_linked' => null,
										'wall_reply_post' => null,
										'wall_reply_url' => null,
										'wall_reply_date' => null,
										'wall_reply_owner' => null,
										'wall_reply_owner_linked' => null,
										'wall_reply_published' => null,
										'wall_reply_user_id' => null,
										'wall_reply_group' => null,
										'wall_reply_replies_count' => null
									);
		}

		$extras				=	array_merge( $extras, $reply );

		if ( $cbtxt ) foreach ( $extras as $k => $v ) {
			$extras["[$k]"]	=	$v;

			unset( $extras[$k] );
		}

		return $extras;
	}
}

class cbgjWallData {

    static public function getPosts( $access = array(), $filtering = array(), $ordering = array(), $limits = 0, $list = true ) {
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
                .	"\n FROM " . $_CB_database->NameQuote( '#__groupjive_plugin_wall' ) . " AS a";

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
            $cache[$id]		=	$_CB_database->loadObjectList( 'id', 'cbgjWall', array( & $_CB_database ) );
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
                $rows		=	new cbgjWall( $_CB_database );
            }

            return $rows;
        }
    }
}
?>