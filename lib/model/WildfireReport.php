<?
class WildfireReport extends WaxModel{
  public static $data_models = array(''=>'-- Select --');
  public function setup(){
    //if the cms exists, add the model to the array
    if(defined("CONTENT_MODEL")) WildfireReport::$data_models[CONTENT_MODEL] = "Content";

    $this->define("title", "CharField", array('required'=>true, 'default'=>'TITLE', 'scaffold'=>true));
    $this->define("data_model", "CharField", array('widget'=>'SelectInput', 'choices'=>WildfireReport::$data_models, 'scaffold'=>true));
    $this->define("graphs", "ManyToManyField", array('scaffold'=>true, "eager_loading"=>true, "join_model_class"=>"WildfireOrderedTagJoin", "join_order"=>"join_order", 'target_model'=>'WildfireGraph', 'group'=>'relationships'));

    parent::setup();
  }
  
  public function before_save(){
    if(!$this->title) $this->title = "TITLE";
  }

}
?>