<?php

/**
 * Direkter Aufruf, um zu testen, ob der Ordner redaxo/include
 * erreichbar ist. Dies darf aus Sicherheitsgründen nicht möglich sein!
 */

if (!defined('IS_SALLY')) {
	require $REX['INCLUDE_PATH'].'/views/setup/direct.phtml';
	exit();
}

/**
 * Ausgabe des Setup spezifischen Titels
 */
function rex_setup_title($title)
{
	rex_title($title);
	print '<div id="rex-setup" class="rex-area">';
}

function rex_setup_import($sqlScript)
{
	global $I18N;

	$err_msg = '';

	if (file_exists($sqlScript)) {
		$importer = new sly_DB_Importer();
		$result   = $importer->import($sqlScript);

		if ($result['state'] === false) {
			$err_msg = nl2br($result['message']) .'<br />';
		}
	}
	else {
		$err_msg = $I18N->msg('setup_03702').'<br />';
	}

	return $err_msg;
}

function rex_setup_is_writable($elements, $elementsAreDirs)
{
	global $REX;
	
	$res = array();

	foreach ($elements as $element) {
		if ($elementsAreDirs && !is_dir($element)) {
			mkdir($element, $REX['DIRPERM']);
		}
		
		$writable = _rex_is_writable($element);
		if ($writable != 0) $res[$writable][] = $element;
	}

	return $res;
}

/**
 * System AddOns prüfen
 */
function rex_setup_addons($uninstallBefore = false, $installDump = true)
{
	global $REX, $I18N;

	$addonErr     = '';
	$addonManager = rex_addonManager::getInstance();

	foreach ($REX['SYSTEM_ADDONS'] as $systemAddon) {
		$state = true;

		if ($state === true && $uninstallBefore && !OOAddon::isInstalled($systemAddon)) {
			$state = $addonManager->uninstall($systemAddon);
		}
		 
		if ($state === true && !OOAddon::isInstalled($systemAddon)) {
			$state = $addonManager->install($systemAddon, $installDump);
		}

		if ($state === true && !OOAddon::isActivated($systemAddon)) {
			$state = $addonManager->activate($systemAddon);
		}

		if ($state !== true) {
			$addonErr .= '<li>'.$systemAddon.'<ul><li>'.$state.'</li></ul></li>';
		}
	}

	if (!empty($addonErr)) {
		$addonErr = '
<ul class="rex-ul1">
	<li>
		<h3 class="rex-hl3">'.$I18N->msg('setup_011', '<span class="rex-error">', '</span>').'</h3>
		<ul>'.$addonErr.'</ul>
	</li>
</ul>';
	}
	
	return $addonErr;
}

function rex_print_hiddens($checkmodus, $lang)
{
	?>
	<input type="hidden" name="page" value="setup" />
	<input type="hidden" name="checkmodus" value="<?= $checkmodus ?>" />
	<input type="hidden" name="send" value="1" />
	<input type="hidden" name="lang" value="<?= $lang ?>" />
	<?php
}

$MSG['err'] = '';

$checkmodus = sly_request('checkmodus', 'float');
$send       = sly_request('send', 'string');
$dbanlegen  = sly_request('dbanlegen', 'string');
$noadmin    = sly_request('noadmin', 'string');
$lang       = sly_request('lang', 'string');

// MODUS 0 | Start

if ($checkmodus <= 0 || $checkmodus > 10) {
	$langpath = $REX['INCLUDE_PATH'].'/lang';
	
	foreach ($REX['LANGUAGES'] as $l) {
		$isUTF8 = substr($l, -4) == 'utf8';
		$i18n   = rex_create_lang($l, $langpath, false);
		$label  = $i18n->msg('lang');
		
		if ($isUTF8) $label .= ' (UTF-8)';
		
		$langs[$l] = '<li><a href="index.php?checkmodus=0.5&amp;lang='.urlencode($l).'">'.$label.'</a></li>';
	}
	
	unset($i18n);

	// wenn nur eine Sprache -> direkte Weiterleitung
	
	if (count($REX['LANGUAGES']) == 1) {
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: index.php?checkmodus=0.5&lang='.urlencode(key($langs)));
		exit();
	}

	rex_setup_title('SETUP: SPRACHAUSWAHL');

	print '
<h2 class="rex-hl2">Please choose a language!</h2>
	<div class="rex-area-content">
	<ul class="rex-setup-language">'.implode('', $langs).'</ul>
</div>';
}

