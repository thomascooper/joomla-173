<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

jimport( 'joomla.html.html' );
jimport( 'joomla.form.formfield' );

class JFormFieldCBfields extends JFormField {
	protected $type = 'cbfields';

	protected function getInput() {
		$options		=	$this->getOptions();
		$attributes		=	null;
		$attributes		.=	( $this->element['class'] ? ' class="' . htmlspecialchars( $this->element['class'] ) . '"' : null );

		if ( ( $this->element['readonly'] == 'true' ) || ( $this->element['disabled'] == 'true' ) ) {
			$attributes	.=	' disabled="disabled"';
		}

		$attributes		.=	( $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : null );
		$attributes		.=	( $this->multiple ? ' multiple="multiple"' : null );
		$attributes		.=	( $this->element['onchange'] ? ' onchange="' . htmlspecialchars( $this->element['onchange'] ) . '"' : null );

		if ( ! is_array( $this->value ) ) {
			$selected	=	explode( '|*|', $this->value );
		} else {
			$selected	=	$this->value;
		}

		if ( $this->element['readonly'] == 'true' ){
			$return		=	JHtml::_( 'select.genericlist', $options, '', trim( $attributes ), 'value', 'text', $selected, $this->id )
						. '<input type="hidden" name="' . htmlspecialchars( $this->name ) . '" value="' . htmlspecialchars( ( is_array( $this->value ) ? implode( '|*|', $this->value ) : $this->value ) ) . '"/>';
		} else {
			$return		=	JHtml::_( 'select.genericlist', $options, $this->name, trim( $attributes ), 'value', 'text', $selected, $this->id );
		}

		return $return;
	}

	protected function getOptions() {
		global $_CB_database, $mainframe;

		static $CB_loaded		=	0;

		if ( ! $CB_loaded++ ) {
			if ( defined( 'JPATH_ADMINISTRATOR' ) ) {
				if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) {
					return;
				}

				include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );
			} else {
				if ( ! file_exists( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' ) ) {
					return;
				}

				include_once( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' );
			}

			cbimport( 'cb.html' );
			cbimport( 'language.front' );
		}

		$options				=	array();

		$query					=	'SELECT f.*'
								. 	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_fields' ) . " AS f"
								.	"\n LEFT JOIN " . $_CB_database->NameQuote( '#__comprofiler_tabs' ) . " AS t"
								.	' ON t.' . $_CB_database->NameQuote( 'tabid' ) . ' = f.' . $_CB_database->NameQuote( 'tabid' )
								.	"\n WHERE f." . $_CB_database->NameQuote( 'published' ) . " = 1"
								.	"\n AND f." . $_CB_database->NameQuote( 'name' ) . " != " . $_CB_database->Quote( 'NA' )
								.	"\n ORDER BY t." . $_CB_database->NameQuote( 'ordering' ) . ", f." . $_CB_database->NameQuote( 'ordering' );
		$_CB_database->setQuery( $query );
		$fields					=	$_CB_database->loadObjectList( null, 'moscomprofilerFields', array( &$_CB_database ) );

		if ( $fields ) foreach ( $fields as $field ) {
			if ( count( $field->getTableColumns() ) ) {
				$options[]		=	JHtml::_( 'select.option', $field->fieldid, CBTxt::T( getLangDefinition( $field->title ) ) );
			}
		}

		return $options;
	}
}
?>