<?php $token=Tools::getAdminToken('AdminOrders'.(int)TabCore::getIdFromClassName('AdminOrders').(int)\ContextCore::getContext()->cookie->id_employee);?>
<script src="../modules/mds/helper.js"></script>
<script>

	var suburbs = <?=json_encode($subs)?>,
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
		$("#city").replaceWith('<select id="city" name="city" class="form-control"></select>');
		selectSuburb();
	});
	$(document).ready(function() {
	addStateOnChange();
	});
	var suburbsFlattened = new Array;

	for (var key in suburbs) {
		if (suburbs.hasOwnProperty(key)) {
			for (var sub in suburbs[key]) {
				suburbsFlattened.push(suburbs[key][sub]);
			}
		}
	}
//console.log(suburbs);
	addDropDownLocationType(location_types, location_type);

//	function addDropDownSuburb(suburbs, suburb) {
//		$(document).ready(function () {
//			var text = '';
//
//			for (var key in suburbs) {
//				if (suburbs.hasOwnProperty(key)) {
//					text += '<option value="' + suburbs[key] + '">' + suburbs[key] + '</option>';
//				}
//			}
//
//			$("#city")
//				.replaceWith('<select id="city" name="city" class="form-control">'
//				+ '<option value="' + suburb + '">' + suburb + '</option>'
//				+ text
//				+ '</select>'
//			);
//		});
//	}




	function addStateOnChange()
	{
		$(document).ready(function () {
		$('#id_state').on('change', selectSuburb);
			});
	}

	function selectSuburb() {
		$(document).ready(function () {

		var townName = $('#id_state option:selected').text();
		var suburbsInTown = suburbs[townName];

			//console.log(suburbsInTown);
		changeDropDownSuburb(suburbsInTown);
		});
	}

	function changeDropDownSuburb(suburbsInTown) {
		$(document).ready(function () {
		var text = '<option value="">Please Select Town first</option>';

		for (var key in suburbsInTown) {
			if (suburbsInTown.hasOwnProperty(key)) {
				var selected = (suburbsInTown[key] == suburb) ? ' selected="selected"' : '';
				text += '<option value="' + suburbsInTown[key] + '"' + selected +'>' + suburbsInTown[key] + '</option>';
			}
		}

		$("#city").empty().append(text).change();
	});
	}



</script>
