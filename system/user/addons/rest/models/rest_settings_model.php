<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rest_settings_model {

  public function get()
  {
    $results = ee()->db->select('settings')
      ->from('extensions')
      ->where(array(
        'class' => 'Rest_ext'
      ))
      ->limit(1)
      ->get();
    
    if($results->result_array);
    {
      return unserialize($results->row('settings'));
    }
  }
}