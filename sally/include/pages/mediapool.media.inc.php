<?php
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

// *************************************** CONFIG

$args         = rex_request('args', 'array', array());
$thumbs       = true;
$thumbsresize = sly_Service_Factory::getService('AddOn')->isAvailable('image_resize');

// *************************************** KATEGORIEN CHECK UND AUSWAHL

// ***** kategorie auswahl
$db = new rex_sql();
$file_cat = $db->getArray('SELECT * FROM '.$REX['DATABASE']['TABLE_PREFIX'].'file_category ORDER BY name ASC');

// ***** select bauen
$sel_media = new rex_select;
$sel_media->setId("rex_file_category");
$sel_media->setName("rex_file_category");
$sel_media->setSize(1);
$sel_media->setStyle('class="rex-form-select"');
$sel_media->setSelected($rex_file_category);
$sel_media->setAttribute('onchange', 'this.form.submit();');
$sel_media->addOption($I18N->msg('pool_kats_no'),"0");

$mediacat_ids = array();
if ($rootCats = OOMediaCategory::getRootCategories())
{
    foreach( $rootCats as $rootCat) {
        rex_mediapool_addMediacatOptions( $sel_media, $rootCat, $mediacat_ids);
    }
}


function _rex_deleteMediaController(OOMedia $media)
{
	global $subpage, $I18N, $REX, $PERMALL;

	$retval    = array('info' => null, 'warning' => null, 'subpage' => null);
	$file_name = $media->getFileName();

	if ($PERMALL || $REX['USER']->hasPerm('media['.$media->getCategoryId().']')) {
		$usages   = $media->isInUse();
		$filename = $media->getValue('filename');

		if ($usages === false) {
			if ($media->delete() !== false) {
				_rex_deleteFileCache($filename);
				$retval['info'] = $I18N->msg('pool_file_deleted');
			}
			else {
				$retval['warning'] = $I18N->msg('pool_file_delete_error_1', $file_name);
			}

			$retval['subpage'] = '';
		}
		else {
			$tmp   = array();
			$tmp[] = $I18N->msg('pool_file_delete_error_1', $file_name).'. '.$I18N->msg('pool_file_delete_error_2').':<br />';
			$tmp[] = '<ul>';

			foreach ($usages as $usage) {
				if (!empty($usage['link'])) {
					$tmp[] = '<li><a href="javascript:openPage(\''.htmlspecialchars($usage['link']).'\')">'.htmlspecialchars($usage['title']).'</a></li>';
				}
				else {
					$tmp[] = '<li>'.htmlspecialchars($usage['title']).'</li>';
				}
			}

			$tmp[] = '</ul>';
			$retval['warning'] = implode("\n", $tmp);
		}
	}
	else {
		$retval['warning'] = $I18N->msg('no_permission');
	}

	return $retval;
}

function _rex_deleteFileCache($filename)
{
	global $REX;

	$path = $REX['MEDIAFOLDER'].'/addons/image_resize/image_resize__**__'.$filename;
	foreach (glob($path) as $file) unlink($file);
}

// ----- EXTENSION POINT
echo rex_register_extension_point('PAGE_MEDIAPOOL_HEADER', '',
  array(
    'subpage' => $subpage,
    'category_id' => $rex_file_category
  )
);


// ***** formular
$cat_out = '<div class="rex-form" id="rex-form-mediapool-selectcategory">
              <form action="index.php" method="post">
                <fieldset class="rex-form-col-1 num1">
                  <legend>'. $I18N->msg('pool_select_cat') .'</legend>

                  <div class="rex-form-wrapper">
                    <input type="hidden" name="page" value="mediapool" />
                    '. $arg_fields .'

                    <div class="rex-form-row">
                      <p class="rex-form-select">
                        <label for="rex_file_category">'. $I18N->msg('pool_kats') .'</label>
                        '. $sel_media->get();

if ($subpage=='detail')
{
	$cat_out .= '<input class="rex-form-submit" type="submit" value="'. $I18N->msg('show') .'" />';
}

$cat_out .= '
                      </p>
                    </div>';

if ($subpage!='detail')
{
	$cat_out .= '			<noscript>
                      <div class="rex-form-row">
                        <p class="rex-form-submit">
                          <input class="rex-form-submit" type="submit" value="'. $I18N->msg('pool_search') .'" />
                        </p>
                      </div>
                    </noscript>';
}


