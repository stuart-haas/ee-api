<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rest_api_ext {

  public $settings = array();
	public $description	= 'Expression Engine REST API';
	public $docs_url		= 'http://artisandm.com';
	public $name			  = 'RestAPI';
	public $settings_exist	= 'n';
  public $version			= '1.0.0';
  public $hooks = array(
    'sessions_start' => 'route_url'
  );

  public function __construct($settings='') {}
    
  public function route_url($sssion) {

    if(ee()->uri->segment(1) == 'api') {

      if(ee()->uri->segment(2) !== "")
      {
        $api = ee()->uri->segment(2).'_api';

        ee()->load->library($api);

        if(ee()->uri->segment(3)) {
          ee()->$api->call(ee()->uri->segment(3));
        } else {
          ee()->$api->call('index');
        }

      } else {

        ee()->load->library('api_lib');

        ee()->api_lib->call();
      }
    }
  }

  public function activate_extension()
  {
    $this->insert_hooks($this->hooks);
  }
  
  public function update_extension($current = '')
  {
    if ($current == '' OR $current == $this->version)
    {
      return FALSE;
    }

    $this->insert_hooks($this->hooks);

    ee()->db->where('class', __CLASS__);
    ee()->db->update('extensions', array(
      'version' => $this->version
    ));
  }

  public function disable_extension()
  {
      ee()->db->where('class', __CLASS__);
      ee()->db->delete('extensions');
  }

  private function insert_hooks($hooks)
  { 
    $data = array(
      'class'	 	=> __CLASS__,
      'settings'  => serialize($this->settings),
      'priority'  => 10,
      'version'   => $this->version,
      'enabled'   => 'y'
    );

    foreach ($hooks as $hook => $method)
    {
      $data['hook'] = $hook;
      $data['method'] = $method;

      ee()->db->insert('extensions', $data);
    }
  }

}