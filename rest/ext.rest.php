<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once PATH_THIRD . 'rest/config.php';

class Rest_ext {

  public $version = REST_VERSION;
  public $settings = array();

  public $hooks = array(
    'sessions_start' => 'route_url'
  );

  public function __construct($settings = '')
  {
    $this->settings = $settings;
  }

  public function route_url($session)
  {
    ee()->load->model('settings_model');

    $this->settings = ee()->settings_model->get();

    if (isset($this->settings['api_trigger']) AND $this->settings['api_key'] AND ee()->uri->segment(1) == $this->settings['api_trigger'])
    {
      if(ee()->uri->segment(2) !== "")
      {
        $api = ee()->uri->segment(2).'_api';

        ee()->load->library($api);

        ee()->$api->call(ee()->uri->segment(3));

      } else {
        ee()->load->library('api_lib');

        ee()->api_lib->call();
      }
    
      die();
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