$cat_out .= '     </div>
                </fieldset>
              </form>
            </div>
';

// ----- EXTENSION POINT
$cat_out = rex_register_extension_point('MEDIA_LIST_TOOLBAR', $cat_out,
  array(
    'subpage' => $subpage,
    'category_id' => $rex_file_category
  )
);

// *************************************** Subpage: Detail

if ($subpage=='detail' && rex_post('btn_delete', 'string')) {
	$media = OOMedia::getMediaById($file_id);

	if ($media) {
		$retval = _rex_deleteMediaController($media);

		if ($retval['info'] !== null)    $info    = $retval['info'];
		if ($retval['warning'] !== null) $warning = $retval['warning'];
		if ($retval['subpage'] !== null) $subpage = $retval['subpage'];
	}
	else {
		$warning = $I18N->msg('pool_file_not_found');
		$subpage = '';
	}
}

if ($subpage=="detail" && rex_post('btn_update', 'string')){

  $gf = new rex_sql;
  $gf->setQuery("select * from ".$REX['DATABASE']['TABLE_PREFIX']."file where file_id='$file_id'");
  if ($gf->getRows()==1)
  {
    if ($PERMALL || ($REX['USER']->hasPerm('media['.$gf->getValue('category_id').']') && $REX['USER']->hasPerm('media['. $rex_file_category .']')))
    {

      $FILEINFOS = array();
      $FILEINFOS["rex_file_category"] = $rex_file_category;
      $FILEINFOS["file_id"] = $file_id;
      $FILEINFOS["title"] = rex_request("ftitle","string");
      $FILEINFOS["filetype"] = $gf->getValue('filetype');
      $FILEINFOS["filename"] = $gf->getValue('filename');

      $return = rex_mediapool_updateMedia($_FILES['file_new'],$FILEINFOS,$REX['USER']->getValue("login"));
		_rex_deleteFileCache($FILEINFOS["filename"]);

		sly_Core::cache()->delete('sly.medium', $FILEINFOS["file_id"]);

      $info = $return['msg'];

      if($return["ok"])
      {
        // ----- EXTENSION POINT
         // rex_register_extension_point('MEDIA_UPDATED','',array('id' => $file_id, 'type' => $FILEINFOS["filetype"], 'filename' => $FILEINFOS["filename"] ));
         rex_register_extension_point('MEDIA_UPDATED','', $return);
         $info = $return['msg'];
      }else{
      	$warning = $return['msg'];
      }
    }else
    {
      $warning = $I18N->msg('no_permission');
    }
  }else
  {
    $warning = $I18N->msg('pool_file_not_found');
    $subpage = "";
  }
}

