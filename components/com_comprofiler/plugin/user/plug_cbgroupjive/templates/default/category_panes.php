<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveCategoryPanes {

	/**
	 * render frontend category panes
	 *
	 * @param cbgjCategory $row
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 * @return string
	 */
	static function showCategoryPanes( $row, $user, $plugin ) {
		$authorized		=	cbgjClass::getAuthorization( $row, null, $user );

		if ( $row->get( 'published' ) == 1 ) {
			$state		=	'<div><i class="icon-ban-circle"></i> <a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'unpublish', (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to unpublish this [category]?', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) ) . '">' . CBTxt::Ph( 'Unpublish [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div>';
		} else {
			$state		=	'<div><i class="icon-ok"></i> <a href="' . cbgjClass::getPluginURL( array( 'categories', 'publish', (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'Publish [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div>';
		}

		$return			=	'<legend class="gjHeaderTitle">' . $row->getName() . '</legend>'
						.	'<div class="gjGrid row-fluid">'
						.		'<div class="gjGridLeft span9">'
						.			'<div class="gjGridLeftLogo span4">'
						.				$row->getLogo( true )
						.			'</div>'
						.			'<div class="gjGridLeftInfo span8">'
						.				cbgjClass::getIntegrations( 'gj_onBeforeCategoryInfo', array( $row, $user, $plugin ) )
						.				( ( ( ( ! $row->get( 'nested' ) ) && cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ) || $row->get( 'nested' ) ) && $row->nestedCount() ? '<div>' . cbgjClass::getOverride( 'category', true ) . ': ' . $row->nestedCount() . '</div>' : null )
						.				( $row->groupCount() ? '<div>' . cbgjClass::getOverride( 'group', true ) . ': ' . $row->groupCount() . '</div>' : null )
						.				( $row->get( 'user_id' ) ? '<div>' . cbgjClass::getOverride( 'owner' ) . ': ' . $row->getOwnerName( true ) . '</div>' : null )
						.				'<div>' . CBTxt::Ph( 'Types: [cat_types]', array( '[cat_types]' => implode( ', ', $row->getTypes() ) ) ) . '</div>'
						.				'<div>' . CBTxt::Ph( 'Access: [cat_access]', array( '[cat_access]' => $row->getAccess() ) ) . '</div>'
						.				( $row->get( 'parent' ) ? '<div>' . cbgjClass::getOverride( 'category' ) . ': ' . $row->getParent()->getName( 0, true ) . '</div>' : null )
						.				'<div>' . CBTxt::Ph( 'Created: [cat_date]', array( '[cat_date]' => cbFormatDate( $row->get( 'date' ), 1, false ) ) ) . '</div>'
						.				cbgjClass::getIntegrations( 'gj_onAfterCategoryInfo', array( $row, $user, $plugin ) )
						.			'</div>';

		if ( $row->get( 'description' ) ) {
			$return		.=			'<div class="gjGridLeftDesc span12 well well-small">'
						.				$row->getDescription()
						.			'</div>';
		}

		$return			.=		'</div>'
						.		'<div class="gjGridRight span3">'
						.			cbgjClass::getIntegrations( 'gj_onBeforeCategoryMenu', array( $row, $user, $plugin ), null, null )
						.			( cbgjClass::hasAccess( 'cat_nested_create', $authorized ) ? '<div><i class="icon-plus"></i> <a href="' . cbgjClass::getPluginURL( array( 'categories', 'new', (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'New [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( 'cat_grp_create', $authorized ) ? '<div><i class="icon-plus"></i> <a href="' . cbgjClass::getPluginURL( array( 'groups', 'new', (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'New [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ? '<div><i class="icon-pencil"></i> <a href="' . cbgjClass::getPluginURL( array( 'categories', 'edit', (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'Edit [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( 'cat_message', $authorized ) && $row->groupCount() ? '<div><i class="icon-envelope"></i> <a href="' . cbgjClass::getPluginURL( array( 'categories', 'message', (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Ph( 'Message [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) . '</a></div>' : null )
						.			( cbgjClass::hasAccess( 'cat_can_publish', $authorized ) ? $state : null )
						.			( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) ? '<div><i class="icon-remove"></i> <a href="javascript: void(0);" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'delete', (int) $row->get( 'id' ) ), CBTxt::P( 'Are you sure you want to delete this [category] and all its associated [groups]?', array( '[category]' => cbgjClass::getOverride( 'category' ), '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) ) . '">' . CBTxt::Ph( 'Delete [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div>' : null )
						.			cbgjClass::getIntegrations( 'gj_onAfterCategoryMenu', array( $row, $user, $plugin ), null, null )
						.			( cbgjClass::hasAccess( 'cat_usr_notifications', $authorized ) ? '<div><i class="icon-info-sign"></i> <a href="' . cbgjClass::getPluginURL( array( 'notifications', 'show', (int) $row->get( 'id' ) ) ) . '">' . CBTxt::Th( 'Notifications' ) . '</a></div>' : null )
						.			( $row->get( 'parent' ) ? '<div><i class="icon-share-alt"></i> <a href="' . $row->getParent()->getUrl() . '">' . CBTxt::Ph( 'Back to [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) . '</a></div>' : null )
						.			( ! $row->get( 'parent' ) ? '<div><i class="icon-share-alt"></i> <a href="' . cbgjClass::getPluginURL( array( 'overview' ) ) . '">' . CBTxt::Ph( 'Back to [overview]', array( '[overview]' => cbgjClass::getOverride( 'overview' ) ) ) . '</a></div>' : null )
						.		'</div>'
						.	'</div>';

		return $return;
	}
}
?>