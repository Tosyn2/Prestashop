
<div class="tab-content panel">
      <div class="tab-pane  in active" id="addressShipping">
			      <form class="form-horizontal hidden-print" method="post" action="<?php $link->getAdminLink->AdminOrders ?>&amp;token=<?php getAdminToken ?>&amp;vieworder&amp;id_order=<?= $orderId ?>">
				      <div class="form-group">
					      <div class="col-lg-9">
						      <select name="id_address">
							      <?php foreach ($deliveryAddresses as $deliveryAddress):  ?>
							      <option value="<?= $deliveryAddress['alias'] ?> " >
							      <?= $deliveryAddress['alias'] ?> - 
							      <?= $deliveryAddress['address1'] ?> 
							      <?= $deliveryAddress['city'] ?> 
							      <?= $deliveryAddress['id_state'] ?> 
							      </option>
							      <?php endforeach; ?>
						      </select>
					      </div>
					      <div class="col-lg-3">
						      <button class="btn btn-default" type="submit" name="submitAddressShipping"><i class="icon-refresh"></i>Change</button>
					      </div>
				      </div>
			      </form>
		      
		      <div class="well">
			      <div class="row">
				      <div class="col-sm-6">
					      <a class="btn btn-default pull-right" href="?tab=AdminAddresses&amp;id_address=<?=$deliveryAddress['id_address'] ?>&amp;addaddress&amp;realedit=1&amp;id_order=<?= $orderId ?>&amp;address_type=1&amp;token=<?php getAdminToken ?>">
						      <i class="icon-pencil"></i>
						      Edit
					      </a>
					         <?= $deliveryAddress['alias'] ?> - 
							      <?= $deliveryAddress['address1'] ?> 
							      <?= $deliveryAddress['city'] ?> 
							      <?= $deliveryAddress['id_state'] ?> 
				      </div>
				      <div class="col-sm-6 hidden-print">
					      <div id="map-delivery-canvas" style="height: 190px"></div>
				      </div>
			      </div>
		      </div>
	      
      </div>
</div>
<script>
	$('#tabAddresses a').click(function (e) {
		e.preventDefault()
		$(this).tab('show')
	})
</script>

								<?php $sql = 'SELECT `name` FROM `' . _DB_PREFIX_ . 'state` WHERE `id_state` = ' . $deliveryAddress['id_state'];
								$stateName = Db::getInstance()->getValue($sql); ?>
								<?= $stateName ?>
						<?php foreach ($deliveryAddresses as $deliveryAddress):
						 if ($deliveryAddress['id_address'] == $deliveryAddressId)
						 {
						 ?>
						  <a class="btn btn-default pull-right" href="?controller=adminaddresses&amp;id_address=<?=$deliveryAddress['id_address']?>&amp;updateaddress&amp;token=<?= $token ?>">
							<i class="icon-pencil"></i>
							Edit
						</a>
						<?php
						   echo
						   $deliveryAddress['alias'] . "<br>" .
						   $deliveryAddress['address1']  . "<br>" .
						   $deliveryAddress['city']  . "<br>" .
						   $stateName ;
						 }

						?>

						<?php endforeach; ?>
<script type="text/javascript">
	var geocoder = new google.maps.Geocoder();
	var delivery_map, invoice_map;

	$(document).ready(function()
	{
		$(".textarea-autosize").autosize();

		geocoder.geocode({
			address: '{$addresses.delivery->address1|@addcslashes:'\''},{$addresses.delivery->postcode|@addcslashes:'\''},{$addresses.delivery->city|@addcslashes:'\''}{if isset($addresses.deliveryState->name) && $addresses.delivery->id_state},{$addresses.deliveryState->name|@addcslashes:'\''}{/if},{$addresses.delivery->country|@addcslashes:'\''}'
			}, function(results, status) {
			if (status === google.maps.GeocoderStatus.OK)
			{
				delivery_map = new google.maps.Map(document.getElementById('map-delivery-canvas'), {
					zoom: 10,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					center: results[0].geometry.location
				});
				var delivery_marker = new google.maps.Marker({
					map: delivery_map,
					position: results[0].geometry.location,
					url: 'http://maps.google.com?q={$addresses.delivery->address1|urlencode},{$addresses.delivery->postcode|urlencode},{$addresses.delivery->city|urlencode}{if isset($addresses.deliveryState->name) && $addresses.delivery->id_state},{$addresses.deliveryState->name|urlencode}{/if},{$addresses.delivery->country|urlencode}'
				});
				google.maps.event.addListener(delivery_marker, 'click', function() {
					window.open(delivery_marker.url);
				});
			}
		});

		geocoder.geocode({
			address: '{$addresses.invoice->address1|@addcslashes:'\''},{$addresses.invoice->postcode|@addcslashes:'\''},{$addresses.invoice->city|@addcslashes:'\''}{if isset($addresses.deliveryState->name) && $addresses.invoice->id_state},{$addresses.deliveryState->name|@addcslashes:'\''}{/if},{$addresses.invoice->country|@addcslashes:'\''}'
			}, function(results, status) {
			if (status === google.maps.GeocoderStatus.OK)
			{
				invoice_map = new google.maps.Map(document.getElementById('map-invoice-canvas'), {
					zoom: 10,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					center: results[0].geometry.location
				});
				invoice_marker = new google.maps.Marker({
					map: invoice_map,
					position: results[0].geometry.location,
					url: 'http://maps.google.com?q={$addresses.invoice->address1|urlencode},{$addresses.invoice->postcode|urlencode},{$addresses.invoice->city|urlencode}{if isset($addresses.deliveryState->name) && $addresses.invoice->id_state},{$addresses.deliveryState->name|urlencode}{/if},{$addresses.invoice->country|urlencode}'
				});
				google.maps.event.addListener(invoice_marker, 'click', function() {
					window.open(invoice_marker.url);
				});
			}
		});

		var date = new Date();
		var hours = date.getHours();
		if (hours < 10)
			hours = "0" + hours;
		var mins = date.getMinutes();
		if (mins < 10)
			mins = "0" + mins;
		var secs = date.getSeconds();
		if (secs < 10)
			secs = "0" + secs;

		$('.datepicker').datetimepicker({
			prevText: '',
			nextText: '',
			dateFormat: 'yy-mm-dd ' + hours + ':' + mins + ':' + secs
		});
	});

	// Fix wrong maps center when map is hidden
	$('#tabAddresses').click(function(){
		x = delivery_map.getZoom();
		c = delivery_map.getCenter();
		google.maps.event.trigger(delivery_map, 'resize');
		delivery_map.setZoom(x);
		delivery_map.setCenter(c);

		x = invoice_map.getZoom();
		c = invoice_map.getCenter();
		google.maps.event.trigger(invoice_map, 'resize');
		invoice_map.setZoom(x);
		invoice_map.setCenter(c);
	});
</script>


