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
      //parse the data based on the kind of graph and metrics
      //pie chart is the same as grouping by count
      if(($graph->type == "PieChart" && ($graph->basic_secondary_metric = "count(*)")) || $graph->basic_secondary_metric) $this->graph_data[] = $this->simple_metric($data, $graph);

    }



    exit;
  }

  public function simple_metric($data, $graph){
    $primary_metric = stripslashes($graph->primary_metric);
    $primary_name = $graph->primary_metric_name;
    $secondary_metric = stripslashes($graph->basic_secondary_metric);
    $secondary_name = $graph->secondary_metric_name;

    $parsed = array(array($primary_name, $secondary_name));
    $original = $data;
    //group by the primary & also include it in the return
    $results = $data->group($primary_metric);
    $results->select_columns = array_merge(array("*"), array($primary_metric ." AS primary_metric"));
    $results = $results->all();

    foreach($results as $res){
      $filter = $res->row['primary_metric'];
      $look = $data->group($primary_metric);
      $look->select_columns = array_merge(array("*"), array($secondary_metric ." AS secondary_metric"));
      $found = $look->filter($primary_metric."='".$filter."'")->first();
      $parsed[] = array($filter, $found->row['secondary_metric']);
    }
    return $parsed;
  }

}

?>