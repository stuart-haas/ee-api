<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'rest/libraries/iapi_lib.php';

class Api_lib implements iApi_lib {

  public $status = array(
    200 => '200 OK',
    400 => '400 Bad Request',
    401 => '401 Unauthorize',
    500 => '500 Internal Server Error'
  );

  public function __construct()
  {
		ee()->load->add_package_path(PATH_THIRD.'rest');
	}

  public function call($method = '')
  {
    ob_start();

    if(!method_exists($this, $method))
    {
      $this->response('Method does not exist', 400);
    }

    $this->$method();
  }

  public function response($data, $code = 200)
  {
    ob_clean();

    $response = json_encode($data);

    header_remove();
    header('Content-Type: application/json');

    ee()->output->set_status_header($code);

    echo $code > 200 ? json_encode(array('status' => $this->status[$code], 'message' => $data)) : $response;

    exit();
  }
}