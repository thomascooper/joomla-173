<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveCategoryEdit {

	/**
	 * render frontend category edit
	 *
	 * @param cbgjCategory $row
	 * @param array $input
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showCategoryEdit( $row, $input, $user, $plugin ) {
		$authorized		=	cbgjClass::getAuthorization( $row, null, $user );
		$pageTitle		=	CBTxt::P( ( $row->get( 'id' ) ? 'Edit [category]' : 'Create [category]' ), array( '[category]' => cbgjClass::getOverride( 'category' ) ) );

		$row->setPathway( $pageTitle, cbgjClass::getPluginURL( ( $row->get( 'id' ) ? array( 'categories', 'edit', (int) $row->get( 'id' ) ) : array( 'categories', 'new', (int) $row->get( 'parent' ) ) ) ) );

		$tabs			=	new cbTabs( 0, 1 );

		$onEdit			=	cbgjClass::getIntegrations( 'gj_onCategoryEdit', array( $tabs, $row, $user, $plugin ), null, null );

		$return			=	'<div class="gjCategoryEdit">'
						.		'<form action="' . cbgjClass::getPluginURL( array( 'categories', 'save', (int) $row->get( 'id' ) ), null, true, false, null, 'current' ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
						.			'<legend class="gjEditTitle">' . $pageTitle . '</legend>';

		if ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || $row->getParentAccess( array( 'mod_lvl1', $user ) ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Published' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['publish']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select publish status of [category]. Unpublished [categories] will not be visible to the public as well as its [groups].', array( '[category]' => cbgjClass::getOverride( 'category' ), '[categories]' => cbgjClass::getOverride( 'category', true ), '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( cbgjClass::hasAccess( 'usr_mod', $authorized ) || ( $plugin->params->get( 'category_nested', 1 ) && ( ! $row->get( 'parent' ) ) ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Parent' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['parent']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select parent [category]. Selecting parent [category] allows for nested [category] display.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) )
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
						.						cbgjClass::getIcon( CBTxt::P( 'Input [category] name. This is the name that will distinguish this [category] from others. Suggested to input something unique and intuitive.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>'
						.			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Description' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['description']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Input [category] description. Your [category] description should be short and to the point; describing what your [category] is all about.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) )
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
						.							cbgjClass::getIcon( CBTxt::P( 'Select [category] logo. A logo should represent the focus of your [category]; please be respectful and tasteful when selecting a logo.', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) )
						.						'</span>'
						.					'</div>'
						.				'</div>'
						.			'</div>';

		if ( $plugin->params->get( 'category_types_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Types' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['types']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Select [categories] available [group] types. Types determine the way a [group] is joined (e.g. Invite requires new [users] to be invited to join a [group]).', array( '[categories]' => cbgjClass::getOverride( 'category', true ), '[group]' => cbgjClass::getOverride( 'group' ), '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( $plugin->params->get( 'category_access_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
			$return		.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Access' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['access']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select [category] access. Access determines who can effectively see your [category]. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author).', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
		}

		if ( cbgjClass::hasAccess( 'grp_create', $authorized ) ) {
			if ( $plugin->params->get( 'category_create_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
				$return	.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Create' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['create']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Enable or disable the creation of [groups] in this [category]. Moderators and [owner] are exempt from this configuration and can always create [groups].', array( '[category]' => cbgjClass::getOverride( 'category' ), '[owner]' => cbgjClass::getOverride( 'owner' ), '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
			}

			if ( $plugin->params->get( 'category_createaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
				$return	.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Create Access' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['create_access']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select [group] create access. Create access determines who can create [groups] in this [category]. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators and [owner] are exempt from this configuration and can always create [groups].', array( '[category]' => cbgjClass::getOverride( 'category' ), '[owner]' => cbgjClass::getOverride( 'owner' ), '[group]' => cbgjClass::getOverride( 'group' ), '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
			}
		}

		if ( cbgjClass::hasAccess( 'cat_nested', $authorized ) ) {
			if ( $plugin->params->get( 'category_nested_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
				$return	.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Nested' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['nested']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Enable or disable the creation of [categories] in this [category]. Moderators and [owner] are exempt from this configuration and can always create [categories].', array( '[category]' => cbgjClass::getOverride( 'category' ), '[categories]' => cbgjClass::getOverride( 'category', true ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';
			}

			if ( $plugin->params->get( 'category_nestedaccess_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
				$return	.=			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Nested Access' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['nested_access']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( CBTxt::P( 'Select [categories] create access. Create access determines who can create [categories] in this [category]. The group selected as well as those above it will have access (e.g. Registered will also be accessible to Author). Moderators and [owner] are exempt from this configuration and can always create [categories].', array( '[category]' => cbgjClass::getOverride( 'category' ), '[categories]' => cbgjClass::getOverride( 'category', true ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
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
						.						cbgjClass::getIcon( CBTxt::P( 'Input [category] [owner]. [category] [owner] determines the creator of the [category] specified as User ID.', array( '[category]' => cbgjClass::getOverride( 'category' ), '[owner]' => cbgjClass::getOverride( 'owner' ) ) ) )
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
						.				'<input type="submit" value="' . htmlspecialchars( ( $row->get( 'id' ) ? CBTxt::P( 'Update [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) : CBTxt::P( 'Create [category]', array( '[category]' => cbgjClass::getOverride( 'category' ) ) ) ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
						.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( ( $row->get( 'id' ) ? array( 'categories', 'show', (int) $row->get( 'id' ) ) : ( $row->get( 'parent' ) ? array( 'categories', 'show', (int) $row->get( 'parent' ) ) : array( 'overview' ) ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ), true, false, null, false, false, true ) . '" />'
						.			'</div>'
						.			cbGetSpoofInputTag( 'plugin' )
						.		'</form>'
						.	'</div>';

		echo $return;
	}
}
?>