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
		$errors  = false;
		$results = array();
		$tester  = new sly_Util_Requirements();
		$level   = error_reporting(0);

		$results['php_version']       = array('5.1', $tester->phpVersion());
		$results['mysql_version']     = array('5.0', $tester->mySQLVersion());
		$results['php_time_limit']    = array('20s', $tester->execTime());
		$results['php_mem_limit']     = array('16MB / 64MB', $tester->memoryLimit());
		$results['php_pseudo']        = array('translate:none', $tester->nonsenseSecurity());
		$results['php_short']         = array('translate:activated', $tester->shortOpenTags());
		$results['apache_modrewrite'] = array('translate:required', $tester->modRewrite());

		error_reporting($level);

		foreach ($results as $result) {
			if ($result[1]['status'] == sly_Util_Requirements::FAILED) {
				$errors = true;
				break;
			}
		}

		// init directories

		$cantCreate = $this->checkDirsAndFiles();
		$protected  = array('../develop', '../data/dyn/internal');
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
		$this->render('views/setup/fsperms.phtml', $params);
	}

	protected function dbconfig() {
		$config = sly_Core::config();
		$data   = $config->get('DATABASE');
		$isSent = isset($_POST['submit']);

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
				unset($_POST['submit']);
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
		$isSent = isset($_POST['submit']);

		if ($isSent) {
			$config->setLocal('SERVER', sly_post('server', 'string'));
			$config->setLocal('SERVERNAME', sly_post('servername', 'string'));
			$config->setLocal('INSTNAME', 'sly'.date('YmdHis'));
			$config->setLocal('ERROR_EMAIL', sly_post('error_email', 'string'));

			$config->set('LANG', $this->lang);

			unset($_POST['submit']);
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
			unset($_POST['submit']);
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

		if (isset($_POST['submit'])) {
			if ($createAdmin) {
				if (empty($adminUser)) {
					$error = $I18N->msg('setup_040');
				}

				if (empty($adminPass)) {
					if (!empty($error)) $error .= ' ';
					$error .= $I18N->msg('setup_041');
				}

				if (empty($error)) {
					$service   = sly_Service_Factory::getService('User');
					$user      = $service->find(array('login' => $adminUser));
					$user      = empty($user) ? new sly_Model_User() : reset($user);
					$adminPass = $service->hashPassword($adminPass);

					$user->setName(ucfirst(strtolower($adminUser)));
					$user->setLogin($adminUser);
					$user->setPassword($adminPass);
					$user->setRights('#admin[]#');
					$user->setStatus(true);
					$user->setCreateDate(time());
					$user->setUpdateDate(time());
					$user->setCreateUser('setup');
					$user->setUpdateUser('setup');
					$user->setRevision(0);

					if (!$service->save($user)) {
						$error = $I18N->msg('setup_043');
					}
				}
			}
			elseif (!$usersExist) {
				$error = $I18N->msg('setup_044');
			}

			if (empty($error)) {
				unset($_POST['submit']);
				$this->finish();
				return;
			}
		}

		$this->warning = $error;
		$this->render('views/setup/createuser.phtml', array(
			'usersExist' => $usersExist,
			'adminUser'  => $adminUser
		));
	}

	public function finish() {
		sly_Core::config()->setLocal('SETUP', false);
		$this->render('views/setup/finish.phtml');
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
			SLY_DEVELOPFOLDER.$s.'actions',
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
		return is_dir($dir) || (mkdir($dir) && chmod($dir, 0777));
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

	protected function checkPermission() {
		return sly_Core::config()->get('SETUP') === true;
	}
}
