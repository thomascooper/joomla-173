<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerFunction( 'onLogChange', 'onLogChange', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'onAfterUserRegistration', 'onAfterUserRegistration', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'onAfterLogin', 'onAfterLogin', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'onAfterLogout', 'onAfterLogout', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'onAfterUserUpdate', 'onAfterUserUpdate', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'onAfterUserAvatarUpdate', 'onAfterUserAvatarUpdate', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'onAfterAcceptConnection', 'onAfterAcceptConnection', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'onAfterUserProfileDisplay', 'onAfterUserProfileDisplay', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateCategory', 'gj_onAfterCreateCategory', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateGroup', 'gj_onAfterCreateGroup', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterJoinGroup', 'gj_onAfterJoinGroup', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gjint_onAfterCreateEvent', 'gjint_onAfterCreateEvent', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gjint_onAfterUpdateEventAttendance', 'gjint_onAfterUpdateEventAttendance', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gjint_onAfterCreateFile', 'gjint_onAfterCreateFile', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gjint_onAfterCreatePhoto', 'gjint_onAfterCreatePhoto', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gjint_onAfterCreateVideo', 'gjint_onAfterCreateVideo', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'gjint_onAfterCreateWall', 'gjint_onAfterCreateWall', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'kunenaIntegration', 'kunenaIntegration', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'cbblogs_onAfterCreateBlog', 'cbblogs_onAfterCreateBlog', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'activity_addActivity', 'addActivity', 'cbactivityPlugin' );
$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'deleteActivity', 'cbactivityPlugin' );

class cbactivityPlugin extends cbPluginHandler {
	var $_fieldsUpdated	=	array();

	public function onLogChange( $event, $objectType, $elementType, $user, $plugin, $field, $oldValues, $newValues, $reason ) {
		global $_CB_framework;

		if ( ( $_CB_framework->getUi() == 1 ) && $field && $this->params->get( 'activity_profile', 1 ) && ( $oldValues != $newValues ) && ( ! in_array( $field->name, array( 'params', 'avatar' ) ) ) ) {
			$this->_fieldsUpdated[]	=	$field;
		}
	}

