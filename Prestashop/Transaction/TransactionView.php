<?php namespace Mds\Prestashop\Transaction;

use Mds;
use Mds\Prestashop;
use Mds_View;
use Mds_ColliveryApi;
use Mds\Prestashop\Settings;

class TransactionView extends Transaction {

	protected $collivery;

	/**
	 * @param $params
	 * @param $token
	 *
	 * @return string|void
	 * @throws \PrestaShopDatabaseException
	 */
	public function generateView($params, $token)
	{

		$this->collivery = Mds_ColliveryApi::getInstance();

		$sql = 'SELECT `id_delivery_address` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `id_order` = ' . $params['id_order'];
		$deliveryAddressId = $this->db->getValue($sql);

		$sql = 'SELECT `id_service` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `id_order` = ' . $params['id_order'];
		$serviceId = $this->db->getValue($sql);
		$services = $this->collivery->getServices();
		$serviceName = $services[ $serviceId ];

		$sql = 'SELECT * FROM `ps_address` LEFT JOIN (`ps_state`) ON (`ps_address`.`id_state`=`ps_state`.`id_state`) where `id_customer` = 2 AND deleted = 0';
		$deliveryAddresses = $this->db->ExecuteS($sql);

		$sql = 'SELECT `id_manufacturer` FROM `ps_manufacturer` where `name` = "MDS Collection Addresses"';
		$idManufacturer = $this->db->getValue($sql);

		$sql = 'SELECT * FROM `ps_address` LEFT JOIN (`ps_state`) ON (`ps_address`.`id_state`=`ps_state`.`id_state`) where `id_manufacturer` = ' . $idManufacturer . ' AND deleted = 0';
		$collectionAddresses = $this->db->ExecuteS($sql);

		$sql = 'SELECT `id_collection_address` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `id_order` = ' . $params['id_order'];
		$collectionAddressId = $this->db->getValue($sql);

		$orderId = $params['id_order'];

		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` = ' . $deliveryAddressId;
		$address = $this->db->getRow($sql);

		$suburb = $address['city'];
		$suburbs = $this->collivery->getSuburbs('');

		$locationType = $address['other'];
		$locationTypes = $this->collivery->getLocationTypes();

		$back = "Location: ./index.php?controller=AdminOrders&id_order=" . $params['id_order'] . "&vieworder&token=" . $token;

		if (isset($_POST['func_name'])) {

			$_GET['func_name'];
			$form_action_func = $_GET['func_name'];

			if ($form_action_func === "getQuote") {

				$price = $this->getQuote($params);

			} elseif ($form_action_func === "addCollivery") {

				$idOrder = $_GET['id_order'];
				$this->despatchDelivery($params, $idOrder);

				return header($back);

			} elseif ($form_action_func === "changeCollectionAddress") {
				$idOrder = $_GET['id_order'];

				if (Tools::isSubmit('id_address_col')) {

					$value = Tools::getValue('id_collection_address');

					$this->changeCollectionAddress($value, $idOrder);
				}

				return header($back);

			} elseif ($form_action_func === "changeDeliveryAddress") {
				$idOrder = $_GET['id_order'];

				if (Tools::isSubmit('id_address_del')) {

					$value = Tools::getValue('id_address');

					$this->changeDeliveryAddress($value, $idOrder);
				}

				return header($back);

			}

		}
		$sql = 'SELECT `waybill` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `id_order` = ' . $params['id_order'];
		$waybill = $this->db->getValue($sql);

		$waybillEnc = base64_encode($waybill);

		if ( ! $waybill) {
			return Mds_View::make(
				'shipping_control',
				compact(
					'idManufacturer',
					'deliveryAddressId',
					'orderId',
					'carrierName',
					'serviceId',
					'deliveryAddresses',
					'suburb',
					'suburbs',
					'locationType',
					'locationTypes',
					'countryName',
					'token',
					'collectionAddresses',
					'collectionAddressId',
					'price',
					'message',
					'mdsManufacturerId'
				)
			);

		} else {

			$status = $this->getDeliveryStatus($waybill);

			return Mds_View::make(
				'delivery_details',
				compact(
					'deliveryAddressId',
					'orderId',
					'serviceId',
					'deliveryAddresses',
					'suburb',
					'suburbs',
					'locationType',
					'locationTypes',
					'token',
					'collectionAddresses',
					'collectionAddressId',
					'status',
					'waybill',
					'serviceName',
					'waybillEnc'
				)
			);
		}
	}

	/**
	 * @return string
	 */
	public function addAdminJs()
	{

		$this->collivery = Mds_ColliveryApi::getInstance();
		$idAddress = $_GET['id_address'];

		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` = ' . $idAddress;
		$address = $this->db->getRow($sql);

		$suburb = $address['city'];
		$suburbs = $this->collivery->getSuburbs('');

		$locationTypes = $this->collivery->getLocationTypes();
		$locationType = $address['other'];

		return Mds_View::make(
			'admin_header',
			compact('suburbs', 'suburb', 'locationTypes', 'locationType')
		);
	}

	/**
	 * @return string
	 */
	public function addFrontEndJs()
	{

		$this->collivery = Mds_ColliveryApi::getInstance();
		$idAddress = (int) $this->context->cart->id_address_delivery;

		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` = ' . $idAddress;
		$address = $this->db->getRow($sql);

		$suburb = $address['city'];
		$suburbs = $this->collivery->getSuburbs('');

		$locationType = $address['other'];
		$locationTypes = $this->collivery->getLocationTypes();

		//$this->context->controller->addJS(($this->_path) . 'helper.js');

		return Mds_View::make(
			'footer',
			compact('suburbs', 'suburb', 'locationTypes', 'locationType')
		);
	}

}
