
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


