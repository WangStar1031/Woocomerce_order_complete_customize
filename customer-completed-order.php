<?php
/**
 * Customer completed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: Site title */ ?>
<p><?php printf( esc_html__( 'Your %s order has been marked complete on our side.', 'woocommerce' ), esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ) ); ?></p>
<?php

function getAllProducts(){
	$fName = $_SERVER['DOCUMENT_ROOT'] . "/token_manager/JRA/logs/products.json";
	if( file_exists($fName)){
		$contents = file_get_contents( $fName);
		return json_decode($contents);
	}
	return false;
}
function makeEncryptKey($_keyword){
	if( $_keyword == "")return "";
	$_key1 = crypt($_keyword . time(), "");
	$_key2 = ""; //crypt($_keyword, "");
	$_key3 = ""; //crypt(date("Ymd"), "");
	$key =  $_key1 . $_key2 . $_key3;
	$key = str_replace("$", "", $key);
	$key = str_replace(".", "", $key);
	$key = str_replace("/", "", $key);
	return $key;
}
function updateUser($_eMail, $_lstTokens){
	$fName = $_SERVER['DOCUMENT_ROOT'] . "/token_manager/JRA/logs/users/" . $_eMail;
	$contents = file_get_contents($fName);
	$userInfo = json_decode($contents);
	$arrTokens = $userInfo->arrTokens;
	$retVal = [];
	$products = getAllProducts();
	// print_r($products);
	foreach ($_lstTokens as $value) {
		$newVal = new \stdClass;
		$product_name = $value->product_name;
		$token = $value->token;
		$newVal->product_name = $product_name;
		$newVal->token = $token;
		$expPeriod = " + 6 month";
		if( $products == false){
		} else{
			foreach ($products as $product) {
				if( strcasecmp( $product->product_name, $product_name) == 0){
					$expPeriod = " + " . $product->expPeriod . " month";
				}
			}
		}
		$newVal->expDate = date("Y-m-d", strtotime(date("Y-m-d") . $expPeriod));
		$isInclude = false;
		foreach ($arrTokens as $curToken) {
			if( strcasecmp( $curToken->product_name, $product_name) == 0){
				// $newVal->expDate = date("Y-m-d", strtotime( $curToken->expDate . $expPeriod));
				$curToken->expDate = $newVal->expDate;
				$curToken->token = $newVal->token;
				$isInclude = true;
			}
		}
		if( $isInclude == false){
			$arrTokens[] = $newVal;
		}
		$retVal[] = $newVal;
	}
	file_put_contents($fName, json_encode($userInfo));
	return $retVal;
}
function registerUser( $_eMail, $_lstTokens){
	$fName = $_SERVER['DOCUMENT_ROOT'] . "/token_manager/JRA/logs/users/" . $_eMail;
	if( file_exists($fName)){
		return updateUser($_eMail, $_lstTokens);
	}
	$user = new \stdClass;
	$user->eMail = $_eMail;
	$arrTokens = [];
	$products = getAllProducts();
	foreach ($_lstTokens as $value) {
		$tokenPair = new \stdClass;
		$tokenPair->token = $value->token;
		$tokenPair->product_name = $value->product_name;
		$expPeriod = " + 6 month";
		if( $products == false){
		} else{
			foreach ($products as $product) {
				if( strcasecmp( $product->product_name, $value->product_name) == 0){
					$expPeriod = " + " . $product->expPeriod . " month";
				}
			}
		}
		$tokenPair->expDate = date("Y-m-d", strtotime(date("Y-m-d") . $expPeriod));
		$tokenPair->isLoged = "0";
		$arrTokens[] = $tokenPair;
	}
	$user->arrTokens = $arrTokens;
	file_put_contents($fName, json_encode($user));
	return $arrTokens;
}

$items = $order->get_items();
$lstProductNames = [];
$userMail = $order->billing_email;
$lstTokens = [];
foreach ( $items as $item ) {
    $product_name = $item->get_name();
    $product_id = $item->get_product_id();
    $product_variation_id = $item->get_variation_id();

    $lstProductNames[] = $product_name;
    $keyword = $product_name . $userMail;
    $token = makeEncryptKey($keyword);
    $newPair = new \stdClass;
    $newPair->product_name = $product_name;
    $newPair->token = $token;
    $lstTokens[] = $newPair;
}
$lstTokenDate = registerUser( $userMail, $lstTokens);

foreach ($lstTokenDate as $value) {
?>
	<p><?php printf( esc_html__("Product name is %s", 'woocommerce'), esc_html__($value->product_name)); ?></p>

	<p><?php printf( esc_html__("Your token code is %s", 'woocommerce'), esc_html__($value->token)); ?></p>

	<p><?php printf( esc_html__("Your token expiration date is %s.", 'woocommerce'), esc_html__($value->expDate)); ?></p>
<?php
}
?>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

?>
<p>
<?php esc_html_e( 'Thanks for shopping with us.', 'woocommerce' ); ?>
</p>
<?php

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
