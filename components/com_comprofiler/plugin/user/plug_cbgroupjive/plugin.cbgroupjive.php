<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerFunction( 'onAfterDeleteUser', 'deleteUser', 'cbgjPlugin' );
$_PLUGINS->registerFunction( 'onAfterUserRegistration', 'acceptInvites', 'cbgjPlugin' );
$_PLUGINS->registerFunction( 'onAfterNewUser', 'acceptInvites', 'cbgjPlugin' );
$_PLUGINS->registerFunction( 'onAfterBackendUsersList', 'onAfterBackendUsersList', 'cbgjPlugin' );

class cbgjPlugin extends cbPluginHandler {

	public function onAfterBackendUsersList( $listId, &$rows, &$pageNav, &$search, &$lists, $option, $selectTagAttribs ) {
		global $_CB_framework;

		$plugin		=	cbgjClass::getPlugin();

		$_CB_framework->document->addHeadStyleSheet( $plugin->livePath . '/admin.' . $plugin->element . '.css' );

		$url		=	cbgjClass::getPluginURL( array( 'users', 'edit' ), null, false );

		$toolbar	=	'<a href="#" id="gjaddgroup" class="cbtoolbar cbtoolbaraction">'
					.		'<span class="cbicon-32-gjplugin" title="' . htmlspecialchars( CBTxt::T( 'Join GJ Group' ) ) . '"></span>'
					.		CBTxt::T( 'Join GJ Group' )
					.	'</a>'
					.	'<span class="cbtoolbarspacer" style="width:20px;">&nbsp;</span>';

		$js			=	"$( '.cbtoolbaractions' ).delegate( '#gjaddgroup', 'click', function() {"
					.		"var usersChecked = new Array();"
					.		"$( '#cbshowusersform input[name=\"cid[]\"]:checked' ).each( function() {"
					.			"usersChecked.push( $( this ).val() );"
					.		"});"
					.		"if ( ! usersChecked.length ) {"
					.			"alert( '" . addslashes( CBTxt::T( 'Please select a user from the list to add a CB GroupJive Group to.' ) ) . "' );"
					.		"} else {"
					.			"window.location = '" . addslashes( $url ) . "&users=' + usersChecked.join( '|*|' );"
					.		"}"
					.	"});"
					.	"$( '.cbtoolbaractions' ).prepend( '" . addslashes( $toolbar ) . "' );";

		$_CB_framework->outputCbJQuery( $js );
	}

	/**
	 * finds and deletes a users invites, their place within groups, their groups, and their categories when deleted within CB
	 *
	 * @param  moscomprofilerUser $user
	 * @param  boolean            $deleted
	 */
	public function deleteUser( $user, $deleted ) {
		$invites		=	cbgjData::getInvites( null, array( 'user_id', '=', (int) $user->id, array( 'user', '=', (int) $user->id ) ) );

		if ( $invites ) foreach ( $invites as $invite ) {
			$invite->delete();
		}

		$usrs			=	cbgjData::getUsers( null, array( 'user_id', '=', (int) $user->id ) );

		if ( $usrs ) foreach ( $usrs as $usr ) {
			$usr->deleteAll();
		}

		$groups			=	cbgjData::getGroups( null, array( 'user_id', '=', (int) $user->id ) );

		if ( $groups ) foreach ( $groups as $group ) {
			$group->deleteAll();
		}

		$categories		=	cbgjData::getCategories( null, array( 'user_id', '=', (int) $user->id ) );

		if ( $categories ) foreach ( $categories as $category ) {
			$category->deleteAll();
		}

		$notifications	=	cbgjData::getNotifications( null, array( 'user_id', '=', (int) $user->id ) );

		if ( $notifications ) foreach ( $notifications as $notification ) {
			$notification->delete();
		}
	}

	/**
	 * finds and accepts a users invites when registered within CB (front and backend)
	 *
	 * @param  moscomprofilerUser $user
	 */
	public function acceptInvites( $user ) {
		$plugin					=	cbgjClass::getPlugin();

		if ( $plugin->params->get( 'group_invites_accept', 1 ) ) {
			$invites			=	cbgjData::getInvites( null, array( 'email', '=', $user->email ) );

			if ( $invites ) foreach ( $invites as $invite ) {
				$invite->set( 'accepted', cbgjClass::getUTCDate() );
				$invite->set( 'user', (int) $user->id );

				if ( $invite->store() ) {
					$row		=	cbgjData::getUsers( null, array( array( 'group', '=', (int) $invite->get( 'group' ) ), array( 'user_id', '=', (int) $user->id ) ), null, null, false );

					if ( ! $row->get( 'id' ) ) {
						$row->set( 'user_id', (int) $user->id );
						$row->set( 'group', (int) $invite->get( 'group' ) );
						$row->set( 'date', cbgjClass::getUTCDate() );
						$row->set( 'status', 1 );

						$row->store();
					}
				}

				$notification	=	cbgjData::getNotifications( array( array( 'grp_usr_notifications' ), 'owner' ), array( array( 'type', '=', 'group' ), array( 'item', '=', (int) $invite->get( 'group' ) ), array( 'user_id', '=', (int) $invite->get( 'user_id' ) ), array( 'params', 'CONTAINS', 'group_inviteaccept=1' ) ), null, null, false );

				if ( $notification->get( 'id' ) ) {
					$subject	=	CBTxt::T( '[group_name] - Invite Accepted!' );
					$message	=	CBTxt::T( '[user] has accepted your invite to join [group] in [category]!' );

					cbgjClass::getNotification( $notification->get( 'user_id' ), $invite->get( 'user' ), $subject, $message, 1, $invite->getCategory(), $invite->getGroup() );
				}
			}
		}
	}
}
?>