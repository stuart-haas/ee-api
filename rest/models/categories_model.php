<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Categories_model {

  public function get($entry_id = '')
  {
    ee()->load->model('query_model');

    $cat_id = ee()->uri->segment(4);
    $embedded = ee()->query_model->get_param('embedded');

    if(!is_string($embedded) && empty($cat_id)) {
      $fields = 'categories.cat_id';
    } else {
      $fields = ee()->query_model->get_fields('categories');
    }

    ee()->db->select($fields);
    ee()->db->from('categories');

    if($entry_id) {
      ee()->db->join('category_posts cp', 'cp.cat_id = categories.cat_id');
      ee()->db->where('cp.entry_id = '.$entry_id);
    } else if($cat_id) {
      ee()->db->where('categories.cat_id = '.$cat_id);
    } else if(ee()->query_model->get_param('filters')) {
      $filters = ee()->query_model->get_filters();
      ee()->db->where($filters);
    }

    $query =  ee()->db->get();

    if(!is_string($embedded) && empty($cat_id))
    {
      ee()->load->helper('url');

      $results = array();

      $query_results = $query->result_array();

      foreach($query_results as $k1 => $v1)
      {
        foreach($v1 as $k2 => $v2) 
        {
          $results[] = Query_model::api_url().'/categories/get/'.$v1[$k2];
        }
      }

      return $results;
    } 

    return $query->result_array();
  }
}