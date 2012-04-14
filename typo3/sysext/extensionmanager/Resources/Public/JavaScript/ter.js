jQuery(document).ready(function() {
	jQuery('#terTable').dataTable({
		"bJQueryUI":true,
		"bLengthChange": false,
		'iDisplayLength': 50,
		"bStateSave": true,
		"bInfo": false,
		"bPaginate": false,
		"bFilter": false
	});
});
