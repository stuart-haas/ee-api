<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'rest/libraries/api_lib.php';
require_once PATH_THIRD . 'rest/libraries/iapi_lib.php';

class Categories_api extends Api_lib {

  public function __construct(){}

  public function get()
  {
    ee()->load->model('categories_model');

    $data = ee()->categories_model->get();

    $this->response($data);
  }
}