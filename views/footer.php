<script src="./modules/mds/helper.js"></script>
<script>
	var suburbs        = <?=json_encode($suburbs)?>,
		location_types = <?=json_encode($locationTypes)?>,
		suburb         = <?=json_encode($suburb)?>,
		location_type  = <?=json_encode($locationType)?>;

	replaceText('State', 'Town');
	replaceText('City', 'Suburb');
	replaceText('Additional information', 'Location Type');

	addDropDownSuburb(suburbs, suburb);
	addDropDownLocationType(location_types, location_type);
</script>
