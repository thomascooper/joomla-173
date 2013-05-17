<?php
/**
* Joomla Community Builder User Plugin: plug_cbfilefield
* @version $Id: cb.filefield.php 2247 2012-02-03 21:30:41Z kyle $
* @package plug_cbfilefield
* @subpackage cb.filefield.php
* @author Beat
* @copyright (C) 2009 www.joomlapolis.com
* @license Limited http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerFunction( 'onAfterUserRegistration', 'sendNotifications', 'CBfield_file' );
$_PLUGINS->registerFunction( 'onAfterNewUser', 'sendNotifications', 'CBfield_file' );
$_PLUGINS->registerFunction( 'onAfterUpdateUser', 'sendNotifications', 'CBfield_file' );
$_PLUGINS->registerFunction( 'onAfterUserUpdate', 'sendNotifications', 'CBfield_file' );
$_PLUGINS->registerFunction( 'onAfterUserFileUpdate', 'sendNotifications', 'CBfield_file' );
$_PLUGINS->registerFunction( 'onBeforeDeleteUser', 'deleteFiles', 'CBfield_file' );
$_PLUGINS->registerUserFieldTypes( array( 'file' => 'CBField_ajaxfile' ) );
$_PLUGINS->registerUserFieldParams();

/**
 * Returns array of uploaded files to be handled later
 *
 * @param object $filename
 * @return array
 */
function CBfield_file_rememberFileToUpload( $file ) {
	static $files	=	array();

	$return			=	null;

	if ( $file === true ) {
		$return		=	$files;
	} elseif ( $file === 'clear' ) {
		$files		=	array();
	} else {
		$files[]	=	$file;
	}

	return $return;
}

class CBfield_file extends cbFieldHandler {

	/**
	 * Returns a field in specified format
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  string                $output  'html', 'xml', 'json', 'php', 'csvheader', 'csv', 'rss', 'fieldslist', 'htmledit'
	 * @param  string                $reason  'profile' for user profile view, 'edit' for profile edit, 'register' for registration, 'list' for user-lists
	 * @param  int                   $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @return mixed
	 */
	function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		cbimport( 'language.cbteamplugins' );

		$value					=	$user->get( $field->name );

		switch ( $output ) {
			case 'html':
			case 'rss':
				$return			=	$this->_fileLivePath( $field, $user, $reason );
				break;
			case 'htmledit':
				if ( $reason == 'search' ) {
					$choices	=	array();
					$choices[]	=	moscomprofilerHTML::makeOption( '', _UE_NO_PREFERENCE );
					$choices[]	=	moscomprofilerHTML::makeOption( '1', CBTxt::T( 'Has a file' ) );
					$choices[]	=	moscomprofilerHTML::makeOption( '0', CBTxt::T( 'Has no file' ) );
					$html		=	$this->_fieldEditToHtml( $field, $user, $reason, 'input', 'select', $value, '', $choices );
					$return		=	$this->_fieldSearchModeHtml( $field, $user, $html, 'singlechoice', $list_compare_types );
				} else {
					$return		=	$this->_htmlEditForm( $field, $user, $reason );
				}
				break;
			default:
				$fileUrl		=	$this->_fileLivePath( $field, $user, $reason );
				$return			=	$this->_formatFieldOutput( $field->name, $fileUrl, $output );
				break;
		}

