<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once PATH_THIRD . 'rest_api/libraries/api_lib.php';
require_once PATH_THIRD . 'rest_api/libraries/iapi_lib.php';

class Entries_api extends Api_lib {

  const LIMIT = 10;

  public function __construct(){}

  public function index()
  {
    ee()->load->helper('url');
    
    if(isset($_GET['channels'])) {
      $channels = explode(",", $_GET['channels']);
      $channels = "'" . implode("','", $channels) . "'";
    }

    if(isset($_GET['limit'])) {
      $limit = $_GET['limit'];
    } else {
      $limit = Entries_api::LIMIT;
    }

    if(isset($_GET['categories'])) {
      $categories = explode(",", $_GET['categories']);
      $categories = implode(",", $categories);
    }

    if(isset($_GET['categories_exclude'])) {
      $categories_exclude = explode(",", $_GET['categories_exclude']);
      $categories_exclude = implode(",", $categories_exclude);
    }

    if(isset($_GET['fields'])) {
      $fields = explode(",", $_GET['fields']);
      $fields = "'" . implode("','", $fields) . "'";
    }

    if(isset($_GET['ids'])) {
      $ids = explode(",", $_GET['ids']);
      $ids = implode(",", $ids);
    }

    if(isset($_GET['ids_exclude'])) {
      $ids_exclude = explode(",", $_GET['ids_exclude']);
      $ids_exclude = implode(",", $ids_exclude);
    }

    if(isset($_GET['page'])) {
      $page = $_GET['page'];
    } else {
      $page = 1;
    }

    $offset = ($page - 1) * $limit;

    if(isset($fields)) {
      $fields = ee()->db->query(
        "SELECT * FROM exp_channel_fields 
        LEFT JOIN exp_channels ON exp_channel_fields.group_id = exp_channels.field_group 
        WHERE exp_channels.channel_id IN ($channels) AND exp_channel_fields.field_name IN ($fields)"
      )->result_array();
    } else {
      $fields = ee()->db->query(
        "SELECT * FROM exp_channel_fields 
        LEFT JOIN exp_channels ON exp_channel_fields.group_id = exp_channels.field_group 
        WHERE exp_channels.channel_id IN ($channels)"
      )->result_array();
    }

    if(isset($categories)) {
      $query = 
        "FROM exp_channel_data cd 
        LEFT JOIN exp_channel_titles ct ON cd.entry_id = ct.entry_id 
        LEFT JOIN exp_category_posts cp ON ct.entry_id = cp.entry_id
        WHERE ct.channel_id IN ($channels) AND cp.cat_id IN ($categories)";

      if(isset($ids)) {
        $query.=" AND ct.entry_id IN ($ids)";
      }

      if(isset($ids_exclude)) {
        $query.=" AND ct.entry_id NOT IN ($ids_exclude)";
      }

      if(isset($categories_exclude)) {
        $query.=" AND ct.entry_id NOT IN (SELECT ct.entry_id FROM exp_channel_titles ct LEFT JOIN exp_category_posts cp ON cp.entry_id = ct.entry_id WHERE cp.cat_id IN ($categories_exclude) GROUP BY ct.entry_id)";
      }

      $entries = ee()->db->query(
        "SELECT * ".$query." GROUP BY ct.entry_id LIMIT $offset, $limit"
      )->result_array();

    } else {
      if(isset($channels)) {
        $query = 
        "FROM exp_channel_data cd 
        LEFT JOIN exp_channel_titles ct ON cd.entry_id = ct.entry_id
        WHERE ct.channel_id IN ($channels)";

        if(isset($ids)) {
          $query.=" AND cd.entry_id IN ($ids)";
        }

        if(isset($ids_exclude)) {
          $query.=" AND ct.entry_id NOT IN ($ids_exclude)";
        }

        $entries = ee()->db->query(
          "SELECT * ".$query." GROUP BY ct.entry_id LIMIT $offset, $limit"
        )->result_array();

      } else {
        $query = 
        "FROM exp_channel_data cd 
        LEFT JOIN exp_channel_titles ct ON cd.entry_id = ct.entry_id";

        if(isset($ids)) {
          $query.=" AND ct.entry_id IN ($ids)";
        }

        if(isset($ids_exclude)) {
          $query.=" AND ct.entry_id NOT IN ($ids_exclude)";
        }

        $entries = ee()->db->query(
          "SELECT * ".$query." GROUP BY ct.entry_id LIMIT $offset, $limit"
        )->result_array();
      }
    }

    $total_rows = ee()->db->query("SELECT ct.entry_id,COUNT(*) AS count ".$query." GROUP BY ct.entry_id")->num_rows();
    
    $content['meta'] = $this->pagination($total_rows, $page, $limit);

    foreach ($entries as $id => $row) {

      $content['data'][$id] = array();
      $entry_id = $row['entry_id'];
      $content['data'][$id]['entry_id'] = $row['entry_id'];
      //$content['data'][$id]['title'] = trim($this->clean_up_title($row['title']));
      $content['data'][$id]['title'] = html_entity_decode($row['title'], ENT_QUOTES | ENT_HTML5);
      $content['data'][$id]['url_title'] = $row['url_title'];
      $content['data'][$id]['status'] = $row['status'] == 'closed' ? 'disabled' : 'enabled';
      $content['data'][$id]['enabled'] = $row['status'] == 'closed' ? 0 : 1;
      $content['data'][$id]['entry_date'] = ($row['entry_date']) ? date('Y-m-d H:i:s', $row['entry_date']) : '';
      $content['data'][$id]['expiration_date'] = ($row['expiration_date']) ? date('Y-m-d H:i:s', $row['expiration_date']) : '';
      $content['data'][$id]['author_id'] = $row['author_id'];
      //$content['data'][$id]['podcast_channel'] = $this->get_podcast_channel($row['title']);
      $content['data'][$id]['article_category'] = 'News';

      $categories = ee()->db->query(
        "SELECT * FROM exp_categories c
        JOIN exp_category_posts cp ON cp.cat_id = c.cat_id
        JOIN exp_category_groups cg ON cg.group_id = c.group_id
        WHERE cp.entry_id = $entry_id"
      );

      $content['data'][$id]['categories'] = array();

      if($categories !== false) {
        foreach ($categories->result_array() as $categories) {
          $content['data'][$id]['categories'][$categories['group_name']][] = array(
            'cat_id' => $categories['cat_id'],
            'cat_name' => $categories['cat_name']
          );
        }
      }

      if($content['data'][$id]['categories']['The Latest - Author'] == false) {
        $content['data'][$id]['categories']['Legacy Author'][] = array(
          'cat_name' => 'UMC Young People'
        );
      }

      foreach ($fields as $field) {
        // P&T Matrix field
        if($field['field_type'] == 'matrix') {
          $query = ee()->db->query("SELECT * FROM exp_matrix_cols WHERE field_id=".$field['field_id']);

          foreach ($query->result_array() as $matrix_col) {
            $query = ee()->db->query("SELECT * FROM exp_matrix_data WHERE field_id=".$field['field_id']." AND entry_id=".$row['entry_id']." ORDER BY row_order");

            if($query !== false) {
                foreach ($query->result_array() as $key => $matrix_row) {
              
                if($matrix_col['col_type'] == 'file') {
                  // File field
                  ee()->load->library('file_field');
                  $fileInfo = ee()->file_field->parse_field($matrix_row['col_id_'.$matrix_col['col_id']]);
                  
                  $content['data'][$id][$field['field_name']][$key][$matrix_col['col_name']] = $fileInfo['url'];

                } elseif ($matrix_col['col_type'] == 'assets') {
                  // Assets field
                  $content['data'][$id][$field['field_name']][$key][$matrix_col['col_name']] = $this->get_asset_url($entry_id, $matrix_col['col_id']);

                } else {
                  // Any other field
                  $content['data'][$id][$field['field_name']][$key][$matrix_col['col_name']] = $matrix_row['col_id_'.$matrix_col['col_id']];
                }
              }
            }
          }
        // P&T Playa field
        } elseif ($field['field_type'] == 'playa') {
          // Playa field
          $value = preg_replace("/\[[^]]+\]/", '', $row['field_id_' . $field['field_id']]);
          $content['data'][$id][$field['field_name']] = trim($value);

        } elseif ($field['field_type'] == 'file') {
          // File field
          ee()->load->library('file_field');
          $fileInfo = ee()->file_field->parse_field($row['field_id_' . $field['field_id']]);
          $content['data'][$id][$field['field_name']] = $fileInfo['url'];
          
        } elseif ($field['field_type'] == 'assets') {
          // P&T Assets field
          $value = $row['field_id_' . $field['field_id']];
          if($value) {
            try {
              $content['data'][$id][$field['field_name']] = $this->get_asset_url($entry_id);
            } 
            catch(Exception $e) {
              $content['data'][$id][$field['field_name']] = site_url().'content/'.$value;
            }
          } else {
            $content['data'][$id][$field['field_name']] = $value;
          }
        } else {
          // Any other field
          if($field['field_name'] == "article_body") {

            $html = $row['field_id_' . $field['field_id']];
            $content['data'][$id][$field['field_name']] = $this->remove_iframe($this->replace_asset_html($html));

            //$content['data'][$id]['podcast_embed'] = $this->extract_iframe($html);

          } else if($field['field_name'] == "address") {

            $data = $row['field_id_' . $field['field_id']];
            $content['data'][$id][$field['field_name']]  = $data;
            $data = explode("|", $data);
            $data = $data[1].','.$data[2];
            $content['data'][$id]['formatted_address'] = $this->get_location($data);

          } else if($field['field_name'] == "description_copy") {

            $html = $row['field_id_' . $field['field_id']];
            $content['data'][$id][$field['field_name']] = $html;
            $url = $this->get_url_from_html($html);
            if($url) {
              $content['data'][$id]['mission_web_site'] = $url;
            }

          } else if($field['field_name'] == "mission_web_site") {

            $data = $row['field_id_' . $field['field_id']];
            if($data) {

              if (strpos($data,'http://') === false && strpos($data,'https://') === false){
                $content['data'][$id][$field['field_name']] = 'http://'.$data;
              } else if (strpos($data,'https://') === false && strpos($data,'http://') === false){
                $content['data'][$id][$field['field_name']] = 'https://'.$data;
              } else {
                $content['data'][$id][$field['field_name']] = $data;
              }
            }

          } else {
            $content['data'][$id][$field['field_name']] = $row['field_id_' . $field['field_id']];
          }
        }
      }  
    }
    $this->response($content);
  }

