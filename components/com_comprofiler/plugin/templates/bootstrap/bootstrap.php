<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

class CBProfileView_html_bootstrap extends cbProfileView {
	var $wLeft;
	var $wMiddle;
	var $wRight;
	var $nCols;

	public function draw( $part = null ) {
		global $ueConfig;

		if ( ! $part ) {
			$this->wLeft			=	( isset( $this->userViewTabs['cb_left'] ) ? 100 : 0 );
			$this->wMiddle			=	( isset( $this->userViewTabs['cb_middle'] ) ? 100 : 0 );
			$this->wRight			=	( isset( $this->userViewTabs['cb_right'] ) ? 100 : 0 );
			$this->nCols			=	intval( ( $this->wLeft + $this->wMiddle + $this->wRight ) / 100 );

			switch ( $this->nCols ) {
				case 2 :
					$this->wLeft	=	( $this->wLeft ? intval( $ueConfig['left2colsWidth'] ) - 1 : 0 );
					$this->wMiddle	=	( $this->wMiddle ? ( $this->wLeft ? 100 - intval( $ueConfig['left2colsWidth'] ) - 1 : intval( $ueConfig['left2colsWidth'] ) - 1 ) : 0 );
					$this->wRight	=	( $this->wRight ? 100 - intval( $ueConfig['left2colsWidth'] ) - 1 : 0 );
					break;
				case 3 :
					$this->wLeft	=	( intval( $ueConfig['left3colsWidth'] ) - 1 );
					$this->wMiddle	=	( 100 - intval( $ueConfig['left3colsWidth'] ) - intval( $ueConfig['right3colsWidth'] ) - 1 );
					$this->wRight	=	( intval( $ueConfig['right3colsWidth'] ) - 1 );
					break;
			}
		}

		return parent::draw( $part );
	}

	public function _render() {
		$return							=	null;

		if ( isset( $this->userViewTabs['cb_head'] ) ) {
			$return						.=	'<div class="cbPosHead">'
										.		$this->userViewTabs['cb_head']
										.	'</div>'
										.	'<div class="cbClr"></div>';
		}

 		if ( $this->nCols != 0 ) {
			$return						.=	'<div class="cbPosTop cbColumns">';

			 if ( $this->nCols > 2 ) {
				$topClass				=	'cbColumn4';
			 } elseif ( $this->nCols > 1 ) {
				$topClass				=	'cbColumn6';
			 } else {
				$topClass				=	'cbColumn12';
			 }

			if ( isset( $this->userViewTabs['cb_left'] ) ) {
				$return					.=		'<div class="cbPosLeft ' . $topClass . '">'
										.				$this->userViewTabs['cb_left']
										.		'</div>';
			}

			if ( isset( $this->userViewTabs['cb_middle'] ) ) {
				$return					.=		'<div class="cbPosMiddle ' . $topClass . '">'
										.				$this->userViewTabs['cb_middle']
										.		'</div>';
			}

			if ( isset( $this->userViewTabs['cb_right'] ) ) {
				$return					.=		'<div class="cbPosRight ' . $topClass . '">'
										.			$this->userViewTabs['cb_right']
										.		'</div>';
			}

			$return						.=	'</div>'
										.	'<div class="cbClr"></div>';
		}

		if ( isset( $this->userViewTabs['cb_tabmain'] ) ) {
			$return						.=	'<div class="cbPosTabMain">'
										.		$this->userViewTabs['cb_tabmain']
										.	'</div>'
										.	'<div class="cbClr"></div>';
		}

		if ( isset( $this->userViewTabs['cb_underall'] ) ) {
			$return						.=	'<div class="cbPosUnderAll">'
										.		$this->userViewTabs['cb_underall']
										.	'</div>'
										.	'<div class="cbClr"></div>';
		}

		$line							=	null;
		$indexes						=	array_keys( $this->userViewTabs );

		if ( $indexes ) foreach ( $indexes as $k => $v ) {
			if ( $v && $v[0] == 'L' ) {
				$L						=	$v[1];

				if ( $line === null ) {
					$line				=	$k;
				}

				if ( ! ( isset( $indexes[$k + 1] ) && ( $indexes[$k + 1][1] == $L ) ) ) {
					$cols				=	( $k - $line + 1 );

					switch( $cols ) {
						case 9:
							$colClass	=	'cbColumnCustom';
							break;
						case 8:
							$colClass	=	'cbColumnCustom';
							break;
						case 7:
							$colClass	=	'cbColumnCustom';
							break;
						case 6:
							$colClass	=	'cbColumn2';
							break;
						case 5:
							$colClass	=	'cbColumnCustom';
							break;
						case 4:
							$colClass	=	'cbColumn3';
							break;
						case 3:
							$colClass	=	'cbColumn4';
							break;
						case 2:
							$colClass	=	'cbColumn6';
							break;
						case 1:
						default:
							$colClass	=	'cbColumn12';
							break;
					}

					$width				=	100;
					$step				=	floor( $width / $cols );

					$return				.=	'<div class="cbPosGridLine cbColumns" id="cbPosLine' . substr( $v, 0, 2 ) . '">';

					for ( $i = $line ; $i <= $k ; $i++ ) {
						if ( $i == $k ) {
							$step		=	( $width - ( ( $cols - 1 ) * $step ) );
						}

						$return			.=		'<div class="cbPosGrid ' . $colClass . '" id="cbPos' . $v . '_' . $i . '"' . ( $colClass == 'cbColumnCustom' ? ' style="width: ' . (int) $step . '%;"' : null ) . '>'
										.			'<div class="cbPosGridE">'
										.				$this->userViewTabs[$indexes[$i]]
										.			'</div>'
										.		'</div>';
					}

					$return				.=	'</div>'
										.	'<div class="cbClr" id="cbPosSep' . substr( $v, 0, 2 ) . '"></div>';

					$line				=	null;
				}
			}
		}

		echo $return;
	}

