<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('IS_SALLY', true);
define('IS_SALLY_BACKEND', false);

if (!defined('SLY_IS_TESTING')) define('SLY_IS_TESTING', false);

ob_start();
ob_implicit_flush(0);

// $REX vorbereiten

unset($REX);

define('SLY_HTDOCS_PATH', './');

// Core laden

require_once 'sally/core/master.inc.php';

// Setup?
if (!isset($_GET['sly_asset']) && $config->get('SETUP')) {
	header('Location: backend/index.php');
	exit('Bitte führe das <a href="backend/index.php">Setup</a> aus, um SallyCMS zu nutzen.');
}

// init i18n (TODO: This makes no sense... but addOns require the i18n object to be present)
sly_Core::setI18N(new sly_I18N($config->get('LANG'), SLY_SALLYFOLDER.'/backend/lang'));

// instantiate asset service before addons are loaded to make sure the scaffold css processing is first
$assetService = sly_Service_Factory::getAssetService();

sly_Core::loadAddons();

if ($config->get('DEVELOPER_MODE')) {
	require_once 'sally/core/functions/function_rex_generate.inc.php';

	if (!$config->get('SETUP')) {
		sly_Service_Factory::getTemplateService()->refresh();
		sly_Service_Factory::getModuleService()->refresh();
	}

	$assetService->validateCache();
}

// Asset-Processing, sofern Assets benötigt werden
$assetService->process();

// find current article
$article = sly_Util_Article::findById(sly_Core::getCurrentArticleId(), sly_Core::getCurrentClang());

// last chance to tamper with the page building process before the actual article processing starts
$article = sly_Core::dispatcher()->filter('SLY_PRE_PROCESS_ARTICLE', $article);

if ($article) {
	print $article->getArticleTemplate();
}
else {
	print 'Kein Startartikel selektiert. Bitte setze ihn im <a href="sally/index.php">Backend</a>.';
}

$content = ob_get_clean();
rex_send_article($article, $content, 'frontend');