// MODUS 0 | Start

if ($checkmodus == '0.5') {
	rex_setup_title('SETUP: START');

	$REX['LANG'] = $lang;

	print $I18N->msg('setup_005', '<h2 class="rex-hl2">', '</h2>');
	print '<div class="rex-area-content">';

	print $I18N->msg('setup_005_1', '<h3 class="rex-hl3">', '</h3>', ' class="rex-ul1"');
	print '<div class="rex-area-scroll">';

	$basedir      = dirname(__FILE__);
	$license_file = $basedir.'/../../../_lizenz.txt';
	$license      = '<p class="rex-tx1">'.nl2br(file_get_contents($license_file)).'</p>';

	print strpos($REX['LANG'], 'utf') === false ? $license : utf8_encode($license);;
	print '</div>
	</div>
<div class="rex-area-footer">
	<p class="rex-algn-rght"><a href="index.php?page=setup&amp;checkmodus=1&amp;lang='.$lang.'">&raquo; '.$I18N->msg('setup_006').'</a></p>
</div>';

	$checkmodus = 0;
}

// MODUS 1 | Versionscheck - Rechtecheck

if ($checkmodus == 1) {

	// Versionscheck
	
	if (version_compare(PHP_VERSION, '5.1.0', '<')) {
		$MSG['err'] .= '<li>'. $I18N->msg('setup_010', phpversion()).'</li>';
	}

	// Extensions prüfen
	
	foreach (array('session', 'mysql', 'pcre', 'pdo') as $extension) {
		if (!extension_loaded($extension)) {
			$MSG['err'] .= '<li>'.$I18N->msg('setup_010_1', $extension).'</li>';
		}
	}

	// Schreibrechte
	
	$s = DIRECTORY_SEPARATOR;
	
	$writables = array (
		$REX['INCLUDE_PATH'].$s.'generated',
		$REX['INCLUDE_PATH'].$s.'generated'.$s.'articles',
		$REX['INCLUDE_PATH'].$s.'generated'.$s.'templates',
		$REX['INCLUDE_PATH'].$s.'generated'.$s.'files',
		$REX['MEDIAFOLDER'],
		$REX['DYNFOLDER'],
		$REX['DYNFOLDER'].$s.'public',
		$REX['DYNFOLDER'].$s.'public'.$s.'addons',
		$REX['DYNFOLDER'].$s.'internal',
		$REX['DYNFOLDER'].$s.'internal'.$s.'addons'
	);

	foreach ($REX['SYSTEM_ADDONS'] as $system_addon) {
		$writables[] = $REX['INCLUDE_PATH'].$s.'addons'.$s.$system_addon;
	}

	$res = rex_setup_is_writable($writables, true);

	$writables = array (
		$REX['INCLUDE_PATH'].$s.'master.inc.php',
		$REX['INCLUDE_PATH'].$s.'addons.inc.php',
		$REX['INCLUDE_PATH'].$s.'plugins.inc.php',
		$REX['INCLUDE_PATH'].$s.'clang.inc.php'
	);

	$res = array_merge($res, rex_setup_is_writable($writables, false));
	
	if (!empty($res)) {
		$MSG['err'] .= '<li>';
		
		foreach ($res as $type => $messages) {
			if (!empty($messages)) {
				$MSG['err'] .= '<h3 class="rex-hl3">'._rex_is_writable_info($type).'</h3>';
				$MSG['err'] .= '<ul>';
				
				foreach($messages as $message) {
					$MSG['err'] .= '<li>'.$message.'</li>';
				}
				
				$MSG['err'] .= '</ul>';
			}
		}
		
		$MSG['err'] .= '</li>';
	}
}