if ($subpage == "detail")
{
  $media = OOMedia::getMediaById($file_id);
  if ($media)
  {
    $TPERM = $PERMALL || $REX['USER']->hasPerm("media[".$media->getCategoryId()."]");
    echo $cat_out;
    $ftitle            = $media->getTitle();
    $fname             = $media->getFileName();
    $ffiletype         = $media->getType();
    $ffile_size        = $media->getSize();
    $ffile_size        = $media->getFormattedSize();
    $ffile_update      = $media->getUpdateDate();
    $rex_file_category = $media->getCategoryId();
    $encoded_fname = urlencode($fname);
    $file_ext      = substr(strrchr($fname, '.'),1);
    $icon_src      = 'media/mime-default.gif';

    if (OOMedia::isDocType($file_ext))
    {
	   $icon_src = 'media/mime-'.$file_ext.'.gif';
	 }
    $thumbnail    = '<img src="'. $icon_src .'" alt="'. sly_html($ftitle) .'" title="'. sly_html($ftitle) .'" />';
    $ffiletype_ii = $media->isImage();

    if ($ffiletype_ii)
    {
      $fwidth  = $media->getWidth();
      $fheight = $media->getHeight();

      if ($size = @getimagesize($REX['MEDIAFOLDER'].'/'.$fname))
      {
        $fwidth  = $size[0];
        $fheight = $size[1];
      }
      list($rwidth, $rheight) = sly_mediapool_get_dimensions($fwidth, $fheight, 200, 70);
    }
    $add_image    = '';
    $add_ext_info = '';
    $style_width  = '';

    if ($ffiletype_ii)
    {
      $add_ext_info = '
      <div class="rex-form-row">
        <p class="rex-form-read">
          <label for="fwidth">'. $I18N->msg('pool_img_width') .' / '.$I18N->msg('pool_img_height') .'</label>
          <span class="rex-form-read" id="fwidth">'. $fwidth .' px / '. $fheight .' px</span>
        </p>
      </div>';
      $imgn = '../data/mediapool/'. $encoded_fname .'?t='.$ffile_update;

      if (!file_exists($REX['MEDIAFOLDER'].'/'.$fname))
      {
        $imgn = 'media/mime-error.gif';
      }
      else if ($thumbs && $thumbsresize && ($fwidth > 200 || $fheight > 70))
      {
        $imgn = '../index.php?rex_resize='.$rwidth.'w__'.$rheight.'h__'.$encoded_fname.'&t='.$ffile_update;
      }
      $attrs = array(
        'src'    => $imgn,
        'alt'    => $ftitle,
        'title'  => $ftitle,
        'width'  => $rwidth,
        'height' => $rheight
	   );

      $add_image = '<p class="rex-mediapool-detail-image"><img '.sly_Util_HTML::buildAttributeString($attrs).' /></p>';
    }
    if ($warning != '')
    {
      echo rex_warning($warning);
      $warning = '';
    }
    if ($info != '')
    {
      echo rex_info($info);
      $info = '';
    }

    if($opener_input_field == 'TINYIMG')
    {
      if ($ffiletype_ii)
      {
        $opener_link .= '<a href="javascript:insertImage(\''. $encoded_fname .'\',\''.htmlspecialchars($ftitle).'\');">'.$I18N->msg('pool_image_get').'</a> | ';
      }
    }
    elseif($opener_input_field == 'TINY')
    {
      $opener_link .= '<a href="javascript:insertLink(\''.$encoded_fname.'\');">'.$I18N->msg('pool_link_get').'</a>';
    }
    elseif($opener_input_field != '')
    {
      $opener_link = '<a href="javascript:selectMedia(\''.$encoded_fname.'\');">'.$I18N->msg('pool_file_get').'</a>';
      if (substr($opener_input_field,0,14)=="REX_MEDIALIST_")
      {
        $opener_link = '<a href="javascript:selectMedialist(\''.$encoded_fname.'\');">'.$I18N->msg('pool_file_get').'</a>';
      }
    }

    if($opener_link != '')
    {
      $opener_link = ' | '. $opener_link;
    }

    if ($TPERM)
    {
      $cats_sel = new rex_select;
      $cats_sel->setStyle('class="rex-form-select"');
      $cats_sel->setSize(1);
      $cats_sel->setName('rex_file_category');
      $cats_sel->setId('rex_file_new_category');
      $cats_sel->addOption($I18N->msg('pool_kats_no'),'0');
      $mediacat_ids = array();
      $rootCat = 0;
      if ($rootCats = OOMediaCategory::getRootCategories())
      {
          foreach( $rootCats as $rootCat) {
              rex_mediapool_addMediacatOptionsWPerm( $cats_sel, $rootCat, $mediacat_ids);
          }
      }
      $cats_sel->setSelected($rex_file_category);

      echo '
        <div id="rex-mediapool-detail-wrapper">
        <div class="rex-form" id="rex-form-mediapool-detail"'.$style_width.'>
          <form action="index.php" method="post" enctype="multipart/form-data">
            <fieldset class="rex-form-col-1 num2 sly-file-properties">
              <legend>'. $I18N->msg('pool_file_edit') . $opener_link.'</legend>

              <div class="rex-form-wrapper">
                <input type="hidden" name="page" value="mediapool" />
                <input type="hidden" name="subpage" value="detail" />
                <input type="hidden" name="file_id" value="'.$file_id.'" />
                '. $arg_fields .'


                  <div class="rex-form-row rex-mediapool-detail-image-container">
                    '. $add_image .'
                    <p class="rex-form-text">
                      <label for="ftitle">Titel</label>
                      <input class="rex-form-text" type="text" size="20" id="ftitle" name="ftitle" value="'. htmlspecialchars($ftitle) .'" />
                    </p>
                  </div>

                  <div class="rex-form-row">
                    <p class="rex-form-select">
                      <label for="rex_file_new_category">'. $I18N->msg('pool_file_category') .'</label>
                      '. $cats_sel->get() .'
                    </p>
                  </div>

              	<div class="rex-clearer"></div>';

  // ----- EXTENSION POINT
  echo rex_register_extension_point('MEDIA_FORM_EDIT', '', array ('file_id' => $file_id, 'media' => $media));

  echo '
                      '. $add_ext_info .'
                  <div class="rex-form-row">
                    <p class="rex-form-read">
                      <label for="flink">'. $I18N->msg('pool_filename') .'</label>
                      <span class="rex-form-read"><a href="../data/mediapool/'. $encoded_fname .'" id="flink">'. htmlspecialchars($fname) .'</a> [' . $ffile_size . ']</span>
                    </p>
                  </div>

                  <div class="rex-form-row">
                    <p class="rex-form-read">
                      <label for="fupdate">'. $I18N->msg('pool_last_update') .'</label>
                      <span class="rex-form-read" id="fupdate">'. $media->getUpdateDate('%a %d. %B %Y') .' ['. $media->getUpdateUser() .']</span>
                    </p>
                  </div>

                  <div class="rex-form-row">
                    <p class="rex-form-read">
                      <label for="fcreate">'. $I18N->msg('pool_created') .'</label>
                      <span class="rex-form-read" id="fcreate">'. $media->getCreateDate('%a %d. %B %Y'). ' ['.$media->getCreateUser() .']</span>
                    </p>
                  </div>

                  <div class="rex-form-row">
                    <p class="rex-form-file">
                      <label for="file_new">'. $I18N->msg('pool_file_exchange') .'</label>
                      <input class="rex-form-file" type="file" id="file_new" name="file_new" size="20" />
                    </p>
                  </div>

                  <div class="rex-form-row">
                    <p class="rex-form-submit">
                      <input type="submit" class="rex-form-submit" value="'. $I18N->msg('pool_file_update') .'" name="btn_update" />
                      <input type="submit" class="rex-form-submit rex-form-submit-2" value="'. $I18N->msg('pool_file_delete') .'" name="btn_delete" onclick="return confirm(\''.$I18N->msg('delete').' ?\');" />
                    </p>
                  </div>

              	<div class="rex-clearer"></div>
              </div>
            </fieldset>
          </form>
        </div>
      </div>';
    }
    else
    {
      $catname = $I18N->msg('pool_kats_no');
      $Cat = OOMediaCategory::getCategoryById($rex_file_category);
      if ($Cat) $catname = $Cat->getName();

      if($REX['USER']->hasPerm('advancedMode[]'))
      {
        $ftitle .= ' ['. $file_id .']';
        $catname .= ' ['. $rex_file_category .']';
      }

      echo '<h2 class="rex-hl2">'. $I18N->msg('pool_file_details') . $opener_link.'</h2>
            <div class="rex-form" id="rex-form-mediapool-detail">
              <div class="rex-form-wrapper">
                <div class="rex-mediapool-detail-data"'.$style_width.'>

                  <div class="rex-form-row">
                    <p class="rex-form-read">
                        <label for="ftitle">Titel</label>
                        <span class="rex-form-read" id="ftitle">'. sly_html($ftitle) .'</span>
                    </p>
                  </div>
                  <div class="rex-form-row">
                    <p class="rex-form-read">
                        <label for="rex_file_new_category">'. $I18N->msg('pool_file_category') .'</label>
                        <span class="rex-form-read" id="rex_file_new_category">'. sly_html($catname) .'</span>
                    </p>
                  </div>
                  <div class="rex-form-row">
                    <p class="rex-form-read">
                        <label for="flink">'. $I18N->msg('pool_filename') .'</label>
                        <a class="rex-form-read" href="../data/mediapool/'. $encoded_fname .'" id="flink">'. $fname .'</a> [' . $ffile_size . ']
                    </p>
                  </div>
                  <div class="rex-form-row">
                    <p class="rex-form-read">
                        <label for="fupdate">'. $I18N->msg('pool_last_update') .'</label>
                        <span class="rex-form-read" id="fupdate">'. strftime($I18N->msg('datetimeformat'), $media->getUpdateDate()) .' ['. $media->getUpdateUser() .']</span>
                    </p>
                  </div>
                  <div class="rex-form-row">
                    <p class="rex-form-read">
                        <label for="fcreate">'. $I18N->msg('pool_last_update') .'</label>
                        <span class="rex-form-read" id="fcreate">'. strftime($I18N->msg('datetimeformat'), $media->getCreateDate()).' ['.$media->getCreateUser() .']</span>
                    </p>
                  </div>

                </div><!-- END rex-mediapool-detail-data //-->
                '. $add_image .'


              	<div class="rex-clearer"></div>
              </div>
            </div>';
    }
  }
  else
  {
    $warning = $I18N->msg('pool_file_not_found');
    $subpage = "";
  }
}


