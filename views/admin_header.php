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

	addDropDownSuburb(suburbs, suburb);
	addDropDownLocationType(location_types, location_type);
	addHiddenInputToAdminSave(orderId,token);

</script>