if (empty($MSG['err']) && $checkmodus == 1) {
	rex_setup_title($I18N->msg('setup_step1'));

	print $I18N->msg('setup_016', '<h2 class="rex-hl2">', '</h2>');
	print '<div class="rex-area-content">';

	print $I18N->msg('setup_016_1', ' class="rex-ul1"', '<span class="rex-ok">', '</span>');
	
	?>
	<div class="rex-message">
		<p class="rex-warning" id="security_warning" style="display:none"><span><?= $I18N->msg('setup_security_msg') ?></span></p>
	</div>

	<noscript>
		<div class="rex-message">
			<p class="rex-warning"><span><?= $I18N->msg('setup_no_js_security_msg') ?></span></p>
		</div>
	</noscript>

	<iframe src="include/pages/setup.inc.php?page=setup&amp;checkmodus=1.5&amp;lang=<?= $lang ?>" style="display:none"></iframe>
</div>

<div class="rex-area-footer">
	<p id="nextstep" class="rex-algn-rght">
		<a href="index.php?page=setup&amp;checkmodus=2&amp;lang=<?= $lang ?>">&raquo; <?= $I18N->msg('setup_017') ?></a>
	</p>
</div>
	<?php
}
elseif (!empty($MSG['err'])) {
	rex_setup_title($I18N->msg('setup_step1'));
	?>
<h2 class="rex-hl2"><?= $I18N->msg('setup_headline1') ?></h2>

<div class="rex-area-content">
	<ul class="rex-ul1"><?= $MSG['err'] ?></ul>
	<p class="rex-tx1"> <?= $I18N->msg('setup_018') ?></p>
</div>

<div class="rex-area-footer">
	<p class="rex-algn-rght">
		<a href="index.php?page=setup&amp;checkmodus=1&amp;lang=<?= $lang ?>">&raquo; <?= $I18N->msg('setup_017') ?></a>
	</p>
</div>
	<?php
}

// MODUS 2 | master.inc.php - Datenbankcheck

if ($checkmodus == 2 && $send == 1) {
	$master_file = $REX['INCLUDE_PATH'].'/master.inc.php';
	$cont        = file_get_contents($master_file);
	
	$server      = addcslashes(sly_post('server', 'string'), '"');
	$serverName  = addcslashes(sly_post('servername', 'string'), '"');
	$errorEMail  = addcslashes(sly_post('error_email', 'string'), '"');
	$pwdFunction = addcslashes(sly_post('pwd_func', 'string', 'sha1'), '"');
	$mysqlHost   = addcslashes(sly_post('mysql_host', 'string'), '"');
	$mysqlUser   = addcslashes(sly_post('mysql_user', 'string'), '"');
	$mysqlPass   = addcslashes(sly_post('mysql_pass', 'string'), '"');
	$mysqlName   = addcslashes(sly_post('mysql_name', 'string'), '"');
	$createDB    = sly_post('create_db', 'boolean', false);

	$cont = preg_replace("#(REX\['SERVER'\].?=.?\")[^\"]*#i",               '$1'.$server, $cont);
	$cont = preg_replace("#(REX\['SERVERNAME'\].?=.?\")[^\"]*#i",           '$1'.$serverName, $cont);
	$cont = preg_replace("#(REX\['LANG'\].?=.?\")[^\"]*#i",                 '$1'.$lang, $cont);
	$cont = preg_replace("#(REX\['INSTNAME'\].?=.?\")[^\"]*#i",             '$1'.'sly'.date('YmdHis'), $cont);
	$cont = preg_replace("#(REX\['ERROR_EMAIL'\].?=.?\")[^\"]*#i",          '$1'.$errorEMail, $cont);
	$cont = preg_replace("#(REX\['PSWFUNC'\].?=.?\")[^\"]*#i",              '$1'.$pwdFunction, $cont);
	$cont = preg_replace("#(REX\['DB'\]\['1'\]\['HOST'\].?=.?\")[^\"]*#i",  '$1'.$mysqlHost, $cont);
	$cont = preg_replace("#(REX\['DB'\]\['1'\]\['LOGIN'\].?=.?\")[^\"]*#i", '$1'.$mysqlUser, $cont);
	$cont = preg_replace("#(REX\['DB'\]\['1'\]\['PSW'\].?=.?\")[^\"]*#i",   '$1'.$mysqlPass, $cont);
	$cont = preg_replace("#(REX\['DB'\]\['1'\]\['NAME'\].?=.?\")[^\"]*#i",  '$1'.$mysqlName, $cont);

	if (rex_put_file_contents($master_file, $cont) === false) {
		$err_msg = $I18N->msg('setup_020', '<b>', '</b>');
	}

	// Datenbank-Zugriff
	
	$err = rex_sql::checkDbConnection($mysqlHost, $mysqlUser, $mysqlPass, $mysqlName, $createDB);
	
	if ($err !== true) {
		$err_msg = $err;
	}
	else {
		$REX['DB']['1']['HOST']  = $mysqlHost;
		$REX['DB']['1']['LOGIN'] = $mysqlUser;
		$REX['DB']['1']['PSW']   = $mysqlPass;
		$REX['DB']['1']['NAME']  = $mysqlName;

		$err_msg    = '';
		$checkmodus = 3;
		$send       = '';
	}
}
else {
	// Allgemeine Infos
	
	$server      = $REX['SERVER'];
	$serverName  = $REX['SERVERNAME'];
	$errorEMail  = $REX['ERROR_EMAIL'];
	$pwdFunction = $REX['PSWFUNC'];

	// DB-Infos
	
	$mysqlHost = $REX['DB']['1']['HOST'];
	$mysqlUser = $REX['DB']['1']['LOGIN'];
	$mysqlPass = $REX['DB']['1']['PSW'];
	$mysqlName = $REX['DB']['1']['NAME'];
}

