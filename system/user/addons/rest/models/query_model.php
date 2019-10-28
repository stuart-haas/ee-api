<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Query_model {

  public static function api_url()
  {
    ee()->load->model('settings_model');

    $settings = ee()->settings_model->get();
    $api_trigger = $settings['api_trigger'];

    return base_url().$api_trigger;
  }

  public function get_fields($param = '')  
  {    
    if($this->get_param('fields'))
    {
      $fields = array();

      if($param) {
        $nested_params = $this->get_nested_params($this->get_param('fields'))[$param];
        $nested_param_values = explode(",", $nested_params);
        foreach($nested_param_values as  $k2 => $v2){
          $fields[] = $param.'.'.$v2;
        }
      } else {
        $nested_params = $this->get_nested_params($this->get_param('fields'));
        foreach($nested_params as $k1 => $v1) {
          $nested_param_values = explode(",", $v1);
          foreach($nested_param_values as  $k2 => $v2){
            $fields[] = $k1.'.'.$v2;
          }
        }
      }
      return implode(', ', $fields);
    }
    return '*';
  }

  public function get_filters($param = '')
  {
    if($this->get_param('filters'))
    {
      $filters = array();

      if($param) {
        $nested_params = $this->get_nested_params($this->get_param('filters'))[$param];
        $nested_param_values = explode(",", $nested_params);
        foreach($nested_param_values as  $k2 => $v2){
          $data = $this->evaluate_expression($v2);
          $filters[$param.'.'.$data['value'][0]] = $data['value'][1];
        }
      } else {
        $nested_params = $this->get_nested_params($this->get_param('filters'));
        foreach($nested_params as $k1 => $v1) {
          $nested_param_values = explode(",", $v1);
          foreach($nested_param_values as  $k2 => $v2){
            $data = $this->evaluate_expression($v2);
            $filters[$k1.'.'.$data['field']] = $data['value'][1];
          }
        }
      }
      return $filters;
    }
  }

  public function get_param($var)
  {
    return ee()->input->get($var);
  }

  public function get_nested_params($subject)
  {
    $params = array();

    $values = array_values(array_filter(preg_split('/[^,]*.[:?]/', $subject)));
    
    if(preg_match_all('/[^,]*.[:?]/', $subject, $matches)) {
      foreach($matches[0] as $key => $value) {
        $params[str_replace(':', '', $value)] = trim($values[$key], '[],');
      }
    }
    return $params;
  }

  private function evaluate_expression($subject)
  {
    $field = '';
    $value = array();
    $data = array();

    preg_match('/[%](?!%)/', $subject, $matches);
    if(!$matches) {
      if (preg_match('/(?<!\!)[^a-z0-9_!]/', $subject))  // =
      {
        $value = explode('=', $subject);
        $field = $value[0].' = ';
      } 
      if(preg_match('/(?<=\!)[^a-z0-9_!]/', $subject)) // !=
      {
        $value = explode('!=', $subject);
        $field = $value[0].' != ';
      } 
      if(preg_match('/[<](?!\=)/', $subject)) // <
      {  
        $value = explode('<', $subject);
        $field = $value[0].' < ';
      }
      if(preg_match('/[>](?!\=)/', $subject)) // >
      {  
        $value = explode('>', $subject);
        $field = $value[0].' > ';
      }
      if(preg_match('/[<](?:\=)/', $subject)) // <= 
      {  
        $value = explode('<=', $subject);
        $field = $value[0].' <= ';
      }
      if(preg_match('/[>](?:\=)/', $subject)) // >= 
      {  
        $value = explode('>=', $subject);
        $field = $value[0].' >= ';
      }
    } else {
      $matches1 = preg_match('/(?<!\%)[\%](?!\%)/', $subject); // like ends with %<value>
      $matches2 = preg_match('/(\%)(?:[a-z0-9])(?=\b[^\%])/', $subject); // like ends with %<value>
      if($matches1)
      {
        $value = preg_split('/[=%]/', $subject);
        $value[1] = $value[1].'%';
        $field = $value[0].' like ';
      }
      else if($matches2) {  
        $value = preg_split('/[=%]/', $subject);
        $value[1] = '%'.$value[2];
        $field = $value[0].' like ';
      }
    }
    $data['field'] = $field;
    $data['value'] = $value;
    return $data;
  }
}