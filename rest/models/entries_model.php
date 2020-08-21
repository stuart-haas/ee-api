<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Entries_model {

  public function get()
  {
    ee()->load->model('query_model');

    $fields = ee()->query_model->get_fields('entries');
    $filters = ee()->query_model->get_filters();

    ee()->db->select($fields);
    ee()->db->from('channel_titles entries');

    if(ee()->query_model->get_nested_params(ee()->query_model->get_param('filters'))['categories'])
    {
      ee()->db->join('category_posts cp', 'cp.entry_id = entries.entry_id');
      ee()->db->join('categories', 'cp.cat_id = categories.cat_id');
    }

    if($filters !== null)
    {
      ee()->db->where($filters);
    }

    $query = ee()->db->get();
      
    $results = $query->result_array();

    if(ee()->query_model->get_param('categories') !== false)
    {
      ee()->load->model('categories_model');

      foreach($results as $key => $value) 
      {
        $results[$key]['categories'] = ee()->categories_model->get($results[$key]['entry_id']);
      }
    }

    return $results;
  }
}