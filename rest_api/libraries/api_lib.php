<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'rest_api/libraries/iapi_lib.php';

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

  protected function pagination($total_rows, $page, $limit)
  {
    $total_pages = ceil($total_rows / $limit);
    
    $current_page = intVal($page);
    $prev_page = $page - 1;
    $next_page = $page + 1;

    return array(
      'prev_page_url' => $prev_page > 0 ? $this->pagination_url($current_page, $prev_page) : '',
      'next_page_url' => $next_page <= $total_pages ? $this->pagination_url($current_page, $next_page) : '',
      'current_page' => $current_page,
      'prev_page' => $prev_page > 0 ? $prev_page : '',
      'next_page' => $next_page <= $total_pages  ? $next_page : '',
      'total_rows' => $total_rows,
      'total_pages' => $total_pages
    );
  }

  protected function pagination_url($last_page, $current_page)
  {
    $url = site_url().uri_string();
    $query_string = $_SERVER['QUERY_STRING'];
    if($last_page > 1) {
      $query_string = str_replace('page='.$last_page, 'page='.$current_page, $query_string);
      return $url.'?'.$query_string;
    } else {
      return $url.'?'.$query_string.'&page='.$current_page;
    }
  }
}