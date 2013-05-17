<?php
/**
* Joomla Community Builder User Plugin: plug_ratingfield
* @version $Id: ratingfield.php 2564 2012-08-27 14:07:43Z kyle $
* @package plug_ratingfield
* @subpackage ratingfield.php
* @author CBJoe
* @copyright (C) CBJoe and Beat, www.joomlapolis.com
* @license Limited http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
* @final 1.0
*/

/** ensure this file is being included by a parent file */
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerUserFieldTypes( array( 'myrating' => 'CBfield_myrating' ) );;
$_PLUGINS->registerUserFieldTypes( array( 'yourrating' => 'CBfield_yourrating' ) );;
$_PLUGINS->registerUserFieldParams();

class cbRating_base extends cbFieldHandler {
	/**
	 * Constructor
	 *
	 * @return CBfield_rating_base
	 */
	function cbRating_base( ) {
		parent::cbFieldHandler();
		cbimport( 'language.cbteamplugins' );
	}
	/**
	 * Direct access to field for custom operations, like for Ajax
	 *
	 * WARNING: direct unchecked access, except if $user is set, then check
	 * that the logged-in user has rights to edit that $user.
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  array                 $postdata
	 * @param  string                $reason     'profile' (as nothing can really be trusted in here.
	 * @return string                            Expected output.
	 */
	function fieldClass( &$field, &$user, &$postdata, $reason ) {
		global $_GET;
		
		parent::fieldClass( $field, $user, $postdata, $reason );		// performs spoofcheck.

		$function			=	cbGetParam( $_GET, 'function', '' );
		$nonAjax			=	cbGetParam( $_GET, 'nonajax', 0 );
		if ( $function == 'savevalue' ) {
			
			$this->prepareFieldDataSave($field,$user,$postdata,$reason);
			$user->store();
			$thankYouText	=	$field->params->get( 'RatingTxtThankYou', 'Thank you for rating!' );
			if ( $nonAjax ) {
				$url		=	'index.php?option=com_comprofiler&amp;task=userProfile';
				// cbRedirect( cbSef( , false ), $thankYouText );
			} else {
				return CBTxt::Tutf8( $thankYouText );
			}
		}
		return null;
	}
	/**
	 * Validator:
	 * Validates $value for $field->required and other rules
	 * Override
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user        RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  string                $columnName  Column to validate
	 * @param  string                $value       (RETURNED:) Value to validate, Returned Modified if needed !
	 * @param  array                 $postdata    Typically $_POST (but not necessarily), filtering required.
	 * @param  string                $reason      'edit' for save profile edit, 'register' for registration, 'search' for searches
	 * @return boolean                            True if validate, $this->_setErrorMSG if False
	 */
	function validate( &$field, &$user, $columnName, &$value, &$postdata, $reason ) {
		$validated					=	parent::validate( $field, $user, $columnName, $value, $postdata, $reason );
		if ( $validated && ( $value !== '' ) && ( $value !== null ) ) {		// empty values (e.g. non-mandatory) are treated in the parent validation.
			$validated				=	preg_match( '/^[-0-9]*\\.?[0-9]*$/', $value );
			if ( $validated ) {
				// check range:
				$min				=	0;
				$max				=	(int) $this->numStars;
				if ( $max < $min ) {
					$this->_setValidationError( $field, $user, $reason, "Number of stars setting is negative !" );		// Missing language string.
					$validated		=	false;
				}
				if ( ( ( (float) $value ) < $min ) || ( ( (float) $value ) > $max ) ) {
					$this->_setValidationError( $field, $user, $reason, sprintf( _UE_YEAR_NOT_IN_RANGE, (float) $value, (int) $min, (int) $max ) );		// using that year string, as we don't have a general one.
					$validated		=	false;
				}
			} else {
				$this->_setValidationError( $field, $user, $reason, "Input not authorized" );		// Missing language string: but as it's a bad error, no need
			}
		}
		return $validated;
	}
	/**
	 * Finder:
	 * Prepares field data for saving to database (safe transfer from $postdata to $user)
	 * Override
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $searchVals  RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array                 $postdata    Typically $_POST (but not necessarily), filtering required.
	 * @param  int                   $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @param  string                $reason      'profile' for save user edit, 'register' for save registration
	 * @return boolean                            True: All fields have validated, False: Some fields didn't validate
	 */
	function bindSearchCriteria( &$field, &$searchVals, &$postdata, $list_compare_types, $reason ) {

		$this->_loadSettings( $field );

		$query						=	array();
		$col 						= 	$field->name;
		$minNam						=	$col . '__minval';
		$maxNam						=	$col . '__maxval';
		$searchMode					=	$this->_bindSearchRangeMode( $field, $searchVals, $postdata, $minNam, $maxNam, $list_compare_types );
		if ( $searchMode ) {
			$ratingField			=	$field->name;

			$minVal					=	(int) cbGetParam( $postdata, $minNam, 0 );
			$maxVal					=	(int) cbGetParam( $postdata, $maxNam, 0 );

			if ( $minVal && ( cbGetParam( $postdata, $minNam, '' ) !== '' ) && ( $minVal >= 0 ) ) {
				$searchVals->$minNam =	$minVal;
				$query[]			=	$this->_intToSql( $field, $ratingField, $minVal, '>=', $searchMode );
			}
			if ( $maxVal && ( cbGetParam( $postdata, $maxNam, '' ) !== '' ) && ( $maxVal <= (int) $this->numStars ) ) {
				$searchVals->$maxNam =	$maxVal;
				$query[]			=	$this->_intToSql( $field, $ratingField, $maxVal, '<=', $searchMode );
			}
		}
		return $query;
	}
	function _intToSql( &$field, $col, $value, $operator, $searchMode ) {
		$value							=	(int) $value;
		// $this->validate( $field, $user, $col, $value, $postdata, $reason );
		$sql							=	new cbSqlQueryPart();
		$sql->tag						=	'column';
		$sql->name						=	$col;
		$sql->table						=	$field->table;
		$sql->type						=	'sql:field';
		$sql->operator					=	$operator;
		$sql->value						=	(int) $value;
		$sql->valuetype					=	'const:int';
		$sql->searchmode				=	$searchMode;
		return $sql;
	}
	/**
	 * Returns HTML form for rating with javascript-assisted radio-buttons
	 *
	 * @param  int      $mode         1-Display, 2-Embedded Input, 3-Standalone
	 * @param  int      $value        1 .. $starsNumber
	 * @param  string   $inputName    Name of the <input type="radio" /> tags (value is 1 .. $starsNumber)
	 * @param  boolean  $mandatory    If form is mandatory, a star will be displayed
	 * @param  int      $starsNumber  Total umber of stars (default: 5)
	 * @param  int      $starsFraction
	 * @param  int      $userid
	 * @param  string   $appendDisplay
	 * @param  array    $displayStrings
	 * @return string
	 */	
	function getRatingUI( $mode, $value, $inputName, $mandatory = false, $starsNumber = 5, $starsFraction = 1, $userid = 0, $appendDisplay = '', $displayStrings = null ) {
		if ( $displayStrings === null ) {
			$displayStrings		=	array();
		}
		global $_CB_framework;

		/* Declare Variables */
		$chk					=	' checked="checked"';
		$totalStars				=	$starsNumber * $starsFraction;
		$value 					=	$value * $starsFraction;
		
		$options				= 	"";
		$cbSpoofField			=	cbSpoofField();
		$cbSpoofString			=	cbSpoofString( null, 'fieldclass' );
		$regAntiSpamFieldName	=	cbGetRegAntiSpamFieldName();
		$regAntiSpamValues		=	cbGetRegAntiSpams();

		$live_site				=	$_CB_framework->getCfg( 'live_site' );
		$regAntiSpZ				=	$regAntiSpamValues[0];
		$containerPrefix		= 	$inputName.$userid;	
		
		/* Build HTML */
		$html		=	'<div id="'.$containerPrefix.'DIV" class="content_vote">'
					//.	'<div style="float:left;">'
					//.	($mode!=1 ? $displayStrings['RatingTitle0'].'&nbsp;' : '')
					//.	' </div>'
					.	'<div class="stars" style="float:left;width:' . ( $starsNumber * 17 + 16 + 20 ) . 'px;">'
					;
		for ( $i = 1 ; $i <= $totalStars ; $i++ ) {
			$html	.=	'<input type="radio" '
					.	' class="star {split:'.$starsFraction.'}"'
					.	' alt="' . ( $i == 1 ? sprintf( CBTxt::T('Rate %s %s'), $i, $displayStrings['RatingUnitTitle'] ) : sprintf( CBTxt::T('Rate %s %s'), $i, $displayStrings['RatingUnitsTitle'] ) ) . '"'
//					.	' id="'.$containerPrefix.'"'
					.	' name="' . $inputName . '"'
					.	( $value == $i ? $chk : '' )
					.	' value="' . round($i/$starsFraction,2) . '"'
					.	' title="'.$displayStrings['RatingTitle'.floor(($i/$starsFraction)*pow(10, 0))/pow(10, 0)].'" />';
		}
		$html		.=	'</div>'
					//.	'<div style="float:left;"> '
					//.	($mode!=1 ? '&nbsp;'.$displayStrings['RatingTitle'.$starsNumber].'&nbsp;' : '')
					//.	'</div>'
					.	$appendDisplay					
					.	'<div style="clear:both;"></div>'
					. 	($mode!=1 ? '<span id="'.$containerPrefix.'HOVER">' . CBTxt::Th('Click on a star to rate!') . '</span>' : '')
					.	'</div>'
					;

		/* Load Necessary AJAX/Javascript */
		$ajaxUrl				=	cbSef( 'index.php?option=com_comprofiler&task=fieldclass&field=' . str_replace( 'Self', '', $inputName ) . '&function=savevalue&user=' . $userid . '&reason=profile', false, 'raw' );

		$options				=	( $mandatory ? "required:true, " : "" );
		$options				.=	"cancel: '" . addslashes( CBTxt::T('Cancel Rating') ) . "'";
		if ( $mode == 3 ) {
			$options	.=	", callback: ".
					" function(value, link){ ".
					"	$.ajax( {	type: 'POST',".
					"				url:  '$ajaxUrl', ".
					"				data: '".str_replace("Self","",$inputName)."=' + encodeURIComponent( value ) + '&$cbSpoofField=' + encodeURIComponent('$cbSpoofString') + '&$regAntiSpamFieldName=' + encodeURIComponent('$regAntiSpZ')+ '&uid=$userid', ".
					"				success: function(response) { ".
					"					$('#".$containerPrefix."HOVER').html(response); ".
					"					$('#".$containerPrefix."DIV .cancel').remove(); ".
					"					$('#".$containerPrefix."DIV div.star').addClass('star_readonly').removeClass('star_live').unbind('mouseout').unbind('mouseover').unbind('click'); ".
					"					$('#".$containerPrefix."DIV input[type=hidden]').attr('disabled','disabled'); ".		
					"				}, ".
					"				dataType: 'html' ".
					"	}); ".
					" } ";
		}
		if ( $mode == 1 ) {
			$options .= ", readOnly: true"; 
		}
		if ( $mode != 1 ) {
			$options .= ", focus: function(value, link){ var tip = $('#".$containerPrefix."HOVER'); tip[0].data = tip[0].data || tip.html(); tip.html(link.title || 'value: '+value); }";
  			$options .= ", blur: function(value, link){ var tip = $('#".$containerPrefix."HOVER'); $('#".$containerPrefix."HOVER').html(tip[0].data || '');}";
  		}

		$_CB_framework->outputCbJQuery( "$('#" . $containerPrefix . "DIV input[type=radio].star').rating({".$options."}).trigger('mouseout');", array('rating','metadata') );

		if ( $mode != 2 ) {
			$html		=	'<form action="' . '#' . '" name="' . $containerPrefix . 'FORM" id="' . $containerPrefix . 'FORM" method="post">' . $html . '</form>';
		}
		return $html;
	}
	function _loadSettings( &$field ) {
		$this->numStars					=	$field->params->get( 'NumStars', 5 );
		$this->ratingFraction			=	$field->params->get( 'RatingFraction', 1 );
		$this->allowAnnonymous			=	$field->params->get( 'AllowAnnonymous', 1 );		
		//$RatingDisplay					=	$field->params->get( 'RatingDisplay', 'image' );
	}
	function _loadDisplaySettings( &$field ) {
		$this->displayStrings			=	array('RatingSelfTitle'	=>	CBTxt::T( $field->params->get( 'RatingSelfTitle', 'Self' ) )
												, 'RatingVisitorTitle'	=>	CBTxt::T( $field->params->get( 'RatingVisitorTitle', 'Visitor' ) )
												, 'RatingActionTitle'	=>	CBTxt::T( $field->params->get( 'RatingActionTitle', 'Rating' ) )
												, 'RatingUnitTitle'		=>	CBTxt::T( $field->params->get( 'RatingUnitTitle', 'Star' ) )
												, 'RatingUnitsTitle'	=>	CBTxt::T( $field->params->get( 'RatingUnitsTitle', 'Stars' ) )
												, 'RatingTitle0'		=>	CBTxt::T( $field->params->get( 'RatingTitle0', 'Poorest' ) )
												, 'RatingTitle1'		=>	CBTxt::T( $field->params->get( 'RatingTitle1', 'Poor' ) )
												, 'RatingTitle2'		=>	CBTxt::T( $field->params->get( 'RatingTitle2', 'Average' ) )
												, 'RatingTitle3'		=>	CBTxt::T( $field->params->get( 'RatingTitle3', 'Good' ) )
												, 'RatingTitle4'		=>	CBTxt::T( $field->params->get( 'RatingTitle4', 'Better' ) )											
												, 'RatingTitle5'		=>	CBTxt::T( $field->params->get( 'RatingTitle5', 'Best' ) )
												, 'RatingTitle6'		=>	CBTxt::T( $field->params->get( 'RatingTitle6', '' ) )
												, 'RatingTitle7'		=>	CBTxt::T( $field->params->get( 'RatingTitle7', '' ) )
												, 'RatingTitle8'		=>	CBTxt::T( $field->params->get( 'RatingTitle8', '' ) )
												, 'RatingTitle9'		=>	CBTxt::T( $field->params->get( 'RatingTitle9', '' ) )
												, 'RatingTitle10'		=>	CBTxt::T( $field->params->get( 'RatingTitle10', '' ) )
												, 'RatingTitle11'		=>	CBTxt::T( $field->params->get( 'RatingTitle11', '' ) )
											);
	}
}	// class CBfield_rating_base

