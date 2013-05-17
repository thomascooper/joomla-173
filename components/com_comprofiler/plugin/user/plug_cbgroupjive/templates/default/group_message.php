<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveGroupMessage {

	/**
	 * render frontend group message
	 *
	 * @param cbgjGroup $row
	 * @param array $input
	 * @param cbgjCategory $category
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showGroupMessage( $row, $input, $category, $user, $plugin ) {
		$row->setPathway( CBTxt::P( 'Message [users]', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ), cbgjClass::getPluginURL( array( 'groups', 'message', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) ) );

		$return			=	'<div class="gjGroupMessage">'
						.		'<form action="' . cbgjClass::getPluginURL( array( 'groups', 'send', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ) ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
						.			'<legend class="gjEditTitle">' . CBTxt::Ph( 'Message [users]', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ) . '</legend>'
						.			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Subject' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['subject']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Input [users] message subject.', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>'
						.			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Body' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['body']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Input [users] message body.', array( '[users]' => cbgjClass::getOverride( 'user', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>';

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
						.				'<input type="submit" value="' . htmlspecialchars( CBTxt::T( 'Send Message' ) ) . '" class="gjButton gjButtonSubmit btn btn-primary" />&nbsp;'
						.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( array( 'groups', 'show', (int) $category->get( 'id' ), (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ) ) . '" />'
						.			'</div>'
						.			cbGetSpoofInputTag( 'plugin' )
						.		'</form>'
						.	'</div>';

		echo $return;
	}
}
?>