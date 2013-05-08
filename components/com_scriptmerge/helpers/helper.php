<?php
/**
 * Joomla! System plugin - ScriptMerge
 *
 * @author Yireo (info@yireo.com)
 * @copyright Copyright 2013
 * @license GNU Public License
 * @link http://www.yireo.com
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

// Import the parent class
jimport( 'joomla.plugin.plugin' );

/**
 * ScriptMerge Helper
 */
class ScriptMergeHelper
{
    /**
     * Method to return the output of a JavaScript file
     *
     * @param string $string
     * @return string
     */
    static public function getJsContent($file)
    {
        // Don't try to parse empty (or non-existing) files
        if (empty($file)) return null;

        // Initialize the buffer
        $buffer = file_get_contents($file);
        if (empty($buffer)) return null;

        // Initialize the basepath
        $basefile = ScriptMergeHelper::getFileUrl($file, false);

        // If compression is enabled
        $application = JFactory::getApplication();
        $compress_js = ScriptMergeHelper::getParams()->get('compress_js', 0);
        if ($application->isSite() && $compress_js > 0) {

            // JsMinPlus definitely does not work with MooTools (for now)
            if($compress_js == 2 && stristr($file, 'mootools') == true) {
                $compress_js = 1;
            }

            // Switch between the various compression-schemes
            switch ($compress_js) {

                case 1:
                    $buffer = str_replace('/// ', '///', $buffer);		
				    $buffer = str_replace(',//', ', //', $buffer);
    				$buffer = str_replace('{//', '{ //', $buffer);
	    			$buffer = str_replace('}//', '} //', $buffer);
		    		$buffer = str_replace('/**/', '/*  */', $buffer);
			    	$buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
				    $buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
    				$buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
	    			$buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
		    		$buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
				    $buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
    				$buffer = preg_replace('/\/\/.*\/\/\n/', '', $buffer);
	    			$buffer = preg_replace("/\s\/\/\".*/", "", $buffer);
		    		$buffer = preg_replace("/\/\/\n/", "\n", $buffer);
			    	$buffer = preg_replace("/\/\/\s.*.\n/", "\n  \n", $buffer);
				    $buffer = preg_replace('/\/\/w[^w].*/', '', $buffer);
    				$buffer = preg_replace('/\/\/s[^s].*/', '', $buffer);
	    			$buffer = preg_replace('/\/\/\*\*\*.*/', '', $buffer);
		    		$buffer = preg_replace('/\/\/\*\s\*\s\*.*/', '', $buffer);
			    	$buffer = preg_replace('!/\*[^\'."].*?\*/!s', '', $buffer);
				    $buffer = preg_replace('/\n\s*\n/', "\n", $buffer);
    				$buffer = preg_replace("/<!--.*-->/Us","", $buffer);
	    			$buffer = preg_replace('/[^:.\-.1.\\\.C.b]\/\/[^,.".*.\'.;.$.).w.s.8].*/', '', $buffer);	
                    $buffer = preg_replace('/\s+/', ' ', $buffer);
                    break;

                case 2:
                    // Compress the js-code
                    $jsMinPhp = JPATH_SITE.'/components/com_scriptmerge/lib/jsminplus.php';
                    if(file_exists($jsMinPhp)) {
                        include_once $jsMinPhp;
                        if(class_exists('JSMinPlus')) {
                            $buffer = JSMinPlus::minify($buffer);
                        }
                    }
                    break;

                case 0:
                default:
                    break;
            }

            // Append the filename to the JS-code
            if(ScriptMergeHelper::getParams()->get('use_comments', 1)) {
                $start = "/* [start] ScriptMerge JavaScript file: $basefile */\n\n";
                $end = "/* [end] ScriptMerge JavaScript file: $basefile */\n\n";
                $buffer = $start.$buffer."\n".$end;
            }

        // If compression is disabled
        } else {

            // Append the filename to the JS-code
            if(ScriptMergeHelper::getParams()->get('use_comments', 1)) {
                $start = "/* [start] ScriptMerge uncompressed JavaScript file: $basefile */\n\n";
                $end = "/* [end] ScriptMerge uncompressed JavaScript file: $basefile */\n\n";
                $buffer = $start.$buffer."\n".$end;
            }
        }

        return $buffer;
    }