		return $return;
	}

	/**
	 * Prepares field data for saving to database (safe transfer from $postdata to $user)
	 * Override
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user      RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array                 $postdata  Typically $_POST (but not necessarily), filtering required.
	 * @param  string                $reason    'edit' for save profile edit, 'register' for registration, 'search' for searches
	 */
	function prepareFieldDataSave( &$field, &$user, &$postdata, $reason ) {
		global $_CB_framework, $_CB_database;

		$this->_prepareFieldMetaSave( $field, $user, $postdata, $reason );

		cbimport( 'language.cbteamplugins' );

		$col					=	$field->name;
		$col_choice				=	$col . '__choice';
		$col_file				=	$col . '__file';
		$choice					=	stripslashes( cbGetParam( $postdata, $col_choice ) );

		switch ( $choice ) {
			case 'upload':
				if ( ( $reason == 'profile' ) && $field->params->get( 'fieldAjax', 0 ) ) {
					$this->commitFieldDataSave( $field, $user, $postdata, $reason );
				} else {
					$value		=	( isset( $_FILES[$col_file] ) ? $_FILES[$col_file] : null );

					$this->validate( $field, $user, $choice, $value, $postdata, $reason );
				}
				break;
			case 'delete':
				if ( $user->id && ( $user->$col != null ) && ( $user->$col != '' ) ) {
					if ( isset( $user->$col ) ) {
						$this->_logFieldUpdate( $field, $user, $reason, $user->$col, '' );
					}

					$this->deleteFiles( $user, $user->$col );

					$user->$col	=	null;

					$query		=	'UPDATE ' . $_CB_database->NameQuote( $field->table )
								.	"\n SET " . $_CB_database->NameQuote( $col ) . " = NULL"
								.	', ' . $_CB_database->NameQuote( 'lastupdatedate' ) . ' = ' . $_CB_database->Quote( $_CB_framework->dateDbOfNow() )
								.	"\n WHERE " . $_CB_database->NameQuote( 'id' ) . " = " . (int) $user->id;
					$_CB_database->setQuery( $query );
					$_CB_database->query();
				}
				break;
			default:
				$value			=	$user->get( $field->name );

				$this->validate( $field, $user, $choice, $user->get( $field->name ), $postdata, $reason );
				break;
		}
	}

	/**
	 * Mutator:
	 * Prepares field data commit
	 * Override
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user      RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array                 $postdata  Typically $_POST (but not necessarily), filtering required.
	 * @param  string                $reason    'edit' for save user edit, 'register' for save registration
	 */
	function commitFieldDataSave( &$field, &$user, &$postdata, $reason ) {
		global $_CB_framework, $_PLUGINS, $_FILES;

		$col						=	$field->name;
		$col_choice					=	$col . '__choice';
		$col_file					=	$col . '__file';
		$choice						=	stripslashes( cbGetParam( $postdata, $col_choice ) );

		switch ( $choice ) {
			case 'upload':
				$value				=	( isset( $_FILES[$col_file] ) ? $_FILES[$col_file] : null );

				if ( $this->validate( $field, $user, $choice, $value, $postdata, $reason ) ) {
					$ajax			=	(int) $field->params->get( 'fieldAjax', 0 );

					$_PLUGINS->loadPluginGroup( 'user' );

					if ( ( $reason == 'profile' ) && $ajax ) {
						$_PLUGINS->trigger( 'onBeforeUserAjaxFileUpdate', array( &$user, &$value['tmp_name'] ) );
					} else {
						$_PLUGINS->trigger( 'onBeforeUserFileUpdate', array( &$user, &$value['tmp_name'] ) );
					}

					if ( $_PLUGINS->is_errors() ) {
						$this->_setErrorMSG( $_PLUGINS->getErrorMSG() );
					}

					$path			=	$_CB_framework->getCfg( 'absolute_path' );
					$index_path		=	$path . '/components/com_comprofiler/plugin/user/plug_cbfilefield/index.html';
					$files_path		=	$path . '/images/comprofiler/plug_cbfilefield';
					$file_path		=	$files_path . '/' . (int) $user->id;

					if ( ! is_dir( $files_path ) ) {
						$oldmask	=	@umask( 0 );

						if ( @mkdir( $files_path, 0755, true ) ) {
							@umask( $oldmask );
							@chmod( $files_path, 0755 );

							if ( ! file_exists( $files_path . '/index.html' ) ) {
								@copy( $index_path, $files_path . '/index.html' );
								@chmod( $files_path . '/index.html', 0755 );
							}
						} else {
							@umask( $oldmask );
						}
					}

					if ( ! is_dir( $file_path ) ) {
						$oldmask	=	@umask( 0 );

						if ( @mkdir( $file_path, 0755, true ) ) {
							@umask( $oldmask );
							@chmod( $file_path, 0755 );

							if ( ! file_exists( $file_path . '/index.html' ) ) {
								@copy( $index_path, $file_path . '/index.html' );
								@chmod( $file_path . '/index.html', 0755 );
							}
						} else {
							@umask( $oldmask );
						}
					}

					$uploaded_file	=	preg_match( '/(.+)\.([a-zA-Z0-9]+)$/i', $value['name'], $matches );
					$uploaded_name	=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $matches[1] );
					$uploaded_ext	=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $matches[2] );
					$newFileName	=	uniqid( $uploaded_name . '_' ). '.' . $uploaded_ext;

					if ( ! move_uploaded_file( $value['tmp_name'], $file_path . '/'. $newFileName ) ) {
						$this->_setValidationError( $field, $user, $reason, sprintf( CBTxt::T( 'CBFile-failed to upload file: %s' ), $newFileName ) );

						return;
					} else {
						@chmod( $file_path . '/' . $value['tmp_name'], 0755 );
					}

					if ( isset( $user->$col ) ) {
						$this->_logFieldUpdate( $field, $user, $reason, $user->$col, '' );
					}

					if ( isset( $user->$col ) && ( $user->$col != '' ) ) {
						$this->deleteFiles( $user, $user->$col );
					}

					$user->$col		=	$newFileName;

					if ( ( $reason == 'profile' ) && $ajax ) {
						$_PLUGINS->trigger( 'onAfterUserAjaxFileUpdate', array( &$user, $newFileName ) );
					} else {
						$_PLUGINS->trigger( 'onAfterUserFileUpdate', array( &$user, $newFileName ) );
					}

					$file			=	new stdClass();
					$file->field	=	$field;
					$file->name		=	$newFileName;

					CBfield_file_rememberFileToUpload( $file );
				}
				break;
		}
	}

	/**
	 * Mutator:
	 * Prepares field data rollback
	 * Override
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user      RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array                 $postdata  Typically $_POST (but not necessarily), filtering required.
	 * @param  string                $reason    'edit' for save user edit, 'register' for save registration
	 */
	function rollbackFieldDataSave( &$field, &$user, &$postdata, $reason ) {
		global $_FILES;

		$col			=	$field->name;
		$col_choice		=	$col . '__choice';
		$col_file		=	$col . '__file';

		$choice			=	stripslashes( cbGetParam( $postdata, $col_choice ) );

		switch ( $choice ) {
			case 'upload':
				$value	=	( isset( $_FILES[$col_file] ) ? $_FILES[$col_file] : null );

				if ( $this->validate( $field, $user, $choice, $value, $postdata, $reason ) ) {
					$this->deleteFiles( $user, $user->$col );
				}
				break;
		}
	}

	/**
	 * outputs a secure list of allowed file extensions
	 *
	 * @param string $extensions
	 * @return array
	 */
	function allowedExtensions( $extensions = 'zip,rar,doc,pdf,txt,xls' ) {
		$allowed			=	explode( ',', $extensions );

		if ( $allowed ) {
			$not_allowed	=	array( 'php', 'php3', 'php4', 'php5', 'asp', 'exe', 'py' );

			foreach ( $not_allowed as $extension ) {
				$key		=	array_search( $extension, $allowed );

				if ( $key ) {
					unset( $allowed[$key] );
				}
			}
		}

		return $allowed;
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
	 * @param  string                $reason      'edit' for save user edit, 'register' for save registration
	 * @return boolean                            True if validate, $this->_setErrorMSG if False
	 */
	function validate( &$field, &$user, $columnName, &$value, &$postdata, $reason ) {
		$isRequired							=	$this->_isRequired( $field, $user, $reason );

		switch ( $columnName ) {
			case 'upload':
				if ( ! isset( $value['tmp_name'] ) || empty( $value['tmp_name'] ) || ( $value['error'] != 0 ) || ! is_uploaded_file( $value['tmp_name'] ) ) {
					if ( $isRequired ) {
						$this->_setValidationError( $field, $user, $reason, CBTxt::T( 'Please select a file before uploading' ) );
					}

					return false;
				} else {
					$upload_size_limit_max	=	(int) $field->params->get( 'fieldValidateFile_sizeMax', 1024 );
					$upload_size_limit_min	=	(int) $field->params->get( 'fieldValidateFile_sizeMin', 0 );
					$upload_ext_limit		=	$this->allowedExtensions( $field->params->get( 'fieldValidateFile_types', 'zip,rar,doc,pdf,txt,xls' ) );
					$uploaded_file			=	preg_match( '/(.+)\.([a-zA-Z0-9]+)$/i', $value['name'], $matches );

					if ( $uploaded_file ) {
						if ( isset( $matches[1] ) ) {
							$uploaded_name	=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $matches[1] );
						} else {
							$this->_setValidationError( $field, $user, $reason, CBTxt::T( 'Please select a file before uploading' ) );
							return false;
						}

						if ( isset( $matches[2] ) ) {
							$uploaded_ext	=	preg_replace( '/[^-a-zA-Z0-9_]/', '', $matches[2] );
						} else {
							$this->_setValidationError( $field, $user, $reason, sprintf( CBTxt::T( 'Please upload only %s' ), implode( ',', $upload_ext_limit ) ) );
							return false;
						}
					} else {
						$this->_setValidationError( $field, $user, $reason, CBTxt::T( 'Please select a file before uploading' ) );
						return false;
					}

					$newFileName			=	uniqid( $uploaded_name . '_' ). '.' . $uploaded_ext;

					if ( ! $newFileName ) {
						$this->_setValidationError( $field, $user, $reason, CBTxt::T( 'Please select a file before uploading' ) );
						return false;
					}

					$uploaded_size			=	$value['size'];

					if ( ( $uploaded_size / 1024 ) > $upload_size_limit_max ) {
						$this->_setValidationError( $field, $user, $reason, sprintf( CBTxt::T( 'The file size exceeds the maximum of %s KB' ), $upload_size_limit_max ) );
						return false;
					}

					if ( ( $uploaded_size / 1024 ) < $upload_size_limit_min ) {
						$this->_setValidationError( $field, $user, $reason, sprintf( CBTxt::T( 'The file is too small, the minimum is %s KB' ), $upload_size_limit_min ) );
						return false;
					}

					if ( ! in_array( $uploaded_ext, $upload_ext_limit ) ) {
						$this->_setValidationError( $field, $user, $reason, sprintf( CBTxt::T( 'Please upload only %s' ), implode( ',', $upload_ext_limit ) ) );
						return false;
					}
				}
				break;
			default:
				$valCol						=	$field->name;

				if ( $isRequired && ( ( ! $user ) || ( ! isset( $user->$valCol ) ) || ( ! $user->$valCol ) ) ) {
					if ( ! $value ) {
						$this->_setValidationError( $field, $user, $reason, _UE_FIELDREQUIRED );
						return false;
					}
				}
				break;
		}

		return true;
	}

	/**	 * Finder:
	 * Prepares field data for saving to database (safe transfer from $postdata to $user)
	 * Override
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $searchVals  RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array                 $postdata    Typically $_POST (but not necessarily), filtering required.
	 * @param  int                   $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @param  string                $reason      'edit' for save profile edit, 'register' for registration, 'search' for searches
	 * @return array of cbSqlQueryPart
	 */
	function bindSearchCriteria( &$field, &$searchVals, &$postdata, $list_compare_types, $reason ) {
		$query					=	array();
		$searchMode				=	$this->_bindSearchMode( $field, $searchVals, $postdata, 'none', $list_compare_types );
		$col					=	$field->name;
		$value					=	cbGetParam( $postdata, $col );

		if ( $value === '0' ) {
			$value				=	0;
		} elseif ( $value == '1' ) {
			$value				=	1;
		} else {
			$value				=	null;
		}

		if ( $value !== null ) {
			$searchVals->$col	=	$value;

			$sql				=	new cbSqlQueryPart();
			$sql->tag			=	'column';
			$sql->name			=	$col;
			$sql->table			=	$field->table;
			$sql->type			=	'sql:field';
			$sql->operator		=	$value ? 'IS NOT' : 'IS';
			$sql->value			=	'NULL';
			$sql->valuetype		=	'const:null';
			$sql->searchmode	=	$searchMode;

			$query[]			=	$sql;
		}

		return $query;
	}

	/**
	 * Returns full URL of thumbnail of avatar
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser  $user
	 * @param  int                 $show_avatar
	 * @return string              URL
	 */
	function _fileLivePath( &$field, &$user, $reason ) {
		global $_CB_framework;

		$oValue					=	null;

		if ( $user && $user->id ) {
			$col				=	$field->name;
			$file				=	$user->$col;

			if ( $file != null ) {
				$live_site		=	$_CB_framework->getCfg( 'live_site' );
				$clean_file		=	preg_replace( '/[^-a-zA-Z0-9_.]/', '', $file );
				$file_ext		=	strtolower( pathinfo( $clean_file, PATHINFO_EXTENSION ) );
				$file_name		=	substr( rtrim( pathinfo( $clean_file, PATHINFO_BASENAME ), '.' . $file_ext ), 0, -14 ) . '.' . $file_ext;
				$file_icon		=	'/components/com_comprofiler/plugin/user/plug_cbfilefield/images/' . $file_ext . '.png';
				$oValue			=	'/images/comprofiler/plug_cbfilefield/' . (int) $user->id . '/' . $clean_file;

				if ( file_exists( $_CB_framework->getCfg( 'absolute_path' ) . $file_icon ) ) {
					$icon		=	'<img src="' . $live_site . $file_icon . '" alt="' . htmlspecialchars( $clean_file ) . '" />';
				} else {
					$icon		=	'<img src="' . $live_site . '/components/com_comprofiler/plugin/user/plug_cbfilefield/images/none.png" alt="' . htmlspecialchars( $clean_file ) . '" />';
				}
			}

			if ( $oValue ) {
				cbimport( 'language.cbteamplugins' );

				$oValue			=	'index.php?option=com_comprofiler&task=fieldclass&field=' . urlencode( $field->name ) . '&function=download&user=' . (int) $user->id . '&reason=' . $reason;
				if ( $_CB_framework->getUi() == 2 ) {
					$oValue		=	$_CB_framework->backendUrl( $oValue, true );
				} else {
					$oValue		=	cbSef( $oValue, true );
				}
				$oValue			=	( ( $reason == 'edit' ) ? '<p>' : null ) . ( ( $field->params->get( 'fieldFile_icons', 0 ) == 0 ) ? $icon : null ) . ' <a href="' . $oValue . '" title="' . CBTxt::T( 'Click or right-click filename to download' ) . '">' . $file_name . '</a>' . ( ( $reason == 'edit' ) ? '</p>' : null );
			}
		}

		return $oValue;
	}

	/**
	 *
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  string                $reason      'profile' for user profile view, 'edit' for profile edit, 'register' for registration, 'list' for user-lists
	 * @param  boolean               $displayFieldIcons
	 * @return string                            HTML: <tag type="$type" value="$value" xxxx="xxx" yy="y" />
	 */
	function _htmlEditForm( &$field, &$user, $reason, $displayFieldIcons = true ) {
		global $_CB_framework, $ueConfig;

		if ( ( $field->name == 'avatar' ) && ! ( $ueConfig['allowAvatarUpload'] || $ueConfig['allowAvatarGallery'] ) ) {
			return null;
		}

		cbimport( 'language.cbteamplugins' );

		$value							=	$user->get( $field->name );
		$required						=	$this->_isRequired( $field, $user, $reason );
		$upload_size_limit_max			=	$field->params->get( 'fieldValidateFile_sizeMax', 1024 );
		$upload_size_limit_min			=	$field->params->get( 'fieldValidateFile_sizeMin', 0 );
		$upload_ext_limit				=	$this->allowedExtensions( $field->params->get( 'fieldValidateFile_types', 'zip,rar,doc,pdf,txt,xls' ) );
		$noFile							=	CBTxt::T( 'No file' );
		$noChange						=	CBTxt::T( 'No change of file' );
		$newFile						=	CBTxt::T( 'Upload new file' );
		$uploadFile						=	CBTxt::T( 'Upload file' );
		$removeFile						=	CBTxt::T( 'Remove file' );
		$selectFile						=	CBTxt::T( 'Select file' );
		$fileRestrictions				=	CBTxt::Th( 'Your file must be of [ext] type and should exceed [min] KB, but not [max] KB' );
		$TOC							=	CBTxt::Th( 'Terms and Conditions' );
		$terms							=	CBTxt::Th( 'By uploading, you certify that you have the right to distribute this file and that it does not violate the [toc].' );
		$disclaimer						=	CBTxt::Th( 'By uploading, you certify that you have the right to distribute this file.' );
		$restriction_strings			=	array( '[max]', '[min]', '[ext]' );
		$restriction_values				=	array( $upload_size_limit_max, $upload_size_limit_min, implode( ',', $upload_ext_limit ) );
		$restrictions					=	str_replace( $restriction_strings, $restriction_values, $fileRestrictions );
		$disclaimer_terms				=	str_replace( '[toc]', '<a href="' . cbSef( htmlspecialchars( $ueConfig['reg_toc_url'] ) ) . '" target="_BLANK">' . $TOC . '</a>', $terms );
		$existingFile					=	( $user->id ? ( ( $value != null ) ? true : false ) : false );
		$return							=	'<div>';
		$choices						=	array();

		if ( ( $reason == 'register' ) || ( ( $reason == 'edit' ) && ( $user->id == 0 ) ) ) {
			if ( $required == 0 ) {
				$choices[]				=	moscomprofilerHTML::makeOption( '', $noFile );
			}
		} else {
			if ( $existingFile || ( $required == 0 ) ) {
				$choices[]				=	moscomprofilerHTML::makeOption( '', $noChange );
			}
		}

		$choices[]						=	moscomprofilerHTML::makeOption( 'upload', ( $existingFile ? $newFile : $uploadFile ) );

		if ( $existingFile && ( $required == 0 ) ) {
			$choices[]					=	moscomprofilerHTML::makeOption( 'delete', $removeFile );
		}

		$return							.=	'<div>';

		if ( ( $reason != 'register' ) && ( $user->id != 0 ) ) {
			$return						.=	$this->_fileLivePath( $field, $user, $reason ) . ' ';
		}

		if ( count( $choices ) > 1 ) {
			static $functOut			=	false;

			$additional					=	' class="inputbox"';

			if ( ( $_CB_framework->getUi() == 1 ) && ( $reason == 'edit' ) && $field->readonly ) {
				$additional				.=	' disabled="disabled"';
			}

			$return						.=	moscomprofilerHTML::selectList( $choices, $field->name . '__choice', $additional, 'value', 'text', null, $required );

			if ( ! $functOut ) {
				$js						=	"function cbslideFile(choice,uplodid) {"
										.		"if ( ( choice == '' ) || ( choice == 'delete' ) ) {"
										.			"$(uplodid).slideUp('slow');"
										.		"} else if ( choice == 'upload' ) {"
										.			"$(uplodid).slideDown('slow');"
										.		"}"
										.	"}"
										;
				$_CB_framework->outputCbJQuery( $js );

				$functOut				=	true;
			}

			$js							=	"$('#cbfile_upload_" . $field->name . "').hide();"
										.	"\n	{"
										.	"\n	  $('#" . $field->name . "__choice').click( function() {"
										.	"\n		cbslideFile( $(this).val(), '#cbfile_upload_" . $field->name . "');"
										.	"\n	  } ).click();"
										.	"\n	  $('#" . $field->name . "__choice').change( function() {"
										.	"\n		cbslideFile( $(this).val(), '#cbfile_upload_" . $field->name . "');"
										.	"\n	  } );"
										.	"\n	}"
										;
			$_CB_framework->outputCbJQuery( $js );
		} else {
			$return						.=	'<input type="hidden" name="' . $field->name . '__choice" value="' . $choices[0]->value . '" />';
		}

		$return							.=	$this->_fieldIconsHtml( $field, $user, 'htmledit', $reason, 'select', '', null, '', array(), $displayFieldIcons, $required )
										.	'</div>'
										.	'<div id="cbfile_upload_' . $field->name . '">'
										.		'<p>' . $restrictions . '</p>'
										.		'<div>' . $selectFile . ' '
										.			'<input type="file" name="' . $field->name . '__file" value="" class="inputbox" />'
										.		'</div>'
										.		'<p>' . ( $ueConfig['reg_enable_toc'] ? $disclaimer_terms : $disclaimer ) . '</p>'
										.	'</div>'
										.	'</div>';

		return $return;
	}

	/**
	 * Uploads files from remembered array
	 *
	 * @param object $user
	 */
	function sendNotifications( $user ) {
		global $_CB_framework;

		$files							=	CBfield_file_rememberFileToUpload( true );

		if ( $files ) {
			static $notify				=	false;

			$emailSubject				=	$this->params->get( 'fieldFile_notify_subject', '[username] uploaded a file' );
			$emailBody					=	$this->params->get( 'fieldFile_notify_body', '[username] ([user_id]) has uploaded [file] to [field].' );
			$body						=	array();
			$attachment					=	array();

			foreach ( $files as $file ) {
				$field					=	$file->field;
				$upload_notify			=	(int) $field->params->get( 'fieldFile_notify', 0 );
				$upload_notify_atch		=	(int) $field->params->get( 'fieldFile_notifyatch', 1 );

				//Build notification e-mail
				if ( $upload_notify == 0 ) {
					$body[]				=	str_replace( array( '[file]', '[field]' ), array( $file->name, $field->name ), $emailBody );

					if ( $upload_notify_atch == 0 ) {
						$attachment[]	=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/plug_cbfilefield/' . (int) $user->id. '/' . preg_replace( '/[^-a-zA-Z0-9_.]/', '', $file->name );
					}

					$notify				=	true;
				}
			}

			//Send notification e-mail
			if ( $notify ) {
				$email_Body				=	null;
				$email_Attachment		=	null;

				if ( $body && ( count( $body ) > 1 ) ) {
					$email_Body			=	implode( "\n", $body );
				} elseif ( $body ) {
					$email_Body			=	$body[0];
				}

				if ( $attachment ) {
					$email_Attachment	=	$attachment;
				}

				$this->notifcationMail( $user, $emailSubject, $email_Body, $email_Attachment );

				CBfield_file_rememberFileToUpload( 'clear' );
			}
		}
	}

	/**
	 * Sends notification to moderators
	 *
	 * @param  moscomprofilerUser  $user
	 * @param  string              $mailSubject
	 * @param  string              $mailBody
	 * @param  string              $mailAttachment
	 */
	function notifcationMail( $user, $mailSubject, $mailBody, $mailAttachments ) {
		global $_CB_database, $_CB_framework, $ueConfig;

		if ( ! $user ) {
			return;
		}

		$cbUser					=&	CBuser::getInstance( $user->id );

		if ( ! $cbUser ) {
			return;
		}

		$mailFrom_name			=	trim( $this->params->get( 'fieldFile_notify_fromname', null ) );
		$mailFrom_email			=	trim( $this->params->get( 'fieldFile_notify_from', null ) );
		$mailTo					=	trim( $cbUser->replaceUserVars( $this->params->get( 'fieldFile_notify_to', null ), false, false, array(), false ) );
		$mailSubject			=	trim( $cbUser->replaceUserVars( $mailSubject, false, false, array(), false ) );
		$mailBody				=	trim( $cbUser->replaceUserVars( $mailBody, false, false, array(), false ) );

		if ( $mailTo ) {
			$mailTo				=	preg_split( ' *, *', $mailTo );
		} else {
			$mailTo				=	null;
		}

		if ( $mailSubject || $mailBody  ) {
			$mailBody			.=	"\n\n" . sprintf( _UE_EMAILFOOTER, cb_html_entity_decode_all( $_CB_framework->getCfg( 'sitename' ) ), $_CB_framework->getCfg( 'live_site' ) );

			if ( $mailTo ) {
				comprofilerMail( $mailFrom_email, $mailFrom_name, $mailTo, $mailSubject, $mailBody, 0, null, null, $mailAttachments );
			} else {
				$moderators		=	implode( ',', getParentGIDS( $ueConfig['imageApproverGid'] ) );

				if ( $moderators ) {
					$query		=	'SELECT u.' . $_CB_database->NameQuote( 'email' )
								.	"\n FROM " . $_CB_database->NameQuote( '#__users' ) . " AS u"
								.	"\n INNER JOIN " . $_CB_database->NameQuote( '#__comprofiler' ) . " AS c"
								.	' ON u.' . $_CB_database->NameQuote( 'id' ) . ' = c.' . $_CB_database->NameQuote( 'id' );

					if ( checkJversion() == 2 ) {
						$query	.=	"\n INNER JOIN " . $_CB_database->NameQuote( '#__user_usergroup_map' ) . " AS g"
								.	' ON u.' . $_CB_database->NameQuote( 'id' ) . ' = g.' . $_CB_database->NameQuote( 'user_id' )
								.	"\n WHERE g." . $_CB_database->NameQuote( 'group_id' ) . " IN ( $moderators )";
					} else {
						$query	.=	"\n WHERE u." . $_CB_database->NameQuote( 'gid' ) . " IN ( $moderators )";
					}

					$query		.=	"\n AND u." . $_CB_database->NameQuote( 'block' ) . " = " . $_CB_database->Quote( '0' )
								.	"\n AND c." . $_CB_database->NameQuote( 'confirmed' ) . " = " . $_CB_database->Quote( '1' )
								.	"\n AND c." . $_CB_database->NameQuote( 'approved' ) . " = " . $_CB_database->Quote( '1' )
								.	"\n AND u." . $_CB_database->NameQuote( 'sendEmail' ) . " = " . $_CB_database->Quote( '1' );
					$_CB_database->setQuery( $query );
					$mods		=	$_CB_database->loadObjectList();

					if ( ! $_CB_database->getErrorNum() ) {
						if ( $mods ) foreach ( $mods AS $mod ) {
							comprofilerMail( $mailFrom_email, $mailFrom_name, $mod->email, $mailSubject, $mailBody, 0, null, null, $mailAttachments );
						}
					}
				}
			}
		}
	}

	/**
	 * Deletes file from users folder
	 *
	 * @param  moscomprofilerUser  $user
	 * @param  string              $file
	 */
	function deleteFiles( $user, $file = null ) {
		global $_CB_framework;

		if ( ! is_object( $user ) ) {
			return;
		}

		$file_path	=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/plug_cbfilefield/' . (int) $user->id . '/';

		if ( ! is_dir( $file_path ) ) {
			return;
		}

		if ( ! $file ) {
			if ( false !== ( $handle = opendir( $file_path ) ) ) {
				while ( false !== ( $file = readdir( $handle ) ) ) {
					if ( $file && ( ( $file != '.' ) && ( $file != '..' ) ) ) {
						@unlink( $file_path . $file );
					}
				}
				closedir( $handle );
			}

			if ( is_dir( $file_path ) ) {
				@rmdir( $file_path );
			}
		} else {
			if ( file_exists( $file_path . $file ) ) {
				@unlink( $file_path . $file );
			}
		}
	}

	/**
	 * return array map of extension to mime
	 *
	 * @return array
	 */
	public function getMimeMap() {
		$mimemap	=	array(	'3ds' => 'image/x-3ds',
								'BLEND' => 'application/x-blender',
								'C' => 'text/x-c++src',
								'CSSL' => 'text/css',
								'NSV' => 'video/x-nsv',
								'XM' => 'audio/x-mod',
								'Z' => 'application/x-compress',
								'a' => 'application/x-archive',
								'abw' => 'application/x-abiword',
								'abw.gz' => 'application/x-abiword',
								'ac3' => 'audio/ac3',
								'adb' => 'text/x-adasrc',
								'ads' => 'text/x-adasrc',
								'afm' => 'application/x-font-afm',
								'ag' => 'image/x-applix-graphics',
								'ai' => 'application/illustrator',
								'aif' => 'audio/x-aiff',
								'aifc' => 'audio/x-aiff',
								'aiff' => 'audio/x-aiff',
								'al' => 'application/x-perl',
								'arj' => 'application/x-arj',
								'as' => 'application/x-applix-spreadsheet',
								'asc' => 'text/plain',
								'asf' => 'video/x-ms-asf',
								'asp' => 'application/x-asp',
								'asx' => 'video/x-ms-asf',
								'au' => 'audio/basic',
								'avi' => 'video/x-msvideo',
								'aw' => 'application/x-applix-word',
								'bak' => 'application/x-trash',
								'bcpio' => 'application/x-bcpio',
								'bdf' => 'application/x-font-bdf',
								'bib' => 'text/x-bibtex',
								'bin' => 'application/octet-stream',
								'blend' => 'application/x-blender',
								'blender' => 'application/x-blender',
								'bmp' => 'image/bmp',
								'bz' => 'application/x-bzip',
								'bz2' => 'application/x-bzip',
								'c' => 'text/x-csrc',
								'c++' => 'text/x-c++src',
								'cc' => 'text/x-c++src',
								'cdf' => 'application/x-netcdf',
								'cdr' => 'application/vnd.corel-draw',
								'cer' => 'application/x-x509-ca-cert',
								'cert' => 'application/x-x509-ca-cert',
								'cgi' => 'application/x-cgi',
								'cgm' => 'image/cgm',
								'chrt' => 'application/x-kchart',
								'class' => 'application/x-java',
								'cls' => 'text/x-tex',
								'cpio' => 'application/x-cpio',
								'cpio.gz' => 'application/x-cpio-compressed',
								'cpp' => 'text/x-c++src',
								'cpt' => 'application/mac-compactpro',
								'crt' => 'application/x-x509-ca-cert',
								'cs' => 'text/x-csharp',
								'csh' => 'application/x-shellscript',
								'css' => 'text/css',
								'csv' => 'text/x-comma-separated-values',
								'cur' => 'image/x-win-bitmap',
								'cxx' => 'text/x-c++src',
								'dat' => 'video/mpeg',
								'dbf' => 'application/x-dbase',
								'dc' => 'application/x-dc-rom',
								'dcl' => 'text/x-dcl',
								'dcm' => 'image/x-dcm',
								'dcr' => 'application/x-director',
								'deb' => 'application/x-deb',
								'der' => 'application/x-x509-ca-cert',
								'desktop' => 'application/x-desktop',
								'dia' => 'application/x-dia-diagram',
								'diff' => 'text/x-patch',
								'dir' => 'application/x-director',
								'djv' => 'image/vnd.djvu',
								'djvu' => 'image/vnd.djvu',
								'dll' => 'application/octet-stream',
								'dms' => 'application/octet-stream',
								'doc' => 'application/msword',
								'dsl' => 'text/x-dsl',
								'dtd' => 'text/x-dtd',
								'dvi' => 'application/x-dvi',
								'dwg' => 'image/vnd.dwg',
								'dxf' => 'image/vnd.dxf',
								'dxr' => 'application/x-director',
								'egon' => 'application/x-egon',
								'el' => 'text/x-emacs-lisp',
								'eps' => 'image/x-eps',
								'epsf' => 'image/x-eps',
								'epsi' => 'image/x-eps',
								'etheme' => 'application/x-e-theme',
								'etx' => 'text/x-setext',
								'exe' => 'application/x-executable',
								'ez' => 'application/andrew-inset',
								'f' => 'text/x-fortran',
								'fig' => 'image/x-xfig',
								'fits' => 'image/x-fits',
								'flac' => 'audio/x-flac',
								'flc' => 'video/x-flic',
								'fli' => 'video/x-flic',
								'flw' => 'application/x-kivio',
								'fo' => 'text/x-xslfo',
								'g3' => 'image/fax-g3',
								'gb' => 'application/x-gameboy-rom',
								'gcrd' => 'text/x-vcard',
								'gen' => 'application/x-genesis-rom',
								'gg' => 'application/x-sms-rom',
								'gif' => 'image/gif',
								'glade' => 'application/x-glade',
								'gmo' => 'application/x-gettext-translation',
								'gnc' => 'application/x-gnucash',
								'gnucash' => 'application/x-gnucash',
								'gnumeric' => 'application/x-gnumeric',
								'gra' => 'application/x-graphite',
								'gsf' => 'application/x-font-type1',
								'gtar' => 'application/x-gtar',
								'gz' => 'application/x-gzip',
								'h' => 'text/x-chdr',
								'h++' => 'text/x-chdr',
								'hdf' => 'application/x-hdf',
								'hh' => 'text/x-c++hdr',
								'hp' => 'text/x-chdr',
								'hpgl' => 'application/vnd.hp-hpgl',
								'hqx' => 'application/mac-binhex40',
								'hs' => 'text/x-haskell',
								'htm' => 'text/html',
								'html' => 'text/html',
								'icb' => 'image/x-icb',
								'ice' => 'x-conference/x-cooltalk',
								'ico' => 'image/x-ico',
								'ics' => 'text/calendar',
								'idl' => 'text/x-idl',
								'ief' => 'image/ief',
								'ifb' => 'text/calendar',
								'iff' => 'image/x-iff',
								'iges' => 'model/iges',
								'igs' => 'model/iges',
								'ilbm' => 'image/x-ilbm',
								'iso' => 'application/x-cd-image',
								'it' => 'audio/x-it',
								'jar' => 'application/x-jar',
								'java' => 'text/x-java',
								'jng' => 'image/x-jng',
								'jp2' => 'image/jpeg2000',
								'jpg' => 'image/jpeg',
								'jpe' => 'image/jpeg',
								'jpeg' => 'image/jpeg',
								'jpr' => 'application/x-jbuilder-project',
								'jpx' => 'application/x-jbuilder-project',
								'js' => 'application/x-javascript',
								'kar' => 'audio/midi',
								'karbon' => 'application/x-karbon',
								'kdelnk' => 'application/x-desktop',
								'kfo' => 'application/x-kformula',
								'kil' => 'application/x-killustrator',
								'kon' => 'application/x-kontour',
								'kpm' => 'application/x-kpovmodeler',
								'kpr' => 'application/x-kpresenter',
								'kpt' => 'application/x-kpresenter',
								'kra' => 'application/x-krita',
								'ksp' => 'application/x-kspread',
								'kud' => 'application/x-kugar',
								'kwd' => 'application/x-kword',
								'kwt' => 'application/x-kword',
								'la' => 'application/x-shared-library-la',
								'latex' => 'application/x-latex',
								'lha' => 'application/x-lha',
								'lhs' => 'text/x-literate-haskell',
								'lhz' => 'application/x-lhz',
								'log' => 'text/x-log',
								'ltx' => 'text/x-tex',
								'lwo' => 'image/x-lwo',
								'lwob' => 'image/x-lwo',
								'lws' => 'image/x-lws',
								'lyx' => 'application/x-lyx',
								'lzh' => 'application/x-lha',
								'lzo' => 'application/x-lzop',
								'm' => 'text/x-objcsrc',
								'm15' => 'audio/x-mod',
								'm3u' => 'audio/x-mpegurl',
								'man' => 'application/x-troff-man',
								'md' => 'application/x-genesis-rom',
								'me' => 'text/x-troff-me',
								'mesh' => 'model/mesh',
								'mgp' => 'application/x-magicpoint',
								'mid' => 'audio/midi',
								'midi' => 'audio/midi',
								'mif' => 'application/x-mif',
								'mkv' => 'application/x-matroska',
								'mm' => 'text/x-troff-mm',
								'mml' => 'text/mathml',
								'mng' => 'video/x-mng',
								'moc' => 'text/x-moc',
								'mod' => 'audio/x-mod',
								'moov' => 'video/quicktime',
								'mov' => 'video/quicktime',
								'movie' => 'video/x-sgi-movie',
								'mp2' => 'video/mpeg',
								'mp3' => 'audio/x-mp3',
								'mpe' => 'video/mpeg',
								'mpeg' => 'video/mpeg',
								'mpg' => 'video/mpeg',
								'mpga' => 'audio/mpeg',
								'ms' => 'text/x-troff-ms',
								'msh' => 'model/mesh',
								'msod' => 'image/x-msod',
								'msx' => 'application/x-msx-rom',
								'mtm' => 'audio/x-mod',
								'mxu' => 'video/vnd.mpegurl',
								'n64' => 'application/x-n64-rom',
								'nc' => 'application/x-netcdf',
								'nes' => 'application/x-nes-rom',
								'nsv' => 'video/x-nsv',
								'o' => 'application/x-object',
								'obj' => 'application/x-tgif',
								'oda' => 'application/oda',
								'odb' => 'application/vnd.oasis.opendocument.database',
								'odc' => 'application/vnd.oasis.opendocument.chart',
								'odf' => 'application/vnd.oasis.opendocument.formula',
								'odg' => 'application/vnd.oasis.opendocument.graphics',
								'odi' => 'application/vnd.oasis.opendocument.image',
								'odm' => 'application/vnd.oasis.opendocument.text-master',
								'odp' => 'application/vnd.oasis.opendocument.presentation',
								'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
								'odt' => 'application/vnd.oasis.opendocument.text',
								'ogg' => 'application/ogg',
								'old' => 'application/x-trash',
								'oleo' => 'application/x-oleo',
								'otg' => 'application/vnd.oasis.opendocument.graphics-template',
								'oth' => 'application/vnd.oasis.opendocument.text-web',
								'otp' => 'application/vnd.oasis.opendocument.presentation-template',
								'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
								'ott' => 'application/vnd.oasis.opendocument.text-template',
								'p' => 'text/x-pascal',
								'p12' => 'application/x-pkcs12',
								'p7s' => 'application/pkcs7-signature',
								'pas' => 'text/x-pascal',
								'patch' => 'text/x-patch',
								'pbm' => 'image/x-portable-bitmap',
								'pcd' => 'image/x-photo-cd',
								'pcf' => 'application/x-font-pcf',
								'pcf.Z' => 'application/x-font-type1',
								'pcl' => 'application/vnd.hp-pcl',
								'pdb' => 'application/vnd.palm',
								'pdf' => 'application/pdf',
								'pem' => 'application/x-x509-ca-cert',
								'perl' => 'application/x-perl',
								'pfa' => 'application/x-font-type1',
								'pfb' => 'application/x-font-type1',
								'pfx' => 'application/x-pkcs12',
								'pgm' => 'image/x-portable-graymap',
								'pgn' => 'application/x-chess-pgn',
								'pgp' => 'application/pgp',
								'php' => 'application/x-php',
								'php3' => 'application/x-php',
								'php4' => 'application/x-php',
								'pict' => 'image/x-pict',
								'pict1' => 'image/x-pict',
								'pict2' => 'image/x-pict',
								'pl' => 'application/x-perl',
								'pls' => 'audio/x-scpls',
								'pm' => 'application/x-perl',
								'png' => 'image/png',
								'pnm' => 'image/x-portable-anymap',
								'po' => 'text/x-gettext-translation',
								'pot' => 'application/vnd.ms-powerpoint',
								'ppm' => 'image/x-portable-pixmap',
								'pps' => 'application/vnd.ms-powerpoint',
								'ppt' => 'application/vnd.ms-powerpoint',
								'ppz' => 'application/vnd.ms-powerpoint',
								'ps' => 'application/postscript',
								'ps.gz' => 'application/x-gzpostscript',
								'psd' => 'image/x-psd',
								'psf' => 'application/x-font-linux-psf',
								'psid' => 'audio/prs.sid',
								'pw' => 'application/x-pw',
								'py' => 'application/x-python',
								'pyc' => 'application/x-python-bytecode',
								'pyo' => 'application/x-python-bytecode',
								'qif' => 'application/x-qw',
								'qt' => 'video/quicktime',
								'qtvr' => 'video/quicktime',
								'ra' => 'audio/x-pn-realaudio',
								'ram' => 'audio/x-pn-realaudio',
								'rar' => 'application/x-rar',
								'ras' => 'image/x-cmu-raster',
								'rdf' => 'text/rdf',
								'rej' => 'application/x-reject',
								'rgb' => 'image/x-rgb',
								'rle' => 'image/rle',
								'rm' => 'audio/x-pn-realaudio',
								'roff' => 'application/x-troff',
								'rpm' => 'application/x-rpm',
								'rss' => 'text/rss',
								'rtf' => 'application/rtf',
								'rtx' => 'text/richtext',
								's3m' => 'audio/x-s3m',
								'sam' => 'application/x-amipro',
								'scm' => 'text/x-scheme',
								'sda' => 'application/vnd.stardivision.draw',
								'sdc' => 'application/vnd.stardivision.calc',
								'sdd' => 'application/vnd.stardivision.impress',
								'sdp' => 'application/vnd.stardivision.impress',
								'sds' => 'application/vnd.stardivision.chart',
								'sdw' => 'application/vnd.stardivision.writer',
								'sgi' => 'image/x-sgi',
								'sgl' => 'application/vnd.stardivision.writer',
								'sgm' => 'text/sgml',
								'sgml' => 'text/sgml',
								'sh' => 'application/x-shellscript',
								'shar' => 'application/x-shar',
								'shtml' => 'text/html',
								'siag' => 'application/x-siag',
								'sid' => 'audio/prs.sid',
								'sik' => 'application/x-trash',
								'silo' => 'model/mesh',
								'sit' => 'application/x-stuffit',
								'skd' => 'application/x-koan',
								'skm' => 'application/x-koan',
								'skp' => 'application/x-koan',
								'skt' => 'application/x-koan',
								'slk' => 'text/spreadsheet',
								'smd' => 'application/vnd.stardivision.mail',
								'smf' => 'application/vnd.stardivision.math',
								'smi' => 'application/smil',
								'smil' => 'application/smil',
								'sml' => 'application/smil',
								'sms' => 'application/x-sms-rom',
								'snd' => 'audio/basic',
								'so' => 'application/x-sharedlib',
								'spd' => 'application/x-font-speedo',
								'spl' => 'application/x-futuresplash',
								'sql' => 'text/x-sql',
								'src' => 'application/x-wais-source',
								'stc' => 'application/vnd.sun.xml.calc.template',
								'std' => 'application/vnd.sun.xml.draw.template',
								'sti' => 'application/vnd.sun.xml.impress.template',
								'stm' => 'audio/x-stm',
								'stw' => 'application/vnd.sun.xml.writer.template',
								'sty' => 'text/x-tex',
								'sun' => 'image/x-sun-raster',
								'sv4cpio' => 'application/x-sv4cpio',
								'sv4crc' => 'application/x-sv4crc',
								'svg' => 'image/svg+xml',
								'swf' => 'application/x-shockwave-flash',
								'sxc' => 'application/vnd.sun.xml.calc',
								'sxd' => 'application/vnd.sun.xml.draw',
								'sxg' => 'application/vnd.sun.xml.writer.global',
								'sxi' => 'application/vnd.sun.xml.impress',
								'sxm' => 'application/vnd.sun.xml.math',
								'sxw' => 'application/vnd.sun.xml.writer',
								'sylk' => 'text/spreadsheet',
								't' => 'application/x-troff',
								'tar' => 'application/x-tar',
								'tar.Z' => 'application/x-tarz',
								'tar.bz' => 'application/x-bzip-compressed-tar',
								'tar.bz2' => 'application/x-bzip-compressed-tar',
								'tar.gz' => 'application/x-compressed-tar',
								'tar.lzo' => 'application/x-tzo',
								'tcl' => 'text/x-tcl',
								'tex' => 'text/x-tex',
								'texi' => 'text/x-texinfo',
								'texinfo' => 'text/x-texinfo',
								'tga' => 'image/x-tga',
								'tgz' => 'application/x-compressed-tar',
								'theme' => 'application/x-theme',
								'tif' => 'image/tiff',
								'tiff' => 'image/tiff',
								'tk' => 'text/x-tcl',
								'torrent' => 'application/x-bittorrent',
								'tr' => 'application/x-troff',
								'ts' => 'application/x-linguist',
								'tsv' => 'text/tab-separated-values',
								'ttf' => 'application/x-font-ttf',
								'txt' => 'text/plain',
								'tzo' => 'application/x-tzo',
								'ui' => 'application/x-designer',
								'uil' => 'text/x-uil',
								'ult' => 'audio/x-mod',
								'uni' => 'audio/x-mod',
								'uri' => 'text/x-uri',
								'url' => 'text/x-uri',
								'ustar' => 'application/x-ustar',
								'vcd' => 'application/x-cdlink',
								'vcf' => 'text/x-vcalendar',
								'vcs' => 'text/x-vcalendar',
								'vct' => 'text/x-vcard',
								'vfb' => 'text/calendar',
								'vob' => 'video/mpeg',
								'voc' => 'audio/x-voc',
								'vor' => 'application/vnd.stardivision.writer',
								'vrml' => 'model/vrml',
								'vsd' => 'application/vnd.visio',
								'wav' => 'audio/x-wav',
								'wax' => 'audio/x-ms-wax',
								'wb1' => 'application/x-quattropro',
								'wb2' => 'application/x-quattropro',
								'wb3' => 'application/x-quattropro',
								'wbmp' => 'image/vnd.wap.wbmp',
								'wbxml' => 'application/vnd.wap.wbxml',
								'wk1' => 'application/vnd.lotus-1-2-3',
								'wk3' => 'application/vnd.lotus-1-2-3',
								'wk4' => 'application/vnd.lotus-1-2-3',
								'wks' => 'application/vnd.lotus-1-2-3',
								'wm' => 'video/x-ms-wm',
								'wma' => 'audio/x-ms-wma',
								'wmd' => 'application/x-ms-wmd',
								'wmf' => 'image/x-wmf',
								'wml' => 'text/vnd.wap.wml',
								'wmlc' => 'application/vnd.wap.wmlc',
								'wmls' => 'text/vnd.wap.wmlscript',
								'wmlsc' => 'application/vnd.wap.wmlscriptc',
								'wmv' => 'video/x-ms-wmv',
								'wmx' => 'video/x-ms-wmx',
								'wmz' => 'application/x-ms-wmz',
								'wpd' => 'application/wordperfect',
								'wpg' => 'application/x-wpg',
								'wri' => 'application/x-mswrite',
								'wrl' => 'model/vrml',
								'wvx' => 'video/x-ms-wvx',
								'xac' => 'application/x-gnucash',
								'xbel' => 'application/x-xbel',
								'xbm' => 'image/x-xbitmap',
								'xcf' => 'image/x-xcf',
								'xcf.bz2' => 'image/x-compressed-xcf',
								'xcf.gz' => 'image/x-compressed-xcf',
								'xht' => 'application/xhtml+xml',
								'xhtml' => 'application/xhtml+xml',
								'xi' => 'audio/x-xi',
								'xls' => 'application/vnd.ms-excel',
								'xla' => 'application/vnd.ms-excel',
								'xlc' => 'application/vnd.ms-excel',
								'xld' => 'application/vnd.ms-excel',
								'xll' => 'application/vnd.ms-excel',
								'xlm' => 'application/vnd.ms-excel',
								'xlt' => 'application/vnd.ms-excel',
								'xlw' => 'application/vnd.ms-excel',
								'xm' => 'audio/x-xm',
								'xml' => 'text/xml',
								'xpm' => 'image/x-xpixmap',
								'xsl' => 'text/x-xslt',
								'xslfo' => 'text/x-xslfo',
								'xslt' => 'text/x-xslt',
								'xwd' => 'image/x-xwindowdump',
								'xyz' => 'chemical/x-xyz',
								'zabw' => 'application/x-abiword',
								'zip' => 'application/zip',
								'zoo' => 'application/x-zoo',
								'123' => 'application/vnd.lotus-1-2-3',
								'669' => 'audio/x-mod'
							);

		return $mimemap;
	}
}

