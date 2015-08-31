<div class="row">
	<div class="tab-content panel">
		<h1>MDS Delivery Status</h1>



		<div class="tab-pane  in active" id="addressShipping" style="display:inline-block; width:100%">
			<div style="display:inline-block; width:50%">
				<h2>Collection Address</h2>


				<div class="well">
					<div class="row">

							<?php foreach ($collectionAddresses as $collectionAddress): ?>
								<?php if ($collectionAddress['id_address'] == $collectionAddressId): ?>

									<?= $collectionAddress['alias'] ?> <br>
									<?= $collectionAddress['address1'] ?> <br>
									<?= $collectionAddress['city'] ?> <br>
									<?= $collectionAddress['name'] ?> <br>
								<?php endif; ?>
							<?php endforeach; ?>


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

					</div>
				</div>
			</div>

		</div>
	</div>
</div>
</div>






