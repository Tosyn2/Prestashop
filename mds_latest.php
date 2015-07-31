<?php

// Avoid direct access to the file
if (!defined('_PS_VERSION_'))
	exit;
	
class mdss extends CarrierModule
{
	public  $id_carrier;
	private $_html = '';
	private $_postErrors = array();
	private $_moduleName = 'mds';
	public static $_this = false;
	protected $cache;
	protected $db;
	protected $towns;
	protected $services;
	protected $location_types;
	protected $extension_id;
	protected $app_name;
	protected $app_info;
	protected $collivery;
	protected $password;
	protected $username;
	protected $converter;
	protected $risk_cover;
	protected $email;
	//public $colliveryCost;

	/*
	** Construct Method
	**
	*/

	public function __construct()
	{
		$this->name = 'mds';
		$this->tab = 'shipping_logistics';
		$this->version = '1.0';
		$this->author = 'Nicole Johnson';
		$this->limited_countries = array();

		parent::__construct ();

		$this->displayName = $this->l('Mds Shipping');
		$this->description = $this->l('Offer your customers, different delivery methods that you want');

		if (self::isInstalled($this->name))
		{
			// Getting carrier list
			global $cookie;
			$carriers = Carrier::getCarriers($cookie->id_lang, true, false, false, NULL, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);

			// Saving id carrier list
			$id_carrier_list = array();
			foreach($carriers as $carrier)
				$id_carrier_list[] .= $carrier['id_carrier'];

			// Testing if Carrier Id exists
			$warning = array();
			if (!in_array((int)(Configuration::get('MYCARRIER1_CARRIER_ID')), $id_carrier_list))
				$warning[] .= $this->l('"Carrier 1"').' ';
			if (!in_array((int)(Configuration::get('MYCARRIER2_CARRIER_ID')), $id_carrier_list))
				$warning[] .= $this->l('"Carrier 2"').' ';
			if (!Configuration::get('MYCARRIER1_OVERCOST'))
				$warning[] .= $this->l('"Carrier 1 Overcost"').' ';
			if (!Configuration::get('MYCARRIER2_OVERCOST'))
				$warning[] .= $this->l('"Carrier 2 Overcost"').' ';
			if (count($warning))
				$this->warning .= implode(' , ',$warning).$this->l('must be configured to use this module correctly').' ';
		}
	
				require_once 'helperClasses/MdsColliveryService.php';
				
				$this->mdsService = \helperClasses\MdsColliveryService::getInstance();
				$this->collivery = $this->mdsService->returnColliveryClass();

				
				

	}


	/*
	** Install / Uninstall Methods
	**
	*/
	

