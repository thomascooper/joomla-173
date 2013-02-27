<?php defined( '_JEXEC' ) or die( 'Restricted access' );
/**
 * @version 	  $Id: admin.jcrawler.php 2012-06-17 19:34:00Z zanardi$
 * @package 	  JCrawler
 * @copyright 	(C) 2008-2012 Patrick Winkler
 * @license 	  GNU/GPL, see http://www.gnu.org/licenses/gpl-2.0.html
 */

// Permissions check
if (! checkPermissions() ) $app->redirect( 'index.php', JText::_('ALERTNOTAUTH') );

// These settings may or may not work depending on your hosting environment
@set_time_limit(99999999);
@ini_set("max_execution_time","false");
@ini_set("memory_limit",'512M');

if ( array_search( 'curl', get_loaded_extensions() ) ) define ( 'CURL_LOADED', True );

jimport( 'joomla.application.helper' );
jimport( 'joomla.filesystem.file' );

$option = JRequest::getCmd('option');
$task = JRequest::getVar( 'task', '' );
$visited_urls = array(); // keeps track of visited pages in order to avoid getting them twice

switch ($task) {
	case 'submit':
		submit($option);
		break;
	case 'notify':
		notify($option);
		break;
	case 'updatecheck':
		updatecheck($option);
		break;
	default:
    require_once( JApplicationHelper::getPath( 'admin_html', 'com_jcrawler' ) ); 
		HTML_jcrawler::showForm($option);
		break;
}

/**
 * Check execution permissions
 *
 * @return  bool  true if user can execute the crawling
 */
function checkPermissions() 
{
  $user = &JFactory::getUser();
  $auth = true;

  $jversion = new JVersion();
  if( $jversion->RELEASE == '1.5' ) { // Joomla 1.5
    if (!($user->usertype == 'Super Administrator' || $user->usertype == 'Administrator')) {	
      $auth = false;
    }
  } else { // Joomla 2.5
    if ( ! ( in_array ( '7', $user->getAuthorisedGroups() ) || in_array ( '8', $user->getAuthorisedGroups() ) ) ) {
      $auth = false;
    }
  }
  
  return( $auth );
}

/**
 * Main wrapper function for submit task
 * - get parameters from the form
 * - save config
 * - do several checks 
 * - call main crawling function (getLinks) to get all the links at once
 * - complete it with priority information
 * - generate sitemap XML file
 * 
 * @param   string $option  the component name
 * @return  nothing 
 */
