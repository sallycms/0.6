<?php

require $REX['INCLUDE_PATH'].'/layout/top.php';

rex_title('WV Cache');

$func = rex_request('func', 'string', '');

if($func == 'delete'){
	FileCache::flushstatic();
}

?>
<div class="rex-addon-output">
	<div class="rex-addon-content">
		<h4 class="rex-hl3"><?= $I18N->msg("delete_cache") ?></h4>
		<p class="rex-button"><a class="rex-button" href="index.php?page=cache&amp;func=delete"><span><span><?= $I18N->msg("delete_cache") ?></span></span></a></p>
	</div>
</div>

<?php
require $REX['INCLUDE_PATH'].'/layout/bottom.php';