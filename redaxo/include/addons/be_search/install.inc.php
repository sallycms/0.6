<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Backend Search Addon
 *
 * @author  markus[dot]staab[at]redaxo[dot]de Markus Staab
 * @package redaxo4
 */

$error = '';

if ($error != '')
  $REX['ADDON']['installmsg']['be_search'] = $error;
else
  $REX['ADDON']['install']['be_search'] = true;