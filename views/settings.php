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

<?php if(isset($errors)):
foreach ($errors as $error): ?>
	<div class="alert error"><img src="<?=_PS_IMG_?>admin/forbbiden.gif" alt="nok" /> <?=$error?></div>
<?php endforeach;
endif; ?>

<fieldset>
	<legend><img src="logo.gif" alt=""><?= $displayName ?> Status</legend>

	<?php if($configured): ?>
		<img src="' . _PS_IMG_ . 'admin/module_install.png" />
		<strong><?= $displayName ?> is configured correctly!</strong>
	<?php endif; ?>
</fieldset>

<div class="clear">&nbsp;</div>

<div id="tabList">
	<div class="tabItem">
		<form action="<?= $formUrl ?>" method="post" class="form" id="configForm">
			<fieldset style="border: 0px;">
				<h4>General configuration:</h4>
				<?php foreach ($inputs as $key => $value): ?>
					<label><?=$value['name']?>:</label>
					<div class="margin-form">
						<input type="<?=$value['type']?>" size="20" name="<?=$key?>" value="<?=
							Tools::getValue($key, Configuration::get($key))
						?>">
					</div>
				<?php endforeach; ?>

				<div class="margin-form">
					<input class="button" name="submitSave" type="submit">
				</div>
			</fieldset>
		</form>
	</div>
</div>
