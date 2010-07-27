## Redaxo Database Dump Version 0
## Prefix sly_

CREATE TABLE `sly_action` ( `id` int(11) NOT NULL  auto_increment, `name` varchar(255) NOT NULL  , `preview` text NULL  , `presave` text NULL  , `postsave` text NULL  , `previewmode` tinyint(4) NULL  , `presavemode` tinyint(4) NULL  , `postsavemode` tinyint(4) NULL  , `createuser` varchar(255) NOT NULL  , `createdate` int(11) NOT NULL  , `updateuser` varchar(255) NOT NULL  , `updatedate` int(11) NOT NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`id`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_article` ( `pid` int(11) NOT NULL  auto_increment, `id` int(11) NOT NULL  , `re_id` int(11) NOT NULL  , `name` varchar(255) NOT NULL  , `catname` varchar(255) NOT NULL  , `catprior` int(11) NOT NULL  , `attributes` text NOT NULL  , `startpage` tinyint(1) NOT NULL  , `prior` int(11) NOT NULL  , `path` varchar(255) NOT NULL  , `status` tinyint(1) NOT NULL  , `createdate` int(11) NOT NULL  , `updatedate` int(11) NOT NULL  , `template_id` int(11) NOT NULL  , `clang` int(11) NOT NULL  , `createuser` varchar(255) NOT NULL  , `updateuser` varchar(255) NOT NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`pid`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_article_slice` ( `id` int(11) NOT NULL  auto_increment, `clang` int(11) NOT NULL  , `ctype` int(11) NOT NULL  , `re_article_slice_id` int(11) NOT NULL  ,`slice_id` bigint(20) NOT NULL DEFAULT '0',`article_id` int(11) NOT NULL  , `modultyp_id` int(11) NOT NULL  , `createdate` int(11) NOT NULL  , `updatedate` int(11) NOT NULL  , `createuser` varchar(255) NOT NULL  , `updateuser` varchar(255) NOT NULL  , `next_article_slice_id` int(11) NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`id`,`re_article_slice_id`,`article_id`,`modultyp_id`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_clang` ( `id` int(11) NOT NULL  , `name` varchar(255) NOT NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`id`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_file` ( `file_id` int(11) NOT NULL  auto_increment, `re_file_id` int(11) NOT NULL  , `category_id` int(11) NOT NULL  , `attributes` text NULL  , `filetype` varchar(255) NULL  , `filename` varchar(255) NULL  , `originalname` varchar(255) NULL  , `filesize` varchar(255) NULL  , `width` int(11) NULL  , `height` int(11) NULL  , `title` varchar(255) NULL  , `createdate` int(11) NOT NULL  , `updatedate` int(11) NOT NULL  , `createuser` varchar(255) NOT NULL  , `updateuser` varchar(255) NOT NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`file_id`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_file_category` ( `id` int(11) NOT NULL  auto_increment, `name` varchar(255) NOT NULL  , `re_id` int(11) NOT NULL  , `path` varchar(255) NOT NULL  , `createdate` int(11) NOT NULL  , `updatedate` int(11) NOT NULL  , `createuser` varchar(255) NOT NULL  , `updateuser` varchar(255) NOT NULL  , `attributes` text NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`id`,`name`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_module` ( `id` int(11) NOT NULL  auto_increment, `name` varchar(255) NOT NULL  , `category_id` int(11) NOT NULL  , `ausgabe` text NOT NULL  , `eingabe` text NOT NULL  , `createuser` varchar(255) NOT NULL  , `updateuser` varchar(255) NOT NULL  , `createdate` int(11) NOT NULL  , `updatedate` int(11) NOT NULL  , `attributes` text NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`id`,`category_id`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_module_action` ( `id` int(11) NOT NULL  auto_increment, `module_id` int(11) NOT NULL  , `action_id` int(11) NOT NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`id`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_template` ( `id` int(11) NOT NULL  auto_increment, `label` varchar(255) NULL  , `name` varchar(255) NULL  , `content` text NULL  , `active` tinyint(1) NULL  , `createuser` varchar(255) NOT NULL  , `updateuser` varchar(255) NOT NULL  , `createdate` int(11) NOT NULL  , `updatedate` int(11) NOT NULL  , `attributes` text NULL  , `revision` int(11) NOT NULL  , PRIMARY KEY (`id`)) TYPE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `sly_user` ( `id` int(11) NOT NULL auto_increment, `name` varchar(255) , `description` text , `login` varchar(50) NOT NULL  , `psw` varchar(50) NOT NULL  , `status` varchar(5) NOT NULL  , `rights` text NOT NULL  , `login_tries` tinyint(4) DEFAULT 0 , `createuser` varchar(255) NOT NULL  , `updateuser` varchar(255) NOT NULL  , `createdate` int(11) NOT NULL , `updatedate` int(11) NOT NULL , `lasttrydate` int(11) DEFAULT 0 , `session_id` varchar(255) , `cookiekey` varchar(255) , `revision` int(11) NOT NULL, PRIMARY KEY(`id`))TYPE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `sly_clang` VALUES ('0','deutsch', 0);

CREATE TABLE `sly_slice` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `module_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_slice_value` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `slice_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `finder` varchar(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `slice_id` (`slice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `sly_registry` (`name` varchar(255) NOT NULL UNIQUE , `value` mediumtext ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
