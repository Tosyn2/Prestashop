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

		$("#address2")
			.replaceWith('<select id="address2" name="address2" class="form-control">'
			+ '<option value="' + location_type + '">' + location_type + '</option>'
			+ text
			+ '</select>'
		);
	});
}