    /**
     * Method to return the output of a CSS file
     *
     * @param string $string
     * @return string
     */
    static public function getCssContent($file)
    {
        // Don't try to parse empty (or non-existing) files
        if (empty($file)) return null;

        // Skip files that have already been included
        static $files = array();
        if (in_array($file, $files)) {
            return null;
        } else {
            $files[] = $file;
        }

        // Initialize the buffer
        $buffer = @file_get_contents($file);
        if (empty($buffer)) return null;

        // Initialize the basepath
        $basefile = ScriptMergeHelper::getFileUrl($file, false);

        // Follow all @import rules
        if (ScriptMergeHelper::getParams()->get('follow_imports', 1) == 1) {
            if (preg_match_all('/@import\ url\((.*)\);/i', $buffer, $matches)) {
                foreach ($matches[1] as $index => $match) {

                    // Strip quotes
                    $match = str_replace('\'', '', $match);
                    $match = str_replace('"', '', $match);

                    $importFile = ScriptMergeHelper::getFilePath($match, $file);
                    if (empty($importFile) && strstr($importFile, '/') == false) $importFile = dirname($file).'/'.$match;
                    $importBuffer = ScriptMergeHelper::getCssContent($importFile);

                    if (!empty($importBuffer)) {
                        $buffer = str_replace($matches[0][$index], "\n".$importBuffer."\n", $buffer);
                    } else {
                        $buffer = "\n/* ScriptMerge error: CSS import of $importFile returned empty */\n\n".$buffer;
                    }
                }
            }
        }

        // Replace all relative paths with absolute paths
        if (preg_match_all('/url\(([^\(]+)\)/i', $buffer, $url_matches)) {
            foreach ($url_matches[1] as $url_index => $url_match) {

                // Strip quotes
                $url_match = str_replace('\'', '', $url_match);
                $url_match = str_replace('"', '', $url_match);

                // Skip CSS-stylesheets which need to be followed differently anyway
                if (strstr($url_match, '.css')) continue;

                // Skip URLs and data-URIs
                if (preg_match('/^(http|https):\/\//', $url_match)) continue;
                if (preg_match('/^\/\//', $url_match)) continue;
                if (preg_match('/^data\:/', $url_match)) continue;

                // Normalize this path
                $url_match_path = ScriptMergeHelper::getFilePath($url_match, $file);
                if (empty($url_match_path) && strstr($url_match, '/') == false) $url_match_path = dirname($file).'/'.$url_match;
                if (!empty($url_match_path)) $url_match = ScriptMergeHelper::getFileUrl($url_match_path);
    
                // Include data-URIs in CSS as well
                if (ScriptMergeHelper::getParams()->get('data_uris', 0) == 1) {
                    $imageContent = ScriptMergeHelper::getDataUri($url_match_path);
                    if (!empty($imageContent)) {
                        $url_match = $imageContent;
                    }
                }

                $buffer = str_replace($url_matches[0][$url_index], 'url('.$url_match.')', $buffer);
            }
        }

        // Detect PNG-images and try to replace them with WebP-images
        if (preg_match_all('/([a-zA-Z0-9\-\_\/]+)\.(png|jpg|jpeg)/i', $buffer, $matches)) {
            foreach ($matches[0] as $index => $image) {
                $webp = ScriptMergeHelper::getWebpImage($image);
                if ($webp != false && !empty($webp)) {
                    $buffer = str_replace($image, $webp, $buffer);
                } 
            }
        }

        // If compression is enabled
        $compress_css = ScriptMergeHelper::getParams()->get('compress_css', 0);
        if ($compress_css > 0) {

            switch ($compress_css) {

                case 1: 
                    $buffer = preg_replace('#[\r\n\t\s]+//[^\n\r]+#', ' ', $buffer);
                    $buffer = preg_replace('/[\r\n\t\s]+/s', ' ', $buffer);
                    $buffer = preg_replace('#/\*.*?\*/#', '', $buffer);
                    $buffer = preg_replace('/[\s]*([\{\},;:])[\s]*/', '\1', $buffer);
                    $buffer = preg_replace('/^\s+/', '', $buffer);
                    $buffer .= "\n";
                    break;

                case 2:
                    // Compress the CSS-code
                    $cssMin = JPATH_SITE.'/components/com_scriptmerge/lib/cssmin.php';
                    if(file_exists($cssMin)) include_once $cssMin;
                    if(class_exists('CssMin')) {
                        $buffer = CssMin::minify($buffer);
                    }
                    break;

                case 0:
                default:
                    break;
            }

        // If compression is disabled
        } else { 

            // Append the filename to the CSS-code
            if(ScriptMergeHelper::getParams()->get('use_comments', 1)) {
                $start = "/* [start] ScriptMerge CSS-stylesheet: $basefile */\n\n";
                $end = "/* [end] ScriptMerge CSS-stylesheet: $basefile */\n\n";
                $buffer = $start.$buffer."\n".$end;
            }
        }

        return $buffer;
    }