// *************************************** EXTRA FUNCTIONS

if($PERMALL && $media_method == 'updatecat_selectedmedia')
{
  $selectedmedia = rex_post('selectedmedia','array');
  if($selectedmedia[0]>0){

    foreach($selectedmedia as $file_id){

      $db = new rex_sql;
      // $db->debugsql = true;
      $db->setTable($REX['DATABASE']['TABLE_PREFIX'].'file');
      $db->setWhere('file_id='.$file_id);
      $db->setValue('category_id',$rex_file_category);
      $db->addGlobalUpdateFields();
      if($db->update())
      {
        $info = $I18N->msg('pool_selectedmedia_moved');
      }
      else
      {
        $warning = $I18N->msg('pool_selectedmedia_error');
      }
    }
  }
  else
  {
    $warning = $I18N->msg('pool_selectedmedia_error');
  }
}

if ($PERMALL && $media_method == 'delete_selectedmedia') {
	$selectedmedia = rex_post("selectedmedia","array");

	if (!empty($selectedmedia)) {
		$warning = array();
		$info    = array();

		foreach ($selectedmedia as $file_id) {
			$media = OOMedia::getMediaById($file_id);

			if ($media) {
				$retval = _rex_deleteMediaController($media);

				if ($retval['info'] !== null)    $info[]    = $retval['info'];
				if ($retval['warning'] !== null) $warning[] = $retval['warning'];
				if ($retval['subpage'] !== null) $subpage   = $retval['subpage'];
			}
			else {
				$warning[] = $I18N->msg('pool_file_not_found');
			}
		}

		$warning = implode("<br />\n", $warning);
		$info    = implode("<br />\n", $info);
	}
}


