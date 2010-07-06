<?php

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
