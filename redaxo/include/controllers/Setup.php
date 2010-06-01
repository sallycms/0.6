<?php

class sly_Controller_Setup extends sly_Controller_Sally
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
		$languages = $REX['LANGUAGES'];

		// wenn nur eine Sprache -> direkte Weiterleitung

		if (count($languages) == 1) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: index.php?subpage=license&lang='.urlencode(key($languages)));
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
		global $I18N;

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
		global $I18N;

		$config = sly_Core::config()->get(false);
		$isSent = isset($_POST['sly-submit']);

		if ($isSent) {
			$masterFile = $config['INCLUDE_PATH'].'/config/sally.yaml';
			$oldData    = sly_Configuration::load($masterFile);
			$createDB   = sly_post('create_db', 'boolean', false);

			$oldData['SERVER']               = sly_post('server', 'string');
			$oldData['SERVERNAME']           = sly_post('servername', 'string');
			$oldData['LANG']                 = $this->lang;
			$oldData['INSTNAME']             = 'sly'.date('YmdHis');
			$oldData['ERROR_EMAIL']          = sly_post('error_email', 'string');
			$oldData['PSWFUNC']              = sly_post('pwd_func', 'string');
			$oldData['DATABASE']['HOST']     = sly_post('mysql_host', 'string');
			$oldData['DATABASE']['LOGIN']    = sly_post('mysql_user', 'string');
			$oldData['DATABASE']['PASSWORD'] = sly_post('mysql_pass', 'string');
			$oldData['DATABASE']['NAME']     = sly_post('mysql_name', 'string');
			
			sly_Core::config()->appendArray($oldData);
			
			$config = sly_Core::config()->get(false);
			$dumper = new sfYamlDumper();

			if (file_put_contents($masterFile, $dumper->dump($oldData, 2)) === false) {
				$this->warning = $I18N->msg('setup_020', '<b>', '</b>');
			}
			else {
				sly_Core::config()->save();
				
				// Datenbank-Zugriff

				extract($oldData['DATABASE'], EXTR_SKIP);
				$err = rex_sql::checkDbConnection($HOST, $LOGIN, $PASSWORD, $NAME, $createDB);

				if ($err !== true) {
					$this->warning = $err;
				}
				else {
					unset($_POST['sly-submit']);
					$this->initdb();
					return;
				}
			}
		}

		$this->render('views/setup/config.phtml', array(
			'server'      => $config['SERVER'],
			'serverName'  => $config['SERVERNAME'],
			'errorEMail'  => $config['ERROR_EMAIL'],
			'pwdFunction' => $config['PSWFUNC'],
			'mysqlHost'   => $config['DATABASE']['HOST'],
			'mysqlUser'   => $config['DATABASE']['LOGIN'],
			'mysqlPass'   => $config['DATABASE']['PASSWORD'],
			'mysqlName'   => $config['DATABASE']['NAME']
		));
	}

	public function initdb()
	{
		global $I18N, $REX;

		$config         = sly_Core::config();
		$prefix         = $config->get('TABLE_PREFIX');
		$error          = '';
		$dbInitFunction = sly_post('db_init_function', 'string', '');

		// nenötigte Tabellen prüfen

		$requiredTables = array (
			$prefix.'action',
			$prefix.'article',
			$prefix.'article_slice',
			$prefix.'clang',
			$prefix.'file',
			$prefix.'file_category',
			$prefix.'module_action',
			$prefix.'module',
			$prefix.'template',
			$prefix.'user',
			$prefix.'slice',
			$prefix.'slice_value'
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

				$installScript = $REX['INCLUDE_PATH'].'/install/sally4_2.sql';

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
				if (substr($tblname, 0, strlen($prefix)) == $prefix) {
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
		global $I18N;
		
		$config      = sly_Core::config();
		$prefix      = $config->get('TABLE_PREFIX');
		$pdo         = sly_DB_Persistence::getInstance();
		$usersExist  = $pdo->listTables($prefix.'user') && $pdo->magicFetch('user', 'user_id') !== false;
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
					$userOK = $pdo->listTables($prefix.'user') && $pdo->fetch('user', 'user_id', array('login' => $adminUser)) > 0;
					
					if ($userOK) {
						$error = $I18N->msg('setup_042'); // Dieses Login existiert schon!
					}
					else {
						$adminPass = call_user_func($config->get('PSWFUNC'), $adminPass);
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
		global $I18N, $REX;
		
		$masterFile = $REX['INCLUDE_PATH'].'/config/sally.yaml';
		$oldData    = sly_Configuration::load($masterFile);
		$dumper     = new sfYamlDumper();
		
		$oldData['SETUP'] = false;

		if (file_put_contents($masterFile, $dumper->dump($oldData, 2))) {
			sly_Core::config()->save();
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
		global $REX;

		// Schreibrechte

		$s = DIRECTORY_SEPARATOR;

		$writables = array (
			$REX['INCLUDE_PATH'].$s.'generated',
			$REX['INCLUDE_PATH'].$s.'generated'.$s.'articles',
			$REX['INCLUDE_PATH'].$s.'generated'.$s.'templates',
			$REX['INCLUDE_PATH'].$s.'generated'.$s.'files',
			$REX['DATAFOLDER'],
			$REX['MEDIAFOLDER'],
			$REX['DYNFOLDER'],
			$REX['DYNFOLDER'].$s.'public',
			$REX['DYNFOLDER'].$s.'internal',
			$REX['DYNFOLDER'].$s.'internal'.$s.'sally',
			$REX['DYNFOLDER'].$s.'internal'.$s.'sally'.$s.'css-cache',
			$REX['DYNFOLDER'].$s.'internal'.$s.'sally'.$s.'yaml-cache'
		);

		foreach ($REX['SYSTEM_ADDONS'] as $system_addon) {
			$writables[] = $REX['INCLUDE_PATH'].$s.'addons'.$s.$system_addon;
		}

		$res = $this->isWritable($writables, true);

		$writables = array(
			$REX['INCLUDE_PATH'].$s.'config'.$s.'sally.yaml',
			$REX['INCLUDE_PATH'].$s.'config'.$s.'addons.yaml',
			$REX['INCLUDE_PATH'].$s.'config'.$s.'plugins.yaml',
			$REX['INCLUDE_PATH'].$s.'config'.$s.'clang.yaml'
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
		global $REX, $I18N;

		$addonErr     = '';
		$addonService = sly_Service_Factory::getService('Addon');

		foreach ($REX['SYSTEM_ADDONS'] as $systemAddon) {
			$state = true;

			if ($state === true && $uninstallBefore && !$addonService->isInstalled($systemAddon)) {
				$state = $$addonService->uninstall($systemAddon);
			}

			if ($state === true && !$addonService->isInstalled($systemAddon)) {
				$state = $addonService->install($systemAddon, $installDump);
			}

			if ($state === true && !$addonService->isActivated($systemAddon)) {
				$state = $addonService->activate($systemAddon);
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