if ($checkmodus == 2) {
	rex_setup_title($I18N->msg('setup_step2'));

	?>
<h2 class="rex-hl2"><?= $I18N->msg('setup_023') ?></h2>

<div class="rex-form" id="rex-form-setup-step-2">
	<form action="index.php" method="post">
		<fieldset class="rex-form-col-1">
			<?= rex_print_hiddens(2, $lang) ?>
	<?php

	if (!empty($err_msg)) {
		print rex_warning($err_msg);
	}

	$pwdFunctions = '';
	
	foreach (array('md5', 'sha1') as $algo) {
		$algoText = $I18N->msg('setup_'.$algo.'_encryption');
		$selected = $algo == $pwdFunction ? ' selected="selected"' : '';

		$pwdFunctions .= '<option value="'.$algo.'"'.$selected.'>'.$algoText.'</option>';
	}

	?>
			<legend><?= $I18N->msg('setup_0201') ?></legend>

			<div class="rex-form-wrapper">
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="server"><?= $I18N->msg('setup_024') ?>:</label>
						<input class="rex-form-text" type="text" id="server" name="server" value="<?= sly_html($server) ?>" />
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="servername"><?= $I18N->msg('setup_025') ?>:</label>
						<input class="rex-form-text" type="text" id="servername" name="servername" value="<?= sly_html($serverName) ?>" />
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="error_email"><?= $I18N->msg('setup_026') ?>:</label>
						<input class="rex-form-text" type="text" id="error_email" name="error_email" value="<?= sly_html($errorEMail) ?>" />
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-select">
						<label for="pwd_func"><?= $I18N->msg('setup_encryption') ?>:</label>
						<select class="rex-form-select" id="pwd_func" name="pwd_func">
						<?= $pwdFunctions ?>
						</select>
					</p>
				</div>
			</div>
		</fieldset>

		<fieldset class="rex-form-col-1">
			<legend><?= $I18N->msg('setup_0202') ?></legend>
			
			<div class="rex-form-wrapper">
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="mysql_host">MySQL-Host:</label>
						<input class="rex-form-text" type="text" id="mysql_host" name="mysql_host" value="<?= sly_html($mysqlHost) ?>" />
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="mysql_user">MySQL-Login:</label>
						<input class="rex-form-text" type="text" id="mysql_user" name="mysql_user" value="<?= sly_html($mysqlUser) ?>" />
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="mysql_pass"><?= $I18N->msg('setup_028') ?>:</label>
						<input class="rex-form-text" type="text" id="mysql_pass" name="mysql_pass" value="<?= sly_html($mysqlPass) ?>" />
					</p>
				</div>
				
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="mysql_name"><?= $I18N->msg('setup_027') ?>:</label>
						<input class="rex-form-text" type="text" id="mysql_name" name="mysql_name" value="<?= sly_html($mysqlName) ?>" />
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-checkbox">
						<label for="create_db"><?= $I18N->msg('setup_create_db') ?>:</label>
						<input class="rex-form-checkbox" type="checkbox" id="create_db" name="create_db" value="1" />
					</p>
				</div>
			</div>
		</fieldset>

		<fieldset class="rex-form-col-1">
			<div class="rex-form-wrapper">
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-submit">
						<input class="rex-form-submit" type="submit" value="<?= $I18N->msg('setup_029') ?>" />
					</p>
				</div>
			</div>
		</fieldset>
	</form>
</div>

<script type="text/javascript">
<!--
jQuery(function($) {
	$('#server').focus();
});
//-->
</script>
	<?php
}

