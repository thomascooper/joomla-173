<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

jimport( 'joomla.html.html' );
jimport( 'joomla.form.formfield' );

class JFormFieldgjcategories extends JFormField {
	protected $type = 'gjcategories';

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
		global $_CB_framework, $mainframe;

		static $CB_loaded	=	0;

		if ( ! $CB_loaded++ ) {
			if ( defined( 'JPATH_ADMINISTRATOR' ) ) {
				if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) {
					return array();
				}

				include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );
			} else {
				if ( ! file_exists( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' ) ) {
					return array();
				}

				include_once( $mainframe->getCfg( 'absolute_path' ) . '/administrator/components/com_comprofiler/plugin.foundation.php' );
			}

			if ( ! file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' ) ) {
				return array();
			}

			cbimport( 'cb.html' );
			cbimport( 'language.front' );

			require_once( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/user/plug_cbgroupjive/cbgroupjive.class.php' );
		}

		$options			=	cbgjData::listArray( cbgjData::getCategories() );

		array_unshift( $options, JHtml::_( 'select.option', null, CBTxt::T( '- Select Categories -' ) ) );

		return $options;
	}
}
?>