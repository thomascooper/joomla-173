<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerFunction( 'gj_onAfterTools', 'getTools', 'cbgjForumsModel' );
$_PLUGINS->registerFunction( 'gj_onPluginBE', 'fixForum', 'cbgjForumsModel' );
$_PLUGINS->registerFunction( 'kunenaIntegration', 'getAccess', 'cbgjForumsModel' );

class cbgjForumsModel extends cbPluginHandler {

	static public function getForum( $tabs, $row, $user, $plugin ) {
		global $_CB_database;

		if ( ! class_exists( 'KunenaForumMessageHelper' ) ) {
			return CBTxt::T( 'Kunena not installed, enabled, or failed to load.' );
		}

		$params				=	$row->getParams();
		$forumId			=	$params->get( 'forum_id', null );

		cbgjClass::getTemplate( 'cbgroupjiveforums' );

		$paging				=	new cbgjPaging( 'forum' );

		$limit				=	$paging->getlimit( (int) $plugin->params->get( 'forum_limit', 15 ) );
		$limitstart			=	$paging->getLimistart();
		$search				=	$paging->getFilter( 'search' );
		$where				=	array();

		if ( isset( $search ) && ( $search != '' ) ) {
			$where[]		=	'( m.' . $_CB_database->NameQuote( 'subject' ) . ' LIKE ' . $_CB_database->Quote( '%' . $_CB_database->getEscaped( $search, true ) . '%', false )
							.	' OR t.' . $_CB_database->NameQuote( 'message' ) . ' LIKE ' . $_CB_database->Quote( '%' . $_CB_database->getEscaped( $search, true ) . '%', false ) . ' )';
		}

		$params				=	array(	'starttime' => -1,
										'where' => ( count( $where ) ? implode( ' AND ', $where ) : null )
									);

		$rows				=	KunenaForumMessageHelper::getLatestMessages( $forumId, 0, 0, $params );
		$total				=	array_shift( $rows );

		if ( $total <= $limitstart ) {
			$limitstart		=	0;
		}

		$pageNav			=	$paging->getPageNav( $total, $limitstart, $limit );

		if ( $plugin->params->get( 'forum_paging', 1 ) ) {
			$rows			=	KunenaForumMessageHelper::getLatestMessages( $forumId, (int) $pageNav->limitstart, (int) $pageNav->limit, $params );
			$rows			=	array_pop( $rows );
		} else {
			$rows			=	array_pop( $rows );
		}

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
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return;
		}

		$forum				=	null;

