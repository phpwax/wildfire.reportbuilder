<?
class WildfireGraph extends WaxModel{

  public static $graph_types = array('ColumnChart'=>'Column', 'BarChart'=>'Bar', 'PieChart'=>'Pie');
  public function setup(){
    $this->define("title", "CharField", array('required'=>true, 'default'=>'TITLE'));
    $this->define("primary_metric", "CharField");
    $this->define("grouping_metric", "CharField");
    parent::setup();
  }

}
?>