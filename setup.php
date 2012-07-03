<?php
CMSApplication::register_module("graphs", array("display_name"=>"Graphs", "link"=>"/admin/graphs/", 'plugin_name'=>'wildfire.reportbuilder', 'assets_for_cms'=>true));
CMSApplication::register_module("reports", array("display_name"=>"Reports", "link"=>"/admin/reports/", 'split'=>true));

if(!defined("CONTENT_MODEL")){
  $con = new ApplicationController(false, false);
  define("CONTENT_MODEL", $con->cms_content_class);
}

?>