	public function install()
	{
		$carrierConfig = array(
			0 => array('name' => 'Overnight before 10:00',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array('fr' => 'Description 1', 'en' => 'Description 1', Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Description 1'),
				'id_zone' => 1,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'mds',
				'need_range' => true
			),
			1 => array('name' => 'Overnight before 16:00',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array('fr' => 'Description 2', 'en' => 'Description 2', Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Description 2'),
				'id_zone' => 1,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'mds',
				'need_range' => true
			),
			2 => array('name' => 'Road Freight',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array('fr' => 'Description 2', 'en' => 'Description 2', Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Description 2'),
				'id_zone' => 1,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'mds',
				'need_range' => true
			),
			3 => array('name' => 'Road Freight Express',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array('fr' => 'Description 2', 'en' => 'Description 2', Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Description 2'),
				'id_zone' => 1,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'mds',
				'need_range' => true
			),
		);

		$id_carrier1 = $this->installExternalCarrier($carrierConfig[0]);
		$id_carrier2 = $this->installExternalCarrier($carrierConfig[1]);
		$id_carrier3 = $this->installExternalCarrier($carrierConfig[2]);
		$id_carrier4 = $this->installExternalCarrier($carrierConfig[3]);
            
		Configuration::updateValue('MYCARRIER1_CARRIER_ID', (int)$id_carrier1);
		Configuration::updateValue('MYCARRIER2_CARRIER_ID', (int)$id_carrier2);
		Configuration::updateValue('MYCARRIER3_CARRIER_ID', (int)$id_carrier3);
		Configuration::updateValue('MYCARRIER4_CARRIER_ID', (int)$id_carrier4);

		if (!parent::install() ||
		    !Configuration::updateValue('MYCARRIER1_OVERCOST', '') ||
		    !Configuration::updateValue('MYCARRIER2_OVERCOST', '') ||
		    !Configuration::updateValue('MYCARRIER3_OVERCOST', '') ||
		    !Configuration::updateValue('MYCARRIER4_OVERCOST', '') ||
		    !$this->registerHook('updateCarrier') ||
		    !$this->registerHook('actionPaymentConfirmation') ||
		    !$this-> registerHook('leftColumn')) 
			return false;
		return true;
	}
	
	public function uninstall()
	{
		// Uninstall
		if (!parent::uninstall() ||
		    !Configuration::deleteByName('MYCARRIER1_OVERCOST') ||
		    !Configuration::deleteByName('MYCARRIER2_OVERCOST') ||
		    !Configuration::deleteByName('MYCARRIER3_OVERCOST') ||
		    !Configuration::deleteByName('MYCARRIER4_OVERCOST') ||
		   
		    
		    !$this->unregisterHook('updateCarrier') ||
		    !$this->unregisterHook('actionPaymentConfirmation') ||
		    !$this->unregisterHook('leftColumn'))
			return false;
		
		// Delete External Carrier
		$Carrier1 = new Carrier((int)(Configuration::get('MYCARRIER1_CARRIER_ID')));
		$Carrier2 = new Carrier((int)(Configuration::get('MYCARRIER2_CARRIER_ID')));
		$Carrier3 = new Carrier((int)(Configuration::get('MYCARRIER3_CARRIER_ID')));
		$Carrier4 = new Carrier((int)(Configuration::get('MYCARRIER4_CARRIER_ID')));



		// If external carrier is default set other one as default
		if (Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier1->id) || Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier2->id) ||Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier3->id) || Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier3->id) || Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier4->id))
		{
			global $cookie;
			$carriersD = Carrier::getCarriers($cookie->id_lang, true, false, false, NULL, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
			foreach($carriersD as $carrierD)
				if ($carrierD['active'] AND !$carrierD['deleted'] AND ($carrierD['name'] != $this->_config['name']))
					Configuration::updateValue('PS_CARRIER_DEFAULT', $carrierD['id_carrier']);
		}

		// Then delete Carrier
		$Carrier1->deleted = 1;
		$Carrier2->deleted = 1;
		$Carrier3->deleted = 1;
		$Carrier4->deleted = 1;
		if (!$Carrier1->update() || !$Carrier2->update() ||  !$Carrier3->update() ||  !$Carrier4->update())
			return false;

		return true;
	}
	
