CREATE TABLE IF NOT EXISTS `#__imageshow_external_source_facebook` (
  `external_source_id` int(11) unsigned NOT NULL auto_increment,
  `external_source_profile_title` varchar(255) default NULL,
  `facebook_user_id` varchar(20) default '',
  `facebook_access_token` varchar(255) default '',
  `facebook_app_id` varchar(255) default '',
  `facebook_app_secret` varchar(255) default '',
  `facebook_thumbnail_size` char(30) default '144',
  `facebook_image_size` char(30) default '1024',
  PRIMARY KEY  (`external_source_id`)
) DEFAULT CHARSET=utf8;