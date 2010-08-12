<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Setup extends sly_Controller_Sally {
	protected $warning;
	protected $info;
	protected $lang;

	protected function init() {
		$this->lang = sly_request('lang', 'string');
		$layout = sly_Core::getLayout();
		$layout->appendToTitle(t('setup'));
	}

	public function index()	{
		global $REX;

		$languages = $REX['LANGUAGES'];

		// wenn nur eine Sprache -> direkte Weiterleitung

		if (count($languages) == 1) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: index.php?subpage=license&lang='.urlencode(key($languages)));
			exit();
		}

		$this->render('views/setup/chooselang.phtml');
	}

	protected function license() {
		$this->render('views/setup/license.phtml');
	}

	protected function fsperms() {
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
			if (
				is_dir($directory) &&
				!file_exists($directory.'/.htaccess') &&
				!copy($htaccess, $directory.'/.htaccess') &&
				!chmod($directory.'/.htaccess', 0777)
			) {
				$errors[] = 'Vezeichnis '.realpath($directory).' konnte nicht gegen HTTP-Zugriffe geschützt werden.';
			}
		}

		if ($errorMsg !== true) {
			$errors[] = $errorMsg;
		}

		$this->render('views/setup/fsperms.phtml', array('errors' => $errors));
	}

	protected function dbconfig() {
		$config = sly_Core::config();
		$data   = $config->get('DATABASE');
		$isSent = isset($_POST['sly-submit']);

		if ($isSent) {
			$data['TABLE_PREFIX'] = sly_post('prefix', 'string');
			$data['HOST']         = sly_post('host', 'string');
			$data['LOGIN']        = sly_post('user', 'string');
			$data['PASSWORD']     = sly_post('pass', 'string');
			$data['NAME']         = sly_post('dbname', 'string');
			$data['DRIVER']       = sly_post('driver', 'string');
			$createDatabase       = sly_post('create_db', 'bool');

			try {
				if ($createDatabase && $data['DRIVER'] != 'sqlite') {
					$db = new sly_DB_PDO_Persistence($data['DRIVER'], $data['HOST'], $data['LOGIN'], $data['PASSWORD']);
					$db->query('CREATE DATABASE `'.$data['NAME'].'` DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci');
				}
				else {
					$db = new sly_DB_PDO_Persistence($data['DRIVER'], $data['HOST'], $data['LOGIN'], $data['PASSWORD'], $data['NAME']);
				}

				$config->setLocal('DATABASE', $data);
				unset($_POST['sly-submit']);
				$this->initdb();
				return;
			}
			catch (sly_DB_PDO_Exception $e) {
				$this->warning = $e->getMessage();
			}
		}

		$this->render('views/setup/dbconfig.phtml', array(
			'host'   => $data['HOST'],
			'user'   => $data['LOGIN'],
			'pass'   => $data['PASSWORD'],
			'dbname' => $data['NAME'],
			'prefix' => $data['TABLE_PREFIX'],
			'driver' => $data['DRIVER']
		));
	}

	protected function config() {
		global $I18N;

		$config = sly_Core::config();
		$isSent = isset($_POST['sly-submit']);

		if ($isSent) {
			$config->setLocal('SERVER', sly_post('server', 'string'));
			$config->setLocal('SERVERNAME', sly_post('servername', 'string'));
			$config->setLocal('INSTNAME', 'sly'.date('YmdHis'));
			$config->setLocal('ERROR_EMAIL', sly_post('error_email', 'string'));

			$config->set('LANG', $this->lang);

			unset($_POST['sly-submit']);
			$this->createUser();
			return;
		}

		$this->render('views/setup/config.phtml', array(
			'server'     => $config->get('SERVER'),
			'serverName' => $config->get('SERVERNAME'),
			'errorEMail' => $config->get('ERROR_EMAIL')
		));
	}

	protected function initdb() {
		global $I18N, $REX;

		$config         = sly_Core::config();
		$prefix         = $config->get('DATABASE/TABLE_PREFIX');
		$error          = '';
		$dbInitFunction = sly_post('db_init_function', 'string', '');

		// nenötigte Tabellen prüfen

		$requiredTables = array (
			$prefix.'article',
			$prefix.'article_slice',
			$prefix.'clang',
			$prefix.'file',
			$prefix.'file_category',
			$prefix.'user',
			$prefix.'slice',
			$prefix.'slice_value',
			$prefix.'registry'
		);

		switch ($dbInitFunction) {
			case 'nop': // Datenbank schon vorhanden, nichts tun

				$error = false;
				break;

			case 'drop': // alte DB löschen

				$db = sly_DB_Persistence::getInstance();

				foreach ($requiredTables as $table) {
					$db->query('DROP TABLE IF EXISTS ?', array($table));
				}

				// kein break;

			case 'setup': // leere Datenbank neu einrichten

				$installScript = SLY_INCLUDE_PATH.'/install/sally0_3.sql';
				$error         = $this->setupImport($installScript);

				break;

			default: // Extensions eine Chance geben

				rex_register_extension_point('SLY_SETUP_INIT_DATABASE', $dbInitFunction);
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
				$error .= t('setup_031', $missingTable).'<br />';
			}
		}

		if (empty($error)) {
			unset($_POST['sly-submit']);
			$this->config();
		}
		else {
			$this->warning = empty($dbInitFunction) ? '' : $error;
			$this->render('views/setup/initdb.phtml', array(
				'dbInitFunction'  => $dbInitFunction,
				'dbInitFunctions' => array('setup', 'nop', 'drop')
			));
		}
	}

	protected function createuser() {
		global $I18N;

		$config      = sly_Core::config();
		$prefix      = $config->get('DATABASE/TABLE_PREFIX');
		$pdo         = sly_DB_Persistence::getInstance();
		$usersExist  = $pdo->listTables($prefix.'user') && $pdo->magicFetch('user', 'id') !== false;
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
					$userOK = $pdo->listTables($prefix.'user') && $pdo->magicFetch('user', 'id', array('login' => $adminUser)) > 0;

					if ($userOK) {
						$error = $I18N->msg('setup_042'); // Dieses Login existiert schon!
					}
					else {
						$service   = sly_Service_Factory::getService('User');
						$adminPass = $service->hashPassword($adminPass);
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

	public function finish() {
		global $I18N, $REX;

		sly_Core::config()->setLocal('SETUP', false);

		$this->render('views/setup/finish.phtml');
	}

	protected function title($title) {
		rex_title($title);
		print '<div id="rex-setup" class="rex-area">';
	}

	protected function teardown()	{
		print '</div>'; // rex_setup_title() schließen
	}

	protected function checkDirsAndFiles() {
		global $REX;

		// Schreibrechte

		$s = DIRECTORY_SEPARATOR;

		$writables = array(
			$REX['DATAFOLDER'],
			$REX['MEDIAFOLDER'],
			SLY_INCLUDE_PATH.$s.'addons',
			SLY_DYNFOLDER,
			SLY_DYNFOLDER.$s.'public',
			SLY_DYNFOLDER.$s.'internal',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally'.$s.'css-cache',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally'.$s.'yaml-cache',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally'.$s.'articles',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally'.$s.'templates',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally'.$s.'files'
		);

		$res = $this->isWritable($writables, true);

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
				mkdir($element);
				chmod($element, 0777);
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

	protected function checkPermission()
	{
		return true;
	}
}
