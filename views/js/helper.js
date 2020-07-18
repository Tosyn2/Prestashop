/**
 * Copyright 2020 MDS Technologies (Pty) Ltd and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 *  @author MDS Collivery <integration@collivery.co.za>
 *  @copyright  2020 MDS Technologies (Pty) Ltd
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

function replaceText(oldText, newText, node){ 
  node = node || document.body; 

  var childs = node.childNodes, i = 0;

  while(node = childs[i]){ 
    if (node.nodeType == Node.TEXT_NODE){ 
      node.textContent = node.textContent.replace(oldText, newText); 
    } else { 
      replaceText(oldText, newText, node); 
    } 
    i++; 
  } 
}

function addDropDownSuburb(suburbs , suburb)
{
	var text = '';

	for (var key in suburbs) {
		if (suburbs.hasOwnProperty(key)) {
			text += '<option value="' + suburbs[key] + '">' + suburbs[key] + '</option>';
		}
	}

	$("#city")
		.replaceWith( '<select id="city" name="city" class="form-control">' 
							+ '<option value="'+  suburb +'">' +  suburb +'</option>' 
							+ text
							+ '</select>'
							);

}



function addDropDownSuburbs(suburbs)
{
	var text = '';

	for (var key in suburbs) {
		if (suburbs.hasOwnProperty(key)) {
			text += '<option value="' + suburbs[key] + '">' + suburbs[key] + '</option>';
		}
  }

	$("#conf_id_PS_SHOP_CITY")
		.replaceWith(
		'<label class="control-label col-lg-3"> Suburb </label>' +
		'<select id="conf_id_PS_SHOP_CITY" name="PS_SHOP_CITY" >' +
		'<option value="">-</option>' + text +
		'</select>');

}



function addDropDownLocationType(location_types,location_type)
{
	var text = '';

	for (var key in location_types) {
		if (location_types.hasOwnProperty(key)) {
			text += '<option value="' +  location_types[key] + '">' + location_types[key] + '</option>';
		}
	}
	
	$("#address2")
		.replaceWith('<select id="address2" name="address2" class="form-control">' 
		+'<option value="'+location_type+'">' +location_type+'</option>' 
		+ text
		+'</select>' 
		);

}

function addDropDownLocationTypes(location_types)
{
	var text = '';

	for (var key in location_types) {
		if (location_types.hasOwnProperty(key)) {
			text += '<option value="' +  location_types[key] + '">' + location_types[key] + '</option>';
		}
	}
	
	
	$("#conf_id_PS_SHOP_ADDR2")
		.replaceWith(
		'<label class="control-label col-lg-3"> Location Type </label>' +
		'<select id="conf_id_PS_SHOP_ADDR2" name="PS_SHOP_ADDR2" >' +
		'<option value="">-</option>' + text +
		'</select>');

}


