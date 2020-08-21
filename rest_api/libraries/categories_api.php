<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once PATH_THIRD . 'rest_api/libraries/api_lib.php';
require_once PATH_THIRD . 'rest_api/libraries/iapi_lib.php';

class Categories_api extends Api_lib {

  const LIMIT = 10;

  public function __construct(){}

  public function index()
  {
    ee()->load->helper('url');
    
    if(isset($_GET['group'])) {
      $group = $_GET['group'];
    }

    if(isset($_GET['limit'])) {
      $limit = $_GET['limit'];
    } else {
      $limit = Categories_api::LIMIT;
    }

    if(isset($_GET['page'])) {
      $page = $_GET['page'];
    } else {
      $page = 1;
    }

    $offset = ($page - 1) * $limit;

    if(isset($group)) {
      $query = "FROM exp_categories WHERE group_id = '$group'";

      $categories = ee()->db->query(
        "SELECT * ".$query." LIMIT $offset, $limit"
      )->result_array();
    } else {
      $query = "FROM exp_categories";

      $categories = ee()->db->query(
        "SELECT * ".$query." LIMIT $offset, $limit"
      )->result_array();
    }

    $content = array();

    $total_rows = intVal(ee()->db->query("SELECT COUNT(*) AS count ".$query)->row('count'));

    $content['meta'] = $this->pagination($total_rows, $page, $limit);

    foreach ($categories as $id => $row) {
      $content['data'][] = array(
        'cat_id' => $row['cat_id'],
        'group_id' => $row['group_id'],
        'cat_name' => $row['cat_name'],
        'cat_url_title' => $row['cat_url_title'],
        'cat_description' => $row['cat_description'],
        'cat_image' => $this->format_asset_url($row['cat_image']),
        'cat_order' => $row['cat_order']
      );
    }

    $this->response($content);
  }

  private function format_asset_url($data) 
  {
    return preg_replace("/\{([^}]+)\}/", site_url().'content/', $data);
  }
}