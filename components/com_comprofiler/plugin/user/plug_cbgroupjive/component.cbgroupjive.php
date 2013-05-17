<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class CBplug_cbgroupjive extends cbPluginHandler {

	/**
	 * render frontend plugin view
	 *
	 * @param object $tab
	 * @param moscomprofilerUser $user
	 * @param int $ui
	 * @param array $postdata
	 */
	public function getCBpluginComponent( $tab, $user, $ui, $postdata ) {
		global $_CB_framework, $_PLUGINS;

		outputCbJs( 1 );
		outputCbTemplate( 1 );

		$plugin			=	cbgjClass::getPlugin();
		$action			=	cbGetParam( $_REQUEST, 'action', null );
		$function		=	cbGetParam( $_REQUEST, 'func', null );
		$category		=	cbGetParam( $_REQUEST, 'cat', null );
		$group			=	cbGetParam( $_REQUEST, 'grp', null );
		$id				=	cbGetParam( $_REQUEST, 'id', null );
		$user			=&	CBuser::getUserDataInstance( $_CB_framework->myId() );

		ob_start();
		switch ( $action ) {
			case 'panel':
				$this->getPanel( $function, $user, $plugin );
				break;
			case 'categories':
				switch ( $function ) {
					case 'publish':
						$this->stateCategory( 1, $category, $user, $plugin );
						break;
					case 'unpublish':
						$this->stateCategory( 0, $category, $user, $plugin );
						break;
					case 'delete':
						$this->deleteCategory( $category, $user, $plugin );
						break;
					case 'new':
						$this->showCategoryEdit( $category, null, $user, $plugin );
						break;
					case 'edit':
						$this->showCategoryEdit( null, $category, $user, $plugin );
						break;
					case 'save':
						cbSpoofCheck( 'plugin' );
						$this->saveCategoryEdit( $category, $user, $plugin );
						break;
					case 'message':
						$this->showCategoryMessage( $category, $user, $plugin );
						break;
					case 'send':
						cbSpoofCheck( 'plugin' );
						$this->sendCategoryMessage( $category, $user, $plugin );
						break;
					case 'all':
						$this->showCategoryAll( false, $user, $plugin );
						break;
					case 'allmy':
						$this->showCategoryAll( true, $user, $plugin );
						break;
					case 'approval':
						$this->showCategoryApproval( $user, $plugin );
						break;
					case 'show':
					default:
						$this->showCategory( $category, $user, $plugin );
						break;
				}
				break;
			case 'groups':
				switch ( $function ) {
					case 'join':
						$this->joinGroup( $category, $group, $id, $user, $plugin );
						break;
					case 'leave':
						$this->leaveGroup( $category, $group, $user, $plugin );
						break;
					case 'publish':
						$this->stateGroup( 1, $category, $group, $user, $plugin );
						break;
					case 'unpublish':
						$this->stateGroup( 0, $category, $group, $user, $plugin );
						break;
					case 'delete':
						$this->deleteGroup( $category, $group, $user, $plugin );
						break;
					case 'new':
						$this->showGroupEdit( $category, $group, null, $user, $plugin );
						break;
					case 'edit':
						$this->showGroupEdit( $category, null, $group, $user, $plugin );
						break;
					case 'save':
						cbSpoofCheck( 'plugin' );
						$this->saveGroupEdit( $category, $group, $user, $plugin );
						break;
					case 'message':
						$this->showGroupMessage( $category, $group, $user, $plugin );
						break;
					case 'send':
						cbSpoofCheck( 'plugin' );
						$this->sendGroupMessage( $category, $group, $user, $plugin );
						break;
					case 'all':
						$this->showGroupAll( false, $user, $plugin );
						break;
					case 'allmy':
						$this->showGroupAll( true, $user, $plugin );
						break;
					case 'approval':
						$this->showGroupApproval( $user, $plugin );
						break;
					case 'show':
					default:
						$this->showGroup( $category, $group, $user, $plugin );
						break;
				}
				break;
			case 'users':
				switch ( $function ) {
					case 'ban':
						$this->statusUser( -1, $category, $group, $id, $user, $plugin );
						break;
					case 'active':
						$this->statusUser( 1, $category, $group, $id, $user, $plugin );
						break;
					case 'inactive':
						$this->statusUser( 0, $category, $group, $id, $user, $plugin );
						break;
					case 'mod':
						$this->statusUser( 2, $category, $group, $id, $user, $plugin );
						break;
					case 'admin':
						$this->statusUser( 3, $category, $group, $id, $user, $plugin );
						break;
					case 'delete':
						$this->deleteUser( $category, $group, $id, $user, $plugin );
						break;
					case 'approval':
						$this->showUsersApproval( $user, $plugin );
						break;
					default:
						$this->showGroup( $category, $group, $user, $plugin );
						break;
				}
				break;
			case 'invites':
				switch ( $function ) {
					case 'list':
						$this->listInvites( $category, $group, $id, $user, $plugin );
						break;
					case 'send':
						$this->sendInvite( $category, $group, $id, $user, $plugin );
						break;
					case 'delete':
						$this->deleteInvite( $category, $group, $id, $user, $plugin );
						break;
					default:
						$this->showGroup( $category, $group, $user, $plugin );
						break;
				}
				break;
			case 'notifications':
				switch ( $function ) {
					case 'save':
						cbgjClass::saveNotifications( $category, $group, $user, $plugin, false );
						break;
					default:
						$this->showNotifications( $category, $group, $user, $plugin );
						break;
				}
				break;
			case 'plugin':
				$_PLUGINS->trigger( 'gj_onPluginFE', array( array( $function, $category, $group, $id ), $user, $plugin ) );
				break;
			case 'overview':
			default:
				switch ( $function ) {
					case 'message':
						$this->showOverviewMessage( $user, $plugin );
						break;
					case 'send':
						cbSpoofCheck( 'plugin' );
						$this->sendOverviewMessage( $user, $plugin );
						break;
					default:
						$this->showOverview( $user, $plugin );
						break;
				}
				break;
		}
		$html			=	ob_get_contents();
		ob_end_clean();

		$return			=	'<div id="cbGj" class="cbGroupJive' . htmlspecialchars( $plugin->params->get( 'general_class', null ) ) . ' cb_template_' . selectTemplate( 'dir' ) . '">'
						.		'<div id="cbGjInner" class="cbGroupJiveInner">'
						.			$html
						.		'</div>'
						.	'</div>';

		echo $return;
	}

	/**
	 * prepare frontend panel render
	 *
	 * @param string $function
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	public function getPanel( $function, $user, $plugin ) {
		$authorized		=	cbgjClass::getAuthorization( null, null, $user );

		if ( cbgjClass::hasAccess( 'usr_panel', $authorized ) ) {
			cbgjClass::getTemplate( array( 'panel', 'panel_panes', 'panel_main' ) );

			HTML_groupjivePanel::showPanel( $function, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend categories render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param mixed $message
	 */
	public function showOverview( $user, $plugin, $message = null ) {
		cbgjClass::getTemplate( array( 'overview', 'overview_panes', 'overview_main' ) );

		$paging					=	new cbgjPaging( 'overview' );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'overview_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		$where[]				=	array( 'parent', '=', 0 );

		switch( $plugin->params->get( 'overview_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'group_count_asc';
				break;
			case 8:
				$orderBy		=	'group_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy, ( $plugin->params->get( 'overview_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [categories]...', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		cbgjClass::displayMessage( $message );

		HTML_groupjiveOverview::showOverview( $rows, $pageNav, $user, $plugin );
	}

	/**
	 * prepare frontend overview message render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param mixed $message
	 */
	public function showOverviewMessage( $user, $plugin, $message = null ) {
		global $_CB_framework;

		$authorized				=	cbgjClass::getAuthorization( null, null, $user );

		if ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$editor				=	$plugin->params->get( 'overview_message_editor', 1 );

			cbgjClass::getTemplate( 'overview_message' );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation();
			}

			$input				=	array();

			$input['subject']	=	'<input type="text" id="subject" name="subject" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'subject' ) ) . '" class="input-large required" size="40" />';

			if ( $editor >= 2 ) {
				$body			=	cbgjClass::getHTMLCleanParam( true, 'body' );
			} else {
				$body			=	cbgjClass::getCleanParam( true, 'body' );
			}

			if ( $editor == 3 ) {
				$input['body']	=	$_CB_framework->displayCmsEditor( 'body', $body, 400, 200, 40, 6 );
			} else {
				$input['body']	=	'<textarea id="body" name="body" class="input-xlarge required" cols="40" rows="6">' . htmlspecialchars( $body ) . '</textarea>';
			}

			cbgjClass::displayMessage( $message );

			HTML_groupjiveOverviewMessage::showOverviewMessage( $input, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * send overview message
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function sendOverviewMessage( $user, $plugin ) {
		global $_PLUGINS;

		$authorized				=	cbgjClass::getAuthorization( null, null, $user );

		if ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$subject			=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'subject' ) );

			if ( $plugin->params->get( 'overview_message_editor', 1 ) >= 2 ) {
				$body			=	cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'body' ) );
			} else {
				$body			=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'body' ) );
			}

			if ( $subject == '' ) {
				$error			=	CBTxt::T( 'Subject not specified!' );
			} elseif ( $body == '' ) {
				$error			=	CBTxt::T( 'Body not specified!' );
			}

			$users				=	array();
			$categories			=	cbgjData::getCategories();

			if ( $categories ) foreach ( $categories as $category ) {
				if ( ! in_array( $category->get( 'user_id' ), $users ) ) {
					$users[]	=	$category->get( 'user_id' );
				}
			}

			$_PLUGINS->trigger( 'gj_onBeforeMessageOverview', array( $subject, $body, $users, $user, $plugin ) );

			if ( isset( $error ) ) {
				$this->showOverviewMessage( $user, $plugin, CBTxt::P( '[categories] message failed to save! Error: [error]', array( '[categories]' => cbgjClass::getOverride( 'category', true ), '[error]' => $error ) ) );
				return;
			}

			if ( $users ) {
				$msgSubject		=	CBTxt::P( '[site_name] - [msg_subject]', array( '[msg_subject]' => $subject ) );
				$msgBody		=	CBTxt::P( 'Hello [username], the following is a message from [site].<br /><br />[msg_body]', array( '[msg_body]' => $body ) );

				foreach ( $users as $user_id ) {
					cbgjClass::getNotification( $user_id, $user->id, $msgSubject, $msgBody, 2 );
				}
			} else {
				cbgjClass::getPluginURL( array( 'overview' ), CBTxt::P( '[categories] message failed to send! Error: no [users] to message', array( '[categories]' => cbgjClass::getOverride( 'category', true ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ), false, true, 'error' );
			}

			$_PLUGINS->trigger( 'gj_onAfterMessageOverview', array( $subject, $body, $users, $user, $plugin ) );

			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::P( '[categories] message sent successfully!', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend categories approval render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	public function showCategoryApproval( $user, $plugin ) {
		if ( $plugin->params->get( 'category_approve', 0 ) ) {
			cbgjClass::getTemplate( 'category_approval' );

			$paging					=	new cbgjPaging( 'category_approval' );

			$limit					=	$paging->getlimit( (int) $plugin->params->get( 'category_approval_limit', 15 ) );
			$limitstart				=	$paging->getLimistart();
			$search					=	$paging->getFilter( 'search' );
			$where					=	array();

			if ( isset( $search ) && ( $search != '' ) ) {
				$where[]			=	array( 'name', 'CONTAINS', $search );
			}

			$searching				=	( count( $where ) ? true : false );

			$where[]				=	array( 'published', '=', -1 );

			switch( $plugin->params->get( 'category_approval_orderby', 1 ) ) {
				case 2:
					$orderBy		=	array( 'ordering', 'DESC' );
					break;
				case 3:
					$orderBy		=	array( 'date', 'ASC' );
					break;
				case 4:
					$orderBy		=	array( 'date', 'DESC' );
					break;
				case 5:
					$orderBy		=	array( 'name', 'ASC' );
					break;
				case 6:
					$orderBy		=	array( 'name', 'DESC' );
					break;
				case 7:
					$orderBy		=	'group_count_asc';
					break;
				case 8:
					$orderBy		=	'group_count_desc';
					break;
				case 9:
					$orderBy		=	'nested_count_asc';
					break;
				case 10:
					$orderBy		=	'nested_count_desc';
					break;
				default:
					$orderBy		=	null;
					break;
			}

			$total					=	count( cbgjData::getCategories( array( array( 'cat_can_publish' ), $user ), $where, $orderBy ) );

			if ( $total <= $limitstart ) {
				$limitstart			=	0;
			}

			$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

			$rows					=	cbgjData::getCategories( array( array( 'cat_can_publish' ), $user ), $where, $orderBy, ( $plugin->params->get( 'category_approval_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

			$pageNav->search		=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [categories]...', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), $search );
			$pageNav->searching		=	$searching;
			$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
			$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

			HTML_groupjiveCategoryApproval::showCategoryApproval( $rows, $pageNav, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend all categories render
	 *
	 * @param boolean $self
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	public function showCategoryAll( $self, $user, $plugin ) {
		cbgjClass::getTemplate( 'category_all' );

		$paging					=	new cbgjPaging( ( $self ? 'category_allmy' : 'category_all' ) );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'category_all_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		if ( $self ) {
			$where[]			=	array( 'user_id', '=', $user->id );
		}

		switch( $plugin->params->get( 'category_all_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'group_count_asc';
				break;
			case 8:
				$orderBy		=	'group_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy, ( $plugin->params->get( 'category_all_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [categories]...', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		HTML_groupjiveCategoryAll::showCategoryAll( $self, $rows, $pageNav, $user, $plugin );
	}

	/**
	 * prepare frontend category render
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param mixed $message
	 */
	public function showCategory( $id, $user, $plugin, $message = null ) {
		$row	=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( $row->get( 'id' ) ) {
			cbgjClass::getTemplate( array( 'category', 'category_panes', 'category_main' ) );

			cbgjClass::displayMessage( $message );

			HTML_groupjiveCategory::showCategory( $row, $user, $plugin );
		} else {
			if ( $user->id ) {
				cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
			} else {
				cbgjClass::getCBURL( 'login', CBTxt::P( 'Login or Register to view this [category].', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), false, true, 'error', true );
			}
		}
	}

	/**
	 * set category publish state status
	 *
	 * @param int $state
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function stateCategory( $state, $id, $user, $plugin ) {
		$row						=	cbgjData::getCategories( array( array( 'cat_can_publish' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( $row->get( 'id' ) ) {
			$currentState			=	$row->get( 'published' );

			if ( ! $row->storeState( $state ) ) {
				cbgjClass::getPluginURL( array( 'categories', 'show', (int) $row->get( 'id' ) ), CBTxt::P( '[category] state failed to saved! Error: [error]', array( '[category]' => cbgjClass::getOverride( 'category' ), '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $state && ( $currentState == -1 ) ) {
				if ( ! $row->get( 'parent' ) ) {
					$notifications	=	cbgjData::getNotifications( array( array( 'gen_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'general' ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'general_categorynew=1' ) ) );
				} else {
					$notifications	=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_nestednew=1' ) ) );
				}

				if ( $notifications ) {
					$subject		=	CBTxt::T( '[overview_name] - [category_override] Created!' );
					$message		=	CBTxt::T( '[user] created [category] [category_override]!' );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $row );
					}
				}

				if ( $user->id != $row->get( 'user_id' ) ) {
					$subject		=	CBTxt::T( '[category_name] - Create Request Accepted!' );
					$message		=	CBTxt::T( 'Your request to create [category] has been accepted!' );

					cbgjClass::getNotification( $row->get( 'user_id' ), $user->id, $subject, $message, 1, $row );
				}
			}

			cbgjClass::getPluginURL( array( 'categories', 'show', (int) $row->get( 'id' ) ), CBTxt::P( '[category] state saved successfully!', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * delete category
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteCategory( $id, $user, $plugin ) {
		$row					=	cbgjData::getCategories( array( array( 'mod_lvl1' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( $row->get( 'id' ) ) {
			if ( ! $row->get( 'parent' ) ) {
				$notifications	=	cbgjData::getNotifications( array( array( 'gen_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'general' ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'general_categorydelete=1' ) ) );
			} else {
				$notifications	=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_nesteddelete=1' ) ) );
			}

			if ( ! $row->deleteAll() ) {
				cbgjClass::getPluginURL( array( 'categories', 'show', (int) $row->get( 'id' ) ), CBTxt::P( '[category] failed to delete! Error: [error]', array( '[category]' => cbgjClass::getOverride( 'category' ), '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $notifications ) {
				$subject		=	CBTxt::T( '[overview_name] - [category_override] Deleted!' );
				$message		=	CBTxt::T( '[user] deleted [category_name] [category_override]!' );

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $row );
				}
			}

			cbgjClass::getPluginURL( ( $row->get( 'parent' ) ? array( 'categories', 'show', (int) $row->get( 'parent' ) ) : array( 'overview' ) ), CBTxt::P( '[category] deleted successfully!', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend category edit render
	 *
	 * @param int $parent
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param mixed $message
	 */
	public function showCategoryEdit( $parent, $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$row							=	cbgjData::getCategories( array( array( 'mod_lvl1' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( $parent && ( ! $row->get( 'parent' ) ) ) {
			$row->set( 'parent', (int) $parent );
		}

		$authorized						=	cbgjClass::getAuthorization( $row, null, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || ( ( ! $row->get( 'id' ) ) && ( ( ( ! $row->get( 'parent' ) ) && cbgjClass::hasAccess( 'cat_create', $authorized ) ) || ( $row->get( 'parent' ) && $row->getParentAccess( array( 'cat_nested_create', $user ) ) ) ) ) ) {
			$categoryEditor				=	$plugin->params->get( 'category_editor', 1 );
			$categoryLimit				=	$plugin->params->get( 'category_limit', 0 );
			$categoryInputLimit			=	$plugin->params->get( 'category_desc_inputlimit', 0 );

			if ( ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) && ( ( ! $row->get( 'id' ) ) && ( $categoryLimit > 0 ) ) ) {
				if ( count( cbgjData::getCategories( null, array( 'user_id', '=', $user->id ) ) ) >= $categoryLimit ) {
					cbgjClass::getPluginURL( ( $row->get( 'parent' ) ? array( 'categories', 'show', (int) $row->get( 'parent' ) ) : array( 'overview' ) ), CBTxt::P( '[category] limit reached!', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), false, true, 'error' );
				}
			}

			if ( $categoryInputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$loadTemplate			=	array( 'category_edit', 'jquery_inputlimit' );
			} else {
				$loadTemplate			=	'category_edit';
			}

			cbgjClass::getTemplate( $loadTemplate );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation();
			}

			$input						=	array();

			if ( $categoryInputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				HTML_groupjiveInputLimit::loadJquery( '#description', '#description_inputlimit', $categoryInputLimit );

				$input['inputlimit']	=	'<input type="text" id="description_inputlimit" name="description_inputlimit" value="' . (int) $categoryInputLimit . '" class="input-small" size="7" disabled="disabled" />';
			} else {
				$input['inputlimit']	=	false;
			}

			$input['publish']			=	moscomprofilerHTML::yesnoSelectList( 'published', ( ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) && ( ! $row->getParentAccess( array( 'mod_lvl1', $user ) ) ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || $row->getParentAccess( array( 'mod_lvl1', $user ) ) ), 'published', $row->get( 'published', ( $plugin->params->get( 'category_approve', 0 ) ? 0 : 1 ) ) ) );

			if ( $row->get( 'id' ) ) {
				$categories				=	cbgjClass::getCategoryOptions( 'cat_grp_create', 0, array( $row->get( 'id' ) ) );
			} else {
				$categories				=	cbgjClass::getCategoryOptions();
			}

			if ( $categories ) {
				array_unshift( $categories, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );

				$input['parent']		=	moscomprofilerHTML::selectList( $categories, 'parent', ( ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) && $row->get( 'id' ) ) ? 'disabled="disabled"' : null ) . ' class="input-large required"', 'value', 'text', (int) cbgjClass::getCleanParam( ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || ( ! $row->get( 'id' ) ) ), 'parent', $row->get( 'parent', 0 ) ), 1, false, false );
			} else {
				$input['parent']		=	CBTxt::P( 'There are no [categories] available.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) );
			}

			$input['name']				=	'<input type="text" id="name" name="name" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'name', $row->get( 'name' ) ) ) . '" class="input-large required" size="40" />';

			if ( $categoryEditor >= 2 ) {
				$description			=	cbgjClass::getHTMLCleanParam( true, 'description', $row->get( 'description' ) );
			} else {
				$description			=	cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) );
			}

			if ( $categoryEditor == 3 ) {
				$input['description']	=	$_CB_framework->displayCmsEditor( 'description', $description, 400, 200, 40, 5 );
			} else {
				$input['description']	=	'<textarea id="description" name="description" class="input-xlarge" cols="40" rows="5">' . htmlspecialchars( $description ) . '</textarea>';
			}

			$input['file']				=	'<input type="file" name="logo" class="input-large" size="40" />';
			$input['del_logo']			=	'<input type="checkbox" id="del_logo" name="del_logo" value="1" /> <label for="del_logo">' . CBTxt::T( 'Delete logo?' ) . '</label>';

			$listTypes					=	array();
			$listTypes[]				=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Open' ) );
			$listTypes[]				=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Approval' ) );
			$listTypes[]				=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Invite' ) );
			$input['types']				=	moscomprofilerHTML::selectList( $listTypes, 'types[]', 'size="4" multiple="multiple" class="input-small required"', 'value', 'text', explode( '|*|', cbgjClass::getCleanParam( ( $plugin->params->get( 'category_types_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'types', $row->get( 'types', $plugin->params->get( 'category_types_default', '1|*|2|*|3' ) ) ) ), 1, false, false );

			$listAccess					=	array();
			$listAccess[]				=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( '- Everybody' ) );
			$listAccess[]				=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
			$listAccess					=	array_merge( $listAccess, $_CB_framework->acl->get_groups_below_me() );
			$input['access']			=	moscomprofilerHTML::selectList( $listAccess, 'access', 'class="input-large required"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_access_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'access', $row->get( 'access', $plugin->params->get( 'category_access_default', -2 ) ) ), 1, false, false );

			$input['create']			=	moscomprofilerHTML::yesnoSelectList( 'create', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_create_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'create', $row->get( 'create', $plugin->params->get( 'category_create_default', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );

			$listCreate					=	array();
			$listCreate[]				=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
			$listCreate					=	array_merge( $listCreate, $_CB_framework->acl->get_groups_below_me() );
			$input['create_access']		=	moscomprofilerHTML::selectList( $listCreate, 'create_access', 'class="input-large"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_createaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'create_access', $row->get( 'create_access', $plugin->params->get( 'category_createaccess_default', -1 ) ) ), 1, false, false );

			$input['nested']			=	moscomprofilerHTML::yesnoSelectList( 'nested', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_nested_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'nested', $row->get( 'nested', $plugin->params->get( 'category_nested_default', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
			$input['nested_access']		=	moscomprofilerHTML::selectList( $listCreate, 'nested_access', 'class="input-large"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_nestedaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'nested_access', $row->get( 'nested_access', $plugin->params->get( 'category_nestedaccess_default', -1 ) ) ), 1, false, false );
			$input['owner']				=	'<input type="text" id="user_id" name="user_id" value="' . (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'usr_mod', $authorized ), 'user_id', $row->get( 'user_id' ), $user->id ) . '" class="input-small required digits" size="6" ' . ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ? 'disabled="disabled"' : null ) . ' />';

			if ( $plugin->params->get( 'category_captcha', 0 ) && ( ! cbgjClass::hasAccess( array( 'cat', 'usr_mod' ), $authorized ) ) ) {
				$input['captcha']		=	cbgjCaptcha::render();
			} else {
				$input['captcha']		=	false;
			}

			cbgjClass::displayMessage( $message );

			HTML_groupjiveCategoryEdit::showCategoryEdit( $row, $input, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * save category
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function saveCategoryEdit( $id, $user, $plugin ) {
		$row								=	cbgjData::getCategories( array( array( 'mod_lvl1' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( isset( $_POST['parent'] ) && ( ! $row->get( 'parent' ) ) ) {
			$row->set( 'parent', (int) cbgjClass::getCleanParam( true, 'parent' ) );
		}

		$authorized							=	cbgjClass::getAuthorization( $row, null, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || ( ( ! $row->get( 'id' ) ) && ( ( ( ! $row->get( 'parent' ) ) && cbgjClass::hasAccess( 'cat_create', $authorized ) ) || ( $row->get( 'parent' ) && $row->getParentAccess( array( 'cat_nested_create', $user ) ) ) ) ) ) {
			$categoryLimit					=	$plugin->params->get( 'category_limit', 0 );
			$categoryApprove				=	$plugin->params->get( 'category_approve', 0 );
			$categoryInputLimit				=	$plugin->params->get( 'category_desc_inputlimit', 0 );

			if ( ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) && ( ( ! $row->get( 'id' ) ) && ( $categoryLimit > 0 ) ) ) {
				if ( count( cbgjData::getCategories( null, array( 'user_id', '=', (int) $user->id ) ) ) >= $categoryLimit ) {
					cbgjClass::getPluginURL( ( $row->get( 'parent' ) ? array( 'categories', 'show', (int) $row->get( 'parent' ) ) : array( 'overview' ) ), CBTxt::P( '[category] limit reached!', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ), false, true, 'error' );
				}
			}

			$row->set( 'published', (int) cbgjClass::getCleanParam( ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || $row->getParentAccess( array( 'mod_lvl1', $user ) ) ), 'published', $row->get( 'published', ( $categoryApprove ? -1 : 1 ) ) ) );
			$row->set( 'parent', (int) cbgjClass::getCleanParam( ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || ( ! $row->get( 'id' ) ) ), 'parent', $row->get( 'parent', 0 ) ) );
			$row->set( 'user_id', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'usr_mod', $authorized ), 'user_id', $row->get( 'user_id', $user->id ) ) );
			$row->set( 'name', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'name', $row->get( 'name' ) ) ) );

			if ( $plugin->params->get( 'category_editor', 1 ) >= 2 ) {
				$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'description', $row->get( 'description' ) ) ) );
			} else {
				$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) ) ) );
			}

			if ( $categoryInputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) && ( cbIsoUtf_strlen( $row->get( 'description' ) ) > $categoryInputLimit ) ) {
				$row->set( 'description', trim( cbIsoUtf_substr( $row->get( 'description' ), 0, ( $categoryInputLimit - 3 ) ) ) . '...' );
			}

			$row->set( 'types', cbgjClass::getCleanParam( ( $plugin->params->get( 'category_types_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'types', $row->get( 'types', $plugin->params->get( 'category_types_default', '1|*|2|*|3' ) ) ) );
			$row->set( 'access', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_access_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'access', $row->get( 'access', $plugin->params->get( 'category_access_default', -2 ) ) ) );
			$row->set( 'create', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_create_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'create', $row->get( 'create', $plugin->params->get( 'category_create_default', 1 ) ) ) );
			$row->set( 'create_access', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_createaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'create_access', $row->get( 'create_access', $plugin->params->get( 'category_createaccess_default', -1 ) ) ) );
			$row->set( 'nested', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_nested_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'nested', $row->get( 'nested', $plugin->params->get( 'category_nested_default', 1 ) ) ) );
			$row->set( 'nested_access', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'category_nestedaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'nested_access', $row->get( 'nested_access', $plugin->params->get( 'category_nestedaccess_default', -1 ) ) ) );
			$row->set( 'date', $row->get( 'date', cbgjClass::getUTCDate() ) );
			$row->set( 'ordering', (int) $row->get( 'ordering', 99999 ) );

			if ( $row->get( 'name' ) == '' ) {
				$row->set( '_error', CBTxt::T( 'Name not specified!' ) );
			} elseif ( ! $row->get( 'user_id' ) ) {
				$row->set( '_error', CBTxt::P( '[owner] not specified!', array( '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) );
			} elseif ( ! $row->get( 'types' ) ) {
				$row->set( '_error', CBTxt::T( 'Types not specified!' ) );
			} elseif ( $plugin->params->get( 'category_captcha', 0 ) && ( ! cbgjClass::hasAccess( array( 'cat', 'usr_mod' ), $authorized ) ) ) {
				$captcha					=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$row->set( '_error', CBTxt::T( $captcha ) );
				}
			}

			if ( (int) cbgjClass::getCleanParam( true, 'del_logo', 0 ) ) {
				$row->unsetLogo();
			}

			$new							=	( $row->get( 'id' ) ? false : true );

			if ( $row->getError() || ( ! $row->store() ) ) {
				$this->showCategoryEdit( $row->get( 'parent' ), $row->get( 'id' ), $user, $plugin, CBTxt::P( '[category] failed to save! Error: [error]', array( '[category]' => cbgjClass::getOverride( 'category' ), '[error]' => $row->getError() ) ) );
				return;
			}

			if ( $categoryApprove && ( $row->get( 'published' ) == -1 ) && ( ( ( ! $row->get( 'parent' ) ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) || ( $row->get( 'parent' ) && ( ! cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) ) ) ) {
				$successMsg					=	CBTxt::P( '[category] saved successfully and awaiting approval!', array( '[category]' => cbgjClass::getOverride( 'category' ) ) );
			} else {
				$successMsg					=	CBTxt::P( '[category] saved successfully!', array( '[category]' => cbgjClass::getOverride( 'category' ) ) );
			}

			$row->storeLogo( 'logo' );

			if ( $row->getError() ) {
				cbgjClass::displayMessage( $successMsg, 'message' );

				$this->showCategoryEdit( $row->get( 'parent' ), $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Logo failed to upload! Error: [error]', array( '[error]' => $row->getError() ) ) );
				return;
			}

			if ( $new ) {
				if ( $row->get( 'published' ) ) {
					if ( ! $row->get( 'parent' ) ) {
						$notifications		=	cbgjData::getNotifications( array( array( 'gen_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'general' ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'general_categorynew=1' ) ) );
					} else {
						$notifications		=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_nestednew=1' ) ) );
					}

					if ( $notifications ) {
						$subject			=	CBTxt::T( '[overview_name] - [category_override] Created!' );
						$message			=	CBTxt::T( '[user] created [category] [category_override]!' );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $row );
						}
					}
				} elseif ( $categoryApprove && ( $row->get( 'published' ) == -1 ) && ( ( ( ! $row->get( 'parent' ) ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) || ( $row->get( 'parent' ) && ( ! cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) ) ) ) {
					if ( ! $row->get( 'parent' ) ) {
						$notifications		=	cbgjData::getNotifications( array( array( 'gen_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'general' ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'general_categoryapprove=1' ) ) );
					} else {
						$notifications		=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_nestedapprove=1' ) ) );
					}

					if ( $notifications ) {
						$subject			=	CBTxt::T( '[overview_name] - [category_override] Created Requires Approval!' );
						$message			=	CBTxt::T( '[user] created [category] [category_override] and requires approval!' );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $row );
						}
					}
				}
			} elseif ( $row->get( 'published' ) ) {
				if ( ! $row->get( 'parent' ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'gen_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'general' ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'general_categoryupdate=1' ) ) );
				} else {
					$notifications			=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_nestedupdate=1' ) ) );
				}

				if ( $notifications ) {
					$subject				=	CBTxt::T( '[overview_name] - [category_override] Edited!' );
					$message				=	CBTxt::T( '[user] edited [category] [category_override]!' );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $row );
					}
				}
			}

			cbgjClass::getPluginURL( array( 'categories', 'show', (int) $row->get( 'id' ) ), $successMsg, false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend category message render
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param mixed $message
	 */
	public function showCategoryMessage( $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$row						=	cbgjData::getCategories( array( array( 'cat_message' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( $row->get( 'id' ) ) {
			$editor					=	$plugin->params->get( 'category_message_editor', 1 );
			$authorized				=	cbgjClass::getAuthorization( $row, null, $user, $row->getOwner() );

			cbgjClass::getTemplate( 'category_message' );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation();
			}

			$input					=	array();

			$input['subject']		=	'<input type="text" id="subject" name="subject" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'subject' ) ) . '" class="input-large required" size="40" />';

			if ( $editor >= 2 ) {
				$body				=	cbgjClass::getHTMLCleanParam( true, 'body' );
			} else {
				$body				=	cbgjClass::getCleanParam( true, 'body' );
			}

			if ( $editor == 3 ) {
				$input['body']		=	$_CB_framework->displayCmsEditor( 'body', $body, 400, 200, 40, 6 );
			} else {
				$input['body']		=	'<textarea id="body" name="body" class="input-xlarge required" cols="40" rows="6">' . htmlspecialchars( $body ) . '</textarea>';
			}

			if ( $plugin->params->get( 'category_message_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$input['captcha']	=	cbgjCaptcha::render();
			} else {
				$input['captcha']	=	false;
			}

			cbgjClass::displayMessage( $message );

			HTML_groupjiveCategoryMessage::showCategoryMessage( $row, $input, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * send category message
	 *
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function sendCategoryMessage( $id, $user, $plugin ) {
		global $_PLUGINS;

		$row					=	cbgjData::getCategories( array( array( 'cat_message' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( $row->get( 'id' ) ) {
			$authorized			=	cbgjClass::getAuthorization( $row, null, $user, $row->getOwner() );
			$subject			=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'subject' ) );

			if ( $plugin->params->get( 'category_message_editor', 1 ) >= 2 ) {
				$body			=	cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'body' ) );
			} else {
				$body			=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'body' ) );
			}

			if ( $subject == '' ) {
				$error			=	CBTxt::T( 'Subject not specified!' );
			} elseif ( $body == '' ) {
				$error			=	CBTxt::T( 'Body not specified!' );
			} elseif ( $plugin->params->get( 'category_message_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha		=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$error		=	CBTxt::T( $captcha );
				}
			}

			$users				=	array();
			$groups				=	cbgjData::getGroups( null, array( 'category', '=', (int) $row->get( 'id' ) ) );

			if ( $groups ) foreach ( $groups as $group ) {
				if ( ! in_array( $group->get( 'user_id' ), $users ) ) {
					$users[]	=	$group->get( 'user_id' );
				}
			}

			$_PLUGINS->trigger( 'gj_onBeforeMessageCategory', array( $row, $subject, $body, $users, $user, $plugin ) );

			if ( isset( $error ) ) {
				$this->showCategoryMessage( $id, $user, $plugin, CBTxt::P( '[groups] message failed to save! Error: [error]', array( '[groups]' => cbgjClass::getOverride( 'group', true ), '[error]' => $error ) ) );
				return;
			}

			if ( $users ) {
				$msgSubject		=	CBTxt::P( '[site_name] - [msg_subject]', array( '[msg_subject]' => $subject ) );
				$msgBody		=	CBTxt::P( 'Hello [username], the following is a message from [category].<br /><br />[msg_body]', array( '[msg_body]' => $body ) );

				foreach ( $users as $user_id ) {
					cbgjClass::getNotification( $user_id, $user->id, $msgSubject, $msgBody, 2, $row );
				}
			} else {
				cbgjClass::getPluginURL( ( $row->get( 'parent' ) ? array( 'categories', 'show', (int) $row->get( 'parent' ) ) : array( 'categories', 'show', (int) $row->get( 'id' ) ) ), CBTxt::P( '[groups] message failed to send! Error: no [users] to message', array( '[groups]' => cbgjClass::getOverride( 'group', true ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ), false, true, 'error' );
			}

			$_PLUGINS->trigger( 'gj_onAfterMessageCategory', array( $row, $subject, $body, $users, $user, $plugin ) );

			cbgjClass::getPluginURL( array( 'categories', 'show', (int) $row->get( 'id' ) ), CBTxt::P( '[groups] message sent successfully!', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend nested categories render
	 *
	 * @param object $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return mixed
	 */
	public function showNestedCategories( $category, $user, $plugin ) {
		cbgjClass::getTemplate( 'category_nested' );

		$paging					=	new cbgjPaging( 'category_nested' );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'category_nested_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		$where[]				=	array( 'parent', '=', (int) $category->get( 'id' ) );

		switch( $plugin->params->get( 'category_nested_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'group_count_asc';
				break;
			case 8:
				$orderBy		=	'group_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), $where, $orderBy, ( $plugin->params->get( 'category_nested_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [categories]...', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveNestedCategories::showNested( $rows, $pageNav, $category, $user, $plugin );
	}

	/**
	 * prepare frontend category groups render
	 *
	 * @param object $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return mixed
	 */
	public function showCategoryGroups( $category, $user, $plugin ) {
		cbgjClass::getTemplate( 'category_groups' );

		$paging					=	new cbgjPaging( 'category_groups' );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'category_groups_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		$where[]				=	array( 'parent', '=', 0 );
		$where[]				=	array( 'category', '=', (int) $category->get( 'id' ) );

		switch( $plugin->params->get( 'category_groups_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'user_count_asc';
				break;
			case 8:
				$orderBy		=	'user_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy, ( $plugin->params->get( 'category_groups_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [groups]...', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveCategoryGroups::showGroups( $rows, $pageNav, $category, $user, $plugin );
	}

	/**
	 * prepare frontend groups approval render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return mixed
	 */
	public function showGroupApproval( $user, $plugin ) {
		if ( $plugin->params->get( 'group_approve', 0 ) ) {
			cbgjClass::getTemplate( 'group_approval' );

			$paging					=	new cbgjPaging( 'group_approval' );

			$limit					=	$paging->getlimit( (int) $plugin->params->get( 'group_approval_limit', 15 ) );
			$limitstart				=	$paging->getLimistart();
			$search					=	$paging->getFilter( 'search' );
			$where					=	array();

			if ( isset( $search ) && ( $search != '' ) ) {
				$where[]			=	array( 'name', 'CONTAINS', $search );
			}

			$searching				=	( count( $where ) ? true : false );

			$where[]				=	array( 'published', '=', -1 );

			switch( $plugin->params->get( 'group_approval_orderby', 1 ) ) {
				case 2:
					$orderBy		=	array( 'ordering', 'DESC' );
					break;
				case 3:
					$orderBy		=	array( 'date', 'ASC' );
					break;
				case 4:
					$orderBy		=	array( 'date', 'DESC' );
					break;
				case 5:
					$orderBy		=	array( 'name', 'ASC' );
					break;
				case 6:
					$orderBy		=	array( 'name', 'DESC' );
					break;
				case 7:
					$orderBy		=	'user_count_asc';
					break;
				case 8:
					$orderBy		=	'user_count_desc';
					break;
				case 9:
					$orderBy		=	'nested_count_asc';
					break;
				case 10:
					$orderBy		=	'nested_count_desc';
					break;
				default:
					$orderBy		=	null;
					break;
			}

			$total					=	count( cbgjData::getGroups( array( array( 'grp_can_publish', 'cat_approved' ), $user, null, true ), $where, $orderBy ) );

			if ( $total <= $limitstart ) {
				$limitstart			=	0;
			}

			$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

			$rows					=	cbgjData::getGroups( array( array( 'grp_can_publish', 'cat_approved' ), $user, null, true ), $where, $orderBy, ( $plugin->params->get( 'group_approval_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

			$pageNav->search		=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [groups]...', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), $search );
			$pageNav->searching		=	$searching;
			$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
			$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

			return HTML_groupjiveGroupApproval::showGroupApproval( $rows, $pageNav, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend all groups render
	 *
	 * @param boolean $self
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return mixed
	 */
	public function showGroupAll( $self, $user, $plugin ) {
		cbgjClass::getTemplate( 'group_all' );

		$paging					=	new cbgjPaging( ( $self ? 'group_allmy' : 'group_all' ) );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'group_all_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		if ( $self ) {
			$where[]			=	array( 'user_id', '=', (int) $user->id );
		}

		switch( $plugin->params->get( 'group_all_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'user_count_asc';
				break;
			case 8:
				$orderBy		=	'user_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy, ( $plugin->params->get( 'group_all_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [groups]...', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveGroupAll::showGroupAll( $self, $rows, $pageNav, $user, $plugin );
	}

	/**
	 * prepare frontend group render
	 *
	 * @param int $catid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param mixed $message
	 * @param string $expand
	 * @param object $plugin
	 */
	public function showGroup( $catid, $id, $user, $plugin, $message = null, $expand = null ) {
		global $_CB_framework;

		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$row					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $row->get( 'id' ) ) {
			$category			=	$row->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$toggle				=	$plugin->params->get( 'group_toggle', 3 );

			cbgjClass::getTemplate( array( 'group', 'group_panes', 'group_main' ) );

			if ( $toggle > 1 ) {
				$toggleJs		=	"$( '.gjToggleCollapse' ).live( 'click', function() {"
								.		"$( $( this ).attr( 'href' ) ).trigger( 'click' );"
								.		"return false;"
								.	"});"
								.	"$( '.gjToggleExpand' ).live( 'click', function() {"
								.		"var button = $( this );"
								.		"$( button.attr( 'href' ) ).toggle( 'slow', function() {"
								.			"button.toggle();"
								.		"});"
								.		"return false;"
								.	"});";

				if ( $toggle == 3 ) {
					$toggleJs	.=	"$( '.gjToggle' ).hide();";
				} else {
					$toggleJs	.=	"$( '.gjToggle' ).show();"
								.	"$( '.gjToggleExpand' ).hide();";
				}

				$_CB_framework->outputCbJQuery( $toggleJs );

				if ( $expand ) {
					$_CB_framework->outputCbJQuery( "$( '" . addslashes( $expand ) . " .gjTabEditExpand' ).trigger( 'click' );" );
				}
			}

			cbgjClass::displayMessage( $message );

			HTML_groupjiveGroup::showGroup( $row, $category, $user, $plugin );
		} else {
			if ( $user->id ) {
				cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
			} else {
				cbgjClass::getCBURL( 'login', CBTxt::P( 'Login or Register to view this [group].', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), false, true, 'error', true );
			}
		}
	}

	/**
	 * join group safely
	 *
	 * @param int $catid
	 * @param int $grpid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function joinGroup( $catid, $grpid, $id, $user, $plugin ) {
		global $_PLUGINS;

		$category						=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group							=	cbgjData::getGroups( array( array( 'grp_join' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row							=	cbgjData::getUsers( null, array( array( 'group', '=', $grpid ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group						=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category					=	$group->getCategory();
		}

		if ( $group->get( 'id' ) && ( ! $row->get( 'id' ) ) ) {
			$authorized					=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

			$row->set( 'user_id', (int) $user->id );
			$row->set( 'group', (int) $group->get( 'id' ) );
			$row->set( 'date', cbgjClass::getUTCDate() );
			$row->set( 'status', ( ( $group->get( 'type' ) == 2 ) && ( ! cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_invited' ), $authorized ) ) ? 0 : 1 ) );

			$_PLUGINS->trigger( 'gj_onBeforeJoinGroup', array( $row, $group, $category, $user, $plugin ) );

			if ( ! $row->store() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Failed to join [group]! Error: [error]', array( '[group]' => cbgjClass::getOverride( 'group' ), '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			$_PLUGINS->trigger( 'gj_onAfterJoinGroup', array( $row, $group, $category, $user, $plugin ) );

			$notifications				=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_userjoin=1', array( 'params', 'CONTAINS', 'group_userapprove=1' ) ) ) );

			if ( $notifications ) {
				$subject				=	CBTxt::T( '[group_name] - New [user_override]!' );

				foreach ( $notifications as $notification ) {
					$notificationParams	=	$notification->getParams();
					$message			=	null;

					if ( ( $row->get( 'status' ) == 0 ) && $notificationParams->get( 'group_userapprove' ) ) {
						$message		=	CBTxt::T( '[user] has joined [group] in [category] and requires approval!' );
					} elseif ( $notificationParams->get( 'group_userjoin' ) ) {
						$message		=	CBTxt::T( '[user] has joined [group] in [category]!' );
					}

					if ( $message ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
					}
				}
			}

			$row->acceptInvites( $id );

			$invites					=	$row->getUnacceptedInvites( $id );

			if ( $invites ) foreach ( $invites as $invite ) {
				$notification			=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $invite->get( 'group' ) ), array( 'user_id', '=', (int) $invite->get( 'user_id' ) ), array( 'params', 'CONTAINS', 'group_inviteaccept=1' ) ), null, null, false );

				if ( $notification->get( 'id' ) ) {
					$subject			=	CBTxt::T( '[group_name] - Invite Accepted!' );
					$message			=	CBTxt::T( '[user] has accepted your invite to join [group] in [category]!' );

					cbgjClass::getNotification( $notification->get( 'user_id' ), $invite->get( 'user' ), $subject, $message, 1, $category, $group );
				}
			}

			if ( ( $group->get( 'type' ) == 2 ) && ( ! cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_invited' ), $authorized ) ) ) {
				$success				=	CBTxt::P( 'Joined [group] successfully and awaiting approval!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			} else {
				$success				=	CBTxt::P( 'Joined [group] successfully!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), $success, false, true, null, false, false, true );
		} else {
			if ( $user->id ) {
				if ( $row->get( 'id' ) ) {
					cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'You are already a member of this [group].', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), false, true, 'error' );
				} else {
					cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
				}
			} else {
				cbgjClass::getCBURL( 'login', CBTxt::P( 'Login or Register to join [groups].', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), false, true, 'error', true );
			}
		}
	}

	/**
	 * leave group safely
	 *
	 * @param int $catid
	 * @param int $grpid
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function leaveGroup( $catid, $grpid, $user, $plugin ) {
		global $_PLUGINS;

		$category			=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group				=	cbgjData::getGroups( array( array( 'grp_leave' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row				=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category		=	$group->getCategory();
		}

		if ( $group->get( 'id' ) && $row->get( 'id' ) ) {
			$_PLUGINS->trigger( 'gj_onBeforeLeaveGroup', array( $row, $group, $category, $user, $plugin ) );

			$notifications	=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_userleave=1' ) ) );

			if ( ! $row->deleteAll() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Failed to leave [group]! Error: [error]', array( '[group]' => cbgjClass::getOverride( 'group' ), '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			$_PLUGINS->trigger( 'gj_onAfterLeaveGroup', array( $row, $group, $category, $user, $plugin ) );

			if ( $notifications ) {
				$subject	=	CBTxt::T( '[group_name] - [user_override] Left!' );
				$message	=	CBTxt::T( '[user] has left [group] in [category]!' );

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Left [group] successfully!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * set group publish state status
	 *
	 * @param int $state
	 * @param int $catid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function stateGroup( $state, $catid, $id, $user, $plugin ) {
		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$row						=	cbgjData::getGroups( array( array( 'grp_can_publish' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $row->get( 'id' ) ) {
			$category				=	$row->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$currentState			=	$row->get( 'published' );

			if ( ! $row->storeState( $state ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( '[group] state failed to saved! Error: [error]', array( '[group]' => cbgjClass::getOverride( 'group' ), '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $state && ( $currentState == -1 ) ) {
				if ( ! $row->get( 'parent' ) ) {
					$notifications	=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $category->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_groupnew=1' ) ) );
				} else {
					$notifications	=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_nestednew=1' ) ) );
				}

				if ( $notifications ) {
					$subject		=	CBTxt::P( '[category_name] - [group_override] Created!' );
					$message		=	CBTxt::T( '[user] created [group] in [category]!' );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $row );
					}
				}

				if ( $user->id != $row->get( 'user_id' ) ) {
					$subject		=	CBTxt::T( '[group_name] - Create Request Accepted!' );
					$message		=	CBTxt::T( 'Your request to create [group] has been accepted!' );

					cbgjClass::getNotification( $row->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $row );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( '[group] state saved successfully!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * delete group
	 *
	 * @param int $catid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteGroup( $catid, $id, $user, $plugin ) {
		$category			=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$row				=	cbgjData::getGroups( array( array( 'mod_lvl2' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $row->get( 'id' ) ) {
			$category		=	$row->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			if ( ! $row->get( 'parent' ) ) {
				$notifications	=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $category->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_groupdelete=1' ) ) );
			} else {
				$notifications	=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_nesteddelete=1' ) ) );
			}

			if ( ! $row->deleteAll() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( '[group] failed to delete! Error: [error]', array( '[group]' => cbgjClass::getOverride( 'group' ), '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $notifications ) {
				$subject		=	CBTxt::T( '[category_name] - [group_override] Deleted!' );
				$message		=	CBTxt::T( '[user] deleted [group_name] in [category]!' );

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $row );
				}
			}

			cbgjClass::getPluginURL( ( $row->get( 'parent' ) ? array( 'groups', 'show', (int) $row->get( 'category' ), (int) $row->get( 'parent' ) ) : array( 'categories', 'show', (int) $category->get( 'id' ) ) ), CBTxt::P( '[group] deleted successfully!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend group edit render
	 *
	 * @param int $catid
	 * @param int $parent
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param mixed $message
	 */
	public function showGroupEdit( $catid, $parent, $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$category								=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$row									=	cbgjData::getGroups( array( array( 'cat_grp_create', 'mod_lvl3' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $row->get( 'id' ) ) {
			$category							=	$row->getCategory();
		}

		if ( $parent && ( ! $row->get( 'parent' ) ) ) {
			$row->set( 'parent', (int) $parent );
		}

		if ( $category && ( ! $row->get( 'category' ) ) ) {
			$row->set( 'category', (int) $category->get( 'id' ) );
		}

		$authorized								=	cbgjClass::getAuthorization( $category, $row, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || ( ( ! $row->get( 'id' ) ) && ( ( ( ! $row->get( 'parent' ) ) && cbgjClass::hasAccess( 'cat_grp_create', $authorized ) ) || ( $row->get( 'parent' ) && $row->getParentAccess( array( 'grp_nested_create', $user ) ) ) || ( ( ! $category->get( 'id' ) ) && cbgjClass::hasAccess( 'grp_create', $authorized ) ) ) ) ) {
			$groupEditor						=	$plugin->params->get( 'group_editor', 1 );
			$groupLimit							=	$plugin->params->get( 'group_limit', 0 );
			$groupInputLimit					=	$plugin->params->get( 'group_desc_inputlimit', 0 );

			if ( ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) && ( ( ! $row->get( 'id' ) ) && ( $groupLimit > 0 ) ) ) {
				if ( count( cbgjData::getGroups( null, array( 'user_id', '=', (int) $user->id ) ) ) >= $groupLimit ) {
					cbgjClass::getPluginURL( ( $row->get( 'parent' ) ? array( 'groups', 'show', (int) $row->get( 'category' ), (int) $row->get( 'parent' ) ) : array( 'categories', 'show', (int) $category->get( 'id' ) ) ), CBTxt::P( '[group] limit reached!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), false, true, 'error' );
				}
			}

			if ( $groupInputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$loadTemplate			=	array( 'group_edit', 'jquery_inputlimit' );
			} else {
				$loadTemplate			=	'group_edit';
			}

			cbgjClass::getTemplate( $loadTemplate );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation();
			}

			$input								=	array();

			if ( $groupInputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				HTML_groupjiveInputLimit::loadJquery( '#description', '#description_inputlimit', $groupInputLimit );

				$input['inputlimit']			=	'<input type="text" id="description_inputlimit" name="description_inputlimit" value="' . (int) $groupInputLimit . '" class="input-small" size="7" disabled="disabled" />';
			} else {
				$input['inputlimit']			=	false;
			}

			$input['publish']					=	moscomprofilerHTML::yesnoSelectList( 'published', ( ( ! cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) && ( ! $row->getParentAccess( array( 'mod_lvl2', $user ) ) ) ? 'disabled="disabled"' : null ) . ' class="input-small"', (int) cbgjClass::getCleanParam( ( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) || $row->getParentAccess( array( 'mod_lvl2', $user ) ) ), 'published', $row->get( 'published', ( $plugin->params->get( 'group_approve', 0 ) ? 0 : 1 ) ) ) );

			$categories							=	cbgjClass::getCategoryOptions();

			if ( $categories ) {
				$input['category']				=	moscomprofilerHTML::selectList( $categories, 'category', ( ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) && $category->get( 'id' ) ) ? 'disabled="disabled"' : null ) . ' class="input-large required"', 'value', 'text', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'usr_mod', $authorized ), 'category', $row->get( 'category', $category->get( 'id' ) ) ), 1, false, false );
			} else {
				$input['category']				=	CBTxt::P( 'There are no [categories] available.', array( '[categories]' => cbgjClass::getOverride( 'category', true ) ) );
			}

			if ( $row->get( 'id' ) ) {
				$groups							=	cbgjClass::getGroupOptions( 'grp_nested_create', $category->get( 'id' ), 0, array( $row->get( 'id' ) ) );
			} else {
				$groups							=	cbgjClass::getGroupOptions( 'grp_nested_create', $category->get( 'id' ) );
			}

			if ( $groups ) {
				array_unshift( $groups, moscomprofilerHTML::makeOption( '0', CBTxt::T( 'No Parent' ) ) );

				$input['parent']				=	moscomprofilerHTML::selectList( $groups, 'parent', ( ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) && $row->get( 'id' ) ) ? 'disabled="disabled"' : null ) . ' class="input-large required"', 'value', 'text', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'usr_mod', $authorized ), 'parent', $row->get( 'parent', 0 ) ), 1, false, false );
			} else {
				if ( $category->get( 'id' ) ) {
					$input['parent']			=	CBTxt::P( 'There are no [groups] available.', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) );
				} else {
					$input['parent']			=	CBTxt::P( 'Please save [group] with [category] before selecting parent [group].', array( '[group]' => cbgjClass::getOverride( 'group' ), '[category]' => cbgjClass::getOverride( 'category' ) ) );
				}
			}

			$input['name']						=	'<input type="text" id="name" name="name" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'name', $row->get( 'name' ) ) ) . '" class="input-large required" size="40" />';

			if ( $groupEditor >= 2 ) {
				$description					=	cbgjClass::getHTMLCleanParam( true, 'description', $row->get( 'description' ) );
			} else {
				$description					=	cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) );
			}

			if ( $groupEditor == 3 ) {
				$input['description']			=	$_CB_framework->displayCmsEditor( 'description', $description, 400, 200, 40, 5 );
			} else {
				$input['description']			=	'<textarea id="description" name="description" class="input-xlarge" cols="40" rows="5">' . htmlspecialchars( $description ) . '</textarea>';
			}

			$input['file']						=	'<input type="file" name="logo" class="input-large" size="40" />';
			$input['del_logo']					=	'<input type="checkbox" id="del_logo" name="del_logo" value="1" /> <label for="del_logo">' . CBTxt::T( 'Delete logo?' ) . '</label>';

			$types								=	explode( '|*|', $category->get( 'types' ) );
			$listType							=	array();

			if ( in_array( 1, $types ) || cbgjClass::hasAccess( 'mod_lvl1', $authorized ) || ( ! $category->get( 'id' ) ) ) {
				$listType[]						=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Open' ) );
			}

			if ( in_array( 2, $types ) || cbgjClass::hasAccess( 'mod_lvl1', $authorized ) || ( ! $category->get( 'id' ) ) ) {
				$listType[]						=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Approval' ) );
			}

			if ( in_array( 3, $types ) || cbgjClass::hasAccess( 'mod_lvl1', $authorized ) || ( ! $category->get( 'id' ) ) ) {
				$listType[]						=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Invite' ) );
			}

			$input['type']						=	moscomprofilerHTML::selectList( $listType, 'type', 'class="input-small required"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_type_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'type', $row->get( 'type', $plugin->params->get( 'group_type_default', 1 ) ) ), 1, false, false );

			$listAccess							=	array();
			$listAccess[]						=	moscomprofilerHTML::makeOption( '-2', CBTxt::T( '- Everybody' ) );
			$listAccess[]						=	moscomprofilerHTML::makeOption( '-1', CBTxt::T( '- All Registered Users' ) );
			$listAccess							=	array_merge( $listAccess, $_CB_framework->acl->get_groups_below_me() );
			$input['access']					=	moscomprofilerHTML::selectList( $listAccess, 'access', 'class="input-large required"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_access_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'access', $row->get( 'access', $plugin->params->get( 'group_access_default', -2 ) ) ), 1, false, false );

			$listInvite							=	array();
			$listInvite[]						=	moscomprofilerHTML::makeOption( '0', cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'user', true ) );
			$listInvite[]						=	moscomprofilerHTML::makeOption( '1', '.&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'moderator', true ) );
			$listInvite[]						=	moscomprofilerHTML::makeOption( '2', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'admin', true ) );
			$listInvite[]						=	moscomprofilerHTML::makeOption( '3', '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;' . cbgjClass::getOverride( 'group' ) . '&nbsp;' . cbgjClass::getOverride( 'owner' ) );
			$input['invite']					=	moscomprofilerHTML::selectList( $listInvite, 'invite', 'class="input-large required"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_invite_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'invite', $row->get( 'invite', $plugin->params->get( 'group_invite_default', 0 ) ) ), 1, false, false );

			$input['users']						=	moscomprofilerHTML::yesnoSelectList( 'users', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_users_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'users', $row->get( 'users', $plugin->params->get( 'group_users_default', 1 ) ) ) );

			$input['nested']					=	moscomprofilerHTML::yesnoSelectList( 'nested', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_nested_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'nested', $row->get( 'nested', $plugin->params->get( 'group_nested_default', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
			$input['nested_access']				=	moscomprofilerHTML::selectList( $listInvite, 'nested_access', 'class="input-large"', 'value', 'text', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_nestedaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'nested_access', $row->get( 'nested_access', $plugin->params->get( 'group_nestedaccess_default', -1 ) ) ), 1, false, false );

			$input['owner']						=	'<input type="text" id="user_id" name="user_id" value="' . (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'usr_mod', $authorized ), 'user_id', $row->get( 'user_id', $user->id ) ) . '" class="input-small required digits" size="6" ' . ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ? 'disabled="disabled"' : null ) . ' />';

			if ( $plugin->params->get( 'group_captcha', 0 ) && ( ! cbgjClass::hasAccess( array( 'grp', 'usr_mod' ), $authorized ) ) ) {
				$input['captcha']				=	cbgjCaptcha::render();
			} else {
				$input['captcha']				=	false;
			}

			cbgjClass::displayMessage( $message );

			HTML_groupjiveGroupEdit::showGroupEdit( $row, $input, $category, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * save group
	 *
	 * @param int $catid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function saveGroupEdit( $catid, $id, $user, $plugin ) {
		$category							=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$row								=	cbgjData::getGroups( array( array( 'cat_grp_create', 'mod_lvl3' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $row->get( 'id' ) ) {
			$category						=	$row->getCategory();
		}

		if ( isset( $_POST['parent'] ) && ( ! $row->get( 'parent' ) ) ) {
			$row->set( 'parent', (int) cbgjClass::getCleanParam( true, 'parent' ) );
		}

		$authorized							=	cbgjClass::getAuthorization( $category, $row, $user, $row->getOwner() );

		if ( $row->get( 'id' ) || ( ( ! $row->get( 'id' ) ) && ( ( ( ! $row->get( 'parent' ) ) && cbgjClass::hasAccess( 'cat_grp_create', $authorized ) ) || ( $row->get( 'parent' ) && $row->getParentAccess( array( 'grp_nested_create', $user ) ) ) || ( ( ! $category->get( 'id' ) ) && cbgjClass::hasAccess( 'grp_create', $authorized ) ) ) ) ) {
			$groupLimit						=	$plugin->params->get( 'group_limit', 0 );
			$groupApprove					=	$plugin->params->get( 'group_approve', 0 );
			$groupInputLimit				=	$plugin->params->get( 'group_desc_inputlimit', 0 );

			if ( ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) && ( ( ! $row->get( 'id' ) ) && ( $groupLimit > 0 ) ) ) {
				if ( count( cbgjData::getGroups( null, array( 'user_id', '=', (int) $user->id ) ) ) >= $groupLimit ) {
					cbgjClass::getPluginURL( ( $row->get( 'parent' ) ? array( 'groups', 'show', (int) $row->get( 'category' ), (int) $row->get( 'parent' ) ) : array( 'categories', 'show', (int) $category->get( 'id' ) ) ), CBTxt::P( '[group] limit reached!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), false, true, 'error' );
				}
			}

			$row->set( '_previousCategory', $row->get( 'category', $category->get( 'id' ) ) );
			$row->set( 'published', (int) cbgjClass::getCleanParam( ( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) || $row->getParentAccess( array( 'mod_lvl2', $user ) ) ), 'published', $row->get( 'published', ( $groupApprove ? -1 : 1 ) ) ) );
			$row->set( 'category', (int) cbgjClass::getCleanParam( ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || ( ! $category->get( 'id' ) ) ), 'category', $row->get( 'category', $category->get( 'id' ) ) ) );

			if ( ( ! $category->get( 'id' ) ) && $row->get( 'category' ) ) {
				$category					=	$row->getCategory();
			}

			$row->set( 'parent', (int) cbgjClass::getCleanParam( ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || ( ! $row->id ) ), 'parent', $row->get( 'parent', 0 ) ) );
			$row->set( 'user_id', (int) cbgjClass::getCleanParam( cbgjClass::hasAccess( 'usr_mod', $authorized ), 'user_id', $row->get( 'user_id', $user->id ) ) );
			$row->set( 'name', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'name', $row->get( 'name' ) ) ) );

			if ( $plugin->params->get( 'group_editor', 1 ) >= 2 ) {
				$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'description', $row->get( 'description' ) ) ) );
			} else {
				$row->set( 'description', cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'description', $row->get( 'description' ) ) ) );
			}

			if ( $groupInputLimit && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) && ( cbIsoUtf_strlen( $row->get( 'description' ) ) > $groupInputLimit ) ) {
				$row->set( 'description', trim( cbIsoUtf_substr( $row->get( 'description' ), 0, ( $groupInputLimit - 3 ) ) ) . '...' );
			}

			$row->set( 'type', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_type_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'type', $row->get( 'type', $plugin->params->get( 'group_type_default', 1 ) ) ) );
			$row->set( 'access', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_access_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'access', $row->get( 'access', $plugin->params->get( 'group_access_default', -2 ) ) ) );
			$row->set( 'invite', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_invite_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'invite', $row->get( 'invite', $plugin->params->get( 'group_invite_default', 0 ) ) ) );
			$row->set( 'users', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_users_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'users', $row->get( 'users', $plugin->params->get( 'group_users_default', 1 ) ) ) );
			$row->set( 'nested', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_nested_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'nested', $row->get( 'nested', $plugin->params->get( 'group_nested_default', 1 ) ) ) );
			$row->set( 'nested_access', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'group_nestedaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'nested_access', $row->get( 'nested_access', $plugin->params->get( 'group_nestedaccess_default', -1 ) ) ) );
			$row->set( 'date', $row->get( 'date', cbgjClass::getUTCDate() ) );
			$row->set( 'ordering', (int) $row->get( 'ordering', 1 ) );

			if ( $row->get( 'name' ) == '' ) {
				$row->set( '_error', CBTxt::T( 'Name not specified!' ) );
			} elseif ( ! $row->get( 'user_id' ) ) {
				$row->set( '_error', CBTxt::P( '[owner] not specified!', array( '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) );
			} elseif ( ! $row->get( 'category' ) ) {
				$row->set( '_error', CBTxt::P( '[category] not specified!', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) );
			} elseif ( ! $row->get( 'type' ) ) {
				$row->set( '_error', CBTxt::T( 'Type not specified!' ) );
			} elseif ( ( ! in_array( $row->get( 'type' ), explode( '|*|', $category->get( 'types' ) ) ) ) && ( ! cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) ) {
				$row->set( '_error', CBTxt::T( 'Type not permitted!' ) );
			} elseif ( $row->get( 'parent' ) && ( $row->get( 'category' ) != $row->getParent()->get( 'category' ) ) ) {
				$row->set( '_error', CBTxt::P( '[category] does not match parent [category]!', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) );
			} elseif ( $plugin->params->get( 'group_captcha', 0 ) && ( ! cbgjClass::hasAccess( array( 'grp', 'usr_mod' ), $authorized ) ) ) {
				$captcha			=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$row->set( '_error', CBTxt::T( $captcha ) );
				}
			}

			if ( (int) cbgjClass::getCleanParam( true, 'del_logo', 0 ) ) {
				$row->unsetLogo();
			}

			$new							=	( $row->get( 'id' ) ? false : true );

			if ( $row->getError() || ( ! $row->store() ) ) {
				$this->showGroupEdit( $category->get( 'id' ), $row->get( 'parent' ), $row->get( 'id' ), $user, $plugin, CBTxt::P( '[group] failed to save! Error: [error]', array( '[group]' => cbgjClass::getOverride( 'group' ), '[error]' => $row->getError() ) ) );
				return;
			}

			if ( $groupApprove && ( $row->get( 'published' ) == -1 ) && ( ( ( ! $row->get( 'parent' ) ) && ( ! cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) ) || ( $row->get( 'parent' ) && ( ! cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) ) ) ) {
				$successMsg					=	CBTxt::P( '[group] saved successfully and awaiting approval!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			} else {
				$successMsg					=	CBTxt::P( '[group] saved successfully!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
			}

			$row->storeLogo( 'logo' );

			if ( $row->getError() ) {
				cbgjClass::displayMessage( $successMsg, 'message' );

				$this->showGroupEdit( $category->get( 'id' ), $row->get( 'parent' ), $row->get( 'id' ), $user, $plugin, CBTxt::P( 'Logo failed to upload! Error: [error]', array( '[error]' => $row->getError() ) ) );
				return;
			}

			if ( $new ) {
				if ( $row->get( 'published' ) ) {
					if ( ! $row->get( 'parent' ) ) {
						$notifications		=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $category->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_groupnew=1' ) ) );
					} else {
						$notifications		=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_nestednew=1' ) ) );
					}

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[category_name] - [group_override] Created!' );
						$message			=	CBTxt::T( '[user] created [group] in [category]!' );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $row );
						}
					}
				} elseif ( $groupApprove && ( $row->get( 'published' ) == -1 ) && ( ( ( ! $row->get( 'parent' ) ) && ( ! cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) ) || ( $row->get( 'parent' ) && ( ! cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) ) ) ) {
					if ( ! $row->get( 'parent' ) ) {
						$notifications		=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $category->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_groupapprove=1' ) ) );
					} else {
						$notifications		=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_nestedapprove=1' ) ) );
					}

					if ( $notifications ) {
						$subject			=	CBTxt::P( '[category_name] - [group_override] Created Requires Approval!' );
						$message			=	CBTxt::T( '[user] created [group] in [category] and requires approval!' );

						foreach ( $notifications as $notification ) {
							cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $row );
						}
					}
				}
			} elseif ( $row->get( 'published' ) ) {
				if ( ! $row->get( 'parent' ) ) {
					$notifications			=	cbgjData::getNotifications( array( array( 'cat_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $category->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'category_groupupdate=1' ) ) );
				} else {
					$notifications			=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $row->get( 'parent' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_nestedupdate=1' ) ) );
				}

				if ( $notifications ) {
					$subject				=	CBTxt::P( '[category_name] - [group_override] Edited!' );
					$message				=	CBTxt::T( '[user] edited [group] in [category]!' );

					foreach ( $notifications as $notification ) {
						cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $row );
					}
				}
			}

			$row->storeOwner( $row->get( 'user_id' ) );

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), $successMsg, false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend group message render
	 *
	 * @param int $catid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @param mixed $message
	 */
	public function showGroupMessage( $catid, $id, $user, $plugin, $message = null ) {
		global $_CB_framework;

		$category					=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$row						=	cbgjData::getGroups( array( array( 'grp_message' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $row->get( 'id' ) ) {
			$category				=	$row->getCategory();
		}

		if ( $row->id ) {
			$editor					=	$plugin->params->get( 'group_message_editor', 1 );
			$authorized				=	cbgjClass::getAuthorization( $category, $row, $user, $row->getOwner() );

			cbgjClass::getTemplate( 'group_message' );

			if ( $plugin->params->get( 'general_validate', 1 ) ) {
				cbgjClass::getFormValidation();
			}

			$input					=	array();

			$input['subject']		=	'<input type="text" id="subject" name="subject" value="' . htmlspecialchars( cbgjClass::getCleanParam( true, 'subject' ) ) . '" class="input-large required" size="40" />';

			if ( $editor >= 2 ) {
				$body			=	cbgjClass::getHTMLCleanParam( true, 'body' );
			} else {
				$body			=	cbgjClass::getCleanParam( true, 'body' );
			}

			if ( $editor == 3 ) {
				$input['body']	=	$_CB_framework->displayCmsEditor( 'body', $body, 400, 200, 40, 6 );
			} else {
				$input['body']	=	'<textarea id="body" name="body" class="input-xlarge required" cols="40" rows="6">' . htmlspecialchars( $body ) . '</textarea>';
			}

			if ( $plugin->params->get( 'group_message_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$input['captcha']	=	cbgjCaptcha::render();
			} else {
				$input['captcha']	=	false;
			}

			cbgjClass::displayMessage( $message );

			HTML_groupjiveGroupMessage::showGroupMessage( $row, $input, $category, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * send group message
	 *
	 * @param int $catid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function sendGroupMessage( $catid, $id, $user, $plugin ) {
		global $_PLUGINS;

		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$row					=	cbgjData::getGroups( array( array( 'grp_message' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $row->get( 'id' ) ) {
			$category			=	$row->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			$authorized			=	cbgjClass::getAuthorization( $category, $row, $user, $row->getOwner() );
			$subject			=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'subject' ) );

			if ( $plugin->params->get( 'group_message_editor', 1 ) >= 2 ) {
				$body			=	cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'body' ) );
			} else {
				$body			=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'body' ) );
			}

			if ( $subject == '' ) {
				$error			=	CBTxt::T( 'Subject not specified!' );
			} elseif ( $body == '' ) {
				$error			=	CBTxt::T( 'Body not specified!' );
			} elseif ( $plugin->params->get( 'group_message_captcha', 0 ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				$captcha		=	cbgjCaptcha::validate();

				if ( $captcha !== true ) {
					$error		=	CBTxt::T( $captcha );
				}
			}

			$users				=	array();
			$usrs				=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $row->get( 'id' ) ), array( 'status', '>=', 1 ) ) );

			if ( $usrs ) foreach ( $usrs as $usr ) {
				if ( ! in_array( $usr->get( 'user_id' ), $users ) ) {
					$users[]	=	$usr->get( 'user_id' );
				}
			}

			$_PLUGINS->trigger( 'gj_onBeforeMessageGroup', array( $row, $subject, $body, $users, $category, $user, $plugin ) );

			if ( isset( $error ) ) {
				$this->showGroupMessage( $catid, $id, $user, $plugin, CBTxt::P( '[group] message failed to save! Error: [error]', array( '[group]' => cbgjClass::getOverride( 'group' ), '[error]' => $error ) ) );
				return;
			}

			if ( $users ) {
				$msg_subject	=	CBTxt::P( '[site_name] - [msg_subject]', array( '[msg_subject]' => $subject ) );
				$msg_body		=	CBTxt::P( 'Hello [username], the following is a message from [group].<br /><br />[msg_body]', array( '[msg_body]' => $body ) );

				foreach ( $users as $user_id ) {
					cbgjClass::getNotification( $user_id, $user->id, $msg_subject, $msg_body, 2, $category, $row );
				}
			} else {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( '[group] message failed to send! Error: no [users] to message', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ), false, true, 'error' );
			}

			$_PLUGINS->trigger( 'gj_onAfterMessageGroup', array( $row, $subject, $body, $users, $category, $user, $plugin ) );

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( '[group] message sent successfully!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend nested groups render
	 *
	 * @param object $group
	 * @param object $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return object
	 */
	public function showNestedGroups( $group, $category, $user, $plugin ) {
		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		cbgjClass::getTemplate( 'group_nested' );

		$paging					=	new cbgjPaging( 'group_nested' );

		$limit					=	$paging->getlimit( (int) $plugin->params->get( 'group_nested_limit', 15 ) );
		$limitstart				=	$paging->getLimistart();
		$search					=	$paging->getFilter( 'search' );
		$where					=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]			=	array( 'name', 'CONTAINS', $search );
		}

		$searching				=	( count( $where ) ? true : false );

		$where[]				=	array( 'parent', '=', (int) $group->get( 'id' ) );

		switch( $plugin->params->get( 'group_nested_orderby', 1 ) ) {
			case 2:
				$orderBy		=	array( 'ordering', 'DESC' );
				break;
			case 3:
				$orderBy		=	array( 'date', 'ASC' );
				break;
			case 4:
				$orderBy		=	array( 'date', 'DESC' );
				break;
			case 5:
				$orderBy		=	array( 'name', 'ASC' );
				break;
			case 6:
				$orderBy		=	array( 'name', 'DESC' );
				break;
			case 7:
				$orderBy		=	'user_count_asc';
				break;
			case 8:
				$orderBy		=	'user_count_desc';
				break;
			case 9:
				$orderBy		=	'nested_count_asc';
				break;
			case 10:
				$orderBy		=	'nested_count_desc';
				break;
			default:
				$orderBy		=	null;
				break;
		}

		$total					=	count( cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy ) );

		if ( $total <= $limitstart ) {
			$limitstart			=	0;
		}

		$pageNav				=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, $orderBy, ( $plugin->params->get( 'group_nested_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search		=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [groups]...', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), $search );
		$pageNav->searching		=	$searching;
		$pageNav->limitbox		=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks		=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveNestedGroups::showNested( $rows, $pageNav, $category, $group, $user, $plugin );
	}

	/**
	 * prepare frontend users approval render
	 *
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return object
	 */
	public function showUsersApproval( $user, $plugin ) {
		cbgjClass::getTemplate( 'group_users_approval' );

		$paging				=	new cbgjPaging( 'users_approval' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'group_users_approval_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'd.id', '=', (int) $search, array( 'd.username', 'CONTAINS', $search ), array( 'd.name', 'CONTAINS', $search ) );
		}

		$searching			=	( count( $where ) ? true : false );

		$where[]			=	array( 'status', '=', 0, 'b.type', '=', 2 );

		$total				=	count( cbgjData::getUsers( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjData::getUsers( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), $where, null, ( $plugin->params->get( 'group_users_approval_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm', 'search', CBTxt::P( 'Search [users]...', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveUsersApproval::showUsersApproval( $rows, $pageNav, $user, $plugin );
	}

	/**
	 * prepare frontend users render
	 *
	 * @param object $group
	 * @param object $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return object
	 */
	public function showUsers( $group, $category, $user, $plugin ) {
		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category		=	$group->getCategory();
		}

		$authorized			=	cbgjClass::getAuthorization( $category, $group, $user );

		cbgjClass::getTemplate( 'group_users' );

		$paging				=	new cbgjPaging( 'users' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'group_users_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'd.id', '=', (int) $search, array( 'd.username', 'CONTAINS', $search ), array( 'd.name', 'CONTAINS', $search ) );
		}

		$searching			=	( count( $where ) ? true : false );

		if ( ! cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ) {
			$where[]		=	array( 'status', 'IN', array( 1, 2, 3, 4 ) );
		}

		$where[]			=	array( 'group', '=', (int) $group->get( 'id' ) );

		$total				=	count( cbgjData::getUsers( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjData::getUsers( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, null, ( $plugin->params->get( 'group_users_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm_users', 'search', CBTxt::P( 'Search [users]...', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveUsers::showUsers( $rows, $pageNav, $category, $group, $user, $plugin );
	}

	/**
	 * set user active status status
	 *
	 * @param int $status
	 * @param int $catid
	 * @param int $grpid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function statusUser( $status, $catid, $grpid, $id, $user, $plugin ) {
		$category			=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group				=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row				=	cbgjData::getUsers( array( array( 'mod_lvl4', 'grp_approved' ), $user, null, true ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group			=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category		=	$group->getCategory();
		}

		if ( $row->get( 'id' ) && ( $row->get( 'user_id' ) != $group->get( 'user_id' ) ) ) {
			$currentStatus	=	$row->get( 'status' );

			if ( ! $row->setStatus( $status ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( '[user] status failed to saved! Error: [error]', array( '[user]' => cbgjClass::getOverride( 'user' ), '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			if ( $status && ( $currentStatus == 0 ) && ( $user->id != $row->get( 'user_id' ) ) ) {
				$subject	=	CBTxt::T( '[group_name] - Join Request Accepted!' );
				$message	=	CBTxt::T( 'Your request to join [group] has been accepted!' );

				cbgjClass::getNotification( $row->get( 'user_id' ), $user->id, $subject, $message, 1, $category, $group );
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( '[user] status saved successfully!', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * delete user
	 *
	 * @param int $catid
	 * @param int $grpid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteUser( $catid, $grpid, $id, $user, $plugin ) {
		$category		=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group			=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row			=	cbgjData::getUsers( array( array( 'mod_lvl2', 'grp_approved' ), $user, null, true ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group		=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category	=	$group->getCategory();
		}

		if ( $row->get( 'id' ) && ( $row->get( 'user_id' ) != $group->get( 'user_id' ) ) ) {
			if ( ! $row->deleteAll() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( '[user] failed to delete! Error: [error]', array( '[user]' => cbgjClass::getOverride( 'user' ), '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( '[user] deleted successfully!', array( '[user]' => cbgjClass::getOverride( 'user' ) ) ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend invites render
	 *
	 * @param object $category
	 * @param object $group
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return mixed
	 */
	public function showInvites( $category, $group, $user, $plugin ) {
		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category		=	$group->getCategory();
		}

		cbgjClass::getTemplate( 'group_invites' );

		if ( $plugin->params->get( 'general_validate', 1 ) ) {
			cbgjClass::getFormValidation( '#gjForm_invite' );
		}

		$paging				=	new cbgjPaging( 'invites' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'group_invites_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	array( 'email', 'CONTAINS', $search, array( 'f.id', '=', (int) $search ), array( 'f.username', 'CONTAINS', $search ), array( 'f.name', 'CONTAINS', $search ) );
		}

		$searching			=	( count( $where ) ? true : false );

		$where[]			=	array( 'group', '=', (int) $group->get( 'id' ) );
		$where[]			=	array( 'user_id', '=', (int) $user->id );

		$total				=	count( cbgjData::getInvites( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where ) );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$rows				=	cbgjData::getInvites( array( array( 'grp_access', 'mod_lvl2' ), $user ), $where, null, ( $plugin->params->get( 'group_invites_paging', 1 ) ? array( $pageNav->limitstart, $pageNav->limit ) : null ) );

		$pageNav->search	=	$paging->getInputSearch( 'gjForm_invites', 'search', CBTxt::T( 'Search Invites...' ), $search );
		$pageNav->searching	=	$searching;
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		return HTML_groupjiveInvites::showInvites( $rows, $pageNav, $category, $group, $user, $plugin );
	}

	/**
	 * prepare frontend invites list
	 *
	 * @param int $catid
	 * @param int $grpid
	 * @param string $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function listInvites( $catid, $grpid, $id, $user, $plugin ) {
		$category			=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group				=	cbgjData::getGroups( array( array( 'grp_invite', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category		=	$group->getCategory();
		}

		if ( $group->get( 'id' ) ) {
			cbgjClass::getTemplate( 'group_invites_list' );

			$userIds		=	explode( ',', $id );

			cbArrayToInts( $userIds );

			$userIds		=	array_unique( array_filter( $userIds ) );
			$rows			=	array();

			if ( $userIds ) foreach ( $userIds as $userId ) {
				$row		=&	CBuser::getUserDataInstance( (int) $userId );

				if ( $row->id ) {
					$rows[]	=	$row;
				}
			}

			HTML_groupjiveInvitesList::showInvitesList( $rows, $category, $group, $user, $plugin );
		} else {
			if ( $category->get( 'id' ) ) {
				$url		=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url		=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * send group invite
	 *
	 * @param int $catid
	 * @param int $grpid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function sendInvite( $catid, $grpid, $id, $user, $plugin ) {
		global $_CB_database;

		$category							=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group								=	cbgjData::getGroups( array( array( 'grp_invite', 'grp_approved' ), $user, null, true ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row								=	cbgjData::getInvites( array( array( 'mod_lvl2', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group							=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category						=	$group->getCategory();
		}

		if ( $group->get( 'id' ) ) {
			$authorized						=	cbgjClass::getAuthorization( $category, $group, $user, $row->getOwner() );

			if ( $row->get( 'id' ) ) {
				if ( ! ( $row->dateDifference() >= 5 ) ) {
					$row->set( '_error', CBTxt::T( 'You must wait 5 days before resending an invite!' ) );
				}
			} else {
				$invite						=	cbgjClass::getCleanParam( true, 'invites_invite' );
				$inviteList					=	cbgjClass::getCleanParam( true, 'invites_list', null, 0 );
				$inviteBy					=	explode( '|*|', $plugin->params->get( 'group_invites_by', '1|*|2|*|3|*|4' ) );

				if ( $plugin->params->get( 'group_invites_captcha', 0 ) && ( ! $inviteList ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
					$captcha				=	cbgjCaptcha::validate();

					if ( $captcha !== true ) {
						$invalid			=	$captcha;
					}
				}

				if ( ( in_array( 1, $inviteBy ) || $inviteList ) && preg_match( '!^\d+$!', $invite ) ) {
					$query					=	'SELECT ' . $_CB_database->NameQuote( 'user_id' )
											.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler' )
											.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $invite;
					$_CB_database->setQuery( $query );
					$userId					=	$_CB_database->loadResult();

					if ( $userId ) {
						$row->set( 'user', (int) $userId );
					}
				} elseif ( in_array( 4, $inviteBy ) && cbIsValidEmail( $invite ) ) {
					$query					=	'SELECT a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler' ). " AS a"
											.	"\n INNER JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS b"
											.	' ON a.' . $_CB_database->NameQuote( 'user_id' ) . ' = b.' . $_CB_database->NameQuote( 'id' )
											.	"\n WHERE b." . $_CB_database->NameQuote( 'email' ) . " = " . $_CB_database->Quote( $invite );
					$_CB_database->setQuery( $query );
					$userId					=	$_CB_database->loadResult();

					if ( $userId ) {
						$row->set( 'user', (int) $userId );
					} else {
						$row->set( 'email', $invite );
					}
				} elseif ( $invite ) {
					if ( in_array( 2, $inviteBy ) ) {
						$query				=	'SELECT a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler' ). " AS a"
											.	"\n INNER JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS b"
											.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n WHERE b." . $_CB_database->NameQuote( 'username' ) . " LIKE " . $_CB_database->Quote( '%' . $_CB_database->getEscaped( $invite, true ) . '%', false )
											.	"\n AND a." . $_CB_database->NameQuote( 'user_id' ) . " NOT IN ( SELECT " . $_CB_database->NameQuote( 'user_id' ) . " FROM " . $_CB_database->NameQuote( '#__groupjive_users' ) . " WHERE " . $_CB_database->NameQuote( 'group' ) . " = " . (int) $group->get( 'id' ) . " )"
											.	"\n AND a." . $_CB_database->NameQuote( 'user_id' ) . " NOT IN ( SELECT " . $_CB_database->NameQuote( 'user' ) . " FROM " . $_CB_database->NameQuote( '#__groupjive_invites' ) . " WHERE " . $_CB_database->NameQuote( 'group' ) . " = " . (int) $group->get( 'id' ) . " )"
											.	"\n AND a." . $_CB_database->NameQuote( 'user_id' ) . " != " . (int) $group->get( 'user_id' );
						$_CB_database->setQuery( $query );
						$userId				=	$_CB_database->loadResultArray();

						if ( count( $userId ) > 1 ) {
							if ( ! isset( $invalid ) ) {
								cbArrayToInts( $userId );

								$userIds	=	implode( ',', $userId );

								cbgjClass::getPluginURL( array( 'invites', 'list', (int) $category->get( 'id' ), (int) $group->get( 'id' ), $userIds ), CBTxt::P( 'Multiple [users] were found. Please specify which to invite.', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ), false, true, 'error' );
							}
						} elseif ( count( $userId ) == 1 ) {
							$row->set( 'user', (int) $userId[0] );
						} else {
							$query			=	'SELECT a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler' ). " AS a"
											.	"\n INNER JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS b"
											.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n WHERE b." . $_CB_database->NameQuote( 'username' ) . " = " . $_CB_database->Quote( $invite );
							$_CB_database->setQuery( $query );
							$userId			=	$_CB_database->loadResult();

							if ( $userId ) {
								$row->set( 'user', (int) $userId );
							}
						}
					}

					if ( in_array( 3, $inviteBy ) && ( ! isset( $userId ) ) ) {
						$query				=	'SELECT a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler' ). " AS a"
											.	"\n INNER JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS b"
											.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n WHERE b." . $_CB_database->NameQuote( 'name' ) . " LIKE " . $_CB_database->Quote( '%' . $_CB_database->getEscaped( $invite, true ) . '%', false )
											.	"\n AND a." . $_CB_database->NameQuote( 'user_id' ) . " NOT IN ( SELECT " . $_CB_database->NameQuote( 'user_id' ) . " FROM " . $_CB_database->NameQuote( '#__groupjive_users' ) . " WHERE " . $_CB_database->NameQuote( 'group' ) . " = " . (int) $group->get( 'id' ) . " )"
											.	"\n AND a." . $_CB_database->NameQuote( 'user_id' ) . " NOT IN ( SELECT " . $_CB_database->NameQuote( 'user' ) . " FROM " . $_CB_database->NameQuote( '#__groupjive_invites' ) . " WHERE " . $_CB_database->NameQuote( 'group' ) . " = " . (int) $group->get( 'id' ) . " )"
											.	"\n AND a." . $_CB_database->NameQuote( 'user_id' ) . " != " . (int) $group->get( 'user_id' );
						$_CB_database->setQuery( $query );
						$userId				=	$_CB_database->loadResultArray();

						if ( count( $userId ) > 1 ) {
							if ( ! isset( $invalid ) ) {
								cbArrayToInts( $userId );

								$userIds	=	implode( ',', $userId );

								cbgjClass::getPluginURL( array( 'invites', 'list', (int) $category->get( 'id' ), (int) $group->get( 'id' ), $userIds ), CBTxt::P( 'Multiple [users] were found. Please specify which to invite.', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ), false, true, 'error' );
							}
						} elseif ( count( $userId ) == 1 ) {
							$row->set( 'user', (int) $userId[0] );
						} else {
							$query			=	'SELECT a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler' ). " AS a"
											.	"\n INNER JOIN " . $_CB_database->NameQuote( '#__users' ) . " AS b"
											.	' ON b.' . $_CB_database->NameQuote( 'id' ) . ' = a.' . $_CB_database->NameQuote( 'user_id' )
											.	"\n WHERE b." . $_CB_database->NameQuote( 'name' ) . " = " . $_CB_database->Quote( $invite );
							$_CB_database->setQuery( $query );
							$userId			=	$_CB_database->loadResult();

							if ( $userId ) {
								$row->set( 'user', (int) $userId );
							}
						}
					}
				}

				if ( $row->get( 'user' ) ) {
					$exists					=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'user_id', '=', (int) $row->get( 'user' ) ) ), null, null, false );
					$invited				=	cbgjData::getInvites( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'user', '=', (int) $row->get( 'user' ) ) ), null, null, false );

					if ( $row->get( 'user' ) == $user->id ) {
						$row->set( '_error', CBTxt::T( 'You can not invite your self!' ) );
					} elseif ( $row->get( 'user' ) == $group->get( 'user_id' ) ) {
						$row->set( '_error', CBTxt::P( 'You can not invite the [group] [owner]!', array( '[group]' => cbgjClass::getOverride( 'group' ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) );
					} elseif ( $exists->get( 'id' ) ) {
						$row->set( '_error', CBTxt::P( 'The [user] you are inviting already belongs to this [group]!', array( '[user]' => cbgjClass::getOverride( 'user' ), '[group]' => cbgjClass::getOverride( 'group' ) ) ) );
					} elseif ( $invited->get( 'id' ) ) {
						$row->set( '_error', CBTxt::P( 'The [user] you are inviting is already invited to join this [group]!', array( '[user]' => cbgjClass::getOverride( 'user' ), '[group]' => cbgjClass::getOverride( 'group' ) ) ) );
					}
				} elseif ( $row->get( 'email' ) ) {
					$invited				=	cbgjData::getInvites( null, array( array( 'group', '=', (int) $group->get( 'id' ) ), array( 'email', '=', $row->get( 'email' ) ) ), null, null, false );

					if ( $invited->get( 'id' ) ) {
						$row->set( '_error', CBTxt::P( 'The email you are inviting is already invited to join this [group]!', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) );
					}
				} else {
					$row->set( '_error', CBTxt::T( 'Invalid invite recipient!' ) );
				}

				if ( isset( $invalid ) ) {
					$row->set( '_error', CBTxt::T( $invalid ) );
				}

				$row->set( 'user_id', (int) $user->id );
				$row->set( 'group', (int) $group->get( 'id' ) );
				$row->set( 'code', uniqid( (int) $category->get( 'id' ) . (int) $group->get( 'id' ) . (int) $row->get( 'user_id' ) . (int) $row->get( 'user' ) ) );
			}

			$row->set( 'invited', cbgjClass::getUTCDate() );

			if ( $row->getError() || ( ! $row->store() ) ) {
				$this->showGroup( $category->get( 'id' ), $group->get( 'id' ), $user, $plugin, CBTxt::P( 'Invite failed to send! Error: [error]', array( '[error]' => $row->getError() ) ), '#gjForm_invite' );
				return;
			}

			$msgLink						=	'<a href="' . cbgjClass::getPluginURL( array( 'groups', 'join', (int) $category->get( 'id' ), (int) $group->get( 'id' ), $row->get( 'code' ) ) ) . '">' . CBTxt::T( 'here' ) . '</a>';

			if ( $row->get( 'user' ) ) {
				$to							=	(int) $row->get( 'user' );
				$subject					=	CBTxt::T( '[group_override] Invite' );
				$message					=	CBTxt::P( 'Hello [username], you were invited to join [group] in [category]. Click [invite_link] to join this [group_override]!', array( '[invite_link]' => $msgLink ) );
			} else {
				$to							=	$row->get( 'email' );
				$subject					=	CBTxt::T( '[site_name] - [group_override] Invite' );
				$message					=	CBTxt::P( 'Hello, you were invited to join [group] in [category]. Click [invite_link] to join this [group_override]!<br /><br />NOTE: This message was automatically generated from [site]. You should register with the address at which you received this invite.', array( '[invite_link]' => $msgLink ) );
			}

			cbgjClass::getNotification( $to, $row->get( 'user_id' ), $subject, $message, ( $row->get( 'user' ) ? 2 : 1 ), $category, $group );

			$notifications					=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $group->get( 'id' ) ), array( 'user_id', '!IN', array( (int) $user->id, (int) $row->get( 'user_id' ) ) ), array( 'params', 'CONTAINS', 'group_userinvite=1' ) ) );

			if ( $notifications ) {
				$subject					=	CBTxt::T( '[group_name] - [user_override] Invited!' );

				if ( $row->get( 'user' ) ) {
					$message				=	CBTxt::P( '[user] has invited [invite_user] to join [group] in [category]!', array( '[invite_user]' => $row->getInvitedName( true ) ) );
				} else {
					$message				=	CBTxt::P( '[user] has invited [invite_email] to join [group] in [category]!', array( '[invite_email]' => $row->get( 'email' ) ) );
				}

				foreach ( $notifications as $notification ) {
					cbgjClass::getNotification( $notification->get( 'user_id' ), $row->get( 'user_id' ), $subject, $message, 1, $category, $group );
				}
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Invite sent successfully!' ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * delete invite
	 *
	 * @param int $catid
	 * @param int $grpid
	 * @param int $id
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	private function deleteInvite( $catid, $grpid, $id, $user, $plugin ) {
		$category		=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group			=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );
		$row			=	cbgjData::getInvites( array( array( 'mod_lvl2', 'usr_me' ), $user ), array( 'id', '=', (int) $id ), null, null, false );

		if ( ( ! $group->get( 'id' ) ) && $row->get( 'id' ) ) {
			$group		=	$row->getGroup();
		}

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category	=	$group->getCategory();
		}

		if ( $row->get( 'id' ) ) {
			if ( ! $row->delete() ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'Invite failed to delete! Error: [error]', array( '[error]' => $row->getError() ) ), false, true, 'error' );
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'Invite deleted successfully!' ), false, true, null, false, false, true );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	/**
	 * prepare frontend notifications render
	 *
	 * @param int $catid
	 * @param int $grpid
	 * @param moscomprofilerUser $user
	 * @param moscomprofilerPlugin $plugin
	 * @param mixed $message
	 */
	public function showNotifications( $catid, $grpid, $user, $plugin, $message = null ) {
		$category								=	cbgjData::getCategories( null, array( 'id', '=', (int) $catid ), null, null, false );
		$group									=	cbgjData::getGroups( null, array( 'id', '=', (int) $grpid ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category							=	$group->getCategory();
		}

		$authorized								=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'usr_notifications', $authorized ) ) {
			$categoryApprove					=	$plugin->params->get( 'category_approve', 0 );
			$groupApprove						=	$plugin->params->get( 'group_approve', 0 );

			cbgjClass::getTemplate( 'notifications' );

			$generalNotifications				=	cbgjData::getNotifications( array( array( 'usr_reg' ), $user ), array( array( 'type', '=', 'general' ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );
			$generalParams						=	$generalNotifications->getParams();
			$categoryNotifications				=	cbgjData::getNotifications( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( array( 'type', '=', 'category' ), array( 'item', '=', (int) $catid ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );
			$categoryParams						=	$categoryNotifications->getParams();
			$groupNotifications					=	cbgjData::getNotifications( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $grpid ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );
			$groupParams						=	$groupNotifications->getParams();

			$input								=	array();

			$input['general_categorynew']		=	moscomprofilerHTML::yesnoSelectList( 'general_categorynew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ? cbgjClass::getCleanParam( true, 'general_categorynew', $generalParams->get( 'general_categorynew', $plugin->params->get( 'notifications_general_categorynew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['general_categoryapprove']	=	moscomprofilerHTML::yesnoSelectList( 'general_categoryapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'usr_mod', $authorized ) && $categoryApprove ? cbgjClass::getCleanParam( true, 'general_categoryapprove', $generalParams->get( 'general_categoryapprove', $plugin->params->get( 'notifications_general_categoryapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['general_categoryupdate']	=	moscomprofilerHTML::yesnoSelectList( 'general_categoryupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ? cbgjClass::getCleanParam( true, 'general_categoryupdate', $generalParams->get( 'general_categoryupdate', $plugin->params->get( 'notifications_general_categoryupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['general_categorydelete']	=	moscomprofilerHTML::yesnoSelectList( 'general_categorydelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ? cbgjClass::getCleanParam( true, 'general_categorydelete', $generalParams->get( 'general_categorydelete', $plugin->params->get( 'notifications_general_categorydelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

			$input['category_nestednew']		=	moscomprofilerHTML::yesnoSelectList( 'category_nestednew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_nestednew', $categoryParams->get( 'category_nestednew', $plugin->params->get( 'notifications_category_nestednew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['category_nestedapprove']	=	moscomprofilerHTML::yesnoSelectList( 'category_nestedapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_nested' ), $authorized, true ) && $categoryApprove ? cbgjClass::getCleanParam( true, 'category_nestedapprove', $categoryParams->get( 'category_nestedapprove', $plugin->params->get( 'notifications_category_nestedapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['category_nestedupdate']		=	moscomprofilerHTML::yesnoSelectList( 'category_nestedupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_nestedupdate', $categoryParams->get( 'category_nestedupdate', $plugin->params->get( 'notifications_category_nestedupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['category_nesteddelete']		=	moscomprofilerHTML::yesnoSelectList( 'category_nesteddelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_nesteddelete', $categoryParams->get( 'category_nesteddelete', $plugin->params->get( 'notifications_category_nesteddelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['category_groupnew']			=	moscomprofilerHTML::yesnoSelectList( 'category_groupnew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_create' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_groupnew', $categoryParams->get( 'category_groupnew', $plugin->params->get( 'notifications_category_groupnew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['category_groupapprove']		=	moscomprofilerHTML::yesnoSelectList( 'category_groupapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_create' ), $authorized, true ) && $groupApprove ? cbgjClass::getCleanParam( true, 'category_groupapprove', $categoryParams->get( 'category_groupapprove', $plugin->params->get( 'notifications_category_groupapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['category_groupupdate']		=	moscomprofilerHTML::yesnoSelectList( 'category_groupupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_create' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_groupupdate', $categoryParams->get( 'category_groupupdate', $plugin->params->get( 'notifications_category_groupupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['category_groupdelete']		=	moscomprofilerHTML::yesnoSelectList( 'category_groupdelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl1', 'grp_create' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'category_groupdelete', $categoryParams->get( 'category_groupdelete', $plugin->params->get( 'notifications_category_groupdelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

			$input['group_nestednew']			=	moscomprofilerHTML::yesnoSelectList( 'group_nestednew', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_nestednew', $groupParams->get( 'group_nestednew', $plugin->params->get( 'notifications_group_nestednew', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_nestedapprove']		=	moscomprofilerHTML::yesnoSelectList( 'group_nestedapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_nested' ), $authorized, true ) && $groupApprove ? cbgjClass::getCleanParam( true, 'group_nestedapprove', $groupParams->get( 'group_nestedapprove', $plugin->params->get( 'notifications_group_nestedapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_nestedupdate']		=	moscomprofilerHTML::yesnoSelectList( 'group_nestedupdate', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_nestedupdate', $groupParams->get( 'group_nestedupdate', $plugin->params->get( 'notifications_group_nestedupdate', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_nesteddelete']		=	moscomprofilerHTML::yesnoSelectList( 'group_nesteddelete', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( array( 'mod_lvl2', 'grp_nested' ), $authorized, true ) ? cbgjClass::getCleanParam( true, 'group_nesteddelete', $groupParams->get( 'group_nesteddelete', $plugin->params->get( 'notifications_group_nesteddelete', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_userjoin']			=	moscomprofilerHTML::yesnoSelectList( 'group_userjoin', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? cbgjClass::getCleanParam( true, 'group_userjoin', $groupParams->get( 'group_userjoin', $plugin->params->get( 'notifications_group_userjoin', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_userleave']			=	moscomprofilerHTML::yesnoSelectList( 'group_userleave', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? cbgjClass::getCleanParam( true, 'group_userleave', $groupParams->get( 'group_userleave', $plugin->params->get( 'notifications_group_userleave', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_userinvite']			=	moscomprofilerHTML::yesnoSelectList( 'group_userinvite', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ? cbgjClass::getCleanParam( true, 'group_userinvite', $groupParams->get( 'group_userinvite', $plugin->params->get( 'notifications_group_userinvite', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_userapprove']			=	moscomprofilerHTML::yesnoSelectList( 'group_userapprove', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'mod_lvl4', $authorized ) ? cbgjClass::getCleanParam( true, 'group_userapprove', $groupParams->get( 'group_userapprove', $plugin->params->get( 'notifications_group_userapprove', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );
			$input['group_inviteaccept']		=	moscomprofilerHTML::yesnoSelectList( 'group_inviteaccept', 'class="input-medium"', ( (int) ( cbgjClass::hasAccess( 'grp_invite', $authorized ) ? cbgjClass::getCleanParam( true, 'group_inviteaccept', $groupParams->get( 'group_inviteaccept', $plugin->params->get( 'notifications_group_inviteaccept', 0 ) ) ) : 0 ) ), CBTxt::T( 'Notify' ), CBTxt::T( 'Don\'t Notify' ) );

			cbgjClass::displayMessage( $message );

			HTML_groupjiveNotifications::showNotifications( $input, $generalNotifications, $categoryNotifications, $groupNotifications, $category, $group, $user, $plugin );
		} else {
			cbgjClass::getPluginURL( array( 'overview' ), CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}
}
?>