// MODUS 3 | Datenbank anlegen

$dbInitFunctions = array('setup', 'nop', 'drop');

if ($checkmodus == 3 && $send == 1) {
	$err_msg        = '';
	$dbInitFunction = rex_post('db_init_function', 'string', 'import');

	// nenötigte Tabellen prüfen
	
	$requiredTables = array (
		$REX['TABLE_PREFIX'].'action',
		$REX['TABLE_PREFIX'].'article',
		$REX['TABLE_PREFIX'].'article_slice',
		$REX['TABLE_PREFIX'].'clang',
		$REX['TABLE_PREFIX'].'file',
		$REX['TABLE_PREFIX'].'file_category',
		$REX['TABLE_PREFIX'].'module_action',
		$REX['TABLE_PREFIX'].'module',
		$REX['TABLE_PREFIX'].'template',
		$REX['TABLE_PREFIX'].'user',
		$REX['TABLE_PREFIX'].'slice',
		$REX['TABLE_PREFIX'].'slice_value'
	);

	switch ($dbInitFunction) {
		case 'nop': // Datenbank schon vorhanden, nichts tun
		
			$err_msg = rex_setup_addons(true, false);
			break;
		
		case 'drop': // alte DB löschen
		
			$db = new rex_sql();
			
			foreach ($requiredTables as $table) {
				$db->setQuery('DROP TABLE IF EXISTS `'.$table.'`');
			}
			
			// kein break;
		
		case 'setup': // leere Datenbank neu einrichten
			
			$installScript = $REX['INCLUDE_PATH'].'/install/sally4_2.sql';

			if (empty($err_msg)) $err_msg = rex_setup_import($installScript);
			if (empty($err_msg)) $err_msg = rex_setup_addons($dbInitFunction == 'drop');
			
			break;
		
		default: // Extensions eine Chance geben
		
			rex_register_extension_point('SLY_SETUP_INIT_DATABASE', $dbInitFunction);
		
//			$importName = wv_post('import_name', 'string');
//
//			if (empty($importName)) {
//				$err_msg = '<p>'.$I18N->msg('setup_03701').'</p>';
//			}
//			else {
//				$importSQL     = getImportDir().'/'.$import_name.'.sql';
//				$importArchive = getImportDir().'/'.$import_name.'.tar.gz';
//
//				// Nur hier zuerst die Addons installieren
//				// Da sonst Daten aus dem eingespielten Export
//				// überschrieben würden
//				
//				if ($err_msg == '')
//					$err_msg .= rex_setup_addons(true, false);
//				
//				if ($err_msg == '')
//					$err_msg .= rex_setup_import($import_sql, $import_archiv);
//			}
//			
//			break;
	}
	
	// Wenn kein Fehler aufgetreten ist, aber auch etwas geändert wurde, prüfen
	// wir, ob dadurch alle benötigten Tabellen erzeugt wurden.

	if (empty($err_msg) && !empty($dbInitFunction)) {
		$existingTables = array();
		
		foreach (rex_sql::showTables() as $tblname) {
			if (substr($tblname, 0, strlen($REX['TABLE_PREFIX'])) == $REX['TABLE_PREFIX']) {
				$existingTables[] = $tblname;
			}
		}

		foreach (array_diff($requiredTables, $existingTables) as $missingTable) {
			$err_msg .= $I18N->msg('setup_031', $missingTable).'<br />';
		}
	}

	if (empty($err_msg)) {
		$send       = '';
		$checkmodus = 4;
	}
}