class CBField_ajaxfile extends CBfield_file {

	/**
	 * Returns a field in specified format
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  string                $output  'html', 'xml', 'json', 'php', 'csvheader', 'csv', 'rss', 'fieldslist', 'htmledit'
	 * @param  string                $reason  'profile' for user profile view, 'edit' for profile edit, 'register' for registration, 'list' for user-lists
	 * @param  int                   $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @return mixed
	 */
	function fieldClass( &$field, &$user, &$postdata, $reason ) {
		global $_CB_framework;

		$function						=	cbGetParam( $_GET, 'function', null );

		if ( ( $function == 'savevalue' ) && $this->ajaxEditAuthorized( $user ) ) {
			parent::fieldClass( $field, $user, $postdata, $reason );

			$result						=	null;
			$validated					=	null;

			foreach ( $field->getTableColumns() as $col ) {
				$value					=	stripslashes( cbGetParam( $postdata, $col ) );
				$validated				=	$this->validate( $field, $user, $col, $value, $postdata, $reason );

				if ( $validated ) {
					foreach ( $postdata as $i => $value ) {
					   $postdata[$i]	=	utf8ToISO( $value );
					}

					$this->prepareFieldDataSave( $field, $user, $postdata, $reason );
					$user->store();
				}

				$result					=	parent::getField( $field, $user, 'html', $reason, 0 );
			}

			cbimport( 'language.cbteamplugins' );

			$result						=	( $result != '' ? ISOtoUtf8( $result ) : CBTxt::Th( $field->params->get( 'cbAjplaceholdertext', 'Click to edit' ) ) );
			$error						=	$this->getFieldAjaxError( $field, $user, $reason );

			if ( $error ) {
				$result					=	'<div>' . $result . '</div>'
										.	'<div class="cb_result_error">' . $error . '</div>';
			}

			return $result;
		} elseif ( $function == 'download' ) {
			$col						=	$field->name;
			$file						=	$user->$col;

			if ( $file != null ) {
				if ( $reason == 'edit' ) {
					$redirect_url		=	$_CB_framework->userProfileEditUrl( $user->id, false );
				} elseif ( $reason == 'list' ) {
					$redirect_url		=	$_CB_framework->userProfilesListUrl( cbGetParam( $_REQUEST, 'listid', 0 ), false );
				} elseif ( $reason == 'register' ) {
					$redirect_url		=	$_CB_framework->viewUrl( 'registers', false );
				} else {
					$redirect_url		=	$_CB_framework->userProfileUrl( $user->id, false );
				}

				$clean_file				=	preg_replace( '/[^-a-zA-Z0-9_.]/', '', $file );
				$file_path				=	$_CB_framework->getCfg( 'absolute_path' ) . '/images/comprofiler/plug_cbfilefield/' . (int) $user->id . '/' . $clean_file;

				if ( ! file_exists( $file_path ) ) {
					cbRedirect( $redirect_url, CBTxt::T( 'File failed to download! Error: File not found' ), 'error' );
					exit();
				}

				$file_ext				=	strtolower( pathinfo( $clean_file, PATHINFO_EXTENSION ) );

				if ( ! $file_ext ) {
					cbRedirect( $redirect_url, CBTxt::T( 'File failed to download! Error: Unknown extension' ), 'error' );
					exit();
				}

				$file_name				=	substr( rtrim( pathinfo( $clean_file, PATHINFO_BASENAME ), '.' . $file_ext ), 0, -14 ) . '.' . $file_ext;

				if ( ! $file_name ) {
					cbRedirect( $redirect_url, CBTxt::T( 'File failed to download! Error: File not found' ), 'error' );
					exit();
				}

				$mimemap				=	$this->getMimeMap();
				$file_mime				=	( array_key_exists( $file_ext, $mimemap ) ? $mimemap[$file_ext] : 'x-extension/' . $file_ext );

				if ( ! $file_mime ) {
					cbRedirect( $redirect_url, CBTxt::T( 'File failed to download! Error: Unknown MIME' ), 'error' );
					exit();
				}

				$file_size				=	@filesize( $file_path );
				$file_modified			=	date( 'r', filemtime( $file_path ) );

				while ( @ob_end_clean() );

				if ( ini_get( 'zlib.output_compression' ) ) {
					ini_set( 'zlib.output_compression', 'Off' );
				}

				if ( function_exists( 'apache_setenv' ) ) {
					apache_setenv( 'no-gzip', '1' );
				}

				header( "Content-Type: $file_mime" );
				header( 'Content-Disposition: attachment; filename="' . $file_name . '"; modification-date="' . $file_modified . '"; size=' . $file_size .';' );
				header( "Content-Transfer-Encoding: binary" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Pragma: public" );
				header( "Content-Length: $file_size" );

				if ( ! ini_get( 'safe_mode' ) ) {
					@set_time_limit( 0 );
				}

				$handle			=	fopen( $file_path, 'rb' );

				if ( $handle === false ) {
					exit();
				}

				$chunksize		=	( 1 * ( 1024 * 1024 ) );
				$buffer			=	'';

				while ( ! feof( $handle ) ) {
					$buffer		=	fread( $handle, $chunksize );
					echo $buffer;
					@ob_flush();
					flush();
				}

				fclose( $handle );
				exit();
			}
		} else {
			parent::fieldClass( $field, $user, $postdata, $reason );
		}

		return null;
	}

	/**
	 * Returns a field in specified format
	 *
	 * @param  moscomprofilerFields  $field
	 * @param  moscomprofilerUser    $user
	 * @param  string                $output  'html', 'xml', 'json', 'php', 'csvheader', 'csv', 'rss', 'fieldslist', 'htmledit'
	 * @param  string                $reason  'profile' for user profile view, 'edit' for profile edit, 'register' for registration, 'list' for user-lists
	 * @param  int                   $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @return mixed
	 */
	function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		$value				=	parent::getField( $field, $user, $output, $reason, $list_compare_types );

		if ( $field->params->get( 'fieldAjax', 0 ) && $this->ajaxEditAuthorized( $user ) && ( ( $output == 'html' ) && ( $reason == 'profile' ) && ( ! $field->readonly ) ) ) {
			$return			=	$this->getAjaxInput( $field, $user, $value, $reason, parent::getField( $field, $user, 'htmledit', 'edit', $list_compare_types ) );
		} else {
			$return			=	$value;
		}

		return $return;
	}

