<?php


CMSApplication::register_module("graph", array("display_name"=>"Graphs", "link"=>"/admin/graph/", 'plugin_name'=>'wildfire.reportbuilder', 'assets_for_cms'=>true));
CMSApplication::register_module("report", array("display_name"=>"Reports", "link"=>"/admin/report/", 'split'=>true));


?>