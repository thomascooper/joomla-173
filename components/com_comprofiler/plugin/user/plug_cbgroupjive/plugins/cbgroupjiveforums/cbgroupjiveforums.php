<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $_PLUGINS;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

$_PLUGINS->registerFunction( 'gj_onAfterCategoryTab', 'getCategoryForum', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateCategory', 'setCategoryForum', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateCategory', 'saveCategoryForum', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateCategoryState', 'stateCategoryForum', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteCategory', 'deleteCategoryForums', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onCategoryEdit', 'getCategoryParam', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onBeforeGroupTab', 'getGroupForum', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateGroup', 'setGroupForum', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateGroup', 'saveGroupForum', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateGroupState', 'stateGroupForum', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteGroup', 'deleteGroupForums', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupEdit', 'getGroupParam', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateUserStatus', 'updateForumUser', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateUser', 'setForumUser', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterDeleteUser', 'setForumUser', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onConfigIntegrations', 'getConfig', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterTools', 'getTools', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onPluginBE', 'fixAllForums', 'cbgjForumsPlugin' );
$_PLUGINS->registerFunction( 'gj_onAuthorization', 'getAuthorization', 'cbgjForumsPlugin' );

class cbgjForumsPlugin extends cbPluginHandler {

	public function __construct() {
		cbgjForumsPlugin::getForumModel();
	}

	static public function getForumModel() {
		global $_CB_framework;

		static $model					=	null;

		if ( ! isset( $model ) ) {
			$plugin						=	cbgjClass::getPlugin();
			$forum						=	$plugin->params->get( 'forum_model', 1 );
			$path						=	$_CB_framework->getCfg( 'absolute_path' );
			$model						=	new stdClass();

			if ( in_array( $forum, array( 1, 2, 3 ) ) && file_exists( $path . '/administrator/components/com_kunena/api.php' ) ) {
				require_once( $path . '/administrator/components/com_kunena/api.php' );

				if ( ! class_exists( 'KunenaForum' ) ) {
					kimport( 'error' );
					kimport( 'category' );
					kimport( 'session' );

					require_once( $plugin->absPath . '/plugins/cbgroupjiveforums/models/kunena17.php' );

					$model->detected	=	CBTxt::T( 'Kunena 1.7' );
				} else {
					if ( class_exists( 'KunenaForum' ) ) {
						KunenaForum::setup();
					}

					require_once( $plugin->absPath . '/plugins/cbgroupjiveforums/models/kunena20.php' );

					$model->detected	=	CBTxt::T( 'Kunena 2.0' );
				}
			} elseif ( in_array( $forum, array( 1, 4 ) ) && file_exists( $path . '/administrator/components/com_kunena/' ) ) {
				require_once( $plugin->absPath . '/plugins/cbgroupjiveforums/models/kunena15.php' );

				$model->detected		=	CBTxt::T( 'Kunena 1.5' );
			} else {
				$model->detected		=	CBTxt::T( 'None' );
			}

			if ( class_exists( 'cbgjForumsModel' ) ) {
				$model->installed		=	true;
			} else {
				$model->installed		=	false;
			}
		}

		return $model;
	}

	public function getCategoryForum( $tabs, $category, $user, $plugin ) {
		$model		=	cbgjForumsPlugin::getForumModel();

		if ( ( ! $model->installed ) || ( ! $plugin->params->get( 'forum_categories', 1 ) ) ) {
			return;
		}

		$authorized	=	cbgjClass::getAuthorization( $category );

		if ( ! cbgjClass::hasAccess( 'forum_cat_show', $authorized ) ) {
			return;
		}

		return cbgjForumsModel::getForum( $tabs, $category, $user, $plugin );
	}

	public function getGroupForum( $tabs, $group, $category, $user, $plugin ) {
		$model		=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		$authorized	=	cbgjClass::getAuthorization( $category, $group );

		if ( ! cbgjClass::hasAccess( 'forum_grp_show', $authorized ) ) {
			return;
		}

		return cbgjForumsModel::getForum( $tabs, $group, $user, $plugin );
	}

	static public function showForum( $rows, $pageNav, $tabs, $row, $user, $plugin ) {
		$model					=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		$forumSearch			=	( $plugin->params->get( 'forum_search', 1 ) && ( $pageNav->searching || $pageNav->total ) );
		$forumPaging			=	$plugin->params->get( 'forum_paging', 1 );
		$forumLimitbox			=	$plugin->params->get( 'forum_limitbox', 1 );
		$menuDisplay			=	true;

		if ( method_exists( $row, 'getCategory' ) ) {
			$formUrl			=	cbgjClass::getPluginURL( array( 'groups', 'show', (int) $row->get( 'category' ), (int) $row->get( 'id' ) ) );
			$authorized			=	cbgjClass::getAuthorization( $row->get( 'category' ), $row );

			if ( ! cbgjClass::hasAccess( 'forum_grp_access', $authorized ) ) {
				$menuDisplay	=	false;
			}
		} else {
			$formUrl			=	cbgjClass::getPluginURL( array( 'categories', 'show', (int) $row->get( 'id' ) ) );
			$authorized			=	cbgjClass::getAuthorization( $row );

			if ( ! cbgjClass::hasAccess( 'forum_cat_access', $authorized ) ) {
				$menuDisplay	=	false;
			}
		}

		$params					=	$row->getParams();

		$return					=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Forum' ) ), 'gjIntegrationsForum' )
								.	'<div class="gjForums">'
								.		'<form action="' . $formUrl . '" method="post" name="gjForm_forum" id="gjForm_forum" class="gjForm">';

		if ( $menuDisplay || $forumSearch ) {
			$return				.=			'<div class="gjTop row-fluid">'
								.				'<div class="gjTop gjTopLeft span6">'
								.					( $menuDisplay ? '<input type="button" value="' . htmlspecialchars( CBTxt::T( 'New Topic' ) ) . '" class="gjButton btn" onclick="javascript: location.href = \'' . addslashes( cbgjForumsModel::getForumURL( $params->get( 'forum_id', null ) ) ) . '\';" />' : null )
								.				'</div>'
								.				'<div class="gjTop gjTopRight span6">'
								.					( $forumSearch ? $pageNav->search : null )
								.				'</div>'
								.			'</div>';
		}

		if ( $rows ) foreach ( $rows as $r ) {
			$cbUser				=&	CBuser::getInstance( (int) $r->userid );

			if ( ! $cbUser ) {
				$cbUser			=&	CBuser::getInstance( null );
			}

			$user				=&	$cbUser->getUserData();

			if ( $user->id ) {
				$userAvatar		=	$cbUser->getField( 'avatar', null, 'html', 'none', 'list', 0, true );
				$userName		=	$cbUser->getField( 'formatname', null, 'html', 'none', 'list', 0, true );
				$userOnline		=	$cbUser->getField( 'onlinestatus', null, 'html', 'none', 'list', 0, true );
			} else {
				$userName		=	htmlspecialchars( ( $r->name ? $r->name : CBTxt::T( 'Guest' ) ) );
				$userAvatar		=	'<img src="' . selectTemplate() . 'images/avatar/tnnophoto_n.png" alt="' . $userName . '" class="img-polaroid" />';
				$userOnline		=	null;
			}

			$postUrl			=	cbgjForumsModel::getForumURL( $r->catid, $r->id );

			if ( method_exists( $row, 'getCategory' ) ) {
				$postSubject	=	( cbgjClass::hasAccess( 'forum_grp_access', $authorized ) && $postUrl ? '<a href="' . $postUrl . '">' . htmlspecialchars( cbgjForumsPlugin::cleanPost( $r->subject ) ) . '</a>' : htmlspecialchars( cbgjForumsPlugin::cleanPost( $r->subject ) ) );
			} else {
				$postSubject	=	( cbgjClass::hasAccess( 'forum_cat_access', $authorized ) && $postUrl ? '<a href="' . $postUrl . '">' . htmlspecialchars( cbgjForumsPlugin::cleanPost( $r->subject ) ) . '</a>' : htmlspecialchars( cbgjForumsPlugin::cleanPost( $r->subject ) ) );
			}

			$return				.=			'<div class="gjContent row-fluid">'
								.				'<div class="gjContentLogo span2">'
								.					'<div class="gjContentLogoRow">' . $userName . '</div>'
								.					'<div class="gjContentLogoRow">' . $userAvatar . '</div>'
								.					'<div class="gjContentLogoRow">' . $userOnline . '</div>'
								.				'</div>'
								.				'<div class="gjContentBody mini-layout span10">'
								.					'<div class="gjContentBodyHeader row-fluid">'
								.						'<div class="gjContentBodyTitle span9"><h5>' . $postSubject . '<small> ' . cbFormatDate( cbgjClass::getUTCDate( null, $r->time ), 1, false ) . '</small></h5></div>'
								.					'</div>'
								.					'<div class="gjContentBodyInfo">' . ( $r->message ? '<div class="well well-small">' . htmlspecialchars( cbgjForumsPlugin::cleanPost( $r->message, 250 ) ) . '</div>' : null ) . '</div>'
								.				'</div>'
								.			'</div>';
		} else {
			$return				.=			'<div class="gjContent">';

			if ( $forumSearch && $pageNav->searching ) {
				$return			.=				CBTxt::Th( 'No forum search results found.' );
			} else {
				if ( method_exists( $row, 'getCategory' ) ) {
					$return		.=				CBTxt::Ph( 'This [group] has no posts.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) );
				} else {
					$return		.=				CBTxt::Ph( 'This [category] has no posts.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) );
				}
			}

			$return				.=			'</div>';
		}

		if ( $forumPaging ) {
			$return				.=			'<div class="gjPaging pagination pagination-centered">'
								.				( $pageNav->total > $pageNav->limit ? $pageNav->pagelinks : null )
								.				( ! $forumLimitbox ? $pageNav->getLimitBox( false ) : ( $pageNav->total ? '<div class="gjPagingLimitbox">' . $pageNav->limitbox . '</div>' : null ) )
								.			'</div>';
		}

		$return					.=			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>'
								.	$tabs->endTab();

		return $return;
	}

	static private function cleanPost( $text, $length = null ) {
		$text		=	preg_replace( '!:(pinch|whistle|evil|side|kiss|blush|silly|cheer|woohoo|rolleyes|money|lol|dry|huh|blink|ohmy|unsure|mad|angry|laugh):!', '', $text ); //Smilies
		$text		=	preg_replace( '!((\[b\])*\w+ wrote:(\[\/b\])*\s+)*(?s)(\[quote\])(.*?)(\[/quote\])!', '...', $text ); //Quotes
		$text		=	preg_replace( '!(?s)(\[code(.*?)\])(.*?)(\[/code(.*?)\])!', '...', $text ); //Code
		$text		=	preg_replace( '!(?s)(\[i\])(.*?)(\[\/i\])!', '\2', $text ); //Italic
		$text		=	preg_replace( '!(?s)(\[u\])(.*?)(\[\/u\])!', '\2', $text ); //Underline
		$text		=	preg_replace( '!(?s)(\[b\])(.*?)(\[\/b\])!', '\2', $text ); //Bold
		$text		=	preg_replace( '!(?s)(\[strike\])(.*?)(\[\/strike\])!', '\2', $text ); //Strike
		$text		=	preg_replace( '!(?s)(\[sub\])(.*?)(\[\/sub\])!', '\2', $text ); //Subscript
		$text		=	preg_replace( '!(?s)(\[sup\])(.*?)(\[\/sup\])!', '\2', $text ); //Superscript
		$text		=	preg_replace( '!(?s)(\[ul\])(.*?)(\[\/ul\])!', '\2', $text ); //Unodered List
		$text		=	preg_replace( '!(?s)(\[ol\])(.*?)(\[\/ol\])!', '\2', $text ); //Ordered List
		$text		=	preg_replace( '!(?s)(\[li\])(.*?)(\[\/li\])!', '\2', $text ); //List Item
		$text		=	preg_replace( '!(?s)(\[size(.*?)\])(.*?)(\[\/size\])!', '\3', $text ); //Font Size
		$text		=	preg_replace( '!(?s)(\[color(.*?)\])(.*?)(\[\/color\])!', '\3', $text ); //Font Color
		$text		=	preg_replace( '!(?s)(\[img(.*?)\])(.*?)(\[\/img\])!', '...', $text ); //Image
		$text		=	preg_replace( '!(?s)(\[video(.*?)\])(.*?)(\[\/video\])!', '...', $text ); //Video
		$text		=	preg_replace( '!(?s)(\[hide(.*?)\])(.*?)(\[\/hide\])!', '...', $text ); //Hidden
		$text		=	preg_replace( '!(?s)(\[ebay(.*?)\])(.*?)(\[\/ebay\])!', '...', $text ); //Ebay Item
		$text		=	preg_replace( '!(?s)(\[file(.*?)\])(.*?)(\[\/file\])!', '...', $text ); //File
		$text		=	preg_replace( '!(?s)(\[attachment(.*?)\])(.*?)(\[\/attachment\])!', '...', $text ); //Attachment
		$text		=	preg_replace( '!(?s)(\[spoiler(.*?)\])(.*?)(\[\/spoiler\])!', '...', $text ); //Spoiler
		$text		=	preg_replace( '!(?s)(\[url(.*?)\])(.*?)(\[\/url\])!', '...', $text ); //URL
		$text		=	preg_replace( '!(?s)(\[confidential(.*?)\])(.*?)(\[\/confidential\])!', '', $text ); //Confidential
		$text		=	preg_replace( '%[[/!]*?[^[\]]*?\]%', '', $text ); //Remaining Tags
		$text		=	preg_replace( '/(\.\.\.\s*){2,}/', '... ', $text ); //Remove Duplicate Replacements
		$text		=	strip_tags( $text );
		$text		=	stripslashes( $text );

		if ( $length && ( cbIsoUtf_strlen( $text ) > $length ) ) {
			$text	=	trim( cbIsoUtf_substr( $text, 0, $length ) ) . '...';
			$text	=	preg_replace( '/(\.\.\.\s*){2,}/', '... ', $text ); //Remove Duplicate Replacements
		}

		$text		=	trim( $text );

		return $text;
	}

	public function setForumUser( $row, $group, $category, $user, $plugin ) {
		$this->updateForumUser( -2, $row, $group, $category, $user, $plugin );
	}

	public function updateForumUser( $status, $row, $group, $category, $user, $plugin ) {
		$model	=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		cbgjForumsModel::unsetSession( $row->get( 'user_id' ) );

		if ( in_array( $row->get( 'status' ), array( 2, 3, 4 ) ) ) {
			cbgjForumsModel::setModerator( $row->get( 'user_id' ), $group );
		} else {
			cbgjForumsModel::unsetModerator( $row->get( 'user_id' ), $group );
		}
	}

	public function setCategoryForum( $category, $user, $plugin ) {
		$model				=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		if ( $category->get( 'parent' ) ) {
			$parent			=	$category->getParent();
			$params			=	$parent->getParams();
			$parentForum	=	$params->get( 'forum_id', null );
		} else {
			$parentForum	=	$plugin->params->get( 'forum_id', null );
		}

		cbgjForumsModel::unsetSession( $category->get( 'user_id' ) );

		return cbgjForumsModel::setForum( $parentForum, $category, $user, $plugin );
	}

	public function saveCategoryForum( $category, $user, $plugin ) {
		$model				=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		if ( $category->get( 'parent' ) ) {
			$parent			=	$category->getParent();
			$params			=	$parent->getParams();
			$parentForum	=	$params->get( 'forum_id', null );
		} else {
			$parentForum	=	$plugin->params->get( 'forum_id', null );
		}

		if ( ! $parentForum ) {
			return;
		}

		cbgjForumsModel::unsetSession( $category->get( 'user_id' ) );
		cbgjForumsModel::saveForum( $parentForum, $category, $user, $plugin );
	}

	public function stateCategoryForum( $state, $category, $user, $plugin ) {
		$model	=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		cbgjForumsModel::unsetSession( $category->get( 'user_id' ) );
		cbgjForumsModel::stateForum( $category, $user, $plugin );
	}

	public function deleteCategoryForums( $category, $user, $plugin ) {
		$model	=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		cbgjForumsModel::unsetSession( $category->get( 'user_id' ) );
		cbgjForumsModel::deleteAllForums( $category, $user, $plugin );
	}

	public function setGroupForum( $group, $category, $user, $plugin ) {
		$model				=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		if ( $group->get( 'parent' ) ) {
			$parent			=	$group->getParent();
			$params			=	$parent->getParams();
			$parentForum	=	$params->get( 'forum_id', null );
		} else {
			$params			=	$category->getParams();
			$parentForum	=	$params->get( 'forum_id', null );
		}

		if ( ! $parentForum ) {
			return;
		}

		cbgjForumsModel::unsetSession( $group->get( 'user_id' ) );

		return cbgjForumsModel::setForum( $parentForum, $group, $user, $plugin );
	}

	public function saveGroupForum( $group, $category, $user, $plugin ) {
		$model				=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		if ( $group->get( 'parent' ) ) {
			$parent			=	$group->getParent();
			$params			=	$parent->getParams();
			$parentForum	=	$params->get( 'forum_id', null );
		} else {
			$params			=	$category->getParams();
			$parentForum	=	$params->get( 'forum_id', null );
		}

		if ( ! $parentForum ) {
			return;
		}

		cbgjForumsModel::unsetSession( $group->get( 'user_id' ) );
		cbgjForumsModel::saveForum( $parentForum, $group, $user, $plugin );
	}

	public function stateGroupForum( $state, $group, $category, $user, $plugin ) {
		$model	=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		cbgjForumsModel::unsetSession( $group->get( 'user_id' ) );
		cbgjForumsModel::stateForum( $group, $user, $plugin );
	}

	public function deleteGroupForums( $group, $category, $user, $plugin ) {
		$model	=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		cbgjForumsModel::unsetSession( $group->get( 'user_id' ) );
		cbgjForumsModel::deleteAllForums( $group, $user, $plugin );
	}

	public function getCategoryParam( $tabs, $category, $user, $plugin ) {
		$model		=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		$authorized	=	cbgjClass::getAuthorization( $category, null, $user );

		if ( ! cbgjClass::hasAccess( 'forum_categories', $authorized ) ) {
			return;
		}

		return $this->showParam( $tabs, $category, $user, $plugin );
	}

	public function getGroupParam( $tabs, $group, $category, $user, $plugin ) {
		$model		=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		$authorized	=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( ! cbgjClass::hasAccess( 'forum_cat_show', $authorized ) ) {
			return;
		}

		return $this->showParam( $tabs, $group, $user, $plugin );
	}

	public function showParam( $tabs, $row, $user, $plugin ) {
		global $_CB_framework;

		$model					=	cbgjForumsPlugin::getForumModel();

		if ( ! $model->installed ) {
			return;
		}

		if ( method_exists( $row, 'getCategory' ) ) {
			if ( $_CB_framework->getUi() == 2 ) {
				$location		=	CBTxt::T( 'group' );
			} else {
				$location		=	cbgjClass::getOverride( 'group' );
			}

			$authorized			=	cbgjClass::getAuthorization( $row->getCategory(), $row, $user );
		} else {
			if ( $_CB_framework->getUi() == 2 ) {
				$location		=	CBTxt::T( 'category' );
			} else {
				$location		=	cbgjClass::getOverride( 'category' );
			}

			$authorized			=	cbgjClass::getAuthorization( $row, null, $user );
		}

		$params					=	$row->getParams();
		$forumShow				=	$plugin->params->get( 'forum_show_config', 1 );
		$forumPublic			=	$plugin->params->get( 'forum_public_config', 1 );

		$input					=	array();

		$input['forum_show']	=	moscomprofilerHTML::yesnoSelectList( 'forum_show', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $forumShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_show', $params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['forum_public']	=	moscomprofilerHTML::yesnoSelectList( 'forum_public', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $forumPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'forum_public', $params->get( 'forum_public', $plugin->params->get( 'forum_public', 1 ) ) ) );

		if ( $_CB_framework->getUi() == 2 ) {
			$return				=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Forum' ) ), 'gjIntegrationsForum' )
								.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
								.			'<tbody>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Display' ) . '</div>'
								.					'<td width="40%">' . $input['forum_show'] . '</div>'
								.					'<td>' . CBTxt::Ph( 'Select usage of [location] forums.', array( '[location]' => $location ) ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Public' ) . '</div>'
								.					'<td width="40%">' . $input['forum_public'] . '</div>'
								.					'<td>' . CBTxt::Ph( 'Select if [location] forums tab is publicly visible.', array( '[location]' => $location ) ) . '</div>'
								.				'</tr>'
								.			'</tbody>'
								.		'</table>'
								.	$tabs->endTab();
		} else {
			if ( ( ! $forumShow ) && ( ! $forumPublic ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				return;
			}

			cbgjClass::getTemplate( 'cbgroupjiveforums_params' );

			if ( class_exists( 'HTML_cbgroupjiveforumsParams' ) ) {
				$return			=	HTML_cbgroupjiveforumsParams::showForumsParams( $row, $input, $user, $plugin );
			} else {
				$return			=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Forum' ) ), 'gjIntegrationsForum' );

				if ( $forumShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Display' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['forum_show']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select usage of [location] forums.', array( '[location]' => $location ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $forumPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Public' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['forum_public']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select if [location] forums tab is publicly visible.', array( '[location]' => $location ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				$return			.=	$tabs->endTab();
			}
		}

		return $return;
	}

	public function getTools( $msgs, $user, $plugin ) {
		$forum									=	$plugin->params->get( 'forum_id', null );
		$model									=	cbgjForumsPlugin::getForumModel();
		$configUrl								=	'<a href="' . cbgjClass::getPluginURL( array( 'config' ) ) . '">' . CBTxt::Th( 'Configuration' ) . '</a>';

		if ( ! $model->installed ) {
			$msgs->errors[]						=	CBTxt::Ph( ':: Forum :: No supported forum extension found - [config_url]', array( '[config_url]' => $configUrl ) );
		} elseif ( $forum ) {
			$catMissId							=	array();
			$catMiss							=	array();
			$grpMissId							=	array();
			$grpMiss							=	array();

			$categories							=	cbgjData::getCategories();

			if ( $categories ) foreach ( $categories as $category ) {
				$catUrl							=	'<a href="' . cbgjClass::getPluginURL( array( 'categories', 'edit', (int) $category->get( 'id' ) ) ) . '">' . $category->get( 'id' ) . '</a>';

				$catParams						=	$category->getParams();
				$catForumId						=	$catParams->get( 'forum_id', null );

				if ( ! $catForumId ) {
					$catMissId[]				=	$catUrl;
				} else {
					$catForum					=	cbgjForumsModel::getCategory( $catForumId );

					if ( ( ! $catForum ) || ( ! $catForum->name ) ) {
						$catMiss[]				=	$catUrl;
					}

					$groups						=	cbgjData::getGroups( null, array( 'category', '=', (int) $category->get( 'id' ) ) );

					if ( $groups ) foreach ( $groups as $group ) {
						$grpUrl					=	'<a href="' . cbgjClass::getPluginURL( array( 'groups', 'edit', (int) $group->get( 'id' ) ) ) . '">' . $group->get( 'id' ) . '</a>';

						$grpParams				=	$group->getParams();
						$grpForumId				=	$grpParams->get( 'forum_id', null );

						if ( ! $grpForumId ) {
							$grpMissId[]		=	$grpUrl;
						} else {
							$grpForum			=	cbgjForumsModel::getCategory( $grpForumId );

							if ( ( ! $grpForum ) || ( ! $grpForum->name ) ) {
								$grpMiss[]		=	$grpUrl;
							}
						}
					}
				}
			}

			$fixUrl								=	'<a href="' . cbgjClass::getPluginURL( array( 'plugin', 'fixallforums' ) ) . '">' . CBTxt::Th( 'Fix All' ) . '</a>';
			$recreateUrl						=	'<a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'plugin', 'recreateforums' ), CBTxt::T( 'Are you sure you want to recreate all Category and Group forums? Note this will delete all GroupJive forums and recreate them as new!' ) ) . '">' . CBTxt::Th( 'Recreate All' ) . '</a>';

			if ( ! empty( $catMissId ) ) {
				$msgs->errors[]					=	CBTxt::Ph( ':: Category :: Missing forum id - [cat] - [fixall] - [recreate]', array( '[cat]' => implode( ', ', $catMissId ), '[fixall]' => $fixUrl, '[recreate]' => $recreateUrl ) );
			}

			if ( ! empty( $catMiss ) ) {
				$msgs->errors[]					=	CBTxt::Ph( ':: Category :: Forum does not exist - [cat] - [fixall] - [recreate]', array( '[cat]' => implode( ', ', $catMiss ), '[fixall]' => $fixUrl, '[recreate]' => $recreateUrl ) );
			}

			if ( ! empty( $grpMissId ) ) {
				$msgs->errors[]					=	CBTxt::Ph( ':: Group :: Missing forum id - [grp] - [fixall] - [recreate]', array( '[grp]' => implode( ', ', $grpMissId ), '[fixall]' => $fixUrl, '[recreate]' => $recreateUrl ) );
			}

			if ( ! empty( $grpMiss ) ) {
				$msgs->errors[]					=	CBTxt::Ph( ':: Group :: Forum does not exist - [grp] - [fixall] - [recreate]', array( '[grp]' => implode( ', ', $grpMiss ), '[fixall]' => $fixUrl, '[recreate]' => $recreateUrl ) );
			}
		} else {
			$msgs->warnings[]					=	CBTxt::Ph( ':: Forum :: Missing forum category - [config_url]', array( '[config_url]' => $configUrl ) );
		}
	}

	public function fixAllForums( $params, $user, $plugin ) {
		if ( $params[0] == 'fixallforums' ) {
			$forum						=	$plugin->params->get( 'forum_id', null );

			if ( $forum ) {
				$categories				=	cbgjData::getCategories();

				if ( $categories ) foreach ( $categories as $category ) {
					$catParams			=	$category->getParams();
					$catForum			=	cbgjForumsModel::getCategory( $catParams->get( 'forum_id', null ) );

					if ( $catForum && $catForum->id ) {
						$this->saveCategoryForum( $category, $user, $plugin );
					} else {
						$this->setCategoryForum( $category, $user, $plugin );
					}

					$groups				=	cbgjData::getGroups( null, array( 'category', '=', (int) $category->get( 'id' ) ) );

					if ( $groups ) foreach ( $groups as $group ) {
						$grpParams		=	$group->getParams();
						$grpForum		=	cbgjForumsModel::getCategory( $grpParams->get( 'forum_id', null ) );

						if ( $grpForum && $grpForum->id ) {
							$this->saveGroupForum( $group, $category, $user, $plugin );
						} else {
							$this->setGroupForum( $group, $category, $user, $plugin );
						}
					}
				}

				cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Forums fixed successfully!' ), false, true );
			}

			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Forums not found.' ), false, true, 'error' );
		} elseif ( $params[0] == 'recreateforums' ) {
			$forum			=	$plugin->params->get( 'forum_id', null );

			if ( $forum ) {
				$categories	=	cbgjData::getCategories();

				if ( $categories ) foreach ( $categories as $category ) {
					$this->deleteCategoryForums( $category, $user, $plugin );
					$this->setCategoryForum( $category, $user, $plugin );

					$groups	=	cbgjData::getGroups( null, array( 'category', '=', (int) $category->get( 'id' ) ) );

					if ( $groups ) foreach ( $groups as $group ) {
						$this->deleteGroupForums( $group, $category, $user, $plugin );
						$this->setGroupForum( $group, $category, $user, $plugin );
					}
				}

				cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Forums recreated successfully!' ), false, true );
			}

			cbgjClass::getPluginURL( array( 'tools' ), CBTxt::T( 'Forums not found.' ), false, true, 'error' );
		}
	}

	public function getConfig( $tabs, $user, $plugin ) {
		$input							=	array();

		$listModel						=	array();
		$listModel[]					=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Auto Detect' ) );
		$listModel[]					=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'Kunena 2.0' ) );
		$listModel[]					=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'Kunena 1.7' ) );
		$listModel[]					=	moscomprofilerHTML::makeOption( '4', CBTxt::T( 'Kunena 1.5' ) );
		$input['forum_model']			=	moscomprofilerHTML::selectList( $listModel, 'forum_model', null, 'value', 'text', $plugin->params->get( 'forum_model', 1 ), 1, false, false );

		$model							=	cbgjForumsPlugin::getForumModel();

		if ( $model->installed ) {
			$listCategories				=	array();
			$listCategories[]			=	moscomprofilerHTML::makeOption( '', CBTxt::T( '- Select Category -' ) );
			$listCategories				=	array_merge( $listCategories, cbgjForumsModel::getBoards( $plugin ) );
			$input['forum_id']			=	moscomprofilerHTML::selectList( $listCategories, 'forum_id', null, 'value', 'text', $plugin->params->get( 'forum_id', null ), 1, false, false );
		} else {
			$input['forum_id']			=	CBTxt::Th( 'No supported forum extension found!' );
		}

		$input['forum_categories']		=	moscomprofilerHTML::yesnoSelectList( 'forum_categories', null, $plugin->params->get( 'forum_categories', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['forum_backlink']		=	moscomprofilerHTML::yesnoSelectList( 'forum_backlink', null, $plugin->params->get( 'forum_backlink', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['forum_paging']			=	moscomprofilerHTML::yesnoSelectList( 'forum_paging', null, $plugin->params->get( 'forum_paging', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['forum_search']			=	moscomprofilerHTML::yesnoSelectList( 'forum_search', null, $plugin->params->get( 'forum_search', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['forum_limitbox']		=	moscomprofilerHTML::yesnoSelectList( 'forum_limitbox', null, $plugin->params->get( 'forum_limitbox', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['forum_limit']			=	'<input type="text" id="forum_limit" name="forum_limit" value="' . (int) $plugin->params->get( 'forum_limit', 15 ) . '" class="inputbox" size="5" />';
		$input['forum_show']			=	moscomprofilerHTML::yesnoSelectList( 'forum_show', null, $plugin->params->get( 'forum_show', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['forum_show_config']		=	moscomprofilerHTML::yesnoSelectList( 'forum_show_config', null, $plugin->params->get( 'forum_show_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['forum_public']			=	moscomprofilerHTML::yesnoSelectList( 'forum_public', null, $plugin->params->get( 'forum_public', 1 ) );
		$input['forum_public_config']	=	moscomprofilerHTML::yesnoSelectList( 'forum_public_config', null, $plugin->params->get( 'forum_public_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$return							=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Forum' ) ), 'gjIntegrationsForum' )
										.		$tabs->startPane( 'gjIntegrationsForumTabs' )
										.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsForumGeneral' )
										.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
										.					'<tbody>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Forum' ) . '</th>'
										.							'<td width="40%">' . $input['forum_model'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Select supported forum to be used for the integration.' ). '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Category' ) . '</th>'
										.							'<td width="40%">' . $input['forum_id'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Select parent forum category which all forums (categories and groups) will be stored under.' ) . '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Categories' ) . '</th>'
										.							'<td width="40%">' . $input['forum_categories'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Enable or disable usage of Category forums display. Please note this will hide Category forums menu link and tab, but forums will still be created.' ) . '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Back Link' ) . '</th>'
										.							'<td width="40%">' . $input['forum_backlink'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Enable or disable display of back link in forum. Back link allows users to return to their previous location (e.g. overview, category, or group).' ) . '</td>'
										.						'</tr>'
										.					'</tbody>'
										.				'</table>'
										.			$tabs->endTab()
										.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Paging' ) ), 'gjIntegrationsForumPaging' )
										.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
										.					'<tbody>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Paging' ) . '</th>'
										.							'<td width="40%">' . $input['forum_paging'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Enable or disable usage of paging on forum category and group tab.' ) . '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::T( 'Page Limitbox' ) . '</th>'
										.							'<td width="50%">' . $input['forum_limitbox'] . '</td>'
										.							'<td>' . CBTxt::T( 'Enable or disable usage of page limit on forum category and group tab. Requires Paging to be Enabled.' ) . '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
										.							'<td width="50%">' . $input['forum_limit'] . '</td>'
										.							'<td>' . CBTxt::T( 'Input default page limit on forum category and group tab. Page limit determines how many posts are displayed per page.' ) . '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Search' ) . '</th>'
										.							'<td width="40%">' . $input['forum_search'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Enable or disable usage of searching on forum category and group tab.' ) . '</td>'
										.						'</tr>'
										.					'</tbody>'
										.				'</table>'
										.			$tabs->endTab()
										.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsForumDefaults' )
										.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
										.					'<tbody>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
										.							'<td width="40%">' . $input['forum_show'] . ' ' . $input['forum_show_config'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Select default value for category and group parameter "Display". Additionally select the display of the "Display" category and group parameter. Moderators are exempt from this configuration.' ) . '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Public' ) . '</th>'
										.							'<td width="40%">' . $input['forum_public'] . ' ' . $input['forum_public_config'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Select default value for category and group parameter "Public". Additionally select the display of the "Public" category and group parameter. Moderators are exempt from this configuration.' ) . '</td>'
										.						'</tr>'
										.					'</tbody>'
										.				'</table>'
										.			$tabs->endTab()
										.		$tabs->endPane()
										.	$tabs->endTab();

		return $return;
	}

	public function getAuthorization( &$access, $category, $group, $user, $owner, $row, $plugin ) {
		if ( $plugin->params->get( 'forum_id', null ) ) {
			if ( $plugin->params->get( 'forum_categories', 1 ) ) {
				$access[]			=	'forum_categories';
			}

			if ( isset( $category->id ) && cbgjClass::hasAccess( 'cat_approved', $access ) ) {
				$params				=	$category->getParams();
				$forumId			=	$params->get( 'forum_id', null );
				$forumShow			=	$params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) );
				$forumPublic		=	$params->get( 'forum_public', $plugin->params->get( 'forum_public', 1 ) );

				if ( cbgjClass::hasAccess( 'mod_lvl1', $access ) && $forumShow && $forumId ) {
					$access[]		=	'forum_cat_edit';
				}

				if ( ( $forumPublic || cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_has_grp', 'cat_is_usr' ), $access ) ) && $forumShow && $forumId ) {
					$access[]		=	'forum_cat_show';

					if ( cbgjClass::hasAccess( array( 'mod_lvl1', 'cat_has_grp', 'cat_is_usr' ), $access ) ) {
						$access[]	=	'forum_cat_access';
					}

					if ( cbgjClass::hasAccess( 'forum_cat_access', $access ) || ( ( cbGetParam( $_GET, 'plugin', null ) == 'cbgroupjive' ) && ( cbGetParam( $_GET, 'action', null ) == 'categories' ) && cbGetParam( $_GET, 'cat', null ) ) ) {
						$access[]	=	'forum_cat_read';
					}
				}
			}

			if ( isset( $group->id ) && cbgjClass::hasAccess( array( 'forum_cat_show', 'grp_approved' ), $access, true ) ) {
				$params				=	$group->getParams();
				$forumId			=	$params->get( 'forum_id', null );
				$forumShow			=	$params->get( 'forum_show', $plugin->params->get( 'forum_show', 1 ) );
				$forumPublic		=	$params->get( 'forum_public', $plugin->params->get( 'forum_public', 1 ) );

				if ( cbgjClass::hasAccess( 'mod_lvl5', $access ) && $forumShow && $forumId ) {
					$access[]		=	'forum_grp_edit';
				}

				if ( ( $forumPublic || cbgjClass::hasAccess( 'mod_lvl5', $access ) ) && $forumShow && $forumId ) {
					$access[]		=	'forum_grp_show';

					if ( cbgjClass::hasAccess( 'mod_lvl5', $access ) ) {
						$access[]	=	'forum_grp_access';
					}

					if ( cbgjClass::hasAccess( 'forum_grp_access', $access ) || ( ( cbGetParam( $_GET, 'plugin', null ) == 'cbgroupjive' ) && ( cbGetParam( $_GET, 'action', null ) == 'groups' ) && cbGetParam( $_GET, 'grp', null ) ) ) {
						$access[]	=	'forum_grp_read';
					}
				}
			}
		}
	}
}

cbgjForumsPlugin::getForumModel();
?>