<style>
	.nobootstrap input[type="email"], .nobootstrap input[type="number"] {
		border: 1px solid #CCC;
		background-color: #FFF;
		padding: 2px 4px;
		box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.1) inset;
		width: 150px;
	}
</style>

<h1><?= $displayName ?></h1>

<?= $errors ?>

<form action="<?= $formUrl ?>" method="post" class="form" id="configForm">
	<fieldset>
		<legend>General configuration:</legend>
		<?php foreach ($surcharges as $serviceId => $data): ?>
			<div class="form-group">
				<label><?= $data['name'] ?> (R):</label>
				<input class="form-control" type="text" name="surcharge[<?= $serviceId ?>]" value="<?= $data['value'] ?>">
			</div>
		<?php endforeach; ?>

		<div class="form-group">
			<label for="collivery-email">Email:</label>
			<input id="collivery-email" class="form-control" type="text" name="email" value="<?= $email ?>">
		</div>

		<div class="form-group">
			<label for="collivery-password">Password:</label>
			<input id="collivery-password" class="form-control" type="password" name="password" value="">
		</div>

		<div class="form-group">
			<label for="collivery-risk-cover">Risk Cover:</label>
			<input id="collivery-risk-cover" class="form-control" type="checkbox" name="risk-cover" value="1"<?= $riskCover ? ' checked="checked"' : '' ?>>
		</div>

		<div class="margin-form">
			<input class="button" name="submitSave" type="submit">
		</div>
	</fieldset>
</form>
