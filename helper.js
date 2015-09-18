function replaceText(oldText, newText, node) {

	node = node || document.body;

	var childs = node.childNodes, i = 0;

	while (node = childs[i]) {
		if (node.nodeType == Node.TEXT_NODE) {
			node.textContent = node.textContent.replace(oldText, newText);
		} else {
			replaceText(oldText, newText, node);
		}
		i++;
	}

}

function replaceAdminText(oldText, newText, node) {

	$(document).ready(function () {
		node = node || document.body;
		var childs = node.childNodes, i = 0;
		while (node = childs[i]) {
			if (node.nodeType == Node.TEXT_NODE) {
				node.textContent = node.textContent.replace(oldText, newText);
			} else {
				replaceText(oldText, newText, node);
			}
			i++;
		}
	});

}

function addDropDownSuburb(suburbs, suburb) {
	$(document).ready(function () {
		var text = '';

		for (var key in suburbs) {
			if (suburbs.hasOwnProperty(key)) {
				text += '<option value="' + suburbs[key] + '">' + suburbs[key] + '</option>';
			}
		}

		$("#city")
			.replaceWith('<select id="city" name="city" class="form-control">'
			+ '<option value="' + suburb + '">' + suburb + '</option>'
			+ text
			+ '</select>'
		);
	});
}


function addDropDownLocationType(location_types, location_type) {
	$(document).ready(function () {
		var text = '';

		for (var key in location_types) {
			if (location_types.hasOwnProperty(key)) {
				text += '<option value="' + location_types[key] + '">' + location_types[key] + '</option>';
			}
		}

		$("#other")
			.replaceWith('<select id="other" name="other" class="form-control">'
			+ '<option value="' + location_type + '">' + location_type + '</option>'
			+ text
			+ '</select>'
		);
	});
}

function addDropDownSuburb(suburbs, suburb) {
	$(document).ready(function () {
		var text = '';

		for (var key in suburbs) {
			if (suburbs.hasOwnProperty(key)) {
				text += '<option value="' + suburbs[key] + '">' + suburbs[key] + '</option>';
			}
		}

		$("#city")
			.replaceWith('<select id="city" name="city" class="form-control">'
			+ '<option value="' + suburb + '">' + suburb + '</option>'
			+ text
			+ '</select>'
		);
	});
}

function addHiddenInputToAdminSave(orderId,token) {

	$(document).ready(function () {

		$("#address_form_submit_btn")
			.replaceWith(
			 '<input value="./index.php?controller=AdminOrders&id_order='+orderId+'&vieworder&token='+token+'" name="back" type="hidden" />'
			+ '<button type="submit" value="1" id="address_form_submit_btn" name="submitAddaddress" class="btn btn-default pull-right">'
			+'<i class="process-icon-save"></i>'
			+ 'Save'
			+'</button>'

		);
	});
}