	public function onAfterUserRegistration( $user ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_registration', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $user->get( 'id' ) );
			$activity->set( 'type', 'profile' );
			$activity->set( 'subtype', 'registration' );
			$activity->set( 'title', 'has joined [sitename_linked]' );
			$activity->set( 'icon', 'bookmark' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function onAfterLogin( $user ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_login', 0 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $user->get( 'id' ) );
			$activity->set( 'type', 'profile' );
			$activity->set( 'subtype', 'login' );
			$activity->set( 'title', 'has logged in' );
			$activity->set( 'icon', 'eye-open' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function onAfterLogout( $user ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_logout', 0 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $user->get( 'id' ) );
			$activity->set( 'type', 'profile' );
			$activity->set( 'subtype', 'logout' );
			$activity->set( 'title', 'has logged out' );
			$activity->set( 'icon', 'eye-close' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function onAfterUserUpdate( $user, $userDuplicate, $oldUser ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_profile', 1 ) && isset( $this->_fieldsUpdated ) && $this->_fieldsUpdated ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $user->get( 'id' ) );
			$activity->set( 'type', 'profile' );
			$activity->set( 'subtype', 'update' );
			$activity->set( 'title', 'has an updated profile' );
			$activity->set( 'icon', 'pencil' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function onAfterUserAvatarUpdate( $user, $userDuplicate, $isModerator, $newFilename ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_avatar', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $user->get( 'id' ) );
			$activity->set( 'type', 'profile' );
			$activity->set( 'subtype', 'avatar' );
			$activity->set( 'title', 'has an updated profile picture' );
			$activity->set( 'icon', 'camera' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function onAfterAcceptConnection( $userId, $connectionId, $useMutualConnections, $autoAddConnections ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_connections', 1 ) ) {
			$activity		=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $connectionId );
			$activity->set( 'user', (int) $userId );
			$activity->set( 'type', 'profile' );
			$activity->set( 'subtype', 'connection' );
			$activity->set( 'title', 'and [user_linked] are now connected' );
			$activity->set( 'icon', 'plus' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();

			if ( $useMutualConnections ) {
				$activity	=	new cbactivityActivity( $_CB_database );

				$activity->set( 'user_id', (int) $userId );
				$activity->set( 'user', (int) $connectionId );
				$activity->set( 'type', 'profile' );
				$activity->set( 'subtype', 'connection' );
				$activity->set( 'title', 'and [user_linked] are now connected' );
				$activity->set( 'icon', 'plus' );
				$activity->set( 'date', cbactivityClass::getUTCDate() );

				$activity->store();
			}
		}
	}

	public function onAfterUserProfileDisplay( $user ) {
		global $_CB_framework, $_CB_database;

		$myId							=	$_CB_framework->myId();

		if ( $myId ) {
			$userId						=	$user->get( 'id' );

			if ( class_exists( 'pbProfileBookEntry' ) ) {
				$pbComment				=	stripslashes( cbGetParam( $_POST, 'profilebookpostercomments' ) );
				$pbGuest				=	$this->params->get( 'activity_pb_guest_create', 1 );
				$pbWall					=	$this->params->get( 'activity_pb_wall_create', 1 );
				$pbBlog					=	$this->params->get( 'activity_pb_blog_create', 1 );

				if ( $pbComment && ( $pbGuest || $pbWall || $pbBlog ) ) {
					$query				=	'SELECT ' . $_CB_database->NameQuote( 'id' )
										.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plug_profilebook' )
										.	"\n WHERE " . $_CB_database->NameQuote( 'postercomment' ) . " = " . $_CB_database->Quote( $pbComment )
										.	"\n AND " . $_CB_database->NameQuote( 'posterid' ) . " = " . (int) $myId
										.	"\n ORDER BY " . $_CB_database->NameQuote( 'date' ) . " DESC";
					$_CB_database->setQuery( $query );
					$pbEntry			=	$_CB_database->loadResult();

					if ( $pbEntry ) {
						if ( $pbGuest && ( cbGetParam( $_POST, 'profilebookformactiong' ) == 'new' ) ) {
							$activity	=	new cbactivityActivity( $_CB_database );

							$activity->set( 'user_id', (int) $myId );
							$activity->set( 'user', (int) $userId );
							$activity->set( 'type', 'profilebook' );
							$activity->set( 'subtype', 'guest' );
							$activity->set( 'item', (int) $pbEntry );
							$activity->set( 'title', 'posted a guestbook entry to [user_linked]' );
							$activity->set( 'icon', 'book' );
							$activity->set( 'date', cbactivityClass::getUTCDate() );

							$activity->store();
						} elseif ( $pbWall && ( cbGetParam( $_POST, 'profilebookformactionw' ) == 'new' ) ) {
							$activity	=	new cbactivityActivity( $_CB_database );

							$activity->set( 'user_id', (int) $myId );
							$activity->set( 'type', 'profilebook' );
							$activity->set( 'subtype', 'wall' );
							$activity->set( 'item', (int) $pbEntry );
							$activity->set( 'icon', 'comment' );
							$activity->set( 'date', cbactivityClass::getUTCDate() );

							if ( $myId != $userId ) {
								$activity->set( 'user', (int) $userId );
								$activity->set( 'title', 'posted a wall entry to [user_linked]' );
							} else {
								$activity->set( 'title', 'posted a wall entry' );
							}

							$activity->store();
						} elseif ( $pbBlog && ( cbGetParam( $_POST, 'profilebookformactionb' ) == 'new' ) ) {
							$activity	=	new cbactivityActivity( $_CB_database );

							$activity->set( 'user_id', (int) $myId );
							$activity->set( 'type', 'profilebook' );
							$activity->set( 'subtype', 'blog' );
							$activity->set( 'item', (int) $pbEntry );
							$activity->set( 'title', 'posted a blog entry' );
							$activity->set( 'icon', 'font' );
							$activity->set( 'date', cbactivityClass::getUTCDate() );

							$activity->store();
						}
					}
				}
			}

			if ( class_exists( 'getProfileGalleryTab' ) ) {
				$pgTitle				=	stripslashes( cbGetParam( $_POST, 'profilegallerypgitemtitle' ) );

				if ( $pgTitle && $this->params->get( 'activity_pg_create', 1 ) ) {
					$query				=	'SELECT *'
										.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plug_profilegallery' )
										.	"\n WHERE " . $_CB_database->NameQuote( 'pgitemtitle' ) . " = " . $_CB_database->Quote( $pgTitle )
										.	"\n AND " . $_CB_database->NameQuote( 'userid' ) . " = " . (int) $myId
										.	"\n ORDER BY " . $_CB_database->NameQuote( 'pgitemdate' ) . " DESC";
					$_CB_database->setQuery( $query );
					$pgEntry			=	null;
					$_CB_database->loadObject( $pgEntry );

					if ( $pgEntry ) {
						if ( in_array( $pgEntry->pgitemtype, array( 'jpg', 'jpeg', 'gif', 'png', 'bmp' ) ) ) {
							$isImage	=	true;
						} else {
							$isImage	=	false;
						}

						$activity		=	new cbactivityActivity( $_CB_database );

						$activity->set( 'user_id', (int) $myId );
						$activity->set( 'type', 'profilegallery' );
						$activity->set( 'item', (int) $pgEntry->id );
						$activity->set( 'date', cbactivityClass::getUTCDate() );

						if ( $isImage ) {
							$activity->set( 'subtype', 'image' );
							$activity->set( 'title', 'uploaded a gallery image' );
							$activity->set( 'icon', 'picture' );
						} else {
							$activity->set( 'subtype', 'file' );
							$activity->set( 'title', 'uploaded a gallery file' );
							$activity->set( 'icon', 'download-alt' );
						}

						$activity->store();
					}
				}
			}
		}
	}

	public function gj_onAfterCreateCategory( $row, $user, $plugin ) {
		global $_CB_framework, $_CB_database;

		if ( ( $_CB_framework->getUi() == 1 ) && $this->params->get( 'activity_gj_cat_create', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'category' );
			$activity->set( 'item', (int) $row->get( 'id' ) );
			$activity->set( 'title', 'created a category' );
			$activity->set( 'icon', 'folder-open' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function gj_onAfterCreateGroup( $row, $category, $user, $plugin ) {
		global $_CB_framework, $_CB_database;

		if ( ( $_CB_framework->getUi() == 1 ) && $this->params->get( 'activity_gj_grp_create', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'group' );
			$activity->set( 'item', (int) $row->get( 'id' ) );
			$activity->set( 'title', 'created a group' );
			$activity->set( 'icon', 'user' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function gj_onAfterJoinGroup( $row, $group, $category, $user, $plugin ) {
		global $_CB_framework, $_CB_database;

		if ( ( $_CB_framework->getUi() == 1 ) && $this->params->get( 'activity_gj_grp_join', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'group' );
			$activity->set( 'item', (int) $group->get( 'id' ) );
			$activity->set( 'title', 'joined a group' );
			$activity->set( 'icon', 'user' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function gjint_onAfterCreateEvent( $row, $group, $category, $user, $plugin ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_gj_events_create', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'event' );
			$activity->set( 'item', (int) $row->get( 'id' ) );
			$activity->set( 'title', 'scheduled a group event' );
			$activity->set( 'icon', 'calendar' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function gjint_onAfterUpdateEventAttendance( $row, $group, $category, $user, $plugin ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_gj_events_attend', 1 ) ) {
			$attendees				=	$row->getAttendees();
			$attending				=	false;

			if ( $attendees ) foreach ( $attendees as $attendance => $attendances ) {
				if ( ( $attendance == 'yes' ) && $attendances ) foreach ( $attendances as $attendee ) {
					if ( $attendee == $user->get( 'id' ) ) {
						$attending	=	true;
					}
				}
			}

			if ( $attending ) {
				$activity			=	new cbactivityActivity( $_CB_database );

				$activity->set( 'user_id', (int) $user->get( 'id' ) );
				$activity->set( 'type', 'groupjive' );
				$activity->set( 'subtype', 'event' );
				$activity->set( 'item', (int) $row->get( 'id' ) );
				$activity->set( 'title', 'is attending a group event' );
				$activity->set( 'icon', 'calendar' );
				$activity->set( 'date', cbactivityClass::getUTCDate() );

				$activity->store();
			}
		}
	}

	public function gjint_onAfterCreateFile( $row, $group, $category, $user, $plugin ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_gj_files_create', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'file' );
			$activity->set( 'item', (int) $row->get( 'id' ) );
			$activity->set( 'title', 'uploaded a group file' );
			$activity->set( 'icon', 'download-alt' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function gjint_onAfterCreatePhoto( $row, $group, $category, $user, $plugin ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_gj_photos_create', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'photo' );
			$activity->set( 'item', (int) $row->get( 'id' ) );
			$activity->set( 'title', 'uploaded a group photo' );
			$activity->set( 'icon', 'picture' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function gjint_onAfterCreateVideo( $row, $group, $category, $user, $plugin ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_gj_videos_create', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'video' );
			$activity->set( 'item', (int) $row->get( 'id' ) );
			$activity->set( 'title', 'published a group video' );
			$activity->set( 'icon', 'film' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function gjint_onAfterCreateWall( $row, $group, $category, $user, $plugin ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_gj_wall_create', 1 ) && ( ! $row->get( 'reply' ) ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'wall' );
			$activity->set( 'item', (int) $row->get( 'id' ) );
			$activity->set( 'title', 'posted in a group' );
			$activity->set( 'icon', 'comment' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		} elseif ( $this->params->get( 'activity_gj_wall_reply', 1 ) && $row->get( 'reply' ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $row->get( 'user_id' ) );
			$activity->set( 'type', 'groupjive' );
			$activity->set( 'subtype', 'wall' );
			$activity->set( 'item', (int) $row->get( 'id' ) );
			$activity->set( 'title', 'replied to a group post' );
			$activity->set( 'icon', 'comment' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function kunenaIntegration( $trigger, $config, $params ) {
		global $_CB_database;

		if ( class_exists( 'KunenaForum' ) ) {
			if ( $this->params->get( 'activity_kunena_create', 1 ) && ( $trigger == 'onAfterPost' ) ) {
				$userId			=	(int) $params['actor'];

				if ( $userId ) {
					$message	=	$params['message'];

					$activity	=	new cbactivityActivity( $_CB_database );

					$activity->set( 'user_id', $userId );
					$activity->set( 'type', 'kunena' );
					$activity->set( 'subtype', 'message' );
					$activity->set( 'item', (int) $message->get( 'id' ) );
					$activity->set( 'title', 'created a new discussion' );
					$activity->set( 'icon', 'folder-open' );
					$activity->set( 'date', cbactivityClass::getUTCDate() );

					$activity->store();
				}
			} elseif ( $this->params->get( 'activity_kunena_reply', 1 ) && ( $trigger == 'onAfterReply' ) ) {
				$userId			=	(int) $params['actor'];

				if ( $userId ) {
					$message	=	$params['message'];

					$activity	=	new cbactivityActivity( $_CB_database );

					$activity->set( 'user_id', $userId );
					$activity->set( 'type', 'kunena' );
					$activity->set( 'subtype', 'message' );
					$activity->set( 'item', (int) $message->get( 'id' ) );
					$activity->set( 'title', 'replied to a discussion' );
					$activity->set( 'icon', 'folder-open' );
					$activity->set( 'date', cbactivityClass::getUTCDate() );

					$activity->store();
				}
			}
		}
	}

	public function cbblogs_onAfterCreateBlog( $row, $article, $user, $plugin ) {
		global $_CB_database;

		if ( $this->params->get( 'activity_cbblogs_create', 1 ) ) {
			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $user->get( 'id' ) );
			$activity->set( 'type', 'cbblogs' );
			$activity->set( 'item', (int) $article->id );
			$activity->set( 'title', 'posted a blog entry' );
			$activity->set( 'icon', 'font' );
			$activity->set( 'date', cbactivityClass::getUTCDate() );

			$activity->store();
		}
	}

	public function addActivity( $userId, $type, $user = null, $subtype = null, $item = null, $from = null, $to = null, $title = null, $message = null, $icon = null, $class = null, $date = null ) {
		global $_CB_database;

		if ( $userId && $type ) {
			if ( ! $date ) {
				$date	=	cbactivityClass::getUTCDate();
			}

			$activity	=	new cbactivityActivity( $_CB_database );

			$activity->set( 'user_id', (int) $userId );
			$activity->set( 'type', $type );

			if ( $user ) {
				$activity->set( 'user', (int) $user );
			}

			if ( $subtype ) {
				$activity->set( 'subtype', $subtype );
			}

			if ( $item ) {
				$activity->set( 'item', $item );
			}

			if ( $from ) {
				$activity->set( 'from', $from );
			}

			if ( $to ) {
				$activity->set( 'to', $to );
			}

			if ( $title ) {
				$activity->set( 'title', $title );
			}

			if ( $message ) {
				$activity->set( 'message', $message );
			}

			if ( $icon ) {
				$activity->set( 'icon', $icon );
			}

			if ( $class ) {
				$activity->set( 'class', $class );
			}

			$activity->set( 'date', $date );

			$activity->store();
		}
	}

	public function deleteActivity( $user ) {
		$plugin		=	cbactivityClass::getPlugin();

		if ( $plugin->params->get( 'general_delete', 1 ) ) {
			$rows	=	cbactivityData::getActivity( array( 'user_id', '=', (int) $user->id, array( 'user', '=', (int) $user->id ) ), array(), 0, true, true );

			if ( $rows ) foreach ( $rows as $row ) {
				$row->delete();
			}
		}
	}
}
?>