<?php

class CMSAdminReportsController extends AdminComponent{

  public $module_name = "reports";
  public $model_class = 'WildfireReport';
  public $display_name = "Reports";
  public $dashboard = false;
  public $operation_actions = array('edit', 'view');


  public function view(){
    WaxEvent::run("cms.model.init", $this);
    WaxEvent::run("cms.form.setup", $this);
    //swap the model_class
    $this->per_page = false;
    $this->report = $this->model;
    $this->model_class = $this->report->data_model;
    //at this point need to change the filters to be from the controller handling the data
    WaxEvent::run("cms.model.init", $this);
    WaxEvent::run("cms.index.setup", $this);
    $this->graph_data = array();

    //go over each graph
    foreach($this->report->graphs as $graph){

      $data = $this->cms_content;
      $gdata = array();
      $secondary = $graph->secondary_metric_column;

      $class = get_class($data->model);
      $model = new $class;

      if($model->columns[$secondary]) $gdata = $this->complex_metric($data, $graph);
      else $gdata = $this->simple_metric($data, $graph);

      $this->graph_data[] = array('results'=>$gdata, 'graph'=>$graph);
    }

    print_r($this->graph_data);

    exit;
  }

  public function simple_metric($data, $graph){
    list($primary_metric, $secondary_metric, $primary_name, $secondary_name) = $this->parse_metric_columns($graph, $data);
    $parsed = array(array($primary_name, $secondary_name));
    //group by the primary & also include it in the return
    $results = $data->group($primary_metric);
    $results->select_columns = array_merge(array($data->model->primary_key), array($primary_metric ." AS primary_metric"), array($secondary_metric ." AS secondary_metric"));
    $results = $results->order($graph->order_results)->all();

    foreach($results as $res) $parsed[] = array($res->row['primary_metric'], $res->row['secondary_metric']);

    return $parsed;
  }

  public function complex_metric($data, $graph){
    $parsed = array();
    list($primary_metric, $secondary_metric, $primary_name, $secondary_name) = $this->parse_metric_columns($graph, $data);

    $titles = array($primary_name=>1);

    //so no grouping on this
    $results = $data->group($primary_metric.", ". $secondary_metric);
    $results->select_columns = array_merge(array($data->model->primary_key), array("count(*) as cnt"), array($primary_metric ." AS primary_metric"), array($secondary_metric ." AS secondary_metric"));
    $results = $results->order($graph->order_results)->all();

    foreach($results as $r){
      $titles[$r->row['secondary_metric']] =1;
    }
    print_r($titles);

    return $parsed;


    exit;
  }

  public function parse_metric_columns($graph, $data){
    $primary_metric = stripslashes(str_replace("?", $graph->primary_metric_column, $graph->primary_metric_function));
    $secondary_metric = stripslashes($graph->secondary_metric_column);

  }

}

?>