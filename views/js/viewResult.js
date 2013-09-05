
$(function () {
    require(['require', 'jquery', '/taoResults/views/js/viewResult.js',root_url  + 'tao/views/js/jquery.fileDownload.js'], function () {
	$('.dataResult').html(function(index, oldhtml) {
	    return layoutResponse(oldhtml);
	    });
	$('#filter').change(function(e) {
	    url = root_url + 'taoResults/Results/viewResult';
	    data.filter = $( this ).val();
	    helpers._load(helpers.getMainContainerSelector(uiBootstrap.tabs), url, data);
	    });
	    $('#filter').val('<?=get_data("filter")?>');
	    });
	$('.traceDownload').click(function (e) {
	  var variableUri = $(this).val();
	  $.fileDownload(root_url + 'taoResults/Results/getTrace', {
	      preparingMessageHtml: __("We are preparing your report, please wait..."),
	      failMessageHtml: __("There was a problem generating your report, please try again."),
	      successCallback: function () { },
	      httpMethod: "POST",
	       ////This gives the current selection of filters (facet based query) and the list of columns selected from the client (the list of columns is not kept on the server side class.taoTable.php
	      data: {'variableUri': variableUri}
	  });
	});

    });

    function layoutResponse(data){
	var formattedData = "";
	//the data may be not valid json, in this case there is a silent fail and the data is returned.
	try{
	var jsData = $.parseJSON(data);
	if (jsData instanceof Array) {
	    formattedData = '<OL >';
	    for (key in jsData){
		formattedData += '<li >';
		formattedData += jsData[key];
		 formattedData += "</li>";
		}
	     formattedData += "</OL>";
	} else {
	    formattedData = data;
	    }
	}
	catch(err){formattedData = data;}
	return formattedData;
	}