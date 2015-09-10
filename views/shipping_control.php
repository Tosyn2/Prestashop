<?php $token = Tools::getAdminToken('AdminAddresses'.(int)TabCore::getIdFromClassName('AdminAddresses').(int)\ContextCore::getContext()->cookie->id_employee);?>
<?php $tokenb = Tools::getAdminToken('AdminManufacturers'.(int)TabCore::getIdFromClassName('AdminManufacturers').(int)\ContextCore::getContext()->cookie->id_employee);?>

<div class="row">
	<div class="tab-content panel">
		<div>
		<img src="../modules/mds/icons/Collivery-Icon.png" style="padding:0 1% 1% 0"><h1 style="display:inline">MDS Shipping Control</h1>
			</div>


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
							<button class="btn btn-default" method="submit" name="id_address_col"><i class="icon-exchange" style="padding-right:5%"></i>Change</button>
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
										<i class="icon-edit"></i> Edit
									</a>
									<?= $collectionAddress['alias'] ?> <br>
									<?= $collectionAddress['address1'] ?> <br>
									<?= $collectionAddress['city'] ?> <br>
									<?= $collectionAddress['name'] ?> <br>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
						<a class="btn btn-default pull-right"
						   href="?controller=AdminManufacturers&amp;addaddress&amp;id_order=<?=$orderId?>&amp;id_manufacturer=<?= $idManufacturer ?>&token=<?=$tokenb?>">
							<i class="icon-plus-circle"></i> Add Address
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
							<button class="btn btn-default" method="submit" name="id_address_del"><i class="icon-exchange" style="padding-right:5%"></i>Change</button>
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
										<i class="icon-edit"></i> Edit
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
					<input type="hidden" name="back" value="<?= $back?>" />

						<a class="btn btn-default pull-right"
						   href="index.php?controller=AdminAddresses&addaddress&id_order=<?=$orderId?>&token=<?=$token?>">
							<i class="icon-plus-circle"></i> Add Address
						</a>
					</div>
				</div>
			</div>
			<div style="display:inline-flex">
			<form style="width:50%"
			      action="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>&amp;func_name=getQuote"
			      method="post">
				<button class="btn btn-default"><i class="icon-question-circle" style="padding-right:5%;"></i>Get Quote</button>
			</form>
			<form style="width:50%;"
			      action="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>&amp;func_name=addCollivery"
			      method="post">
				<button class="btn btn-default"><i class="icon-check-circle" style="padding-right:5%;"></i>Despatch Delivery</button>
			</form>
				</div>
			<div style="padding-top: 1%">
			<span><?php echo $price; ?></span>
				</div>
		</div>
	</div>
</div>
</div>



