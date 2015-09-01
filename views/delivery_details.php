<div class="row">
	<div class="tab-content panel">
		<h1>MDS Delivery Status || Waybill <?= $waybill ?></h1>

		<div class="tab-pane  in active" id="addressShipping" style="display:inline-block; width:100%">
			<ul style="padding-top:2%;list-style: none">
				<li>Current status: <?= $status['status_text'] ?></li>
				<li>Delivery requested: <?= $status['updated_date'] ?> <?= $status['updated_time'] ?> </li>
				<li>Delivery type: <?= $serviceName ?></li>
				<li>Delivery fee: <?= $status['total_price'] ?></li>
				<?php if ($status['status_id'] == 32 || $status['status_id'] == 8): ?>
					<li>
						<a href="?controller=AdminOrders&amp;token=<?= $token ?>&amp;vieworder&amp;id_order=<?= $orderId ?>&amp;func_name=getPod&amp;waybill=<?= $waybill ?>"
						   class="btn btn-default">Get POD file</a></li>
				<?php endif ?>
			</ul>
			<div style="display:inline-block; width:50%">
				<h2>Collection Address</h2>

				<div class="well">
					<div class="row">
						<div class="col-sm-6">
							<?php foreach ($collectionAddresses as $collectionAddress): ?>
								<?php if ($collectionAddress['id_address'] == $collectionAddressId): ?>

									<?= $collectionAddress['alias'] ?> <br>
									<?= $collectionAddress['address1'] ?> <br>
									<?= $collectionAddress['city'] ?> <br>
									<?= $collectionAddress['name'] ?> <br>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
						<?php if ($status['status_id'] == 10 || $status['status_id'] ==  30 ) { ?>
							<div style="pull-right">Collection Time ETA: <?= $status['eta_text'] ?></div>
						<?php }elseif ($status['status_id'] == 7 || $status['status_id'] ==  14 || $status['status_id'] ==  21){?>
							<div style="pull-right">Collected</div>
						<?php }elseif ($status['status_id'] == 11) {?>
							<div style="pull-right">Unable to collect parcel</div>
						<?php }?>
					</div>
				</div>
			</div>
			<div style="float:right;width:50%">
				<h2>Delivery Address</h2>

				<div class="well">
					<div class="row">
						<div class="col-sm-6">
							<?php foreach ($deliveryAddresses as $deliveryAddress):
								if ($deliveryAddress['id_address'] == $deliveryAddressId) {
									?>
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
						<?php if ($status['status_id'] == 15 || $status['status_id'] ==  31) { ?>
							<div style="pull-right">Delivery Time ETA: <?= $status['eta_text'] ?></div>
							<?php }elseif ($status['status_id'] == 8 || $status['status_id'] ==  20 || $status['status_id'] ==  32){?>
							<div style="pull-right">Delivered</div>
						<?php }elseif ($status['status_id'] == 12) {?>
							<div style="pull-right">Unable to deliver parcel</div>
						<?php }?>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>






