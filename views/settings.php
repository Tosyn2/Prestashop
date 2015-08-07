<style>
	#tabList {
		clear: left;
	}

	.tabItem {
		display: block;
		background: #FFFFF0;
		border: 1px solid #CCCCCC;
		padding: 10px;
		padding-top: 20px;
	}
</style>

<h1><?= $displayName ?></h1>

<?php if (isset($errors)):
	foreach ($errors as $error): ?>
		<div class="alert error"><img src="<?= _PS_IMG_ ?>admin/forbbiden.gif" alt="nok"/> <?= $error ?></div>
	<?php endforeach;
endif; ?>

<fieldset>
	<legend><img src="logo.gif" alt=""><?= $displayName ?> Status</legend>

	<?php if ($configured) { ?>
		<img src="<?= _PS_IMG_ ?>admin/module_install.png"/>
		<strong><?= $displayName ?> is configured correctly!</strong>
	<?php } else { ?>
		<img src="<?= _PS_IMG_ ?>admin/error2.png"/>
		<strong><?= $displayName ?> config settings are not correct. Please see below for details.</strong>
		<?php foreach ($inputs as $key => $input): ?>
			<?php if ($key != 'MDS_RISK'): ?>
				<?php foreach ($alerts as $alert): ?>
					<?php if ($alerts[$key] == $key) {
						$image = _PS_IMG_ . 'admin/warn2.png';
						if ($key != 'MDS_EMAIL' && $key != 'MDS_PASSWORD' && $key != 'MDS_RISK') {
							$message = "is not correctly configured. Use '0' to not include a surcharge fee";
						}
						if ($key == 'MDS_EMAIL' || $key == 'MDS_PASSWORD') {
							$message = 'is incorrect';
						}
					} else {
						$image = _PS_IMG_ . 'admin/success.png';
						if ($key != 'MDS_EMAIL' && $key != 'MDS_PASSWORD' && $key != 'MDS_RISK') {
							$message = "is correctly configured";
						}
						if ($key == 'MDS_EMAIL' || $key == 'MDS_PASSWORD') {
							$message = "is correct";
						}
					} ?>
				<?php endforeach; ?>
				<br/><img src="<?= $image ?>"/> <?= $input['name'] ?> <?= $message ?>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php } ?>
</fieldset>

<div class="clear">&nbsp;</div>
<div id="tabList">
	<div class="tabItem">
		<form action="<?= $formUrl ?>" method="post" class="form" id="configForm">
			<fieldset style="border: 0px;">
				<h4>General configuration:</h4>
				<?php foreach ($inputs as $key => $value): ?>
					<label><?= $value['name'] ?>:</label>
					<div class="margin-form">
					
						<?php if ($value['type'] == "checkbox") {?>
							<?php if (Configuration::get('MDS_RISK') == 1) $checked = "checked" ?>
						
						<input type="<?= $value['type'] ?>" size="20" name="<?= $key ?>" value="1" <?= $checked ?>>
						<?php } else {?>
							<input type="<?= $value['type'] ?>" size="20" name="<?= $key ?>" value="<?=
						Tools::getValue($key, Configuration::get($key))
						?>"> <?php } ?>
					</div>
				<?php endforeach; ?>
				<div class="margin-form">
					<input class="button" name="submitSave" type="submit">
				</div>
			</fieldset>
		</form>
	</div>
</div>
