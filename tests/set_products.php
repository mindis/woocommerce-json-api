<?php
require_once "functions.php";
include "config.php";
$Header("Writing Products");
$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_products',
  'arguments'   => array(
    'token' => $token,
    'per_page' => 2,
    'page'     => 1,
    'include' => array(
      'variations' => false,
      'images' => false,
      'featured_image' => false,
      'categories' => false,
      'tags' => false,
      'reviews' => false,
      'variations_in_products' => false,
    ),
  )
);


$result = curl_post($url,$data);
$products = json_decode($result,true);

$product = $products['payload'][0];

$old_price = $product['price'];
$nprice = $old_price * 2;

$oname = $product['name'];
$nname = $oname . " I edited it";

$product['name'] = $nname;
$product['price'] = $nprice;
$products['proc'] = 'set_products';
$products['payload'][0] = $product;


$result = curl_post($url,$products);
//echo $result;
// Now do the load a second time:

$result = curl_post($url,$data);


$products = json_decode($result,true);

$product = $products['payload'][0];
notEqual($oname, $product['name'], 'old name does not equal new name');
equal($nname, $product['name'],'new name is equal to product name');

notEqual(round($old_price,2), round($product['price'],2),"{$old_price} should not equal {$product['price']}");
equal(round($nprice,2), round($product['price'],2),'new price should equal product price');

$product['name'] = $oname;
$product['price'] = $old_price;




$products['proc'] = 'set_products';
$products['payload'][0] = $product;


$result = curl_post($url,$products);
$products = json_decode($result,true);
$product = $products['payload'][0];
equal($oname, $product['name'],'old name is equal to product name');
equal(round($old_price,2), round($product['price'],2),'old price should equal product price');


/*
* Now let's look at creating a completely new product.
*/

//Step 1, let's find out what the supported attributes are:

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'get_supported_attributes',
  'arguments'   => array(
    'token' => $token,
  )
);
$result = curl_post($url,$data);
$result = json_decode($result,true); 
equal($result['status'],true,'Get supported attributes?');
$supported_attributes = $result['payload'][0];
keyExists('Product',$supported_attributes,'Do we have attributes for Product?');

$r = rand(1,9999999);
$sr = rand(1,5);
$p =$sr * 1.25;

// This is the minimum info to get a sellable product
$new_product_data = array(
  'name' => "An API Created Product $r",
  'price' => $p,
  'sku' => "API$r",
  'visibility' => 'visible',
  'product_type' => 'simple',
  'type' => 'product',
  'status' => 'instock',
);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'set_products',
  'arguments'   => array(
    'token' => $token,
  ),
  'payload' => array($new_product_data),
);
$result = curl_post($url,$data);
$result = json_decode($result,true); 
equal($result['status'],true,'Get supported attributes?');
$product = $result['payload'][0];
keyExists('id',$product,'Was the id set?');


// Try uploading an image

$new_product_data = array(
  'name' => "An API Created Product $r",
  'price' => $p,
  'sku' => "API$r",
  'visibility' => 'visible',
  'product_type' => 'simple',
  'type' => 'product',
  'status' => 'instock',
  'images' => array(
    array('name' => 'fractal.png'),
  ),
  'featured_image' => array(
    array('name' => 'fractal3.png'),
  )
);

$data = array(
  'action'      => 'woocommerce_json_api',
  'proc'        => 'set_products',
  'arguments'   => array(
    'token' => $token,
  ),
  'payload' => array($new_product_data),
  'images[0]' => "@" . dirname(__FILE__) ."/fractal.png",
  'images[1]' => "@" . dirname(__FILE__) ."/fractal3.png",
);

$result = curl_post($url,$data);
$result = json_decode($result,true); 
$product = $result['payload'][0];
keyExists('id',$product['images'][0],'Was the id of the image set?');
