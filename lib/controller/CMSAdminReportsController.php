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
    $info = $this->parse_metric_columns($graph, $data);
    $parsed = array(array($info['primary_name'], $info['secondary_name']));
    //group by the primary & also include it in the return
    $results = $data->group($primary_metric);
    $results->select_columns = array_merge(array($data->model->primary_key), array($info['primary_metric'] ." AS primary_metric"), array($info['secondary_metric'] ." AS secondary_metric"));
    $results = $results->order($graph->order_results)->all();

    foreach($results as $res) $parsed[] = array($res->row['primary_metric'], $res->row['secondary_metric']);

    return $parsed;
  }

  public function complex_metric($data, $graph){
    $parsed = array();
    $info = $this->parse_metric_columns($graph, $data);
    $titles = array($info['primary_name']);

    //so no grouping on this
    $results = $data->group($info['primary_metric'].", ". $info['secondary_col']);
    $results->select_columns = array_merge(array($data->model->primary_key), array("count(*) as cnt"), array($info['primary_metric'] ." AS primary_metric"), array($info['secondary_col'] ." AS secondary_metric"));
    $results = $results->order($graph->order_results)->all();

    $class = get_class($data->model);
    $rows = array();
    foreach($results as $r){
      $p = $r->row['primary_metric'];
      $s = $r->row['secondary_metric'];
      if($s){
        $model = new $class($r->row[$data->model->primary_key]);
        if($val = $model->humanize($info['secondary_metric'])) $titles[$s] = $val;
        else $titles[$s] = "n/a";
        $rows[$p][$s] = $r->row['cnt'];
      }
    }

    $parsed[0] = array_values($titles);
    $i=1;
    foreach($rows as $key=>$r){
      $parsed[$i][] = $key;
      foreach($titles as $id=>$t){
        if($r[$id]) $parsed[$i][] = $r[$id];
      }
      if(count($parsed[$i]) < count($titles)) for($x=count($parsed[$i]); $x < count($titles); $x++) $parsed[$i][$x] = 0;
      $i++;
    }

    return $parsed;
  }

  public function parse_metric_columns($graph, $data){
    $primary_metric = stripslashes(str_replace("?", $graph->primary_metric_column, $graph->primary_metric_function));
    $secondary_metric = stripslashes($graph->secondary_metric_column);

    $class = get_class($data->model);
    $model = new $class;
    $info = $model->columns[$graph->primary_metric_column];
    if(!$primary_name = $info['label']) $primary_name = Inflections::humanize($graph->primary_metric_column);
    if(($info = $model->columns[$graph->secondary_metric_column])){
      if(!$secondary_name = $info['label']) $secondary_name = Inflections::humanize($graph->secondary_metric_column);
      $col = $model->get_col($graph->secondary_metric_column);
      $secondary_col = $col->col_name;
    }else $secondary_name = $graph->secondary_metric_column;

    $res = array('primary_metric'=>$primary_metric, 'secondary_metric'=>$secondary_metric, 'primary_name'=>$primary_name, 'secondary_name'=>$secondary_name, 'primary_col'=>$graph->primary_metric_column, 'secondary_col'=>$secondary_col);
    return $res;
  }

}

?>