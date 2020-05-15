<?PHP
/*
  $Id: webcash.php,v 1.2 2008/08/23

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2004 osCommerce

  Released under the GNU General Public License
*/

error_reporting(0);
require('../admin/includes/configure.php');


// Create connection
$conn = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if($conn){
	
	$merchantCode = $_REQUEST['ord_mercID'];
	$returncode = $_REQUEST['returncode'];
	
	
	$result = mysqli_query($conn, "select * from configuration where  configuration_key = 'MODULE_PAYMENT_WEBCASH_KEY'") ;
	$result = (mysqli_fetch_array($result));
	$merchantKey = $result['configuration_value'];
	
	$HashAmount = str_replace(".","",str_replace(",","",$_REQUEST['ord_totalamt']));
	$str = sha1($merchantKey . $merchantCode . $_REQUEST['ord_mercref'] . $HashAmount. $returncode);
	
	if($returncode == '100' && $_REQUEST['ord_key'] == $str) {
		header("Location: ../checkout_process.php?osCsid=" . $_REQUEST['ord_shipcountry']);
	} else {  
		header("Location: ../checkout_payment.php?osCsid=" . $_REQUEST['ord_shipcountry'] . "&payment_error=webcash&ErrDesc=Payment Incomplete. Please try again or call Kiplepay at 03-83188977 (office hour only)"); 
	}
	
}

?>