<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerFunction( 'gj_onAfterTools', 'getTools', 'cbgjForumsModel' );
$_PLUGINS->registerFunction( 'gj_onPluginBE', 'fixForum', 'cbgjForumsModel' );
$_PLUGINS->registerFunction( 'kunenaIntegration', 'getAccess', 'cbgjForumsModel' );

class cbgjForumsModel extends cbPluginHandler {

	static public function getForum( $tabs, $row, $user, $plugin ) {
		global $_CB_database;

		$params				=	$row->getParams();
		$forumId			=	$params->get( 'forum_id', null );

		cbgjClass::getTemplate( 'cbgroupjiveforums' );

		$paging				=	new cbgjPaging( 'forum' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'forum_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	'( a.' . $_CB_database->NameQuote( 'subject' ) . ' LIKE ' . $_CB_database->Quote( '%' . $_CB_database->getEscaped( $search, true ) . '%', false )
							.	' OR b.' . $_CB_database->NameQuote( 'message' ) . ' LIKE ' . $_CB_database->Quote( '%' . $_CB_database->getEscaped( $search, true ) . '%', false ) . ' )';
		}

		$query				=	'SELECT COUNT(*)'
							.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_messages' ) . " AS a"
							.	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__kunena_messages_text' ) . " AS b"
							.	' ON a.' . $_CB_database->NameQuote( 'id' ) . ' = b.' . $_CB_database->NameQuote( 'mesid' )
							.	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__kunena_categories' ) . " AS c"
							.	' ON a.' . $_CB_database->NameQuote( 'catid' ) . ' = c.' . $_CB_database->NameQuote( 'id' )
							.	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__kunena_messages' ) . " AS d"
							.	' ON a.' . $_CB_database->NameQuote( 'thread' ) . ' = d.' . $_CB_database->NameQuote( 'id' )
							.	"\n WHERE a." . $_CB_database->NameQuote( 'hold' ) . " = 0"
							.	"\n AND a." . $_CB_database->NameQuote( 'catid' ) . " = " . (int) $forumId
							.	"\n AND c." . $_CB_database->NameQuote( 'published' ) . " = 1"
							.	( count( $where ) ? "\n AND " . implode( "\n AND ", $where ) : null );
		$_CB_database->setQuery( $query );
		$total				=	$_CB_database->loadResult();

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		$query				=	'SELECT a.*'
							.	', c.' . $_CB_database->NameQuote( 'id' ) . ' AS category'
							.	', c.' . $_CB_database->NameQuote( 'name' ) . ' AS catname'
							.	', b.' . $_CB_database->NameQuote( 'message' )
							.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_messages' ) . " AS a"
							.	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__kunena_messages_text' ) . " AS b"
							.	' ON a.' . $_CB_database->NameQuote( 'id' ) . ' = b.' . $_CB_database->NameQuote( 'mesid' )
							.	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__kunena_categories' ) . " AS c"
							.	' ON a.' . $_CB_database->NameQuote( 'catid' ) . ' = c.' . $_CB_database->NameQuote( 'id' )
							.	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__kunena_messages' ) . " AS d"
							.	' ON a.' . $_CB_database->NameQuote( 'thread' ) . ' = d.' . $_CB_database->NameQuote( 'id' )
							.	"\n WHERE a." . $_CB_database->NameQuote( 'hold' ) . " = 0"
							.	"\n AND a." . $_CB_database->NameQuote( 'catid' ) . " = " . (int) $forumId
							.	"\n AND c." . $_CB_database->NameQuote( 'published' ) . " = 1"
							.	( count( $where ) ? "\n AND " . implode( "\n AND ", $where ) : null )
							.	"\n ORDER BY a." . $_CB_database->NameQuote( 'time' ) . " DESC";
		if ( $plugin->params->get( 'forum_paging', 1 ) ) {
			$_CB_database->setQuery( $query, (int) $pageNav->limitstart, (int) $pageNav->limit );
		} else {
			$_CB_database->setQuery( $query );
		}
		$rows				=	$_CB_database->loadObjectList();

		$pageNav->search	=	$paging->getInputSearch( 'gjForm_forum', 'search', CBTxt::T( 'Search Forums...' ), $search );
		$pageNav->searching	=	( $search ? true : false );
		$pageNav->limitbox	=	$paging->getLimitbox( $pageNav );
		$pageNav->pagelinks	=	$paging->getPagesLinks( $pageNav );

		if ( class_exists( 'HTML_cbgroupjiveforums' ) ) {
			return HTML_cbgroupjiveforums::showForums( $rows, $pageNav, $tabs, $row, $user, $plugin );
		} else {
			return cbgjForumsPlugin::showForum( $rows, $pageNav, $tabs, $row, $user, $plugin );
		}
	}

	static public function setForum( $parentForum, $row, $user, $plugin ) {
		global $_CB_database;

		$forum					=	null;

		if ( $parentForum ) {
			if ( method_exists( $row, 'getCategory' ) ) {
				$authorized		=	cbgjClass::getAuthorization( $row->getCategory(), $row, $user );
			} else {
				$authorized		=	cbgjClass::getAuthorization( $row, null, $user );
			}

			$params				=	$row->getParams();
			$forumShow			=	(int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_show', $params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) ) );

			$query				=	'INSERT INTO ' . $_CB_database->NameQuote( '#__kunena_categories' )
								.	"\n ("
								.		$_CB_database->NameQuote( 'parent' )
								.		', ' . $_CB_database->NameQuote( 'name' )
								.		', ' . $_CB_database->NameQuote( 'accesstype' )
								.		', ' . $_CB_database->NameQuote( 'access' )
								.		', ' . $_CB_database->NameQuote( 'pub_access' )
								.		', ' . $_CB_database->NameQuote( 'pub_recurse' )
								.		', ' . $_CB_database->NameQuote( 'admin_access' )
								.		', ' . $_CB_database->NameQuote( 'admin_recurse' )
								.		', ' . $_CB_database->NameQuote( 'published' )
								.		', ' . $_CB_database->NameQuote( 'description' )
								.	')'
								.	"\n VALUES ("
								.		(int) $parentForum
								.		', ' . $_CB_database->Quote( str_replace( '&amp;', '&', $row->getName() ) )
								.		', ' . $_CB_database->Quote( 'communitybuilder' )
								.		', 0'
								.		', 1'
								.		', 1'
								.		', 0'
								.		', 1'
								.		', ' . (int) ( ! $forumShow ? 0 : $row->get( 'published' ) )
								.		', ' . $_CB_database->Quote( $row->getDescription() )
								.	')';
			$_CB_database->setQuery( $query );
			if ( ! $_CB_database->query() ) {
				trigger_error( CBTxt::P( '[element] - setForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $_CB_database->stderr( true ) ) ) );
			} else {
				$forum			=	$_CB_database->insertid();

				$params->set( 'forum_id', (int) $forum );
				$params->set( 'forum_show', (int) $forumShow );
				$params->set( 'forum_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_public', $params->get( 'forum_public', $plugin->params->get( 'forum_public', 1 ) ) ) );

				if ( ! $row->storeParams( $params ) ) {
					trigger_error( CBTxt::P( '[element] - setForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $row->getError() ) ) );
				}

				cbgjForumsModel::setModerator( $row->get( 'user_id' ), $row );
			}
		}

		return $forum;
	}

	static public function setModerator( $userId, $row ) {
		global $_CB_database;

		$params			=	$row->getParams();
		$forumId		=	$params->get( 'forum_id', null );

		if ( ( ! $userId ) || ( ! $forumId ) ) {
			return;
		}

		$query			=	'SELECT *'
						.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_moderation' )
						.	"\n WHERE " . $_CB_database->NameQuote( 'catid' ) . " = " . (int) $forumId
						.	"\n AND " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $userId;
		$_CB_database->setQuery( $query );
		$moderator		=	$_CB_database->loadResult();

		if ( ! $moderator ) {
			$query		=	'INSERT INTO ' . $_CB_database->NameQuote( '#__kunena_moderation' )
						.	"\n ("
						.		$_CB_database->NameQuote( 'catid' )
						.		', ' . $_CB_database->NameQuote( 'userid' )
						.	')'
						.	"\n VALUES ("
						.		(int) $forumId
						.		', ' . (int) $userId
						.	')';
			$_CB_database->setQuery( $query );
			if ( $_CB_database->query() ) {
				$query	=	'UPDATE ' . $_CB_database->NameQuote( '#__kunena_users' )
						.	"\n SET " . $_CB_database->NameQuote( 'moderator' ) . " = 1"
						.	"\n WHERE " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $userId;
				$_CB_database->setQuery( $query );
				$_CB_database->query();
			}
		}
	}

	static public function unsetModerator( $userId, $row ) {
		global $_CB_database;

		$params				=	$row->getParams();
		$forumId			=	$params->get( 'forum_id', null );

		if ( ( ! $userId ) || ( ! $forumId ) ) {
			return;
		}

		$query				=	'SELECT *'
							.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_moderation' )
							.	"\n WHERE " . $_CB_database->NameQuote( 'catid' ) . " = " . (int) $forumId
							.	"\n AND " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $userId;
		$_CB_database->setQuery( $query );
		$moderator			=	$_CB_database->loadResult();

		if ( $moderator ) {
			$query			=	'DELETE'
							.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_moderation' )
							.	"\n WHERE " . $_CB_database->NameQuote( 'catid' ) . " = " . (int) $forumId
							.	"\n AND " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $userId;
			$_CB_database->setQuery( $query );
			if ( $_CB_database->query() ) {
				$query		=	'SELECT COUNT(*)'
							.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_moderation' )
							.	"\n WHERE " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $userId;
				$_CB_database->setQuery( $query );
				$ismod		=	$_CB_database->loadResult();

				if ( ! $ismod ) {
					$query	=	'UPDATE ' . $_CB_database->NameQuote( '#__kunena_users' )
							.	"\n SET " . $_CB_database->NameQuote( 'moderator' ) . " = 0"
							.	"\n WHERE " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $userId;
					$_CB_database->setQuery( $query );
					$_CB_database->query();
				}
			}
		}
	}

	static public function unsetSession( $userId ) {
		global $_CB_database;

		if ( ! $userId  ) {
			return;
		}

		$query		=	'SELECT *'
					.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_sessions' )
					.	"\n WHERE " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $userId;
		$_CB_database->setQuery( $query );
		$session	=	$_CB_database->loadResult();

		if ( $session ) {
			$query	=	'UPDATE ' . $_CB_database->NameQuote( '#__kunena_sessions' )
					.	"\n SET " . $_CB_database->NameQuote( 'allowed' ) . " = " . $_CB_database->Quote( 'na' )
					.	"\n WHERE " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $userId;
			$_CB_database->setQuery( $query );
			$_CB_database->query();
		}
	}

	static public function saveForum( $parentForum, $row, $user, $plugin ) {
		global $_CB_database;

		if ( $parentForum ) {
			if ( method_exists( $row, 'getCategory' ) ) {
				$authorized			=	cbgjClass::getAuthorization( $row->getCategory(), $row, $user );
			} else {
				$authorized			=	cbgjClass::getAuthorization( $row, null, $user );
			}

			$params					=	$row->getParams();
			$forum					=	$params->get( 'forum_id', null );

			if ( ! $forum ) {
				$forum				=	cbgjForumsModel::setForum( $parentForum, $row, $user, $plugin );
			} else {
				$query				=	'SELECT *'
									.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_categories' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $forum;
				$_CB_database->setQuery( $query );
				$exists				=	$_CB_database->loadResult();

				if ( ! $exists ) {
					$forum			=	cbgjForumsModel::setForum( $parentForum, $row, $user, $plugin );
				}
			}

			if ( $forum ) {
				$forumShow			=	(int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_show', $params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) ) );

				$query				=	'UPDATE ' . $_CB_database->NameQuote( '#__kunena_categories' )
									.	"\n SET " . $_CB_database->NameQuote( 'parent' ) . " = " . (int) $parentForum
									.	', ' . $_CB_database->NameQuote( 'name' ) . " = " . $_CB_database->Quote( str_replace( '&amp;', '&', $row->getName() ) )
									.	', ' . $_CB_database->NameQuote( 'accesstype' ) . " = " . $_CB_database->Quote( 'communitybuilder' )
									.	', ' . $_CB_database->NameQuote( 'access' ) . " = 0"
									.	', ' . $_CB_database->NameQuote( 'pub_access' ) . " = 1"
									.	', ' . $_CB_database->NameQuote( 'pub_recurse' ) . " = 1"
									.	', ' . $_CB_database->NameQuote( 'admin_access' ) . " = 0"
									.	', ' . $_CB_database->NameQuote( 'admin_recurse' ) . " = 1"
									.	', ' . $_CB_database->NameQuote( 'published' ) . " = " . (int) ( ! $forumShow ? 0 : $row->get( 'published' ) )
									.	', ' . $_CB_database->NameQuote( 'description' ) . " = " . $_CB_database->Quote( $row->getDescription() )
									.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $forum;
				$_CB_database->setQuery( $query );
				if ( ! $_CB_database->query() ) {
					trigger_error( CBTxt::P( '[element] - saveForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $_CB_database->stderr( true ) ) ) );
				} else {
					$params->set( 'forum_id', (int) $forum );
					$params->set( 'forum_show', (int) $forumShow );
					$params->set( 'forum_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_public', $params->get( 'forum_public', $plugin->params->get( 'forum_public', 1 ) ) ) );

					if ( ! $row->storeParams( $params ) ) {
						trigger_error( CBTxt::P( '[element] - saveForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $row->getError() ) ) );
					}

					cbgjForumsModel::setModerator( $row->get( 'user_id' ), $row );
				}
			}
		}
	}

	static public function stateForum( $row, $user, $plugin ) {
		global $_CB_database;

		$params				=	$row->getParams();
		$forumId			=	$params->get( 'forum_id', null );

		if ( $forumId ) {
			if ( method_exists( $row, 'getCategory' ) ) {
				$authorized	=	cbgjClass::getAuthorization( $row->getCategory(), $row, $user );
			} else {
				$authorized	=	cbgjClass::getAuthorization( $row, null, $user );
			}

			$forumShow		=	(int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_show', $params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) ) );

			$query			=	'UPDATE ' . $_CB_database->NameQuote( '#__kunena_categories' )
							.	"\n SET " . $_CB_database->NameQuote( 'published' ) . " = " . (int) ( ! $forumShow ? 0 : $row->get( 'published' ) )
							.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $forumId;
			$_CB_database->setQuery( $query );
			$_CB_database->query();
		}
	}

	static public function deleteAllForums( $row, $user, $plugin ) {
		global $_CB_database;

		$params			=	$row->getParams();
		$forumId		=	$params->get( 'forum_id', null );

		if ( $forumId ) {
			$query		=	'SELECT ' . $_CB_database->NameQuote( 'id' )
						.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_categories' )
						.	"\n WHERE " . $_CB_database->NameQuote( 'parent' ) . " = " . (int) $forumId;
			$_CB_database->setQuery( $query );
			$subForums	=	$_CB_database->loadResultArray();

			if ( $subForums ) foreach ( $subForums as $subForum ) {
				cbgjForumsModel::deleteForum( $subForum, $user, $plugin );
			}

			cbgjForumsModel::deleteForum( $forumId, $user, $plugin );
		}
	}

	static public function deleteForum( $forum, $user, $plugin ) {
		global $_CB_database;

		if ( $forum ) {
			$query					=	'DELETE'
									.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_categories' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $forum;
			$_CB_database->setQuery( $query );
			if ( ! $_CB_database->query() ) {
				trigger_error( CBTxt::P( '[element] - deleteForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $_CB_database->stderr( true ) ) ) );
			} else {
				$query				=	'DELETE'
									.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_moderation' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'catid' ) . " = " . (int) $forum;
				$_CB_database->setQuery( $query );
				if ( ! $_CB_database->query() ) {
					trigger_error( CBTxt::P( '[element] - deleteForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $_CB_database->stderr( true ) ) ) );
				} else {
					$query			=	'SELECT ' . $_CB_database->NameQuote( 'id' )
									.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_messages' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'catid' ) . " = " . (int) $forum;
					$_CB_database->setQuery( $query );
					$posts			=	$_CB_database->loadResultArray();

					cbArrayToInts( $posts );

					$posts			=	implode( ',', $posts );

					if ( $posts ) {
						$query		=	'DELETE'
									.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_messages' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " IN ( " . $posts . " )";
						$_CB_database->setQuery( $query );
						if ( ! $_CB_database->query() ) {
							trigger_error( CBTxt::P( '[element] - deleteForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $_CB_database->stderr( true ) ) ) );
						} else {
							$query	=	'DELETE'
									.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_messages_text' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'mesid' ) . " IN ( " . $posts . " )";
							$_CB_database->setQuery( $query );
							if ( ! $_CB_database->query() ) {
								trigger_error( CBTxt::P( '[element] - deleteForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $_CB_database->stderr( true ) ) ) );
							}
						}
					}
				}
			}
		}
	}

	static private function getItemid( $htmlspecialchars = false ) {
		static $Itemid	=	null;

		if ( ! isset( $Itemid ) ) {
			$Itemid		=	KunenaRoute::getItemID();
		}

		if ( $Itemid ) {
			if ( is_bool( $htmlspecialchars ) ) {
				return ( $htmlspecialchars ? '&amp;' : '&' ) . 'Itemid=' . $Itemid;
			} else {
				return $Itemid;
			}
		}
	}

	static public function getForumURL( $forum = null, $post = null ) {
		$url			=	'index.php?option=com_kunena';

		if ( $forum ) {
			if ( $post ) {
				$url	.=	'&func=view';
			} else {
				$url	.=	'&func=showcat';
			}

			$url		.=	'&catid=' . (int) $forum;
		}

		if ( $post ) {
			$url		.=	'&id=' . (int) $post . '#' . (int) $post;
		}

		return KunenaRoute::_( $url );
	}

	static public function getCategory( $forum ) {
		global $_CB_database;

		$query		=	'SELECT *'
					.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_categories' )
					.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $forum;
		$_CB_database->setQuery( $query );
		$category	=	null;
		$_CB_database->loadObject( $category );

		return $category;
	}

	public function getTools( $msgs, $user, $plugin ) {
		global $_CB_database;

		$forum							=	$plugin->params->get( 'forum_id', null );

		if ( $forum ) {
			$query						=	'SELECT *'
										.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_categories' )
										.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $forum;
			$_CB_database->setQuery( $query );
			$parentForum				=	null;
			$_CB_database->loadObject( $parentForum );

			if ( $parentForum ) {
				$fixUrl					=	'<a href="' . cbgjClass::getPluginURL( array( 'plugin', 'fixforums' ) ) . '">' . CBTxt::T( 'Fix' ) . '</a>';

				if ( $parentForum->published != 1 ) {
					$msgs->warnings[]	=	CBTxt::P( ':: Forum :: Forum not published - [fix_url]', array( '[fix_url]' => $fixUrl ) );
				}

				if ( ( $parentForum->accesstype != 'communitybuilder' ) || ( $parentForum->pub_access != 1 ) || ( $parentForum->admin_access != 0 ) ) {
					$msgs->info[]		=	CBTxt::P( ':: Forum :: Forum not private - [fix_url]', array( '[fix_url]' => $fixUrl ) );
				}
			} else {
				$msgs->errors[]			=	CBTxt::P( ':: Forum :: Forum id does not exist in Kunena - [config_url]', array( '[config_url]' => '<a href="' . cbgjClass::getPluginURL( array( 'config' ) ) . '">' . $forum . '</a>' ) );
			}
		}
	}

	public function fixForum( $params, $user, $plugin ) {
		global $_CB_database;

		if ( $params[0] == 'fixforums' ) {
			$forum		=	$plugin->params->get( 'forum_id', null );

			if ( $forum ) {
				$query	=	'UPDATE ' . $_CB_database->NameQuote( '#__kunena_categories' )
						.	"\n SET " . $_CB_database->NameQuote( 'accesstype' ) . " = " . $_CB_database->Quote( 'communitybuilder' )
						.	', ' . $_CB_database->NameQuote( 'access' ) . " = 0"
						.	', ' . $_CB_database->NameQuote( 'pub_access' ) . " = 1"
						.	', ' . $_CB_database->NameQuote( 'pub_recurse' ) . " = 1"
						.	', ' . $_CB_database->NameQuote( 'admin_access' ) . " = 0"
						.	', ' . $_CB_database->NameQuote( 'admin_recurse' ) . " = 1"
						.	', ' . $_CB_database->NameQuote( 'published' ) . " = 1"
						.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $forum;
				$_CB_database->setQuery( $query );
				if ( ! $_CB_database->query() ) {
					cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'Forum failed to fix! Error: [error]', array( '[error]' => $_CB_database->stderr( true ) ) ), false, true, 'error' );
				}

				cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Forum fixed successfully!' ), false, true );
			}

			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Forum not found.' ), false, true, 'error' );
		}
	}

	public function getAccess( $trigger, $fbConfig, $params ) {
		if ( $trigger == 'getAllowedForumsRead' ) {
			$plugin					=	cbgjClass::getPlugin();
			$forum					=	$plugin->params->get( 'forum_id', null );

			if ( $forum ) {
				$forums				=	array();

				$categories			=	cbgjData::getCategories( array( 'forum_cat_access' ), array( 'params', 'REGEX', 'forum_id=[[:digit:]]+' ) );

				if ( $categories ) foreach ( $categories as $category ) {
					$catParams		=	$category->getParams();

					$forums[]		=	$catParams->get( 'forum_id', null );
				}

				$groups				=	cbgjData::getGroups( array( 'forum_grp_access' ), array( 'params', 'REGEX', 'forum_id=[[:digit:]]+' ) );

				if ( $groups ) foreach ( $groups as $group ) {
					$grpParams		=	$group->getParams();

					$forums[]		=	$grpParams->get( 'forum_id', null );
				}

				if ( ! empty( $forums ) ) {
					$forums[]		=	$forum;
					$existingAccess	=	explode( ',', $params[1] );
					$cleanAccess	=	array_diff( $forums, $existingAccess );
					$newAccess		=	array_merge( $existingAccess, $cleanAccess );

					cbArrayToInts( $newAccess );

					$params[1]		=	implode( ',', $newAccess );
				}
			}
		} elseif ( $trigger == 'checkSubscribers' ) {
			$plugin					=	cbgjClass::getPlugin();
			$forum					=	$plugin->params->get( 'forum_id', null );

			if ( $forum ) {
				$forumId			=	$params[0]->id;
				$users				=	$params[1];
				$ids				=	array();

				if ( $forumId && $users ) foreach ( $users as $user ) {
					$category		=	cbgjData::getCategories( array( array( 'forum_cat_access' ), $user ), array( 'params', 'REGEX', 'forum_id=[[:<:]]' . (int) $forumId . '[[:>:]]' ), null, null, false );

					if ( $category->get( 'id' ) ) {
						$ids[]		=	$user;
					}

					$group			=	cbgjData::getGroups( array( array( 'forum_grp_access' ), $user ), array( 'params', 'REGEX', 'forum_id=[[:<:]]' . (int) $forumId . '[[:>:]]' ), null, null, false );

					if ( $group->get( 'id' ) ) {
						$ids[]		=	$user;
					}
				}

				if ( ! empty( $ids ) ) {
					$existingAccess	=	$params[1];
					$cleanAccess	=	array_diff( $ids, $existingAccess );
					$newAccess		=	array_merge( $existingAccess, $cleanAccess );

					cbArrayToInts( $newAccess );

					$params[1]		=	array_values( array_unique( $newAccess ) );
				}
			}
		} elseif ( ( $trigger == 'onStart' ) && cbGetParam( $_REQUEST, 'catid', 0 ) ) {
			$plugin					=	cbgjClass::getPlugin();
			$backlink				=	$plugin->params->get( 'forum_backlink', 1 );
			$forum					=	$plugin->params->get( 'forum_id', null );

			if ( $backlink && $forum ) {
				cbgjClass::getTemplate( 'cbgroupjiveforums' );

				$catid				=	(int) cbGetParam( $_REQUEST, 'catid', 0 );

				if ( $forum == $catid ) {
					echo '<div id="cbGj"><div id="cbGjInner"><div class="gjTop gjTopCenter"><a href="' . cbgjClass::getPluginURL( array( 'overview' ) ) . '" role="button" class="gjButton btn"><i class="icon-share-alt"></i> ' . CBTxt::P( 'Back to [overview]', array( '[overview]' => cbgjClass::getOverride( 'overview' ) ) ) . '</a></div></div></div>';
				} else {
					$category		=	cbgjData::getCategories( array( 'cat_access', 'mod_lvl1' ), array( 'params', 'REGEX', 'forum_id=[[:<:]]' . (int) $catid . '[[:>:]]' ), null, null, false );

					if ( $category->get( 'id' ) ) {
						echo '<div id="cbGj"><div id="cbGjInner"><div class="gjTop gjTopCenter"><a href="' . $category->getUrl() . '" role="button" class="gjButton btn"><i class="icon-share-alt"></i> ' . CBTxt::P( 'Back to [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div></div></div>';
					} else {
						$group		=	cbgjData::getGroups( array( 'grp_access', 'mod_lvl2' ), array( 'params', 'REGEX', 'forum_id=[[:<:]]' . (int) $catid . '[[:>:]]' ), null, null, false );

						if ( $group->get( 'id' ) ) {
							echo '<div id="cbGj"><div id="cbGjInner"><div class="gjTop gjTopCenter"><a href="' . $group->getUrl() . '" role="button" class="gjButton btn"><i class="icon-share-alt"></i> ' . CBTxt::P( 'Back to [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div></div></div>';
						}
					}
				}
			}
		}
	}

	static public function getBoards( $plugin ) {
		global $_CB_database;

		$query					=	'SELECT ' . $_CB_database->NameQuote( 'id' ) . ' AS value'
								.	', ' . $_CB_database->NameQuote( 'name' ) . ' AS text'
								.	', ' . $_CB_database->NameQuote( 'parent' )
								.	"\n FROM " . $_CB_database->NameQuote( '#__kunena_categories' )
								.	"\n WHERE " . $_CB_database->NameQuote( 'published' ) . " = 1"
								.	"\n ORDER BY " . $_CB_database->NameQuote( 'parent' ) . " ASC";
		$_CB_database->setQuery( $query );
		$rows					=	$_CB_database->loadObjectList();

		$children				=	array();

		if ( $rows ) foreach ( $rows as $row ) {
			$parent				=	$row->parent;
			$tree				=	( isset( $children[$parent] ) ? $children[$parent] : array() );

			array_push( $tree, $row );

			$children[$parent]	=	$tree;
		}

		$boards					=	cbgjForumsModel::getBoardTree( $children );

		return array_values( $boards );
	}

	static public function getBoardTree( $children, $id = 0, $indent = '', $list = array() ) {
		$rows					=	( isset( $children[$id] ) ? $children[$id] : null );

		if ( $rows ) foreach ( $rows as $row ) {
			$id					=	$row->value;
			$rows				=	( isset( $children[$id] ) ? $children[$id] : null );

			$list[$id]->value	=	$id;
			$list[$id]->text	=	$indent . ( $row->parent ? '.&nbsp;&nbsp;' : null ) . '-&nbsp;' . htmlspecialchars( $row->text );
			$list				=	cbgjForumsModel::getBoardTree( $children, $id, $indent . '&nbsp;&nbsp;&nbsp;&nbsp;', $list );
		}

		return $list;
	}
}
?>