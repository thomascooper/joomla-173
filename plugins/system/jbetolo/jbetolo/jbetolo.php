<?php
/**
* @version:	2.5.0.44e92e4 - 2013 April 08 08:34:00 +0300
* @package:	jbetolo
* @subpackage:	jbetolo
* @copyright:	Copyright (C) 2010 - 2013 jproven.com. All rights reserved. 
* @license:	GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
*/

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for serving based on content fed by jbetolo
 */
class Minify_Controller_jBetolo extends Minify_Controller_Base {
        public function setupSources($options) {
                $sourceSpec = array(
                    'content' => $options['content']
                    , 'id' => $options['id']
                );
                
                unset($options['content'], $options['id']);

                if (isset($options['minifyAll'])) {
                        // this will be the 2nd argument passed to Minify_HTML::minify()
                        $sourceSpec['minifyOptions'] = array(
                            'cssMinifier' => array('Minify_CSS', 'minify')
                            , 'jsMinifier' => array('JSMin', 'minify')
                        );
                        $this->_loadCssJsMinifiers = true;
                        unset($options['minifyAll']);
                }
                
                $this->sources[] = new Minify_Source($sourceSpec);
                
                return $options;
        }

        protected $_loadCssJsMinifiers = false;

        /**
         * @see Minify_Controller_Base::loadMinifier()
         */
        public function loadMinifier($minifierCallback) {
                if ($this->_loadCssJsMinifiers) {
                        // Minify will not call for these so we must manually load
                        // them when Minify/HTML.php is called for.
                        require_once 'Minify/CSS.php';
                        require_once 'JSMin.php';
                }
                parent::loadMinifier($minifierCallback); // load Minify/HTML.php
        }

}

