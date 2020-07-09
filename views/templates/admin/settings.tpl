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
<style>
	.nobootstrap input[type="email"], .nobootstrap input[type="number"] {
		border: 1px solid #CCC;
		background-color: #FFF;
		padding: 2px 4px;
		box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.1) inset;
		width: 150px;
	}
</style>

<h1>{$htmldata.displayName|escape:'htmlall':'UTF-8'}</h1>

{$htmldata.errors|escape:'htmlall':'UTF-8'}

<form action="{$htmldata.formUrl|escape:'htmlall':'UTF-8'}" method="post" class="form" id="configForm">
	<fieldset>
		<legend>General configuration:</legend>

		{foreach from=$htmldata.surcharges key=serviceId item=data}
		<div class="form-group">
			<label>{$data.name|escape:'htmlall':'UTF-8'}</label>
			<input class="form-control" type="number" name="surcharge[{$serviceId|escape:'htmlall':'UTF-8'}]" value="{$data.value|escape:'htmlall':'UTF-8'}">
		</div>
		{/foreach}

		<div class="form-group">
			<label for="collivery-email">Email:</label>
			<input id="collivery-email" class="form-control" type="email" name="email" value="{$htmldata.email|escape:'htmlall':'UTF-8'}">
		</div>

		<div class="form-group">
			<label for="collivery-password">Password:</label>
			<input id="collivery-password" class="form-control" type="password" name="password" value="">
		</div>

		<div class="form-group">
			<label for="collivery-risk-cover">Risk Cover:</label>
			<input id="collivery-risk-cover" class="form-control" type="checkbox" name="risk-cover" value="1"{($htmldata.riskCover|escape:'htmlall':'UTF-8') ? 'checked="checked"' : '' }>
		</div>

		<div class="margin-form">
			<input class="button" name="submitSave" type="submit">
		</div>
	</fieldset>
</form>