		if ( $parentForum ) {
			if ( method_exists( $row, 'getCategory' ) ) {
				$authorized	=	cbgjClass::getAuthorization( $row->getCategory(), $row, $user );
			} else {
				$authorized	=	cbgjClass::getAuthorization( $row, null, $user );
			}

			$params			=	$row->getParams();
			$forum_show		=	(int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_show', $params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) ) );
			$category		=	KunenaForumCategoryHelper::get();

			if ( method_exists( $row, 'getCategory' ) ) {
				$alias		=	null;

				if ( $row->get( 'parent' ) ) {
					$alias	.=	$row->get( 'parent' ) . ' ';
				}

				$alias		.=	$row->get( 'id' ) . ' ' . $row->getName();
			} else {
				$alias		=	$row->get( 'category' ) . ' ';

				if ( $row->get( 'parent' ) ) {
					$alias	.=	$row->get( 'parent' ) . ' ';
				}

				$alias		.=	$row->get( 'id' ) . ' ' . $row->getName();
			}

			$category->set( 'parent_id', (int) $parentForum );
			$category->set( 'name', str_replace( '&amp;', '&', $row->getName() ) );
			$category->set( 'alias', KunenaRoute::stringURLSafe( $alias ) );
			$category->set( 'accesstype', 'communitybuilder' );
			$category->set( 'access', (int) $row->get( 'id' ) );
			$category->set( 'published', (int) ( ! $forum_show ? 0 : $row->get( 'published' ) ) );
			$category->set( 'description', $row->getDescription() );

			if ( ! $category->save() ) {
				trigger_error( CBTxt::P( '[element] - setForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $category->getError() ) ) );
			} else {
				$forum		=	$category->id;

				$params->set( 'forum_id', (int) $forum );
				$params->set( 'forum_show', (int) $forum_show );
				$params->set( 'forum_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_public', $params->get( 'forum_public', $plugin->params->get( 'forum_public', 1 ) ) ) );

				if ( ! $row->storeParams( $params ) ) {
					trigger_error( CBTxt::P( '[element] - setForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $row->getError() ) ) );
				}

				$category->addModerator( (int) $row->get( 'user_id' ) );
			}
		}

		return $forum;
	}

	static public function setModerator( $userId, $row ) {
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return;
		}

		$params		=	$row->getParams();
		$forumId	=	$params->get( 'forum_id', null );

		if ( ( ! $userId ) || ( ! $forumId ) ) {
			return;
		}

		$category	=	KunenaForumCategoryHelper::get( (int) $forumId );

		$category->addModerator( (int) $userId );
	}

	static public function unsetModerator( $userId, $row ) {
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return;
		}

		$params		=	$row->getParams();
		$forumId	=	$params->get( 'forum_id', null );

		if ( ( ! $userId ) || ( ! $forumId ) ) {
			return;
		}

		$category	=	KunenaForumCategoryHelper::get( (int) $forumId );

		$category->removeModerator( (int) $userId );
	}

	static public function unsetSession( $userId ) {
		if ( ! class_exists( 'KunenaAccess' ) ) {
			return;
		}

		KunenaAccess::getInstance()->clearCache();
	}

	static public function saveForum( $parentForum, $row, $user, $plugin ) {
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return;
		}

		if ( $parentForum ) {
			if ( method_exists( $row, 'getCategory' ) ) {
				$authorized		=	cbgjClass::getAuthorization( $row->getCategory(), $row, $user );
			} else {
				$authorized		=	cbgjClass::getAuthorization( $row, null, $user );
			}

			$params				=	$row->getParams();
			$forum				=	$params->get( 'forum_id', null );

			if ( ! $forum ) {
				$forum			=	cbgjForumsModel::setForum( $parentForum, $row, $user, $plugin );
			} else {
				$exists			=	KunenaForumCategoryHelper::get( (int) $forum );

				if ( ! $exists->name ) {
					$forum		=	cbgjForumsModel::setForum( $parentForum, $row, $user, $plugin );
				}
			}

			if ( $forum ) {
				$forumShow		=	(int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_show', $params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) ) );
				$category		=	KunenaForumCategoryHelper::get( (int) $forum );

				if ( method_exists( $row, 'getCategory' ) ) {
					$alias		=	null;

					if ( $row->get( 'parent' ) ) {
						$alias	.=	$row->get( 'parent' ) . ' ';
					}

					$alias		.=	$row->get( 'id' ) . ' ' . $row->getName();
				} else {
					$alias		=	$row->get( 'category' ) . ' ';

					if ( $row->get( 'parent' ) ) {
						$alias	.=	$row->get( 'parent' ) . ' ';
					}

					$alias		.=	$row->get( 'id' ) . ' ' . $row->getName();
				}

				$category->set( 'parent_id', (int) $parentForum );
				$category->set( 'name', str_replace( '&amp;', '&', $row->getName() ) );
				$category->set( 'alias', KunenaRoute::stringURLSafe( $alias ) );
				$category->set( 'accesstype', 'communitybuilder' );
				$category->set( 'access', (int) $row->get( 'id' ) );
				$category->set( 'published', (int) ( ! $forumShow ? 0 : $row->get( 'published' ) ) );
				$category->set( 'description', $row->getDescription() );

				if ( ! $category->save() ) {
					trigger_error( CBTxt::P( '[element] - saveForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $category->getError() ) ) );
				} else {
					$params->set( 'forum_id', (int) $category->id );
					$params->set( 'forum_show', (int) $forumShow );
					$params->set( 'forum_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_public', $params->get( 'forum_public', $plugin->params->get( 'forum_public', 1 ) ) ) );

					if ( ! $row->storeParams( $params ) ) {
						trigger_error( CBTxt::P( '[element] - saveForum SQL Error: [error]', array( '[element]' => $plugin->element, '[error]' => $row->getError() ) ) );
					}

					$category->addModerator( (int) $row->user_id );
				}
			}
		}
	}

	static public function stateForum( $row, $user, $plugin ) {
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return;
		}

		$params				=	$row->getParams();
		$forumId			=	$params->get( 'forum_id', null );

		if ( $forumId ) {
			if ( method_exists( $row, 'getCategory' ) ) {
				$authorized	=	cbgjClass::getAuthorization( $row->getCategory(), $row, $user );
			} else {
				$authorized	=	cbgjClass::getAuthorization( $row, null, $user );
			}

			$forumShow		=	(int) cbgjClass::getCleanParam( ( $plugin->params->get( 'forum_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_show', $params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) ) );
			$category		=	KunenaForumCategoryHelper::get( (int) $forumId );

			if ( $category->name ) {
				$category->set( 'published', (int) ( ! $forumShow ? 0 : $row->get( 'published' ) ) );
				$category->save();
			}
		}
	}

	static public function deleteAllForums( $row, $user, $plugin ) {
		$params			=	$row->getParams();
		$forumId		=	$params->get( 'forum_id', null );

		if ( $forumId ) {
			$subForums	=	KunenaForumCategoryHelper::getChildren( (int) $forumId, 10 );

			if ( $subForums ) foreach ( $subForums as $subForum ) {
				if ( $subForum->name ) {
					$subForum->delete();
				}
			}

			cbgjForumsModel::deleteForum( $forumId, $user, $plugin );
		}
	}

	static public function deleteForum( $forum, $user, $plugin ) {
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return;
		}

		if ( $forum ) {
			$category	=	KunenaForumCategoryHelper::get( (int) $forum );

			if ( $category->name ) {
				$category->delete();
			}
		}
	}

