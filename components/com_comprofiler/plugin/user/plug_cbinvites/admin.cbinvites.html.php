<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_cbinvitesAdmin {

	static public function showPlugin( $menu, $xml, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbinvitesAdmin::setTitle( $plugin->name, 'cbicon-48-cbinvites-plugin' );

		$access_levels		=	$_CB_framework->acl->get_access_children_tree( false );

		if ( $access_levels ) foreach( $access_levels as $access_level ) {
			if ( $access_level['value'] == $plugin->access ) {
				$access		=	CBTxt::T( $access_level['text'] );
			}
		}

		$return				=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
							.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.			'<tbody>'
							.				'<tr class="row">'
							.					'<td width="37.5%" style="text-align:center;">' . $menu->invites . '</td>'
							.					'<td width="37.5%" style="text-align:center;">' . $menu->config . '</td>'
							.					'<td width="25%">'
							.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
							.							'<thead>'
							.								'<tr>'
							.									'<th colspan="2">' . CBTxt::T( 'Plugin' ) . '</th>'
							.								'</tr>'
							.							'</thead>'
							.							'<tbody>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Project' ) . '</td>'
							.									'<td><a href="' . htmlspecialchars( $xml->project ) . '" target="_blank">' . htmlspecialchars( $xml->name ) . '</a></td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Access' ) . '</td>'
							.									'<td>' . $access . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Published' ) . '</td>'
							.									'<td>' . ( $plugin->published ? CBTxt::T( 'Yes' ) : CBTxt::T( 'No' ) ) . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Version' ) . '</td>'
							.									'<td>' . htmlspecialchars( $xml->release ) . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Created' ) . '</td>'
							.									'<td>' . cbFormatDate( $xml->creationDate ) . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Description' ) . '</td>'
							.									'<td>' . htmlspecialchars( $xml->description ) . '</td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Author' ) . '</td>'
							.									'<td><a href="' . htmlspecialchars( $xml->authorUrl ) . '" target="_blank">' . htmlspecialchars( $xml->author ) . '</a></td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'Copyright' ) . '</td>'
							.									'<td><a href="' . htmlspecialchars( $xml->copyrightUrl ) . '" target="_blank">' . htmlspecialchars( $xml->copyright ) . '</a></td>'
							.								'</tr>'
							.								'<tr>'
							.									'<th width="90px">' . CBTxt::T( 'License' ) . '</td>'
							.									'<td><a href="' . htmlspecialchars( $xml->licenseUrl ) . '" target="_blank">' . htmlspecialchars( $xml->license ) . '</a></td>'
							.								'</tr>'
							.							'</tbody>'
							.						'</table>'
							.					'</td>'
							.				'</tr>'
							.			'</tbody>'
							.		'</table>'
							.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
							.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
							.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
							.		cbGetSpoofInputTag( 'plugin' )
							.	'</form>';

		echo $return;
	}

	static public function showInvites( $rows, $pageNav, $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbinvitesAdmin::setTitle( CBTxt::T( 'Invites' ), 'cbicon-48-cbinvites-invites' );

		$toggleJs		=	"cbToggleAll( this, " . count( $rows ) . ", 'id' );";
		$oneOrTwo		=	0;

		$return			=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
						.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
						.			'<thead>'
						.				'<tr>'
						.					'<th colspan="2">&nbsp;</th>'
						.					'<th style="text-align:left;">' . $input['search'] . '</th>'
						.					'<th style="text-align:left;">' . $input['email'] . '</th>'
						.					'<th colspan="3">&nbsp;</th>'
						.					'<th style="text-align:center;">' . $input['state'] . '</th>'
						.					'<th style="text-align:center;">' . $input['id'] . '</th>'
						.				'</tr>'
						.				'<tr>'
						.					'<th class="title" width="5%" style="text-align:center;">#</th>'
						.					'<th class="title" width="5%" style="text-align:center;"><input type="checkbox" name="toggle" value="" onclick="' . $toggleJs . '" /></th>'
						.					'<th class="title" width="20%">' . CBTxt::T( 'From' ) . '</th>'
						.					'<th class="title" width="20%">' . CBTxt::T( 'To' ) . '</th>'
						.					'<th class="title" width="15%" style="text-align:center;">' . CBTxt::T( 'Code' ) . '</th>'
						.					'<th class="title" width="10%" style="text-align:center;">' . CBTxt::T( 'Sent' ) . '</th>'
						.					'<th class="title" width="10%" style="text-align:center;">' . CBTxt::T( 'Accepted' ) . '</th>'
						.					'<th class="title" width="10%" style="text-align:center;">' . CBTxt::T( 'State' ) . '</th>'
						.					'<th class="title" width="5%" style="text-align:center;">' . CBTxt::T( 'ID' ) . '</th>'
						.				'</tr>'
						.			'</thead>'
						.			'<tbody>';

		if ( $rows ) for ( $i = 0, $n = count( $rows ); $i < $n; $i++ ) {
			$row		=	$rows[$i];
			$editJs		=	"cbListItemTask( this, 'editPlugin', 'action', 'invites.edit', 'id', $i );";

			$return		.=				'<tr class="row' . $oneOrTwo . '">'
						.					'<td style="text-align:center;">' . ( $i + $pageNav->limitstart + 1 ) . '</td>'
						.					'<td style="text-align:center;"><input type="checkbox" id="id' . $i . '" name="id[]" value="' . (int) $row->id . '" /></td>'
						.					'<td>' . $row->getOwnerName() . '</td>'
						.					'<td><a href="javascript: void(0);" onclick="' . $editJs . '">' . $row->getTo( false ) . '</a></td>'
						.					'<td style="text-align:center;">' . htmlspecialchars( $row->code ) . '</td>'
						.					'<td style="text-align:center;">' . ( $row->isSent() ? cbFormatDate( $row->sent ) : null ) . '</td>'
						.					'<td style="text-align:center;">' . ( $row->isAccepted() ? cbFormatDate( $row->accepted ) : null ) . '</td>'
						.					'<td style="text-align:center;">' . $row->getStatus() . '</td>'
						.					'<td style="text-align:center;">' . (int) $row->id . '</td>'
						.				'</tr>';

			$oneOrTwo	=	( $oneOrTwo == 1 ? 2 : 1 );
		} else {
			$return		.=				'<tr>'
						.					'<td colspan="11">';

			if ( $pageNav->searching ) {
				$return	.=						CBTxt::T( 'No invite search results found.' );
			} else {
				$return	.=						CBTxt::T( 'There currently are no invites.' );
			}

			$return		.=					'</td>'
						.				'</tr>';
		}

		$return			.=			'</tbody>'
						.			'<tfoot>'
						.				'<tr>'
						.					'<th colspan="11" style="text-align:center;">' . $pageNav->getListFooter() . '</th>'
						.				'</tr>'
						.			'</tfoot>'
						.		'</table>'
						.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0" style="margin-top: 10px;">'
						.			'<thead>'
						.				'<tr>'
						.					'<th colspan="2" style="text-align: left;">' . CBTxt::Th( 'Batch Process' ) . '</th>'
						.				'</tr>'
						.			'</thead>'
						.			'<tbody>'
						.				'<tr>'
						.					'<td width="10%">' . CBTxt::Th( 'Owner' ) . '</td>'
						.					'<td>' . $input['batch_user'] . '</td>'
						.				'</tr>'
						.			'</tbody>'
						.			'<tfoot>'
						.				'<tr>'
						.					'<th colspan="2" style="text-align: left;">'
						.						'<input type="button" onclick="cbDoListTask( this, \'editPlugin\', \'action\', \'blogs.batch\', \'id\' );return false;" value="' . htmlspecialchars( CBTxt::T( 'Process' ) ) . '">'
						.						' <input type="button" onclick="document.getElementById(\'batch_user\').value=\'\';return false;" value="' . htmlspecialchars( CBTxt::T( 'Reset' ) ) . '">'
						.					'</th>'
						.				'</tr>'
						.			'</tfoot>'
						.		'</table>'
						.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
						.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
						.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
						.		'<input type="hidden" id="action" name="action" value="invites" />'
						.		cbGetSpoofInputTag( 'plugin' )
						.	'</form>';

		echo $return;
	}

	static public function showInviteEdit( $row, $input, $user, $plugin ) {
		global $_CB_framework;

		if ( $row->id ) {
			$title	=	CBTxt::P( 'Invite: <small>Edit [[to]]</small>', array( '[to]' => $row->getTo( false ) ) );
		} else {
			$title	=	CBTxt::T( 'Invite: <small>New</small>' );
		}

		HTML_cbinvitesAdmin::setTitle( $title, 'cbicon-48-cbinvites-invites' );

		$return		=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
					.		'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.			'<tbody>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'To' ) . '</th>'
					.					'<td width="50%">' . $input['to'] . '</td>'
					.					'<td>' . CBTxt::T( 'Input invite email to address.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Subject' ) . '</th>'
					.					'<td width="50%">' . $input['subject'] . '</td>'
					.					'<td>' . CBTxt::T( 'Input invite email subject; if left blank a subject will be applied.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Body' ) . '</th>'
					.					'<td width="50%">' . $input['body'] . '</td>'
					.					'<td>' . CBTxt::T( 'Input private message to include with invite email.' ) . '</td>'
					.				'</tr>'
					.				'<tr>'
					.					'<th width="15%">' . CBTxt::T( 'Owner' ) . '</th>'
					.					'<td width="50%">' . $input['user_id'] . '</td>'
					.					'<td>' . CBTxt::T( 'Input owner of invite as single integer user_id.' ) . '</td>'
					.				'</tr>'
					.			'</tbody>'
					.		'</table>'
					.		'<input type="hidden" id="id" name="id" value="' . (int) $row->id . '" />'
					.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
					.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
					.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
					.		'<input type="hidden" id="action" name="action" value="invites.save" />'
					.		cbGetSpoofInputTag( 'plugin' )
					.	'</form>';

		echo $return;
	}

	static public function showConfig( $input, $user, $plugin ) {
		global $_CB_framework;

		HTML_cbinvitesAdmin::setTitle( CBTxt::T( 'Configuration' ), 'cbicon-48-cbinvites-config' );

		$tabs		=	new cbTabs( 1, 2 );

		$return		=	'<form action="' . $_CB_framework->backendUrl( 'index.php' ) . '" method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="adminform">'
					.		$tabs->startPane( 'invitesConfig' )
					.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'invitesGeneral' )
					.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.					'<tbody>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Template' ) . '</th>'
					.							'<td width="50%">' . $input['general_template'] . '</td>'
					.							'<td>' . CBTxt::P( 'Select template to be used for all of CB Invites. If template is incomplete then missing files will be used from the default template. Template files can be located at the following location: [rel_path]/templates/', array( '[rel_path]' => $plugin->relPath ) ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Class' ) . '</th>'
					.							'<td width="50%">' . $input['general_class'] . '</td>'
					.							'<td>' . CBTxt::T( 'Optionally add a class suffix to surrounding DIV encasing all of CB Invites.' ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Tooltips' ) . '</th>'
					.							'<td width="40%">' . $input['general_tooltips'] . '</td>'
					.							'<td>' . CBTxt::T( 'Select method for tooltip display. Applies to frontend only.' ) . '</td>'
					.						'</tr>'
					.					'</tbody>'
					.				'</table>'
					.			$tabs->endTab()
					.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Tab' ) ), 'invitesTab' )
					.				'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.					'<tbody>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Paging' ) . '</th>'
					.							'<td width="50%">' . $input['tab_paging'] . '</td>'
					.							'<td>' . CBTxt::T( 'Enable or disable usage of paging on tab invites.' ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Page Limit' ) . '</th>'
					.							'<td width="50%">' . $input['tab_limit'] . '</td>'
					.							'<td>' . CBTxt::T( 'Input page limit on tab invites. Page limit determines how many invites are displayed per page.' ) . '</td>'
					.						'</tr>'
					.						'<tr>'
					.							'<th width="15%">' . CBTxt::T( 'Search' ) . '</th>'
					.							'<td width="50%">' . $input['tab_search'] . '</td>'
					.							'<td>' . CBTxt::T( 'Enable or disable usage of search on tab invites.' ) . '</td>'
					.						'</tr>'
					.					'</tbody>'
					.				'</table>'
					.			$tabs->endTab()
					.			$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invites' ) ), 'invitesInvites' )
					.				$tabs->startPane( 'invitesInvitesTabs' )
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'General' ) ), 'invitesInvitesGeneral' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Limit' ) . '</th>'
					.									'<td width="50%">' . $input['invite_limit'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input number of invites each individual user is limited to have active at any given time (accepted invites do not count towards this limit). If blank allow unlimited invites. Moderators are exempt from this configuration.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Resend' ) . '</th>'
					.									'<td width="50%">' . $input['invite_resend'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input number of days after invite sent to allow resending (accepted invites do not permit resending). If blank disable resending invites. Moderators are exempt from this configuration.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Multiple' ) . '</th>'
					.									'<td width="50%">' . $input['invite_multiple'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select usage of multiple emails in a single invite using a comma seperated list (e.g. email1@domain.com, email2@domain.com, email3@domain.com). Moderators are exempt from this configuration.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Duplicate' ) . '</th>'
					.									'<td width="50%">' . $input['invite_duplicate'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select usage of duplicate invites to the same address. Moderators are exempt from this configuration.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Connection' ) . '</th>'
					.									'<td width="50%">' . $input['invite_connection'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select connection method from inviter to invitee.' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Captcha' ) . '</th>'
					.									'<td width="50%">' . $input['invite_captcha'] . '</td>'
					.									'<td>' . CBTxt::T( 'Enable or disable usage of captcha on invites. Requires latest CB Captcha to be installed and published. Moderators are exempt from this configuration.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.					$tabs->startTab( null, htmlspecialchars( CBTxt::T( 'Invite' ) ), 'invitesInvitesInvite' )
					.						'<table class="adminlist" width="100%" cellspacing="0" cellpadding="4" border="0">'
					.							'<tbody>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'CC' ) . '</th>'
					.									'<td width="50%">' . $input['invite_cc'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input a substitution supported CC address (e.g. [email]); multiple addresses supported with comma seperated list (e.g. email1@domain.com, email2@domain.com, email3@domain.com).' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'BCC' ) . '</th>'
					.									'<td width="50%">' . $input['invite_bcc'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input a substitution supported BCC address (e.g. [email]); multiple addresses supported with comma seperated list (e.g. email1@domain.com, email2@domain.com, email3@domain.com).' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Prefix' ) . '</th>'
					.									'<td width="50%">' . $input['invite_prefix'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input substitution supported prefix of invite email subject. Additional subsitutions supported: [site], [sitename], [path], [itemid], [register], [profile], [code], and [to].' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Header' ) . '</th>'
					.									'<td width="50%">' . $input['invite_header'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input substitution supported header of invite email body. Additional subsitutions supported: [site], [sitename], [path], [itemid], [register], [profile], [code], and [to].' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Footer' ) . '</th>'
					.									'<td width="50%">' . $input['invite_footer'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input substitution supported footer of invite email body. Additional subsitutions supported: [site], [sitename], [path], [itemid], [register], [profile], [code], and [to].' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Attachments' ) . '</th>'
					.									'<td width="50%">' . $input['invite_attachments'] . '</td>'
					.									'<td>' . CBTxt::T( 'Input a substitution supported Attachment address (e.g. [cb_myfile]); multiple addresses supported with comma seperated list (e.g. /home/username/public_html/images/file1.zip,[path]/file2.zip, http://www.domain.com/file3.zip). Additional substitutions supported: [site], [sitename], [path], [itemid], [register], [profile], [code], and [to].' ) . '</td>'
					.								'</tr>'
					.								'<tr>'
					.									'<th width="15%">' . CBTxt::T( 'Mode' ) . '</th>'
					.									'<td width="50%">' . $input['invite_mode'] . '</td>'
					.									'<td>' . CBTxt::T( 'Select invite email mode.' ) . '</td>'
					.								'</tr>'
					.							'</tbody>'
					.						'</table>'
					.					$tabs->endTab()
					.				$tabs->endPane()
					.			$tabs->endTab()
					.		$tabs->endPane()
					.		'<input type="hidden" id="option" name="option" value="' . htmlspecialchars( $plugin->option ) . '" />'
					.		'<input type="hidden" id="task" name="task" value="editPlugin" />'
					.		'<input type="hidden" id="cid" name="cid" value="' . (int) $plugin->id . '" />'
					.		'<input type="hidden" id="action" name="action" value="config.save" />'
					.		cbGetSpoofInputTag( 'plugin' )
					.	'</form>';

		echo $return;
	}

	static private function setTitle( $title, $class ) {
		global $_PLUGIN_Backend_Title;

		$title					=	CBTxt::T( $title );

		$_PLUGIN_Backend_Title	=	array( $title, $class );
	}
}
?>