<?php $token=Tools::getAdminToken('AdminOrders'.(int)TabCore::getIdFromClassName('AdminOrders').(int)\ContextCore::getContext()->cookie->id_employee);?>
<script src="../modules/mds/helper.js"></script>
<script>

	var suburbs = <?=json_encode($suburbs)?>,
		location_types = <?=json_encode($locationTypes)?>,
		suburb = <?=json_encode($suburb)?>,
		location_type = <?=json_encode($locationType)?>;
		orderId = <?=json_encode($orderId)?>;
		token = <?=json_encode($token)?>;

	replaceAdminText('State', 'Town');
	replaceAdminText('City', 'Suburb');
	replaceAdminText('Other', 'Location Type');
	replaceAdminText('Address (2)', 'Building Details');



	addHiddenInputToAdminSave(orderId,token);


		$(document).ready(function() {
			addStateOnChange();

		});



//	selectSuburb();



	addDropDownLocationType(location_types, location_type);

	function addStateOnChange()
	{
		$(document).ready(function() {
		$('#id_state').on('change', selectSuburb);

		});
	}

	function selectSuburb() {
		$(document).ready(function() {
		var townName = $("#id_state option:selected").text();

		console.log(townName);


		var suburbsInTown = suburbs[townName];
		changeDropDownSuburb(suburbsInTown);
		});
	}

	function changeDropDownSuburb(suburbsInTown) {
		//console.log(suburbsInTown);
		$(document).ready(function() {
		var text = '<option value="">Please Select Town First</option>';

		for (var key in suburbsInTown) {
			if (suburbsInTown.hasOwnProperty(key)) {
				var selected = (suburbsInTown[key] == suburb) ? ' selected="selected"' : '';
				text += '<option value="' + suburbsInTown[key] + '"' + selected +'>' + suburbsInTown[key] + '</option>';
			}
		}

		$("#city").empty().append(text).change();
		});
	}

	$(document).ready(function() {
	$("#city").replaceWith('<select id="city" name="city" class="form-control"></select>');
		townName = $("#id_state option:selected").text();
		 suburbsInTown = suburbs[townName];
		changeDropDownSuburb(suburbsInTown);
	});


</script>
