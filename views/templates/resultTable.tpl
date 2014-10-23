<?php
use oat\tao\helpers\Template;
?>
<?php if(get_data('message')):?>
	<div id="info-box" class="ui-widget-header ui-corner-all auto-slide">
		<span><?=get_data('message')?></span>
	</div>
<?php endif?>
    <link rel="stylesheet" type="text/css" href="<?= ROOT_URL ?>taoResults/views/css/resultTable.css" />

    <script type="text/javascript">
require(['jquery', 'i18n', 'taoResults/resultTable', 'grid/tao.grid', 'jquery.fileDownload'], function($, __, resultTable) { 
	    //models and columns are parameters used and manipulated by the table operations functions.
	    document.models = [];
	    document.columns = [];
	    document.dataFilter='all';
	    //there is no _url helper in JS,
	    // in order to avoid php call within JS and externalize the js
	    // of the grid in a separate js file, the links to he actions are stored
	    // in the document
	    document.dataUrl = "<?=_url('data')?>";
	    document.getActionSubjectColumnUrl = "<?=_url('getResultOfSubjectColumn')?>";
	    document.getActionGradeColumnUrl = "<?=_url('getGradeColumns')?>";
	    document.getActionResponseColumnUrl = "<?=_url('getResponseColumns')?>";
	    document.getActionCsvFileUrl = "<?=_url('getCsvFile')?>?filterData=lastSubmitted";
	    document.getActionViewResultUrl= "<?=_url('viewResult','Results')?>";
	    document.JsonFilter = <?=tao_helpers_Javascript::buildObject(get_data("filter"))?>;
	    document.JsonFilterSelection = <?=tao_helpers_Javascript::buildObject(array('filter' => get_data("filter")))?>;
	    document.resultOfSubjectConstant = "<?php echo PROPERTY_RESULT_OF_SUBJECT;?>";
        //initiate the grid
        resultTable.initiateGrid();
        //Bind the remove score button click that removes all variables that are taoCoding_models_classes_table_GradeColumn
        resultTable.bindTogglingButtons('#rmScoreButton', '#getScoreButton', document.getActionGradeColumnUrl, 'remove');
        //Bind the remove score button click that removes all variables that are taoCoding_models_classes_table_ResponseColumn
        resultTable.bindTogglingButtons('#rmResponseButton', '#getResponseButton', document.getActionResponseColumnUrl, 'remove');
        //Bind the get score button click that add all variables that are taoCoding_models_classes_table_ResponseColumn
        resultTable.bindTogglingButtons('#getResponseButton', '#rmResponseButton', document.getActionResponseColumnUrl, 'add');
        //Bind the get score button click that add all variables that are taoCoding_models_classes_table_GradeColumn
        resultTable.bindTogglingButtons('#getScoreButton', '#rmScoreButton', document.getActionGradeColumnUrl, 'add');
        resultTable.bindTogglingButtons('#viewSubject', '#removeSubject', document.getActionSubjectColumnUrl, 'add');
        resultTable.bindTogglingButtons('#removeSubject', '#viewSubject', document.getActionSubjectColumnUrl, 'remove');
	    /**
	    * Trigger the download of a csv file using the data provider used for the table display
	     */
	    $('#getCsvFile').click(function(e) {
			e.preventDefault();
		//jquery File Download is a jqueryplugin that allows to trigger a download within a Xhr request.
		//The file is being flushed in the buffer by _url('getCsvFile')
	
            $.fileDownload(document.getActionCsvFileUrl, {
                preparingMessageHtml: __("We are preparing your report, please wait..."),
                failMessageHtml: __("There was a problem generating your report, please try again."),
                successCallback: function () { },
                httpMethod: "POST",
                 ////This gives the current selection of filters (facet based query) and the list of columns selected from the client (the list of columns is not kept on the server side class.taoTable.php
                data: {'filter': document.JsonFilter, 'columns':document.columns}
            });

	    });

	    $('#dataFilter').change(function(e) {
            $("#result-table-grid").jqGrid().setGridParam({ url: document.dataUrl+'?filterData='+$( this ).val() });
            $("#result-table-grid").trigger( 'reloadGrid' );
            document.getActionCsvFileUrl = '<?=_url('getCsvFile')?>'+'?filterData='+$( this ).val() ;
        });
		
		

	    //binds the column chooser button taht launches the feature from jqgrid allowing to make a selection of the columns displayed
        $('#columnChooser').click(function(e) {
		    e.preventDefault();
		    resultTable.columnChooser();
        });
    });

</script>
<div class="main-container">
	<div id="results-custom-table-actions">
	    <div>
            <button id="viewSubject" class="btn-success small disabled" type="button"><span class="icon-add"></span><?=__('Add Test Taker')?></button>
            <button id="getScoreButton" class="btn-success small" type="button"><span class="icon-add"></span><?=__('Add All grades')?></button>
            <button id="getResponseButton" class="btn-success small" type="button"><span class="icon-add"></span><?=__('Add All responses')?></button>
		</div>

		<div>
            <button id="removeSubject" class="btn-neutral small" type="button"><span class="icon-remove"></span><?=__('Anonymise')?></button>
            <button id="rmScoreButton" class="btn-neutral small disabled" type="button"><span class="icon-remove"></span><?=__('Remove All grades')?></button>
            <button id="rmResponseButton" class="btn-neutral small disabled" type="button"><span class="icon-remove"></span><?=__('Remove All responses')?></button>
		</div>
	</div>
	<span id="settingsBox"><?=__('Filter values:')?>
			<select id="dataFilter">
			    <option  value="all"><?=__('All collected values')?></option>
			    <option  value="firstSubmitted"><?=__('First submitted responses only')?></option>
			    <option  value="lastSubmitted" selected><?=__('Last submitted responses only')?></option>
			</select>

	</span>
	<div id="result-table-container">
    	<table id="result-table-grid"></table>
	<div id="pagera1"></div>
	</div>
	<div id="results-custom-table-tools">
		<!--
		<span class="ui-state-default ui-corner-all" id="columnChooser">
			<a href="#"><img src="<?= Template::img('wf_ico.pn', 'tao') ?>" alt="settings"/><?=__('Column chooser')?></a>
		</span>
		-->
        <button id="getCsvFile" class="btn-export-csv" type="button"><?=__('Export CSV File')?></button>
	</div>
</div>
<?php
Template::inc('footer.tpl', 'tao');
?>