	public function _renderEdit() {
		global $_CB_framework;

		$_CB_framework->outputCbJQuery( "$( '.cbEditProfile' ).parent().addClass( 'cb_template_bootstrap' )" );

		$return	=	$this->tabcontent
				.	'<span class="cb_button_wrapper">'
				.		'<input class="button cbProfileEditSubmit" type="submit" id="cbbtneditsubmit" value="' . $this->submitValue . '" />'
				.	'</span>'
				.	'<span class="cb_button_wrapper">'
				.		'<input class="button cbProfileEditCancel" type="button" id="cbbtncancel" name="btncancel" value="' . $this->cancelValue . '" />'
				.	'</span>'
				.	'<div id="cbIconsBottom">'
				.		$this->bottomIcons
				.	'</div>';

		echo $return;
	}
}

class CBRegisterFormView_html_bootstrap extends cbRegistrationView {

	public function _renderRegistrationHead() {
		$return			=	null;

		if ( $this->moduleContent ) {
			if ( $this->introMessage ) {
				$return	.=	'<div class="componentheading" id="cb_comp_login_register_head">'
						.		$this->loginOrRegisterTitle
						.	'</div>'
						.	'<div class="cb_comp_outer">'
						.		'<div class="cb_comp_inner">'
						.			'<div class="contentpaneopen" id="cb_comp_login_register_content">'
						.				$this->introMessage
						.			'</div>'
						.		'</div>'
						.	'</div>';
			}

			$return		.=	'<div class="cbclearboth">'
						.		'<div id="cb_comp_login">'
						.			'<div class="componentheading">' . _LOGIN_TITLE . '</div>'
						.			'<div class="cb_comp_outer">'
						.				'<div class="cb_comp_inner">'
						.					$this->moduleContent
						.				'</div>'
						.			'</div>'
						.		'</div>'
						.		'<div id="cb_comp_register">';
		}

		$return			.=			'<div class="componentheading">' . $this->registerTitle . '</div>'
						.				'<div class="contentpaneopen">'
						.					'<div class="cb_comp_outer">'
						.						'<div class="cb_comp_inner cbHtmlEdit cbRegistration">';

		if ( $this->topIcons ) {
			$return		.=							'<div id="cbIconsTop">'
						.								$this->topIcons
						.							'</div>';
		}

		$return			.=							$this->regFormTag;

		echo $return;
	}