    /**
     * Method to return the WebP-equivalent of an image, if possible
     *
     * @param string $string
     * @return string
     */
    static public function getWebpImage($imageUrl)
    {
        // Check if WebP support is enabled
        if (ScriptMergeHelper::getParams()->get('use_webp', 0) == 0) {
            return false;
        }

        // Check for WebP support
        $webp_support = false;

        // Check for the "webp" cookie
        if (isset($_COOKIE['webp']) && $_COOKIE['webp'] == 1) {
            $webp_support = true;

        // Check for Chrome 9 or higher
        } else if (preg_match('/Chrome\/([0-9]+)/', $_SERVER['HTTP_USER_AGENT'], $match) && $match[1] > 8) {
            $webp_support = true;
        }

        if ($webp_support == false) {
            return false;
        }

        // Check for the cwebp binary
        $cwebp = ScriptMergeHelper::getParams()->get('cwebp', '/usr/local/bin/cwebp');
        if (empty($cwebp) || file_exists($cwebp) == false) return false;
        if (function_exists('exec') == false) return false;

        if (preg_match('/^(http|https):\/\//', $imageUrl) && strstr($imageUrl, JURI::root())) {
            $imageUrl = str_replace(JURI::root(), '', $imageUrl);
        }

        $imagePath = JPATH_ROOT.'/'.$imageUrl;
        if (is_file($imagePath)) {

            // Detect alpha-transparency in PNG-images and skip it
            if (preg_match('/\.png$/', $imagePath)) {
                $imageContents = @file_get_contents($imagePath);
                $colorType = ord(@file_get_contents($imagePath, NULL, NULL, 25, 1));
                if ($colorType == 6 || $colorType == 4) {
                    return false;
                } else if (stripos($imageContents, 'PLTE') !== false && stripos($imageContents, 'tRNS') !== false) {
                    return false;
                }
            }

            $webpPath = preg_replace('/\.(png|jpg|jpeg|gif)$/', '.webp', $imagePath);

            if (is_file($webpPath) == false) {
                $cmd = "$cwebp -q 100 $imagePath -o $webpPath";
                exec($cmd);
            }

            if (is_file($webpPath)) {
                $webpUrl = str_replace(JPATH_ROOT, '', $webpPath);
                $webpUrl = preg_replace('/^\//', '', $webpUrl);
                $webpUrl = preg_replace('/^\//', '', $webpUrl);
                $webpUrl = JURI::root().$webpUrl;
                return $webpUrl;
            }
        }

        return false;
    }

