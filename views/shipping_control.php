<style>
	.nobootstrap input[type="email"], .nobootstrap input[type="number"] {
		border: 1px solid #CCC;
		background-color: #FFF;
		padding: 2px 4px;
		box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.1) inset;
		width: 150px;
	}
	
	.details-block {
		height:300px;
		width:300px;
		border:2px solid black;
	}
	
</style>

<h1>MDS Shipping</h1>

<div class='details-block'>
<h2>Collection Details</h2>
Collect from  Address: (  New  |  Saved   )
</div>

<div class='details-block'>
<h2>Delivery Details</h2>
<?= $deliveryAddressId ?>
</div>

<div class='details-block'>
<h2>Collivery Details</h2>
<?= $carrierName ?>
<?= $serviceId ?>
</div>