if ($checkmodus == 3) {
	$dbInitFunction = rex_post('db_init_function', 'switch', 'import');

	rex_setup_title($I18N->msg('setup_step3'));

	?>
<div class="rex-form rex-form-setup-step-database">
	<form action="index.php" method="post">
		<fieldset class="rex-form-col-1">
			<?= rex_print_hiddens(3, $lang) ?>
			<legend><?= $I18N->msg('setup_030_headline') ?></legend>
			<div class="rex-form-wrapper">
	<?php

	if (!empty($err_msg)) {
		print rex_warning($err_msg.'<br />'.$I18N->msg('setup_033'));
	}

	$dbInitFunction = sly_post('db_init_function', 'string', 'setup');

//	// Vorhandene Exporte auslesen
//	$sel_export = new rex_select();
//	$sel_export->setName('import_name');
//	$sel_export->setId('import_name');
//	$sel_export->setStyle('class="rex-form-select"');
//	$sel_export->setAttribute('onclick', 'checkInput(\'dbanlegen_3\')');
//	$export_dir = getImportDir();
//	$exports_found = false;
//
//	if (is_dir($export_dir)) {
//		if ($handle = opendir($export_dir))
//		{
//		$export_archives = array ();
//		$export_sqls = array ();
//
//		while (($file = readdir($handle)) !== false)
//		{
//		if ($file == '.' || $file == '..')
//		{
//		continue;
//		}
//
//		$isSql = (substr($file, strlen($file) - 4) == '.sql');
//		$isArchive = (substr($file, strlen($file) - 7) == '.tar.gz');
//
//		if ($isSql)
//		{
//		// endung .sql abschneiden
//		$export_sqls[] = substr($file, 0, -4);
//		$exports_found = true;
//		}
//		elseif ($isArchive)
//		{
//		// endung .tar.gz abschneiden
//		$export_archives[] = substr($file, 0, -7);
//		$exports_found = true;
//		}
//		}
//		closedir($handle);
//		}
//
//		foreach ($export_sqls as $sql_export)
//		{
//		// Es ist ein Export Archiv + SQL File vorhanden
//		if (in_array($sql_export, $export_archives))
//		{
//		$sel_export->addOption($sql_export, $sql_export);
//		}
//		}
//	}

	$checks = array();
	
	foreach ($dbInitFunctions as $func) {
		$checks[$func] = $dbInitFunction == $func ? ' checked="checked"' : '';
	}
	
	?>
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-radio rex-form-label-right">
						<input class="rex-form-radio" type="radio" id="func_etup" name="db_init_function" value="setup"<? $checks['setup'] ?> />
						<label for="func_etup"><?= $I18N->msg('setup_034') ?></label>
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-radio rex-form-label-right">
						<input class="rex-form-radio" type="radio" id="func_drop" name="db_init_function" value="drop"<? $checks['drop'] ?> />
						<label for="func_drop"><?= $I18N->msg('setup_035', '<b>', '</b>') ?></label>
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-radio rex-form-label-right">
						<input class="rex-form-radio" type="radio" id="func_nop" name="db_init_function" value="nop"<? $checks['nop'] ?> />
						<label for="func_nop"><?= $I18N->msg('setup_036') ?></label>
					</p>
				</div>
				
				<? rex_register_extension_point('SLY_SETUP_INIT_FUNCTIONS_FORM', $dbInitFunction); ?>

				<?php
				/*
				if ($exports_found) {
					print '
					<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-radio rex-form-label-right">
					<input class="rex-form-radio" type="radio" id="dbanlegen_3" name="dbanlegen" value="3"'.$dbchecked[3] .' />
					<label for="dbanlegen_3">'.$I18N->msg('setup_037').'</label>
					</p>
					<p class="rex-form-col-a rex-form-select rex-form-radio-select">'. $sel_export->get() .'</p>
					</div>';
				}
				*/
				?>
			
			</div>
		</fieldset>
		
		<fieldset class="rex-form-col-1">
			<div class="rex-form-wrapper">
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-submit">
						<input class="rex-form-submit" type="submit" value="<?= $I18N->msg('setup_039') ?>" />
					</p>
				</div>
			</div>
		</fieldset>
	</form>
</div>
	<?php
}

// MODUS 4 | User anlegen ...

if ($checkmodus == 4) {
	$usersExist = new rex_sql();
	$usersExist->setQuery('SELECT user_id FROM '.$REX['TABLE_PREFIX'].'user WHERE 1 LIMIT 1');
	$usersExist = $usersExist->getRows() != 0;
}