class CBfield_myrating extends cbRating_base {
	/**
	 * Returns a field in specified format
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  string                $output  'html', 'xml', 'json', 'php', 'csvheader', 'csv', 'rss', 'fieldslist', 'htmledit'
	 * @param  string                $reason  'profile' for user profile view and edit, 'register' for registration, 'list' for user-lists
	 * @param  int                   $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @return mixed
	 */
	function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		global $_CB_framework;

		$this->_loadSettings( $field );
		$this->_loadDisplaySettings( $field );

		$colSelfRating				=	$field->name;
		$value						=	$user->$colSelfRating;	

		$oReturn					=	null;

		$required					=	$this->_isRequired( $field, $user, $reason );

		switch ( $output ) {
			case 'html':
			case 'rss':
				$oReturn			= 	$this->getRatingUI(($_CB_framework->myId()==$user->id ? 3 : 1), $value, $field->name, false, $this->numStars, $this->ratingFraction, $user->id, '', $this->displayStrings );
				break;
			case 'htmledit':
				if ( $reason == 'search' ) {
					$minNam			=	$field->name . '__minval';
					$maxNam			=	$field->name . '__maxval';

					$minVal			=	$user->get( $minNam );
					$maxVal			=	$user->get( $maxNam );

					// $fieldNameSave	=	$field->name;
					// $field->name	=	$minNam;
					// $minHtml		=	$this->_fieldEditToHtml( $field, $user, $reason, 'input', 'text', $minVal, '' );
					$minHtml		=	$this->getRatingUI(2, $minVal, $minNam, false, $this->numStars, $this->ratingFraction, $user->id, '', $this->displayStrings );	
					// $field->name	=	$maxNam;
					// $maxHtml		=	$this->_fieldEditToHtml( $field, $user, $reason, 'input', 'text', $maxVal, '' );
					$maxHtml		=	$this->getRatingUI(2, $maxVal, $maxNam, false, $this->numStars, $this->ratingFraction, $user->id, '', $this->displayStrings );	
					// $field->name	=	$fieldNameSave;
					$oReturn		=	$this->_fieldSearchRangeModeHtml( $field, $user, $output, $reason, $value, $minHtml, $maxHtml, $list_compare_types );
				} else {
					$oReturn		=	$this->getRatingUI(2, $value, $field->name, $required, $this->numStars, $this->ratingFraction, $user->id, $this->_fieldIconsHtml( $field, $user, 'htmledit', $reason, 'select', '', null, '', array(), true, $required ), $this->displayStrings );	
				}
				break;
			case 'json':
			case 'php':
			case 'xml':
			case 'csvheader':
			case 'fieldslist':
			case 'csv':
			default:
				$oReturn			=	$this->_formatFieldOutputIntBoolFloat( $field->name, $value, $output );
				break;
		}
		return $oReturn;
	}
	/**
	 * Prepares field data for saving to database (safe transfer from $postdata to $user)
	 * Override
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user      RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array                 $postdata  Typically $_POST (but not necessarily), filtering required.
	 * @param  string                $reason    'profile' for save user edit, 'register' for save registration
	 */
	function prepareFieldDataSave( &$field, &$user, &$postdata, $reason ) {
		$this->_prepareFieldMetaSave( $field, $user, $postdata, $reason );

		$this->_loadSettings( $field );

		$colSelfRating			= 	$field->name;
		$value					=	cbGetParam( $postdata, $field->name );
		$validated				=	$this->validate( $field, $user, $colSelfRating, $value, $postdata, $reason );
		if ( $validated && isset( $user->$colSelfRating ) && ( ( (string) $user->$colSelfRating ) !== (string) $value ) && ! ( ( $user->$colSelfRating === '0' ) && ( $value == '' ) ) ) {
			$this->_logFieldUpdate( $field, $user, $reason, $user->$colSelfRating, $value );
		}
		$user->$colSelfRating	=	$value;
	}
	/**
	 * Direct access to field for custom operations, like for Ajax
	 *
	 * WARNING: direct unchecked access, except if $user is set, then check
	 * that the logged-in user has rights to edit that $user.
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  array                 $postdata
	 * @param  string                $reason     'profile' (as nothing can really be trusted in here.
	 * @return string                            Expected output.
	 */
	function fieldClass( &$field, &$user, &$postdata, $reason ) {
		global $_CB_framework;

		$myId							=	$_CB_framework->myId();
		if ( $myId && $user && ( $myId == $user->id ) ) {
			return parent::fieldClass( $field, $user, $postdata, $reason );
		}
		return null;
	}
}	// class CBfield_myrating
class CBfield_yourrating extends cbRating_base {
	function _loadSettings( &$field ) {
		parent::_loadSettings( $field );
		$this->enableLogging			=	$field->params->get( 'EnableLogging', 1 );
		$this->multiVotes				=	$field->params->get( 'MultiVotes', 0 );
		$this->voteGap					=	$field->params->get( 'VoteGap', 0 );
	}
	/**
	 * Returns a field in specified format
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  string                $output  'html', 'xml', 'json', 'php', 'csvheader', 'csv', 'rss', 'fieldslist', 'htmledit'
	 * @param  string                $reason  'profile' for user profile view and edit, 'register' for registration, 'list' for user-lists
	 * @param  int                   $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @return mixed
	 */
	function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		global $_CB_framework;

