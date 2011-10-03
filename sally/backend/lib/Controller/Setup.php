<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Setup extends sly_Controller_Backend {
	protected $warning;
	protected $info;
	protected $lang;

	protected function init() {
		$this->lang = sly_request('lang', 'string');
		sly_Core::getI18N()->appendFile(SLY_SALLYFOLDER.'/backend/lang/pages/setup/');
	}

	public function index()	{
		$languages = sly_I18N::getLocales(SLY_SALLYFOLDER.'/backend/lang');

		// wenn nur eine Sprache -> direkte Weiterleitung

		if (count($languages) === 1) {
			$url = 'index.php?page=setup&func=license&lang='.urlencode(reset($languages));
			sly_Util_HTTP::redirect($url);
		}

		print $this->render('setup/chooselang.phtml');
	}

	protected function license() {
		print $this->render('setup/license.phtml');
	}

	protected function fsperms() {
		$errors  = false;
		$results = array();
		$tester  = new sly_Util_Requirements();
		$level   = error_reporting(0);

		$results['php_version']    = array('5.2', '5.3', $tester->phpVersion());
		$results['mysql_version']  = array('5.0', '5.0', $tester->mySQLVersion());
		$results['php_time_limit'] = array('20s', '60s', $tester->execTime());
		$results['php_mem_limit']  = array('16MB / 64MB', '32MB / 64MB', $tester->memoryLimit());
		$results['php_pseudo']     = array('translate:none', 'translate:none', $tester->nonsenseSecurity());
		$results['php_short']      = array('translate:activated', 'translate:activated', $tester->shortOpenTags());

		error_reporting($level);

		foreach ($results as $result) {
			if ($result[2]['status'] == sly_Util_Requirements::FAILED) {
				$errors = true;
				break;
			}
		}

		// init directories

		$cantCreate = $this->checkDirsAndFiles();
		$protected  = array(SLY_DEVELOPFOLDER, SLY_DYNFOLDER.'/internal');
		$protects   = array();

		foreach ($protected as $i => $directory) {
			if (!sly_Util_Directory::createHttpProtected($directory)) {
				$protects['htaccess_'.$i] = realpath($directory);
				$errors = true;
			}
		}

		if (!empty($cantCreate)) {
			$errors = true;
		}

		$params = compact('results', 'protects', 'errors', 'cantCreate', 'tester');
		print $this->render('setup/fsperms.phtml', $params);
	}

	protected function dbconfig() {
		$config  = sly_Core::config();
		$data    = $config->get('DATABASE');
		$isSent  = isset($_POST['submit']);
		$drivers = sly_DB_PDO_Driver::getAvailable();

		if (empty($drivers)) {
			$this->warning = t('setup_no_drivers_available');
			$sent = false;
		}

		if ($isSent) {
			$data['TABLE_PREFIX'] = sly_post('prefix', 'string');
			$data['HOST']         = sly_post('host', 'string');
			$data['LOGIN']        = sly_post('user', 'string');
			$data['PASSWORD']     = sly_post('pass', 'string');
			$data['NAME']         = sly_post('dbname', 'string');
			$data['DRIVER']       = sly_post('driver', 'string');
			$createDatabase       = sly_post('create_db', 'bool');

			try {
				if (!in_array($data['DRIVER'], $drivers)) {
					throw new sly_Exception(t('setup_invalid_driver'));
				}

				if ($createDatabase && $data['DRIVER'] !== 'sqlite' && $data['DRIVER'] !== 'oci') {
					$driverClass = 'sly_DB_PDO_Driver_'.strtoupper($data['DRIVER']);
					$driver      = new $driverClass('', '', '', '');
					$db          = new sly_DB_PDO_Persistence($data['DRIVER'], $data['HOST'], $data['LOGIN'], $data['PASSWORD']);
					$createStmt  = $driver->getCreateDatabaseSQL($data['NAME']);

					$db->query($createStmt);
				}
				else {
					$db = new sly_DB_PDO_Persistence($data['DRIVER'], $data['HOST'], $data['LOGIN'], $data['PASSWORD'], $data['NAME']);
				}

				$config->setLocal('DATABASE', $data);
				unset($_POST['submit']);
				$this->initdb();
				return;
			}
			catch (sly_DB_PDO_Exception $e) {
				$this->warning = $e->getMessage();
			}
		}

		print $this->render('setup/dbconfig.phtml', array(
			'host'    => $data['HOST'],
			'user'    => $data['LOGIN'],
			'pass'    => $data['PASSWORD'],
			'dbname'  => $data['NAME'],
			'prefix'  => $data['TABLE_PREFIX'],
			'driver'  => $data['DRIVER'],
			'drivers' => $drivers
		));
	}

	protected function config() {
		$config = sly_Core::config();
		$isSent = isset($_POST['submit']);

		if ($isSent) {
			$uid = sha1(microtime(true).mt_rand(10000, 90000));
			$uid = substr($uid, 0, 20);

			$config->set('PROJECTNAME', sly_post('projectname', 'string'));
			$config->setLocal('INSTNAME', 'sly'.$uid);

			$config->set('TIMEZONE', sly_post('timezone', 'string', null));
			$config->set('DEFAULT_LOCALE', $this->lang);

			unset($_POST['submit']);
			$this->createUser();
			return;
		}

		print $this->render('setup/config.phtml', array(
			'projectName' => $config->get('PROJECTNAME'),
			'timezone'    => @date_default_timezone_get()
		));
	}

	protected function initdb() {
		$dbInitFunction = sly_post('db_init_function', 'string', '');

		if (isset($_POST['submit'])) {
			$config = sly_Core::config();
			$prefix = $config->get('DATABASE/TABLE_PREFIX');
			$driver = $config->get('DATABASE/DRIVER');
			$error  = '';

			// benötigte Tabellen prüfen

			$requiredTables = array(
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
				case 'drop': // alte DB löschen
					$db = sly_DB_Persistence::getInstance();

					// 'DROP TABLE IF EXISTS' is MySQL-only...
					foreach ($db->listTables() as $tblname) {
						if (in_array($tblname, $requiredTables)) $db->query('DROP TABLE '.$tblname);
					}

					// kein break;

				case 'setup': // leere Datenbank neu einrichten
					$script = SLY_COREFOLDER.'/install/'.strtolower($driver).'.sql';
					$error  = $this->setupImport($script);

					break;

				case 'nop': // Datenbank schon vorhanden, nichts tun
				default:
			}

			// Wenn kein Fehler aufgetreten ist, aber auch etwas geändert wurde, prüfen
			// wir, ob dadurch alle benötigten Tabellen erzeugt wurden.

			if (empty($error)) {
				$existingTables = array();
				$db             = sly_DB_Persistence::getInstance();

				foreach ($db->listTables() as $tblname) {
					if (substr($tblname, 0, strlen($prefix)) === $prefix) {
						$existingTables[] = $tblname;
					}
				}

				foreach (array_diff($requiredTables, $existingTables) as $missingTable) {
					$error .= t('setup_initdb_table_not_found', $missingTable).'<br />';
				}
			}

			if (empty($error)) {
				unset($_POST['submit']);
				$this->config();
				return;
			}
			else {
				$this->warning = $error;
			}
		}

		print $this->render('setup/initdb.phtml', array(
			'dbInitFunction'  => $dbInitFunction,
			'dbInitFunctions' => array('setup', 'nop', 'drop')
		));
	}

	protected function createuser() {
		$config      = sly_Core::config();
		$prefix      = $config->get('DATABASE/TABLE_PREFIX');
		$pdo         = sly_DB_Persistence::getInstance();
		$usersExist  = $pdo->listTables($prefix.'user') && $pdo->magicFetch('user', 'id') !== false;
		$createAdmin = !sly_post('no_admin', 'boolean', false);
		$adminUser   = sly_post('admin_user', 'string');
		$adminPass   = sly_post('admin_pass', 'string');
		$error       = '';

		if (isset($_POST['submit'])) {
			if ($createAdmin) {
				if (empty($adminUser)) {
					$error = t('setup_createuser_no_admin_given');
				}

				if (empty($adminPass)) {
					if (!empty($error)) $error .= ' ';
					$error .= t('setup_createuser_no_password_given');
				}

				if (empty($error)) {
					$service    = sly_Service_Factory::getUserService();
					$user       = $service->find(array('login' => $adminUser));
					$user       = empty($user) ? new sly_Model_User() : reset($user);

					$user->setName(ucfirst(strtolower($adminUser)));
					$user->setLogin($adminUser);
					$user->setRights('#admin[]#');
					$user->setStatus(true);
					$user->setCreateDate(time());
					$user->setUpdateDate(time());
					$user->setLastTryDate(0);
					$user->setCreateUser('setup');
					$user->setUpdateUser('setup');
					$user->setPassword($adminPass); // call this after $user->setCreateDate();
					$user->setRevision(0);

					if (!$service->save($user)) {
						$error = t('setup_createuser_cant_create_admin');
					}
				}
			}
			elseif (!$usersExist) {
				$error = t('setup_createuser_no_users_found');
			}

			if (empty($error)) {
				unset($_POST['submit']);
				$this->finish();
				return;
			}
		}

		$this->warning = $error;
		print $this->render('setup/createuser.phtml', array(
			'usersExist' => $usersExist,
			'adminUser'  => $adminUser
		));
	}

	public function finish() {
		sly_Core::config()->setLocal('SETUP', false);
		print $this->render('setup/finish.phtml');
	}

	protected function title($title) {
		$layout = sly_Core::getLayout();
		$layout->pageHeader($title);
	}

	protected function checkDirsAndFiles() {
		$s         = DIRECTORY_SEPARATOR;
		$errors    = array();
		$writables = array(
			SLY_DATAFOLDER,
			SLY_MEDIAFOLDER,
			SLY_DEVELOPFOLDER,
			SLY_DEVELOPFOLDER.$s.'templates',
			SLY_DEVELOPFOLDER.$s.'modules',
			SLY_DYNFOLDER,
			SLY_DYNFOLDER.$s.'public',
			SLY_DYNFOLDER.$s.'internal',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally'.$s.'css-cache',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally'.$s.'yaml-cache',
			SLY_DYNFOLDER.$s.'internal'.$s.'sally'.$s.'templates',
		);

		$level = error_reporting(0);

		foreach ($writables as $dir) {
			if (!$this->isWritable($dir)) {
				$errors[] = $dir;
			}
		}

		error_reporting($level);
		return $errors;
	}

	protected function isWritable($dir) {
		return is_dir($dir) || (mkdir($dir) && chmod($dir, sly_Core::getDirPerm()));
	}

	protected function printHiddens($func, $form = null) {
		if ($form instanceof sly_Form) {
			$form->addHiddenValue('page', 'setup');
			$form->addHiddenValue('func', $func);
			$form->addHiddenValue('lang', $this->lang);
		}
		else {
			?>
			<input type="hidden" name="page" value="setup" />
			<input type="hidden" name="func" value="<?= sly_html($func) ?>" />
			<input type="hidden" name="lang" value="<?= sly_html($this->lang) ?>" />
			<?php
		}
	}

	protected function setupImport($sqlScript) {
		$err_msg = '';

		if (file_exists($sqlScript)) {
			$importer = new sly_DB_Importer();
			$result   = $importer->import($sqlScript);

			if ($result['state'] === false) {
				$err_msg = $result['message'];
			}
		}
		else {
			$err_msg = t('setup_import_cant_find_exports').'<br />';
		}

		return $err_msg;
	}

	protected function checkPermission() {
		return sly_Core::config()->get('SETUP') === true;
	}
}
