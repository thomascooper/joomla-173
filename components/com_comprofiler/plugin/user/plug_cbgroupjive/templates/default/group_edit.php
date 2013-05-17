<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveGroupEdit {

	/**
	 * render frontend group edit
	 *
	 * @param cbgjGroup $row
	 * @param array $input
	 * @param cbgjCategory $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showGroupEdit( $row, $input, $category, $user, $plugin ) {
		$authorized		=	cbgjClass::getAuthorization( $category, $row, $user );
		$pageTitle		=	CBTxt::P( ( $row->get( 'id' ) ? 'Edit [group]' : 'Create [group]' ), array( '[group]' => cbgjClass::getOverride( 'group' ) ) );

		$row->setPathway( $pageTitle, cbgjClass::getPluginURL( ( $row->get( 'id' ) ? array( 'groups', 'edit', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) : array( 'groups', 'new', (int) $category->get( 'id' ), (int) $row->get( 'parent' ) ) ) ) );

		$tabs			=	new cbTabs( 0, 1 );

		$onEdit			=	cbgjClass::getIntegrations( 'gj_onGroupEdit', array( $tabs, $row, $category, $user, $plugin ), null, null );

		$return			=	'<div class="gjGroupEdit">'
						.		'<form action="' . cbgjClass::getPluginURL( array( 'groups', 'save', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
						.			'<legend class="gjEditTitle">' . $pageTitle . '</legend>';

		if ( cbgjClass::hasAccess( 'mod_lvl1', $authorized ) || $row->getParentAccess( array( 'mod_lvl2', $user ) ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['publish']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select publish status of [group]. Unpublished [groups] will not be visible to the public.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || ( ! $category->get( 'id' ) ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . cbgjClass::getOverride( 'category' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['category']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select [group] [category]. This is the [category] a [group] will belong to and decide its navigation path.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[category]' => cbgjClass::getOverride( 'category' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		} elseif ( $category->id ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . cbgjClass::getOverride( 'category' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$category->getName()
						.					'<input type="hidden" id="category" name="category" value="' . (int) $category->get( 'id' ) . '" />'
						.				'</div>'
						.			'</div>';
		}

		if ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || ( $plugin->params->get( 'group_nested', 1 ) && ( ! $row->get( 'parent' ) ) ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Parent' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['parent']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select parent [group]. Selecting parent [group] allows for nested [group] display.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		} elseif ( $row->get( 'parent' ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Parent' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$row->getParent()->getName()
						.					'<input type="hidden" id="parent" name="parent" value="' . (int) $row->get( 'parent' ) . '" />'
						.				'</div>'
						.			'</div>';
		}

		$return			.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Name' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['name']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Input [group] name. This is the name that will distinguish this [group] from others. Suggested to input something unique and intuitive.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>'
						.			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Description' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['description']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Input [group] description. Your [group] description should be short and to the point; describing what your [group] is all about.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';

		if ( $input['inputlimit'] !== false ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Input Limit' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['inputlimit']
						.				'</div>'
						.			'</div>';
		}

		$return			.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Logo' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					'<div style="margin-bottom: 10px;">' . $row->getLogo( true ) . '</div>'
						.					( $row->get( 'logo' ) ? '<div style="margin-bottom: 5px;">' . $input['del_logo'] . '</div>' : null )
						.					'<div>'
						.						$input['file']
						.						'<span class="gjEditContentInputIcon help-inline">'
						.							cbgjClass::getIcon( CBTxt::P( 'Select [group] logo. A logo should represent the topic of your [group]; please be respectful and tasteful when selecting a logo.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
						.						'</span>'
						.					'</div>'
						.				'</div>'
						.			'</div>';

		if ( $plugin->params->get( 'group_type_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Type' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['type']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select [group] type. Type determines the way your [group] is joined (e.g. Invite requires new [users] to be invited to join your [group]).', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( $plugin->params->get( 'group_access_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Access' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['access']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select [group] access. Access determines who can effectively see your [group]. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author).', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( $plugin->params->get( 'group_invite_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Invite Access' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['invite']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select [group] invite access. Invite access determines what type of [users] can invite others to join your [group] (e.g. [users] signify only those a member of your [group] can invite). The [users] above the selected will also have access.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( $plugin->params->get( 'group_users_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Ph( '[users] Public', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['users']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select if [group] [users] tab is publicly visible.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( cbgjClass::hasAccess( 'grp_nested', $authorized ) ) {
			if ( $plugin->params->get( 'group_nested_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
				$return	.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Nested' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['nested']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Enable or disable the creation of [groups] in this [group]. Moderators and [owner] are exempt from this configuration and can always create [groups].', array( '[group]' => cbgjClass::getOverride( 'group' ), '[groups]' => cbgjClass::getOverride( 'group', true ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
			}

			if ( $plugin->params->get( 'group_nestedaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
				$return	.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Nested Access' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['nested_access']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select [groups] create access. Create access determines who can create [groups] in this [group]. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators and [owner] are exempt from this configuration and can always create [groups].', array( '[group]' => cbgjClass::getOverride( 'group' ), '[groups]' => cbgjClass::getOverride( 'group', true ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
			}
		}

		if ( cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . cbgjClass::getOverride( 'owner' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['owner']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Input [group] [owner]. [group] [owner] determines the creator of the [group] specified as User ID.', array( '[group]' => cbgjClass::getOverride( 'group' ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( $onEdit ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Integrations' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$tabs->startPane( 'gjIntegrationsTabs' )
						.						$onEdit
						.					$tabs->endPane()
						.				'</div>'
						.			'</div>';
		}

		if ( $input['captcha'] !== false ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Captcha' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					'<div style="margin-bottom: 5px;">' . $input['captcha']['code'] . '</div>'
						.					'<div>' . $input['captcha']['input'] . '</div>'
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		$return			.=			'<div class="gjButtonWrapper form-actions">'
						.				'<input type="submit" value="' . htmlspecialchars( ( $row->get( 'id' ) ? CBTxt::P( 'Update [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) : CBTxt::P( 'Create [group]', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
						.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( ( $row->get( 'id' ) ? array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) : ( $row->get( 'parent' ) ? array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'parent' ) ) : array( 'categories', 'show', (int) $category->get( 'id' ) ) ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ), true, false, null, false, false, true ) . '" />'
						.			'</div>'
						.			cbGetSpoofInputTag( 'plugin' )
						.		'</form>'
						.	'</div>';

		echo $return;
	}
}
?>