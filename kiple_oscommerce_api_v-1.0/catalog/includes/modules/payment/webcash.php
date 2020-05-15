<?php
/*
  $Id: webcash.php,v 1.2 2008/08/23

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2004 osCommerce

  Released under the GNU General Public License
*/

  class webcash {
    var $code, $title, $description, $enabled;

// class constructor
    function webcash() {
      global $order;

      $this->code = 'webcash';
      $this->title = MODULE_PAYMENT_WEBCASH_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_WEBCASH_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_WEBCASH_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_WEBCASH_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_WEBCASH_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_WEBCASH_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();
      $this->form_action_url = 'https://webcash.com.my/wcgatewayinit.php';
    }

// class methods
    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_WEBCASH_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_WEBCASH_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
        $ids=array(
          array('id' => 22, 'text' => 'Webcash'),
        );

        $gateway = "";
        $checked = true;

        $gateway .= tep_draw_pull_down_menu("PaymentId", $ids, $ids[0]);

            return array('id' => $this->code,
                         'module' => $this->title,
                         'fields' => array(array('title' => 'Select Gateway',
                                                 'field' => $gateway)));

    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      return false;
    }

    function get_gateway($id){
	$ids=array(
		array('id' => 22, 'text' => 'Webcash'),

	);

		for ($i=0; $i<sizeof($ids); $i++)
		{
			if ($ids[$i]['id'] == $id)
				$gateway = $ids[$i]['text'];
		}
		return $gateway;

	}

	function process_button() {
		global $order, $currencies;

		$item_number;
		for ($i=0; $i<sizeof($order->products); $i++) {
			$item_number .= ' '.$order->products[$i]['name'].' ,';
		}

		//$amount = number_format(($order->info['total'] + $order->info['shipping_cost']) * $currencies->get_value($my_currency), $currencies->get_decimal_places($my_currency));
		$amount = $order->info['total'];
		$items = substr_replace($item_number,'',-2);

		$merchantCode = MODULE_PAYMENT_WEBCASH_CODE;
		$merchantKey = MODULE_PAYMENT_WEBCASH_KEY;
		$refNo = rand(10000, 9999999999);
		$today = date("F j, Y, g:i a");

    //----------------- Start generate signature ---------------
    //----- To detect the default currency setting in th backend


   $selected_curr = $order->info['currency'];
   $rate          = $order->info['currency_value'];
   $converted_amt = $amount * $rate;
    // ---- End of default currency detect ------------

    $HashAmount = str_replace(".","",str_replace(",","",$converted_amt));
		$str = sha1($merchantKey . $merchantCode . $refNo . $HashAmount);

		for ($i=0;$i<strlen($str);$i=$i+2)
    {
        $ipaySignature .= chr(hexdec(substr($str,$i,2)));
    }
     
      $ipaySignature = base64_encode($ipaySignature);
    //------------------ End generate signature ------------------

		$process_button_string .= "\n".tep_draw_hidden_field('ord_mercID', MODULE_PAYMENT_WEBCASH_CODE) ;
		$process_button_string .= "\n".tep_draw_hidden_field('PaymentId', $_REQUEST['PaymentId']) ;
		$process_button_string .= "\n".tep_draw_hidden_field('ord_mercref', $refNo) ;
		$process_button_string .= "\n".tep_draw_hidden_field('ord_totalamt', $converted_amt) ;
		$process_button_string .= "\n".tep_draw_hidden_field('ord_date', $today);
		$process_button_string .= "\n".tep_draw_hidden_field('ProdDesc', $items) ;
		$process_button_string .= "\n".tep_draw_hidden_field('ord_shipname', $order->billing['firstname']." ".$order->billing['lastname']) ;
		$process_button_string .= "\n".tep_draw_hidden_field('ord_email', $order->customer['email_address']) ;
		$process_button_string .= "\n".tep_draw_hidden_field('ord_telephone', $order->customer['telephone']) ;
		#$process_button_string .= "\n".tep_draw_hidden_field('Remark', $order->info['comments']) ;
		$process_button_string .= "\n".tep_draw_hidden_field('ord_shipcountry', tep_session_id()) ;    
		$process_button_string .= "\n".tep_draw_hidden_field('Signature', $ipaySignature);
    $process_button_string .= "\n".tep_draw_hidden_field('merchant_hashvalue', $str);

		$process_button_string .= "\n".tep_draw_hidden_field('ord_returnURL', 'http://localhost/oscommerce/catalog/epayment/response.php');

		return $process_button_string;
	}


    function mobsha($data) {
      return base64_encode($data);
    }

    function before_process() {
      return false;
    }

    function after_process() {
      return false;
    }

    function get_error() {
      global $HTTP_GET_VARS;

      $error = array('title' => 'Error',
                     'error' => stripslashes(urldecode($HTTP_GET_VARS['ErrDesc'])));

      return $error;
    }


    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_WEBCASH_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Webcash Module', 'MODULE_PAYMENT_WEBCASH_STATUS', 'True', 'Do you want to accept Webcash payments?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant Code', 'MODULE_PAYMENT_WEBCASH_CODE', '', 'The merchant code account number to use for the Webcash service', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant Key', 'MODULE_PAYMENT_WEBCASH_KEY', '', 'The key to use for the Webcash service', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_WEBCASH_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_WEBCASH_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_WEBCASH_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_WEBCASH_STATUS', 'MODULE_PAYMENT_WEBCASH_CODE', 'MODULE_PAYMENT_WEBCASH_KEY', 'MODULE_PAYMENT_WEBCASH_ZONE', 'MODULE_PAYMENT_WEBCASH_ORDER_STATUS_ID', 'MODULE_PAYMENT_WEBCASH_SORT_ORDER');
    }
  }
?>