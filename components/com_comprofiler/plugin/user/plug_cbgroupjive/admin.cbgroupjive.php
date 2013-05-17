<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class cbgjAdmin extends cbPluginHandler {

	/**
	 * render backend edit plugin view
	 *
	 * @param object $row
	 * @param string $option
	 * @param string $task
	 * @param int    $uid
	 * @param string $action
	 * @param string $element
	 * @param int    $mode
	 * @param object $pluginParams
	 */
	public function editPluginView( $row, $option, $task, $uid, $action, $element, $mode, $pluginParams ) {
		global $_CB_framework, $_CB_database, $_CB_Backend_Menu, $_CB_Backend_task, $_GJ_Backend_Title, $_PLUGINS;

		if ( ! CBuser::getMyInstance()->authoriseAction( 'core.manage' ) ) {
			cbRedirect( $_CB_framework->backendUrl( 'index.php' ), _UE_NOT_AUTHORIZED, 'error' );
		}

		outputCbJs( 2 );
		outputCbTemplate( 2 );

		$plugin					=	cbgjClass::getPlugin();

		$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . '/admin.' . $plugin->element . '.css' );

		require_once( $plugin->absPath . '/admin.' . $plugin->element . '.html.php' );

		$_CB_Backend_task		=	$task;
		$_GJ_Backend_Title		=	array();
		$_CB_Backend_Menu->mode	=	$plugin->element . 'Admin';

		$actions				=	explode( '.', $action );
		$action					=	( isset( $actions[0] ) ? $actions[0] : null );
		$function				=	( isset( $actions[1] ) ? $actions[1] : null );
		$id						=	cbGetParam( $_REQUEST, 'id', array( 0 ) );
		$order					=	cbGetParam( $_REQUEST, 'order', array( 0 ) );
		$user					=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		if ( ! is_array( $id ) ) {
			$id					=	array( $id );
		}

		if ( ! $id ) {
			$id					=	array( 0 );
		}

		if ( ! is_array( $order ) ) {
			$order				=	array( $order );
		}

		if ( ! $order ) {
			$order				=	array( 0 );
		}

		$save_mode				=	( $mode == 'applyPlugin' ? 'apply' : $function );

		ob_start();
		switch ( $action ) {
			case 'categories':
				switch ( $function ) {
					case 'menu':
						$this->createCategoryMenu( $id[0], $user, $plugin );
						break;
					case 'publish':
						cbSpoofCheck( 'plugin' );
						$this->stateCategory( $id, 1, $user, $plugin );
						break;
					case 'unpublish':
						cbSpoofCheck( 'plugin' );
						$this->stateCategory( $id, 0, $user, $plugin );
						break;
					case 'order':
						cbSpoofCheck( 'plugin' );
						$this->orderCategory( $id, $order, $user, $plugin );
						break;
					case 'orderup':
						cbSpoofCheck( 'plugin' );
						$this->orderCategory( $id[0], -1, $user, $plugin );
						break;
					case 'orderdown':
						cbSpoofCheck( 'plugin' );
						$this->orderCategory( $id[0], 1, $user, $plugin );
						break;
					case 'batch':
						$this->batchCategory( $id, $user, $plugin );
						break;
					case 'copy':
						$this->copyCategory( $id, $user, $plugin );
						break;
					case 'delete':
						cbSpoofCheck( 'plugin' );
						$this->deleteCategory( $id, $user, $plugin );
						break;
					case 'new':
						$this->showCategoryEdit( null, $user, $plugin );
						break;
					case 'edit':
						$this->showCategoryEdit( $id[0], $user, $plugin );
						break;
					case 'save':
					case 'apply':
						cbSpoofCheck( 'plugin' );
						$this->saveCategoryEdit( $id[0], $save_mode, $user, $plugin );
						break;
					case 'show':
					default:
						$this->showCategories( $user, $plugin );
						break;
				}
				break;
			case 'groups':
				switch ( $function ) {
					case 'menu':
						$this->createGroupMenu( $id[0], $user, $plugin );
						break;
					case 'publish':
						cbSpoofCheck( 'plugin' );
						$this->stateGroup( $id, 1, $user, $plugin );
						break;
					case 'unpublish':
						cbSpoofCheck( 'plugin' );
						$this->stateGroup( $id, 0, $user, $plugin );
						break;
					case 'order':
						cbSpoofCheck( 'plugin' );
						$this->orderGroup( $id, $order, $user, $plugin );
						break;
					case 'orderup':
						cbSpoofCheck( 'plugin' );
						$this->orderGroup( $id[0], -1, $user, $plugin );
						break;
					case 'orderdown':
						cbSpoofCheck( 'plugin' );
						$this->orderGroup( $id[0], 1, $user, $plugin );
						break;
					case 'batch':
						$this->batchGroup( $id, $user, $plugin );
						break;
					case 'copy':
						$this->copyGroup( $id, $user, $plugin );
						break;
					case 'delete':
						cbSpoofCheck( 'plugin' );
						$this->deleteGroup( $id, $user, $plugin );
						break;
					case 'new':
						$this->showGroupEdit( null, $user, $plugin );
						break;
					case 'edit':
						$this->showGroupEdit( $id[0], $user, $plugin );
						break;
					case 'save':
					case 'apply':
						cbSpoofCheck( 'plugin' );
						$this->saveGroupEdit( $id[0], $save_mode, $user, $plugin );
						break;
					case 'show':
					default:
						$this->showGroups( $user, $plugin );
						break;
				}
				break;
			case 'users':
				switch ( $function ) {
					case 'ban':
						cbSpoofCheck( 'plugin' );
						$this->statusUser( $id, -1, $user, $plugin );
						break;
					case 'active':
						cbSpoofCheck( 'plugin' );
						$this->statusUser( $id, 1, $user, $plugin );
						break;
					case 'inactive':
						cbSpoofCheck( 'plugin' );
						$this->statusUser( $id, 0, $user, $plugin );
						break;
					case 'mod':
						cbSpoofCheck( 'plugin' );
						$this->statusUser( $id, 2, $user, $plugin );
						break;
					case 'admin':
						cbSpoofCheck( 'plugin' );
						$this->statusUser( $id, 3, $user, $plugin );
						break;
					case 'owner':
						cbSpoofCheck( 'plugin' );
						$this->statusUser( $id, 4, $user, $plugin );
						break;
					case 'batch':
						$this->batchUser( $id, $user, $plugin );
						break;
					case 'delete':
						cbSpoofCheck( 'plugin' );
						$this->deleteUser( $id, $user, $plugin );
						break;
					case 'new':
						$this->showUserEdit( null, $user, $plugin );
						break;
					case 'edit':
						$this->showUserEdit( $id[0], $user, $plugin );
						break;
					case 'save':
					case 'apply':
						cbSpoofCheck( 'plugin' );
						$this->saveUserEdit( $id[0], $save_mode, $user, $plugin );
						break;
					case 'show':
					default:
						$this->showUsers( $user, $plugin );
						break;
				}
				break;
			case 'invites':
				switch ( $function ) {
					case 'delete':
						cbSpoofCheck( 'plugin' );
						$this->deleteInvite( $id, $user, $plugin );
						break;
					case 'show':
					default:
						$this->showInvites( $user, $plugin );
						break;
				}
				break;
			case 'config':
				switch ( $function ) {
					case 'save':
						cbSpoofCheck( 'plugin' );
						$this->saveConfig( $_POST, $user, $plugin );
						break;
					case 'show':
					default:
						$this->showConfig( $user, $plugin );
						break;
				}
				break;
			case 'tools':
				switch ( $function ) {
					case 'migrate':
						$this->showMigrate( $user, $plugin );
						break;
					case 'delmigrate':
						$this->deleteMigrate( $user, $plugin );
						break;
					case 'show':
					default:
						$this->showTools( $user, $plugin );
						break;
				}
				break;
			case 'fix':
				switch ( $function ) {
					case 'categories':
						$this->fixCategories( $id[0], $user, $plugin );
						break;
					case 'groups':
						$this->fixGroups( $id[0], $user, $plugin );
						break;
					case 'users':
						$this->fixUsers( $id[0], $user, $plugin );
						break;
					default:
						$this->showTools( $user, $plugin );
						break;
				}
				break;
			case 'integrations':
				$this->showIntegrations( $user, $plugin );
				break;
			case 'menus':
				switch ( $function ) {
					case 'save':
						$this->saveMenus( $user, $plugin );
						break;
					default:
						$this->showMenus( $user, $plugin );
						break;
				}
				break;
			case 'plugin':
				$_PLUGINS->trigger( 'gj_onPluginBE', array( array( $function, $id, $order, $save_mode ), $user, $plugin ) );
				break;
			default:
				switch ( $function ) {
					case 'menu':
						$this->createPluginMenu( $user, $plugin );
						break;
					case 'show':
					default:
						$this->showPlugin( $user, $plugin );
						break;
				}
				break;
		}
		$html					=	ob_get_contents();
		ob_end_clean();

		ob_start();
		include( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/toolbar.cbgroupjive.php' );
		$toolbar				=	 ob_get_contents();
		ob_end_clean();

		$title					=	( isset( $_GJ_Backend_Title[0] ) ? $_GJ_Backend_Title[0] : null );
		$class					=	( isset( $_GJ_Backend_Title[1] ) ? ' ' . $_GJ_Backend_Title[1] : null );
		$return					=	'<div style="margin:0px;border-width:0px;padding:0px;float:left;width:100%;text-align:left;" class="gjAdmin">'
								.		'<div id="cbAdminMainWrapper" style="margin:0px;border-width:0px;padding:0px;float:none;width:auto;">'
								.		'<div style="float:right;" class="gjAdminToolbar">'
								.			$toolbar
								.		'</div>'
								.		'<div style="float:left;" class="header' . $class . '">'
								.			$title
								.		'</div>'
								.		'<div style="clear:both;"></div>'
								.		'<div style="float:left;width:100%;margin-top:10px;">'
								.			$html
								.		'</div>'
								.		'<div style="clear:both;"></div>'
								.		'</div>'
								.	'</div>';

		echo $return;
	}

	/**
	 * prepare backend plugin render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showPlugin( $user, $plugin ) {
		$menu				=	new stdClass();

		$menu->categories	=	'<a href="' . cbgjClass::getPluginURL( array( 'categories' ) ) . '">'
							.		'<div><img src="' . $plugin->livePath . '/images/icon-128-categories.png" /></div>'
							.		'<div>' . CBTxt::Th( 'Categories' ) . '</div>'
							.	'</a>';

		$menu->groups		=	'<a href="' . cbgjClass::getPluginURL( array( 'groups' ) ) . '">'
							.		'<div><img src="' . $plugin->livePath . '/images/icon-128-groups.png" /></div>'
							.		'<div>' . CBTxt::Th( 'Groups' ) . '</div>'
							.	'</a>';

		$menu->invites		=	'<a href="' . cbgjClass::getPluginURL( array( 'invites' ) ) . '">'
							.		'<div><img src="' . $plugin->livePath . '/images/icon-128-invites.png" /></div>'
							.		'<div>' . CBTxt::Th( 'Invites' ) . '</div>'
							.	'</a>';

		$menu->users		=	'<a href="' . cbgjClass::getPluginURL( array( 'users' ) ) . '">'
							.		'<div><img src="' . $plugin->livePath . '/images/icon-128-users.png" /></div>'
							.		'<div>' . CBTxt::Th( 'Users' ) . '</div>'
							.	'</a>';

		$menu->config		=	'<a href="' . cbgjClass::getPluginURL( array( 'config' ) ) . '">'
							.		'<div><img src="' . $plugin->livePath . '/images/icon-128-config.png" /></div>'
							.		'<div>' . CBTxt::Th( 'Config' ) . '</div>'
							.	'</a>';

		$menu->tools		=	'<a href="' . cbgjClass::getPluginURL( array( 'tools' ) ) . '">'
							.		'<div><img src="' . $plugin->livePath . '/images/icon-128-tools.png" /></div>'
							.		'<div>' . CBTxt::Th( 'Tools' ) . '</div>'
							.	'</a>';

		$menu->integrations	=	'<a href="' . cbgjClass::getPluginURL( array( 'integrations' ) ) . '">'
							.		'<div><img src="' . $plugin->livePath . '/images/icon-128-integrations.png" /></div>'
							.		'<div>' . CBTxt::Th( 'Integrations' ) . '</div>'
							.	'</a>';

		$menu->menus		=	'<a href="' . cbgjClass::getPluginURL( array( 'menus' ) ) . '">'
							.		'<div><img src="' . $plugin->livePath . '/images/icon-128-menus.png" /></div>'
							.		'<div>' . CBTxt::Th( 'Menus' ) . '</div>'
							.	'</a>';

		$xml				=	new CBSimpleXMLElement( trim( file_get_contents( $plugin->xml ) ) );

		HTML_cbgjAdmin::showPlugin( $menu, $xml, $user, $plugin );
	}

	/**
	 * create internal CMS menu link to plugin
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function createPluginMenu( $user, $plugin ) {
		$general_title	=	CBTxt::T( $plugin->params->get( 'general_title', null ) );
		$frontend_url	=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element;

		if ( ! cbgjClass::setMenu( ( $general_title ? $general_title : $plugin->name ), $frontend_url, $plugin ) ) {
			cbgjClass::getPluginURL( array(), CBTxt::T( 'Plugin menu failed to create!' ), false, true, 'error' );
		}

		cbgjClass::getPluginURL( array(), CBTxt::T( 'Plugin menu created successfully!' ), false, true );
	}

	/**
	 * prepare backend categories render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showCategories( $user, $plugin ) {
		global $_CB_framework;

		$paging						=	new cbgjPaging( 'categories' );

		$limit						=	$paging->getlimit( 30 );
		$limitstart					=	$paging->getLimistart();
		$search						=	$paging->getFilter( 'search' );
		$parent						=	$paging->getFilter( 'parent' );
		$access						=	$paging->getFilter( 'access' );
		$state						=	$paging->getFilter( 'state' );
		$creator					=	$paging->getFilter( 'creator' );
		$id							=	$paging->getFilter( 'id' );
		$where						=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]				=	array( 'name', 'CONTAINS', $search );
		}

		if ( isset( $parent ) && ( $parent != '' ) ) {
			$where[]				=	array( 'parent', '=', (int) $parent );
		}

		if ( isset( $access ) && ( $access != '' ) ) {
			$where[]				=	array( 'access', '=', (int) $access );
		}

		if ( isset( $state ) && ( $state != '' ) ) {
			$where[]				=	array( 'published', '=', (int) $state );
		}

		if ( isset( $creator ) && ( $creator != '' ) ) {
			$where[]				=	array( 'b.id', '=', (int) $creator, array( 'b.username', 'CONTAINS', $creator ), array( 'b.name', 'CONTAINS', $creator ) );
		}

		if ( isset( $id ) && ( $id != '' ) ) {
			$where[]				=	array( 'id', '=', (int) $id );
		}

		$searching					=	( count( $where ) ? true : false );

		$total						=	count( cbgjData::getCategories( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart				=	0;
		}

		$pageNav					=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows						=	array_values( cbgjData::getCategories( null, $where, null, array( $pageNav->limitstart, $pageNav->limit ), true, ( $parent ? $parent : 0 ) ) );

		$input						=	array();

		$categories					=	cbgjClass::getCategoryOptions();

		if ( $categories ) {
			array_unshift( $categories, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );
			array_unshift( $categories, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Parent -' ) ) );

			$input['parent']		=	$paging->getInputSelect( 'adminForm', 'parent', $categories, $parent );
			$input['batch_parent']	=	moscomprofilerHTML::selectList( $categories, 'batch_parent', null, 'value', 'text', null, 1, false, false );
		} else {
			$input['parent']		=	'-';
			$input['batch_parent']	=	'-';
		}

		$listAccess					=	array();
		$listAccess[]				=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Access -' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( '- Everybody' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
		$listAccess					=	array_merge( $listAccess, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$input['access']			=	$paging->getInputSelect( 'adminForm', 'access', $listAccess, $access );
		$input['batch_access']		=	moscomprofilerHTML::selectList( $listAccess, 'batch_access', null, 'value', 'text', null, 1, false, false );

		$listState					=	array();
		$listState[]				=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select State -' ) );
		$listState[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Published' ) );
		$listState[]				=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Unpublished' ) );
		$input['state']				=	$paging->getInputSelect( 'adminForm', 'state', $listState, $state );

		$input['search']			=	$paging->getInputText( 'adminForm', 'search', $search, '30' );
		$input['creator']			=	$paging->getInputText( 'adminForm', 'creator', $creator, '15' );
		$input['batch_creator']		=	'<input type="text" id="batch_creator" name="batch_creator" size="6" />';
		$input['id']				=	$paging->getInputText( 'adminForm', 'id', $id, '6' );

		$pageNav->searching			=	$searching;

		HTML_cbgjAdmin::showCategories( $rows, $pageNav, $input, $user, $plugin );
	}

	/**
	 * prepare backend category edit render
	 *
	 * @param int    $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showCategoryEdit( $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$categoryEditor				=	$plugin->params->get( 'category_editor', 1 );

		$row						=	cbgjData::getCategories( null, array( 'id', '=', (int) $id ), null, null, false );

		$input						=	array();

		$input['publish']			=	moscomprofilerHTML::yesnoSelectList( 'published', null, (int) cbgjClass::getCleanParam( true, 'published', $row->get( 'published', ( $plugin->params->get( 'category_approve', 0 ) ? 0 : 1 ) ) ) );

		if ( $row->get( 'id' ) ) {
			$categories				=	cbgjClass::getCategoryOptions( null, 0, array( $row->get( 'id' ) ) );
		} else {
			$categories				=	cbgjClass::getCategoryOptions();
		}

		if ( $categories ) {
			array_unshift( $categories, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );

			$input['parent']		=	moscomprofilerHTML::selectList( $categories, 'parent', null, 'value', 'text', (int) cbgjClass::getCleanParam( true, 'parent', $row->get( 'parent', 0 ) ), 1, false, false );
		} else {
			$input['parent']		=	CBTxt::Th( 'There currently are no categories.' );
		}

		$input['name']				=	'<input type="text" id="name" name="name" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'name', $row->get( 'name' ) ) ) . '" class="inputbox" size="40" />';

		if ( $categoryEditor >= 2 ) {
			$description			=	cbgjClass::getHTMLCleanParam( true, 'description', $row->get( 'description' ) );
		} else {
			$description			=	cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) );
		}

		if ( $categoryEditor == 3 ) {
			$input['description']	=	$_CB_framework->displayCmsEditor( 'description', $description, 400, 200, 40, 5 );
		} else {
			$input['description']	=	'<textarea id="description" name="description" class="inputbox" cols="40" rows="5">' . htmlspecialchars( $description ) . '</textarea>';
		}

		$input['file']				=	'<input type="file" id="logo" name="logo" class="inputbox" size="40" />';
		$input['del_logo']			=	'<input type="checkbox" id="del_logo" name="del_logo" class="inputbox" value="1" /> <label for="del_logo">' . CBTxt::T( 'Delete logo?' ) . '</label>';

		$listTypes					=	array();
		$listTypes[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Open' ) );
		$listTypes[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Approval' ) );
		$listTypes[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Invite' ) );
		$input['types']				=	moscomprofilerHTML::selectList( $listTypes, 'types[]', 'size="4" multiple="multiple" class="inputbox required"', 'value', 'text', explode( '|*|', cbgjClass::getCleanParam( true, 'types', $row->get( 'types', $plugin->params->get( 'category_types_default', '1|*|2|*|3' ) ) ) ), 1, false, false );

		$listAccess					=	array();
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( '- Everybody' ) );
		$listAccess[]				=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
		$listAccess					=	array_merge( $listAccess, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$input['access']			=	moscomprofilerHTML::selectList( $listAccess, 'access', 'class="inputbox required"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'access', $row->get( 'access', $plugin->params->get( 'category_access_default', -2 ) ) ), 1, false, false );

		$input['create']			=	moscomprofilerHTML::yesnoSelectList( 'create', null, (int) cbgjClass::getCleanParam( true, 'create', $row->get( 'create' ), $plugin->params->get( 'category_create_default', 1 ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$listCreate					=	array();
		$listCreate[]				=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
		$listCreate					=	array_merge( $listCreate, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$input['create_access']		=	moscomprofilerHTML::selectList( $listCreate, 'create_access', 'class="inputbox"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'create_access', $row->get( 'create_access', $plugin->params->get( 'category_createaccess_default', -1 ) ) ), 1, false, false );

		$input['nested']			=	moscomprofilerHTML::yesnoSelectList( 'nested', null, (int) cbgjClass::getCleanParam( true, 'nested', $row->get( 'nested' ), $plugin->params->get( 'category_nested_default', 1 ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['nested_access']		=	moscomprofilerHTML::selectList( $listCreate, 'nested_access', 'class="inputbox"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'nested_access', $row->get( 'nested_access', $plugin->params->get( 'category_nestedaccess_default', -1 ) ) ), 1, false, false );
		$input['owner']				=	'<input type="text" id="user_id" name="user_id" value="' . (int) cbgjClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) . '" class="inputbox required digits" size="6" />';

		cbgjClass::displayMessage( $message );

		HTML_cbgjAdmin::showCategoryEdit( $row, $input, $user, $plugin );
	}

	/**
	 * create internal CMS menu link to category
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function createCategoryMenu( $id, $user, $plugin ) {
		if ( $id == 'all' ) {
			$frontend_url	=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=all';

			if ( ! cbgjClass::setMenu( CBTxt::T( 'Categories' ), $frontend_url, $plugin ) ) {
				cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category menu failed to create!' ), false, true, 'error' );
			}

			cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category menu created successfully!' ), false, true );
		} else{
			$row			=	cbgjData::getCategories( null, array( 'id', '=', (int) $id ), null, null, false );

			$frontend_url	=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=show&cat=' . $row->get( 'id' );

			if ( ! cbgjClass::setMenu( $row->get( 'name' ), $frontend_url, $plugin ) ) {
				cbgjClass::getPluginURL( array( 'categories', 'edit', (int) $row->get( 'id' ) ), CBTxt::T( 'Category menu failed to create!' ), false, true, 'error' );
			}

			cbgjClass::getPluginURL( array( 'categories', 'edit', (int) $row->get( 'id' ) ), CBTxt::T( 'Category menu created successfully!' ), false, true );
		}
	}

	/**
	 * save category
	 *
	 * @param int $id
	 * @param string $task
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function saveCategoryEdit( $id, $task, $user, $plugin ) {
		$row		=	cbgjData::getCategories( null, array( 'id', '=', (int) $id ), null, null, false );

		$row->set( 'published', (int) cbgjClass::getCleanParam( true, 'published', $row->get( 'published', ( $plugin->params->get( 'category_approve', 0 ) ? 0 : 1 ) ) ) );
		$row->set( 'parent', (int) cbgjClass::getCleanParam( true, 'parent', $row->get( 'parent', 0 ) ) );
		$row->set( 'user_id', (int) cbgjClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) );
		$row->set( 'name', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'name', $row->get( 'name' ) ) ) );

		if ( $plugin->params->get( 'category_editor', 1 ) >= 2 ) {
			$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'description', $row->get( 'description' ) ) ) );
		} else {
			$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) ) ) );
		}

		$row->set( 'types', cbgjClass::getCleanParam( true, 'types', $row->get( 'types', $plugin->params->get( 'category_types_default', '1|*|2|*|3' ) ) ) );
		$row->set( 'access', (int) cbgjClass::getCleanParam( true, 'access', $row->get( 'access', $plugin->params->get( 'category_access_default', -2 ) ) ) );
		$row->set( 'create', (int) cbgjClass::getCleanParam( true, 'create', $row->get( 'create', $plugin->params->get( 'category_create_default', 1 ) ) ) );
		$row->set( 'create_access', (int) cbgjClass::getCleanParam( true, 'create_access', $row->get( 'create_access', $plugin->params->get( 'category_createaccess_default', -1 ) ) ) );
		$row->set( 'nested', (int) cbgjClass::getCleanParam( true, 'nested', $row->get( 'nested', $plugin->params->get( 'category_nested_default', 1 ) ) ) );
		$row->set( 'nested_access', (int) cbgjClass::getCleanParam( true, 'nested_access', $row->get( 'nested_access', $plugin->params->get( 'category_nestedaccess_default', -1 ) ) ) );
		$row->set( 'date', $row->get( 'date', cbgjClass::getUTCDate() ) );
		$row->set( 'ordering', (int) $row->get( 'ordering', 99999 ) );

		if ( $row->get( 'name' ) == '' ) {
			$row->set( '_error', CBTxt::T( 'Name not specified!' ) );
		} elseif ( ! $row->get( 'user_id' ) ) {
			$row->set( '_error', CBTxt::T( 'Owner not specified!' ) );
		} elseif ( ! $row->get( 'types' ) ) {
			$row->set( '_error', CBTxt::T( 'Types not specified!' ) );
		}

		if ( (int) cbgjClass::getCleanParam( true, 'del_logo', 0 ) ) {
			$row->unsetLogo();
		}

		if ( $row->getError() || ( ! $row->store() ) ) {
			$this->showCategoryEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Category failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
		}

		$successMsg	=	CBTxt::T( 'Category saved successfully!' );

		$row->storeLogo( 'logo' );

		if ( $row->getError() ) {
			cbgjClass::displayMessage( $successMsg, 'message' );

			$this->showCategoryEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Logo failed to upload! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
		}

		if ( $task == 'apply' ) {
			cbgjClass::getPluginURL( array( 'categories', 'edit', (int) $row->get( 'id' ) ), $successMsg, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'categories' ), $successMsg, false, true );
		}
	}

	/**
	 * set category publish status
	 *
	 * @param array  $ids
	 * @param int    $state
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function stateCategory( $ids, $state, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjData::getCategories( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->storeState( $state ) ) {
					cbgjClass::getPluginURL( array( 'categories' ), CBTxt::P( 'Category state failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category state saved successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category not found.' ), false, true, 'error' );
	}

	/**
	 * set category order
	 *
	 * @param array  $ids
	 * @param int    $order
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function orderCategory( $ids, $order, $user, $plugin ) {
		if ( is_array( $ids ) ) {
			for ( $i = 0; $i < count( $ids ); $i++ ) {
				$row	=	cbgjData::getCategories( null, array( 'id', '=', (int) $ids[$i] ), null, null, false );

				if ( ! $row->storeOrder( $order[$i] ) ) {
					cbgjClass::getPluginURL( array( 'categories' ), CBTxt::P( 'Category order failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}
		} else {
			$row		=	cbgjData::getCategories( null, array( 'id', '=', (int) $ids ), null, null, false );

			$row->move( $order );
		}

		cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category order saved successfully!' ), false, true );
	}

	/**
	 * delete category
	 *
	 * @param array  $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteCategory( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjData::getCategories( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->deleteAll() ) {
					cbgjClass::getPluginURL( array( 'categories' ), CBTxt::P( 'Category failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category deleted successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category not found.' ), false, true, 'error' );
	}

	/**
	 * batch process categories
	 *
	 * @param array  $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function batchCategory( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			$parent					=	cbGetParam( $_REQUEST, 'batch_parent', null );
			$access					=	cbGetParam( $_REQUEST, 'batch_access', null );
			$owner					=	cbGetParam( $_REQUEST, 'batch_owner', null );

			if ( ( $parent != '' ) || ( $access != '' ) || ( $owner != '' ) ) {
				foreach ( $ids as $id ) {
					$row			=	cbgjData::getCategories( null, array( 'id', '=', (int) $id ), null, null, false );
					$process		=	false;

					if ( ( $parent != '' ) && ( $parent != $row->get( 'id' ) ) ) {
						$row->set( 'parent', (int) $parent );

						$process	=	true;
					}

					if ( $access != '' ) {
						$row->set( 'access', (int) $access );

						$process	=	true;
					}

					if ( $owner != '' ) {
						$row->set( 'user_id', (int) $owner );

						$process	=	true;
					}

					if ( $process ) {
						if ( $row->getError() || ( ! $row->store() ) ) {
							cbgjClass::getPluginURL( array( 'categories' ), CBTxt::P( 'Category failed to process! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
						}
					}
				}

				cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category batch process successfully!' ), false, true );
			}

			cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Nothing to process.' ), false, true, 'error' );
		}

		cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category not found.' ), false, true, 'error' );
	}

	/**
	 * copy category
	 *
	 * @param array  $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function copyCategory( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjData::getCategories( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->storeCopy() ) {
					cbgjClass::getPluginURL( array( 'categories' ), CBTxt::P( 'Category failed to copy! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category copy successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'categories' ), CBTxt::T( 'Category not found.' ), false, true, 'error' );
	}

	/**
	 * prepare backend groups render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showGroups( $user, $plugin ) {
		global $_CB_framework;

		$paging								=	new cbgjPaging( 'groups' );

		$limit								=	$paging->getlimit( 30 );
		$limitstart							=	$paging->getLimistart();
		$search								=	$paging->getFilter( 'search' );
		$category							=	$paging->getFilter( 'category' );
		$parent								=	$paging->getFilter( 'parent' );
		$access								=	$paging->getFilter( 'access' );
		$type								=	$paging->getFilter( 'type' );
		$state								=	$paging->getFilter( 'state' );
		$creator							=	$paging->getFilter( 'creator' );
		$id									=	$paging->getFilter( 'id' );
		$where								=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]						=	array( 'name', 'CONTAINS', $search );
		}

		if ( isset( $category ) && ( $category != '' ) ) {
			$where[]						=	array( 'category', '=', (int) $category );
		}

		if ( isset( $parent ) && ( $parent != '' ) ) {
			$where[]						=	array( 'parent', '=', (int) $parent );
		}

		if ( isset( $access ) && ( $access != '' ) ) {
			$where[]						=	array( 'access', '=', (int) $access );
		}

		if ( isset( $type ) && ( $type != '' ) ) {
			$where[]						=	array( 'type', '=', (int) $type );
		}

		if ( isset( $state ) && ( $state != '' ) ) {
			$where[]						=	array( 'published', '=', (int) $state );
		}

		if ( isset( $creator ) && ( $creator != '' ) ) {
			$where[]						=	array( 'd.id', '=', (int) $creator, array( 'd.username', 'CONTAINS', $creator ), array( 'd.name', 'CONTAINS', $creator ) );
		}

		if ( isset( $id ) && ( $id != '' ) ) {
			$where[]						=	array( 'id', '=', (int) $id );
		}

		$searching							=	( count( $where ) ? true : false );

		$total								=	count( cbgjData::getGroups( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart						=	0;
		}

		$pageNav							=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows								=	array_values( cbgjData::getGroups( null, $where, array( array( 'b.ordering', 'ASC' ), array( 'ordering', 'ASC' ) ), array( $pageNav->limitstart, $pageNav->limit ), true, ( $parent ? $parent : 0 ) ) );

		$input								=	array();

		$categories							=	cbgjClass::getCategoryOptions();

		if ( $categories ) {
			array_unshift( $categories, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Category -' ) ) );

			$input['category']				=	$paging->getInputSelect( 'adminForm', 'category', $categories, $category );
			$input['batch_category']		=	moscomprofilerHTML::selectList( $categories, 'batch_category', null, 'value', 'text', null, 1, false, false );
		} else {
			$input['category']				=	'-';
			$input['batch_category']		=	'-';
		}

		$groups								=	cbgjClass::getGroupOptions();

		if ( $groups ) {
			array_unshift( $groups, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );
			array_unshift( $groups, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Parent -' ) ) );

			$input['batch_parent']			=	moscomprofilerHTML::selectList( $groups, 'batch_parent', null, 'value', 'text', null, 1, false, false );

			if ( $category ) {
				$groups						=	cbgjClass::getGroupOptions( null, $category );

				array_unshift( $groups, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );
				array_unshift( $groups, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Parent -' ) ) );
			}

			if ( $groups ) {
				$input['parent']			=	$paging->getInputSelect( 'adminForm', 'parent', $groups, $parent );
			} else {
				$input['parent']			=	'-';
			}
		} else {
			$input['parent']				=	'-';
			$input['batch_parent']			=	'-';
		}

		$listAccess							=	array();
		$listAccess[]						=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Access -' ) );
		$listAccess[]						=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( '- Everybody' ) );
		$listAccess[]						=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
		$listAccess							=	array_merge( $listAccess, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$input['access']					=	$paging->getInputSelect( 'adminForm', 'access', $listAccess, $access );
		$input['batch_access']				=	moscomprofilerHTML::selectList( $listAccess, 'batch_access', null, 'value', 'text', null, 1, false, false );

		$listType							=	array();
		$listType[]							=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Type -' ) );
		$listType[]							=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Open' ) );
		$listType[]							=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Approval' ) );
		$listType[]							=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Invite' ) );
		$input['type']						=	$paging->getInputSelect( 'adminForm', 'type', $listType, $type );
		$input['batch_type']				=	moscomprofilerHTML::selectList( $listType, 'batch_type', null, 'value', 'text', null, 1, false, false );

		$listState							=	array();
		$listState[]						=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select State -' ) );
		$listState[]						=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Published' ) );
		$listState[]						=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Unpublished' ) );
		$input['state']						=	$paging->getInputSelect( 'adminForm', 'state', $listState, $state );

		$input['search']					=	$paging->getInputText( 'adminForm', 'search', $search, '30' );
		$input['creator']					=	$paging->getInputText( 'adminForm', 'creator', $creator, '15' );
		$input['batch_creator']				=	'<input type="text" id="batch_creator" name="batch_creator" size="6" />';
		$input['id']						=	$paging->getInputText( 'adminForm', 'id', $id, '6' );

		$pageNav->searching					=	$searching;

		HTML_cbgjAdmin::showGroups( $rows, $pageNav, $input, $user, $plugin );
	}

	/**
	 * prepare backend group edit render
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param string $message
	 */
	private function showGroupEdit( $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$groupEditor						=	$plugin->params->get( 'group_editor', 1 );

		$row								=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );
		$category							=	$row->getCategory();

		$input								=	array();

		$input['publish']					=	moscomprofilerHTML::yesnoSelectList( 'published', null, (int) cbgjClass::getCleanParam( true, 'published', $row->get( 'published', ( $plugin->params->get( 'group_approve', 0 ) ? 0 : 1 ) ) ) );

		$categories							=	cbgjClass::getCategoryOptions();

		if ( $categories ) {
			$input['category']				=	moscomprofilerHTML::selectList( $categories, 'category', null, 'value', 'text', (int) cbgjClass::getCleanParam( true, 'category', $row->get( 'category', $category->get( 'id' ) ) ), 1, false, false );
		} else {
			$input['category']				=	CBTxt::Th( 'There currently are no categories.' );
		}

		if ( $row->get( 'id' ) ) {
			$groups							=	cbgjClass::getGroupOptions( null, $category->get( 'id' ), 0, array( $row->get( 'id' ) ) );
		} else {
			$groups							=	cbgjClass::getGroupOptions( null, $category->get( 'id' ) );
		}

		if ( $groups ) {
			array_unshift( $groups, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );

			$input['parent']				=	moscomprofilerHTML::selectList( $groups, 'parent', null, 'value', 'text', (int) cbgjClass::getCleanParam( true, 'parent', $row->get( 'parent', 0 ) ), 1, false, false );
		} else {
			if ( $category->get( 'id' ) ) {
				$input['parent']			=	CBTxt::Th( 'There currently are no groups.' );
			} else {
				$input['parent']			=	CBTxt::Th( 'Please save group with category before selecting parent group.' );
			}
		}

		$input['name']						=	'<input type="text" id="name" name="name" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'name', $row->get( 'name' ) ) ) . '" class="inputbox required" size="40" />';

		if ( $groupEditor >= 2 ) {
			$description					=	cbgjClass::getHTMLCleanParam( true, 'description', $row->get( 'description' ) );
		} else {
			$description					=	cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) );
		}

		if ( $groupEditor == 3 ) {
			$input['description']			=	$_CB_framework->displayCmsEditor( 'description', $description, 400, 200, 40, 5 );
		} else {
			$input['description']			=	'<textarea id="description" name="description" class="inputbox" cols="40" rows="5">' . htmlspecialchars( $description ) . '</textarea>';
		}

		$input['file']						=	'<input type="file" id="logo" name="logo" class="inputbox" size="40" />';
		$input['del_logo']					=	'<input type="checkbox" id="del_logo" name="del_logo" class="inputbox" value="1" /> <label for="del_logo">' . CBTxt::T( 'Delete logo?' ) . '</label>';

		$listType							=	array();
		$listType[]							=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Open' ) );
		$listType[]							=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Approval' ) );
		$listType[]							=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Invite' ) );
		$input['type']						=	moscomprofilerHTML::selectList( $listType, 'type', 'class="inputbox required"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'type', $row->get( 'type', $plugin->params->get( 'group_type_default', 1 ) ) ), 1, false, false );

		$listAccess							=	array();
		$listAccess[]						=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( '- Everybody' ) );
		$listAccess[]						=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
		$listAccess							=	array_merge( $listAccess, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$input['access']					=	moscomprofilerHTML::selectList( $listAccess, 'access', 'class="inputbox required"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'access', $row->get( 'access', $plugin->params->get( 'group_access_default', -2 ) ) ), 1, false, false );

		$listInvite							=	array();
		$listInvite[]						=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Group Users' ) );
		$listInvite[]						=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . CBTxt::T( 'Group Moderators' ) );
		$listInvite[]						=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . CBTxt::T( 'Group Admins' ) );
		$listInvite[]						=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . CBTxt::T( 'Group Owner' ) );
		$input['invite']					=	moscomprofilerHTML::selectList( $listInvite, 'invite', 'class="inputbox required"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'invite', $row->get( 'invite', $plugin->params->get( 'group_invite_default', 0 ) ) ), 1, false, false );

		$input['users']						=	moscomprofilerHTML::yesnoSelectList( 'users', null, (int) cbgjClass::getCleanParam( true, 'users', $row->get( 'users', $plugin->params->get( 'group_users_default', 1 ) ) ) );

		$input['nested']					=	moscomprofilerHTML::yesnoSelectList( 'nested', null, (int) cbgjClass::getCleanParam( true, 'nested', $row->get( 'nested', $plugin->params->get( 'group_nested_default', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['nested_access']				=	moscomprofilerHTML::selectList( $listInvite, 'nested_access', 'class="inputbox"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'nested_access', $row->get( 'nested_access', $plugin->params->get( 'group_nestedaccess_default', -1 ) ) ), 1, false, false );

		$input['owner']						=	'<input type="text" id="user_id" name="user_id" value="' . (int) cbgjClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) . '" class="inputbox required digits" size="6" />';

		cbgjClass::displayMessage( $message );

		HTML_cbgjAdmin::showGroupEdit( $row, $category, $input, $user, $plugin );
	}

	/**
	 * create internal CMS menu link to group
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function createGroupMenu( $id, $user, $plugin ) {
		if ( $id == 'all' ) {
			$frontend_url	=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=all';

			if ( ! cbgjClass::setMenu( CBTxt::T( 'Groups' ), $frontend_url, $plugin ) ) {
				cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group menu failed to create!' ), false, true, 'error' );
			}

			cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group menu created successfully!' ), false, true );
		} else {
			$row			=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );

			$frontend_url	=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=show&cat=' . $row->get( 'category' ) . '&grp=' . $row->get( 'id' );

			if ( ! cbgjClass::setMenu( $row->get( 'name' ), $frontend_url, $plugin ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'edit', (int) $row->get( 'id' ) ), CBTxt::T( 'Group menu failed to create!' ), false, true, 'error' );
			}

			cbgjClass::getPluginURL( array( 'groups', 'edit', (int) $row->get( 'id' ) ), CBTxt::T( 'Group menu created successfully!' ), false, true );
		}
	}

	/**
	 * save group
	 *
	 * @param int $id
	 * @param string $task
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function saveGroupEdit( $id, $task, $user, $plugin ) {
		$row			=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );
		$category		=	$row->getCategory();

		$row->set( 'published', (int) cbgjClass::getCleanParam( true, 'published', $row->get( 'published', ( $plugin->params->get( 'group_approve', 0 ) ? 0 : 1 ) ) ) );
		$row->set( '_previousCategory', $row->get( 'category', $category->get( 'id' ) ) );
		$row->set( 'category', (int) cbgjClass::getCleanParam( true, 'category', $row->get( 'category', $category->get( 'id' ) ) ) );
		$row->set( 'parent', (int) cbgjClass::getCleanParam( true, 'parent', $row->get( 'parent', 0 ) ) );
		$row->set( 'user_id', (int) cbgjClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) );
		$row->set( 'name', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'name', $row->get( 'name' ) ) ) );

		if ( $plugin->params->get( 'group_editor', 1 ) >= 2 ) {
			$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'description', $row->get( 'description' ) ) ) );
		} else {
			$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) ) ) );
		}

		$row->set( 'type', (int) cbgjClass::getCleanParam( true, 'type', $row->get( 'type', $plugin->params->get( 'group_type_default', 1 ) ) ) );
		$row->set( 'access', (int) cbgjClass::getCleanParam( true, 'access', $row->get( 'access', $plugin->params->get( 'group_access_default', -2 ) ) ) );
		$row->set( 'invite', (int) cbgjClass::getCleanParam( true, 'invite', $row->get( 'invite', $plugin->params->get( 'group_invite_default', 0 ) ) ) );
		$row->set( 'users', (int) cbgjClass::getCleanParam( true, 'users', $row->get( 'users', $plugin->params->get( 'group_users_default', 1 ) ) ) );
		$row->set( 'nested', (int) cbgjClass::getCleanParam( true, 'nested', $row->get( 'nested', $plugin->params->get( 'group_nested_default', 1 ) ) ) );
		$row->set( 'nested_access', (int) cbgjClass::getCleanParam( true, 'nested_access', $row->get( 'nested_access', $plugin->params->get( 'group_nestedaccess_default', -1 ) ) ) );
		$row->set( 'date', $row->get( 'date', cbgjClass::getUTCDate() ) );
		$row->set( 'ordering', (int) $row->get( 'ordering', 1 ) );

		if ( $row->get( 'name' ) == '' ) {
			$row->set( '_error', CBTxt::T( 'Name not specified!' ) );
		} elseif ( ! $row->get( 'user_id' ) ) {
			$row->set( '_error', CBTxt::T( 'Owner not specified!' ) );
		} elseif ( ! $row->get( 'category' ) ) {
			$row->set( '_error', CBTxt::T( 'Category not specified!' ) );
		} elseif ( ! $row->get( 'type' ) ) {
			$row->set( '_error', CBTxt::T( 'Type not specified!' ) );
		}

		if ( (int) cbgjClass::getCleanParam( true, 'del_logo', 0 ) ) {
			$row->unsetLogo();
		}

		if ( $row->getError() || ( ! $row->store() ) ) {
			$this->showGroupEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Group failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
		}

		$successMsg		=	CBTxt::T( 'Group saved successfully!' );

		$row->storeLogo( 'logo' );

		if ( $row->getError() ) {
			cbgjClass::displayMessage( $successMsg, 'message' );

			$this->showGroupEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Logo failed to upload! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
		}

		$row->storeOwner( $row->get( 'user_id' ) );

		if ( $task == 'apply' ) {
			cbgjClass::getPluginURL( array( 'groups', 'edit', (int) $row->get( 'id' ) ), $successMsg, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'groups' ), $successMsg, false, true );
		}
	}

	/**
	 * set group publish status
	 *
	 * @param array $ids
	 * @param int $state
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function stateGroup( $ids, $state, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->storeState( $state ) ) {
					cbgjClass::getPluginURL( array( 'groups' ), CBTxt::P( 'Group state failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group state saved successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group not found.' ), false, true, 'error' );
	}

	/**
	 * set group order
	 *
	 * @param array $ids
	 * @param int $order
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function orderGroup( $ids, $order, $user, $plugin ) {
		if ( is_array( $ids ) ) {
			for ( $i = 0; $i < count( $ids ); $i++ ) {
				$row	=	cbgjData::getGroups( null, array( 'id', '=', (int) $ids[$i] ), null, null, false );

				if ( ! $row->storeOrder( $order[$i] ) ) {
					cbgjClass::getPluginURL( array( 'groups' ), CBTxt::P( 'Group order failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}
		} else {
			$row		=	cbgjData::getGroups( null, array( 'id', '=', (int) $ids ), null, null, false );

			$row->move( $order );
		}

		cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group order saved successfully!' ), false, true );
	}

	/**
	 * delete group
	 *
	 * @param array $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteGroup( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->deleteAll() ) {
					cbgjClass::getPluginURL( array( 'groups' ), CBTxt::P( 'Group failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group deleted successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group not found.' ), false, true, 'error' );
	}

	/**
	 * batch process groups
	 *
	 * @param array $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function batchGroup( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			$category				=	cbGetParam( $_REQUEST, 'batch_category', null );
			$parent					=	cbGetParam( $_REQUEST, 'batch_parent', null );
			$access					=	cbGetParam( $_REQUEST, 'batch_access', null );
			$type					=	cbGetParam( $_REQUEST, 'batch_type', null );
			$owner					=	cbGetParam( $_REQUEST, 'batch_owner', null );

			if ( ( $category != '' ) || ( $parent != '' ) || ( $access != '' ) || ( $type != '' ) || ( $owner != '' ) ) {
				foreach ( $ids as $id ) {
					$row			=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );
					$process		=	false;

					if ( $category != '' ) {
						$row->set( '_previousCategory', $row->get( 'category' ) );
						$row->set( 'category', (int) $category );

						$process	=	true;
					}

					if ( ( $parent != '' ) && ( $parent != $row->get( 'id' ) ) ) {
						$row->set( 'parent', (int) $parent );

						$process	=	true;
					}

					if ( $access != '' ) {
						$row->set( 'access', (int) $access );

						$process	=	true;
					}

					if ( $type != '' ) {
						$row->set( 'type', (int) $type );

						$process	=	true;
					}

					if ( $process ) {
						if ( $row->getError() || ( ! $row->store() ) ) {
							cbgjClass::getPluginURL( array( 'groups' ), CBTxt::P( 'Group failed to process! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
						}
					}

					if ( $owner != '' ) {
						$row->storeOwner( (int) $owner );
					}
				}

				cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group batch process successfully!' ), false, true );
			}

			cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Nothing to process.' ), false, true, 'error' );
		}

		cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group not found.' ), false, true, 'error' );
	}

	/**
	 * copy group
	 *
	 * @param array $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function copyGroup( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjData::getGroups( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->storeCopy() ) {
					cbgjClass::getPluginURL( array( 'groups' ), CBTxt::P( 'Group failed to copy! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group copy successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'groups' ), CBTxt::T( 'Group not found.' ), false, true, 'error' );
	}

	/**
	 * prepare backend users render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showUsers( $user, $plugin ) {
		$paging								=	new cbgjPaging( 'users' );

		$limit								=	$paging->getlimit( 30 );
		$limitstart							=	$paging->getLimistart();
		$search								=	$paging->getFilter( 'search' );
		$category							=	$paging->getFilter( 'category' );
		$group								=	$paging->getFilter( 'group' );
		$status								=	$paging->getFilter( 'status' );
		$id									=	$paging->getFilter( 'id' );
		$where								=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]						=	array( 'd.id', '=', (int) $search, array( 'd.username', 'CONTAINS', $search ), array( 'd.name', 'CONTAINS', $search ) );
		}

		if ( isset( $category ) && ( $category != '' ) ) {
			$where[]						=	array( 'c.id', '=', (int) $category );
		}

		if ( isset( $group ) && ( $group != '' ) ) {
			$where[]						=	array( 'group', '=', (int) $group );
		}

		if ( isset( $status ) && ( $status != '' ) ) {
			$where[]						=	array( 'status', '=', (int) $status );
		}

		if ( isset( $id ) && ( $id != '' ) ) {
			$where[]						=	array( 'id', '=', (int) $id );
		}

		$searching							=	( count( $where ) ? true : false );

		$total								=	count( cbgjData::getUsers( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart						=	0;
		}

		$pageNav							=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows								=	array_values( cbgjData::getUsers( null, $where, null, array( $pageNav->limitstart, $pageNav->limit ) ) );

		$input								=	array();

		$categories							=	cbgjData::getCategories();
		$listCategories						=	cbgjData::listArray( $categories );

		if ( $listCategories ) {
			array_unshift( $listCategories, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Category -' ) ) );

			$input['category']				=	$paging->getInputSelect( 'adminForm', 'category', $listCategories, $category );

			$listGroups						=	array();

			foreach ( $categories as $cat ) {
				$groups						=	cbgjData::listArray( cbgjData::getGroups( null, array( 'category', '=', (int) $cat->get( 'id' ) ) ) );

				if ( $groups ) {
					$listGroups[]			=	moscomprofilerHTML::makeOptGroup( $cat->get( 'name' ) );

					foreach ( $groups as $grp ) {
						$listGroups[]		=	moscomprofilerHTML::makeOption( $grp->value, $grp->text );
					}
				}
			}

			array_unshift( $listGroups, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Group -' ) ) );

			$input['batch_group']			=	moscomprofilerHTML::selectList( $listGroups, 'batch_group', null, 'value', 'text', null, 1, false, false );

			if ( $category ) {
				$listGroups					=	cbgjData::listArray( cbgjData::getGroups( null, array( 'category', '=', (int) $category ) ) );

				array_unshift( $listGroups, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Group -' ) ) );
			}

			$input['group']					=	$paging->getInputSelect( 'adminForm', 'group', $listGroups, $group );
		} else {
			$input['category']				=	'-';
			$input['group']					=	'-';
			$input['batch_group']			=	'-';
		}

		$listStatus							=	array();
		$listStatus[]						=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Status -' ) );
		$listStatus[]						=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( 'Banned' ) );
		$listStatus[]						=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Inactive' ) );
		$listStatus[]						=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Active' ) );
		$listStatus[]						=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Moderator' ) );
		$listStatus[]						=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Admin' ) );
		$listStatus[]						=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Owner' ) );
		$input['status']					=	$paging->getInputSelect( 'adminForm', 'status', $listStatus, $status );
		$input['batch_status']				=	moscomprofilerHTML::selectList( $listStatus, 'batch_status', null, 'value', 'text', null, 1, false, false );

		$input['search']					=	$paging->getInputText( 'adminForm', 'search', $search, '30' );
		$input['id']						=	$paging->getInputText( 'adminForm', 'id', $id, '6' );

		$pageNav->searching					=	$searching;

		HTML_cbgjAdmin::showUsers( $rows, $pageNav, $input, $user, $plugin );
	}

	/**
	 * prepare backend user edit render
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param string $message
	 */
	private function showUserEdit( $id, $user, $plugin, $message = null ) {
		$row						=	cbgjData::getUsers( null, array( 'id', '=', (int) $id ), null, null, false );
		$group						=	$row->getGroup();
		$category					=	$group->getCategory();

		$input						=	array();


		if ( $row->get( 'id' ) ) {
			$userValue				=	(int) cbgjClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) );
		} else {
			$userValue				=	explode( '|*|', cbgjClass::getCleanParam( true, 'users', $user->id, null, 'REQUEST' ) );

			cbArrayToInts( $userValue );

			$userValue				=	implode( ',', $userValue );
		}

		$input['user']				=	'<input type="text" id="user_id" name="user_id" value="' . $userValue . '" class="inputbox required digits" size="25" />';

		$listGroups				=	array();

		$categories					=	cbgjData::getCategories();

		if ( $categories ) foreach ( $categories as $cat ) {
			$groups					=	cbgjData::listArray( cbgjData::getGroups( null, array( 'category', '=', (int) $cat->get( 'id' ) ) ) );

			if ( $groups ) {
				$listGroups[]		=	moscomprofilerHTML::makeOptGroup( $cat->get( 'name' ) );

				foreach ( $groups as $grp ) {
					$listGroups[]	=	moscomprofilerHTML::makeOption( $grp->value, $grp->text );
				}
			}
		}

		if ( ! empty( $listGroups ) ) {
			$input['group']			=	moscomprofilerHTML::selectList( $listGroups, 'group', 'class="inputbox required"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'group', $row->get( 'group' ) ), 1, false, false );
		} else {
			$input['group']			=	CBTxt::Th( 'There currently are no groups.' );
		}

		$listStatus					=	array();
		$listStatus[]				=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( 'Banned' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Inactive' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Active' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Moderator' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Admin' ) );
		$listStatus[]				=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Owner' ) );
		$input['status']			=	moscomprofilerHTML::selectList( $listStatus, 'status', 'class="inputbox required"', 'value', 'text', (int) cbgjClass::getCleanParam( true, 'status', $row->get( 'status', 1 ) ), 1, false, false );

		cbgjClass::displayMessage( $message );

		HTML_cbgjAdmin::showUserEdit( $row, $group, $category, $input, $user, $plugin );
	}

	/**
	 * save user
	 *
	 * @param int $id
	 * @param string $task
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function saveUserEdit( $id, $task, $user, $plugin ) {
		$row					=	cbgjData::getUsers( null, array( 'id', '=', (int) $id ), null, null, false );
		$group					=	$row->getGroup();
		$userIdArray			=	explode( ',', cbgjClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) );

		if ( ! empty( $userIdArray ) ) {
			foreach ( $userIdArray as $k => $userId ) {
				if ( $k != 0 ) {
					$row->set( 'id', null );
				}

				$row->set( 'user_id', (int) $userId );
				$row->set( 'group', (int) cbgjClass::getCleanParam( true, 'group', $row->get( 'group' ) ) );
				$row->set( 'date', $row->get( 'date', cbgjClass::getUTCDate() ) );
				$row->set( 'status', (int) cbgjClass::getCleanParam( true, 'status', $row->get( 'status', 1 ) ) );

				if ( ! $row->get( 'user_id' ) ) {
					$row->set( '_error', CBTxt::T( 'User not specified!' ) );
				} elseif ( ! $row->get( 'group' ) ) {
					$row->set( '_error', CBTxt::T( 'Group not specified!' ) );
				} elseif ( ! $row->get( 'id' ) ) {
					$exists		=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $row->get( 'user_id' ) ) ), null, null, false );

					if ( $exists->get( 'id' ) ) {
						$row->set( '_error', CBTxt::T( 'User already belongs to specified group!' ) );
					}
				}

				if ( $row->getError() || ( ! $row->store() ) ) {
					$this->showUserEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'User failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
				}

				if ( $row->get( 'status' ) == 4 ) {
					$group->storeOwner( $row->get( 'user_id' ) );
				}

				$row->acceptInvites();
			}

			if ( $task == 'apply' ) {
				cbgjClass::getPluginURL( array( 'users', 'edit', (int) $row->get( 'id' ) ), CBTxt::T( 'User saved successfully!' ), false, true );
			} else {
				cbgjClass::getPluginURL( array( 'users' ), CBTxt::T( 'User saved successfully!' ), false, true );
			}
		}

		$this->showUserEdit( $row->get( 'id' ), $user, $plugin, CBTxt::T( 'User failed to save! Error: User not specified!' ) ); return;
	}

	/**
	 * set user active status
	 *
	 * @param array  $ids
	 * @param int    $status
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function statusUser( $ids, $status, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row		=	cbgjData::getUsers( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->setStatus( $status ) ) {
					cbgjClass::getPluginURL( array( 'users' ), CBTxt::P( 'User status failed to save! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}

				if ( $status == 4 ) {
					$group	=	$row->getGroup();

					$group->storeOwner( $row->get( 'user_id' ) );
				}
			}

			cbgjClass::getPluginURL( array( 'users' ), CBTxt::T( 'User status saved successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'users' ), CBTxt::T( 'User not found.' ), false, true, 'error' );
	}

	/**
	 * delete user
	 *
	 * @param array  $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteUser( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjData::getUsers( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->deleteAll() ) {
					cbgjClass::getPluginURL( array( 'users' ), CBTxt::P( 'User failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'users' ), CBTxt::T( 'User deleted successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'users' ), CBTxt::T( 'User not found.' ), false, true, 'error' );
	}

	/**
	 * batch process users
	 *
	 * @param array  $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function batchUser( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			$group					=	cbGetParam( $_REQUEST, 'batch_group', null );
			$status					=	cbGetParam( $_REQUEST, 'batch_status', null );

			if ( ( $group != '' ) || ( $status != '' ) ) {
				foreach ( $ids as $id ) {
					$row			=	cbgjData::getUsers( null, array( 'id', '=', (int) $id ), null, null, false );
					$process		=	false;

					if ( $group != '' ) {
						$row->set( 'group', (int) $group );

						$process	=	true;
					}

					if ( $status != '' ) {
						$row->set( 'status', (int) $status );

						$process	=	true;
					}

					if ( $process ) {
						if ( $row->getError() || ( ! $row->store() ) ) {
							cbgjClass::getPluginURL( array( 'users' ), CBTxt::P( 'User failed to process! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
						}
					}

					if ( $status == 4 ) {
						$group		=	$row->getGroup();

						$group->storeOwner( $row->get( 'user_id' ) );
					}
				}

				cbgjClass::getPluginURL( array( 'users' ), CBTxt::T( 'User batch process successfully!' ), false, true );
			}

			cbgjClass::getPluginURL( array( 'users' ), CBTxt::T( 'Nothing to process.' ), false, true, 'error' );
		}

		cbgjClass::getPluginURL( array( 'users' ), CBTxt::T( 'User not found.' ), false, true, 'error' );
	}

	/**
	 * prepare backend invites render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showInvites( $user, $plugin ) {
		$paging								=	new cbgjPaging( 'invites' );

		$limit								=	$paging->getlimit( 30 );
		$limitstart							=	$paging->getLimistart();
		$search								=	$paging->getFilter( 'search' );
		$category							=	$paging->getFilter( 'category' );
		$group								=	$paging->getFilter( 'group' );
		$code								=	$paging->getFilter( 'code' );
		$id									=	$paging->getFilter( 'id' );
		$where								=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]						=	array( 'email', 'CONTAINS', $search, array( 'f.id', '=', (int) $search ), array( 'f.username', 'CONTAINS', $search ), array( 'f.name', 'CONTAINS', $search ) );
		}

		if ( isset( $category ) && ( $category != '' ) ) {
			$where[]						=	array( 'd.id', '=', (int) $category );
		}

		if ( isset( $group ) && ( $group != '' ) ) {
			$where[]						=	array( 'group', '=', (int) $group );
		}

		if ( isset( $code ) && ( $code != '' ) ) {
			$where[]						=	array( 'code', 'CONTAINS', $code );
		}

		if ( isset( $id ) && ( $id != '' ) ) {
			$where[]						=	array( 'id', '=', (int) $id );
		}

		$searching							=	( count( $where ) ? true : false );

		$total								=	count( cbgjData::getInvites( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart						=	0;
		}

		$pageNav							=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows								=	array_values( cbgjData::getInvites( null, $where, null, array( $pageNav->limitstart, $pageNav->limit ) ) );

		$input								=	array();

		$categories							=	cbgjData::getCategories();
		$listCategories						=	cbgjData::listArray( $categories );

		if ( $categories ) {
			array_unshift( $listCategories, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Category -' ) ) );

			$input['category']				=	$paging->getInputSelect( 'adminForm', 'category', $listCategories, $category );

			if ( $category ) {
				$listGroups					=	cbgjData::listArray( cbgjData::getGroups( null, array( 'category', '=', (int) $category ) ) );
			} else {
				$listGroups					=	array();

				foreach ( $categories as $cat ) {
					$groups					=	cbgjData::listArray( cbgjData::getGroups( null, array( 'category', '=', (int) $cat->get( 'id' ) ) ) );

					if ( $groups ) {
						$listGroups[]		=	moscomprofilerHTML::makeOptGroup( $cat->get( 'name' ) );

						foreach ( $groups as $grp ) {
							$listGroups[]	=	moscomprofilerHTML::makeOption( $grp->value, $grp->text );
						}
					}
				}
			}

			array_unshift( $listGroups, moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Group -' ) ) );

			$input['group']					=	$paging->getInputSelect( 'adminForm', 'group', $listGroups, $group );
		} else {
			$input['category']				=	'-';
			$input['group']					=	'-';
		}

		$input['search']					=	$paging->getInputText( 'adminForm', 'search', $search, '30' );
		$input['code']						=	$paging->getInputText( 'adminForm', 'code', $code, '30' );
		$input['id']						=	$paging->getInputText( 'adminForm', 'id', $id, '6' );

		$pageNav->searching					=	$searching;

		HTML_cbgjAdmin::showInvites( $rows, $pageNav, $input, $user, $plugin );
	}

	/**
	 * delete invite
	 *
	 * @param array  $ids
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteInvite( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbgjData::getInvites( null, array( 'id', '=', (int) $id ), null, null, false );

				if ( ! $row->delete() ) {
					cbgjClass::getPluginURL( array( 'invites' ), CBTxt::P( 'Invite failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbgjClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Invite deleted successfully!' ), false, true );
		}

		cbgjClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Invite not found.' ), false, true, 'error' );
	}

	/**
	 * prepare backend config render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param mixed $message
	 */
	private function showConfig( $user, $plugin, $message = null ) {
		global $_CB_framework;

		$logos											=	array();
		$templates										=	array();

		if ( is_dir( $plugin->absPath . '/images' ) ) {
			foreach ( scandir( $plugin->absPath . '/images' ) as $logo ) {
				if ( ! preg_match( '!^tn.+$!', $logo ) && preg_match( '!^[\w-]+[.](jpg|jpeg|png|gif|bmp)$!', $logo ) ) {
					$logos[]							=	moscomprofilerHTML::makeOption( $logo, $logo );
				}
			}
		}

		if ( is_dir( $plugin->absPath . '/templates' ) ) {
			foreach ( scandir( $plugin->absPath . '/templates' ) as $template ) {
				if ( preg_match( '!^\w+$!', $template ) ) {
					$templates[]						=	moscomprofilerHTML::makeOption( $template, $template );
				}
			}
		}

		$input											=	array();

		// General:
		$listNotifyBy									=	array();
		$listNotifyBy[]									=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'PMS or Email' ) );
		$listNotifyBy[]									=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'PMS & Email' ) );
		$listNotifyBy[]									=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'PMS Only' ) );
		$listNotifyBy[]									=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Email Only' ) );
		$listTooltips									=	array();
		$listTooltips[]									=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Icon' ) );
		$listTooltips[]									=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Text' ) );
		$listTooltips[]									=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Disabled' ) );
		$listAccess										=	array();
		$listAccess[]									=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( '- Everybody' ) );
		$listAccess[]									=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
		$listAccess										=	array_merge( $listAccess, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$listCreateAccess								=	array();
		$listCreateAccess[]								=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
		$listCreateAccess								=	array_merge( $listCreateAccess, $_CB_framework->acl->get_group_children_tree( null, 'USERS', false ) );
		$listTypes										=	array();
		$listTypes[]									=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Open' ) );
		$listTypes[]									=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Approval' ) );
		$listTypes[]									=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Invite' ) );
		$listEditor										=	array();
		$listEditor[]									=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Plain Text' ) );
		$listEditor[]									=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'HTML Text' ) );
		$listEditor[]									=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'WYSIWYG' ) );
		$listCatOrderby									=	array();
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Ordering ASC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Ordering DESC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Date ASC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Date DESC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '5', CBTxt::T( 'Name ASC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '6', CBTxt::T( 'Name DESC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '7', CBTxt::T( 'Group Count ASC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '8', CBTxt::T( 'Group Count DESC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '9', CBTxt::T( 'Nested Count ASC' ) );
		$listCatOrderby[]								=	moscomprofilerHTML::makeOption( '10', CBTxt::T( 'Nested Count DESC' ) );
		$listGrpOrderby									=	array();
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Ordering ASC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Ordering DESC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Date ASC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Date DESC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '5', CBTxt::T( 'Name ASC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '6', CBTxt::T( 'Name DESC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '7', CBTxt::T( 'User Count ASC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '8', CBTxt::T( 'User Count DESC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '9', CBTxt::T( 'Nested Count ASC' ) );
		$listGrpOrderby[]								=	moscomprofilerHTML::makeOption( '10', CBTxt::T( 'Nested Count DESC' ) );

		$input['general_title']							=	'<input type="text" id="general_title" name="general_title" value="' . htmlspecialchars( $plugin->params->get( 'general_title', $plugin->name ) ) . '" class="inputbox" size="25" />';
		$input['general_template']						=	moscomprofilerHTML::selectList( $templates, 'general_template', null, 'value', 'text', $plugin->params->get( 'general_template', 'default' ), 1, false, false );
		$input['general_class']							=	'<input type="text" id="general_class" name="general_class" value="' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . '" class="inputbox" size="10" />';
		$input['general_itemid']						=	'<input type="text" id="general_itemid" name="general_itemid" value="' . htmlspecialchars( $plugin->params->get( 'general_itemid', null ) ) . '" class="inputbox" size="5" />';
		$input['general_dynamicid']						=	moscomprofilerHTML::yesnoSelectList( 'general_dynamicid', null, $plugin->params->get( 'general_dynamicid', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['general_notifications']					=	moscomprofilerHTML::yesnoSelectList( 'general_notifications', null, $plugin->params->get( 'general_notifications', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['general_notifyby']						=	moscomprofilerHTML::selectList( $listNotifyBy, 'general_notifyby', null, 'value', 'text', $plugin->params->get( 'general_notifyby', 1 ), 1, false, false );
		$input['general_tooltips']						=	moscomprofilerHTML::selectList( $listTooltips, 'general_tooltips', null, 'value', 'text', $plugin->params->get( 'general_tooltips', 1 ), 1, false, false );
		$input['general_panes']							=	moscomprofilerHTML::yesnoSelectList( 'general_panes', null, $plugin->params->get( 'general_panes', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['general_validate']						=	moscomprofilerHTML::yesnoSelectList( 'general_validate', null, $plugin->params->get( 'general_validate', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['general_dirperms']						=	'<input type="text" id="general_dirperms" name="general_dirperms" value="' . (int) $plugin->params->get( 'general_dirperms', '755' ) . '" class="inputbox" size="10" />';
		$input['general_fileperms']						=	'<input type="text" id="general_fileperms" name="general_fileperms" value="' . (int) $plugin->params->get( 'general_fileperms', '644' ) . '" class="inputbox" size="10" />';
		$input['general_wordfilter']					=	'<textarea id="general_wordfilter" name="general_wordfilter" class="inputbox" cols="40" rows="5">' . htmlspecialchars( $plugin->params->get( 'general_wordfilter', null ) ) . '</textarea>';

		// Logos:
		$input['logo_size']								=	'<input type="text" id="logo_size" name="logo_size" value="' . htmlspecialchars( $plugin->params->get( 'logo_size', 2000 ) ) . '" class="inputbox" size="5" />';
		$input['logo_width']							=	'<input type="text" id="logo_width" name="logo_width" value="' . htmlspecialchars( $plugin->params->get( 'logo_width', 200 ) ) . '" class="inputbox" size="5" />';
		$input['logo_height']							=	'<input type="text" id="logo_height" name="logo_height" value="' . htmlspecialchars( $plugin->params->get( 'logo_height', 500 ) ) . '" class="inputbox" size="5" />';
		$input['logo_thumbwidth']						=	'<input type="text" id="logo_thumbwidth" name="logo_thumbwidth" value="' . htmlspecialchars( $plugin->params->get( 'logo_thumbwidth', 60 ) ) . '" class="inputbox" size="5" />';
		$input['logo_thumbheight']						=	'<input type="text" id="logo_thumbheight" name="logo_thumbheight" value="' . htmlspecialchars( $plugin->params->get( 'logo_thumbheight', 86 ) ) . '" class="inputbox" size="5" />';

		// Overrides:
		$input['override_category_s']					=	'<input type="text" id="override_category_s" name="override_category_s" value="' . htmlspecialchars( $plugin->params->get( 'override_category_s', 'Category' ) ) . '" class="inputbox" size="25" />';
		$input['override_category_p']					=	'<input type="text" id="override_category_p" name="override_category_p" value="' . htmlspecialchars( $plugin->params->get( 'override_category_p', 'Categories' ) ) . '" class="inputbox" size="25" />';
		$input['override_group_s']						=	'<input type="text" id="override_group_s" name="override_group_s" value="' . htmlspecialchars( $plugin->params->get( 'override_group_s', 'Group' ) ) . '" class="inputbox" size="25" />';
		$input['override_group_p']						=	'<input type="text" id="override_group_p" name="override_group_p" value="' . htmlspecialchars( $plugin->params->get( 'override_group_p', 'Groups' ) ) . '" class="inputbox" size="25" />';
		$input['override_user_s']						=	'<input type="text" id="override_user_s" name="override_user_s" value="' . htmlspecialchars( $plugin->params->get( 'override_user_s', 'User' ) ) . '" class="inputbox" size="25" />';
		$input['override_user_p']						=	'<input type="text" id="override_user_p" name="override_user_p" value="' . htmlspecialchars( $plugin->params->get( 'override_user_p', 'Users' ) ) . '" class="inputbox" size="25" />';
		$input['override_mod_s']						=	'<input type="text" id="override_mod_s" name="override_mod_s" value="' . htmlspecialchars( $plugin->params->get( 'override_mod_s', 'Moderator' ) ) . '" class="inputbox" size="25" />';
		$input['override_mod_p']						=	'<input type="text" id="override_mod_p" name="override_mod_p" value="' . htmlspecialchars( $plugin->params->get( 'override_mod_p', 'Moderators' ) ) . '" class="inputbox" size="25" />';
		$input['override_admin_s']						=	'<input type="text" id="override_admin_s" name="override_admin_s" value="' . htmlspecialchars( $plugin->params->get( 'override_admin_s', 'Admin' ) ) . '" class="inputbox" size="25" />';
		$input['override_admin_p']						=	'<input type="text" id="override_admin_p" name="override_admin_p" value="' . htmlspecialchars( $plugin->params->get( 'override_admin_p', 'Admins' ) ) . '" class="inputbox" size="25" />';
		$input['override_overview_s']					=	'<input type="text" id="override_overview_s" name="override_overview_s" value="' . htmlspecialchars( $plugin->params->get( 'override_overview_s', 'Overview' ) ) . '" class="inputbox" size="25" />';
		$input['override_owner_s']						=	'<input type="text" id="override_owner_s" name="override_owner_s" value="' . htmlspecialchars( $plugin->params->get( 'override_owner_s', 'Owner' ) ) . '" class="inputbox" size="25" />';
		$input['override_panel_s']						=	'<input type="text" id="override_panel_s" name="override_panel_s" value="' . htmlspecialchars( $plugin->params->get( 'override_panel_s', 'Panel' ) ) . '" class="inputbox" size="25" />';

		// Notifications:
		$input['notifications_from_name']				=	'<input type="text" id="notifications_from_name" name="notifications_from_name" value="' . htmlspecialchars( $plugin->params->get( 'notifications_from_name' ) ) . '" class="inputbox" size="30" />';
		$input['notifications_from_address']			=	'<input type="text" id="notifications_from_address" name="notifications_from_address" value="' . htmlspecialchars( $plugin->params->get( 'notifications_from_address' ) ) . '" class="inputbox" size="50" />';
		$input['notifications_desc']					=	$_CB_framework->displayCmsEditor( 'notifications_desc', $plugin->params->get( 'notifications_desc', null ), 400, 200, 40, 5 );
		$input['notifications_desc_content']			=	moscomprofilerHTML::yesnoSelectList( 'notifications_desc_content', null, $plugin->params->get( 'notifications_desc_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['notifications_desc_gen']				=	$_CB_framework->displayCmsEditor( 'notifications_desc_gen', $plugin->params->get( 'notifications_desc_gen', null ), 400, 200, 40, 5 );
		$input['notifications_desc_gen_content']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_desc_gen', null, $plugin->params->get( 'notifications_desc_gen', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['notifications_general_categorynew']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_general_categorynew', null, $plugin->params->get( 'notifications_general_categorynew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_general_categoryapprove']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_general_categoryapprove', null, $plugin->params->get( 'notifications_general_categoryapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_general_categoryupdate']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_general_categoryupdate', null, $plugin->params->get( 'notifications_general_categoryupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_general_categorydelete']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_general_categorydelete', null, $plugin->params->get( 'notifications_general_categorydelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_desc_cat']				=	$_CB_framework->displayCmsEditor( 'notifications_desc_cat', $plugin->params->get( 'notifications_desc_cat', null ), 400, 200, 40, 5 );
		$input['notifications_desc_cat_content']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_desc_cat_content', null, $plugin->params->get( 'notifications_desc_cat_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['notifications_category_nestednew']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_category_nestednew', null, $plugin->params->get( 'notifications_category_nestednew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_category_nestedapprove']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_category_nestedapprove', null, $plugin->params->get( 'notifications_category_nestedapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_category_nestedupdate']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_category_nestedupdate', null, $plugin->params->get( 'notifications_category_nestedupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_category_nesteddelete']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_category_nesteddelete', null, $plugin->params->get( 'notifications_category_nesteddelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_category_groupnew']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_category_groupnew', null, $plugin->params->get( 'notifications_category_groupnew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_category_groupapprove']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_category_groupapprove', null, $plugin->params->get( 'notifications_category_groupapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_category_groupupdate']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_category_groupupdate', null, $plugin->params->get( 'notifications_category_groupupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_category_groupdelete']	=	moscomprofilerHTML::yesnoSelectList( 'notifications_category_groupdelete', null, $plugin->params->get( 'notifications_category_groupdelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_desc_grp']				=	$_CB_framework->displayCmsEditor( 'notifications_desc_grp', $plugin->params->get( 'notifications_desc_grp', null ), 400, 200, 40, 5 );
		$input['notifications_desc_grp_content']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_desc_grp_content', null, $plugin->params->get( 'notifications_desc_grp_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['notifications_group_nestednew']			=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_nestednew', null, $plugin->params->get( 'notifications_group_nestednew', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_group_nestedapprove']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_nestedapprove', null, $plugin->params->get( 'notifications_group_nestedapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_group_nestedupdate']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_nestedupdate', null, $plugin->params->get( 'notifications_group_nestedupdate', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_group_nesteddelete']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_nesteddelete', null, $plugin->params->get( 'notifications_group_nesteddelete', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_group_userjoin']			=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_userjoin', null, $plugin->params->get( 'notifications_group_userjoin', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_group_userleave']			=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_userleave', null, $plugin->params->get( 'notifications_group_userleave', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_group_userinvite']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_userinvite', null, $plugin->params->get( 'notifications_group_userinvite', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_group_userapprove']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_userapprove', null, $plugin->params->get( 'notifications_group_userapprove', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
		$input['notifications_group_inviteaccept']		=	moscomprofilerHTML::yesnoSelectList( 'notifications_group_inviteaccept', null, $plugin->params->get( 'notifications_group_inviteaccept', 0 ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

		// Overview:
		$listOvrEditor									=	array();
		$listOvrEditor[]								=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Plain Text' ) );
		$listOvrEditor[]								=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'HTML Text' ) );
		$listOvrEditor[]								=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'WYSIWYG' ) );

		$input['overview_logo']							=	moscomprofilerHTML::selectList( $logos, 'overview_logo', null, 'value', 'text', $plugin->params->get( 'overview_logo', 'default_overview.png' ), 1, false, false );
		$input['overview_desc']							=	$_CB_framework->displayCmsEditor( 'overview_desc', $plugin->params->get( 'overview_desc', null ), 400, 200, 40, 5 );
		$input['overview_desc_content']					=	moscomprofilerHTML::yesnoSelectList( 'overview_desc_content', null, $plugin->params->get( 'overview_desc_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['overview_new_category']					=	moscomprofilerHTML::yesnoSelectList( 'overview_new_category', null, $plugin->params->get( 'overview_new_category', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['overview_new_group']					=	moscomprofilerHTML::yesnoSelectList( 'overview_new_group', null, $plugin->params->get( 'overview_new_group', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['overview_panel']						=	moscomprofilerHTML::yesnoSelectList( 'overview_panel', null, $plugin->params->get( 'overview_panel', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['overview_cat_desc_limit']				=	'<input type="text" id="overview_cat_desc_limit" name="overview_cat_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'overview_cat_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['overview_orderby']						=	moscomprofilerHTML::selectList( $listCatOrderby, 'overview_orderby', null, 'value', 'text', $plugin->params->get( 'overview_orderby', 1 ), 1, false, false );
		$input['overview_paging']						=	moscomprofilerHTML::yesnoSelectList( 'overview_paging', null, $plugin->params->get( 'overview_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['overview_limitbox']						=	moscomprofilerHTML::yesnoSelectList( 'overview_limitbox', null, $plugin->params->get( 'overview_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['overview_limit']						=	'<input type="text" id="overview_limit" name="overview_limit" value="' . (int) $plugin->params->get( 'overview_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['overview_search']						=	moscomprofilerHTML::yesnoSelectList( 'overview_search', null, $plugin->params->get( 'overview_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['overview_message_editor']				=	moscomprofilerHTML::selectList( $listEditor, 'overview_message_editor', null, 'value', 'text', $plugin->params->get( 'overview_message_editor', 1 ), 1, false, false );

		// Panels:
		$input['panel_logo']							=	moscomprofilerHTML::selectList( $logos, 'panel_logo', null, 'value', 'text', $plugin->params->get( 'panel_logo', 'default_panel.png' ), 1, false, false );
		$input['panel_desc']							=	$_CB_framework->displayCmsEditor( 'panel_desc', $plugin->params->get( 'panel_desc', null ), 400, 200, 40, 5 );
		$input['panel_desc_content']					=	moscomprofilerHTML::yesnoSelectList( 'panel_desc_content', null, $plugin->params->get( 'panel_desc_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['panel_new_category']					=	moscomprofilerHTML::yesnoSelectList( 'panel_new_category', null, $plugin->params->get( 'panel_new_category', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['panel_new_group']						=	moscomprofilerHTML::yesnoSelectList( 'panel_new_group', null, $plugin->params->get( 'panel_new_group', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['panel_category_display']				=	moscomprofilerHTML::yesnoSelectList( 'panel_category_display', null, $plugin->params->get( 'panel_category_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['panel_group_display']					=	moscomprofilerHTML::yesnoSelectList( 'panel_group_display', null, $plugin->params->get( 'panel_group_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['panel_joined_display']					=	moscomprofilerHTML::yesnoSelectList( 'panel_joined_display', null, $plugin->params->get( 'panel_joined_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['panel_invites_display']					=	moscomprofilerHTML::yesnoSelectList( 'panel_invites_display', null, $plugin->params->get( 'panel_invites_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['panel_invited_display']					=	moscomprofilerHTML::yesnoSelectList( 'panel_invited_display', null, $plugin->params->get( 'panel_invited_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		// Categories:
		$input['category_logo']							=	moscomprofilerHTML::selectList( $logos, 'category_logo', null, 'value', 'text', $plugin->params->get( 'category_logo', 'default_category.png' ), 1, false, false );
		$input['category_create']						=	moscomprofilerHTML::yesnoSelectList( 'category_create', null, $plugin->params->get( 'category_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_create_access']				=	moscomprofilerHTML::selectList( $listCreateAccess, 'category_create_access', null, 'value', 'text', $plugin->params->get( 'category_create_access', -1 ), 1, false, false );
		$input['category_nested']						=	moscomprofilerHTML::yesnoSelectList( 'category_nested', null, $plugin->params->get( 'category_nested', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_nested_access']				=	moscomprofilerHTML::selectList( $listCreateAccess, 'category_nested_access', null, 'value', 'text', $plugin->params->get( 'category_nested_access', -1 ), 1, false, false );
		$input['category_approve']						=	moscomprofilerHTML::yesnoSelectList( 'category_approve', null, $plugin->params->get( 'category_approve', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_limit']						=	'<input type="text" id="category_limit" name="category_limit" value="' . htmlspecialchars( $plugin->params->get( 'category_limit', 0 ) ) . '" class="inputbox" size="5" />';
		$input['category_message']						=	moscomprofilerHTML::yesnoSelectList( 'category_message', null, $plugin->params->get( 'category_message', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_editor']						=	moscomprofilerHTML::selectList( $listEditor, 'category_editor', null, 'value', 'text', $plugin->params->get( 'category_editor', 1 ), 1, false, false );
		$input['category_desc_content']					=	moscomprofilerHTML::yesnoSelectList( 'category_desc_content', null, $plugin->params->get( 'category_desc_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_desc_inputlimit']				=	'<input type="text" id="category_desc_inputlimit" name="category_desc_inputlimit" value="' . htmlspecialchars( $plugin->params->get( 'category_desc_inputlimit', 0 ) ) . '" class="inputbox" size="5" />';
		$input['category_captcha']						=	moscomprofilerHTML::yesnoSelectList( 'category_captcha', null, $plugin->params->get( 'category_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_new_category']					=	moscomprofilerHTML::yesnoSelectList( 'category_new_category', null, $plugin->params->get( 'category_new_category', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_new_group']					=	moscomprofilerHTML::yesnoSelectList( 'category_new_group', null, $plugin->params->get( 'category_new_group', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_hide_empty']					=	moscomprofilerHTML::yesnoSelectList( 'category_hide_empty', null, $plugin->params->get( 'category_hide_empty', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_message_editor']				=	moscomprofilerHTML::selectList( $listEditor, 'category_message_editor', null, 'value', 'text', $plugin->params->get( 'category_message_editor', 1 ), 1, false, false );
		$input['category_message_captcha']				=	moscomprofilerHTML::yesnoSelectList( 'category_message_captcha', null, $plugin->params->get( 'category_message_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_groups_desc_limit']			=	'<input type="text" id="category_groups_desc_limit" name="category_groups_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'category_groups_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['category_groups_orderby']				=	moscomprofilerHTML::selectList( $listGrpOrderby, 'category_groups_orderby', null, 'value', 'text', $plugin->params->get( 'category_groups_orderby', 1 ), 1, false, false );
		$input['category_groups_paging']				=	moscomprofilerHTML::yesnoSelectList( 'category_groups_paging', null, $plugin->params->get( 'category_groups_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_groups_limitbox']				=	moscomprofilerHTML::yesnoSelectList( 'category_groups_limitbox', null, $plugin->params->get( 'category_groups_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_groups_limit']					=	'<input type="text" id="category_groups_limit" name="category_groups_limit" value="' . (int) $plugin->params->get( 'category_groups_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['category_groups_search']				=	moscomprofilerHTML::yesnoSelectList( 'category_groups_search', null, $plugin->params->get( 'category_groups_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_nested_desc_limit']			=	'<input type="text" id="category_nested_desc_limit" name="category_nested_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'category_nested_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['category_nested_orderby']				=	moscomprofilerHTML::selectList( $listCatOrderby, 'category_nested_orderby', null, 'value', 'text', $plugin->params->get( 'category_nested_orderby', 1 ), 1, false, false );
		$input['category_nested_paging']				=	moscomprofilerHTML::yesnoSelectList( 'category_nested_paging', null, $plugin->params->get( 'category_nested_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_nested_limitbox']				=	moscomprofilerHTML::yesnoSelectList( 'category_nested_limitbox', null, $plugin->params->get( 'category_nested_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_nested_limit']					=	'<input type="text" id="category_nested_limit" name="category_nested_limit" value="' . (int) $plugin->params->get( 'category_nested_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['category_nested_search']				=	moscomprofilerHTML::yesnoSelectList( 'category_nested_search', null, $plugin->params->get( 'category_nested_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_all_desc_limit']				=	'<input type="text" id="category_all_desc_limit" name="category_all_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'category_all_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['category_all_orderby']					=	moscomprofilerHTML::selectList( $listCatOrderby, 'category_all_orderby', null, 'value', 'text', $plugin->params->get( 'category_all_orderby', 1 ), 1, false, false );
		$input['category_all_paging']					=	moscomprofilerHTML::yesnoSelectList( 'category_all_paging', null, $plugin->params->get( 'category_all_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_all_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'category_all_limitbox', null, $plugin->params->get( 'category_all_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_all_limit']					=	'<input type="text" id="category_all_limit" name="category_all_limit" value="' . (int) $plugin->params->get( 'category_all_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['category_all_search']					=	moscomprofilerHTML::yesnoSelectList( 'category_all_search', null, $plugin->params->get( 'category_all_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_approval_desc_limit']			=	'<input type="text" id="category_approval_desc_limit" name="category_approval_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'category_approval_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['category_approval_orderby']				=	moscomprofilerHTML::selectList( $listCatOrderby, 'category_approval_orderby', null, 'value', 'text', $plugin->params->get( 'category_approval_orderby', 1 ), 1, false, false );
		$input['category_approval_paging']				=	moscomprofilerHTML::yesnoSelectList( 'category_approval_paging', null, $plugin->params->get( 'category_approval_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_approval_limitbox']			=	moscomprofilerHTML::yesnoSelectList( 'category_approval_limitbox', null, $plugin->params->get( 'category_approval_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_approval_limit']				=	'<input type="text" id="category_approval_limit" name="category_approval_limit" value="' . (int) $plugin->params->get( 'category_approval_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['category_approval_search']				=	moscomprofilerHTML::yesnoSelectList( 'category_approval_search', null, $plugin->params->get( 'category_approval_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		// Category Defaults:
		$input['category_types_default']				=	moscomprofilerHTML::selectList( $listTypes, 'category_types_default[]', 'size="4" multiple="multiple"', 'value', 'text', explode( '|*|', $plugin->params->get( 'category_types_default', '1|*|2|*|3' ) ), 0, false, true );
		$input['category_types_config']					=	moscomprofilerHTML::yesnoSelectList( 'category_types_config', null, $plugin->params->get( 'category_types_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['category_access_default']				=	moscomprofilerHTML::selectList( $listAccess, 'category_access_default', null, 'value', 'text', $plugin->params->get( 'category_access_default', -2 ), 1, false, false );
		$input['category_access_config']				=	moscomprofilerHTML::yesnoSelectList( 'category_access_config', null, $plugin->params->get( 'category_access_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['category_create_default']				=	moscomprofilerHTML::yesnoSelectList( 'category_create_default', null, $plugin->params->get( 'category_create_default', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_create_config']				=	moscomprofilerHTML::yesnoSelectList( 'category_create_config', null, $plugin->params->get( 'category_create_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['category_createaccess_default']			=	moscomprofilerHTML::selectList( $listCreateAccess, 'category_createaccess_default', null, 'value', 'text', $plugin->params->get( 'category_createaccess_default', -1 ), 1, false, false );
		$input['category_createaccess_config']			=	moscomprofilerHTML::yesnoSelectList( 'category_createaccess_config', null, $plugin->params->get( 'category_createaccess_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['category_nested_default']				=	moscomprofilerHTML::yesnoSelectList( 'category_nested_default', null, $plugin->params->get( 'category_nested_default', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_nested_config']				=	moscomprofilerHTML::yesnoSelectList( 'category_nested_config', null, $plugin->params->get( 'category_nested_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['category_nestedaccess_default']			=	moscomprofilerHTML::selectList( $listCreateAccess, 'category_nestedaccess_default', null, 'value', 'text', $plugin->params->get( 'category_nestedaccess_default', -1 ), 1, false, false );
		$input['category_nestedaccess_config']			=	moscomprofilerHTML::yesnoSelectList( 'category_nestedaccess_config', null, $plugin->params->get( 'category_nestedaccess_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		// Groups:
		$listGrpToggle									=	array();
		$listGrpToggle[]								=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Disabled' ) );
		$listGrpToggle[]								=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Expanded' ) );
		$listGrpToggle[]								=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Collapsed' ) );
		$listInviteBy									=	array();
		$listInviteBy[]									=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'User ID' ) );
		$listInviteBy[]									=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Username' ) );
		$listInviteBy[]									=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Name' ) );
		$listInviteBy[]									=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Email' ) );
		$listGrpAccess									=	array();
		$listGrpAccess[]								=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Group' ) . '&nbsp;' . CBTxt::T( 'Users' ) );
		$listGrpAccess[]								=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . CBTxt::T( 'Group' ) . '&nbsp;' . CBTxt::T( 'Moderators' ) );
		$listGrpAccess[]								=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . CBTxt::T( 'Group' ) . '&nbsp;' . CBTxt::T( 'Admins' ) );
		$listGrpAccess[]								=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . CBTxt::T( 'Group' ) . '&nbsp;' . CBTxt::T( 'Owner' ) );

		$input['group_logo']							=	moscomprofilerHTML::selectList( $logos, 'group_logo', null, 'value', 'text', $plugin->params->get( 'group_logo', 'default.png' ), 1, false, false );
		$input['group_create']							=	moscomprofilerHTML::yesnoSelectList( 'group_create', null, $plugin->params->get( 'group_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_create_access']					=	moscomprofilerHTML::selectList( $listCreateAccess, 'group_create_access', null, 'value', 'text', $plugin->params->get( 'group_create_access', -1 ), 1, false, false );
		$input['group_nested']							=	moscomprofilerHTML::yesnoSelectList( 'group_nested', null, $plugin->params->get( 'group_nested', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_nested_access']					=	moscomprofilerHTML::selectList( $listCreateAccess, 'group_nested_access', null, 'value', 'text', $plugin->params->get( 'group_nested_access', -1 ), 1, false, false );
		$input['group_approve']							=	moscomprofilerHTML::yesnoSelectList( 'group_approve', null, $plugin->params->get( 'group_approve', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_leave']							=	moscomprofilerHTML::yesnoSelectList( 'group_leave', null, $plugin->params->get( 'group_leave', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_limit']							=	'<input type="text" id="group_limit" name="group_limit" value="' . htmlspecialchars( $plugin->params->get( 'group_limit', 0 ) ) . '" class="inputbox" size="5" />';
		$input['group_message']							=	moscomprofilerHTML::yesnoSelectList( 'group_message', null, $plugin->params->get( 'group_message', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_editor']							=	moscomprofilerHTML::selectList( $listEditor, 'group_editor', null, 'value', 'text', $plugin->params->get( 'group_editor', 1 ), 1, false, false );
		$input['group_desc_content']					=	moscomprofilerHTML::yesnoSelectList( 'group_desc_content', null, $plugin->params->get( 'group_desc_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_desc_inputlimit']					=	'<input type="text" id="group_desc_inputlimit" name="group_desc_inputlimit" value="' . htmlspecialchars( $plugin->params->get( 'group_desc_inputlimit', 0 ) ) . '" class="inputbox" size="5" />';
		$input['group_toggle']							=	moscomprofilerHTML::selectList( $listGrpToggle, 'group_toggle', null, 'value', 'text', $plugin->params->get( 'group_toggle', 3 ), 1, false, false );
		$input['group_captcha']							=	moscomprofilerHTML::yesnoSelectList( 'group_captcha', null, $plugin->params->get( 'group_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_new_group']						=	moscomprofilerHTML::yesnoSelectList( 'group_new_group', null, $plugin->params->get( 'group_new_group', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_message_editor']					=	moscomprofilerHTML::selectList( $listEditor, 'group_message_editor', null, 'value', 'text', $plugin->params->get( 'group_message_editor', 1 ), 1, false, false );
		$input['group_message_captcha']					=	moscomprofilerHTML::yesnoSelectList( 'group_message_captcha', null, $plugin->params->get( 'group_message_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_message_perm']					=	moscomprofilerHTML::selectList( $listGrpAccess, 'group_message_perm', 'class="inputbox"', 'value', 'text', $plugin->params->get( 'group_message_perm', 3 ), 1, false, false );
		$input['group_nested_desc_limit']				=	'<input type="text" id="group_nested_desc_limit" name="group_nested_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'group_nested_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['group_nested_orderby']					=	moscomprofilerHTML::selectList( $listGrpOrderby, 'group_nested_orderby', null, 'value', 'text', $plugin->params->get( 'group_nested_orderby', 1 ), 1, false, false );
		$input['group_nested_paging']					=	moscomprofilerHTML::yesnoSelectList( 'group_nested_paging', null, $plugin->params->get( 'group_nested_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_nested_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'group_nested_limitbox', null, $plugin->params->get( 'group_nested_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_nested_limit']					=	'<input type="text" id="group_nested_limit" name="group_nested_limit" value="' . (int) $plugin->params->get( 'group_nested_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['group_nested_search']					=	moscomprofilerHTML::yesnoSelectList( 'group_nested_search', null, $plugin->params->get( 'group_nested_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_all_desc_limit']					=	'<input type="text" id="group_all_desc_limit" name="group_all_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'group_all_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['group_all_orderby']						=	moscomprofilerHTML::selectList( $listGrpOrderby, 'group_all_orderby', null, 'value', 'text', $plugin->params->get( 'group_all_orderby', 1 ), 1, false, false );
		$input['group_all_paging']						=	moscomprofilerHTML::yesnoSelectList( 'group_all_paging', null, $plugin->params->get( 'group_all_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_all_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'group_all_limitbox', null, $plugin->params->get( 'group_all_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_all_limit']						=	'<input type="text" id="group_all_limit" name="group_all_limit" value="' . (int) $plugin->params->get( 'group_all_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['group_all_search']						=	moscomprofilerHTML::yesnoSelectList( 'group_all_search', null, $plugin->params->get( 'group_all_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_approval_desc_limit']				=	'<input type="text" id="group_approval_desc_limit" name="group_approval_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'group_approval_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['group_approval_orderby']				=	moscomprofilerHTML::selectList( $listGrpOrderby, 'group_approval_orderby', null, 'value', 'text', $plugin->params->get( 'group_approval_orderby', 1 ), 1, false, false );
		$input['group_approval_paging']					=	moscomprofilerHTML::yesnoSelectList( 'group_approval_paging', null, $plugin->params->get( 'group_approval_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_approval_limitbox']				=	moscomprofilerHTML::yesnoSelectList( 'group_approval_limitbox', null, $plugin->params->get( 'group_approval_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_approval_limit']					=	'<input type="text" id="group_approval_limit" name="group_approval_limit" value="' . (int) $plugin->params->get( 'group_approval_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['group_approval_search']					=	moscomprofilerHTML::yesnoSelectList( 'group_approval_search', null, $plugin->params->get( 'group_approval_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_users_paging']					=	moscomprofilerHTML::yesnoSelectList( 'group_users_paging', null, $plugin->params->get( 'group_users_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_users_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'group_users_limitbox', null, $plugin->params->get( 'group_users_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_users_limit']						=	'<input type="text" id="group_users_limit" name="group_users_limit" value="' . (int) $plugin->params->get( 'group_users_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['group_users_search']					=	moscomprofilerHTML::yesnoSelectList( 'group_users_search', null, $plugin->params->get( 'group_users_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_users_approval_paging']			=	moscomprofilerHTML::yesnoSelectList( 'group_users_approval_paging', null, $plugin->params->get( 'group_users_approval_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_users_approval_limitbox']			=	moscomprofilerHTML::yesnoSelectList( 'group_users_approval_limitbox', null, $plugin->params->get( 'group_users_approval_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_users_approval_limit']			=	'<input type="text" id="group_users_approval_limit" name="group_users_approval_limit" value="' . (int) $plugin->params->get( 'group_users_approval_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['group_users_approval_search']			=	moscomprofilerHTML::yesnoSelectList( 'group_users_approval_search', null, $plugin->params->get( 'group_users_approval_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		// Group Defaults:
		$input['group_type_default']					=	moscomprofilerHTML::selectList( $listTypes, 'group_type_default', null, 'value', 'text', $plugin->params->get( 'group_type_default', 1 ), 1, false, false );
		$input['group_type_config']						=	moscomprofilerHTML::yesnoSelectList( 'group_type_config', null, $plugin->params->get( 'group_type_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['group_access_default']					=	moscomprofilerHTML::selectList( $listAccess, 'group_access_default', null, 'value', 'text', $plugin->params->get( 'group_access_default', -2 ), 1, false, false );
		$input['group_access_config']					=	moscomprofilerHTML::yesnoSelectList( 'group_access_config', null, $plugin->params->get( 'group_access_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['group_invite_default']					=	moscomprofilerHTML::selectList( $listGrpAccess, 'group_invite_default', null, 'value', 'text', $plugin->params->get( 'group_invite_default', 0 ), 1, false, false );
		$input['group_invite_config']					=	moscomprofilerHTML::yesnoSelectList( 'group_invite_config', null, $plugin->params->get( 'group_invite_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['group_users_default']					=	moscomprofilerHTML::yesnoSelectList( 'group_users_default', null, $plugin->params->get( 'group_users_default', 1 ), CBTxt::T( 'Yes' ), CBTxt::T( 'No' ) );
		$input['group_users_config']					=	moscomprofilerHTML::yesnoSelectList( 'group_users_config', null, $plugin->params->get( 'group_users_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['group_nested_default']					=	moscomprofilerHTML::yesnoSelectList( 'group_nested_default', null, $plugin->params->get( 'group_nested_default', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_nested_config']					=	moscomprofilerHTML::yesnoSelectList( 'group_nested_config', null, $plugin->params->get( 'group_nested_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['group_nestedaccess_default']			=	moscomprofilerHTML::selectList( $listGrpAccess, 'group_nestedaccess_default', null, 'value', 'text', $plugin->params->get( 'group_nestedaccess_default', -1 ), 1, false, false );
		$input['group_nestedaccess_config']				=	moscomprofilerHTML::yesnoSelectList( 'group_nestedaccess_config', null, $plugin->params->get( 'group_nestedaccess_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		// Invites:
		$input['group_invites_display']					=	moscomprofilerHTML::yesnoSelectList( 'group_invites_display', null, $plugin->params->get( 'group_invites_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_invites_by']						=	moscomprofilerHTML::selectList( $listInviteBy, 'group_invites_by[]', 'size="4" multiple="multiple"', 'value', 'text', explode( '|*|', $plugin->params->get( 'group_invites_by', '1|*|2|*|3|*|4' ) ), 1, false, false );
		$input['group_invites_captcha']					=	moscomprofilerHTML::yesnoSelectList( 'group_invites_captcha', null, $plugin->params->get( 'group_invites_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_invites_list']					=	moscomprofilerHTML::yesnoSelectList( 'group_invites_list', null, $plugin->params->get( 'group_invites_list', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_invites_accept']					=	moscomprofilerHTML::yesnoSelectList( 'group_invites_accept', null, $plugin->params->get( 'group_invites_accept', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_invites_paging']					=	moscomprofilerHTML::yesnoSelectList( 'group_invites_paging', null, $plugin->params->get( 'group_invites_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_invites_limitbox']				=	moscomprofilerHTML::yesnoSelectList( 'group_invites_limitbox', null, $plugin->params->get( 'group_invites_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_invites_limit']					=	'<input type="text" id="group_invites_limit" name="group_invites_limit" value="' . (int) $plugin->params->get( 'group_invites_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['group_invites_search']					=	moscomprofilerHTML::yesnoSelectList( 'group_invites_search', null, $plugin->params->get( 'group_invites_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		// Tabs:
		$input['tab_new_category']						=	moscomprofilerHTML::yesnoSelectList( 'tab_new_category', null, $plugin->params->get( 'tab_new_category', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['tab_new_group']							=	moscomprofilerHTML::yesnoSelectList( 'tab_new_group', null, $plugin->params->get( 'tab_new_group', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$input['category_tab_display']					=	moscomprofilerHTML::yesnoSelectList( 'category_tab_display', null, $plugin->params->get( 'category_tab_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_tab_desc_limit']				=	'<input type="text" id="category_tab_desc_limit" name="category_tab_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'category_tab_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['category_tab_orderby']					=	moscomprofilerHTML::selectList( $listCatOrderby, 'category_tab_orderby', null, 'value', 'text', $plugin->params->get( 'category_tab_orderby', 1 ), 1, false, false );
		$input['category_tab_paging']					=	moscomprofilerHTML::yesnoSelectList( 'category_tab_paging', null, $plugin->params->get( 'category_tab_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_tab_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'category_tab_limitbox', null, $plugin->params->get( 'category_tab_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['category_tab_limit']					=	'<input type="text" id="category_tab_limit" name="category_tab_limit" value="' . (int) $plugin->params->get( 'category_tab_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['category_tab_search']					=	moscomprofilerHTML::yesnoSelectList( 'category_tab_search', null, $plugin->params->get( 'category_tab_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$input['group_tab_display']						=	moscomprofilerHTML::yesnoSelectList( 'group_tab_display', null, $plugin->params->get( 'group_tab_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_tab_desc_limit']					=	'<input type="text" id="group_tab_desc_limit" name="group_tab_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'group_tab_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['group_tab_orderby']						=	moscomprofilerHTML::selectList( $listGrpOrderby, 'group_tab_orderby', null, 'value', 'text', $plugin->params->get( 'group_tab_orderby', 1 ), 1, false, false );
		$input['group_tab_paging']						=	moscomprofilerHTML::yesnoSelectList( 'group_tab_paging', null, $plugin->params->get( 'group_tab_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_tab_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'group_tab_limitbox', null, $plugin->params->get( 'group_tab_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_tab_limit']						=	'<input type="text" id="group_tab_limit" name="group_tab_limit" value="' . (int) $plugin->params->get( 'group_tab_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['group_tab_search']						=	moscomprofilerHTML::yesnoSelectList( 'group_tab_search', null, $plugin->params->get( 'group_tab_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['group_tab_joined']						=	moscomprofilerHTML::yesnoSelectList( 'group_tab_joined', null, $plugin->params->get( 'group_tab_joined', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$input['joined_tab_display']					=	moscomprofilerHTML::yesnoSelectList( 'joined_tab_display', null, $plugin->params->get( 'joined_tab_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['joined_tab_desc_limit']					=	'<input type="text" id="joined_tab_desc_limit" name="joined_tab_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'joined_tab_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['joined_tab_orderby']					=	moscomprofilerHTML::selectList( $listGrpOrderby, 'joined_tab_orderby', null, 'value', 'text', $plugin->params->get( 'joined_tab_orderby', 1 ), 1, false, false );
		$input['joined_tab_paging']						=	moscomprofilerHTML::yesnoSelectList( 'joined_tab_paging', null, $plugin->params->get( 'joined_tab_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['joined_tab_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'joined_tab_limitbox', null, $plugin->params->get( 'joined_tab_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['joined_tab_limit']						=	'<input type="text" id="joined_tab_limit" name="joined_tab_limit" value="' . (int) $plugin->params->get( 'joined_tab_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['joined_tab_search']						=	moscomprofilerHTML::yesnoSelectList( 'joined_tab_search', null, $plugin->params->get( 'joined_tab_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['joined_tab_owned']						=	moscomprofilerHTML::yesnoSelectList( 'joined_tab_owned', null, $plugin->params->get( 'joined_tab_owned', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$input['invites_tab_display']					=	moscomprofilerHTML::yesnoSelectList( 'invites_tab_display', null, $plugin->params->get( 'invites_tab_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['invites_tab_paging']					=	moscomprofilerHTML::yesnoSelectList( 'invites_tab_paging', null, $plugin->params->get( 'invites_tab_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['invites_tab_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'invites_tab_limitbox', null, $plugin->params->get( 'invites_tab_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['invites_tab_limit']						=	'<input type="text" id="invites_tab_limit" name="invites_tab_limit" value="' . (int) $plugin->params->get( 'invites_tab_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['invites_tab_search']					=	moscomprofilerHTML::yesnoSelectList( 'invites_tab_search', null, $plugin->params->get( 'invites_tab_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$input['invited_tab_display']					=	moscomprofilerHTML::yesnoSelectList( 'invited_tab_display', null, $plugin->params->get( 'invited_tab_display', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['invited_tab_desc_limit']				=	'<input type="text" id="invited_tab_desc_limit" name="invited_tab_desc_limit" value="' . htmlspecialchars( $plugin->params->get( 'invited_tab_desc_limit', 150 ) ) . '" class="inputbox" size="5" />';
		$input['invited_tab_orderby']					=	moscomprofilerHTML::selectList( $listGrpOrderby, 'invited_tab_orderby', null, 'value', 'text', $plugin->params->get( 'invited_tab_orderby', 1 ), 1, false, false );
		$input['invited_tab_paging']					=	moscomprofilerHTML::yesnoSelectList( 'invited_tab_paging', null, $plugin->params->get( 'invited_tab_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['invited_tab_limitbox']					=	moscomprofilerHTML::yesnoSelectList( 'invited_tab_limitbox', null, $plugin->params->get( 'invited_tab_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['invited_tab_limit']						=	'<input type="text" id="invited_tab_limit" name="invited_tab_limit" value="' . (int) $plugin->params->get( 'invited_tab_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['invited_tab_search']					=	moscomprofilerHTML::yesnoSelectList( 'invited_tab_search', null, $plugin->params->get( 'invited_tab_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		cbgjClass::displayMessage( $message );

		HTML_cbgjAdmin::showConfig( $input, $user, $plugin );
	}

	/**
	 * save config
	 *
	 * @param array  $config
	 */
	private function saveConfig( $config, $user, $plugin ) {
		global $_CB_database;

		$row			=	new moscomprofilerPlugin( $_CB_database );

		if ( $plugin->id ) {
			$row->load( $plugin->id );
		}

		$params			=	cbgjClass::parseParams( $config, true );

		$row->params	=	trim( $params->toIniString() );

		if ( $row->getError() || ( ! $row->store() ) ) {
			$this->showConfig( $user, $plugin, CBTxt::P( 'Config failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
		}

		cbgjClass::getPluginURL( array( 'config' ), CBTxt::T( 'Config saved successfully!' ), false, true );
	}

	/**
	 * prepare backend tools render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showTools( $user, $plugin ) {
		global $_CB_database, $_PLUGINS;

		$msgs								=	new stdClass();
		$msgs->errors						=	array();
		$msgs->warnings						=	array();
		$msgs->info							=	array();

		$_PLUGINS->trigger( 'gj_onBeforeTools', array( $msgs, $user, $plugin ) );

		if ( file_exists( $plugin->imgsAbs ) ) {
			if ( ! is_writable( $plugin->imgsAbs ) ) {
				$msgs->errors[]				=	CBTxt::P( ':: Images :: directory not writable - [img_path]', array( '[img_path]' => $plugin->imgsAbs ) );
			}
		}

		if ( file_exists( str_replace( '/' . $plugin->folder, '', $plugin->imgsAbs ) ) ) {
			if ( ! is_writable( str_replace( '/' . $plugin->folder, '', $plugin->imgsAbs ) ) ) {
				$msgs->errors[]				=	CBTxt::P( ':: Images :: directory not writable - [img_path]', array( '[img_path]' => str_replace( '/' . $plugin->folder, '', $plugin->imgsAbs ) ) );
			}
		}

		$categories							=	cbgjData::getCategories();

		$cat_types							=	array();
		$cat_published						=	array();
		$cat_name							=	array();
		$cat_user							=	array();
		$cat_notification					=	array();

		if ( $categories ) foreach ( $categories as $category ) {
			$category_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'categories', 'edit', (int) $category->get( 'id' ) ) ) . '">' . $category->get( 'id' ) . '</a>';

			if ( ! $category->get( 'types' ) ) {
				$cat_types[]				=	$category_url;
			}

			if ( ! $category->get( 'published' ) ) {
				$cat_published[]			=	$category_url;
			}

			if ( $category->get( 'name' ) == '' ) {
				$cat_name[]					=	$category_url;
			}

			if ( ! $category->get( 'user_id' ) ) {
				$cat_user[]					=	$category_url;
			} else {
				$notification				=	cbgjData::getNotifications( null, array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $category->get( 'id' ) ), array( 'user_id', '=', (int) $category->get( 'user_id' ) ) ), null, null, false );

				if ( ( ! $notification->get( 'id' ) ) && cbgjClass::hasAccess( 'cat_usr_notifications', cbgjClass::getAuthorization( $category, null, $category->getOwner() ) ) ) {
					$cat_notification[]		=	$category_url;
				}
			}
		}

		if ( ! empty( $cat_types ) ) {
			$fix_url						=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'categories', 'types' ) ) . '">' . CBTxt::T( 'Fix All' ) . '</a>';
			$msgs->errors[]					=	CBTxt::P( ':: Category :: Missing types - [cat] - [fixall]', array( '[cat]' => implode( ', ', $cat_types ), '[fixall]' => $fix_url ) );
		}

		if ( ! empty( $cat_published ) ) {
			$fix_url						=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'categories', 'published' ) ) . '">' . CBTxt::T( 'Fix All' ) . '</a>';
			$msgs->info[]					=	CBTxt::P( ':: Category :: Not published - [cat] - [fixall]', array( '[cat]' => implode( ', ', $cat_published ), '[fixall]' => $fix_url ) );
		}

		if ( ! empty( $cat_name ) ) {
			$msgs->errors[]					=	CBTxt::P( ':: Category :: Missing name - [cat]', array( '[cat]' => implode( ', ', $cat_name ) ) );
		}

		if ( ! empty( $cat_user ) ) {
			$fix_url						=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'categories', 'user_id' ) ) . '">' . CBTxt::T( 'Fix All' ) . '</a>';
			$msgs->errors[]					=	CBTxt::P( ':: Category :: Missing owner - [cat] - [fixall]', array( '[cat]' => implode( ', ', $cat_user ), '[fixall]' => $fix_url ) );
		}

		if ( ! empty( $cat_notification ) ) {
			$fix_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'categories', 'notifications' ) ) . '">' . CBTxt::T( 'Fix All' ) . '</a>';
			$msgs->errors[]				=	CBTxt::Ph( ':: Category :: Missing owner notifications - [cat] - [fixall]', array( '[cat]' => implode( ', ', $cat_notification ), '[fixall]' => $fix_url ) );
		}

		$groups								=	cbgjData::getGroups();

		$grp_cat_id							=	array();
		$grp_published						=	array();
		$grp_type							=	array();
		$grp_cat							=	array();
		$grp_name							=	array();
		$grp_user							=	array();
		$grp_owner							=	array();
		$grp_notification					=	array();

		if ( $groups ) foreach ( $groups as $group ) {
			$group_url						=	'<a href="' . cbgjClass::getPluginURL( array( 'groups', 'edit', (int) $group->get( 'id' ) ) ) . '">' . $group->get( 'id' ) . '</a>';

			if ( ! $group->get( 'published' ) ) {
				$grp_published[]			=	$group_url;
			}

			if ( ! $group->get( 'type' ) ) {
				$grp_type[]					=	$group_url;
			}

			if ( ! $group->get( 'category' ) ) {
				$grp_cat[]					=	$group_url;
			} else {
				if ( ! $group->getCategory()->get( 'id' ) ) {
					$grp_cat_id[]			=	$group_url;
				}
			}

			if ( $group->get( 'name' ) == '' ) {
				$grp_name[]					=	$group_url;
			}

			if ( ! $group->get( 'user_id' ) ) {
				$grp_user[]					=	$group_url;
			} else {
				if ( ! $group->getUser()->get( 'id' ) ) {
					$grp_owner[]			=	$group_url;
				} else {
					$notification			=	cbgjData::getNotifications( null, array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $group->get( 'user_id' ) ) ), null, null, false );

					if ( ( ! $notification->get( 'id' ) ) && cbgjClass::hasAccess( 'grp_usr_notifications', cbgjClass::getAuthorization( $group->getCategory(), $group, $group->getOwner() ) ) ) {
						$grp_notification[]	=	$group_url;
					}
				}
			}
		}

		if ( ! empty( $grp_cat_id ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: Group :: Category does not exist in GroupJive - [grp]', array( '[grp]' => implode( ', ', $grp_cat_id ) ) );
		}

		if ( ! empty( $grp_published ) ) {
			$fix_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'groups', 'published' ) ) . '">' . CBTxt::Th( 'Fix All' ) . '</a>';
			$msgs->info[]				=	CBTxt::Ph( ':: Group :: Not published - [grp] - [fixall]', array( '[grp]' => implode( ', ', $grp_published ), '[fixall]' => $fix_url ) );
		}

		if ( ! empty( $grp_type ) ) {
			$fix_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'groups', 'type' ) ) . '">' . CBTxt::Th( 'Fix All' ) . '</a>';
			$msgs->errors[]				=	CBTxt::Ph( ':: Group :: Missing type - [grp] - [fixall]', array( '[grp]' => implode( ', ', $grp_type ), '[fixall]' => $fix_url ) );
		}

		if ( ! empty( $grp_cat ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: Group :: Missing category - [grp]', array( '[grp]' => implode( ', ', $grp_cat ) ) );
		}

		if ( ! empty( $grp_name ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: Group :: Missing name - [grp]', array( '[grp]' => implode( ', ', $grp_name ) ) );
		}

		if ( ! empty( $grp_user ) ) {
			$fix_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'groups', 'gj_user' ) ) . '">' . CBTxt::Th( 'Fix All' ) . '</a>';
			$msgs->errors[]				=	CBTxt::Ph( ':: Group :: Missing owner - [grp] - [fixall]', array( '[grp]' => implode( ', ', $grp_user ), '[fixall]' => $fix_url ) );
		}

		if ( ! empty( $grp_owner ) ) {
			$fix_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'groups', 'owner' ) ) . '">' . CBTxt::Th( 'Fix All' ) . '</a>';
			$msgs->errors[]				=	CBTxt::Ph( ':: Group :: Missing owner user - [grp] - [fixall]', array( '[grp]' => implode( ', ', $grp_owner ), '[fixall]' => $fix_url ) );
		}

		if ( ! empty( $grp_notification ) ) {
			$fix_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'groups', 'notifications' ) ) . '">' . CBTxt::T( 'Fix All' ) . '</a>';
			$msgs->errors[]				=	CBTxt::Ph( ':: Group :: Missing owner notifications - [grp] - [fixall]', array( '[grp]' => implode( ', ', $grp_notification ), '[fixall]' => $fix_url ) );
		}

		$users							=	cbgjData::getUsers();

		$usr_grp						=	array();
		$usr_grp_id						=	array();
		$usr_user						=	array();
		$usr_notification				=	array();

		if ( $users ) foreach ( $users as $usr ) {
			$user_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'users', 'edit', (int) $usr->get( 'id' ) ) ) . '">' . $usr->get( 'id' ) . '</a>';

			if ( ! $usr->get( 'group' ) ) {
				$usr_grp_id[]			=	$user_url;
			} else {
				if ( ! $usr->getGroup()->get( 'id' ) ) {
					$usr_grp[]			=	$user_url;
				}
			}

			if ( ! $usr->get( 'user_id' ) ) {
				$usr_user[]				=	$user_url;
			} else {
				$notification			=	cbgjData::getNotifications( null, array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $usr->get( 'group' ) ), array( 'user_id', '=', (int) $usr->get( 'user_id' ) ) ), null, null, false );

				if ( ( ! $notification->get( 'id' ) ) && ( $usr->get( 'status' ) > 0 ) && cbgjClass::hasAccess( 'grp_usr_notifications', cbgjClass::getAuthorization( $usr->getCategory(), $usr->getGroup(), $usr->getOwner() ) ) ) {
					$usr_notification[]	=	$user_url;
				}
			}
		}

		if ( ! empty( $usr_grp ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: User :: Group does not exist in GroupJive - [usr]', array( '[usr]' => implode( ', ', $usr_grp_id ) ) );
		}

		if ( ! empty( $usr_grp_id ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: User :: Missing group - [usr]', array( '[usr]' => implode( ', ', $usr_grp_id ) ) );
		}

		if ( ! empty( $usr_user ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: User :: Missing user - [usr]', array( '[usr]' => implode( ', ', $usr_user ) ) );
		}

		if ( ! empty( $usr_notification ) ) {
			$fix_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'fix', 'users', 'notifications' ) ) . '">' . CBTxt::T( 'Fix All' ) . '</a>';
			$msgs->errors[]				=	CBTxt::Ph( ':: User :: Missing notifications - [usr] - [fixall]', array( '[usr]' => implode( ', ', $usr_notification ), '[fixall]' => $fix_url ) );
		}

		$invites						=	cbgjData::getInvites();

		$inv_grp						=	array();
		$inv_grp_id						=	array();
		$inv_usr_id						=	array();
		$inv_user						=	array();

		if ( $invites ) foreach ( $invites as $invite ) {
			$invite_url					=	'<a href="' . cbgjClass::getPluginURL( array( 'invites' ) ) . '">' . $invite->get( 'id' ) . '</a>';

			if ( ! $invite->get( 'group' ) ) {
				$inv_grp_id[]			=	$invite_url;
			} else {
				if ( ! $invite->getGroup()->get( 'id' ) ) {
					$inv_grp[]			=	$invite_url;
				}
			}

			if ( ! $invite->get( 'user_id' ) ) {
				$inv_user[]				=	$invite_url;
			} else {
				if ( ! $invite->getUser()->get( 'id' ) ) {
					$inv_usr_id			=	$invite_url;
				}
			}
		}

		if ( ! empty( $inv_grp ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: Invite :: Group does not exist in GroupJive - [inv]', array( '[inv]' => implode( ', ', $inv_grp ) ) );
		}

		if ( ! empty( $inv_grp_id ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: Invite :: Missing group - [inv]', array( '[inv]' => implode( ', ', $inv_grp_id ) ) );
		}

		if ( ! empty( $inv_usr_id ) ) {
			$msgs->warnings[]			=	CBTxt::Ph( ':: Invite :: User does not exist in Group - [inv]', array( '[inv]' => implode( ', ', $inv_usr_id ) ) );
		}

		if ( ! empty( $inv_user ) ) {
			$msgs->errors[]				=	CBTxt::Ph( ':: Invite :: Missing user - [inv]', array( '[inv]' => implode( ', ', $inv_user ) ) );
		}

		$gj_categories					=	$_CB_database->getTableStatus( '#__gj_grcategory' );
		$gj_groups						=	$_CB_database->getTableStatus( '#__gj_groups' );
		$gj_users						=	$_CB_database->getTableStatus( '#__gj_users' );

		if ( $gj_categories || $gj_groups || $gj_users ) {
			$migrate_url				=	cbgjClass::getPluginURL( array( 'tools', 'migrate' ), CBTxt::Th( 'Are you sure you want to migrate your old GroupJive data?' ) );
			$migrate					=	'<a href="javascript: void(0);" onclick="' . $migrate_url . '">' . CBTxt::Th( 'Migrate' ) . '</a>';

			$delmigrate_url				=	cbgjClass::getPluginURL( array( 'tools', 'delmigrate' ), CBTxt::Th( 'Are you sure you want to delete your old GroupJive data?' ) );
			$delmigrate					=	'<a href="javascript: void(0);" onclick="' . $delmigrate_url . '">' . CBTxt::Th( 'Delete' ) . '</a>';

			$msgs->info[]				=	CBTxt::Ph( ':: GroupJive :: Previous release database found - [mig_url] | [del_url]', array( '[mig_url]' => $migrate, '[del_url]' => $delmigrate ) );
		}

		$_PLUGINS->trigger( 'gj_onAfterTools', array( $msgs, $user, $plugin ) );

		if ( empty( $msgs->errors ) ) {
			$msgs->errors[]				=	CBTxt::Th( 'No errors were found.' );
		}

		if ( empty( $msgs->warnings ) ) {
			$msgs->warnings[]			=	CBTxt::Th( 'No warnings were found.' );
		}

		if ( empty( $msgs->info ) ) {
			$msgs->info[]				=	CBTxt::Th( 'No info was found.' );
		}

		HTML_cbgjAdmin::showTools( $msgs, $user, $plugin );
	}

	/**
	 * resaves categories fixing various issues
	 *
	 * @param string $mode
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function fixCategories( $mode, $user, $plugin ) {
		$categories					=	cbgjData::getCategories();

		if ( ! $categories ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'No categories found to fix.' ), false, true, 'error' );
		}

		switch ( $mode ) {
			case 'types':
				foreach ( $categories as $category ) {
					if ( ! $category->get( 'types' ) ) {
						$category->set( 'types', '1|*|2|*|3' );

						$category->store();
					}
				}
				break;
			case 'published':
				foreach ( $categories as $category ) {
					if ( ! $category->get( 'published' ) ) {
						$category->storeState( 1 );
					}
				}
				break;
			case 'gj_user':
				foreach ( $categories as $category ) {
					if ( ! $category->get( 'user_id' ) ) {
						$category->set( 'user_id', (int) $user->id );

						$category->store();
					}
				}
				break;
			case 'notifications':
				foreach ( $categories as $category ) {
					$notification	=	cbgjData::getNotifications( null, array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $category->get( 'id' ) ), array( 'user_id', '=', (int) $category->get( 'user_id' ) ) ), null, null, false );

					if ( ( ! $notification->get( 'id' ) ) && cbgjClass::hasAccess( 'cat_usr_notifications', cbgjClass::getAuthorization( $category, null, $category->getOwner() ) ) ) {
						cbgjClass::saveNotifications( $category->get( 'id' ), null, $category->getOwner(), $plugin );
					}
				}
				break;
		}

		cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Categories fixed successfully!' ), false, true );
	}

	/**
	 * resaves groups fixing various issues
	 *
	 * @param string $mode
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function fixGroups( $mode, $user, $plugin ) {
		$groups						=	cbgjData::getGroups();

		if ( ! $groups ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'No groups found to fix.' ), false, true, 'error' );
		}

		switch ( $mode ) {
			case 'type':
				foreach ( $groups as $group ) {
					if ( ! $group->get( 'type' ) ) {
						$group->set( 'type', 1 );

						$group->store();
					}
				}
				break;
			case 'published':
				foreach ( $groups as $group ) {
					if ( ! $group->get( 'published' ) ) {
						$group->storeState( 1 );
					}
				}
				break;
			case 'gj_user':
				foreach ( $groups as $group ) {
					if ( ! $group->get( 'user_id' ) ) {
						$group->storeOwner( $user->id );
					}
				}
				break;
			case 'owner':
				foreach ( $groups as $group ) {
					if ( ! $group->getUser()->get( 'id' ) ) {
						$group->storeOwner( $group->get( 'user_id' ) );
					}
				}
				break;
			case 'notifications':
				foreach ( $groups as $group ) {
					$notification	=	cbgjData::getNotifications( null, array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $group->get( 'user_id' ) ) ), null, null, false );

					if ( ( ! $notification->get( 'id' ) ) && cbgjClass::hasAccess( 'grp_usr_notifications', cbgjClass::getAuthorization( $group->getCategory(), $group, $group->getOwner() ) ) ) {
						cbgjClass::saveNotifications( $group->get( 'category' ), $group->get( 'id' ), $group->getOwner(), $plugin );
					}
				}
				break;
		}

		cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Groups fixed successfully!' ), false, true );
	}

	/**
	 * resaves users fixing various issues
	 *
	 * @param string $mode
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function fixUsers( $mode, $user, $plugin ) {
		$users						=	cbgjData::getUsers();

		if ( ! $users ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'No users found to fix.' ), false, true, 'error' );
		}

		switch ( $mode ) {
			case 'notifications':
				foreach ( $users as $usr ) {
					$notification	=	cbgjData::getNotifications( null, array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $usr->get( 'group' ) ), array( 'user_id', '=', (int) $usr->get( 'user_id' ) ) ), null, null, false );

					if ( ( ! $notification->get( 'id' ) ) && ( $usr->get( 'status' ) > 0 ) && cbgjClass::hasAccess( 'grp_usr_notifications', cbgjClass::getAuthorization( $usr->getCategory(), $usr->getGroup(), $usr->getOwner() ) ) ) {
						cbgjClass::saveNotifications( $usr->getCategory()->get( 'id' ), $usr->get( 'group' ), $usr->getOwner(), $plugin );
					}
				}
				break;
		}

		cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Users fixed successfully!' ), false, true );
	}

	/**
	 * migrate old groupjive data
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showMigrate( $user, $plugin ) {
		global $_CB_framework, $_CB_database;

		$gj_categories							=	$_CB_database->getTableStatus( '#__gj_grcategory' );
		$gj_groups								=	$_CB_database->getTableStatus( '#__gj_groups' );
		$gj_users								=	$_CB_database->getTableStatus( '#__gj_users' );
		$gj_path								=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/com_groupjive';

		if ( $gj_categories ) {
			$query								=	'SELECT *'
												.	"\n FROM " . $_CB_database->NameQuote( '#__gj_grcategory' );
			$_CB_database->setQuery( $query );
			$categories							=	$_CB_database->loadObjectList();

			if ( $categories ) foreach ( $categories as $category ) {
				$cat							=	new cbgjCategory( $_CB_database );

				$types							=	array();

				if ( $category->create_open ) {
					$types[]					=	1;
				}

				if ( $category->create_closed ) {
					$types[]					=	2;
				}

				if ( $category->create_invite ) {
					$types[]					=	3;
				}

				$cat->set( 'user_id', (int) ( $category->admin ? $category->admin : $user->id ) );
				$cat->set( 'name', trim( strip_tags( $category->catname ) ) );

				if ( $plugin->params->get( 'category_editor', 1 ) >= 2 ) {
					$cat->set( 'description', cbgjClass::getFilteredText( $category->descr ) );
				} else {
					$cat->set( 'description', trim( strip_tags( $category->descr ) ) );
				}

				$cat->set( 'types', ( is_array( $types ) ? implode( '|*|', $types ) : null ) );
				$cat->set( 'date', cbgjClass::getUTCDate() );
				$cat->set( 'ordering', (int) $category->ordering );
				$cat->set( 'published', (int) $category->published );

				if ( $category->access == 2 ) {
					$cat->set( 'access', 30 );
				} elseif ( $category->access == 1 ) {
					$cat->set( 'access', -1 );
				} else {
					$cat->set( 'access', -2 );
				}

				if ( ! $cat->store() ) {
					cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'Category failed to migrate! Error: [error]', array( '[error]' => $cat->getError() ) ), false, true, 'error' );
				}

				$cat_path						=	$plugin->imgsAbs . '/' . (int) $cat->get( 'id' );

				if ( $category->cat_image ) {
					$mode						=	cbgjClass::getFilePerms();

					if ( ! is_dir( $cat_path ) ) {
						$oldmask				=	@umask( 0 );

						if ( @mkdir( $cat_path, cbgjClass::getFolderPerms(), true ) ) {
							@umask( $oldmask );

							if ( ! file_exists( $plugin->imgsAbs . '/index.html' ) ) {
								@copy( $plugin->absPath . '/images/index.html', $plugin->imgsAbs . '/index.html' );
								@chmod( $plugin->imgsAbs . '/index.html', $mode );
							}

							if ( ! file_exists( $cat_path . '/index.html' ) ) {
								@copy( $plugin->absPath . '/images/index.html', $cat_path . '/index.html' );
								@chmod( $cat_path . '/index.html', $mode );
							}
						} else {
							@umask( $oldmask );
						}
					}

					if ( file_exists( $gj_path . '/' . $category->cat_image ) && ( ! file_exists( $cat_path . '/' . $category->cat_image ) ) ) {
						@copy( $gj_path . '/' . $category->cat_image, $cat_path . '/' . $category->cat_image );
						@chmod( $cat_path . '/' . $category->cat_imag, $mode );
					}

					if ( file_exists( $gj_path . '/tn' . $category->cat_image ) && ( ! file_exists( $cat_path . '/tn' . $category->cat_image ) ) ) {
						@copy( $gj_path . '/tn' . $category->cat_image, $cat_path . '/tn' . $category->cat_image );
						@chmod( $cat_path . '/tn' . $category->cat_image, $mode );
					}

					if ( file_exists( $cat_path . $category->cat_image ) && file_exists( $cat_path . 'tn' . $category->cat_image ) ) {
						$cat->set( 'logo', $category->cat_image );
					}
				}

				if ( $gj_groups ) {
					$query						=	'SELECT *'
												.	"\n FROM " . $_CB_database->NameQuote( '#__gj_groups' )
												.	"\n WHERE " . $_CB_database->NameQuote( 'category' ) . " = " . (int) $cat->get( 'id' );
					$_CB_database->setQuery( $query );
					$groups						=	$_CB_database->loadObjectList();

					if ( $groups ) foreach ( $groups as $group ) {
						$grp					=	new cbgjGroup( $_CB_database );

						$grp->set( 'user_id', (int) ( $group->user_id ? $group->user_id : $user->id ) );
						$grp->set( 'name', trim( strip_tags( $group->name ) ) );

						if ( $plugin->params->get( 'group_editor', 1 ) >= 2 ) {
							$grp->set( 'description', cbgjClass::getFilteredText( $group->descr ) );
						} else {
							$grp->set( 'description', trim( strip_tags( $group->descr ) ) );
						}

						$grp->set( 'type', (int) $group->type );
						$grp->set( 'date', $group->date_s );
						$grp->set( 'category', $cat->get( 'id' ) );
						$grp->set( 'published', (int) $group->active );
						$grp->set( 'access', -2 );

						if ( ! $grp->store() ) {
							cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'Group failed to migrate! Error: [error]', array( '[error]' => $grp->getError() ) ), false, true, 'error' );
						}

						$grp_path				=	$plugin->imgsAbs . '/' . (int) $cat->get( 'id' ) . '/' . (int) $grp->get( 'id' );

						if ( $group->logo ) {
							$mode				=	cbgjClass::getFilePerms();

							if ( ! is_dir( $grp_path ) ) {
								$oldmask		=	@umask( 0 );

								if ( @mkdir( $grp_path, cbgjClass::getFolderPerms(), true ) ) {
									@umask( $oldmask );

									if ( ! file_exists( $plugin->imgsAbs . '/index.html' ) ) {
										@copy( $plugin->absPath . '/images/index.html', $plugin->imgsAbs . '/index.html' );
										@chmod( $plugin->imgsAbs . '/index.html', $mode );
									}

									if ( ! file_exists( $cat_path . '/index.html' ) ) {
										@copy( $plugin->absPath . '/images/index.html', $cat_path . '/index.html' );
										@chmod( $cat_path . '/index.html', $mode );
									}

									if ( ! file_exists( $grp_path . '/index.html' ) ) {
										@copy( $plugin->absPath . '/images/index.html', $grp_path . '/index.html' );
										@chmod( $grp_path . '/index.html', $mode );
									}
								} else {
									@umask( $oldmask );
								}
							}

							if ( file_exists( $gj_path . '/' . $group->logo ) && ( ! file_exists( $grp_path . '/' . $group->logo ) ) ) {
								@copy( $gj_path . '/' . $group->logo, $grp_path . '/' . $group->logo );
								@chmod( $grp_path . '/' . $group->logo, $mode );
							}

							if ( file_exists( $gj_path . '/tn' . $group->logo ) && ( ! file_exists( $grp_path . '/tn' . $group->logo ) ) ) {
								@copy( $gj_path . '/tn' . $group->logo, $grp_path . '/tn' . $group->logo );
								@chmod( $grp_path . '/tn' . $group->logo, $mode );
							}

							if ( file_exists( $grp_path . $group->logo ) && file_exists( $grp_path . 'tn' . $group->logo ) ) {
								$grp->set( 'logo', $group->logo );
							}
						}

						$owner					=	new cbgjUser( $_CB_database );

						$owner->set( 'user_id', (int) $grp->get( 'user_id' ) );
						$owner->set( 'group', (int) $grp->get( 'id' ) );
						$owner->set( 'date', $grp->get( 'date' ) );
						$owner->set( 'status', 4 );

						if ( ! $owner->store() ) {
							cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'Owner failed to migrate! Error: [error]', array( '[error]' => $owner->getError() ) ), false, true, 'error' );
						}

						if ( $gj_users ) {
							$query				=	'SELECT *'
												.	"\n FROM " . $_CB_database->NameQuote( '#__gj_users' )
												.	"\n WHERE " . $_CB_database->NameQuote( 'id_group' ) . " = " . (int) $grp->get( 'id' )
												.	"\n AND " . $_CB_database->NameQuote( 'id_user' ) . " != " . (int) $grp->get( 'user_id' );
							$_CB_database->setQuery( $query );
							$users				=	$_CB_database->loadObjectList();

							if ( $users ) foreach ( $users as $u ) {
								$usr			=	new cbgjUser( $_CB_database );

								$usr->set( 'user_id', (int) $u->id_user );
								$usr->set( 'group', (int) $grp->get( 'id' ) );
								$usr->set( 'date', $u->date );
								$usr->set( 'status', ( $u->status == 'active' ? 1 : 0 ) );

								if ( ! $usr->store() ) {
									cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'User failed to migrate! Error: [error]', array( '[error]' => $usr->getError() ) ), false, true, 'error' );
								}
							}
						}
					}
				}
			}
		} else {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Nothing to migrate.' ), false, true, 'error' );
		}

		cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'GroupJive migration successful.' ), false, true );
	}

	/**
	 * delete old groupjive data
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteMigrate( $user, $plugin ) {
		global $_CB_framework, $_CB_database;

		$query		=	'DROP TABLE IF EXISTS ' . $_CB_database->NameQuote( '#__gj_active' );
		$_CB_database->setQuery( $query );
		if ( ! $_CB_database->query() ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'GroupJive failed to delete! Error: [error]', array( '[error]' => $_CB_database->stderr( true ) ) ), false, true, 'error' );
		}

		$query		=	'DROP TABLE IF EXISTS ' . $_CB_database->NameQuote( '#__gj_bul' );
		$_CB_database->setQuery( $query );
		if ( ! $_CB_database->query() ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'GroupJive failed to delete! Error: [error]', array( '[error]' => $_CB_database->stderr( true ) ) ), false, true, 'error' );
		}

		$query		=	'DROP TABLE IF EXISTS ' . $_CB_database->NameQuote( '#__gj_grcategory' );
		$_CB_database->setQuery( $query );
		if ( ! $_CB_database->query() ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'GroupJive failed to delete! Error: [error]', array( '[error]' => $_CB_database->stderr( true ) ) ), false, true, 'error' );
		}

		$query		=	'DROP TABLE IF EXISTS ' . $_CB_database->NameQuote( '#__gj_groups' );
		$_CB_database->setQuery( $query );
		if ( ! $_CB_database->query() ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'GroupJive failed to delete! Error: [error]', array( '[error]' => $_CB_database->stderr( true ) ) ), false, true, 'error' );
		}

		$query		=	'DROP TABLE IF EXISTS ' . $_CB_database->NameQuote( '#__gj_jb' );
		$_CB_database->setQuery( $query );
		if ( ! $_CB_database->query() ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'GroupJive failed to delete! Error: [error]', array( '[error]' => $_CB_database->stderr( true ) ) ), false, true, 'error' );
		}

		$query		=	'DROP TABLE IF EXISTS ' . $_CB_database->NameQuote( '#__gj_users' );
		$_CB_database->setQuery( $query );
		if ( ! $_CB_database->query() ) {
			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'GroupJive failed to delete! Error: [error]', array( '[error]' => $_CB_database->stderr( true ) ) ), false, true, 'error' );
		}

		$gj_path	=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/com_groupjive';

		if ( is_dir( $gj_path ) ) {
			cbgjClass::deleteDirectory( $gj_path );
		}

		cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'GroupJive deleted successful.' ), false, true );
	}

	/**
	 * prepare backend integrations render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showIntegrations( $user, $plugin ) {
		global $_CB_framework, $_CB_database;

		$paging				=	new cbgjPaging( 'integrations' );

		$limit				=	$paging->getlimit( 30 );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$access				=	$paging->getFilter( 'access' );
		$state				=	$paging->getFilter( 'state' );
		$id					=	$paging->getFilter( 'id' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	'( ' . $_CB_database->NameQuote( 'name' ) . ' LIKE ' . $_CB_database->Quote( '%' . $_CB_database->getEscaped( $search, true ) . '%', false ) . ' )';
		}

		if ( isset( $access ) && ( ! in_array( $access, array( '', '-1' ) ) ) ) {
			$where[]		=	'( ' . $_CB_database->NameQuote( 'access' ) . ' = ' . (int) $access . ' )';
		}

		if ( isset( $state ) && ( $state != '' ) ) {
			$where[]		=	'( ' . $_CB_database->NameQuote( 'published' ) . ' = ' . (int) $state . ' )';
		}

		if ( isset( $id ) && ( $id != '' ) ) {
			$where[]		=	'( ' . $_CB_database->NameQuote( 'id' ) . ' = ' . (int) $id . ' )';
		}

		$query				=	'SELECT COUNT(*)'
							.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
							.	"\n WHERE " . $_CB_database->NameQuote( 'type' ) . " = " . $_CB_database->Quote( 'user/plug_cbgroupjive/plugins' )
							.	( count( $where ) ? "\n AND " . implode( "\n AND ", $where ) : null );
		$_CB_database->setQuery( $query );
		$total				=	$_CB_database->loadResult();

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$query				=	'SELECT *'
							.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
							.	"\n WHERE " . $_CB_database->NameQuote( 'type' ) . " = " . $_CB_database->Quote( 'user/plug_cbgroupjive/plugins' )
							.	( count( $where ) ? "\n AND " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY " . $_CB_database->NameQuote( 'ordering' ) . " ASC";
		$_CB_database->setQuery( $query, (int) $pageNav->limitstart, (int) $pageNav->limit );
		$rows				=	$_CB_database->loadObjectList();

		$input				=	array();

		$accessLevels		=	$_CB_framework->acl->get_access_children_tree( true );

		$listAccess			=	array();
		$listAccess[]		=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- Select Access -' ) );
		$listAccess			=	array_merge( $listAccess, $accessLevels );
		$input['access']	=	$paging->getInputSelect( 'adminForm', 'access', $listAccess, ( in_array( $access, array( '', '-1' ) ) ? -1 : $access ), true );

		$listState			=	array();
		$listState[]		=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select State -' ) );
		$listState[]		=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Published' ) );
		$listState[]		=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Unpublished' ) );
		$input['state']		=	$paging->getInputSelect( 'adminForm', 'state', $listState, $state );

		$input['search']	=	$paging->getInputText( 'adminForm', 'search', $search, '30' );
		$input['id']		=	$paging->getInputText( 'adminForm', 'id', $id, '6' );

		$pageNav->searching	=	( count( $where ) ? true : false );

		HTML_cbgjAdmin::showIntegrations( $rows, $pageNav, $input, $accessLevels, $user, $plugin );
	}

	/**
	 * prepare backend menus render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function showMenus( $user, $plugin ) {
		$categories							=	cbgjData::listArray( cbgjData::getCategories() );
		$groups								=	cbgjData::listArray( cbgjData::getGroups() );

		$input								=	array();

		// General
		$input['plugin']					=	'<input type="checkbox" id="type_plugin" name="type[]" class="inputbox" value="plugin" />';
		$input['overview']					=	'<input type="checkbox" id="type_overview" name="type[]" class="inputbox" value="overview" />';
		$input['panel']						=	'<input type="checkbox" id="type_panel" name="type[]" class="inputbox" value="panel" />';
		$input['notifications']				=	'<input type="checkbox" id="type_notifications" name="type[]" class="inputbox" value="notifications" />';
		$input['message']					=	'<input type="checkbox" id="type_message" name="type[]" class="inputbox" value="message" />';
		$input['all_categories']			=	'<input type="checkbox" id="type_all_categories" name="type[]" class="inputbox" value="all-categories" />';
		$input['all_groups']				=	'<input type="checkbox" id="type_all_groups" name="type[]" class="inputbox" value="all-groups" />';
		$input['all_my_categories']			=	'<input type="checkbox" id="type_all_my_categories" name="type[]" class="inputbox" value="all-my-categories" />';
		$input['all_my_groups']				=	'<input type="checkbox" id="type_all_my_groups" name="type[]" class="inputbox" value="all-my-groups" />';
		$input['new_category']				=	'<input type="checkbox" id="type_new_category" name="type[]" class="inputbox" value="new-category" />';
		$input['new_group']					=	'<input type="checkbox" id="type_new_group" name="type[]" class="inputbox" value="new-group" />';
		$input['approve_category']			=	'<input type="checkbox" id="type_approve_category" name="type[]" class="inputbox" value="approve-category" />';
		$input['approve_group']				=	'<input type="checkbox" id="type_approve_group" name="type[]" class="inputbox" value="approve-group" />';

		// Categories
		$input['cats']						=	moscomprofilerHTML::selectList( $categories, 'cats[]', 'size="5" multiple="multiple" class="inputbox required"', 'value', 'text', null, 1, false, false );
		$input['categories']				=	'<input type="checkbox" id="type_categories" name="type[]" class="inputbox" value="categories" />';
		$input['new_category_nested']		=	'<input type="checkbox" id="type_new_category_nested" name="type[]" class="inputbox" value="new-category-nested" />';
		$input['new_category_group']		=	'<input type="checkbox" id="type_new_category_group" name="type[]" class="inputbox" value="new-category-group" />';
		$input['notifications_category']	=	'<input type="checkbox" id="type_notifications_category" name="type[]" class="inputbox" value="notifications-category" />';
		$input['message_groups']			=	'<input type="checkbox" id="type_message_groups" name="type[]" class="inputbox" value="message-groups" />';
		$input['edit_category']				=	'<input type="checkbox" id="type_edit_category" name="type[]" class="inputbox" value="edit-category" />';

		// Groups
		$input['grps']						=	moscomprofilerHTML::selectList( $groups, 'grps[]', 'size="5" multiple="multiple" class="inputbox required"', 'value', 'text', null, 1, false, false );
		$input['groups']					=	'<input type="checkbox" id="type_groups" name="type[]" class="inputbox" value="groups" />';
		$input['new_group_nested']			=	'<input type="checkbox" id="type_new_group_nested" name="type[]" class="inputbox" value="new-group-nested" />';
		$input['notifications_group']		=	'<input type="checkbox" id="type_notifications_group" name="type[]" class="inputbox" value="notifications-group" />';
		$input['message_users']				=	'<input type="checkbox" id="type_message_users" name="type[]" class="inputbox" value="message-users" />';
		$input['edit_group']				=	'<input type="checkbox" id="type_edit_group" name="type[]" class="inputbox" value="edit-group" />';
		$input['join']						=	'<input type="checkbox" id="type_join" name="type[]" class="inputbox" value="join" />';
		$input['leave']						=	'<input type="checkbox" id="type_leave" name="type[]" class="inputbox" value="leave" />';

		HTML_cbgjAdmin::showMenus( $input, $user, $plugin );
	}

	/**
	 * save menus
	 *
	 * @param string $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function saveMenus( $user, $plugin ) {
		$types								=	cbGetParam( $_POST, 'type', array() );
		$categories							=	cbGetParam( $_POST, 'cats', array() );
		$groups								=	cbGetParam( $_POST, 'grps', array() );

		if ( $categories ) {
			cbArrayToInts( $categories );
		}

		if ( $groups ) {
			cbArrayToInts( $groups );
		}

		if ( $types ) {
			foreach ( $types as $type ) {
				switch ( $type ) {
					case 'categories':
						if ( $categories ) {
							foreach ( $categories as $catid ) {
								$category	=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );

								if ( ! cbgjClass::setMenu( $category->getName(), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=show&cat=' . $category->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[category_name] menu failed to create!', array( '[category_name]' => $category->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No categories specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'groups':
						if ( $groups ) {
							foreach ( $groups as $grpid ) {
								$group		=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

								if ( ! cbgjClass::setMenu( $group->getName(), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=show&cat=' . $group->get( 'category' ) . '&grp=' . $group->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[group_name] menu failed to create!', array( '[group_name]' => $group->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No groups specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'new-category-nested':
						if ( $categories ) {
							foreach ( $categories as $catid ) {
								$category	=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( '[category_name] New [category]', array( '[category_name]' => $category->getName(), '[category]' => cbgjClass::getOverride( 'category' ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=new&cat=' . $category->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[category_name] new category menu failed to create!', array( '[category_name]' => $category->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No categories specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'new-category-group':
						if ( $categories ) {
							foreach ( $categories as $catid ) {
								$category	=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( '[category_name] New [group]', array( '[category_name]' => $category->getName(), '[group]' => cbgjClass::getOverride( 'group' ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=new&cat=' . $category->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[category_name] new group menu failed to create!', array( '[category_name]' => $category->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No categories specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'new-group-nested':
						if ( $groups ) {
							foreach ( $groups as $grpid ) {
								$group		=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( '[group_name] New [category]', array( '[group_name]' => $group->getName(), '[category]' => cbgjClass::getOverride( 'category' ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=new&cat=' . $group->get( 'category' ) . '&grp=' . $group->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[group_name] new category menu failed to create!', array( '[group_name]' => $group->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No categories specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'approve-category':
						if ( ! cbgjClass::setMenu( CBTxt::P( '[category] Approval', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=approval', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Category approval menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'approve-group':
						if ( ! cbgjClass::setMenu( CBTxt::P( '[group] Approval', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=approval', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Group approval menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'new-category':
						if ( ! cbgjClass::setMenu( CBTxt::P( 'New [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=new', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'New category menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'new-group':
						if ( ! cbgjClass::setMenu( CBTxt::P( 'New [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=new', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'New group menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'all-categories':
						if ( ! cbgjClass::setMenu( CBTxt::P( 'All [categories]', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=all', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'All categories menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'all-groups':
						if ( ! cbgjClass::setMenu( CBTxt::P( 'All [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=all', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'All groups menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'all-my-categories':
						if ( ! cbgjClass::setMenu( CBTxt::P( 'All [categories]', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=allmy', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'All categories menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'all-my-groups':
						if ( ! cbgjClass::setMenu( CBTxt::P( 'All [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=allmy', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'All groups menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'edit-group':
						if ( $groups ) {
							foreach ( $groups as $grpid ) {
								$group		=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( 'Edit [group_name]', array( '[group_name]' => $group->getName() ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=edit&cat=' . $group->get( 'category' ) . '&grp=' . $group->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( 'Edit [group_name] menu failed to create!', array( '[group_name]' => $group->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No groups specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'join':
						if ( $groups ) {
							foreach ( $groups as $grpid ) {
								$group		=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( 'Join [group_name]', array( '[group_name]' => $group->getName() ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=join&cat=' . $group->get( 'category' ) . '&grp=' . $group->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( 'Join [group_name] menu failed to create!', array( '[group_name]' => $group->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No groups specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'leave':
						if ( $groups ) {
							foreach ( $groups as $grpid ) {
								$group		=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( 'Leave [group_name]', array( '[group_name]' => $group->getName() ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=leave&cat=' . $group->get( 'category' ) . '&grp=' . $group->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( 'Leave [group_name] menu failed to create!', array( '[group_name]' => $group->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No groups specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'message-groups':
						if ( $categories ) {
							foreach ( $categories as $catid ) {
								$category	=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( '[category_name] Message [groups]', array( '[category_name]' => $category->getName(), '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=message&cat=' . $category->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[category_name] message groups menu failed to create!', array( '[category_name]' => $category->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No categories specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'message-users':
						if ( $groups ) {
							foreach ( $groups as $grpid ) {
								$group		=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( '[group_name] Message [users]', array( '[group_name]' => $group->getName(), '[users]' => cbgjClass::getOverride( 'user', true ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=groups&func=message&cat=' . $group->get( 'category' ) . '&grp=' . $group->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[group_name] message users menu failed to create!', array( '[group_name]' => $group->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No groups specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'edit-category':
						if ( $categories ) {
							foreach ( $categories as $catid ) {
								$category	=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( 'Edit [category_name]', array( '[category_name]' => $category->getName() ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=categories&func=edit&cat=' . $category->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( 'Edit [category_name] menu failed to create!', array( '[category_name]' => $category->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No categories specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'notifications-category':
						if ( $categories ) {
							foreach ( $categories as $catid ) {
								$category	=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( '[category_name] Notifications', array( '[category_name]' => $category->getName() ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=notifications&func=show&cat=' . $category->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[category_name] Notifications menu failed to create!', array( '[category_name]' => $category->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No categories specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'notifications-group':
						if ( $groups ) {
							foreach ( $groups as $grpid ) {
								$group		=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

								if ( ! cbgjClass::setMenu( CBTxt::P( '[group_name] Notifications', array( '[group_name]' => $group->getName() ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=notifications&func=show&cat=' . $group->get( 'category' ) . '&grp=' . $group->get( 'id' ), $plugin ) ) {
									cbgjClass::getPluginURL( array( 'menus' ), CBTxt::P( '[group_name] Notifications menu failed to create!', array( '[group_name]' => $group->getName() ) ), false, true, 'error' );
								}
							}
						} else {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No groups specified to create menus for.' ), false, true, 'error' );
						}
						break;
					case 'message':
						if ( ! cbgjClass::setMenu( CBTxt::P( 'Message [categories]', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=overview&func=message', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Message categories menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'notifications':
						if ( ! cbgjClass::setMenu( CBTxt::T( 'Notifications' ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=notifications&func=show', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Notifications menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'panel':
						if ( ! cbgjClass::setMenu( CBTxt::T( 'Panel' ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=panel', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Panel menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'overview':
						if ( ! cbgjClass::setMenu( CBTxt::P( '[categories] Overview', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element . '&action=overview', $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Overview menu failed to create!' ), false, true, 'error' );
						}
						break;
					case 'plugin':
						$general_title	=	CBTxt::T( $plugin->params->get( 'general_title', null ) );

						if ( ! cbgjClass::setMenu( ( $general_title ? $general_title : $plugin->name ), 'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element, $plugin ) ) {
							cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Plugin menu failed to create!' ), false, true, 'error' );
						}
						break;
					default:
						cbgjClass::getIntegrations( 'gj_onMenusIntegrationsSave', array( $type, $categories, $groups, $user, $plugin ), null, 'raw' );
						break;
				}
			}

			cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'Menus created successfully!' ), false, true );
		} else {
			cbgjClass::getPluginURL( array( 'menus' ), CBTxt::T( 'No menus to create.' ), false, true, 'error' );
		}
	}
}
?>