<?php $token=Tools::getAdminToken('AdminOrders'.(int)TabCore::getIdFromClassName('AdminOrders').(int)\ContextCore::getContext()->cookie->id_employee);?>
<script src="../modules/mds/helper.js"></script>
<script>

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







	function addStateOnChange()
	{
		$('#id_state').on('change', selectSuburb);
	}

	function selectSuburb() {


		changeDropDownSuburb(suburbsInTown);
		});
	}

	function changeDropDownSuburb(suburbsInTown) {

		for (var key in suburbsInTown) {
			if (suburbsInTown.hasOwnProperty(key)) {
				var selected = (suburbsInTown[key] == suburb) ? ' selected="selected"' : '';
				text += '<option value="' + suburbsInTown[key] + '"' + selected +'>' + suburbsInTown[key] + '</option>';
			}
		}

		$("#city").empty().append(text).change();
	}



</script>
