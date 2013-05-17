<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $_PLUGINS;

require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );

$_PLUGINS->registerFunction( 'gj_onBeforeGroupTab', 'getAbout', 'cbgjAboutPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterGroupMenu', 'getAboutMenu', 'cbgjAboutPlugin' );
$_PLUGINS->registerFunction( 'gj_onGroupEdit', 'getParam', 'cbgjAboutPlugin' );
$_PLUGINS->registerFunction( 'gj_onPluginFE', 'getPluginFE', 'cbgjAboutPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterCreateGroup', 'setParam', 'cbgjAboutPlugin' );
$_PLUGINS->registerFunction( 'gj_onAfterUpdateGroup', 'setParam', 'cbgjAboutPlugin' );
$_PLUGINS->registerFunction( 'gj_onConfigIntegrations', 'getConfig', 'cbgjAboutPlugin' );
$_PLUGINS->registerFunction( 'gj_onAuthorization', 'getAuthorization', 'cbgjAboutPlugin' );

class cbgjAboutPlugin extends cbPluginHandler {

	public function getPluginFE( $params, $user, $plugin ) {
		if ( $params[1] && $params[2] ) {
			switch ( $params[0] ) {
				case 'about_edit':
					$this->editAbout( $params[1], $params[2], $params[3], $user, $plugin );
					break;
				case 'about_save':
					cbSpoofCheck( 'plugin' );
					$this->saveAbout( $params[1], $params[2], $params[3], $user, $plugin );
					break;
			}
		}
	}

	public function getAbout( $tabs, $group, $category, $user, $plugin ) {
		$params			=	$group->getParams();
		$aboutContent	=	$params->get( 'about_content', null );
		$authorized		=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( ! cbgjClass::hasAccess( 'about_show', $authorized ) ) {
			return null;
		}

		cbgjClass::getTemplate( 'cbgroupjiveabout' );

		if ( class_exists( 'HTML_cbgroupjiveabout' ) ) {
			return HTML_cbgroupjiveabout::showAbout( $aboutContent, $tabs, $group, $category, $user, $plugin );
		} else {
			return $this->showAbout( $aboutContent, $tabs, $group, $category, $user, $plugin );
		}
	}

