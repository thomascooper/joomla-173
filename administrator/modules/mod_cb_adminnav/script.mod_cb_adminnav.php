<?php
class mod_cb_adminnavInstallerScript {

	public function preflight( $type, $parent ) {
		$element	=	$parent->get( 'element' );
		$installer	=	$parent->getParent();
		$adminPath	=	$installer->getPath( 'source' );

		if ( JFile::exists( $adminPath . '/' . $element . '.j16.xml' ) ) {
			if ( JFile::exists( $adminPath . '/' . $element . '.xml' ) ) {
				JFile::delete( $adminPath . '/' . $element . '.xml' );
			}

			JFile::move( $adminPath . '/' . $element . '.j16.xml', $adminPath . '/' . $element . '.xml' );
			$installer->setPath( 'manifest', $adminPath . '/' . $element . '.xml' );
		}
	}
}
?>