	/**
	 * parses current errors to find error specific to the field then removes its title for ajax display
	 *
	 * @param moscomprofilerFields $field
	 * @param moscomprofilerUser $user
	 * @param string $reason
	 * @return mixed
	 */
	function getFieldAjaxError( &$field, &$user, $reason  ) {
		global $_PLUGINS;

		$errors	=	$_PLUGINS->getErrorMSG( false );
		$title	=	$this->getFieldTitle( $field, $user, 'text', $reason );

		if ( $errors ) foreach ( $errors as $error ) {
			if ( stristr( $error, $title ) ) {
				return str_replace( $title . ' : ', '', $error );
			}
		}

		return null;
	}

	/**
	 * Checks if ajax edit is authorized for that user
	 *
	 * @param  moscomprofilerUser  $user
	 * @return boolean
	 */
	function ajaxEditAuthorized( &$user ) {
		if ( ! cbCheckIfUserCanPerformUserTask( $user->id, 'allowModeratorsUserEdit' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Internal method to add ajax functionality to the field:
	 *
	 * @param  moscomprofilerField  $field
	 * @param  moscomprofilerUser   $user
	 * @param  string               $value
	 * @param  string               $reason
	 * @return string
	 */
	function getAjaxInput( $field, $user, $value, $reason, $fieldFormat ) {
		global $_CB_framework;

		$live_site	=	$_CB_framework->getCfg( 'live_site' );

		/* Field Parameters */
		$submit		=	CBTxt::T( $field->params->get( 'cbAjsubmittext', 'Save' ) );
		$reset		=	CBTxt::T( $field->params->get( 'cbAjresettext', 'Cancel' ) );

		$cbUser		=&	CBuser::getInstance( $user->id );

		if ( ! $cbUser ) {
			$cbUser	=&	CBuser::getInstance( null );
		}

		/* Build HTML */
		if ( $this->ajaxEditAuthorized( $user ) ) {
			$_CB_framework->document->addHeadStyleSheet( $live_site . '/components/com_comprofiler/plugin/user/plug_cbfilefield/jquery.cbfield.css' );

			$html	=	'<div class="cbajContainer">'
					.		'<div style="display:none;" class="cbajAjaxForm">'
					.			'<form name="' . htmlspecialchars( $field->name . (int) $user->id ) . '" action="' . cbSef( 'index.php?option=com_comprofiler&task=fieldclass&field=' . urlencode( $field->name ) . '&function=savevalue&user=' . (int) $user->id . '&reason=profile', true, 'raw' ) .'" enctype="multipart/form-data" method="post">'
					.				$fieldFormat
					.				cbGetSpoofInputTag( 'fieldclass' )
					.				cbGetRegAntiSpamInputTag()
					.				' <input type="submit" class="button cbajButSubmit" value="' . htmlspecialchars( $submit ) . '"' . ( $submit ? null : ' style="display:none;"' ) . ' />'
					.				( $reset ? ' <input type="button" class="button cbajButCancel" value="' . htmlspecialchars( $reset ) . '" />' : null )
					.			'</form>'
					.		'</div>'
					.		'<div class="cbajValue" title="' . htmlspecialchars( CBTxt::T( $field->params->get( 'cbajtooltiptext', 'Click to edit...' ) ) ) . '">'
					.			( $value != '' ? $value : CBTxt::Th( $field->params->get( 'cbAjplaceholdertext', 'Click to edit' ) ) )
					.		'</div>'
					.		'<div style="display:none;" class="cbajLoading">&nbsp;</div>' // &nbsp; needed so basealign works for the title in FF
					.	'</div>';

			$_CB_framework->addJQueryPlugin( 'cbajField', '/components/com_comprofiler/plugin/user/plug_cbfilefield/jquery.cbfield.js' );
			$_CB_framework->outputCbJQuery( null, array( 'cbajField', 'form' ) );
		} else {
			$html	=	$value;
		}

		return $html;
	}
}
?>