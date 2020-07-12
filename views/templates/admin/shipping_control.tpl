{*
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
 *}
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
			      action="?controller=AdminOrders&amp;token={$htmldata.token|escape:'htmlall':'UTF-8'}&amp;vieworder&amp;id_order={$htmldata.orderId|escape:'htmlall':'UTF-8'}">
				<div class="form-group">
					<div class="col-lg-9">
						<select name="id_address">
							{foreach from=$htmldata.collectionAdresses item=collectionAddress}
									<option value="{$collectionAddress.id_address|escape:'htmlall':'UTF-8'}">
										{$collectionAddress.alias|escape:'htmlall':'UTF-8'} -
										{$collectionAddress.address1|escape:'htmlall':'UTF-8'} ,
										{$collectionAddress.city|escape:'htmlall':'UTF-8'} ,
										{$collectionAddress.name|escape:'htmlall':'UTF-8'}
									</option>
							{/foreach}
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
						{foreach from=$htmldata.collectionAdresses item=collectionAddress}
								{if $collectionAddress.id_address == $htmldata.deliveryAddressId}
									<a class="btn btn-default pull-right"
										 href="?controller=adminaddresses&amp;id_address={$collectionAddress.id_address|escape:'htmlall':'UTF-8'}&amp;updateaddress&amp;token={$htmldata.token|escape:'htmlall':'UTF-8'}">
										<i class="icon-pencil"></i>
										Edit
									</a>
									{$collectionAddress.alias|escape:'htmlall':'UTF-8'} "<br>"
									{$collectionAddress.address1|escape:'htmlall':'UTF-8'} "<br>"
									{$collectionAddress.city|escape:'htmlall':'UTF-8'} "<br>"
									{$collectionAddress.name|escape:'htmlall':'UTF-8'}
								{/if}
						{/foreach}
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
			      action="?controller=AdminOrders&amp;token={$htmldata.token|escape:'htmlall':'UTF-8'}&amp;vieworder&amp;
						id_order={$htmldata.orderId|escape:'htmlall':'UTF-8'}">
				<div class="form-group">
					<div class="col-lg-9">
						<select name="id_address">
							{foreach from=$htmldata.deliveryAddresses item=deliveryAddress}
								<option value="{$deliveryAddress.id_address|escape:'htmlall':'UTF-8'}"
									{($deliveryAddress.id_address|escape:'htmlall':'UTF-8' == $htmldata.deliveryAddressId|escape:'htmlall':'UTF-8') ? "selected" : "" }>
									{$deliveryAddress.alias|escape:'htmlall':'UTF-8'} -
									{$deliveryAddress.address1|escape:'htmlall':'UTF-8'} ,
									{$deliveryAddress.city|escape:'htmlall':'UTF-8'} ,
									{$deliveryAddress.name|escape:'htmlall':'UTF-8'}
								</option>
							{/foreach}
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
						{foreach from=$htmldata.deliveryAddresses item=deliveryAddress}
								{if $deliveryAddress.id_address == $htmldata.deliveryAddressId}
										<a class="btn btn-default pull-right"
											 href="?controller=adminaddresses&amp;id_address={$deliveryAddress.id_address|escape:'htmlall':'UTF-8'}&amp;updateaddress
											 &amp;token={$htmldata.token|escape:'htmlall':'UTF-8'}">
											<i class="icon-pencil"></i>
											Edit
										</a>
										{$deliveryAddress.alias|escape:'htmlall':'UTF-8'} <br>
										{$deliveryAddress.address1|escape:'htmlall':'UTF-8'} <br>
										{$deliveryAddress.city|escape:'htmlall':'UTF-8'} <br>
										{$deliveryAddress.name|escape:'htmlall':'UTF-8'}
								{/if}
						{/foreach}
					</div>
					<div class="col-sm-6 hidden-print">
						<div id="map-delivery-canvas" style="height: 190px"></div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