		$this->_loadSettings( $field );
		$this->_loadDisplaySettings( $field );

		$colRating					= 	$field->name;
		$Value_Rating				= 	$user->$colRating;
		// $colCount				= 	$field->name . '_count';
		// $Value_Count				= 	$user->$colCount;

		$oReturn					=	null;

		$value						=	(float) cbRatings::CalcValue( $Value_Rating, $this->ratingFraction );
		
		switch ( $output ) {
			case 'html':
			case 'rss':
				if ( ! ( $_CB_framework->myId() > 0 ) && ! $this->allowAnnonymous ) {
					$canVote		=	false;
				} else {
					$canVote		=	cbRatings::canVote( $user->id, $field->fieldid, $this->multiVotes, $this->voteGap );
				}
				$oReturn			= 	$this->getRatingUI( ( $_CB_framework->myId() == $user->id || ! $canVote ? 1 : 3 ), $value, $field->name, false, $this->numStars, $this->ratingFraction, $user->id, '', $this->displayStrings );	
				break; 
			break;
			case 'htmledit':
				if ( $reason == 'search' ) {
					$minNam			=	$field->name . '__minval';
					$maxNam			=	$field->name . '__maxval';

					$minVal			=	$user->get( $minNam );
					$maxVal			=	$user->get( $maxNam );

					$fieldNameSave	=	$field->name;
					$field->name	=	$minNam;
					$minHtml		=	$this->_fieldEditToHtml( $field, $user, $reason, 'input', 'text', $minVal, '' );
					$field->name	=	$maxNam;
					$maxHtml		=	$this->_fieldEditToHtml( $field, $user, $reason, 'input', 'text', $maxVal, '' );
					$field->name	=	$fieldNameSave;
					$oReturn		=	$this->_fieldSearchRangeModeHtml( $field, $user, $output, $reason, $value, $minHtml, $maxHtml, $list_compare_types );
				}
			break;
			case 'json':
			case 'php':
			case 'xml':
			case 'csvheader':
			case 'fieldslist':
			case 'csv':
			default:
				if ( $Value_Rating !== null ) {
					$value			=	(float) $Value_Rating;
				} elseif ( $output == 'php' ) {
					$value			=	null;
				} else {
					$value			=	'null';
				}
				
				$oReturn			=	$this->_formatFieldOutputIntBoolFloat( $field->name, $value, $output );
				break;
		}
		return $oReturn;
	}
	/**
	 * Prepares field data for saving to database (safe transfer from $postdata to $user)
	 * Override
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user      RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array                 $postdata  Typically $_POST (but not necessarily), filtering required.
	 * @param  string                $reason    'profile' for save user edit, 'register' for save registration
	 */
	function prepareFieldDataSave( &$field, &$user, &$postdata, $reason ) {
		global $_CB_framework;
		
		$this->_prepareFieldMetaSave( $field, $user, $postdata, $reason );

		$this->_loadSettings( $field );

		$colRating					= 	$field->name;
		$colCount					= 	$field->name . '_count';
		$value						=	cbGetParam( $postdata, $colRating );
		$validated					=	$this->validate( $field, $user, $colRating, $value, $postdata, $reason );
		if( $_CB_framework->myId() != $user->id ) {
			if ( $validated ) {
				$this->_logFieldUpdate( $field, $user, $reason, $user->$colRating, $value );
				$user->$colRating	=	( ( ( $user->$colRating * $user->$colCount ) + $value ) / ++$user->$colCount );
				if ( $this->enableLogging == 1 && $value != null ) {
					cbRatings::logVote($value, $field->fieldid, $user->id, $postdata);
				}
			}
		}
		if ( ! isset( $user->$colRating ) ) {
			$user->$colRating		=	null;
		}
	}
	/**
	 * Direct access to field for custom operations, like for Ajax
	 *
	 * WARNING: direct unchecked access, except if $user is set, then check
	 * that the logged-in user has rights to edit that $user.
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  array                 $postdata
	 * @param  string                $reason     'profile' (as nothing can really be trusted in here.
	 * @return string                            Expected output.
	 */
	function fieldClass( &$field, &$user, &$postdata, $reason ) {
		global $_CB_framework;

		if ( $user && $user->id != $_CB_framework->myId() ) {
			return parent::fieldClass( $field, $user, $postdata, $reason );
		}
		return null;
	}
}	// class CBfield_yourrating
class cbRatings {
	var $NumStars			=	0;
	var $RatingFraction		=	1;
	var $RatingAudience		=	"";
	var $RatingDisplay		=	"";
	var $EnableLogging		=	0;
	var $MultiVotes			=	0;
	var $VoteGap			=	0;
	var $LowTitle			=	"";			
	var $HighTitle			=	"";
	var $UnitTitle			=	"";
	var $UnitsTitle			=	"";
	/**
	 * Constructor
	 *
	 * @param  array $params
	 * @return cbRatings
	 */	
	function cbRatings($params) {
		$this->bind($params);
	}
	/**
	 * Returns the calculated rating for a single vote
	 *
	 * @param  int  	 $rawValue         	raw Vote selection
	 * @param  int		 $ratingFraction    fractions votes can be cast in
	 * @return float
	 */	
	function CalcValue($rawValue, $ratingFraction) 
	{
		$val			=	round( abs( $rawValue ) * $ratingFraction ) / $ratingFraction;
		return cbRatings::sgn( $rawValue ) * $val;
	}

