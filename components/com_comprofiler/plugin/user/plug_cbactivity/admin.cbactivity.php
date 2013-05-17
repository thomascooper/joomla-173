<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class cbactivityAdmin extends cbPluginHandler {

	public function editPluginView( $row, $option, $task, $uid, $action, $element, $mode, $pluginParams ) {
		global $_CB_framework, $_CB_database, $_CB_Backend_Menu, $_CB_Backend_task, $_PLUGIN_Backend_Title, $_PLUGINS;

		if ( ! $_CB_framework->check_acl( 'canManageUsers', $_CB_framework->myUserType() ) ) {
			cbRedirect( $_CB_framework->backendUrl( 'index.php' ), _UE_NOT_AUTHORIZED, 'error' );
		}

		outputCbJs( 2 );
		outputCbTemplate( 2 );

		$plugin					=	cbactivityClass::getPlugin();

		$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . '/admin.' . $plugin->element . '.css' );

		require_once( $plugin->absPath . '/admin.' . $plugin->element . '.html.php' );

		$_CB_Backend_task		=	$task;
		$_PLUGIN_Backend_Title	=	array();
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

		if ( ! is_array( $order ) ) {
			$order				=	array( $order );
		}

		$save_mode				=	( $mode == 'applyPlugin' ? 'apply' : $function );

		ob_start();
		switch ( $action ) {
			case 'activity':
				switch ( $function ) {
					case 'batch':
						$this->batchActivity( $id, $user, $plugin );
						break;
					case 'delete':
						cbSpoofCheck( 'plugin' );
						$this->deleteActivity( $id, $user, $plugin);
						break;
					case 'new':
						$this->showActivityEdit( null, $user, $plugin );
						break;
					case 'edit':
						$this->showActivityEdit( $id[0], $user, $plugin );
						break;
					case 'save':
					case 'apply':
						cbSpoofCheck( 'plugin' );
						$this->saveActivityEdit( $id[0], $save_mode, $user, $plugin );
						break;
					case 'show':
					default:
						$this->showActivity( $user, $plugin );
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
			case 'menu':
				$this->createMenu( $user, $plugin );
				break;
			default:
				$this->showPlugin( $user, $plugin );
				break;
		}
		$html					=	ob_get_contents();
		ob_end_clean();

		ob_start();
		include_once( $plugin->absPath . '/toolbar.' . $plugin->element . '.php' );
		$toolbar				=	ob_get_contents();
		ob_end_clean();

		$title					=	( isset( $_PLUGIN_Backend_Title[0] ) ? $_PLUGIN_Backend_Title[0] : null );
		$class					=	( isset( $_PLUGIN_Backend_Title[1] ) ? ' ' . $_PLUGIN_Backend_Title[1] : null );
		$return					=	'<div style="margin:0px;border-width:0px;padding:0px;float:left;width:100%;text-align:left;" class="cbactivityAdmin">'
								.		'<div id="cbAdminMainWrapper" style="margin:0px;border-width:0px;padding:0px;float:none;width:auto;">'
								.			'<div style="float:right;" class="cbactivityAdminToolbar">'
								.				$toolbar
								.			'</div>'
								.			'<div class="header' . $class . '">'
								.				$title
								.			'</div>'
								.			'<div style="clear:both;"></div>'
								.			'<div style="float:left;width:100%;margin-top:10px;">'
								.				$html
								.			'</div>'
								.			'<div style="clear:both;"></div>'
								.		'</div>'
								.	'</div>';

		echo $return;
	}

	private function createMenu( $user, $plugin ) {
		$general_title	=	CBTxt::T( $plugin->params->get( 'general_title', $plugin->name ) );
		$frontend_url	=	'index.php?option=' . $plugin->option . '&task=pluginclass&plugin=' . $plugin->element;

		if ( ! cbactivityClass::setMenu( ( $general_title ? $general_title : $plugin->name ), $frontend_url, $plugin ) ) {
			cbactivityClass::getPluginURL( array(), CBTxt::T( 'Menu failed to create!' ), false, true, 'error' );
		}

		cbactivityClass::getPluginURL( array(), CBTxt::T( 'Menu created successfully!' ), false, true );
	}

	private function showPlugin( $user, $plugin ) {
		$menu			=	new stdClass();

		$menu->activity	=	'<a href="' . cbactivityClass::getPluginURL( array( 'activity' ) ) . '">'
						.		'<div><img src="' . $plugin->livePath . '/images/icon-128-activity.png" /></div>'
						.		'<div>' . CBTxt::T( 'Activity' ) . '</div>'
						.	'</a>';

		$menu->config	=	'<a href="' . cbactivityClass::getPluginURL( array( 'config' ) ) . '">'
						.		'<div><img src="' . $plugin->livePath . '/images/icon-128-config.png" /></div>'
						.		'<div>' . CBTxt::T( 'Config' ) . '</div>'
						.	'</a>';

		$xml			=	new CBSimpleXMLElement( trim( file_get_contents( $plugin->xml ) ) );

		HTML_cbactivityAdmin::showPlugin( $menu, $xml, $user, $plugin );
	}

	private function showActivity( $user, $plugin ) {
		global $_CB_framework;

		$js						=	"$( '.batchSubmit' ).click( function() {"
								.		"cbDoListTask( this, 'editPlugin', 'action', 'activity.batch', 'id' );"
								.		"return false;"
								.	"});"
								.	"$( '.batchReset' ).click( function() {"
								.		"$( '.batchForm tbody' ).find( 'input,select' ).val( '' );"
								.		"return false;"
								.	"});";

		$_CB_framework->outputCbJQuery( $js );

		$paging					=	new cbactivityPaging( 'activity' );

		$limit					=	$paging->getlimit();
		$limitstart				=	$paging->getLimistart();
		$filter_owner			=	$paging->getFilter( 'owner' );
		$filter_user			=	$paging->getFilter( 'user' );
		$filter_type			=	$paging->getFilter( 'type' );
		$filter_subtype			=	$paging->getFilter( 'subtype' );
		$filter_item			=	$paging->getFilter( 'item' );
		$filter_id				=	$paging->getFilter( 'id' );
		$where					=	array();

		if ( isset( $filter_owner ) && ( $filter_owner != '' ) ) {
			$where[]			=	array( 'user_id', '=', $filter_id );
		}

		if ( isset( $filter_user ) && ( $filter_user != '' ) ) {
			$where[]			=	array( 'user', '=', $filter_id );
		}

		if ( isset( $filter_type ) && ( $filter_type != '' ) ) {
			$where[]			=	array( 'type', 'CONTAINS', $filter_type );
		}

		if ( isset( $filter_subtype ) && ( $filter_subtype != '' ) ) {
			$where[]			=	array( 'subtype', 'CONTAINS', $filter_subtype );
		}

		if ( isset( $filter_item ) && ( $filter_item != '' ) ) {
			$where[]			=	array( 'item', '=', $filter_item );
		}

		if ( isset( $filter_id ) && ( $filter_id != '' ) ) {
			$where[]			=	array( 'id', '=', $filter_id );
		}

		$searching				=	( count( $where ) ? true : false );

		$total					=	count( cbactivityData::getActivity( $where ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	array_values( cbactivityData::getActivity( $where, null, array( $pageNav->limitstart, $pageNav->limit ) ) );

		$input					=	array();

		$input['owner']			=	$paging->getInputText( 'adminForm', 'owner', $filter_owner, '20' );
		$input['user']			=	$paging->getInputText( 'adminForm', 'user', $filter_user, '20' );
		$input['type']			=	$paging->getInputText( 'adminForm', 'type', $filter_type, '15' );
		$input['subtype']		=	$paging->getInputText( 'adminForm', 'subtype', $filter_subtype, '15' );
		$input['item']			=	$paging->getInputText( 'adminForm', 'item', $filter_item, '15' );
		$input['id']			=	$paging->getInputText( 'adminForm', 'id', $filter_id, '6' );

		$input['batch_owner']	=	'<input type="text" id="batch_owner" name="batch_owner" value="" class="inputbox" size="4" />';
		$input['batch_user']	=	'<input type="text" id="batch_user" name="batch_user" value="" class="inputbox" size="4" />';
		$input['batch_type']	=	'<input type="text" id="batch_type" name="batch_type" value="" class="inputbox" size="30" />';
		$input['batch_subtype']	=	'<input type="text" id="batch_subtype" name="batch_subtype" value="" class="inputbox" size="30" />';
		$input['batch_item']	=	'<input type="text" id="batch_item" name="batch_item" value="" class="inputbox" size="30" />';
		$input['batch_title']	=	'<input type="text" id="batch_title" name="batch_title" value="" class="inputbox" size="60" />';
		$input['batch_icon']	=	'<input type="text" id="batch_icon" name="batch_icon" value="" class="inputbox" size="20" />';
		$input['batch_class']	=	'<input type="text" id="batch_class" name="batch_class" value="" class="inputbox" size="30" />';

		$pageNav->searching		=	$searching;

		HTML_cbactivityAdmin::showActivity( $rows, $pageNav, $input, $user, $plugin );
	}

	private function showActivityEdit( $id, $user, $plugin, $message = null ) {
		$row				=	cbactivityData::getActivity( array( 'id', '=', $id ), null, null, false );

		$input				=	array();

		$input['owner']		=	'<input type="text" id="owner" name="owner" value="' . (int) cbactivityClass::getCleanParam( 'owner', $row->get( 'user_id', $user->get( 'id' ) ) ) . '" class="inputbox" size="4" />';
		$input['user']		=	'<input type="text" id="user" name="user" value="' . (int) cbactivityClass::getCleanParam( 'user', $row->get( 'user' ) ) . '" class="inputbox" size="4" />';
		$input['type']		=	'<input type="text" id="type" name="type" value="' . htmlspecialchars( cbactivityClass::getCleanParam( 'type', $row->get( 'type' ) ) ) . '" class="inputbox" size="30" />';
		$input['subtype']	=	'<input type="text" id="subtype" name="subtype" value="' . htmlspecialchars( cbactivityClass::getCleanParam( 'subtype', $row->get( 'subtype' ) ) ) . '" class="inputbox" size="30" />';
		$input['item']		=	'<input type="text" id="item" name="item" value="' . htmlspecialchars( cbactivityClass::getCleanParam( 'item', $row->get( 'item' ) ) ) . '" class="inputbox" size="30" />';
		$input['from']		=	'<textarea id="from" name="from" class="inputbox" cols="40" rows="5">' . htmlspecialchars( cbactivityClass::getHTMLCleanParam( 'from', $row->get( 'from' ) ) ) . '</textarea>';
		$input['to']		=	'<textarea id="to" name="to" class="inputbox" cols="40" rows="5">' . htmlspecialchars( cbactivityClass::getHTMLCleanParam( 'to', $row->get( 'to' ) ) ) . '</textarea>';
		$input['title']		=	'<input type="text" id="title" name="title" value="' . htmlspecialchars( cbactivityClass::getCleanParam( 'title', $row->get( 'title' ) ) ) . '" class="inputbox" size="60" />';
		$input['message']	=	'<textarea id="msg" name="msg" class="inputbox" cols="40" rows="5">' . htmlspecialchars( cbactivityClass::getHTMLCleanParam( 'message', $row->get( 'message' ) ) ) . '</textarea>';
		$input['icon']		=	'<input type="text" id="icon" name="icon" value="' . htmlspecialchars( cbactivityClass::getCleanParam( 'icon', $row->get( 'icon' ) ) ) . '" class="inputbox" size="20" />';
		$input['class']		=	'<input type="text" id="class" name="class" value="' . htmlspecialchars( cbactivityClass::getCleanParam( 'class', $row->get( 'class' ) ) ) . '" class="inputbox" size="30" />';

		cbactivityClass::displayMessage( $message );

		HTML_cbactivityAdmin::showActivityEdit( $row, $input, $user, $plugin );
	}

	private function saveActivityEdit( $id, $task, $user, $plugin ) {
		$row	=	cbactivityData::getActivity( array( 'id', '=', $id ), null, null, false );

		$row->set( 'user_id', (int) cbactivityClass::getCleanParam( 'owner', $row->get( 'user_id', $user->get( 'id' ) ) ) );
		$row->set( 'user', (int) cbactivityClass::getCleanParam( 'user', $row->get( 'user' ) ) );
		$row->set( 'type', cbactivityClass::getCleanParam( 'type', $row->get( 'type' ) ) );
		$row->set( 'subtype', cbactivityClass::getCleanParam( 'subtype', $row->get( 'subtype' ) ) );
		$row->set( 'item', cbactivityClass::getCleanParam( 'item', $row->get( 'item' ) ) );
		$row->set( 'from', cbactivityClass::getHTMLCleanParam( 'from', $row->get( 'from' ) ) );
		$row->set( 'to', cbactivityClass::getHTMLCleanParam( 'to', $row->get( 'to' ) ) );
		$row->set( 'title', cbactivityClass::getCleanParam( 'title', $row->get( 'title' ) ) );
		$row->set( 'message', cbactivityClass::getHTMLCleanParam( 'msg', $row->get( 'message' ) ) );
		$row->set( 'icon', cbactivityClass::getCleanParam( 'icon', $row->get( 'icon' ) ) );
		$row->set( 'class', cbactivityClass::getCleanParam( 'class', $row->get( 'class' ) ) );

		if ( ! $row->get( 'date' ) ) {
			$row->set( 'date', cbactivityClass::getUTCDate() );
		}

		if ( $row->get( 'user_id' ) == '' ) {
			$row->set( '_error', CBTxt::T( 'Owner not specified!' ) );
		} elseif ( $row->get( 'type' ) == '' ) {
			$row->set( '_error', CBTxt::T( 'Type not specified!' ) );
		}

		if ( $row->getError() || ( ! $row->store() ) ) {
			$this->showActivityEdit( $id, $user, $plugin, CBTxt::P( 'Activity failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
		}

		if ( $task == 'apply' ) {
			cbactivityClass::getPluginURL( array( 'activity', 'edit', $row->id ), CBTxt::T( 'Activity saved successfully!' ), false, true );
		} else {
			cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::T( 'Activity saved successfully!' ), false, true );
		}

		cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::T( 'Activity saved successfully!' ), false, true );
	}

	private function deleteActivity( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbactivityData::getActivity( array( 'id', '=', $id ), null, null, false );

				if ( ! $row->delete() ) {
					cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::P( 'Activity failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::T( 'Activity deleted successfully!' ), false, true );
		}

		cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::T( 'Activity not found.' ), false, true, 'error' );
	}

	private function batchActivity( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			$batch_owner			=	cbGetParam( $_REQUEST, 'batch_owner', null );
			$batch_user				=	cbGetParam( $_REQUEST, 'batch_user', null );
			$batch_type				=	cbGetParam( $_REQUEST, 'batch_type', null );
			$batch_subtype			=	cbGetParam( $_REQUEST, 'batch_subtype', null );
			$batch_item				=	cbGetParam( $_REQUEST, 'batch_item', null );
			$batch_title			=	cbGetParam( $_REQUEST, 'batch_title', null );
			$batch_icon				=	cbGetParam( $_REQUEST, 'batch_icon', null );
			$batch_class			=	cbGetParam( $_REQUEST, 'batch_class', null );

			if ( ( $batch_owner != '' ) || ( $batch_user != '' ) || ( $batch_type != '' ) || ( $batch_subtype != '' ) || ( $batch_item != '' ) || ( $batch_title != '' ) || ( $batch_icon != '' ) || ( $batch_class != '' ) ) {
				foreach ( $ids as $id ) {
					$row			=	cbactivityData::getActivity( array( 'id', '=', $id ), null, null, false );
					$process		=	false;

					if ( $batch_owner != '' ) {
						$row->set( 'owner', (int) cbactivityClass::getCleanParam( 'batch_owner', $row->get( 'owner' ) ) );

						$process	=	true;
					}

					if ( $batch_owner != '' ) {
						$row->set( 'user', (int) cbactivityClass::getCleanParam( 'batch_user', $row->get( 'user' ) ) );

						$process	=	true;
					}

					if ( $batch_type != '' ) {
						$row->set( 'type', cbactivityClass::getCleanParam( 'batch_type', $row->get( 'type' ) ) );

						$process	=	true;
					}

					if ( $batch_subtype != '' ) {
						$row->set( 'subtype', cbactivityClass::getCleanParam( 'batch_subtype', $row->get( 'subtype' ) ) );

						$process	=	true;
					}

					if ( $batch_item != '' ) {
						$row->set( 'item', cbactivityClass::getCleanParam( 'batch_item', $row->get( 'item' ) ) );

						$process	=	true;
					}

					if ( $batch_title != '' ) {
						$row->set( 'title', cbactivityClass::getCleanParam( 'batch_title', $row->get( 'title' ) ) );

						$process	=	true;
					}

					if ( $batch_icon != '' ) {
						$row->set( 'icon', cbactivityClass::getCleanParam( 'batch_icon', $row->get( 'icon' ) ) );

						$process	=	true;
					}

					if ( $batch_class != '' ) {
						$row->set( 'class', cbactivityClass::getCleanParam( 'batch_class', $row->get( 'class' ) ) );

						$process	=	true;
					}

					if ( $process ) {
						if ( $row->getError() || ( ! $row->store() ) ) {
							cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::P( 'Activity failed to process! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
						}
					}
				}

				cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::T( 'Activity batch process successfully!' ), false, true );
			}

			cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::T( 'Nothing to process.' ), false, true, 'error' );
		}

		cbactivityClass::getPluginURL( array( 'activity' ), CBTxt::T( 'Activity not found.' ), false, true, 'error' );
	}

	private function showConfig( $user, $plugin, $message = null ) {
		global $_CB_framework;

		$templates								=	array();

		if ( is_dir( $plugin->absPath . '/templates' ) ) {
			foreach ( scandir( $plugin->absPath . '/templates' ) as $template ) {
				if ( preg_match( '!^\w+$!', $template ) ) {
					$templates[]				=	moscomprofilerHTML::makeOption( $template, $template );
				}
			}
		}

		$input									=	array();

		$input['general_template']				=	moscomprofilerHTML::selectList( $templates, 'general_template', null, 'value', 'text', $plugin->params->get( 'general_template', 'default' ), 1, false, false );
		$input['general_class']					=	'<input type="text" id="general_class" name="general_class" value="' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . '" class="inputbox" size="20" />';
		$input['general_exclude']				=	'<input type="text" id="general_exclude" name="general_exclude" value="' . htmlspecialchars( $plugin->params->get( 'general_exclude', null ) ) . '" class="inputbox" size="30" />';
		$input['general_delete']				=	moscomprofilerHTML::yesnoSelectList( 'general_delete', null, $plugin->params->get( 'general_delete', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$listDisplay							=	array();
		$listDisplay[]							=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Everyone' ) );
		$listDisplay[]							=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Connections Only' ) );
		$listDisplay[]							=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Self Only' ) );
		$listDisplay[]							=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Connections and Self' ) );

		$input['tab_display']					=	moscomprofilerHTML::selectList( $listDisplay, 'tab_display', null, 'value', 'text', $plugin->params->get( 'tab_display', 4 ), 1, false, false );

		$listAccess								=	array();
		$listAccess[]							=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'Custom Access' ) );
		$listAccess[]							=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( 'Everyone' ) );
		$listAccess[]							=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( 'Connections Only' ) );
		$listAccess[]							=	moscomprofilerHTML::makeOption( '-3', CBTxt::T( 'Self Only' ) );
		$listAccess[]							=	moscomprofilerHTML::makeOption( '-4', CBTxt::T( 'Connections and Self' ) );
		$listAccess[]							=	moscomprofilerHTML::makeOptGroup( CBTxt::T( 'CMS Access' ) );
		$listAccess								=	array_merge( $listAccess, $_CB_framework->acl->get_access_children_tree( true, ( checkJversion() >= 2 ? false : true ) ) );

		$input['tab_access']					=	moscomprofilerHTML::selectList( $listAccess, 'tab_access', null, 'value', 'text', $plugin->params->get( 'tab_access', -1 ), 1, false, false );
		$input['tab_update']					=	moscomprofilerHTML::yesnoSelectList( 'tab_update', null, $plugin->params->get( 'tab_update', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['tab_interval']					=	'<input type="text" id="tab_interval" name="tab_interval" value="' . (int) $plugin->params->get( 'tab_interval', 1 ) . '" class="inputbox" size="5" />';
		$input['tab_interval_limit']			=	'<input type="text" id="tab_interval_limit" name="tab_interval_limit" value="' . (int) $plugin->params->get( 'tab_interval_limit', 10 ) . '" class="inputbox" size="5" />';

		$listCutOff								=	array();
		$listCutOff[]							=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'No Limit' ) );
		$listCutOff[]							=	moscomprofilerHTML::makeOption( '2', CBTxt::T( '1 Day' ) );
		$listCutOff[]							=	moscomprofilerHTML::makeOption( '3', CBTxt::T( '1 Week (7 Days)' ) );
		$listCutOff[]							=	moscomprofilerHTML::makeOption( '4', CBTxt::T( '2 Weeks' ) );
		$listCutOff[]							=	moscomprofilerHTML::makeOption( '5', CBTxt::T( '1 Month (30 Days)' ) );
		$listCutOff[]							=	moscomprofilerHTML::makeOption( '6', CBTxt::T( '3 Months' ) );
		$listCutOff[]							=	moscomprofilerHTML::makeOption( '7', CBTxt::T( '6 Months' ) );
		$listCutOff[]							=	moscomprofilerHTML::makeOption( '8', CBTxt::T( '1 Year (365 Days)' ) );

		$input['tab_cut_off']					=	moscomprofilerHTML::selectList( $listCutOff, 'tab_cut_off', null, 'value', 'text', $plugin->params->get( 'tab_cut_off', 5 ), 1, false, false );
		$input['tab_hide_empty']				=	moscomprofilerHTML::yesnoSelectList( 'tab_hide_empty', null, $plugin->params->get( 'tab_hide_empty', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['tab_paging']					=	moscomprofilerHTML::yesnoSelectList( 'tab_paging', null, $plugin->params->get( 'tab_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['tab_paging_jquery']				=	moscomprofilerHTML::yesnoSelectList( 'tab_paging_jquery', null, $plugin->params->get( 'tab_paging_jquery', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['tab_paging_auto']				=	moscomprofilerHTML::yesnoSelectList( 'tab_paging_auto', null, $plugin->params->get( 'tab_paging_auto', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['tab_limit']						=	'<input type="text" id="tab_limit" name="tab_limit" value="' . (int) $plugin->params->get( 'tab_limit', 7 ) . '" class="inputbox" size="5" />';
		$input['recent_display']				=	moscomprofilerHTML::selectList( $listDisplay, 'recent_display', null, 'value', 'text', $plugin->params->get( 'recent_display', 1 ), 1, false, false );

		array_splice( $listAccess, 2, 3 );

		$input['recent_access']					=	moscomprofilerHTML::selectList( $listAccess, 'recent_access', null, 'value', 'text', $plugin->params->get( 'recent_access', -1 ), 1, false, false );
		$input['recent_update']					=	moscomprofilerHTML::yesnoSelectList( 'recent_update', null, $plugin->params->get( 'recent_update', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['recent_interval']				=	'<input type="text" id="recent_interval" name="recent_interval" value="' . (int) $plugin->params->get( 'recent_interval', 1 ) . '" class="inputbox" size="5" />';
		$input['recent_interval_limit']			=	'<input type="text" id="recent_interval_limit" name="recent_interval_limit" value="' . (int) $plugin->params->get( 'recent_interval_limit', 10 ) . '" class="inputbox" size="5" />';
		$input['recent_cut_off']				=	moscomprofilerHTML::selectList( $listCutOff, 'recent_cut_off', null, 'value', 'text', $plugin->params->get( 'recent_cut_off', 5 ), 1, false, false );
		$input['recent_paging']					=	moscomprofilerHTML::yesnoSelectList( 'recent_paging', null, $plugin->params->get( 'recent_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['recent_paging_jquery']			=	moscomprofilerHTML::yesnoSelectList( 'recent_paging_jquery', null, $plugin->params->get( 'recent_paging_jquery', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['recent_paging_auto']			=	moscomprofilerHTML::yesnoSelectList( 'recent_paging_auto', null, $plugin->params->get( 'recent_paging_auto', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['recent_limit']					=	'<input type="text" id="recent_limit" name="recent_limit" value="' . (int) $plugin->params->get( 'recent_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['date_format']					=	'<input type="text" id="date_format" name="date_format" value="' . htmlspecialchars( $plugin->params->get( 'date_format', 'l, F j, Y \a\t g:ia' ) ) . '" class="inputbox" size="30" />';
		$input['date_ago']						=	moscomprofilerHTML::yesnoSelectList( 'date_ago', null, $plugin->params->get( 'date_ago', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['date_jquery']					=	moscomprofilerHTML::yesnoSelectList( 'date_jquery', null, $plugin->params->get( 'date_jquery', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_title_length']			=	'<input type="text" id="activity_title_length" name="activity_title_length" value="' . (int) $plugin->params->get( 'activity_title_length', 0 ) . '" class="inputbox" size="10" />';
		$input['activity_desc_length']			=	'<input type="text" id="activity_desc_length" name="activity_desc_length" value="' . (int) $plugin->params->get( 'activity_desc_length', 0 ) . '" class="inputbox" size="10" />';
		$input['activity_img_thumbnails']		=	moscomprofilerHTML::yesnoSelectList( 'activity_img_thumbnails', null, $plugin->params->get( 'activity_img_thumbnails', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_registration']			=	moscomprofilerHTML::yesnoSelectList( 'activity_registration', null, $plugin->params->get( 'activity_registration', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_login']				=	moscomprofilerHTML::yesnoSelectList( 'activity_login', null, $plugin->params->get( 'activity_login', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_logout']				=	moscomprofilerHTML::yesnoSelectList( 'activity_logout', null, $plugin->params->get( 'activity_logout', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_profile']				=	moscomprofilerHTML::yesnoSelectList( 'activity_profile', null, $plugin->params->get( 'activity_profile', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_avatar']				=	moscomprofilerHTML::yesnoSelectList( 'activity_avatar', null, $plugin->params->get( 'activity_avatar', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_connections']			=	moscomprofilerHTML::yesnoSelectList( 'activity_connections', null, $plugin->params->get( 'activity_connections', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_cat_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_cat_create', null, $plugin->params->get( 'activity_gj_cat_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_grp_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_grp_create', null, $plugin->params->get( 'activity_gj_grp_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_grp_join']			=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_grp_join', null, $plugin->params->get( 'activity_gj_grp_join', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_events_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_events_create', null, $plugin->params->get( 'activity_gj_events_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_events_attend']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_events_attend', null, $plugin->params->get( 'activity_gj_events_attend', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_files_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_files_create', null, $plugin->params->get( 'activity_gj_files_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_photos_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_photos_create', null, $plugin->params->get( 'activity_gj_photos_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_videos_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_videos_create', null, $plugin->params->get( 'activity_gj_videos_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_wall_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_wall_create', null, $plugin->params->get( 'activity_gj_wall_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_gj_wall_reply']		=	moscomprofilerHTML::yesnoSelectList( 'activity_gj_wall_reply', null, $plugin->params->get( 'activity_gj_wall_reply', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_kunena_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_kunena_create', null, $plugin->params->get( 'activity_kunena_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_kunena_reply']			=	moscomprofilerHTML::yesnoSelectList( 'activity_kunena_reply', null, $plugin->params->get( 'activity_kunena_reply', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_pb_guest_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_pb_guest_create', null, $plugin->params->get( 'activity_pb_guest_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_pb_wall_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_pb_wall_create', null, $plugin->params->get( 'activity_pb_wall_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_pb_blog_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_pb_blog_create', null, $plugin->params->get( 'activity_pb_blog_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_pg_create']			=	moscomprofilerHTML::yesnoSelectList( 'activity_pg_create', null, $plugin->params->get( 'activity_pg_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['activity_cbblogs_create']		=	moscomprofilerHTML::yesnoSelectList( 'activity_cbblogs_create', null, $plugin->params->get( 'activity_cbblogs_create', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		cbactivityClass::displayMessage( $message );

		HTML_cbactivityAdmin::showConfig( $input, $user, $plugin );
	}

	private function saveConfig( $config, $user, $plugin ) {
		global $_CB_database;

		$row			=	new moscomprofilerPlugin( $_CB_database );

		if ( $plugin->id ) {
			$row->load( (int) $plugin->id );
		}

		$params			=	cbactivityClass::parseParams( $config, 'raw' );

		$row->params	=	trim( $params->toIniString() );

		if ( $row->getError() || ( ! $row->store() ) ) {
			$this->showConfig( $user, $plugin, CBTxt::P( 'Config failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
		}

		cbactivityClass::getPluginURL( array( 'config' ), CBTxt::T( 'Config saved successfully!' ), false, true );
	}
}
?>