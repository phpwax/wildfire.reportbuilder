<?php

class CMSAdminReportsController extends AdminComponent{

  public $module_name = "reports";
  public $model_class = 'WildfireReport';
  public $display_name = "Reports";
  public $dashboard = false;
  public $operation_actions = array('edit', 'view');
  public $model_scope = "live";
  public $preset_filters = array();

  public function sorting(){
    if($sort = Request::param('sort')){
      foreach($sort as $s){
        $model = new WaxModel;
        $model->table = "wildfire_graph_wildfire_report";
        if($found = $model->filter("wildfire_graph_id", $s['graph'])->filter("wildfire_report_id", $s['report'])->first()){
          $sql = "UPDATE ".$model->table." SET `join_order`=".$s['order']." WHERE `id`=".$found->row['id'];
          $model::$db->query($sql);
        }
      }
    }
    exit;
  }

  public function view(){
    WaxEvent::run("cms.model.init", $this);
    WaxEvent::run("cms.form.setup", $this);

    //swap the model_class
    $this->per_page = false;
    $this->report = $this->model;
    $this->model_class = $this->report->data_model;

    //at this point need to change the filters to be from the controller handling the data
    foreach(CMSApplication::get_modules() as $name=>$info){
      if($name != "home"){
        $class = "Admin".Inflections::camelize($name,true)."Controller";
        $obj = new $class(false, false);
        if($obj->model_class == $this->model_class){
          $this->filter_fields = $obj->filter_fields;
          break;
        }
      }
    }

    WaxEvent::run("cms.model.init", $this);
    WaxEvent::run("cms.model.setup", $this);
    //WaxEvent::run("cms.index.setup", $this);

    $this->graph_data = array();

    //run filters on to the cms content
    //go over each graph
    foreach($this->report->graphs as $graph){

      $this->model = new $this->model_class;
      //run filters on the model
      WaxEvent::run("cms.model.filters", $this);
      $this->preset_filters = $this->model->filters;
      $gdata = array();
      $secondary = $graph->secondary_metric_column;
      if($model->columns[$secondary]) $gdata = $this->complex_metric($this->model, $graph);
      else $gdata = $this->simple_metric($this->model, $graph);
      $this->graph_data[] = array('results'=>$gdata, 'graph'=>$graph);

    }
    $this->model = $this->report;

  }

  public function pdf(){
    $server = "http://".$_SERVER['HTTP_HOST'];
    $hash = "ex".date("Ymdhis");
    $folder = WAX_ROOT."tmp/export/";
    $primval = Request::get("id");
    mkdir($folder.$hash, 0777, true);
    $file = $folder.$hash."/".$this->module_name."-".$primval.".pdf";
    $permalink = "/admin/".$this->module_name."/view/".$primval."/.print?auth_token=".$this->current_user->auth_token;
    if($filters = Request::param('filters')) foreach($filters as $k=>$v) $permalink.="&filters[".$k."]=".$v;
    $command = '/usr/bin/xvfb-run -a -s "-screen 0 1024x768x16" /usr/bin/wkhtmltopdf --encoding utf-8 -s A4 -T 0mm -B 20mm -L 0mm -R 0mm "'.$server.$permalink.'" '.$file;
    shell_exec($command);
    WaxLog::log('error', '[pdf] '.$command, "pdf");
    header("Content-type: application/pdf");
    header("Content-Disposition: attachment; filename=Report-".$primval."-".$hash);
    header("Pragma: no-cache");
    header("Expires: 0");
    $content = file_get_contents($file);
    unlink($file);
    foreach(glob($folder.$hash."/*") as $f) unlink($f);
    rmdir($folder.$hash);
    echo $content;
  }

  protected function simple_metric($data, $graph){
    $info = $this->parse_metric_columns($graph, $data);
    $parsed = array(array($info['primary_name'], $info['secondary_name']));
    //group by the primary & also include it in the return
    $results = $data->group($info['primary_metric']);
    $class = get_class($data);
    $cols = array_merge(array($data->primary_key), array($info['primary_metric'] ." AS primary_metric"));
    if($info['secondary_metric']) $cols = array_merge($cols, array($info['secondary_metric'] ." AS secondary_metric"));
    $cols = array_filter($cols);
    $results->select_columns = $cols;
    $results->filters = $this->preset_filters;
    if($graph->condition) $results->filter(stripslashes($graph->condition));
    $results = $results->order(($info['order_by']) ? $info['order_by'] : $info['primary_col'] ." ASC")->all();

    foreach($results as $res){
      $model = new $class($res->row[$data->model->primary_key]);
      if($val = $model->humanize($info['primary_label'])) $use = $val;
      else $use = $res->row['primary_metric'];
      $parsed[] = array($use, $res->row['secondary_metric']);
    }
    return $parsed;
  }

  protected function complex_metric($data, $graph){
    $parsed = array();
    $info = $this->parse_metric_columns($graph, $data);
    $titles = array($info['primary_name']);

    //so no grouping on this
    $results->filters = $this->preset_filters;
    $results = $data->group($info['primary_metric'].", ". $info['secondary_col']);
    $results->select_columns = array_merge(array($data->primary_key), array("count(*) as cnt"), array($info['primary_metric'] ." AS primary_metric"), array($info['secondary_col'] ." AS secondary_metric"));
    $results = $results->order(($info['order_by']) ? $info['order_by'] : $info['primary_col'] ." ASC")->all();


    $class = get_class($data);
    $rows = array();
    foreach($results as $r){
      $p = $r->row['primary_metric'];
      $s = $r->row['secondary_metric'];
      if($s){
        $model = new $class($r->row[$data->model->primary_key]);
        if($val = $model->humanize($info['secondary_label'])) $titles[$s] = $val;
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

  protected function parse_metric_columns($graph, $data){

    $class = get_class($data);
    $model = new $class;
    $primary_col = $model->get_col($graph->primary_metric_column);
    $secondary = array();
    $primary = array(
                    'primary_name'    => ($primary_col->label) ? $primary_col->label : Inflections::humanize($graph->primary_metric_column),
                    'primary_col'     => $primary_col->col_name,
                    'primary_label'   => $graph->primary_metric_column,
                    'primary_metric'  => ($graph->primary_metric_function) ? stripslashes(str_replace("?", $primary_col->col_name, $graph->primary_metric_function)) : $primary_col->col_name,
                    'primary_col_obj' => $primary_col,
                    'order_by'        => $graph->order_by
                    );
    if($graph->secondary_metric_column && ($model->columns[$graph->secondary_metric_column]) && ($secondary_col = $model->get_col($graph->secondary_metric_column))){

      $secondary = array(
                      'secondary_name'    => ($secondary_col->label) ? $secondary_col->label : Inflections::humanize($graph->secondary_metric_column),
                      'secondary_col'     => $secondary_col->col_name,
                      'secondary_label'   => $graph->secondary_metric_column,
                      'secondary_metric'  => $secondary_col->col_name,
                      'secondary_col_obj' => $secondary_col,
                      'order_by'          => $graph->order_by
                      );
    }elseif($graph->secondary_metric_column){
      $secondary = array(
                      'secondary_name'    => "Other",
                      'secondary_col'     => $graph->secondary_metric_column,
                      'secondary_metric'  => $graph->secondary_metric_column,
                      'secondary_label'   => $graph->secondary_metric_column,
                      'secondary_col_obj' => $secondary_col,
                      'order_by'          => $graph->order_by
                      );

    }

    return array_merge($primary, $secondary);
  }

}

?>