	function sgn( $x ) {
		return ( $x ? ( $x > 0 ? 1 : -1) : 0 );
	}
	function canVote( $userid, $fieldid, $multiple = true, $secondsBetween = 30 ) {
		global $_CB_framework, $_CB_database, $_SERVER;
		
		$rateruserid	=	$_CB_framework->myId();
		$raterip		=	cbGetIPlist();

		$query			=	'SELECT COUNT(*) FROM #__comprofiler_ratingfield_log WHERE fieldid = ' . (int) $fieldid
						.	' AND rateduserid = ' . (int) $userid
						.	($multiple ? ' AND ' . $_CB_database->NameQuote( 'date' ) . ' > DATE_SUB( NOW(), INTERVAL ' . (int) $secondsBetween . ' SECOND)' : '')
						.	' AND ( (rateruserid = ' . (int) $rateruserid . ' AND rateruserid <> 0) || (rateruserid = 0 AND raterip = ' . $_CB_database->Quote( $raterip ) . '))'
						;
		$_CB_database->setQuery( $query );
		$count			=	$_CB_database->loadResult();	
		return ( $count == 0 );
	}	

	function logVote($rating, $fieldid, $userid, $postdata) {
		global $_CB_framework, $_CB_database, $_SERVER;

		$rateruserid	=	$_CB_framework->myId();
		$raterip		=	cbGetIPlist();
		$rateduserid	=	$userid;
		$rating			=	$rating;
		
		$query			=	'SELECT COUNT(*) FROM #__comprofiler_ratingfield_log WHERE fieldid = ' . (int) $fieldid
						.	' AND rateruserid = ' . (int) $rateruserid
						.	' AND rating = ' . (float) $rating
						.	' AND raterip = ' . $_CB_database->Quote( $raterip )
						.	' AND rateduserid = ' . (int) $rateduserid
						;
		$_CB_database->setQuery($query);
		$count			=	$_CB_database->loadResult();

		if ( $count == 0 ) {			// avoid double-posts on clicking reload !
			$query		=	'INSERT INTO #__comprofiler_ratingfield_log SET fieldid = ' . (int) $fieldid
						.	', rateruserid = ' . (int) $rateruserid
						.	', rating = ' . (float) $rating
						.	', raterip = ' . $_CB_database->Quote( $raterip )
						.	', rateduserid = ' . (int) $rateduserid
						.	', date = NOW()'
						;
			$_CB_database->setQuery($query);
			if ( ! $_CB_database->query() ) {
				trigger_error( 'cbRating::logVote SQL error: ' . $_CB_database->stderr( true ), E_USER_WARNING );
			}
		}
	}
}	// class cbRatings
?>