// *************************************** SUBPAGE: "" -> MEDIEN ANZEIGEN

if ($subpage == '')
{
  $cats_sel = new rex_select;
  $cats_sel->setSize(1);
  $cats_sel->setStyle('class="rex-form-select"');
  $cats_sel->setName("rex_file_category");
  $cats_sel->setId("rex_file_category");
  $cats_sel->addOption($I18N->msg('pool_kats_no'),"0");
  $mediacat_ids = array();
  $rootCat = 0;
  if ($rootCats = OOMediaCategory::getRootCategories())
  {
      foreach( $rootCats as $rootCat) {
          rex_mediapool_addMediacatOptionsWPerm( $cats_sel, $rootCat, $mediacat_ids);
      }
  }
  $cats_sel->setSelected($rex_file_category);

  echo $cat_out;

  if(is_array($warning))
  {
    if(count($warning)>0)
      echo rex_warning_block(implode('<br />', $warning));
    $warning = '';
  }else if($warning != '')
  {
    echo rex_warning($warning);
    $warning = '';
  }

  if(is_array($info))
  {
    if(count($info)>0)
      echo rex_info_block(implode('<br />', $info));
    $info = '';
  }else if($info != '')
  {
    echo rex_info($info);
    $info = '';
  }

  if(!empty($args['types']))
    echo rex_info($I18N->msg('pool_file_filter', $args['types']));

  //deletefilelist und cat change
  echo '<div class="rex-form" id="rex-form-mediapool-media">
       <form action="index.php" method="post" enctype="multipart/form-data">
          <fieldset class="rex-form-col-1 num2">
            <legend class="rex-form-hidden-legend">'. $I18N->msg('pool_selectedmedia') .'</legend>

            <div class="rex-form-wrapper">
              <input type="hidden" name="page" value="mediapool" />
              <input type="hidden" id="media_method" name="media_method" value="" />
              '. $arg_fields .'

              <table class="rex-table sly-mediapool-list" summary="'. htmlspecialchars($I18N->msg('pool_file_summary', $rex_file_category_name)) .'">
                <caption>'. $I18N->msg('pool_file_caption', $rex_file_category_name) .'</caption>
                <colgroup>
                  <col width="40" />
                  <col width="110" />
                  <col width="*" />
                  <col width="153" />
                </colgroup>
                <thead>
                  <tr>
                    <th class="rex-icon">-</th>
                    <th>'. $I18N->msg('pool_file_thumbnail') .'</th>
                    <th>'. $I18N->msg('pool_file_info') .' / '. $I18N->msg('pool_file_description') .'</th>
                    <th>'. $I18N->msg('pool_file_functions') .'</th>
                  </tr>
                </thead>';



  // ----- move and delete selected items
  if($PERMALL)
  {
    $add_input = '';
    $filecat = new rex_sql();
    $filecat->setQuery("SELECT * FROM ".$REX['DATABASE']['TABLE_PREFIX']."file_category ORDER BY name ASC LIMIT 1");
    if ($filecat->getRows() > 0)
    {
      $cats_sel->setId('rex_move_file_dest_category');
      $add_input = '
        <label for="rex_move_file_dest_category">'.$I18N->msg('pool_selectedmedia').'</label>
        '. $cats_sel->get() .'
        <input class="rex-form-submit rex-form-submit-2 sly-button-changecat" type="submit" value="'. $I18N->msg('pool_changecat_selectedmedia') .'" />';
    }
    $add_input .= '<input class="rex-form-submit rex-form-submit-2 sly-button-delete" type="submit" value="'.$I18N->msg('pool_delete_selectedmedia').'" rel="'.$I18N->msg('delete').'" />';

    echo '
      <tfoot>
      <tr>
        <td class="rex-icon">
          <label class="rex-form-hidden-label" for="checkie">'.$I18N->msg('pool_select_all').'</label>
          <input class="rex-form-checkbox" type="checkbox" name="checkie" id="checkie" value="0" onclick="setAllCheckBoxes(\'selectedmedia[]\',this)" />
        </td>
        <td colspan="3">
          '.$add_input.'
        </td>
      </tr>
      </tfoot>
    ';
  }



  $where = 'f.category_id='.$rex_file_category;
  if(isset($args['types']))
  {
    $types = array();
    foreach(explode(',',$args['types']) as $type)
    {
      $types[] = 'SUBSTRING(f.filename,LOCATE(".",f.filename)+1)="'. htmlspecialchars($type) .'"';
    }
    $where .= ' AND ('. implode(' OR ', $types) .')';
  }
  $qry = "SELECT file_id FROM ".$REX['DATABASE']['TABLE_PREFIX']."file f WHERE ". $where ." ORDER BY f.updatedate desc";

  // ----- EXTENSION POINT
  $qry = rex_register_extension_point('MEDIA_LIST_QUERY', $qry,
    array(
      'category_id' => $rex_file_category
    )
  );
  $files = new rex_sql;
//   $files->debugsql = 1;
  $files->setQuery($qry);


  print '<tbody>';
  for ($i=0;$i<$files->getRows();$i++)
  {
    $file_id =   $files->getValue('file_id');
    $media = OOMedia::getMediaById($file_id);
    $file_name = $media->getFileName();
    $file_oname = $media->getOrgFileName();
    $file_title = $media->getTitle();
    $file_type = $media->getValue('filetype');
    $file_size = $media->getValue('filesize');
    $file_update = $media->getUpdateDate();
    $file_stamp = $media->getUpdateDate('%a %d. %B %Y');
    $file_updateuser = $media->getUpdateUser();
    $encoded_file_name = urlencode($file_name);
    $alt  = $media->getTitle();
    $desc = '';
    // wenn datei fehlt
    if (!OOMedia::fileExists($file_name))
    {
      $thumbnail = '<img src="media/mime-error.gif" width="44" height="38" alt="file does not exist" />';
    }
    else
    {
      $file_ext = substr(strrchr($file_name,'.'), 1);
      $icon_src = 'media/mime-default.gif';

      if (OOMedia::isDocType($file_ext))
      {
        $icon_src = 'media/mime-'. $file_ext .'.gif';
      }

      $thumbnail = '<img src="'. $icon_src .'" alt="'.sly_html($alt).'" title="'.sly_html($alt).'" />';
      if (OOMedia::_isImage($file_name) && $thumbs)
      {
        $width  = false;
        $height = false;

        if ($size = @getimagesize($REX['MEDIAFOLDER'].'/'.$file_name))
        {
          $width  = $size[0];
          $height = $size[1];
          list($width, $height) = sly_mediapool_get_dimensions($width, $height, 80, 70);
        }

        $attrs = array(
          'alt'    => $alt,
          'title'  => $alt,
          'width'  => $width,
          'height' => $height
	     );

	     if ($thumbsresize && $width) {
	     	 $attrs['src'] = '../index.php?rex_resize='.$width.'w__'.$height.'h__'.$encoded_file_name.'&t='.$file_update;
	     }
	     else {
	     	 $attrs['src'] = '../data/mediapool/'.$encoded_file_name.'?t='.$file_update;
	     }

        $thumbnail = '<img '.sly_Util_HTML::buildAttributeString($attrs).' />';
      }
    }
    // ----- get file size
    $size = $file_size;
    $file_size = $media->getFormattedSize();

    if ($file_title == '') $file_title = '['.$I18N->msg('pool_file_notitle').']';
    if($REX['USER']->hasPerm('advancedMode[]')) $file_title .= ' ['. $file_id .']';

    // ----- opener
    $opener_link = '';
    if ($opener_input_field == 'TINYIMG')
    {
      if (OOMedia::_isImage($file_name))
      {
        $opener_link .= "<a href=\"javascript:insertImage('$file_name','".$file_title."')\">".$I18N->msg('pool_image_get')."</a><br>";
      }

    } elseif ($opener_input_field == 'TINY'){
        $opener_link .= "<a href=\"javascript:insertLink('".$encoded_file_name."');\">".$I18N->msg('pool_link_get')."</a>";
    } elseif ($opener_input_field != '')
    {
      $opener_link = "<a href=\"javascript:selectMedia('".$encoded_file_name."');\">".$I18N->msg('pool_file_get')."</a>";
      if (substr($opener_input_field,0,14)=="REX_MEDIALIST_")
      {
        $opener_link = "<a href=\"javascript:selectMedialist('".$encoded_file_name."');\">".$I18N->msg('pool_file_get')."</a>";
      }
    }

    $ilink = 'index.php?page=mediapool&amp;subpage=detail&amp;file_id='.$file_id.'&amp;rex_file_category='.$rex_file_category. $arg_url;

    $add_td = '<td></td>';
    if ($PERMALL) $add_td = '<td class="rex-icon"><input class="rex-form-checkbox" type="checkbox" name="selectedmedia[]" value="'.$file_id.'" /></td>';

    echo '<tr>
            '. $add_td .'
            <td class="rex-thumbnail"><a href="'.$ilink.'">'.$thumbnail.'</a></td>
            <td>
                <p class="rex-tx4">
                  <a href="'.$ilink.'">'.htmlspecialchars($file_title).'</a>
                </p>
                <p class="rex-tx4">
                  '. $desc .'
                  <span class="rex-suffix">'.htmlspecialchars($file_name).' ['.$file_size.']</span>
                </p>
                <p class="rex-tx1">
                  '.$file_stamp .' | '. htmlspecialchars($file_updateuser).'
                </p>
            </td>
            <td>';

    echo rex_register_extension_point('MEDIA_LIST_FUNCTIONS',$opener_link,
      array(
        "file_id" => $file_id,
        "file_name" => $file_name,
        "file_oname" => $file_oname,
        "file_title" => $file_title,
        "file_type" => $file_type,
        "file_size" => $file_size,
        "file_stamp" => $media->getUpdateDate(),
        "file_updateuser" => $file_updateuser
      )
    );

    echo '</td>
         </tr>';

    $files->next();
  } // endforeach

  // ----- no items found
  if ($files->getRows()==0)
  {
    echo '
      <tr>
        <td></td>
        <td colspan="3">'.$I18N->msg('pool_nomediafound').'</td>
      </tr>';
  }

  print '
      </tbody>
      </table>
      </div>
    </fieldset>
  </form>
  </div>';
}
