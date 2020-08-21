<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Log_model {

  public function save($method, $url, $data = null)
  {
    ee()->db->insert(
      'rest_log',
      array(
        'date' => date('Y-n-j h:i:s'),
        'method' => $type,
        'url' => $method,
        'data' => $data
      )
    );
  }

  public function get()
  {
    $results = ee()->db->select('*')
    ->from('rest_log')
    ->get();
  
    return $results->result_array();
  }
}