function submit($option) 
{
  $db =& JFactory::getDBO();
  $query = "TRUNCATE TABLE `#__jcrawler_urls`";
  $db->setQuery( $query );
  $db->query();
  
	$app = &JFactory::getApplication();
	
	// get parameters from gui of script
	if(! defined( 'HTTP_HOST' ) ) {
		define( 'HTTP_HOST', JRequest::getVar( 'http_host', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML ) );
	}
	$website = HTTP_HOST;
	if(substr($website,-1)!="/") $website=$website."/";
	$page_root = JRequest::getVar( 'document_root', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$sitemap_file = $page_root . JRequest::getVar( 'sitemap_url', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$sitemap_url = $website . JRequest::getVar( 'sitemap_url', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$sitemap_form = JRequest::getVar( 'sitemap_url', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$priority = JRequest::getVar( 'priority', '1.0', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$forbidden_types = toTrimmedArray(JRequest::getVar( 'forbidden_types', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML ));
	$exclude_names = toTrimmedArray(JRequest::getVar( 'exclude_names', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML ));
	$freq = JRequest::getVar( 'freq', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$modifyrobots = JRequest::getVar( 'robots', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$method = JRequest::getVar( 'method', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$level = JRequest::getVar( 'levels', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$maxcon = JRequest::getVar( 'maxcon', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$timeout = JRequest::getVar( 'timeout', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$whitelist = JRequest::getVar( 'whitelist', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	
	if ($priority >= 1) $priority = "1.0";
	
	$xmlconfig = genConfig($priority,$forbidden_types,$exclude_names,$freq,$method,$level,$maxcon,$sitemap_form,$page_root,$timeout);
		
	if (substr($page_root,-1)!="/") $page_root = $page_root."/";
	$robots = @JFile::read( $page_root.'robots.txt' );

	preg_match_all("/Disallow:(.*?)\n/", $robots, $pos);
	
	if ($exclude_names[0]=="") unset($exclude_names[0]);
	
	foreach($pos[1] as $disallow){
		$disallow=trim($disallow);
		if (strpos($disallow,$website)===false) $disallow=$website.$disallow;
		$exclude_names[]=$disallow;
	}
	
	$forbidden_strings=array("print=1","format=pdf","option=com_mailto","component/mailto","/mailto/","mailto:","login","register","reset","remind");
	foreach ($exclude_names as $name) {
		if ($name!="") $forbidden_strings[]=$name;
	}
	
	$s = microtime(true);
	
	if($whitelist=="yes") AntiFloodControl($website);
	
	$file = genSitemap($priority, getLinks($website,$forbidden_types,$level,$forbidden_strings,$method,$maxcon,$timeout),$freq,$website);
	writeXML($file,$sitemap_file,$option,$sitemap_url);
	writeXML($xmlconfig,$page_root."/administrator/components/com_jcrawler/config.xml",$option,$sitemap_url);
	
	$app->enqueueMessage( "total time: ".round(microtime(true) - $s, 4)." seconds" );

	if ($modifyrobots==1) modifyrobots($sitemap_url,$page_root);
  require_once( JApplicationHelper::getPath( 'admin_html', 'com_jcrawler' ) ); 
	HTML_jcrawler::showNotifyForm($option,$sitemap_url);
}

/**
 * Submit sitemap to search engines
 * 
 * @param   string $option  the component name
 * @return  nothing 
 */
function notify($option) 
{
	$app = &JFactory::getApplication();
	$url = JRequest::getVar( 'url', 'none', 'POST', 'ARRAY', JREQUEST_ALLOWHTML );
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if ($url[0]!="none"){
		foreach ($url as $key) {
			
			curl_setopt($ch, CURLOPT_URL, $key);
			curl_exec($ch);
			
			$curlError = curl_error($ch);
			if($curlError != "") {
				$app->enqueueMessage("Curl error on url $key: $curlError",'error');
			}
				
			$http_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($http_code>=400) {
          $app->enqueueMessage(htmlentities("httpcode: ".$http_code." on url ".$key."<br><a href=\"".$key."\" target=\"_blank\">Click to submit by hand</a>"),'error');
			 } else {
          $app->enqueueMessage( "Submission to ".parse_url($key, PHP_URL_HOST)." succeed " );
			 } 	 	
		}
	}
	curl_close($ch);
	$app->redirect('index.php?option='.$option);
}

/**
 * Check for latest version against JCrawler site
 * 
 * @return  nothing 
 */
function updatecheck()
{
	define("jcrawler_version","1.12");
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://www.jcrawler.net/version.php");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$content = curl_exec($ch);
	curl_close($ch);
	if( $content == jcrawler_version ) {
	 	echo "<h2>Thank you for checking, you have the latest version (". jcrawler_version. ")</h2>";
	} else {
	 	echo "<h2>There is a new version (". $content .") available.</h2>Download version ".$content." now: <a href=\"http://www.jcrawler.net/index.php?option=com_docman&Itemid=13\" target=\"_blank\">JCrawler ".$content."</a>";
	}
}

/**
 * Modify robots.txt file
 * 
 * @param   string $sitemap_url  the url for the generated sitemap
 * @param   string $page_root    the filesystem path to the site root
 * @return  nothing 
 */
function modifyrobots ($sitemap_url,$page_root) 
{
	$app = &JFactory::getApplication();
		
	if (substr($page_root,-1)!="/") { 
		$page_root=$page_root."/"; 
	}
	
	if ( !$robots = JFile::read( $page_root.'robots.txt' ) ) {
		$app->enqueueMessage( "robots.txt does not exist or is not readable" );
		return false;
	}
	
	$pos = preg_match("/Sitemap:/", $robots);
	if ( $pos == 0 ) {	
		$robots .= "\n# BEGIN JCRAWLER-XML-SITEMAP-COMPONENT\nSitemap: ".$sitemap_url."\n# END JCRAWLER-XML-SITEMAP-COMPONENT";
		if ( JFile::write( $page_root.'robots.txt',$robots ) != false ) {
			$app->enqueueMessage( "robots.txt modified" );
		} else {
			$errors[] = JError::getErrors();
			foreach ($errors as $error) {
				$app->enqueueMessage($error->message,'error');
			}
			$app->enqueueMessage("robots.txt is not writable!",'error');
		}
	} else {
		$app->enqueueMessage( "robots.txt already contains sitemap location" );
	}
}
	
/**
 * Get current configuration from saved parameters
 * 
 * @param   string $path        path to the config file
 * @return  array               configuration parameters
 */
function getConf( $path )
{
  $parser = JFactory::getXMLParser('Simple');
  if ($parser->loadFile($path)) {
    if (isset( $parser->document )) {     
      $document = $parser->document;
			$chil     = $document->children();
			$chil1    = $chil[0]->children();
			foreach($chil1 as $child ) {
        $config_data[$child->name()]=$child->data();
      }
		}
	}
	return $config_data;
}

/**
 * Disable sh404SEF antiflood control for localhost
 *
 * @return  nothing 
 */
function AntiFloodControl($url){
	
	$app = &JFactory::getApplication();
	$sef_config_class = JPATH_ADMINISTRATOR.'/components/com_sh404sef/sh404sef.class.php';
	$sef_config_file  = JPATH_ADMINISTRATOR.'/components/com_sh404sef/config/config.sef.php';

	if (!class_exists('SEFConfig')) {
		if (is_readable($sef_config_class)) { 
			require_once($sef_config_class);
		} else { 
			return; 
		}
	}
	
	if ( class_exists('SEFConfig') ) {
		$sefConfig = new SEFConfig();
	} elseif ( class_exists('shSEFConfig') ) {
		$sefConfig = new shSEFConfig();
	} else {
		return;
	}

	$crawlurl = parse_url($url);
	$hosts = gethostbynamel($crawlurl['host']);
	$white_list = null;
	foreach ($hosts as $host) {
		if(array_search($host,$sefConfig->ipWhiteList)===FALSE){
			$white_list.="\n".$host;
		}
	}
		
	$handle = fopen(JPATH_ADMINISTRATOR.'/components/com_sh404sef/security/sh404SEF_IP_white_list.dat',"a");
		
	 if (!fwrite($handle, $white_list) and !empty($white_list)) {
	 	$app->enqueueMessage(htmlentities("Couldn't add IP to sh404SEF whitelist"),'error');
	 }elseif(!empty($white_list)) {
	 	$app->enqueueMessage("Added IP to sh404SEF whitelist");
	 }
	 fclose($handle);
}

/**
 * Write XML sitemap file
 * 
 * @param   string $file          content that should be written in the sitemap file
 * @param   string $location      full path to the sitemap file that is being written
 * @param   string $option        the name of the component
 * @param   string $sitemap_url   url of the sitemap file
 * @return  nothing 
 */
function writeXML ($file, $location, $option, $sitemap_url) {
	$app = &JFactory::getApplication();
	$buffer = pack("CCC",0xef,0xbb,0xbf);
	$buffer .= utf8_encode($file);
	if (JFile::write( $location, $buffer )){
		$app->enqueueMessage( "Success, wrote $location" );
	} else {
		$errors[] = JError::getErrors();
		foreach ($errors as $error) {
			$app->enqueueMessage($error->message,'error');
		}
		$app->enqueueMessage("$location is not writable",'error');
	}
	return;
}

/**
 * Generate the content of a new config file
 *
 * @param   string $priority
 * @param   string $forbidden_types
 * @param   string $exclude_names
 * @param   string $freq
 * @param   string $method
 * @param   string $level
 * @param   string $maxcon
 * @param   string $sitemap_form
 * @param   string $docroot
 * @param   string $timeout
 * @return  string XML config string
 */
function genConfig($priority,$forbidden_types,$exclude_names,$freq,$method,$level,$maxcon,$sitemap_form,$docroot,$timeout) 
{		
		$xmlconfig="<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\" ?>
<document>
  <options>
    <forbiddentypes>".htmlentities(join($forbidden_types,"\n"))."</forbiddentypes>
    <excludelist>".htmlentities(htmlentities(join($exclude_names,"\n"),ENT_QUOTES,'UTF-8'))."</excludelist>
    <sitemapurl>".$sitemap_form."</sitemapurl>
    <priority>".$priority."</priority>
    <changefreq>".$freq."</changefreq>
    <method>".$method."</method>
    <level>".$level."</level>
    <maxconn>".$maxcon."</maxconn>
    <docroot>".$docroot."</docroot>
    <timeout>".$timeout."</timeout>
	</options>
</document>";
	
  return $xmlconfig;
}

/**
 * Get all the links.
 * This function is executed only once: a cycle goes through each level
 * 
 * @param    string $url              the starting URL
 * @param    array $forbidden_types   file types to exclude from the crawling
 * @param    string $level            how many level to crawl
 * @param    array $exclude_name      pattern to exclude from the crawling
 * @param    string $method           crawling method
 * @param    string $maxcon           max parallel connections
 * @param    string $timeout          timeout foreach crawling operation
 * @return   array                    all the links retrieved from the site
 * @todo     refactor code / translate comments and variables
 */ 
function getLinks($url,$forbidden_types,$level,$exclude_names,$method,$maxcon,$timeout) 
{	
	global $linkarray, $linkarraykey;
	$tmp_ret_array = array();
	$linkarray = array(); 
	$linkarraykey = array();
	$tmparr_last = array();
	$ret_array = array();
	
	// This is the maximum depth level. Why is it incremented by one?
	$level = $level + 1;	

	/* changed $tmparr with $tmparr_last because of array_diff of first crawl) */
	
	// $tmparr contains the url (or urls) to use as a starting point to crawl
	is_array($url) ? $tmparr = $url : $tmparr[] = $url;
	
	// sets a limit if urls number is bigger than max parallel connections
	( count( $tmparr )>$maxcon ) ? $z = $maxcon : $z = count( $tmparr );
	
	for ($u=0;$u<$level;$u++){
		
		// $tmparr_last contains the url (or urls) from $tmparr which are not already in $tmparr_last
		$tmparr_last=array_diff( $tmparr, $tmparr_last );
		
		// sets a limit if urls number is bigger than max parallel connections
		( count( $tmparr_last ) > $maxcon ) ? $z = $maxcon : $z = count( $tmparr_last );
		
		$tmparr = array_unique( getUrl( connect( $tmparr_last, $z, $method, $timeout ), $forbidden_types, $exclude_names ) );		
		$linkarraykey = array_unique( getUrl( $linkarraykey,$forbidden_types,$exclude_names ) );
		
		foreach ($tmparr_last as $orr_key => $tmpurl) {

			/* todo: linkcount auf referenzenseite stimmt nocht nicht */
			
			$retsize=count($ret_array);
			for ($i=0;$i<$retsize;$i++){
				if($ret_array[$i]['url']==$tmpurl){
					$retsize=$i;
					break;
				}
			}
			
			$ret_array[$retsize]['url']=$tmpurl;
			
			if (! isset( $ret_array[$retsize]['out_links'] ) ) {
				$ret_array[$retsize]['out_links'] = array();
			}
			
			for($i=0;$i<count($linkarraykey);$i++){
				if($linkarraykey[$i]==$tmpurl){
					for($z=0;$z<count($linkarray[$i]);$z++){
						if( isset( $linkarray[$i][$z] ) ) { 
							$ret_array[$retsize]['out_links'][]=$linkarray[$i][$z];
						}
					}
				}
			}
			if(is_array($ret_array[$retsize]['out_links'])) $ret_array[$retsize]['out_links']=array_unique($ret_array[$retsize]['out_links']);
				if (!isset($ret_array[$retsize]['level'])) $ret_array[$retsize]['level']=$u;
				$ret_array[$retsize]['PR']=0;
		}
	}
	return $ret_array;
}

/**
 * Automatically calc priorities for urls
 * 
 * @param   array $urls   a list of urls
 * @return                a list of urls completed with priority
 * @todo    refactor code / translate and clarify comments
 */
function calcPriorities($urls) {
	//$website = JRequest::getVar( 'http_host', 'none', 'POST', 'STRING', JREQUEST_ALLOWHTML );
	$website = HTTP_HOST;
	$damp = 0.35;
	$iterate = 40; # loop 40 times
	$thesum=0;
	/*# Plain Heirarchical
	# forward links
	# a -> b     - 1 outgoing link  - home
	# b -> a,c   - 2 outgoing links - doc page 1
	# c -> b,a,d - 3 outgoing links - doc page 2
	# d -> a,c   - 2 outgoing links - doc page 3
	
	# i.e. "backward" links (what's pointing to me?)
	# a <= b/2, c/3, d/2
	# b <= a, c/3
	# c <= b/2, d/2
	# d <= c/3
	*/
	
	/* PR is always 0 */
	
	while ($iterate--) {
		$linkssize=count($urls);
		for($i=0;$i<$linkssize;$i++){
			//if(is_array($urls[$i]['out_links'])){
				//foreach ($urls[$i]['url'] as $out_links){
					$tempIndex_arr=getIndexfromURL($urls,$urls[$i]['url']);
					
					foreach($tempIndex_arr as $tempIndex_arr_url){
						$tempCount=count($urls[$tempIndex_arr_url]['out_links']);
						if ($tempCount>0) $thesum = $thesum + ($urls[$tempIndex_arr_url]['PR']/$tempCount);
					}
					
				if((1 - $damp) + ($damp * $thesum)>1 or $urls[$i]['url']==$website){
					$urls[$i]['PR']="1.0";
				} else {
					$urls[$i]['PR'] = round((1 - $damp) + ($damp * $thesum),2);
				}
				//echo " | ".$urls[$i]['PR'];
				$thesum=0;
			//}
		    /*$a = 1 - $damp + $damp * ($b/2 + $c/3 + $d/2);
		    $b = 1 - $damp + $damp * ($a + $c/3);
		    $c = 1 - $damp + $damp * ($b/2 + $d/2);
		    $d = 1 - $damp + $damp * ($c/3);*/
		} 
	}
	return $urls;
}

/**
 * I am not sure about how this function works
 * 
 * @param   array $links    an array of urls
 * @param   string $url     a specific url
 * @return  
 */
function getIndexfromURL($links,$url) {
	$linked_arr = array();
	$linkssize = count( $links );
	for($i=0;$i<$linkssize;$i++) {
		if(strpos($links[$i]['url'],$url)!==FALSE) {
			$linked_arr[]=$i;
		}
	}
	return $linked_arr;
}

/**
 * Build a sitemap in XML format
 * 
 * @param   string $priority        priority setting or "auto" to auto calculate
 * @param   array $url              the list of all the urls of the site
 * @param   string $freq            update frequency
 * @param   string $document_root   base site URL
 * @return  string                  an XML string containing the sitemap
 */
function genSitemap($priority, $urls, $freq, $document_root) {
	$app = &JFactory::getApplication();
	if($priority=="auto") {
		$urls=calcPriorities($urls);
	}
	
	$xml_string = '<?xml version=\'1.0\' encoding=\'UTF-8\'?><?xml-stylesheet type="text/xsl" href="'.$document_root.'/administrator/components/com_jcrawler/sitemap.xsl"?>
	<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
	$i=0;
	
	foreach ($urls as $loc) {
		$i++;
		/* urf-8 encoding */
		//$loc=htmlentities($loc,ENT_QUOTES,'UTF-8');
		//$loc=htmlspecialchars($loc,ENT_QUOTES,'UTF-8',false);
		$loc['url']=htmlspecialchars($loc['url']);
		
		$modified_at = date('Y-m-d\Th:i:s\Z');
		$xml_string .= "
		<url>
		   <loc>".$loc['url']."</loc>
		   <lastmod>$modified_at</lastmod>
		   <changefreq>".$freq."</changefreq>
		   <priority>";
		   ($priority=="auto")?($xml_string .= $loc['PR']):($xml_string .= $priority);
		   $xml_string .= "</priority>
		</url>";
	}
	
	$xml_string .= "
	</urlset>";
	
	$app->enqueueMessage( "There are $i links in your sitemap." );
	
	return $xml_string;
}

/**
 * I know this function parse an HTML page to find all contained links,
 * but some of its code is still unclear to me
 * 
 * @global  object $linkarray       ???
 * @global  object $linkarraykey    global list of urls
 * @param   string $buffer          a crawl result (an html page)
 * @param   string $key             the url of the html page
 * @return  array                   a list of urls (links) contained in this html page
 * @todo    some pages are parsed twice, that is not correct
 */
function parseBuffer( $buffer, $key ) 
{  
  global $linkarray, $linkarraykey;
	$urllist = array();
	$pattern = '/<a[\s]+[^>]*href\s*=\s*[\"\']?([^\'\" >]+)[\'\" >]/i';
	preg_match_all( $pattern, $buffer, $treffer );
	preg_match('/Location:(.*?)\n/', $buffer, $matches);
				
	foreach($matches as $match){
		if(strpos($match,"Location:")===false) $treffer[1][]=trim($match);
	}
	unset($matches);
	
	if(!in_array($key,$linkarraykey)) $linkarraykey[]=$key;
	$thekey=array_search($key,$linkarraykey);
	
	foreach($treffer[1] as $val){
		$linkarray[$thekey][]=$val;	
	}
		
	$urllist = array_unique($treffer[1]);
  
  $db =& JFactory::getDBO();
  
  // updates crawled url
  //$query = "UPDATE `#__jcrawler_urls` SET `visited` = 1 WHERE `url` = '$key'"; 
  //$db->setQuery( $query );
  //$db->query();
  
  // write new links to database
  //$query = "INSERT INTO `#__jcrawler_urls` ( `url` ) VALUES ('" . join( "'),('", array_unique( $treffer[1] ) ) .  "')"; 
  //$db->setQuery( $query );
  //$db->query();
  
	return $urllist;
	
}

/**
 * Check if fopen method is available on this server
 * 
 * @return   bool   true if fopen is available
 */ 
function checkFopen() 
{
	$val=ini_get('allow_url_fopen');
	if($val=="" or $val==0){
		return false;	
	}elseif($val=="On" or $val==1){
		return true;	
	}
} 

/**
 * Retrieve the pages (crawl)
 * 
 * @global  array $_POST
 * @param   array $url          a list of urls to crawl
 * @param   integer $z          maximum number of parallel connections
 * @param   string $method      crawling method
 * @param   integer $timeout    timeout period for each connection
 * @return  array               a list of urls contained in the crawled pages
 */

function connect ( $url, $z, $method, $timeout ) {
  
  global $visited_urls;
	global $_POST;
	$app =& JFactory::getApplication();

	$buffer = array();
	$str = array(
		"Accept-Language: en-us,en;q=0.5",
		"Accept-Charset: utf-8;q=0.7,*;q=0.7",
		"Keep-Alive: 300",
		"Connection: keep-alive",
		"Pragma: ");
	
	if ( CURL_LOADED and (!function_exists('curl_multi_init') or $z==1) and $method=="curl") {
		
		$tmp_buffer = array();
		$ch = curl_init();$i=0;
		foreach ($url as $key) {
      
      if( isset( $visited_urls[ $key ] ) ) continue;
      $visited_urls[ $key ] = 1;
		
			 // erzeuge einen neuen cURL-Handle
	 			curl_setopt($ch, CURLOPT_URL, $key);
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_REFERER, "http://www.google.com");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $str);
				curl_setopt($ch, CURLOPT_USERAGENT, "Googlebot/2.1 (+http://www.google.com/bot.html)");
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
				curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
	 
				 // führe die Aktion aus und gebe die Daten an den Browser weiter
				 $tmp_buffer[$i]=curl_exec($ch);
				 
				$curlError = curl_error($ch);
				if($curlError != "") {
	        		$app->enqueueMessage("Curl error on url $urlentry: $curlError",'error');
	      		}
					
				$http_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($http_code>=400) {
					 $app->enqueueMessage(htmlentities("httpcode: ".$http_code." on url ".$key),'error');
				 } else {
					$buffer = array_unique(array_merge($buffer,parseBuffer($tmp_buffer[$i],$key)));
				 }
				 $i++;
		} //end foreach
		unset($tmp_buffer,$url);
	 	curl_close ($ch);
	
	} elseif ( CURL_LOADED and function_exists('curl_multi_init') and $z>1 and $method=="curl") {
		
		if(count($url)!=0){
			$k=ceil(count($url)/$z);
			$urls=array_chunk($url, ceil(count($url) / $k),true);
		}else {
			$k=0;
		}
		$mh = curl_multi_init();
		
		for ($i=0;$i<$k;$i++){	

			foreach ($urls[$i] as $key => $urlentry){
				$ch[$key] = curl_init($urlentry);
        
        if( isset( $visited_urls[ $urlentry ] ) ) continue;
        $visited_urls[ $urlentry ] = 1;
        
				curl_setopt($ch[$key], CURLOPT_HEADER, true);
				curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch[$key], CURLOPT_REFERER, "http://www.google.com");
				curl_setopt($ch[$key], CURLOPT_HTTPHEADER, $str);
				curl_setopt($ch[$key], CURLOPT_USERAGENT, "Googlebot/2.1 (+http://www.google.com/bot.html)");
				curl_setopt($ch[$key], CURLOPT_VERBOSE, true);
				curl_setopt($ch[$key], CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($ch[$key], CURLOPT_TIMEOUT, $timeout);
				curl_setopt($ch[$key], CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch[$key], CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch[$key], CURLOPT_FRESH_CONNECT, false);
				curl_setopt($ch[$key], CURLOPT_FORBID_REUSE, false);
				
				curl_multi_add_handle($mh,$ch[$key]);
			}

			$runningHandles=null;
			
			 // Start performing the request
			 do {
				  $execReturnValue = curl_multi_exec($mh, $runningHandles);
			 } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
			 // Loop and continue processing the request
			 while ($runningHandles && $execReturnValue == CURLM_OK) {
				// Wait forever for network
				$numberReady = curl_multi_select($mh);
				if ($numberReady != -1) {
				  // Pull in any new data, or at least handle timeouts
				  do {
					 $execReturnValue = curl_multi_exec($mh, $runningHandles);
				  } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
				}
			 }
			
			// Check for any errors
		 if ($execReturnValue != CURLM_OK) {
			 $app->enqueueMessage("Curl multi read error $execReturnValue\n",'error');
		 }				
			
			
			foreach ($urls[$i] as $key => $urlentry){ 
				$curlError = curl_error($ch[$key]);
				
				if($curlError != "") {
        			$app->enqueueMessage("Curl error on url $urlentry: $curlError",'error');
      			}
				
				$http_code=curl_getinfo($ch[$key], CURLINFO_HTTP_CODE);
				if ($http_code>=400) {
					 $app->enqueueMessage(htmlentities("httpcode: ".$http_code." on url ".$urlentry) ,'error');
				 } else {
					$buffer = array_unique(array_merge($buffer,parseBuffer(curl_multi_getcontent ($ch[$key]),$urlentry)));
				 }
				// Remove and close the handle
				curl_multi_remove_handle($mh,$ch[$key]);
				curl_close($ch[$key]);
			}
			unset($url,$ch);
		}
		
			curl_multi_close($mh);
			unset($urls,$mh);
	 	
	} elseif (function_exists('fopen')  and $method=="fopen"){
	
		foreach ($url as $key) {
			
      if( isset( $visited_urls[ $key ] ) ) continue;
      $visited_urls[ $key ] = 1;
      
			$handle = @fopen ($key, "r");
			
			$return_code = @explode(' ', $http_response_header[0]);
    		$return_code = (int)$return_code[1];
			
			if ($return_code>=400) {
				 $app->enqueueMessage(htmlentities($http_response_header[0]." on url ".$key),'error');
			} elseif(!$handle) {
				$app->enqueueMessage(htmlentities("Could not connect to ".$key),'error');
			} else {
				while (!feof($handle)) {
					$buffer = array_unique(array_merge($buffer,parseBuffer(fgets($handle),$key)));
				}
			}
			@fclose($handle);
		}
		unset($url);
		
	} elseif (function_exists('file_get_contents')) {
		foreach ($url as $key) {
      
      if( isset( $visited_urls[ $key ] ) ) continue;
      $visited_urls[ $key ] = 1;
			
			$data=file_get_contents($key);
			$return_code = @explode(' ', $http_response_header[0]);
    		$return_code = (int)$return_code[1];
			
			if ($return_code>=400) {
				 $app->enqueueMessage(htmlentities($http_response_header[0]." on url ".$key),'error');
			} else {
				while (!feof($handle)) {
					$buffer = array_unique(array_merge($buffer,parseBuffer($data,$key)));
				}
			}
		}
		unset($url);
	} else {
		$app->enqueueMessage("You need curl or fopen, neither of them were available",'error');
	}
	
	return $buffer;

}

/* this walks recursivly through all directories starting at page_root and
   adds all files that fits the filter criterias */
// taken from Lasse Dalegaard, http://php.net/opendir
function getUrl( $buffer, $forbidden_types, $forbidden_strings ) 
{
	global $_POST;
	$website = HTTP_HOST;
	$web = parse_url($website);
	(strtolower(substr($web['host'],0,4))=="www.")?$web['host']=substr($web['host'],4):null;
	
	$tmparray=array();
		foreach ($buffer as $key) {
			if ($web['scheme']."://www.".$web['host']."/"==$key or $web['scheme']."://".$web['host']."/"==$key){
				$key=$website; 
			}
			if(strtolower(substr($key,0,4))!="http"){
				// slash management
				if(substr($key,0,1)=="/" and substr($website,-1)=="/" ){
					$key=substr($key,1);
					//print $key."<br>";
				}
				($web['path']!="" and $web['path']!="/")?$key=substr($website,0,strpos($website,$web['path'])).$key:$key=$website.$key;
			}
			$key=preg_replace(array('/([\?&]PHPSESSID=\w+)$/i','/(#[^\/]*)$/i', '/&amp;/','/^(javascript:.*)|(javascript:.*)$/i'),array('','','&','',''),$key);
			$pattern = "/".$web['scheme'].":\/\/(.*?)".$web['host'].str_replace('/','\/',$web['path'])."/";
			preg_match($pattern, $key, $treffer);
			
			$key=encodeUrl(trim(relative2absolute($website,$key)));
			/* todo add url from Location: header tag without any check */
			
			if(!in_array($key,$tmparray) && count($treffer)>0 && searchInArray($key, $forbidden_strings)==false && in_array(substr($key,strrpos($key,".")),$forbidden_types)===false){
				$tmparray[]=$key;
			}
			unset($key,$treffer);
		} //endforeach
		unset($buffer);
		
	return $tmparray;
}

/**
 * Encode some parts of the URL:
 * - the path
 * - the VALUES of the query string
 * 
 * @todo    why not also the KEYS of the query string and the hostname
 * @todo    it is necessary at all? Google states that only some chars need to be escaped in URLs
 * @param   string $url       the url to encode
 * @return  string            encoded string
 */ 
function encodeUrl( $url ) 
{
  // Make sure we have a string to work with
  if( !empty( $url ) ) {
    // Explode into URL components (scheme, host, port, user, pass, path, query, fragment)
    $urlparts = parse_url($url);

    // Make sure we have a valid result set and a query field
    if(is_array($urlparts) && isset($urlparts["query"])) {
      // Explode into key/value array
      $keyvalue_list=explode("&",($urlparts["query"]));

      // Store resulting key/value pairs
      $keyvalue_result=array();

      foreach($keyvalue_list as $value) {
        // Explode each individual key/value into an array
        $keyvalue=explode("=",$value);

        // Make sure we have a "key=value" array
        if(count($keyvalue)==2) {
          // Encode the value portion
          //($encode==1)?($keyvalue[1]=rawurlencode($keyvalue[1])):($keyvalue[1]=rawurldecode($keyvalue[1]));
          $keyvalue[1] = rawurlencode( $keyvalue[1] );

          // Add our key and encoded value into the result
          $keyvalue_result[] = implode("=",$keyvalue);
        }
      }

      // Repopulate our query key with encoded results
      $urlparts["query"]=implode("&",$keyvalue_result);
      // Build the the final output URL
    } //end if isset query
	
    if(is_array($urlparts) && isset($urlparts["path"])) {
      // Explode into key/value array
      $keyvalue_list2=explode("/",($urlparts["path"]));

      // Store resulting key/value pairs
      $keyvalue_result2=array();

      foreach($keyvalue_list2 as $value2) {
          // Encode the value portion
          //($encode==1)?($val2=rawurlencode($value2)):($val2=rawurldecode($value2));
          $val2 = rawurlencode( $value2 );

          // Add our key and encoded value into the result
         $keyvalue_result2[] = $val2;
      }

      // Repopulate our query key with encoded results
      $urlparts["path"]=implode("/",$keyvalue_result2);
      unset($keyvalue_list2,$keyvalue_result2,$keyvalue_list,$keyvalue_result);
      // Build the the final output URL
    } //end if isset query
	
	  $url =  ( isset($urlparts["scheme"] )   ? $urlparts["scheme"] . "://" : '' ) .
            ( isset($urlparts["user"] )     ? $urlparts["user"] . ":" : '').
            ( isset($urlparts["pass"] )     ? $urlparts["pass"]."@" : '').
            ( isset($urlparts["host"] )     ? $urlparts["host"] : '').
            ( isset($urlparts["port"] )     ? ":" . $urlparts["port"] : '').
            ( isset($urlparts["path"] )     ? cleanPath($urlparts["path"]) : '').
            ( isset($urlparts["query"] )    ? "?" . $urlparts["query"] : '').
            ( isset($urlparts["fragment"] ) ? "#" . $urlparts["fragment"] : '');
  }
	
  return $url;
}

function relative2absolute( $base, $relative ) 
{
	if (stripos($base, '?')!==false) { 
		$base=explode('?', $base);$base=$base[0];
	}
	if (strtolower(substr($relative, 0, 4))=='http') {
		return $relative;
	} else {
		$bparts=explode('/', $base, -1);
		$rparts=explode('/', $relative);
		foreach ($rparts as $i=>$part) {
			if ($part=='' || $part=='.') {
				unset($rparts[$i]);
				if ($i==0) {
					$bparts=array_slice($bparts, 0, 3);
				}
			} elseif ($part=='..') {
				unset($rparts[$i]);
				$done=false;
				for ($j=$i-1;$j>=0;$jÐ) {
					if (isset($rparts[$j])) {
						unset($rparts[$j]); $done=true; break;
					}
				}
				if (!($done) && count($bparts)>3) {
					array_pop($bparts);
				}
			}
		}
		return implode('/', array_merge($bparts, $rparts));
	}
}

function cleanPath( $path ) 
{
	$path = explode('/', preg_replace('#(/+)#', '/', $path));
	for ($i = 0; $i < count($path); $i ++) {
		if ($path[$i] == '.') {
			unset ($path[$i]);
			$path = array_values($path);
			$i --;
		} elseif ($path[$i] == '..' AND ($i > 1 OR ($i == 1 AND $path[0] != ''))) {
			unset ($path[$i]);
			unset ($path[$i -1]);
			$path = array_values($path);
			$i -= 2;
		} elseif ($path[$i] == '..' AND $i == 1 AND $path[0] == '') {
			unset ($path[$i]);
			$path = array_values($path);
			$i --;
		} else {
			continue;
		}
	}
	return implode('/', $path);
}

/**
 * This function explodes an array from a string using specified delimiter
 * and trim each value
 * 
 * @param   string $str     full string to be exploded
 * @param   string $delim   string separator
 * @return  array           resulting array
 */
function toTrimmedArray($str, $delim = "\n") {
	$array = explode( $delim, $str );
  $array = array_map( "trim", $array );
  return $array;
}

/* Search if given substring is contained in at least one of array values
 * 
 * @param   string $key     the string to search for
 * @param   array $array    the array to search in
 * @return  bool            true if string is found
 */
function searchInArray($key, $array) {
  if (is_array($array) && count($array) > 0) {
    foreach ($array as $val) {
      if ( strpos($key, $val) !== false) {
        return true;
      }
    }
  }
  return false;
}

/**
 * v1.13 OBSOLETE
 * this function is not used anymore 
 * remove after next release
 */
/* function changeOffset($array, $old_offset, $offset) {
	$res = array();
	if (is_array($array) && count($array) > 0) {
		foreach ($array as $val) {
			$res[] = str_replace($old_offset, $offset, $val);
		}
	}
	return $res;
} */

/**
 * v1.13 OBSOLETE
 * this function is not used anymore 
 * remove after next release
 */
/* function in_array_like($referencia,$array){ 
	foreach( $array as $ref ) { 
		if( strstr( $referencia, $ref ) ){          
			return true; 
		} 
	} 
	return false; 
} */

/**
 * v1.13 OBSOLETE
 * this function is not used anymore 
 * remove after next release
 */
/* function array_search_recursive($needle, $haystack, $path=array()) {
    foreach($haystack as $id => $val) {
		$path2=$path;
		$path2[] = $id;
		if($val === $needle) {
			return $path2;
		} else if(is_array($val)) {
			if($ret = array_search_recursive($needle, $val, $path2)) {
				return $ret;
			}
		}
	}
	return false;
} */

/**
 * v1.13 OBSOLETE
 * this function is not used anymore 
 * remove after next release
 */
/* function recursive_in_array($needle, $haystack) {
    foreach ($haystack as $stalk) {
        if ($needle === $stalk || (is_array($stalk) && recursive_in_array($needle, $stalk))) {
            return true;
        }
    }
    return false;
} */

/**
 * v1.13 OBSOLETE
 * this function is not used anymore 
 * remove after next release
 */
/* function printprogress($url, $count) {
	print "<script language='JavaScript' type='text/javascript'>
				<!--
  					var d = document.getElementById('statusinfo');
  					if (d) d.innerHTML = '<b>&nbsp;Lese: </b>".$url." &nbsp; <b>".$count."</b> Links gefunden. Noch zu durchsuchende Seiten <b>".$count."</b>';
				//-->
			</script>";
} */

/**
 * v1.13 OBSOLETE
 * this function is not used anymore 
 * remove after next release
 */
/* function parsePHPinfo() {
	ob_start();
	phpinfo();
	$phpinfo = array('phpinfo' => array());
	if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?(?: colspan=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			if(strlen($match[1])) {
				$phpinfo[$match[1]] = array();
			} elseif(isset($match[3])) {
				$phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
			} else {
				$phpinfo[end(array_keys($phpinfo))][] = $match[2];
			}
		}
	}
	return $phpinfo;
} */  

/**
 * v1.13 OBSOLETE
 * this function is not used anymore 
 * remove after next release
 */
/* function fl_begins($key, $array) {
	//print $key;print_r($array);
	if (is_array($array) && count($array) > 0) {
		foreach ($array as $val) {
			//print $key." | ".$val." | ".substr($key,0,strlen($val))."\n";
			//substr($key,0,strlen(myUrlcode($val)))==$val or 
			if (substr($key,0,strlen($val))==$val){
			  return true;
			}
		}
	}
	return false;
} */
