<div class="row">
	<div class="tab-content panel">
		<h1>Shipping Control</h1>

		<div class="tab-pane  in active" id="addressShipping" style="display:inline-block; width:100%">
			<div style="display:inline-block">
				<h2>Collection Address</h2>

				<form class="form-horizontal hidden-print" method="post"
				      action="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>&amp;func_name=changeCollectionAddress">
					<div class="form-group">
						<div class="col-lg-9">
							<select name="id_collection_address">
								<?php foreach ($collectionAddresses as $collectionAddress): ?>
									<option value=<?= $collectionAddress['id_address'] ?>
										<?php if ($collectionAddress['id_address'] == $collectionAddressId) {
											echo "selected";
										} ?>>
										<?= $collectionAddress['alias'] ?> -
										<?= $collectionAddress['address1'] ?> ,
										<?= $collectionAddress['city'] ?> ,
										<?= $collectionAddress['name'] ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-lg-3">
							<button method="submit" name="id_address_col">Change</button>
						</div>
					</div>
				</form>
				<div class="well">
					<div class="row">
						<div class="col-sm-6">
							<?php foreach ($collectionAddresses as $collectionAddress): ?>
								<?php if ($collectionAddress['id_address'] == $collectionAddressId): ?>
									<a class="btn btn-default pull-right"
									   href="?controller=AdminManufacturers&amp;token=<?= $token ?>&amp;id_address=<?= $collectionAddress['id_address'] ?>&amp;editaddresses=1">
										<i class="icon-pencil"></i> Edit
									</a>
									<?= $collectionAddress['alias'] ?> <br>
									<?= $collectionAddress['address1'] ?> <br>
									<?= $collectionAddress['city'] ?> <br>
									<?= $collectionAddress['name'] ?> <br>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
						<a class="btn btn-default pull-right"
						   href="?controller=AdminManufacturers&amp;addaddress&amp;token=<?= $token ?>&amp;id_manufacturer=<?= $idManufacturer ?>">
							<i class="icon-pencil"></i> Add Address
						</a>
					</div>
				</div>
			</div>
			<div style="float:right;width:50%">
				<h2>Delivery Address</h2>

				<form class="form-horizontal hidden-print" method="post"
				      action="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>&amp;func_name=changeDeliveryAddress">

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
							<button method="submit" name="id_address_del">Change</button>
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
										<i class="icon-pencil"></i> Edit
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
						<a class="btn btn-default pull-right"
						   href="?controller=adminaddresses&amp;addaddress&amp;token=<?= $token ?>">
							<i class="icon-pencil"></i> Add Address
						</a>
					</div>
				</div>
			</div>
			<form align="center"
			      action="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>&amp;func_name=getQuote"
			      method="post">
				<button class="btn btn-default">Get Quote</button>
			</form>
			<form align="center"
			      action="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>&amp;func_name=addCollivery"
			      method="post">
				<button class="btn btn-default">Despatch Delivery</button>
			</form>
			<?php echo $price; ?>
		</div>
	</div>
</div>
</div>



