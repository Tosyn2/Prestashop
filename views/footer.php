<script src="./modules/mds/helper.js"></script>
<script>
	var suburbs = <?=json_encode($subs)?>,
		location_types = <?=json_encode($locationTypes)?>,
		suburb = <?=json_encode($suburb)?>,
		location_type = <?=json_encode($locationType)?>;

	replaceText('State', 'Town');
	replaceText('City', 'Suburb');
	replaceText('Additional information', 'Location Type');
	replaceText('Address (Line 2)', 'Building Details');
	addStateOnChange();

	var suburbsFlattened = new Array;

	for (var key in suburbs) {
		if (suburbs.hasOwnProperty(key)) {
			for (var sub in suburbs[key]) {
				suburbsFlattened.push(suburbs[key][sub]);
			}
		}
	}

	addDropDownLocationType(location_types, location_type);

	function addStateOnChange()
	{
		$('#id_state').on('change', selectSuburb);
	}

	function selectSuburb() {
		var townName = $('#id_state option:selected').text();
		var suburbsInTown = suburbs[townName];
		changeDropDownSuburb(suburbsInTown);
	}

	function changeDropDownSuburb(suburbsInTown) {

		var text = '<option value="">Please Select</option>';

		for (var key in suburbsInTown) {
			if (suburbsInTown.hasOwnProperty(key)) {
				var selected = (suburbsInTown[key] == suburb) ? ' selected="selected"' : '';
				text += '<option value="' + suburbsInTown[key] + '"' + selected +'>' + suburbsInTown[key] + '</option>';
			}
		}

		$("#city").empty().append(text).change();
	}

	$(document).ready(function() {
		$("#city").replaceWith('<select id="city" name="city" class="form-control"></select>');
		selectSuburb();
	});

</script>