	public function _renderdivs() {
		$this->_renderRegistrationHead();

		$return		=	'<div class="contentpane" id="registrationTable">';

		if ( $this->introMessage && ( ! $this->moduleContent ) ) {
			$return	.=		'<div class="contentpaneopen">' . $this->introMessage . '</div>';
		}

		$return		.=		$this->tabcontent
					.		'<div class="contentpaneopen">';

		if ( $this->conclusionMessage ) {
			$return	.=			$this->conclusionMessage;
		} else {
			$return	.=			'&nbsp;';
		}

		$return		.=		'</div>'
					.		'<div class="contentpaneopen">'
					.			'<span class="cb_button_wrapper">'
					.				'<input type="submit" value="' . $this->registerButton . '" class="button" />'
					.			'</span>'
					.		'</div>'
					.	'</div>';

		echo $return;

		$this->_renderRegistrationFooter();
	}

	public function _render() {
		$this->_renderRegistrationHead();

		$return		=	'<table class="contentpane" id="registrationTable">';

		if ( $this->introMessage && ( ! $this->moduleContent ) ) {
			$return	.=		'<tr>'
					.			'<td colspan="2" class="contentpaneopen">' . $this->introMessage . '</td>'
					.		'</tr>';
		}

		$return		.=		$this->tabcontent
					.		'<tr>'
					.			'<td colspan="2" class="contentpaneopen">';

		if ( $this->conclusionMessage ) {
			$return	.=				$this->conclusionMessage;
		} else {
			$return	.=				'&nbsp;';
		}

		$return		.=			'</td>'
					.		'</tr>'
					.		'<tr>'
					.			'<td colspan="2">'
					.				'<span class="cb_button_wrapper">'
					.					'<input type="submit" value="' . $this->registerButton . '" class="button" />'
					.				'</span>'
					.			'</td>'
					.		'</tr>'
					.	'</table>';

		echo $return;

		$this->_renderRegistrationFooter();
	}

	public function _renderRegistrationFooter() {
		$return		=						'</form>';

		if ( $this->bottomIcons ) {
			$return	.=						'<div id="cbIconsBottom">'
					.							$this->bottomIcons
					.						'</div>';
		}

		$return		.=					'</div>'
					.		'		</div>'
					.			'</div>';

		if ( $this->moduleContent ) {
			$return	.=		'</div>'
					.	'</div>';
		}

		$return		.=	'<div class="cbClr"></div>';

		echo $return;
	}
}

class CBListView_html_bootstrap extends cbListView {

	public function _renderHead() {
		global $_CB_framework;

		$return				=	'<div class="cbUserListHeadTitle">';

		if ( ( count( $this->lists ) > 0 ) || $this->searchTabContent ) {
			$return			.=		'<div class="cbUserListChanger">';

			if ( count( $this->lists ) > 0 ) foreach ( $this->lists as $kname => $ncontent ) {
				$return		.=			'<div class="cbUserListChangeItem cbUserList' . $kname . '">' . $ncontent . '</div>';
			}

			if ( $this->searchTabContent ) {
				if ( ! $this->searchResultDisplaying ) {
					$return	.=			'<div class="cbUserListSearchButtons" id="cbUserListsSearchTrigger">'
							.				'<a class="pagenav" href="#">' . _UE_SEARCH_USERS . '</a>'
							.			'</div>';
				} else {
					$return	.=			'<div id="cbUserListListAll">'
							.				'<a class="pagenav" href="' . cbSef( $this->ue_base_url ) . '">' . _UE_LIST_ALL . '</a>'
							.			'</div>';
				}
			}

			$return			.=		'</div>';
		}

		$return				.=		'<div class="contentheading cbUserListTitle">' . $this->listTitleHtml . '</div>';

		if ( trim( $this->listDescription ) ) {
			$return			.=		'<div class="contentdescription cbUserListDescription">' . $this->listDescription . '</div>';
		}

		$return				.=		'<div class="contentdescription cbUserListResultCount">';

		if ( $this->totalIsAllUsers ) {
			$return			.=			$_CB_framework->getCfg( 'sitename' ) . ' ' . _UE_HAS . ' <strong>' . $this->total . '</strong> ' . _UE_USERS;
		} else {
			$return			.=			'<strong>' . $this->total . '</strong> ' . _UE_USERPENDAPPRACTION . ':';
		}

		$return				.=		'</div>'
							.		'<div class="cbClr"></div>';

		if ( $this->searchTabContent ) {
			$return			.=		'<div class="contentdescription cbUserListSearch" id="cbUserListsSearcher">'
							.			'<div class="componentheading">' . $this->searchCriteriaTitleHtml . '</div>'
							.			'<div class="cbUserListSearchFields">'
							.				$this->searchTabContent
							.				'<div class="cbClr"></div>'
							.				'<div class="cb_form_buttons_line">'
							.					'<input type="submit" class="button" id="cbsearchlist" value="' . _UE_FIND_USERS . '" />'
							.				'</div>'
							.				'<div class="cbClr"></div>'
							.			'</div>';

			if ( $this->searchResultsTitleHtml ) {
				$return		.=			'<div class="componentheading">' . $this->searchResultsTitleHtml . '</div>';
			}

			$return			.=		'</div>';
		}

		$return				.=	'</div>';

		echo $return;
	}

