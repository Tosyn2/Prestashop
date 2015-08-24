
<script>
	$('#tabAddresses a').click(function (e) {
		e.preventDefault()
		$(this).tab('show')
	})
</script>

<div class="row">
	<ul class="nav nav-tabs" id="tabAddresses">
		<li class="active">
			<a href="#addressShipping">
				<i class="icon-truck"></i>
				Shipping address
			</a>
		</li>

	</ul>
	<div class="tab-content panel">
		<div class="tab-pane  in active" id="addressShipping">


			<h2>Collection Address</h2>

			<form class="form-horizontal hidden-print" method="post"
			      action="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>">
				<div class="form-group">
					<div class="col-lg-9">
						<select name="id_address">
							<?php foreach ($collectionAddresses as $deliveryAddress): ?>
								<option value="<?= $collectionAddress['id_address'] ?> "
									<?php if ($collectionAddress['id_address'] == $collectionAddressId) {
										echo "selected";
									} ?> >
									<?= $collectionAddress['alias'] ?> -
									<?= $collectionAddress['address1'] ?> ,
									<?= $collectionAddress['city'] ?> ,
									<?= $collectionAddress['name'] ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-lg-3">
						<button class="btn btn-default" type="submit" name="submitAddressShipping"><i
								class="icon-refresh"></i>Change
						</button>
					</div>
				</div>
			</form>
			<div class="well">
				<div class="row">
					<div class="col-sm-6">

						<?php foreach ($CollectionAddresses as $collectionAddress):
							if ($collectionAddress['id_address'] == $deliveryAddressId) {
								?>
								<a class="btn btn-default pull-right"
								   href="?controller=adminaddresses&amp;id_address=<?= $collectionAddress['id_address'] ?>&amp;updateaddress&amp;token=<?= $token ?>">
									<i class="icon-pencil"></i>
									Edit
								</a>
								<?php
								echo
									$collectionAddress['alias'] . "<br>" .
									$collectionAddress['address1'] . "<br>" .
									$collectionAddress['city'] . "<br>" .
									$collectionAddress['name'];
							}

							?>

						<?php endforeach; ?>

					</div>
					<div class="col-sm-6 hidden-print">
						<div id="map-delivery-canvas" style="height: 190px"></div>
					</div>
				</div>
			</div>

		</div>
	</div>


	<div class="tab-content panel">
		<div class="tab-pane  in active" id="addressShipping">
			<h2>Delivery Address</h2>

			<form class="form-horizontal hidden-print" method="post"
			      action="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>">
				<div class="form-group">
					<div class="col-lg-9">
						<select name="id_address">
							<?php foreach ($deliveryAddresses as $deliveryAddress): ?>
								<option value="<?= $deliveryAddress['id_address'] ?> "
									<?php if ($deliveryAddress['id_address'] == $deliveryAddressId) {
										echo "selected";
									} ?> >
									<?= $deliveryAddress['alias'] ?> -
									<?= $deliveryAddress['address1'] ?> ,
									<?= $deliveryAddress['city'] ?> ,
									<?= $deliveryAddress['name'] ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-lg-3">
						<button class="btn btn-default" type="submit" name="submitAddressShipping"><i
								class="icon-refresh"></i>Change
						</button>
					</div>
				</div>
			</form>
			<div class="well">
				<div class="row">
					<div class="col-sm-6">

						<?php foreach ($deliveryAddresses as $deliveryAddress):
							if ($deliveryAddress['id_address'] == $deliveryAddressId) {
								?>
								<a class="btn btn-default pull-right"
								   href="?controller=adminaddresses&amp;id_address=<?= $deliveryAddress['id_address'] ?>&amp;updateaddress&amp;token=<?= $token ?>">
									<i class="icon-pencil"></i>
									Edit
								</a>
								<?php
								echo
									$deliveryAddress['alias'] . "<br>" .
									$deliveryAddress['address1'] . "<br>" .
									$deliveryAddress['city'] . "<br>" .
									$deliveryAddress['name'];
							}

							?>

						<?php endforeach; ?>

					</div>
					<div class="col-sm-6 hidden-print">
						<div id="map-delivery-canvas" style="height: 190px"></div>
					</div>
				</div>


			</div>

		</div>
	</div>
</div>