    /**
     * Method to translate an image into data URI
     *
     * @param string $url
     * @return string
     */
    static public function getDataUri($file = null)
    {
        // If this is not a file, do not continue
        if (is_file($file) == false) {
            return null;
        }

        // If this is not an image, do not continue
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file) == false) {
            return null;
        }

        // Check the file-length
        if (filesize($file) > ScriptMergeHelper::getParams()->get('data_uris_filesize', 2000)) {
            return null;
        }

        // Fetch the content
        $content = @file_get_contents($file);
        if (empty($content)) {
            return null;
        }

        $mimetype = null; 
        if (preg_match('/\.gif$/i', $file)) {
            $mimetype = 'image/gif';
        } else if (preg_match('/\.png$/i', $file)) {
            $mimetype = 'image/png';
        } else if (preg_match('/\.webp$/i', $file)) {
            $mimetype = 'image/webp';
        } else if (preg_match('/\.(jpg|jpeg)$/i', $file)) {
            $mimetype = 'image/jpg';
        }

        if (!empty($content) && !empty($mimetype)) {
            return 'data:'.$mimetype.';base64,'.base64_encode($content);
        }

        return null;
    }

    /**
     * Check if the cache has expired
     *
     * @param string $cache
     * @return null
     */
    static public function hasExpired($timestampFile, $cacheFile)
    {
        // Check if the expiration file exists
        if (is_file($timestampFile)) {
            $time = (int)@file_get_contents($timestampFile);
            if ($time < time()) {
                jimport( 'joomla.filesystem.file' );
                JFile::delete($timestampFile);
                JFile::delete($cacheFile);
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Set a new cache expiration
     *
     * @param string $cache
     * @return null
     */
    private function setCacheExpire($file)
    {
        $config = JFactory::getConfig();
        if(method_exists($config, 'getValue')) {
            $lifetime = (int)$config->getValue('config.lifetime');
        } else {
            $lifetime = (int)$config->get('config.lifetime');
        }
        if (empty($lifetime) || $lifetime < 120) $lifetime = 120;
        $time = time() + $lifetime;
        jimport( 'joomla.filesystem.file' );
        JFile::write($file, $time);
    }

    /**
     * Get a valid file URL
     *
     * @param string $path
     * @return string
     */
    static public function getFileUrl($path, $include_url = true)
    {
        $path = str_replace(JPATH_SITE.'/', '', $path);

        if ($include_url) {
            $path = JURI::root().$path;
        }
        return $path;
    }

    /**
     * Get a valid filename
     *
     * @param string $file
     * @param string $base_path
     * @return string
     */
    static public function getFilePath($file, $base_path = null)
    {
        // If this is already a correct path, return it
        if (is_file($file) && is_readable($file)) {
            return realpath($file);
        }

        // Determine the application path
        $app = JRequest::getInt('app', JFactory::getApplication()->getClientId());
        if ($app == 1) {
            $app_path = JPATH_ADMINISTRATOR;
        } else {
            $app_path = JPATH_SITE;
        }

        // Make sure the basepath is not a file
        if (is_file($base_path)) {
            $base_path = dirname($base_path);
        }

        // Determine the basepath
        if (empty($base_path)) {
            if (substr($file, 0, 1) == '/') {
                $base_path = JPATH_SITE;
            } else {
                $base_path = $app_path;
            }
        }

        // Append the base_path
        if (strstr($file, $base_path) == false && !empty($base_path)) {
            $file = $base_path.'/'.$file;
        }

        // Detect the right application-path
        if (JFactory::getApplication()->isAdmin()) {
            if (strstr($file, JPATH_ADMINISTRATOR) == false && is_file(JPATH_ADMINISTRATOR.'/'.$file)) {
                $file = JPATH_ADMINISTRATOR.'/'.$file;
            } else if (strstr($file, JPATH_SITE) == false && is_file(JPATH_SITE.'/'.$file)) {
                $file = JPATH_SITE.'/'.$file;
            }
        } else {
            if (strstr($file, JPATH_SITE) == false && is_file(JPATH_SITE.'/'.$file)) {
                $file = JPATH_SITE.'/'.$file;
            }
        }

        // If this is not a file, return empty
        if (is_file($file) == false || is_readable($file) == false) {
            return null;
        }

        // Return the file
        return realpath($file);
    }

    /**
     * Encode the file-list
     *
     * @param array $files
     * @return string
     */
    static public function encodeList($files)
    {
        $files = implode(',', $files);
        $files = str_replace(JPATH_ADMINISTRATOR.'/', '$B', $files);
        $files = str_replace(JPATH_SITE.'/', '$F', $files);
        $files = str_replace('template', '$T', $files);
        $files = str_replace('js', '$J', $files);
        $files = str_replace('media', '$M', $files);
        $files = str_replace('css', '$C', $files);
        $files = str_replace('system', '$S', $files);
        $files = str_replace('layout', '$l', $files);
        $files = str_replace('cache', '$c', $files);
        $files = str_replace('font', '$f', $files);
        $files = str_replace('tools', '$t', $files);
        $files = str_replace('widgetkit', '$w', $files);
        $files = base64_encode($files);
        return $files;
    }

    /**
     * Decode the file-list
     *
     * @param string $files
     * @return array
     */
    static public function decodeList($files)
    {
        $files = base64_decode($files);
        $files = str_replace('$F', JPATH_SITE.'/', $files);
        $files = str_replace('$B', JPATH_ADMINISTRATOR.'/', $files);
        $files = str_replace('$T', 'template', $files);
        $files = str_replace('$J', 'js', $files);
        $files = str_replace('$M', 'media', $files);
        $files = str_replace('$C', 'css', $files);
        $files = str_replace('$S', 'system', $files);
        $files = str_replace('$l', 'layout', $files);
        $files = str_replace('$c', 'cache', $files);
        $files = str_replace('$f', 'font', $files);
        $files = str_replace('$t', 'tools', $files);
        $files = str_replace('$w', 'widgetkit', $files);
        $files = explode(',', $files);
        return $files;
    }

    /**
     * Load the parameters
     *
     * @param null
     * @return JParameter
     */
    static public function getParams()
    {
        $plugin = JPluginHelper::getPlugin('system', 'scriptmerge');

        JLoader::import( 'joomla.version' );
        $version = new JVersion();
        if (version_compare( $version->RELEASE, '1.5', 'eq')) {
            jimport('joomla.html.parameter');
            $params = new JParameter($plugin->params);
        } else {
            $params = new JRegistry($plugin->params);
        }

        return $params;
    }
}