	public function _renderBody() {
		$return				=	'<hr class="cbUserListHrTop" size="1" />'
							.	'<table id="cbUserTable" class="cbUserListTable cbUserListT_' . $this->listid . '">'
							.		'<thead>';

		$columnCount		=	count( $this->columns );

		if ( $columnCount ) {
			$return			.=			'<tr class="sectiontableheader">';

			foreach ( $this->columns as $column ) {
				$return		.=				'<th><b>' . $column->titleRendered . '</b></th>';
			}

			$return			.=			'</tr>';
		}

		$return				.=		'</thead>'
							.		'<tbody>';

		$i					=	0;

		if ( is_array( $this->users ) && count( $this->users ) > 0 ) foreach ( $this->users as $userIndex => $user ) {
			$class			=	'sectiontableentry' . ( 1 + ( $i % 2 ) );

			if ( $this->allow_profilelink ) {
				$style		=	' style="cursor: hand; cursor: pointer;" id="cbU' . $i . '"';
			} else {
				$style		=	null;
			}

			if ( $user->banned ) {
				$return		.=			'<tr class="' . $class . '">'
							.				'<td colspan="' . $columnCount . '">'
							.					'<span class="error" style="color:red;">' . _UE_BANNEDUSER . ' (' . _UE_VISIBLE_ONLY_MODERATOR . ') :</span>'
							.				'</td>'
							.			'</tr>';
			}

			if ( $columnCount ) {
				$return		.=			'<tr class="' . $class . '"' . $style . '>';

				foreach ( array_keys( $this->columns ) as $columnIndex ) {
					$return	.=				'<td valign="top" class="cbUserListCol' . $columnIndex . '">' . $this->_getUserListCell( $this->tableContent[$userIndex][$columnIndex] ) . '</td>';
				}

				$return		.=			'</tr>';
			}

			$i++;
		} else {
			$return			.=			'<tr class="sectiontableentry1">'
							.				'<td colspan="' . $columnCount . '">' . _UE_NO_USERS_IN_LIST . '</td>'
							.			'</tr>';
		}

		$return				.=		'<tbody>'
							.	'</table>'
							.	'<hr class="cbUserListHrBottom" size="1" />';

		echo $return;
	}

	public function _getUserListCell( &$cellFields ) {
		$return				=	null;

		foreach ( $cellFields as $fieldView ) {
			if ( $fieldView->value !== null ) {
				$return		.=	'<div class="cbUserListFieldLine">';

				if  ( $fieldView->title ) {
					$return	.=		'<span class="cbUserListFieldTitle cbUserListFT_' . $fieldView->name . '">' . $fieldView->title . ':</span> ';
				}

				$return		.=		'<span class="cbListFieldCont cbUserListFC_' . $fieldView->name . '">' . $fieldView->value . '</span>'
							.	'</div>';
			}
		}

		return $return;
	}
}
?>