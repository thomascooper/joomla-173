<?php
/**
* ProfileBook plugin bbcode handling functions
* @version $Id: bbcode.inc.php 2650 2012-10-25 14:06:13Z kyle $
* @package Community Builder
* @subpackage ProfileBook plugin
* @author JoomlaJoe, Beat and various
* @copyright (C) JoomlaJoe and Beat 2005-2006, www.joomlapolis.com and various
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

function begtoend($htmltag){
	return preg_replace('/<([A-Za-z]+)>/','</$1>',$htmltag);
}
function replace_pcre_array($text,$array){
	$pattern = array_keys($array);
	$replace = array_values($array);
	$text = preg_replace($pattern,$replace,$text);
	return $text;
}
class profilebook_bbcode {
	var $tags;
	var $settings;
	function profilebook_bbcode(){
		$this->tags = array();
		$this->settings = array('enced'=>true);
	}
	function get_data($name,$cfa = ''){
		if(!array_key_exists($name,$this->tags)) return '';
		$data = $this->tags[$name];
		if($cfa) $sbc = $cfa; else $sbc = $name;
		if(!is_array($data)){
			$data = preg_replace('/^ALIAS(.+)$/','$1',$data);
			return $this->get_data($data,$sbc);
		}else{
			$data['Name'] = $sbc;
			return $data;
		}
	}
	function change_setting($name,$value){
		$this->settings[$name] = $value;
	}
	function add_alias($name,$aliasof){
		if(!array_key_exists($aliasof,$this->tags) or array_key_exists($name,$this->tags)) return false;
		$this->tags[$name] = 'ALIAS'.$aliasof;
		return true;
	}
	function onparam($param,$regexarray){
		$param = replace_pcre_array($param,$regexarray);
		if(!$this->settings['enced']){
			$param = htmlentities($param);
		}
		return $param;
	}
	function export_definition(){
		return serialize($this->tags);
	}
	function import_definiton($definition,$mode = 'append'){
		switch($mode){
			case 'append':
			$array = unserialize($definition);
			$this->tags = $array + $this->tags;
			break;
			case 'prepend':
			$array = unserialize($definition);
			$this->tags = $this->tags + $array;
			break;
			case 'overwrite':
			$this->tags = unserialize($definition);
			break;
			default:
			return false;
		}
		return true;
	}
	function add_tag($params){
		if(!is_array($params)) return 'Paramater array not an array.';
		if(!array_key_exists('Name',$params) or empty($params['Name'])) return 'Name parameter is required.';
		if(preg_match('/[^A-Za-z =]/',$params['Name'])) return 'Name can only contain letters.';
		if(!array_key_exists('HasParam',$params)) $params['HasParam'] = false;
		if(!array_key_exists('HtmlBegin',$params)) return 'HtmlBegin paremater not specified!';
		if(!array_key_exists('HtmlEnd',$params)){
			 if(preg_match('/^(<[A-Za-z]>)+$/',$params['HtmlBegin'])){
			 	$params['HtmlEnd'] = begtoend($params['HtmlBegin']);
			 }else{
			 	return 'You didn\'t specify the HtmlEnd parameter, and your HtmlBegin parameter is too complex to change to an HtmlEnd parameter.  Please specify HtmlEnd.';
			 }
		}
		if(!array_key_exists('ParamRegexReplace',$params)) $params['ParamRegexReplace'] = array();
		if(!array_key_exists('ParamRegex',$params)) $params['ParamRegex'] = '[^\\]]+';
		if(!array_key_exists('HasEnd',$params)) $params['HasEnd'] = true;
		if(!array_key_exists('ReplaceContent',$params)) $params['ReplaceContent'] = false;
		if(array_key_exists($params['Name'],$this->tags)) return 'The name you specified is already in use.';
		$this->tags[$params['Name']] = $params;
		return '';
	}
	function parse_bbcode($text){
		$ignore					=	null;
		foreach($this->tags as $tagname => $tagdata){
			if(!is_array($tagdata)) $tagdata = $this->get_data($tagname);
			$startfind = "/\\[{$tagdata['Name']}";
			if($tagdata['HasParam']){
				$startfind.= '=('.$tagdata['ParamRegex'].')';
			}
			if ( $tagdata['ReplaceContent'] ) {
				$startfind.= '\\]([^\\["<>]*)/';
			} else {
				$startfind.= '\\]/';
			}
			if ( $tagdata['HasEnd'] ) {
				$ps				=	strpos( $tagdata['Name'], ' ' );
				$endfind		=	'[/' . ( $ps ? substr( $tagdata['Name'], 0, $ps ) : $tagdata['Name'] ) . ']';
				$starttags		=	preg_match_all($startfind,$text,$ignore);
				$endtags		=	substr_count($text,$endfind);
/*				if ( ( $starttags === false ) || ( $starttags != $endtags ) ) {
					continue;
				}
				$text	=	str_replace($endfind,$tagdata['HtmlEnd'],$text);
*/
/* OLD BEGIN */
				if ( $starttags !== false ) {
					if( $endtags < $starttags ) {
						$text	.=	str_repeat($endfind,$starttags - $endtags);
					}
					if ( $endtags > $starttags ) {
						$last	=	strrpos( $text, $endfind );
						$text	=	str_replace( $endfind, $tagdata['HtmlEnd'], substr( $text, 0, $last ) ) . substr( $text, $last );
					} else {
						$text	=	str_replace($endfind,$tagdata['HtmlEnd'],$text);
					}
				}
/* OLD END */
			}
			$replace = str_replace(array('%%P%%','%%p%%'), '\'.$this->onparam(\'$1\',$tagdata[\'ParamRegexReplace\']).\'', '\''.$tagdata['HtmlBegin'].'\'');
			$text = preg_replace($startfind.'e',$replace,$text);
		}
		return $text;
	}
}
?>
