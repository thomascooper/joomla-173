<?php
class JConfig {
	protected $time;
	function __construct() {
		$this->time = time() + 1; // dynamic assignment
	} 
	public $offline = '0';
	public $offline_message = 'This site is down for maintenance.<br /> Please check back again soon.';
	public $display_offline_message = '1';
	public $sitename = 'World Wide Interweb';
	public $editor = 'tinymce';
	public $list_limit = '20';
	public $access = '1';
	public $debug = '0';
	public $debug_lang = '0';
	public $dbtype = 'mysqli';
	public $host = 'localhost';
	public $user = 'joomla_user';
	public $password = 'CHu=ukaW?a_w';
	public $db = 'joomla_db';
	public $dbprefix = 'jom17_';
	public $live_site = '';
	public $secret = 'DvcJbwEPm3X3ccI8';
	public $gzip = '0';
	public $error_reporting = 'development';
	public $helpurl = 'http://help.joomla.org/proxy/index.php?option=com_help&keyref=Help16:{keyref}';
	public $ftp_host = '127.0.0.1';
	public $ftp_port = '21';
	public $ftp_user = '';
	public $ftp_pass = '';
	public $ftp_root = '';
	public $ftp_enable = '0';
	public $offset = 'UTC';
	public $offset_user = 'UTC';
	public $mailer = 'mail';
	public $mailfrom = 'admin@worldwideinterweb.com';
	public $fromname = 'World Wide Interweb';
	public $sendmail = '/usr/sbin/sendmail';
	public $smtpauth = '0';
	public $smtpuser = '';
	public $smtppass = '';
	public $smtphost = 'localhost';
	public $smtpsecure = 'none';
	public $smtpport = '25';
	public $caching = '0';
	public $cache_handler = 'apc';
	public $cachetime = '15';
	public $MetaDesc = '';
	public $MetaKeys = '';
	public $MetaAuthor = '1';
	public $sef = '1';
	public $sef_rewrite = '0';
	public $sef_suffix = '0';
	public $unicodeslugs = '0';
	public $feed_limit = '10';
	public $log_path = '/var/www/html/www.worldwideinterweb.com/htdocs/logs';
	public $tmp_path = '/var/www/html/www.worldwideinterweb.com/htdocs/tmp';
	public $lifetime = '15';
	public $session_handler = 'database';
	public $MetaRights = '';
	public $sitename_pagetitles = '0';
	public $force_ssl = '0';
	public $feed_email = 'author';
	public $cookie_domain = '';
	public $cookie_path = '';
}
