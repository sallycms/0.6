<?php

require $REX['INCLUDE_PATH'].'/layout/top.php';

rex_title('Dateisystem-Cache');

$func = rex_request('func', 'string', '');

if($func == 'delete'){
	FileCache::flushstatic();
	print rex_info('Cache wurde gelöscht');
}

?>
<div class="rex-addon-output">
	<div class="rex-addon-content">
		<h4 class="rex-hl3"><?= $I18N->msg("delete_cache") ?></h4>
		<p class="rex-button"><a class="rex-button" href="index.php?page=fscache&amp;func=delete"><span><span><?= $I18N->msg("delete_cache") ?></span></span></a></p>
	</div>
</div>

<?php
require $REX['INCLUDE_PATH'].'/layout/bottom.php';