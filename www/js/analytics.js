var MAX_CHART_HEIGHT = 300;

function renderMetric(theData, theMetric, theContainerId) {
	$('#' + theContainerId).empty();
	renderMetricChart(theData, theMetric, theContainerId);
	renderMetricDataValues(theData, theMetric, theContainerId);
}

function renderMetricChart(theData, theMetric, theContainerId) {
	var aChartValues = [];

	aChartValues.push(['Commit-permutation', 'value']);
	for(var i = 0; i < theData.values.length; i++) {
		aChartValues.push([theData.values[i].commit + '-' + theData.values[i].permutation, theData.values[i].value]);
	}

	var aOptions = {
		title: theData.metrics,
		legend: { position: 'none' },
		height: MAX_CHART_HEIGHT
	};

	var aChart = new google.visualization.Histogram(document.getElementById(theContainerId));
	aChart.draw(google.visualization.arrayToDataTable(aChartValues), aOptions);
}

function renderMetricDataValues(theData, theMetric, theContainerId) {
	var aValues = theData.values.slice(0, 20), aText = '';

	aText = '' +
	'<table width="100%" class="table table-striped table-bordered table-hover">' +
		'<thead>' +
			'<tr>' +
				'<th>Commit-permutation</th>' +
				'<th>Value</th>' +
			'</tr>' +
		'</thead>' +
		'<tbody>';

	for(var i = 0; i < aValues.length; i++) {
		aText +=
			'<tr>' +
				'<td><a href="result.php?commit=' + aValues[i].commit + '&permutation=' + aValues[i].permutation + '">' + aValues[i].commit + '-' + aValues[i].permutation + '</a></td>' +
				'<td>' + aValues[i].value + '</td>' +
			'</tr>';
	}

	aText += '</tbody></table>';

	$('#' + theContainerId).append(aText);
}

function onMetricDataFail(theJqXHR, theTextStatus, theErrorThrown) {
    console.error('onFail()', theErrorThrown);
}

$(function() {
	$('.show-stats').click(function() {
		var aContainerId = $(this).data('container');
		var aContainerRowId = 'row' + aContainerId;
		var aMetric = $(this).data('metric');

		$('#' + aContainerRowId).css({height: MAX_CHART_HEIGHT}).slideDown();
		$('#' + aContainerId).html('<i class="fa fa-circle-o-notch fa-spin"></i> Loading...');

		$.ajax({
			url: 'analytics.php',
			data: {metric: aMetric, json: true},
			dataType: 'json'
		}).done(function(theData) {
			renderMetric(theData, aMetric, aContainerId);
		}).fail(onMetricDataFail);
	})
})

// Load the Visualization API and the corechart package.
google.charts.load('current', {'packages':['corechart']});

// Set a callback to run when the Google Visualization API is loaded.
//google.charts.setOnLoadCallback(onChartReady);
