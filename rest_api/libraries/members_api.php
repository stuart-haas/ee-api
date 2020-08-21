<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once PATH_THIRD . 'rest_api/libraries/api_lib.php';
require_once PATH_THIRD . 'rest_api/libraries/iapi_lib.php';

class Members_api extends Api_lib {

  const LIMIT = 10;

  private $members = "'Chris Mahaffey','Kim Griffith','Candace Lewis','Philip Brooks','Dirk Elliott','Alicia Julia Stanley','Bryan Brooks','Chad Brown','Carol Buser','Terry Carty','Zach Clayton','Andre Contino','Miguel DeGuzman','Junius Dotson','Ola Filemoni','Ana Gan','Tommy Gossett','Francis Hennessy','Lia Icaza-Willetts','Debra Irving','David Johnson','Helen Kemper','Jamie Lewis','Castaneda Lliana','Brian Marcoulier','Matt Matulewicz','Elizabeth McVicker','Randy Neff','Deborah Oh','Jacki Ratledge','Linda Reid','Pamela Sattiewhite','Iris Smith','Stephen Sparks','Karl Stamm','Nathan Stanton','Emma Vega','Paul Joseph Yang','Kara Oliver','Stephen Bryant','Armando Arellano', 'Philip Brooks', 'Stephen Bryant'";

  public function __construct(){}

  public function index()
  {
    ee()->load->helper('url');
    
    if(isset($_GET['site'])) {
      $site = $_GET['site'];
    }

    if(isset($_GET['limit'])) {
      $limit = $_GET['limit'];
    } else {
      $limit = Members_api::LIMIT;
    }

    if(isset($_GET['page'])) {
      $page = $_GET['page'];
    } else {
      $page = 1;
    }

    $offset = ($page - 1) * $limit;

    if(isset($site)) {
      $query = "FROM exp_members m LEFT JOIN exp_member_groups mg ON m.group_id = mg.group_id WHERE mg.site_id = $site AND m.screen_name IN ($this->members)";

      $members = ee()->db->query(
        "SELECT * ".$query." LIMIT $offset, $limit"
      )->result_array();
    } else {
      $query = "FROM exp_members m LEFT JOIN exp_member_groups mg ON m.group_id = mg.group_id";

      $categories = ee()->db->query(
        "SELECT * ".$query." LIMIT $offset, $limit"
      )->result_array();
    }

    $content = array();

    $total_rows = intVal(ee()->db->query("SELECT COUNT(*) AS count ".$query)->row('count'));

    $content['meta'] = $this->pagination($total_rows, $page, $limit);

    foreach ($members as $id => $row) {
      $content['data'][] = array(
        'group_id' => $row['group_id'],
        'screen_name' => $row['screen_name'],
        'group_id' => $row['group_id'],
        'group_title' => $row['group_title']
      );
    }

    $this->response($content);
  }
}