<?
$version = sly_Service_Factory::getService('AddOn')->getVersion('image_resize');
?><div class="rex-addon-output">
  <h2 class="rex-hl2">Image Resize Addon (Version <?= sly_html($version) ?>)</h2>
  <div class="rex-addon-content">
    <?php include dirname(__FILE__).'/../help.inc.php'; ?>
  </div>
</div>