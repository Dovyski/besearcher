var MAX_CHART_HEIGHT = 300;

function renderMetric(theData, theMetric, theContainerId) {
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
	$('#' + theContainerId).append('<p></p>');
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