	//name =[express => 1, normal => 2, road =>5]
	public static function installExternalCarrier($config)
	{
		$carrier = new Carrier();
		$carrier->name = $config['name'];
		$carrier->id_tax_rules_group = $config['id_tax_rules_group'];
		$carrier->id_zone = $config['id_zone'];
		$carrier->active = $config['active'];
		$carrier->deleted = $config['deleted'];
		$carrier->delay = $config['delay'];
		$carrier->shipping_handling = $config['shipping_handling'];
		$carrier->range_behavior = $config['range_behavior'];
		$carrier->is_module = $config['is_module'];
		$carrier->shipping_external = $config['shipping_external'];
		$carrier->external_module_name = $config['external_module_name'];
		$carrier->need_range = $config['need_range'];

		$languages = Language::getLanguages(true);
		foreach ($languages as $language)
		{
			if ($language['iso_code'] == 'fr')
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			if ($language['iso_code'] == 'en')
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			if ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')))
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
		}

		if ($carrier->add())
		{
			$groups = Group::getGroups(true);
			foreach ($groups as $group)
				Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_group', array('id_carrier' => (int)($carrier->id), 'id_group' => (int)($group['id_group'])), 'INSERT');

			$rangePrice = new RangePrice();
			$rangePrice->id_carrier = $carrier->id;
			$rangePrice->delimiter1 = '0';
			$rangePrice->delimiter2 = '10000';
			$rangePrice->add();

			$rangeWeight = new RangeWeight();
			$rangeWeight->id_carrier = $carrier->id;
			$rangeWeight->delimiter1 = '0';
			$rangeWeight->delimiter2 = '10000';
			$rangeWeight->add();

			$zones = Zone::getZones(true);
			foreach ($zones as $zone)
			{
				Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_zone', array('id_carrier' => (int)($carrier->id), 'id_zone' => (int)($zone['id_zone'])), 'INSERT');
				Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => (int)($rangePrice->id), 'id_range_weight' => NULL, 'id_zone' => (int)($zone['id_zone']), 'price' => '0'), 'INSERT');
				Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => NULL, 'id_range_weight' => (int)($rangeWeight->id), 'id_zone' => (int)($zone['id_zone']), 'price' => '0'), 'INSERT');
			}

			// Copy Logo
			if (!copy(dirname(__FILE__).'/carrier.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'))
				return false;

			// Return ID Carrier
			return (int)($carrier->id);
		}

		return false;
	}




	/*
	** Form Config Methods
	**
	*/

	public function getContent()
	{
		$this->_html .= '<h2>' . $this->l('My Carrier').'</h2>';
		if (!empty($_POST) AND Tools::isSubmit('submitSave'))
		{
			$this->_postValidation();
			if (!sizeof($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors AS $err)
					$this->_html .= '<div class="alert error"><img src="'._PS_IMG_.'admin/forbbiden.gif" alt="nok" />&nbsp;'.$err.'</div>';
		}
		$this->_displayForm();
		return $this->_html;
	}

	private function _displayForm()
	{
		$this->_html .= '<fieldset>
		<legend><img src="'.$this->_path.'logo.gif" alt="" /> '.$this->l('My Carrier Module Status').'</legend>';

		$alert = array();
		if (!Configuration::get('MYCARRIER1_OVERCOST') || Configuration::get('MYCARRIER1_OVERCOST') == '')
			$alert['carrier1'] = 1;
		if (!Configuration::get('MYCARRIER2_OVERCOST') || Configuration::get('MYCARRIER2_OVERCOST') == '')
			$alert['carrier2'] = 1;
		if (!Configuration::get('MYCARRIER3_OVERCOST') || Configuration::get('MYCARRIER3_OVERCOST') == '')
			$alert['carrier3'] = 1;
		if (!Configuration::get('MYCARRIER4_OVERCOST') || Configuration::get('MYCARRIER4_OVERCOST') == '')
			$alert['carrier4'] = 1;

		if (!count($alert))
			$this->_html .= '<img src="'._PS_IMG_.'admin/module_install.png" /><strong>'.$this->l('My Carrier is configured and online!').'</strong>';
		else
		{
			$this->_html .= '<img src="'._PS_IMG_.'admin/warn2.png" /><strong>'.$this->l('My Carrier is not configured yet, please:').'</strong>';
			$this->_html .= '<br />'.(isset($alert['carrier1']) ? '<img src="'._PS_IMG_.'admin/warn2.png" />' : '<img src="'._PS_IMG_.'admin/module_install.png" />').' 1) '.$this->l('Configure the carrier 1 overcost');
			$this->_html .= '<br />'.(isset($alert['carrier2']) ? '<img src="'._PS_IMG_.'admin/warn2.png" />' : '<img src="'._PS_IMG_.'admin/module_install.png" />').' 2) '.$this->l('Configure the carrier 2 overcost');
		}

		$this->_html .= '</fieldset><div class="clear">&nbsp;</div>
			<style>
				#tabList { clear: left; }
				.tabItem { display: block; background: #FFFFF0; border: 1px solid #CCCCCC; padding: 10px; padding-top: 20px; }
			</style>
			<div id="tabList">
				<div class="tabItem">
					<form action="index.php?tab='.Tools::getValue('tab').'&configure='.Tools::getValue('configure').'&token='.Tools::getValue('token').'&tab_module='.Tools::getValue('tab_module').'&module_name='.Tools::getValue('module_name').'&id_tab=1&section=general" method="post" class="form" id="configForm">

					<fieldset style="border: 0px;">
						<h4>'.$this->l('General configuration').' :</h4>
						<label>'.$this->l('Overnight before 10:00').' : </label>
						<div class="margin-form"><input type="text" size="20" name="mycarrier1_overcost" value="'.Tools::getValue('mycarrier1_overcost', Configuration::get('MYCARRIER1_OVERCOST')).'" /></div>
						<label>'.$this->l('Overnight before 16:00').' : </label>
						<div class="margin-form"><input type="text" size="20" name="mycarrier2_overcost" value="'.Tools::getValue('mycarrier2_overcost', Configuration::get('MYCARRIER2_OVERCOST')).'" /></div>
						<label>'.$this->l('Road Freight Express').' : </label>
						<div class="margin-form"><input type="text" size="20" name="mycarrier3_overcost" value="'.Tools::getValue('mycarrier3_overcost', Configuration::get('MYCARRIER3_OVERCOST')).'" /></div>
						<label>'.$this->l('Road Freight').' : </label>
						<div class="margin-form"><input type="text" size="20" name="mycarrier4_overcost" value="'.Tools::getValue('mycarrier4_overcost', Configuration::get('MYCARRIER4_OVERCOST')).'" /></div>
						<label>'.$this->l('MDS account email').'</label>
						<div class="margin-form"><input type="text" name="your_email" value="'. Tools::getValue('your_email') . '"  /></div>
						<label>'.$this->l('MDS account password').'</label>
						<div class="margin-form"><input type="text" name="your_password" value="'. Tools::getValue('your_password') . '" /></div>
						<label>'.$this->l('MDS risk cover').'</label>
						<div class="margin-form"><input type="checkbox" name="your_riskcover" /></div>
					</div>
				<br /><br />
				</fieldset>				
				<div class="margin-form"><input class="button" name="submitSave" type="submit"></div>
			</form>
		</div></div>';
	}

	private function _postValidation()
	{
		// Check configuration values
		if (Tools::getValue('mycarrier1_overcost') == '' && Tools::getValue('mycarrier2_overcost') == '' && Tools::getValue('mycarrier3_overcost') == '' && Tools::getValue('mycarrier4_overcost') == '')
			$this->_postErrors[]  = $this->l('You have to configure at least one carrier');
	}

	private function _postProcess()
	{
			$email = Tools::getValue('your_email'); //nj
            $password = Tools::getValue('your_password'); //nj
            
            

            // Saving new configurations
		
		
		if (Configuration::updateValue('MYCARRIER1_OVERCOST', Tools::getValue('mycarrier1_overcost')) &&
			Configuration::updateValue('MYCARRIER1_OVERCOST', Tools::getValue('mycarrier1_overcost')) &&
		    Configuration::updateValue('MYCARRIER2_OVERCOST', Tools::getValue('mycarrier2_overcost')) &&
		    Configuration::updateValue('MYCARRIER3_OVERCOST', Tools::getValue('mycarrier3_overcost')) &&
		    Configuration::updateValue('MYCARRIER4_OVERCOST', Tools::getValue('mycarrier4_overcost')) &&
		    Configuration::updateValue($this->name.'_email', $email) && //nj
			Configuration::updateValue($this->name.'_password', $password))
			$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
		else
			$this->_html .= $this->displayErrors($this->l('Settings failed'));
	}


	/*
	** Hook update carrier
	**
	*/

	public function hookupdateCarrier($params)
	{
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER1_CARRIER_ID')))
			Configuration::updateValue('MYCARRIER1_CARRIER_ID', (int)($params['carrier']->id));
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER2_CARRIER_ID')))
			Configuration::updateValue('MYCARRIER2_CARRIER_ID', (int)($params['carrier']->id));
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER3_CARRIER_ID')))
			Configuration::updateValue('MYCARRIER3_CARRIER_ID', (int)($params['carrier']->id));
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER4_CARRIER_ID')))
			Configuration::updateValue('MYCARRIER4_CARRIER_ID', (int)($params['carrier']->id));
	}

	/*
	** Front Methods
	**
	** If you set need_range at true when you created your carrier (in install method), the method called by the cart will be getOrderShippingCost
	** If not, the method called will be getOrderShippingCostExternal
	**
	** $params var contains the cart, the customer, the address
	** $shipping_cost var contains the price calculated by the range in carrier tab
	**
	*/

	
	
	public function buildColliveryDataArray($params)
	{
			//print_r($params);
// 			$params[id_address_delivery]  => sql search for address details collivery to
// 			[id_customer] =>sql search for contact to
// 			
// 			
// 			Product
// 			[width] => 50.000000
// 								[width] => 50.000000
// 								[height] => 50.000000
// 								[depth] => 50.000000
// 								[quantity] => 1
// 								[id_address_delivery] => 5
// 													[weight_attribute] => 2.000000

		}
	
	public function getOrderShippingCost($params, $shipping_cost)
	{
	
				//$orderParams = $this->buildColliveryDataArray($params);
		
			//	$orderParams = $colliveryData;
			
		$orderParams = Array
					(
						'collivery_from' => '1190306',
						'contact_from' => '1232622',
						'collivery_to' => '1223084',
						'contact_to' => '1267157',
						'collivery_type'=> '2',
						//'service' => '3',
						'weight' => '6',
						'cover' => 'true',
						'parcels' => Array
								( 
									'0' => Array
											(
												'weight' => '2',
												'height' => '10',
												'length' => '12',
												'width' => '7'
											),
									'1' => Array
											(
												'weight' => '4',
												'height' => '3',
												'length' => '17',
												'width' => '19'
											)
								)
					);
						
				//		print_r($orderParams);
				

		
				switch ($this->id_carrier) {
					case '64':
					
						$orderParams[service] = 1;
						$colliveryPriceOptions =  $this->collivery->getPrice($orderParams); //Code broken here.
						(float)$colliveryPrice = $colliveryPriceOptions[price][inc_vat];
								print_r($this->collivery->getPrice($orderParams));
						
					//	echo $colliveryPriceOptions[price][inc_vat] . "<br>";

						$totalShipping = (float)(Configuration::get('MYCARRIER1_OVERCOST')) + $colliveryPrice;
						
						return $totalShipping;
						break;
						
					case '65':
						$orderParams[service] = 2;
						$colliveryPriceOptions =  $this->collivery->getPrice( $orderParams );
						(float)$colliveryPrice = $colliveryPriceOptions[price][inc_vat];

												echo $colliveryPriceOptions[price][inc_vat] . "<br>";

						$totalShipping = (float)(Configuration::get('MYCARRIER2_OVERCOST')) + $colliveryPrice;
						return $totalShipping;

						break;
						
					case '66':
						$orderParams[service] = 3;
						$colliveryPriceOptions =  $this->collivery->getPrice( $orderParams );
						(float)$colliveryPrice = $colliveryPriceOptions[price][inc_vat];

						$totalShipping = (float)(Configuration::get('MYCARRIER3_OVERCOST')) + $colliveryPrice;
						return $totalShipping;
						
						break;
						
					case '67':
						$orderParams[service] = 5;
						$colliveryPriceOptions =  $this->collivery->getPrice( $orderParams );
						(float)$colliveryPrice = $colliveryPriceOptions[price][inc_vat];

						$totalShipping = (float)(Configuration::get('MYCARRIER4_OVERCOST')) + $colliveryPrice;
						return $totalShipping;
						
						break;
						
					default:
 						return  False;
 				}

	}
	
	public function getOrderShippingCostExternal($params)
	{
		return False;
	}
	
    public function hookLeftColumn() {
    
// 		$this->collivery = new Collivery;
// 		$towns = $this->collivery->getTowns();
// 		$suburbs = $this->collivery->getSuburbs('147');
// 		print_r($suburbs);
	}
	
	public function hookActionPaymentConfirmation() 
	{
		$orderParams = Array
					(
						'collivery_from' => '1190306',
						'contact_from' => '1232622',
						'collivery_to' => '1223084',
						'contact_to' => '1267157',
						'collivery_type'=> '2',
						'service' => '3',
						'weight' => '6',
						'cover' => 'true',
						'parcels' => Array
								( 
									'0' => Array
											(
												'weight' => '2',
												'height' => '10',
												'length' => '12',
												'width' => '7'
											),
									'1' => Array
											(
												'weight' => '4',
												'height' => '3',
												'length' => '17',
												'width' => '19'
											)
								)
					);
					
				
	
	return $this->mdsService->addCollivery($orderParams, true);

	}
	


	

	
}







