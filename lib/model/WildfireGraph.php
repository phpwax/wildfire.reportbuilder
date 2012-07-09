<?
class WildfireGraph extends WaxModel{

  public static $graph_types = array('ColumnChart'=>'Column', 'BarChart'=>'Bar', 'PieChart'=>'Pie', 'AreaChart'=>'Area');
  public static $graph_sizes = array('small'=>'Small', 'medium'=>'Medium', 'large'=>'Large');
  public function setup(){
    $this->define("title", "CharField", array('required'=>true, 'default'=>'TITLE', 'scaffold'=>true));
    $this->define("type", "CharField", array('scaffold'=>true, 'widget'=>'SelectInput', 'choices'=>WildfireGraph::$graph_types));

    $this->define("primary_metric_column", "CharField");
    $this->define("primary_metric_function", "CharField");
    $this->define("secondary_metric_column", "CharField", array('label'=>'Secondary metric'));
    $this->define("condition", "CharField");

    $this->define("size", "CharField", array('widget'=>'SelectInput', 'choices'=>WildfireGraph::$graph_sizes, 'default'=>'small'));
    parent::setup();
  }

  public function before_save(){
    if(!$this->title) $this->title = "TITLE";
  }
}
?>