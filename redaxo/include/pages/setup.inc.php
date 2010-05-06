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

function rex_print_hiddens($subpage, $lang)
{
	?>
	<input type="hidden" name="page" value="setup" />
	<input type="hidden" name="subpage" value="<?= sly_html($subpage) ?>" />
	<input type="hidden" name="send" value="1" />
	<input type="hidden" name="lang" value="<?= sly_html($lang) ?>" />
	<?php
}

$MSG['err'] = '';

$checkmodus = sly_request('checkmodus', 'float');
$send       = sly_request('send', 'string');
$dbanlegen  = sly_request('dbanlegen', 'string');
$noadmin    = sly_request('noadmin', 'string');
$lang       = sly_request('lang', 'string');

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