	static private function getItemid( $htmlspecialchars = false ) {
		if ( ! class_exists( 'KunenaRoute' ) ) {
			return;
		}

		static $Itemid	=	null;

		if ( ! isset( $Itemid ) ) {
			$Itemid		=	KunenaRoute::getItemID();
		}

		if ( $Itemid ) {
			if ( is_bool( $htmlspecialchars ) ) {
				return ( $htmlspecialchars ? '&amp;' : '&' ) . 'Itemid=' . (int) $Itemid;
			} else {
				return $Itemid;
			}
		}
	}

	static public function getForumURL( $forum = null, $post = null ) {
		if ( ! class_exists( 'KunenaForumMessage' ) ) {
			return;
		}

		if ( $post ) {
			$url	=	KunenaForumMessage::getInstance( (int) $post )->getUrl();
		} else {
			$url	=	cbgjForumsModel::getCategory( (int) $forum )->getUrl();
		}

		return $url;
	}

	static public function getCategory( $forum ) {
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return;
		}

		static $cache		=	array();

		if ( ! isset( $cache[$forum] ) ) {
			$cache[$forum]	=	KunenaForumCategoryHelper::get( (int) $forum );
		}

		return $cache[$forum];
	}

	public function getTools( $msgs, $user, $plugin ) {
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return;
		}

		$forum							=	$plugin->params->get( 'forum_id', null );

		if ( $forum ) {
			$parentForum				=	KunenaForumCategoryHelper::get( (int) $forum );

			if ( $parentForum->name ) {
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
		if ( $params[0] == 'fixforums' ) {
			if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
				cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Kunena not installed, enabled, or failed to load.' ), false, true, 'error' );
			}

			$forum			=	$plugin->params->get( 'forum_id', null );

			if ( $forum ) {
				$category	=	KunenaForumCategoryHelper::get( (int) $forum );

				$category->set( 'accesstype', 'communitybuilder' );
				$category->set( 'access', 0 );
				$category->set( 'published', 1 );

				if ( ! $category->save() ) {
					cbgjClass::getPluginURL( array( 'tools' ), CBTxt::P( 'Forum failed to fix! Error: [error]', array( '[error]' => $category->getError() ) ), false, true, 'error' );
				}

				cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Forum fixed successfully!' ), false, true );
			}

			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Forum not found.' ), false, true, 'error' );
		}
	}

	public function getAccess( $trigger, $fbConfig, $params ) {
		if ( $trigger == 'getAllowedForumsRead' ) {
			$plugin						=	cbgjClass::getPlugin();
			$forum						=	$plugin->params->get( 'forum_id', null );

			if ( $forum ) {
				$forums					=	array();

				$categories				=	cbgjData::getCategories( array( 'forum_cat_read' ), array( 'params', 'REGEX', 'forum_id=[[:digit:]]+' ) );

				if ( $categories ) foreach ( $categories as $category ) {
					$cat_params			=	$category->getParams();

					$forums[]			=	$cat_params->get( 'forum_id', null );
				}

				$groups					=	cbgjData::getGroups( array( 'forum_grp_read' ), array( 'params', 'REGEX', 'forum_id=[[:digit:]]+' ) );

				if ( $groups ) foreach ( $groups as $group ) {
					$grp_params			=	$group->getParams();

					$forums[]			=	$grp_params->get( 'forum_id', null );
				}

				if ( ! empty( $forums ) ) {
					$forums[]			=	$forum;
					$existingAccess		=	explode( ',', $params[1] );
					$cleanAccess		=	array_diff( $forums, $existingAccess );
					$newAccess			=	array_merge( $existingAccess, $cleanAccess );

					cbArrayToInts( $newAccess );

					$params[1]			=	implode( ',', $newAccess );
				}
			}
		} elseif ( $trigger == 'authoriseUsers' ) {
			$plugin						=	cbgjClass::getPlugin();
			$forum						=	$plugin->params->get( 'forum_id', null );

			if ( $forum ) {
				$forumId				=	$params['category']->id;
				$users					=	$params['userids'];
				$ids					=	array();

				if ( $forumId && $users ) foreach ( $users as $user ) {
					$category			=	cbgjData::getCategories( array( array( 'forum_cat_access' ), $user ), array( 'params', 'REGEX', 'forum_id=[[:<:]]' . (int) $forumId . '[[:>:]]' ), null, null, false );

					if ( $category->get( 'id' ) ) {
						$ids[]			=	$user;
					}

					$group				=	cbgjData::getGroups( array( array( 'forum_grp_access' ), $user ), array( 'params', 'REGEX', 'forum_id=[[:<:]]' . (int) $forumId . '[[:>:]]' ), null, null, false );

					if ( $group->get( 'id' ) ) {
						$ids[]			=	$user;
					}
				}

				if ( ! empty( $ids ) ) {
					cbArrayToInts( $ids );

					$params['allow']	=	$ids;
				}
			}
		} elseif ( ( $trigger == 'onStart' ) && ( ( in_array( cbGetParam( $_REQUEST, 'view', null ), array( 'category', 'topic' ) ) ) && cbGetParam( $_REQUEST, 'catid', 0 ) ) ) {
			$plugin						=	cbgjClass::getPlugin();
			$backlink					=	$plugin->params->get( 'forum_backlink', 1 );
			$forum						=	$plugin->params->get( 'forum_id', null );

			if ( $backlink && $forum ) {
				cbgjClass::getTemplate( 'cbgroupjiveforums' );

				$catid					=	(int) cbGetParam( $_REQUEST, 'catid', 0 );

				if ( $forum == $catid ) {
					echo '<div id="cbGj"><div id="cbGjInner"><div class="gjTop gjTopCenter"><a href="' . cbgjClass::getPluginURL( array( 'overview' ) ) . '" role="button" class="gjButton btn"><i class="icon-share-alt"></i> ' . CBTxt::P( 'Back to [overview]', array( '[overview]' => cbgjClass::getOverride( 'overview' ) ) ) . '</a></div></div></div>';
				} else {
					$category			=	cbgjData::getCategories( array( 'cat_access', 'mod_lvl1' ), array( 'params', 'REGEX', 'forum_id=[[:<:]]' . (int) $catid . '[[:>:]]' ), null, null, false );

					if ( $category->get( 'id' ) ) {
						echo '<div id="cbGj"><div id="cbGjInner"><div class="gjTop gjTopCenter"><a href="' . $category->getUrl() . '" role="button" class="gjButton btn"><i class="icon-share-alt"></i> ' . CBTxt::P( 'Back to [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div></div></div>';
					} else {
						$group			=	cbgjData::getGroups( array( 'grp_access', 'mod_lvl2' ), array( 'params', 'REGEX', 'forum_id=[[:<:]]' . (int) $catid . '[[:>:]]' ), null, null, false );

						if ( $group->get( 'id' ) ) {
							echo '<div id="cbGj"><div id="cbGjInner"><div class="gjTop gjTopCenter"><a href="' . $group->getUrl() . '" role="button" class="gjButton btn"><i class="icon-share-alt"></i> ' . CBTxt::P( 'Back to [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div></div></div>';
						}
					}
				}
			}
		} elseif ( $trigger == 'loadGroups' ) {
			$items						=	cbgjClass::getCategoryGroupOptions();
			$itemsArray					=	array();

			foreach ( $items as $item ) {
				$id						=	$item->value;

				$row					=	new stdClass();
				$row->id				=	$id;
				$row->parent_id			=	0;
				$row->name				=	$item->text;

				$itemsArray[$id]		=	$row;
			}

			$params['groups']			=	$itemsArray;
		}
	}

	static public function getBoards( $plugin ) {
		if ( ! class_exists( 'KunenaForumCategoryHelper' ) ) {
			return array();
		}

		$rows				=	KunenaForumCategoryHelper::getChildren( 0, 10 );
		$categories			=	array();

		if ( $rows ) foreach ( $rows as $row ) {
			$categories[]	=	moscomprofilerHTML::makeOption( $row->id, str_repeat( '- ', $row->level + 1  ) . ' ' . $row->name );
		}

		return $categories;
	}
}
?>