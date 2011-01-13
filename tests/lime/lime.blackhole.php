<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class lime_output_blackhole extends lime_output
{
  public function __construct()
  {
    $this->colorizer = new lime_colorizer_blackhole(false);
    $this->base_dir  = getcwd();
  }

  public function error($message, $file = null, $line = null, $traces = array())
  {
    /* nichts tun */
  }

  protected function print_trace($method = null, $file = null, $line = null)
  {
    /* nichts tun */
  }

  public function echoln($message, $colorizer_parameter = null, $colorize = true)
  {
    /* nichts tun */
  }
}

class lime_colorizer_blackhole extends lime_colorizer
{
  public function colorize($text = '', $parameters = array())
  {
    return '';
  }
}
