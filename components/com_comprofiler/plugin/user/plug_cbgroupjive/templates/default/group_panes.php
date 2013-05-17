<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveGroupPanes {

	/**
	 * render frontend group panes
	 *
	 * @param cbgjGroup $row
	 * @param cbgjCategory $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showGroupPanes( $row, $category, $user, $plugin ) {
		$authorized		=	cbgjClass::getAuthorization( $category, $row, $user );

		if ( $row->get( 'published' ) == 1 ) {
			$state		=	'<div><i class="icon-ban-circle"></i> <a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'unpublish', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to unpublish this [group]?', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) . '">' . CBTxt::Ph( 'Unpublish [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div>';
		} else {
			$state		=	'<div><i class="icon-ok"></i> <a href="' . cbgjClass::getPluginURL( array( 'groups', 'publish', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'Publish [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div>';
		}

		$groupAdmins	=	$row->getAdmins();
		$groupMods		=	$row->getModerators();

		$return			=	'<legend class="gjHeaderTitle">' . $row->getName() . '</legend>'
						.	'<div class="gjGrid row-fluid">'
						.		'<div class="gjGridLeft span9">'
						.			'<div class="gjGridLeftLogo span4">'
						.				$row->getLogo( true )
						.			'</div>'
						.			'<div class="gjGridLeftInfo span8">'
						.				cbgjClass::getIntegrations( 'gj_onBeforeGroupInfo', array( $row, $category, $user, $plugin ) )
						.				( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() ? '<div>' . cbgjClass::getOverride( 'group', true ) . ': ' . $row->nestedCount() . '</div>' : null )
						.				( $row->userCount() ? '<div>' . cbgjClass::getOverride( 'user', true ) . ': ' . $row->userCount() . '</div>' : null )
						.				( $row->get( 'user_id' ) ? '<div>' . cbgjClass::getOverride( 'owner' ) . ': ' . $row->getOwnerName( true ) . '</div>' : null )
						.				( ! empty( $groupMods ) ? '<div>' . cbgjClass::getOverride( 'moderator', true ) . ': ' . implode( ', ', $groupMods ) . '</div>' : null )
						.				( ! empty( $groupAdmins ) ? '<div>' . cbgjClass::getOverride( 'admin', true ) . ': ' . implode( ', ', $groupAdmins ) . '</div>' : null )
						.				'<div>' . CBTxt::Ph( 'Type: [grp_type]', array( '[grp_type]' => $row->getType() ) ) . '</div>'
						.				'<div>' . CBTxt::Ph( 'Access: [grp_access]', array( '[grp_access]' => $row->getAccess() ) ) . '</div>'
						.				'<div>' . cbgjClass::getOverride( 'category' ) . ': ' . $category->getName( 0, true ) . '</div>'
						.				( $row->get( 'parent' ) ? '<div>' . cbgjClass::getOverride( 'group' ) . ': ' . $row->getParent()->getName( 0, true ) . '</div>' : null )
						.				'<div>' . CBTxt::Ph( 'Created: [grp_date]', array( '[grp_date]' => cbFormatDate( $row->get( 'date' ), 1, false ) ) ) . '</div>'
						.				cbgjClass::getIntegrations( 'gj_onAfterGroupInfo', array( $row, $category, $user, $plugin ) )
						.			'</div>';

		if ( $row->get( 'description' ) ) {
			$return		.=			'<div class="gjGridLeftDesc span12 well well-small">'
						.				$row->getDescription()
						.			'</div>';
		}

		$return			.=		'</div>'
						.		'<div class="gjGridRight span3">'
						.			cbgjClass::getIntegrations( 'gj_onBeforeGroupMenu', array( $row, $category, $user, $plugin ), null, null )
						.			( cbgjClass::hasAccess( array( 'grp_join', 'grp_approved' ), $authorized, true ) ? '<div><i class="icon-plus"></i> <a href="' . cbgjClass::getPluginURL( array( 'groups', 'join', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) ) . '">' . ( cbgjClass::hasAccess( 'grp_invited', $authorized ) ? CBTxt::Th( 'Accept Invite' ) : CBTxt::Ph( 'Join [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( array( 'grp_nested_create', 'grp_approved' ), $authorized, true ) ? '<div><i class="icon-plus"></i> <a href="' . cbgjClass::getPluginURL( array( 'groups', 'new', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'New [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( array( 'grp_leave', 'grp_approved' ), $authorized, true ) ? '<div><i class="icon-minus"></i> <a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'leave', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to leave this [group]?', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) . '">' . CBTxt::Ph( 'Leave [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( 'mod_lvl3', $authorized ) ? '<div><i class="icon-pencil"></i> <a href="' . cbgjClass::getPluginURL( array( 'groups', 'edit', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'Edit [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( 'grp_message', $authorized ) && $row->userCount() ? '<div><i class="icon-envelope"></i> <a href="' . cbgjClass::getPluginURL( array( 'groups', 'message', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'Message [users]', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( 'grp_can_publish', $authorized ) ? $state : null )
						.			( cbgjClass::hasAccess( 'mod_lvl2', $authorized ) ? '<div><i class="icon-remove"></i> <a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'delete', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to delete this [group] and all its associated [users]?', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) ) . '">' . CBTxt::Ph( 'Delete [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div>' : null )
						.			cbgjClass::getIntegrations( 'gj_onAfterGroupMenu', array( $row, $category, $user, $plugin ), null, null )
						.			( cbgjClass::hasAccess( 'grp_usr_notifications', $authorized ) ? '<div><i class="icon-info-sign"></i> <a href="' . cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Th( 'Notifications' ) . '</a></div>' : null )
						.			( $row->get( 'parent' ) ? '<div><i class="icon-share-alt"></i> <a href="' . $row->getParent()->getUrl() . '">' . CBTxt::Ph( 'Back to [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div>' : null )
						.			( ! $row->get( 'parent' ) ? '<div><i class="icon-share-alt"></i> <a href="' . $category->getUrl() . '">' . CBTxt::Ph( 'Back to [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div>' : null )
						.		'</div>'
						.	'</div>';

		return $return;
	}
}
?>