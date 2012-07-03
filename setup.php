<?php
CMSApplication::register_module("graph", array("display_name"=>"Graphs", "link"=>"/admin/graph/", 'plugin_name'=>'wildfire.reportbuilder', 'assets_for_cms'=>true));
CMSApplication::register_module("report", array("display_name"=>"Reports", "link"=>"/admin/report/", 'split'=>true));

if(!defined("CONTENT_MODEL")){
  $con = new ApplicationController(false, false);
  define("CONTENT_MODEL", $con->cms_content_class);
}

?>