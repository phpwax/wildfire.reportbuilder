jQuery(document).ready(function(){
  jQuery(".graph_container").sortable({
    stop:function(){
      var sort="", dashboards = jQuery(".graph_container .dashboard");
      dashboards.each(function(){
        var obj = jQuery(this),
            pos = dashboards.index(obj),
            graph = obj.attr("data-graph"),
            report = obj.attr("data-report")
            ;
        sort += "sort["+pos+"][order]="+pos+"&sort["+pos+"][graph]="+graph+"&sort["+pos+"][report]="+report+"&";
      });
      console.log(sort);
      jQuery.ajax({
        url:"/admin/reports/sorting/",
        data:sort,
        type:"get",
        success:function(res){}
      });
    }
  });

  jQuery(document).bind("sortstop", function(){
    jQuery(window).trigger("graph.draw");
  });
});