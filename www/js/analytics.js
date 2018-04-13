var MAX_CHART_HEIGHT = 300;
var MAX_LAST_ENTRIES = 20;

function renderMetric(theData, theMetric, theContainerId) {
	$('#' + theContainerId).empty();
	renderMetricChart(theData, theMetric, theContainerId);
	renderMetricDataValues(theData, theMetric, theContainerId);
}

function renderMetricChart(theData, theMetric, theContainerId) {
	var aChartValues = [];

	aChartValues.push(['Commit-permutation', 'value']);
	for(var i = 0; i < theData.values.length; i++) {
		aChartValues.push([theData.values[i].experiment_hash + '-' + theData.values[i].permutation_hash, theData.values[i].value]);
	}

	var aOptions = {
		title: 'Distribution of ' + theData.metrics,
		legend: { position: 'none' },
		height: MAX_CHART_HEIGHT
	};

	var aChart = new google.visualization.Histogram(document.getElementById(theContainerId));
	aChart.draw(google.visualization.arrayToDataTable(aChartValues), aOptions);
}

function renderMetricDataValues(theData, theMetric, theContainerId) {
	var aValues = theData.values.slice(0, MAX_LAST_ENTRIES), aText = '';

	aText = '' +
	'<table width="100%" class="table table-striped table-bordered table-hover">' +
		'<thead>' +
			'<tr>' +
				'<th>Hash-permutation</th>' +
				'<th>Value</th>' +
			'</tr>' +
		'</thead>' +
		'<tbody>';

	for(var i = 0; i < aValues.length; i++) {
		aText +=
			'<tr>' +
				'<td><a href="result.php?experiment_hash=' + aValues[i].experiment_hash + '&permutation_hash=' + aValues[i].permutation_hash + '">' + aValues[i].experiment_hash + '-' + aValues[i].permutation_hash + '</a></td>' +
				'<td>' + aValues[i].value + '</td>' +
			'</tr>';
	}

	aText += '</tbody></table>';

	$('#' + theContainerId).append(aText);
}

function onMetricDataFail(theJqXHR, theTextStatus, theErrorThrown) {
    console.error('onFail()', theErrorThrown);
}

function handleShowStats() {
	var aContainerId = $(this).data('container');
	var aContainerRowId = 'row' + aContainerId;
	var aMetric = $(this).data('metric');

	var aOpen = $('#' + aContainerRowId).data('open');

	if(aOpen) {
		$('#' + aContainerRowId).slideUp().data('open', false);
		return;
	}

	$('#' + aContainerRowId).css({height: MAX_CHART_HEIGHT}).slideDown().data('open', true);
	$('#' + aContainerId).html('<i class="fa fa-circle-o-notch fa-spin"></i> Loading...');

	$.ajax({
		url: 'analytics.php',
		data: {metric: aMetric, json: true},
		dataType: 'json'
	}).done(function(theData) {
		renderMetric(theData, aMetric, aContainerId);
	}).fail(onMetricDataFail);
}

function renderExperimentReport(theData) {
	$('#experiment-report-table').html(theData).show();
}

function handleExperimentReportSubmit() {
	$('#experiment-report-table').html('<i class="fa fa-circle-o-notch fa-spin"></i> Loading...').show();

	$.ajax({
		url: 'ajax-experiment-report.php',
		data: $('#form-experiment-report').serialize(),
		dataType: 'html'
	}).done(function(theData) {
		renderExperimentReport(theData);
	}).fail(onMetricDataFail);

	return false;
}

$(function() {
	$('.show-stats').click(handleShowStats);
	$('#form-experiment-report').submit(handleExperimentReportSubmit);
})

// Load the Visualization API and the corechart package.
google.charts.load('current', {'packages':['corechart']});

// Set a callback to run when the Google Visualization API is loaded.
//google.charts.setOnLoadCallback(onChartReady);
