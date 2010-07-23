<?
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @package redaxo4
 */

$version = sly_Service_Factory::getService('AddOn')->getVersion('image_resize');
?><div class="rex-addon-output">
  <h2 class="rex-hl2">Image Resize Addon (Version <?= sly_html($version) ?>)</h2>
  <div class="rex-addon-content">
    <?php include dirname(__FILE__).'/../help.inc.php'; ?>
  </div>
</div>