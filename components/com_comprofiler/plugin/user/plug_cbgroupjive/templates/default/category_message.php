<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class HTML_groupjiveCategoryMessage {

	/**
	 * render frontend category message
	 *
	 * @param cbgjCategory $row
	 * @param array $input
	 * @param moscomprofilerUser $user
	 * @param object $plugin
	 */
	static function showCategoryMessage( $row, $input, $user, $plugin ) {
		$row->setPathway( CBTxt::P( 'Message [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ), cbgjClass::getPluginURL( array( 'categories', 'message', (int) $row->get( 'id' ) ) ) );

		$return			=	'<div class="gjCategoryMessage">'
						.		'<form action="' . cbgjClass::getPluginURL( array( 'categories', 'send', (int) $row->get( 'id' ) ) ) . '" method="post" enctype="multipart/form-data" name="gjForm" id="gjForm" class="gjForm form-horizontal">'
						.			'<legend class="gjEditTitle">' . CBTxt::Ph( 'Message [groups]', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) . '</legend>'
						.			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Subject' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['subject']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Input [groups] message subject.', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) )
						.					'</span>'
						.				'</div>'
						.			'</div>'
						.			'<div class="gjEditContentInput control-group">'
						.				'<label class="gjEditContentInputTitle control-label">' . CBTxt::Th( 'Body' ) . '</label>'
						.				'<div class="gjEditContentInputField controls">'
						.					$input['body']
						.					'<span class="gjEditContentInputIcon help-inline">'
						.						cbgjClass::getIcon( null, CBTxt::T( 'Required' ), 'icon-star' )
						.						cbgjClass::getIcon( CBTxt::P( 'Input [groups] message body.', array( '[groups]' => cbgjClass::getOverride( 'group', true ) ) ) )
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
						.				'<input type="button" value="' . htmlspecialchars( CBTxt::T( 'Cancel' ) ) . '" class="gjButton gjButtonCancel btn btn-mini" onclick="' . cbgjClass::getPluginURL( array( 'categories', 'show', (int) $row->get( 'id' ) ), CBTxt::T( 'Are you sure you want to cancel? All unsaved data will be lost!' ) ) . '" />'
						.			'</div>'
						.			cbGetSpoofInputTag( 'plugin' )
						.		'</form>'
						.	'</div>';

		echo $return;
	}
}
?>