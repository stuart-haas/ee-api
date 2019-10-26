<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'rest/config.php';

class Rest_mcp {

  public function __construct() {}

  public function index()
  {
    $vars = $this->get_settings_form_vars();

    $this->render_nav();
  
    return ee('View')->make('rest:index')->render($vars);
  }

  public function log()
  {
    ee()->load->model('rest_log_model');

    $vars = ee()->rest_log_model->get();

    ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css">');
    ee()->cp->add_to_foot('<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>');
    ee()->cp->add_to_foot('<script type="text/javascript" src="//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>');

    ee()->javascript->output('
      $("#data-table").dataTable({
        order: [[ 1, "desc" ]],
        pageLength: 10,
        scrollX: true
      });
    ');
    ee()->javascript->compile();

    $this->render_nav();

    return ee('View')->make('rest:log')->render($vars);
  }

  public function save()
  {
    $rules = array(
			'api_trigger' => 'required',
      'api_key' => 'required'
		);

		$validationResult = ee('Validation')->make($rules)->validate($_POST);

		if ($validationResult->failed())
		{
			ee('CP/Alert')->makeStandard('rest-settings-saved')
				->asIssue()
				->withTitle(lang('settings_save_error'))
				->addToBody(lang('settings_save_error_desc'))
        ->now();

      $vars = $this->get_settings_form_vars();

      $vars['errors'] = $validationResult;

      return ee('View')->make('rest:index')->render($vars);
		}
		else
		{
      ee()->db->where('class', 'Rest_ext');
      ee()->db->update('extensions', array('settings' => serialize($_POST)));

			ee('CP/Alert')->makeStandard('rest-settings-saved')
				->asSuccess()
				->withTitle(lang('settings_saved'))
				->addToBody(sprintf(lang('settings_saved_desc'), 'ShippingEasy'))
				->defer();

      ee()->functions->redirect(ee('CP/URL', 'addons/settings/rest'));
    }
  }

  private function render_nav()
  {
    $sidebar = ee('CP/Sidebar')->make();

    $header = $sidebar->addHeader(lang('REST'));
    
    $nav = $header->addBasicList();
    $nav->addItem(lang('Settings'), ee('CP/URL', 'addons/settings/rest'));
    $nav->addItem(lang('Log'), ee('CP/URL', 'addons/settings/rest/log'));
  }

  private function get_settings_form_vars()
  {		
    ee()->load->model('rest_settings_model');

    $this->settings = ee()->rest_settings_model->get();
    
    $vars['sections'] = array(
      array(
        array(
          'title' => 'API Trigger',
          'fields' => array(
            'api_trigger' => array(
              'type' => 'text',
              'value' => !isset($this->settings['api_trigger']) ? '' : $this->settings['api_trigger'],
              'required' => TRUE
            )
          )
        ),
        array(
          'title' => 'API Key',
          'fields' => array(
            'api_key' => array(
              'type' => 'text',
              'value' => !isset($this->settings['api_key']) ? '' : $this->settings['api_key'],
              'required' => TRUE
            )
          )
        )
      )
    );

    $vars += array(
      'base_url' => ee('CP/URL')->make('addons/settings/rest/save'),
      'cp_page_title' => lang('Settings'),
      'save_btn_text' => 'btn_save_settings',
      'save_btn_text_working' => 'btn_saving'
    );

    return $vars;
  }
}