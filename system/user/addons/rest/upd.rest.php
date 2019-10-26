<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'rest/config.php';

class Rest_upd {

  public $version = REST_VERSION;
  public $module_name;

  public function __construct() {
    $this->module_name = str_replace(array('_ext', '_mcp', '_upd'), "", __CLASS__);
    $this->log_table_name = strtolower($this->module_name.'_log');
  }

  public function install() {
    
    $data = array(
      'module_name' => $this->module_name ,
      'module_version' => $this->version,
      'has_cp_backend' => 'y',
      'has_publish_fields' => 'n'
    );
   
    ee()->db->insert('modules', $data);

    $this->create_tables();

    return TRUE;
  }

  public function update($current = '') {

    $this->current = $current;
		
		if ($this->current == $this->version)
		{
			return FALSE;
    }
    
    return TRUE;
  }

  public function uninstall()
	{
    ee()->load->dbforge();
    
		ee()->db->delete('modules', array('module_name' => $this->module_name));
		
    ee()->db->like('class', $this->module_name, 'after')->delete('actions');
    
    ee()->dbforge->drop_table($this->log_table_name);
		
		return TRUE;
  }
  
  private function create_tables()
  {
    ee()->load->dbforge();

    ee()->dbforge->add_field('id');

    $fields = array(
      'date' => array(
        'type' => 'DATETIME',
      ),
      'method' => array(
        'type' => 'VARCHAR',
        'constraint' => '255',
      ),
      'url' => array(
        'type' => 'VARCHAR',
        'constraint' => '255',
      ),
      'data' => array(
        'type' => 'TEXT',
        'null' => TRUE,
      ),
    );

    ee()->dbforge->add_field($fields);

    ee()->dbforge->create_table($this->log_table_name);
  }
}