if ($checkmodus == 4 && $send == 1) {
	$createAdmin = !sly_post('no_admin', 'boolean', false);
	$adminUser   = sly_post('admin_user', 'string');
	$adminPass   = sly_post('admin_pass', 'string');
	$err_msg     = '';
	
	if ($createAdmin) {
		if (empty($adminUser)) {
			$err_msg = $I18N->msg('setup_040');
		}

		if (empty($adminPass)) {
			if (!empty($err_msg)) $err_msg .= ' ';
			$err_msg .= $I18N->msg('setup_041');
		}

		if (empty($err_msg)) {
			$findUser = new rex_sql();
			$findUser->setQuery('SELECT user_id FROM '.$REX['TABLE_PREFIX'].'user WHERE login = "'.mysql_real_escape_string($adminUser).'" LIMIT 1');

			if ($findUser->getRows() > 0) {
				$err_msg = $I18N->msg('setup_042');
			}
			else {
				$adminPass = call_user_func($REX['PSWFUNC'], $adminPass);

				$user = new rex_sql();
				$user->setTable($REX['TABLE_PREFIX'].'user');
				$user->setValue('name', 'Administrator');
				$user->setValue('login', mysql_real_escape_string($adminUser));
				$user->setValue('psw', mysql_real_escape_string($adminPass));
				$user->setValue('rights', '#admin[]#');
				$user->addGlobalCreateFields('setup');
				$user->setValue('status', '1');
				
				if (!$user->insert()) {
					$err_msg = $I18N->msg('setup_043');
				}
			}
		}
	}
	elseif (!$usersExist) {
		$err_msg = $I18N->msg('setup_044');
	}

	if (empty($err_msg)) {
		$checkmodus = 5;
		$send       = '';
	}
}

if ($checkmodus == 4) {
	rex_setup_title($I18N->msg('setup_step4'));
	?>

<div class="rex-form rex-form-setup-admin">
	<form action="index.php" method="post" autocomplete="off">
		<fieldset class="rex-form-col-1">
			<?= rex_print_hiddens(4, $lang) ?>
			<legend><?= $I18N->msg('setup_045') ?></legend>
			<div class="rex-form-wrapper">
	
	<?php

	if (!empty($err_msg)) {
		print rex_warning($err_msg);
	}

	$adminUser = sly_post('admin_user', 'string');
	$adminPass = sly_post('admin_pass', 'string');
	
	?>
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="admin_user"><?= $I18N->msg('setup_046') ?>:</label>
						<input class="rex-form-text" type="text" value="<?= sly_html($adminUser) ?>" id="admin_user" name="admin_user" />
					</p>
				</div>
				
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-text">
						<label for="admin_pass"><?= $I18N->msg('setup_047') ?>:</label>
						<input class="rex-form-text" type="password" value="" id="admin_pass" name="admin_pass" />
					</p>
				</div>
				
				<? if (!$usersExist): ?>
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-checkbox rex-form-label-right">
						<input class="rex-form-checkbox" type="checkbox" id="no_admin" name="no_admin" value="1" />
						<label for="no_admin"><?= $I18N->msg('setup_048') ?></label>
					</p>
				</div>
				<? endif ?>
			</div>
		</fieldset>
		
		<fieldset class="rex-form-col-1">
			<div class="rex-form-wrapper">
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-submit">
						<input class="rex-form-submit" type="submit" value="<?= $I18N->msg('setup_049') ?>" />
					</p>
				</div>
			</div>
		</fieldset>
	</form>
</div>

<script type="text/javascript">
<!--
jQuery(function($) {
	$('#admin_user').focus();
});
//-->
</script>
	<?php
}

// MODUS 5 | Setup verschieben ...

if ($checkmodus == 5) {
	$master_file = $REX['INCLUDE_PATH'].'/master.inc.php';
	$cont        = file_get_contents($master_file);
	$cont        = preg_replace("#(REX\['SETUP'\].?=.?)[^;]*#s", '$1false', $cont);

	if (rex_put_file_contents($master_file, $cont)) {
		$errmsg = '';
	}
	else {
		$errmsg = $I18N->msg('setup_050');
	}

	rex_setup_title($I18N->msg('setup_step5'));
	
	print $I18N->msg('setup_051', '<h2 class="rex-hl2">', '</h2>');
	print '<div class="rex-area-content">';
	print $I18N->msg('setup_052', '<h3 class="rex-hl3">', '</h3>', ' class="rex-ul1"', '<a href="index.php">', '</a>');
	print '<p class="rex-tx1">'.$I18N->msg('setup_053').'</p>';
	print '</div>';
}

print '</div>'; // rex_setup_title() schließen