  private function remove_iframe($html) {
    return preg_replace_callback('/<p><iframe\s+.*?\s+src=(".*?").*?<\/iframe><\/p>/', function($match) {
      return preg_replace('/<p><iframe\s+.*?\s+src=(".*?").*?<\/iframe><\/p>/', '', $match[0]);
    }, $html);
}

  private function extract_iframe($html)
  {
    if(preg_match_all('/<iframe\s+.*?\s+src=(".*?").*?<\/iframe>/', $html, $matches)) {
      return $matches[0][0];
    }
  }

  private function clean_up_title($title)
  {
    if (strpos($title, '-') !== false) {

      $title = explode('-', $title);
      return $title[1];

    } else if(strpos($title, ':') !== false) {

      $title = explode(':', $title);
      return $title[1];

    } else if(strpos($title, '003') !== false || strpos($title, '208') !== false) {

      $title = explode('with', $title);
      return $title[1];
      
    } else {
      return $title;
    }
  }

  private function get_podcast_channel($title)
  {
    if (strpos(strtolower($title), 'reverb') !== false) {
      return 'Reverb';
    }  
    else if (strpos(strtolower($title), 'conspiritor') !== false) {
      return 'The Conspiritor Collective';
    }
  }

  private function get_asset_url($entry_id, $col_id = '') 
  {
    ee()->load->add_package_path(PATH_THIRD.'assets/');
    ee()->load->library('assets_lib');

    $asset = ee()->db->query(
      "SELECT DISTINCT a.source_type, a.folder_id, a.file_name, a.file_id, af.source_id, af.filedir_id
      FROM exp_assets_files AS a
      INNER JOIN exp_assets_selections AS ae ON ae.file_id = a.file_id
      INNER JOIN exp_assets_folders AS af ON af.folder_id = a.folder_id
      WHERE ae.entry_id = $entry_id")->result_array();
    
    if($asset !== false) {

      $asset_id = $asset[0]['file_id'];
      
      return ee()->assets_lib->get_file_url($asset_id);;
    }
  }

  private function replace_asset_html($html) {
      return preg_replace_callback('/\{([^}]+)\}/', function($match) {
        return preg_replace("/\{assets_(?!x)[0-9:]+|[{}]/", '', $match[0]);
      }, $html);
  }

  private function get_location($latlng)
  {
    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latlng}&key=AIzaSyDZYeiZ76M_UvPl2EF9PHvN3UdpSOmz_TQ";

    $resp_json = file_get_contents($url);

    $resp = json_decode($resp_json, true);

    $data = array();

    if($resp['status']=='OK'){
      $code = $resp['plus_code']['compound_code'];
      $address = $resp['results'][0]['address_components'];
      foreach($address as $index => $row) {
        foreach($row as $col) {
          if(is_array($col)) {
            foreach($col as $type) {
              
              if(strpos($code, 'USA') !== false) {
                if($type == 'locality' || $type == 'neighborhood') {
                  $data['city'] = $row['long_name'];
                }
      
                if($type == 'administrative_area_level_1') {
                  $data['state'] = $row['long_name'];;
                }
              } else {
                if($type == 'administrative_area_level_1') {
                  $data['city'] = $row['long_name'];
                }
      
                if($type == 'country') {
                  $data['state'] = $row['long_name'];;
                }
              }
            }
          }
        }
      }
      return $data['city'].', '.$data['state'];
    }
  }

  private function get_url_from_html($html)
  {
    $regexp = '<a\s[^>]*href=(\"??)((http|https)[^\" >]*?)\\1[^>]*>(.*)<\/a>';
    if(preg_match_all("/$regexp/siU", $html, $matches)) {
      return $matches[2][0];
    }
  }
}