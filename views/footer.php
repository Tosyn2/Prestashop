<script>
	var suburbs        = <?=json_encode($suburbs)?>,
	    location_types = <?=json_encode($location_types)?>,
		suburb         = <?=json_encode($suburb)?>,
		location_type  = <?=json_encode($location_type)?>;

	replaceText('State', 'Town');
	replaceText('City', 'Suburb');
	replaceText('Address (Line 2)', 'Location Type');

	addDropDownSuburb(suburbs, suburb);
	addDropDownLocationType(location_types, location_type);
</script>
