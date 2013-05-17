<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class cbinvitesAdmin extends cbPluginHandler {

	public function editPluginView( $row, $option, $task, $uid, $action, $element, $mode, $pluginParams ) {
		global $_CB_framework, $_CB_database, $_CB_Backend_Menu, $_CB_Backend_task, $_PLUGIN_Backend_Title, $_PLUGINS;

		if ( ! $_CB_framework->check_acl( 'canManageUsers', $_CB_framework->myUserType() ) ) {
			cbRedirect( $_CB_framework->backendUrl( 'index.php' ), _UE_NOT_AUTHORIZED, 'error' );
		}

		outputCbJs( 2 );
		outputCbTemplate( 2 );

		$plugin					=	cbinvitesClass::getPlugin();

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
			case 'invites':
				switch ( $function ) {
					case 'new':
						$this->showInviteEdit( null, $user, $plugin );
						break;
					case 'edit':
						$this->showInviteEdit( $id[0], $user, $plugin );
						break;
					case 'save':
					case 'apply':
						cbSpoofCheck( 'plugin' );
						$this->saveInviteEdit( $id[0], $save_mode, $user, $plugin );
						break;
					case 'send':
						$this->sendInvite( $id, $user, $plugin );
						break;
					case 'batch':
						$this->batchInvite( $id, $user, $plugin );
						break;
					case 'delete':
						cbSpoofCheck( 'plugin' );
						$this->deleteInvite( $id, $user, $plugin);
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
		$return					=	'<div style="margin:0px;border-width:0px;padding:0px;float:left;width:100%;text-align:left;">'
								.		'<div id="cbAdminMainWrapper" style="margin:0px;border-width:0px;padding:0px;float:none;width:auto;" class="cbinvitesAdmin">'
								.			'<div style="float:right;" class="cbinvitesAdminToolbar">'
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

	private function showPlugin( $user, $plugin ) {
		$menu			=	new stdClass();

		$menu->invites	=	'<a href="' . cbinvitesClass::getPluginURL( array( 'invites' ) ) . '">'
						.		'<div><img src="' . $plugin->livePath . '/images/icon-128-invites.png" /></div>'
						.		'<div>' . CBTxt::T( 'Invites' ) . '</div>'
						.	'</a>';

		$menu->config	=	'<a href="' . cbinvitesClass::getPluginURL( array( 'config' ) ) . '">'
						.		'<div><img src="' . $plugin->livePath . '/images/icon-128-config.png" /></div>'
						.		'<div>' . CBTxt::T( 'Config' ) . '</div>'
						.	'</a>';

		$xml			=	new CBSimpleXMLElement( trim( file_get_contents( $plugin->xml ) ) );

		HTML_cbinvitesAdmin::showPlugin( $menu, $xml, $user, $plugin );
	}

	private function showInvites( $user, $plugin ) {
		$paging				=	new cbinvitesPaging( 'invites' );

		$limit					=	$paging->getlimit();
		$limitstart				=	$paging->getLimistart();
		$filter_from			=	$paging->getFilter( 'from' );
		$filter_to				=	$paging->getFilter( 'to' );
		$filter_state			=	$paging->getFilter( 'state' );
		$filter_id				=	$paging->getFilter( 'id' );
		$where					=	array();

		if ( isset( $filter_from ) && ( $filter_from != '' ) ) {
			$where[]			=	array( 'c.id', '=', $filter_from, array( 'c.username', 'CONTAINS', $filter_from ), array( 'c.name', 'CONTAINS', $filter_from ) );
		}

		if ( isset( $filter_to ) && ( $filter_to != '' ) ) {
			$where[]			=	array( 'to', 'CONTAINS', $filter_to, array( 'b.id', '=', $filter_to ), array( 'b.username', 'CONTAINS', $filter_to ), array( 'b.name', 'CONTAINS', $filter_to ) );
		}

		if ( isset( $filter_state ) && ( $filter_state != '' ) ) {
			if ( $filter_state == 0 ) {
				$where[]		=	array( 'accepted', 'IN', array( null, '', '0000-00-00 00:00:00', '0000-00-00' ) );
			} else {
				$where[]		=	array( 'accepted', '!IN', array( null, '', '0000-00-00 00:00:00', '0000-00-00' ) );
			}
		}

		if ( isset( $filter_id ) && ( $filter_id != '' ) ) {
			$where[]			=	array( 'id', '=', $filter_id );
		}

		$searching				=	( count( $where ) ? true : false );

		$total					=	count( cbinvitesData::getInvites( null, $where ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	array_values( cbinvitesData::getInvites( null, $where, null, array( $pageNav->limitstart, $pageNav->limit ) ) );

		$input					=	array();

		$list_state				=	array();
		$list_state[]			=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select State -' ) );
		$list_state[]			=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Accepted' ) );
		$list_state[]			=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Pending' ) );
		$input['state']			=	$paging->getInputSelect( 'adminForm', 'state', $list_state, $filter_state );

		$input['search']		=	$paging->getInputText( 'adminForm', 'from', $filter_from, '30' );
		$input['email']			=	$paging->getInputText( 'adminForm', 'to', $filter_to, '30' );
		$input['id']			=	$paging->getInputText( 'adminForm', 'id', $filter_id, '6' );
		$input['batch_user']	=	'<input type="text" id="batch_user" name="batch_user" size="6" />';

		$pageNav->searching		=	$searching;

		HTML_cbinvitesAdmin::showInvites( $rows, $pageNav, $input, $user, $plugin );
	}

	private function showInviteEdit( $id, $user, $plugin, $message = null ) {
		$row				=	cbinvitesData::getInvites( null, array( 'id', '=', $id ), null, null, false );

		$input				=	array();

		$input['to']		=	'<input type="text" id="to" name="to" value="' . htmlspecialchars( cbinvitesClass::getCleanParam( true, 'to', $row->get( 'to' ) ) ) . '" class="inputbox" size="35" />';
		$input['subject']	=	'<input type="text" id="subject" name="subject" value="' . htmlspecialchars( cbinvitesClass::getCleanParam( true, 'subject', $row->get( 'subject' ) ) ) . '" class="inputbox" size="25" />';
		$input['body']		=	'<textarea id="body" name="body" class="inputbox" cols="35" rows="4">' . htmlspecialchars( cbinvitesClass::getHTMLCleanParam( true, 'body', $row->get( 'body' ) ) ) . '</textarea>';
		$input['user_id']	=	'<input type="text" id="user_id" name="user_id" value="' . (int) cbinvitesClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) . '" class="inputbox" size="6" />';

		cbinvitesClass::displayMessage( $message );

		HTML_cbinvitesAdmin::showInviteEdit( $row, $input, $user, $plugin );
	}

	private function saveInviteEdit( $id, $task, $user, $plugin ) {
		$row					=	cbinvitesData::getInvites( null, array( 'id', '=', $id ), null, null, false );
		$to_array				=	explode( ',', cbinvitesClass::getCleanParam( true, 'to' ) );
		$sent					=	false;

		if ( ! empty( $to_array ) ) {
			foreach ( $to_array as $k => $to ) {
				if ( $k != 0 ) {
					$row->set( 'id', null );
					$row->set( 'code', null );
				}

				$org_to			=	$row->get( 'to' );

				$row->set( 'to', $to );
				$row->set( 'subject', cbinvitesClass::getCleanParam( true, 'subject', $row->get( 'subject' ) ) );
				$row->set( 'body', cbinvitesClass::getHTMLCleanParam( true, 'body', $row->get( 'body' ) ) );
				$row->set( 'user_id', (int) cbinvitesClass::getCleanParam( true, 'user_id', $row->get( 'user_id', $user->id ) ) );

				if ( ! $row->get( 'code' ) ) {
					$row->set( 'code', md5( uniqid() ) );
				}

				if ( ! $row->get( 'to' ) ) {
					$row->set( '_error', CBTxt::T( 'To address not specified.' ) );
				} elseif ( ! cbIsValidEmail( $row->get( 'to' ) ) ) {
					$row->set( '_error', CBTxt::P( 'To address not valid: [to_address]', array( '[to_address]' => $row->get( 'to' ) ) ) );
				} elseif ( $row->getUser()->id == $row->get( 'user_id' ) ) {
					$row->set( '_error', CBTxt::T( 'You can not invite your self.' ) );
				} elseif ( $row->getUser()->id && ( $row->get( 'to' ) != $org_to ) ) {
					$row->set( '_error', CBTxt::T( 'To address is already a user.' ) );
				}

				$new			=	( $row->get( 'id' ) ? false : true );

				if ( $row->getError() || ( ! $row->store() ) ) {
					$this->showInviteEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Invite failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
				}

				if ( ( $new || ( ! $row->isSent() ) ) && ( ! $row->getUser()->id ) ) {
					if ( ! $row->send() ) {
						$this->showInviteEdit( $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Invite failed to send! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
					} else {
						$sent	=	true;
					}
				}
			}

			cbinvitesClass::getPluginURL( ( $task == 'apply' ? array( 'invites', 'edit', $row->id ) : array( 'invites' ) ), ( $sent ? CBTxt::T( 'Invite sent successfully!' ) : CBTxt::T( 'Invite saved successfully!' ) ), false, true );
		} else {
			$this->showInviteEdit( $row->get( 'id' ), $user, $plugin, CBTxt::T( 'To address not specified.' ) ); return;
		}
	}

	private function sendInvite( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbinvitesData::getInvites( null, array( 'id', '=', $id ), null, null, false );

				if ( ! $row->send() ) {
					cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::P( 'Invite failed to send! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Invite sent successfully!' ), false, true );
		}

		cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Invite not found.' ), false, true, 'error' );
	}

	private function batchInvite( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			$owner					=	cbGetParam( $_REQUEST, 'batch_user', null );

			if ( $owner != '' ) {
				foreach ( $ids as $id ) {
					$row			=	cbinvitesData::getInvites( null, array( 'id', '=', $id ), null, null, false );
					$process		=	false;

					if ( $owner != '' )	{
						$row->set( 'user_id', (int) $owner );

						$process	=	true;
					}

					if ( $process ) {
						if ( ! $row->store() ) {
							cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::P( 'Invite failed to process! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
						}
					}
				}

				cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Invite batch process successfully!' ), false, true );
			}

			cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Nothing to process.' ), false, true, 'error' );
		}

		cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Invite not found.' ), false, true, 'error' );
	}

	private function deleteInvite( $ids, $user, $plugin ) {
		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {
				$row	=	cbinvitesData::getInvites( null, array( 'id', '=', $id ), null, null, false );

				if ( ! $row->delete() ) {
					cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::P( 'Invite failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
				}
			}

			cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Invite deleted successfully!' ), false, true );
		}

		cbinvitesClass::getPluginURL( array( 'invites' ), CBTxt::T( 'Invite not found.' ), false, true, 'error' );
	}

	private function showConfig( $user, $plugin, $message = null ) {
		$templates						=	array();

		if ( is_dir( $plugin->absPath . '/templates' ) ) {
			foreach ( scandir( $plugin->absPath . '/templates' ) as $template ) {
				if ( preg_match( '!^\w+$!', $template ) ) {
					$templates[]		=	moscomprofilerHTML::makeOption( $template, $template );
				}
			}
		}

		$input							=	array();

		$invite_header					=	$plugin->params->get( 'invite_header', '<p>You have been invited by [username] to join [sitename]!</p><br />' );
		$invite_footer					=	$plugin->params->get( 'invite_footer', '<br /><p>Invite Code - [code]<br />[sitename] - [site]<br />Registration - [register]<br />[username] - [profile]</p>' );

		$input['general_template']		=	moscomprofilerHTML::selectList( $templates, 'general_template', null, 'value', 'text', $plugin->params->get( 'general_template', 'default' ), 1, false, false );
		$input['general_class']			=	'<input type="text" id="general_class" name="general_class" value="' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . '" class="inputbox" size="20" />';

		$list_tooltips					=	array();
		$list_tooltips[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Icon' ) );
		$list_tooltips[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Text' ) );
		$list_tooltips[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Disabled' ) );
		$input['general_tooltips']		=	moscomprofilerHTML::selectList( $list_tooltips, 'general_tooltips', null, 'value', 'text', $plugin->params->get( 'general_tooltips', 1 ), 1, false, false );

		$input['tab_paging']			=	moscomprofilerHTML::yesnoSelectList( 'tab_paging', null, $plugin->params->get( 'tab_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['tab_limit']				=	'<input type="text" id="tab_limit" name="tab_limit" value="' . (int) $plugin->params->get( 'tab_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['tab_search']			=	moscomprofilerHTML::yesnoSelectList( 'tab_search', null, $plugin->params->get( 'tab_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$input['invite_limit']			=	'<input type="text" id="invite_limit" name="invite_limit" value="' . htmlspecialchars( $plugin->params->get( 'invite_limit', null ) ) . '" class="inputbox" size="5" />';
		$input['invite_resend']			=	'<input type="text" id="invite_resend" name="invite_resend" value="' . htmlspecialchars( $plugin->params->get( 'invite_resend', 7 ) ) . '" class="inputbox" size="5" />';
		$input['invite_multiple']		=	moscomprofilerHTML::yesnoSelectList( 'invite_multiple', null, $plugin->params->get( 'invite_multiple', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['invite_duplicate']		=	moscomprofilerHTML::yesnoSelectList( 'invite_duplicate', null, $plugin->params->get( 'invite_duplicate', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

		$list_invite_connection			=	array();
		$list_invite_connection[]		=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Disabled' ) );
		$list_invite_connection[]		=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Auto Connection' ) );
		$list_invite_connection[]		=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Pending Connection' ) );
		$input['invite_connection']		=	moscomprofilerHTML::selectList( $list_invite_connection, 'invite_connection', null, 'value', 'text', $plugin->params->get( 'invite_connection', 2 ), 1, false, false );

		$input['invite_captcha']		=	moscomprofilerHTML::yesnoSelectList( 'invite_captcha', null, $plugin->params->get( 'invite_captcha', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['invite_cc']				=	'<input type="text" id="invite_cc" name="invite_cc" value="' . htmlspecialchars( $plugin->params->get( 'invite_cc', null ) ) . '" class="inputbox" size="40" />';
		$input['invite_bcc']			=	'<input type="text" id="invite_bcc" name="invite_bcc" value="' . htmlspecialchars( $plugin->params->get( 'invite_bcc', null ) ) . '" class="inputbox" size="40" />';
		$input['invite_prefix']			=	'<input type="text" id="invite_prefix" name="invite_prefix" value="' . htmlspecialchars( $plugin->params->get( 'invite_prefix', '[sitename] - ' ) ) . '" class="inputbox" size="20" />';
		$input['invite_header']			=	'<textarea id="invite_header" name="invite_header" class="inputbox" cols="40" rows="5">' . htmlspecialchars( $invite_header ) . '</textarea>';
		$input['invite_footer']			=	'<textarea id="invite_footer" name="invite_footer" class="inputbox" cols="40" rows="5">' . htmlspecialchars( $invite_footer ) . '</textarea>';
		$input['invite_attachments']	=	'<input type="text" id="invite_attachments" name="invite_attachments" value="' . htmlspecialchars( $plugin->params->get( 'invite_attachments', null ) ) . '" class="inputbox" size="40" />';
		$input['invite_mode']			=	moscomprofilerHTML::yesnoSelectList( 'invite_mode', null, $plugin->params->get( 'invite_mode', 1 ), CBTxt::T( 'HTML' ), CBTxt::T( 'Text' ) );

		cbinvitesClass::displayMessage( $message );

		HTML_cbinvitesAdmin::showConfig( $input, $user, $plugin );
	}

	private function saveConfig( $config, $user, $plugin ) {
		global $_CB_database;

		$row			=	new moscomprofilerPlugin( $_CB_database );

		if ( $plugin->id ) {
			$row->load( $plugin->id );
		}

		$params			=	cbinvitesClass::parseParams( $config, 'raw' );

		$row->params	=	trim( $params->toIniString() );

		if ( $row->getError() || ( ! $row->store() ) ) {
			$this->showConfig( $user, $plugin, CBTxt::P( 'Config failed to save! Error: [error]', array( '[error]' => $row->getError() ) ) ); return;
		}

		cbinvitesClass::getPluginURL( array( 'config' ), CBTxt::T( 'Config saved successfully!' ), false, true );
	}
}
?>