	private function showAbout( $content, $tabs, $group, $category, $user, $plugin ) {
		if ( $plugin->params->get( 'about_content', 0 ) ) {
			$content	=	cbgjClass::prepareContentPlugins( $content );
		}

		$return			=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'About' ) ), 'gjIntegrationsAbout' )
						.	'<div class="gjAbout">'
						.		'<div class="gjContent">'
						.			( $plugin->params->get( 'about_editor', 3 ) >= 2 ? $content : htmlspecialchars( $content ) )
						.		'</div>'
						.	'</div>'
						.	$tabs->endTab();

		return $return;
	}

	public function getAboutMenu( $group, $category, $user, $plugin ) {
		$authorized	=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'about_edit', $authorized ) ) {
			return '<div><i class="icon-edit"></i> <a href="' . cbgjClass::getPluginURL( array( 'plugin', 'about_edit', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '">' . CBTxt::Th( 'Edit About' ) . '</a></div>';
		}
	}

	private function editAbout( $catid, $grpid, $id, $user, $plugin ) {
		global $_CB_framework;

		$category				=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group					=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category			=	$group->getCategory();
		}

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'about_edit', $authorized ) ) {
			$aboutEditor		=	$plugin->params->get( 'about_editor', 3 );

			$group->setPathway( CBTxt::T( 'Edit About' ), true );

			cbgjClass::getTemplate( 'cbgroupjiveabout_edit' );

			$params				=	$group->getParams();

			$input				=	array();

			if ( $aboutEditor >= 2 ) {
				$about			=	cbgjClass::getHTMLCleanParam( true, 'about_content', $params->get( 'about_content' ) );
			} else {
				$about			=	cbgjClass::getCleanParam( true, 'about_content', $params->get( 'about_content' ) );
			}

			if ( $aboutEditor == 3 ) {
				$input['about']	=	$_CB_framework->displayCmsEditor( 'about_content', $about, 500, 400, 60, 25 );
			} else {
				$input['about']	=	'<textarea id="about_content" name="about_content" class="input-xlarge" cols="60" rows="25">' . htmlspecialchars( $about ) . '</textarea>';
			}

			if ( class_exists( 'HTML_cbgroupjiveaboutEdit' ) ) {
				$return			=	HTML_cbgroupjiveaboutEdit::showAboutEdit( $input, $group, $category, $user, $plugin );
			} else {
				$return			=	'<div class="gjAboutEdit">'
								.		'<form action="' . cbgjClass::getPluginURL( array( 'plugin', 'about_save', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ) ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
								.			'<legend class="gjEditTitle">' . CBTxt::Th( 'Edit About' ) . '</legend>'
								.			'<div class="gjEditContentInput control-group">'
								.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Content' ) . '</label>'
								.				'<div class="gjEditContentInputField controls">'
								.					$input['about']
								.					'<span class="gjEditContentInputIcon help-inline">'
								.						cbgjClass::getIcon( CBTxt::T( 'Input about tab content.' ) )
								.					'</span>'
								.				'</div>'
								.			'</div>'
								.			'<div class="gjButtonWrapper form-actions">'
								.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Update About' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
								.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'show', $category->id, $group->id ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ) ) . '" />'
								.			'</div>'
								.			cbGetSpoofInputTag( 'plugin' )
								.		'</form>'
								.	'</div>';
			}

			echo $return;
		} else {
			if ( $group->get( 'id' ) ) {
				$url			=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url			=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url			=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	private function saveAbout( $catid, $grpid, $id, $user, $plugin ) {
		$category			=	cbgjData::getCategories( array( array( 'cat_access', 'mod_lvl1' ), $user ), array( 'id', '=', (int) $catid ), null, null, false );
		$group				=	cbgjData::getGroups( array( array( 'grp_access', 'mod_lvl2' ), $user ), array( 'id', '=', (int) $grpid ), null, null, false );

		if ( ( ! $category->get( 'id' ) ) && $group->get( 'id' ) ) {
			$category		=	$group->getCategory();
		}

		$authorized			=	cbgjClass::getAuthorization( $category, $group, $user );

		if ( cbgjClass::hasAccess( 'about_edit', $authorized ) ) {
			$params			=	$group->getParams();

			if ( $plugin->params->get( 'about_editor', 3 ) >= 2 ) {
				$content	=	cbgjClass::getWordFiltering( cbgjClass::getHTMLCleanParam( true, 'about_content', $params->get( 'about_content' ) ) );
			} else {
				$content	=	cbgjClass::getWordFiltering( cbgjClass::getCleanParam( true, 'about_content', $params->get( 'about_content' ) ) );
			}

			$params->set( 'about_content', $content );

			if ( ! $group->storeParams( $params ) ) {
				cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::P( 'About failed to save! Error: [error]', array( '[error]' => $group->getError() ) ), false, true, 'error' );
			}

			cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) ), CBTxt::T( 'About saved successfully!' ), false, true );
		} else {
			if ( $group->get( 'id' ) ) {
				$url		=	array( 'groups', 'show', (int) $category->get( 'id' ), (int) $group->get( 'id' ) );
			} elseif ( $category->get( 'id' ) ) {
				$url		=	array( 'categories', 'show', (int) $category->get( 'id' ) );
			} else {
				$url		=	array( 'overview' );
			}

			cbgjClass::getPluginURL( $url, CBTxt::T( 'Not authorized.' ), false, true, 'error' );
		}
	}

	public function getParam( $tabs, $group, $category, $user, $plugin ) {
		global $_CB_framework;

		$authorized				=	cbgjClass::getAuthorization( $category, $group, $user );
		$params					=	$group->getParams();
		$aboutShow				=	$plugin->params->get( 'about_show_config', 1 );
		$aboutPublic			=	$plugin->params->get( 'about_public_config', 1 );

		$input					=	array();

		$input['about_show']	=	moscomprofilerHTML::yesnoSelectList( 'about_show', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $aboutShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'about_show', $params->get( 'about_show', $plugin->params->get( 'about_show', 1 ) ) ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['about_public']	=	moscomprofilerHTML::yesnoSelectList( 'about_public', 'class="input-small"', (int) cbgjClass::getCleanParam( ( $aboutPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'about_public', $params->get( 'about_public', $plugin->params->get( 'about_public', 1 ) ) ) );

		if ( $_CB_framework->getUi() == 2 ) {
			$return				=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'About' ) ), 'gjIntegrationsAbout' )
								.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
								.			'<tbody>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Display' ) . '</div>'
								.					'<td width="40%">' . $input['about_show'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select usage of group about.' ) . '</div>'
								.				'</tr>'
								.				'<tr>'
								.					'<th width="15%">' . CBTxt::Th( 'Public' ) . '</div>'
								.					'<td width="40%">' . $input['about_public'] . '</div>'
								.					'<td>' . CBTxt::Th( 'Select if group about tab is publicly visible.' ) . '</div>'
								.				'</tr>'
								.			'</tbody>'
								.		'</table>'
								.	$tabs->endTab();
		} else {
			if ( ( ! $aboutShow ) && ( ! $aboutPublic ) && ( ! cbgjClass::hasAccess( 'usr_mod', $authorized ) ) ) {
				return;
			}

			cbgjClass::getTemplate( 'cbgroupjiveabout_params' );

			if ( class_exists( 'HTML_cbgroupjiveaboutParams' ) ) {
				$return			=	HTML_cbgroupjiveaboutParams::showAboutParams( $input, $group, $category, $user, $plugin );
			} else {
				$return			=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'About' ) ), 'gjIntegrationsAbout' );

				if ( $aboutShow || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Display' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['about_show']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select usage of [group] about.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				if ( $aboutPublic || cbgjClass::hasAccess( 'usr_mod', $authorized ) ) {
					$return		.=		'<div class="gjEditContentInput control-group">'
								.			'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Public' ) . '</label>'
								.			'<div class="gjEditContentInputField controls">'
								.				$input['about_public']
								.				'<span class="gjEditContentInputIcon help-inline">'
								.					cbgjClass::getIcon( CBTxt::P( 'Select if [group] about tab is publicly visible.', array( '[group]' => cbgjClass::getOverride( 'group' ) ) ) )
								.				'</span>'
								.			'</div>'
								.		'</div>';
				}

				$return			.=	$tabs->endTab();
			}
		}

		return $return;
	}

	public function setParam( $group, $category, $user, $plugin ) {
		$authorized	=	cbgjClass::getAuthorization( $category, $group, $user );
		$params		=	$group->getParams();

		$params->set( 'about_show', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'about_show_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'about_show', $params->get( 'about_show', $plugin->params->get( 'about_show', 1 ) ) ) );
		$params->set( 'about_public', (int) cbgjClass::getCleanParam( ( $plugin->params->get( 'about_public_config', 1 ) || cbgjClass::hasAccess( 'usr_mod', $authorized ) ), 'about_public', $params->get( 'about_public', $plugin->params->get( 'about_public', 1 ) ) ) );

		$group->storeParams( $params );
	}

	public function getConfig( $tabs, $user, $plugin ) {
		$input							=	array();

		$listEditor						=	array();
		$listEditor[]					=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Plain Text' ) );
		$listEditor[]					=	moscomprofilerHTML::makeOption( '2', CBTxt::T( 'HTML Text' ) );
		$listEditor[]					=	moscomprofilerHTML::makeOption( '3', CBTxt::T( 'WYSIWYG' ) );
		$input['about_editor']			=	moscomprofilerHTML::selectList( $listEditor, 'about_editor', null, 'value', 'text', $plugin->params->get( 'about_editor', 3 ), 1, false, false );

		$input['about_content']			=	moscomprofilerHTML::yesnoSelectList( 'about_content', null, $plugin->params->get( 'about_content', 0 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['about_show']			=	moscomprofilerHTML::yesnoSelectList( 'about_show', null, $plugin->params->get( 'about_show', 1 ), CBTxt::T( 'Enable' ), CBTxt::T( 'Disable' ) );
		$input['about_show_config']		=	moscomprofilerHTML::yesnoSelectList( 'about_show_config', null, $plugin->params->get( 'about_show_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );
		$input['about_public']			=	moscomprofilerHTML::yesnoSelectList( 'about_public', null, $plugin->params->get( 'about_public', 1 ) );
		$input['about_public_config']	=	moscomprofilerHTML::yesnoSelectList( 'about_public_config', null, $plugin->params->get( 'about_public_config', 1 ), CBTxt::T( 'Show' ), CBTxt::T( 'Hide' ) );

		$return							=	$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'About' ) ), 'gjIntegrationsAbout' )
										.		$tabs->startPane( 'gjIntegrationsAboutTabs' )
										.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'gjIntegrationsEventsGeneral' )
										.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
										.					'<tbody>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Editor' ) . '</th>'
										.							'<td width="40%">' . $input['about_editor'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Select method for About editing.' ) . '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Content Plugins' ) . '</th>'
										.							'<td width="40%">' . $input['about_content'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Enable or disable processing of content plugins on anout content.' ) . '</td>'
										.						'</tr>'
										.					'</tbody>'
										.				'</table>'
										.			$tabs->endTab()
										.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Defaults' ) ), 'gjIntegrationsAboutDefaults' )
										.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
										.					'<tbody>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Display' ) . '</th>'
										.							'<td width="40%">' . $input['about_show'] . ' ' . $input['about_show_config'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Display". Additionally select the display of the "Display" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
										.						'</tr>'
										.						'<tr>'
										.							'<th width="15%">' . CBTxt::Th( 'Public' ) . '</th>'
										.							'<td width="40%">' . $input['about_public'] . ' ' . $input['about_public_config'] . '</td>'
										.							'<td>' . CBTxt::Th( 'Select default value for group parameter "Public". Additionally select the display of the "Public" group parameter. Moderators are exempt from this configuration.' ) . '</td>'
										.						'</tr>'
										.					'</tbody>'
										.				'</table>'
										.			$tabs->endTab()
										.		$tabs->endPane()
										.	$tabs->endTab();

		return $return;
	}

	public function getAuthorization( &$access, $category, $group, $user, $owner, $row, $plugin ) {
		if ( isset( $group->id ) && cbgjClass::hasAccess( 'grp_approved', $access ) ) {
			$params			=	$group->getParams();
			$aboutShow		=	$params->get( 'about_show', $plugin->params->get( 'about_show', 1 ) );
			$aboutPublic	=	$params->get( 'about_public', $plugin->params->get( 'about_public', 1 ) );
			$aboutContent	=	$params->get( 'about_content', null );

			if ( ( $aboutPublic || cbgjClass::hasAccess( 'mod_lvl5', $access ) ) && $aboutShow && $aboutContent ) {
				$access[]	=	'about_show';
			}

			if ( $aboutShow && cbgjClass::hasAccess( 'mod_lvl3', $access ) ) {
				$access[]	=	'about_edit';
			}
		}
	}
}
?>