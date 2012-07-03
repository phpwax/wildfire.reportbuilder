<?
class WildfireGraph extends WaxModel{

  public static $graph_types = array('ColumnChart'=>'Column', 'BarChart'=>'Bar', 'PieChart'=>'Pie');
  public function setup(){
    $this->define("title", "CharField", array('required'=>true, 'default'=>'TITLE', 'scaffold'=>true));
    $this->define("type", "CharField", array('scaffold'=>true, 'widget'=>'SelectInput', 'choices'=>WildfireGraph::$graph_types));
    $this->define("primary_metric_name", "CharField", array('scaffold'=>true));
    $this->define("primary_metric", "CharField"));
    $this->define("secondary_metric_name", "CharField", array('scaffold'=>true));
    $this->define("basic_secondary_metric", "CharField", array('label'=>'Secondary metric (for simple graphs like count)'));
    $this->define("complex_secondary_metric", "CharField", array('label'=>'Complex metric (eg splitting the values by gender'));

    parent::setup();
  }

  public function before_save(){
    if(!$this->title) $this->title = "TITLE";
  }
}
?>