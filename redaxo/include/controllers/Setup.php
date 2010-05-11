<?php

class sly_Controller_Setup extends sly_Controller_Base
{
	protected $warning;
	protected $info;
	protected $lang;

	public function init()
	{
		$this->lang = sly_request('lang', 'string');
	}

	public function index()
	{
		global $SLY;

		// wenn nur eine Sprache -> direkte Weiterleitung

		if (count($SLY['LANGUAGES']) == 1) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: index.php?subpage=license&lang='.urlencode(key($SLY['LANGUAGES'])));
			exit();
		}

		$this->render('views/setup/chooselang.phtml');
	}

	public function license()
	{
		$this->render('views/setup/license.phtml');
	}

	public function fsperms()
	{
		global $I18N, $SLY;

		$errors = array();

		// Versionscheck

		if (version_compare(PHP_VERSION, '5.1.0', '<')) {
			$errors[] = $I18N->msg('setup_010', phpversion());
		}

		// Extensions prüfen

		foreach (array('session', 'mysql', 'pcre', 'pdo') as $extension) {
			if (!extension_loaded($extension)) {
				$errors[] = $I18N->msg('setup_010_1', $extension);
			}
		}

		$errorMsg = $this->checkDirsAndFiles();
		
		// Verzeichnisse schützen
		
		$protected = array('../develop', '../data/dyn/internal');
		$htaccess  = 'include/.htaccess';
		
		foreach ($protected as $directory) {
			if (is_dir($directory) && !file_exists($directory.'/.htaccess') && !copy($htaccess, $directory.'/.htaccess')) {
				$errors[] = 'Vezeichnis '.realpath($directory).' konnte nicht gegen HTTP-Zugriffe geschützt werden.';
			}
		}

		if ($errorMsg !== true) {
			$errors[] = $errorMsg;
		}

		$this->render('views/setup/fsperms.phtml', array('errors' => $errors));
	}

	public function config()
	{
		global $SLY, $I18N;

		$isSent = isset($_POST['sly-submit']);

		if ($isSent) {
			$master_file = $SLY['INCLUDE_PATH'].'/master.inc.php';
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
			$cont = preg_replace("#(REX\['LANG'\].?=.?\")[^\"]*#i",                 '$1'.$this->lang, $cont);
			$cont = preg_replace("#(REX\['INSTNAME'\].?=.?\")[^\"]*#i",             '$1'.'sly'.date('YmdHis'), $cont);
			$cont = preg_replace("#(REX\['ERROR_EMAIL'\].?=.?\")[^\"]*#i",          '$1'.$errorEMail, $cont);
			$cont = preg_replace("#(REX\['PSWFUNC'\].?=.?\")[^\"]*#i",              '$1'.$pwdFunction, $cont);
			$cont = preg_replace("#(REX\['DB'\]\['1'\]\['HOST'\].?=.?\")[^\"]*#i",  '$1'.$mysqlHost, $cont);
			$cont = preg_replace("#(REX\['DB'\]\['1'\]\['LOGIN'\].?=.?\")[^\"]*#i", '$1'.$mysqlUser, $cont);
			$cont = preg_replace("#(REX\['DB'\]\['1'\]\['PSW'\].?=.?\")[^\"]*#i",   '$1'.$mysqlPass, $cont);
			$cont = preg_replace("#(REX\['DB'\]\['1'\]\['NAME'\].?=.?\")[^\"]*#i",  '$1'.$mysqlName, $cont);

			if (file_put_contents($master_file, $cont) === false) {
				$this->warning = $I18N->msg('setup_020', '<b>', '</b>');
			}
			else {
				// Datenbank-Zugriff

				$err = rex_sql::checkDbConnection($mysqlHost, $mysqlUser, $mysqlPass, $mysqlName, $createDB);

				if ($err !== true) {
					$this->warning = $err;
				}
				else {
					$SLY['DB']['1']['HOST']  = $mysqlHost;
					$SLY['DB']['1']['LOGIN'] = $mysqlUser;
					$SLY['DB']['1']['PSW']   = $mysqlPass;
					$SLY['DB']['1']['NAME']  = $mysqlName;

					unset($_POST['sly-submit']);
					$this->initdb();
					return;
				}
			}
		}
		else {
			// Allgemeine Infos

			$server      = $SLY['SERVER'];
			$serverName  = $SLY['SERVERNAME'];
			$errorEMail  = $SLY['ERROR_EMAIL'];
			$pwdFunction = $SLY['PSWFUNC'];

			// DB-Infos

			$mysqlHost = $SLY['DB']['1']['HOST'];
			$mysqlUser = $SLY['DB']['1']['LOGIN'];
			$mysqlPass = $SLY['DB']['1']['PSW'];
			$mysqlName = $SLY['DB']['1']['NAME'];
		}

		$this->render('views/setup/config.phtml', array(
			'server'      => $server,
			'serverName'  => $serverName,
			'errorEMail'  => $errorEMail,
			'pwdFunction' => $pwdFunction,
			'mysqlHost'   => $mysqlHost,
			'mysqlUser'   => $mysqlUser,
			'mysqlPass'   => $mysqlPass,
			'mysqlName'   => $mysqlName
		));
	}

	public function initdb()
	{
		global $SLY, $I18N;

		$error          = '';
		$dbInitFunction = sly_post('db_init_function', 'string', '');

		// nenötigte Tabellen prüfen

		$requiredTables = array (
			$SLY['TABLE_PREFIX'].'action',
			$SLY['TABLE_PREFIX'].'article',
			$SLY['TABLE_PREFIX'].'article_slice',
			$SLY['TABLE_PREFIX'].'clang',
			$SLY['TABLE_PREFIX'].'file',
			$SLY['TABLE_PREFIX'].'file_category',
			$SLY['TABLE_PREFIX'].'module_action',
			$SLY['TABLE_PREFIX'].'module',
			$SLY['TABLE_PREFIX'].'template',
			$SLY['TABLE_PREFIX'].'user',
			$SLY['TABLE_PREFIX'].'slice',
			$SLY['TABLE_PREFIX'].'slice_value'
		);

		switch ($dbInitFunction) {
			case 'nop': // Datenbank schon vorhanden, nichts tun

				$error = $this->setupAddOns(true, false);
				break;

			case 'drop': // alte DB löschen

				$db = new rex_sql();

				foreach ($requiredTables as $table) {
					$db->setQuery('DROP TABLE IF EXISTS `'.$table.'`');
				}

				// kein break;

			case 'setup': // leere Datenbank neu einrichten

				$installScript = $SLY['INCLUDE_PATH'].'/install/sally4_2.sql';

				if (empty($error)) $error = $this->setupImport($installScript);
				if (empty($error)) $error = $this->setupAddOns($dbInitFunction == 'drop');

				break;

			default: // Extensions eine Chance geben

				rex_register_extension_point('SLY_SETUP_INIT_DATABASE', $dbInitFunction);

//				$importName = wv_post('import_name', 'string');
//
//				if (empty($importName)) {
//					$error = '<p>'.$I18N->msg('setup_03701').'</p>';
//				}
//				else {
//					$importSQL     = getImportDir().'/'.$import_name.'.sql';
//					$importArchive = getImportDir().'/'.$import_name.'.tar.gz';
//
//					// Nur hier zuerst die Addons installieren
//					// Da sonst Daten aus dem eingespielten Export
//					// überschrieben würden
//
//					if ($error == '')
//						$error .= rex_setup_addons(true, false);
//
//					if ($error == '')
//						$error .= rex_setup_import($import_sql, $import_archiv);
//				}
//
//				break;
		}

		// Wenn kein Fehler aufgetreten ist, aber auch etwas geändert wurde, prüfen
		// wir, ob dadurch alle benötigten Tabellen erzeugt wurden.

		if (empty($error)) {
			$existingTables = array();

			foreach (rex_sql::showTables() as $tblname) {
				if (substr($tblname, 0, strlen($SLY['TABLE_PREFIX'])) == $SLY['TABLE_PREFIX']) {
					$existingTables[] = $tblname;
				}
			}

			foreach (array_diff($requiredTables, $existingTables) as $missingTable) {
				$error .= $I18N->msg('setup_031', $missingTable).'<br />';
			}
		}

		if (empty($error)) {
			unset($_POST['sly-submit']);
			$this->createuser();
		}
		else {
			$this->warning = empty($dbInitFunction) ? '' : $error;
			$this->render('views/setup/initdb.phtml', array(
				'dbInitFunction'  => $dbInitFunction,
				'dbInitFunctions' => array('setup', 'nop', 'drop')
			));
		}
	}
	
	public function createuser()
	{
		global $SLY, $I18N;
		
		$pdo         = sly_DB_PDO_Persistence::getInstance();
		$usersExist  = $pdo->listTables($SLY['TABLE_PREFIX'].'user') && $pdo->magicFetch('user', 'user_id') !== false;
		$createAdmin = !sly_post('no_admin', 'boolean', false);
		$adminUser   = sly_post('admin_user', 'string');
		$adminPass   = sly_post('admin_pass', 'string');
		$error       = '';
		
		if (isset($_POST['sly-submit'])) {
			if ($createAdmin) {
				if (empty($adminUser)) {
					$error = $I18N->msg('setup_040');
				}

				if (empty($adminPass)) {
					if (!empty($error)) $error .= ' ';
					$error .= $I18N->msg('setup_041');
				}

				if (empty($error)) {
					$userOK = $pdo->listTables($SLY['TABLE_PREFIX'].'user') && $pdo->fetch('user', 'user_id', array('login' => $adminUser)) > 0;
					
					if ($userOK) {
						$error = $I18N->msg('setup_042'); // Dieses Login existiert schon!
					}
					else {
						$adminPass = call_user_func($SLY['PSWFUNC'], $adminPass);
						$affected  = $pdo->insert('user', array(
							'name'       => 'Administrator',
							'login'      => $adminUser,
							'psw'        => $adminPass,
							'rights'     => '#admin[]#',
							'createdate' => time(),
							'createuser' => 'setup',
							'status'     => 1
						));
						
						if ($affected == 0) {
							$error = $I18N->msg('setup_043');
						}
					}
				}
			}
			elseif (!$usersExist) {
				$error = $I18N->msg('setup_044');
			}
			
			if (empty($error)) {
				unset($_POST['sly-submit']);
				$this->finish();
				return;
			}
		}

		$this->warning = $error;
		$this->render('views/setup/createuser.phtml', array(
			'usersExist' => $usersExist,
			'adminUser'  => $adminUser,
			'adminPass'  => $adminPass
		));
	}
	
	public function finish()
	{
		global $SLY, $I18N;
		
		$master_file = $SLY['INCLUDE_PATH'].'/master.inc.php';
		$cont        = file_get_contents($master_file);
		$cont        = preg_replace("#^(\\\$REX\['SETUP'\].?=.?)[^;]*#m", '$1false', $cont);

		if (file_put_contents($master_file, $cont)) {
			$this->warning = '';
		}
		else {
			$this->warning = $I18N->msg('setup_050');
		}
		
		$this->render('views/setup/finish.phtml');
	}

	protected function title($title)
	{
		rex_title($title);
		print '<div id="rex-setup" class="rex-area">';
	}

	protected function footer()
	{
		print '</div>'; // rex_setup_title() schließen
	}

	protected function checkDirsAndFiles()
	{
		global $SLY;

		// Schreibrechte

		$s = DIRECTORY_SEPARATOR;

		$writables = array (
			$SLY['INCLUDE_PATH'].$s.'generated',
			$SLY['INCLUDE_PATH'].$s.'generated'.$s.'articles',
			$SLY['INCLUDE_PATH'].$s.'generated'.$s.'templates',
			$SLY['INCLUDE_PATH'].$s.'generated'.$s.'files',
			$SLY['DATAFOLDER'],
			$SLY['MEDIAFOLDER'],
			$SLY['DYNFOLDER'],
			$SLY['DYNFOLDER'].$s.'public',
			$SLY['DYNFOLDER'].$s.'internal'
		);

		foreach ($SLY['SYSTEM_ADDONS'] as $system_addon) {
			$writables[] = $SLY['INCLUDE_PATH'].$s.'addons'.$s.$system_addon;
		}

		$res = $this->isWritable($writables, true);

		$writables = array(
			$SLY['INCLUDE_PATH'].$s.'master.inc.php',
			$SLY['INCLUDE_PATH'].$s.'addons.inc.php',
			$SLY['INCLUDE_PATH'].$s.'plugins.inc.php',
			$SLY['INCLUDE_PATH'].$s.'clang.inc.php'
		);

		$res = array_merge($res, $this->isWritable($writables, false));

		if (!empty($res)) {
			$errors = array();

			foreach ($res as $type => $messages) {
				$error = array();

				if (!empty($messages)) {
					$error[] = _rex_is_writable_info($type);
					$error[] = '<ul>';

					foreach ($messages as $message) {
						$error[] = '<li>'.$message.'</li>';
					}

					$error[] = '</ul>';
				}

				$errors[] = implode("\n", $error);
			}

			return implode("</li><li>\n", $errors);
		}

		return true;
	}

	protected function isWritable($elements, $elementsAreDirs)
	{
		global $SLY;

		$res = array();

		foreach ($elements as $element) {
			if ($elementsAreDirs && !is_dir($element)) {
				mkdir($element, $SLY['DIRPERM']);
			}

			$writable = _rex_is_writable($element);
			if ($writable != 0) $res[$writable][] = $element;
		}

		return $res;
	}

	protected function printHiddens($func)
	{
		?>
		<input type="hidden" name="page" value="setup" />
		<input type="hidden" name="func" value="<?= sly_html($func) ?>" />
		<input type="hidden" name="lang" value="<?= sly_html($this->lang) ?>" />
		<?php
	}

	protected function setupImport($sqlScript)
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

	/**
	 * System AddOns prüfen
	 */
	protected function setupAddOns($uninstallBefore = false, $installDump = true)
	{
		global $SLY, $I18N;

		$addonErr     = '';
		$addonManager = rex_addonManager::getInstance();

		foreach ($SLY['SYSTEM_ADDONS'] as $systemAddon) {
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

	public function checkPermission()
	{
		return true;
	}
}
