 	$testForColumn = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "mds_collivery_processed", ARRAY_A);
		if(!empty($testForColumn) && !array_key_exists('order_id', $testForColumn)) {
			$wpdb->query(ALTER TABLE  `ps_state` ADD  `id_mds` INT NULL AFTER  `iso_code`);
		}
		
		ALTER TABLE  `ps_state` ADD  `id_mds` INT NULL AFTER  `iso_code`
		
		
			$table_name = $wpdb->prefix . 'mds_collivery_processed';
		$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`waybill` int(11) NOT NULL,
			`order_id` int(11) NOT NULL,
			`validation_results` TEXT NOT NULL,
			`status` int(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`id`)
		);";
		
			if (version_compare(PHP_VERSION, '5.3.0') < 0) {
			die('Your PHP version is not able to run this plugin, update to the latest version before installing this plugin.');
		}
