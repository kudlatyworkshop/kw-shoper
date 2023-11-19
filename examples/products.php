<?php

// Include the ShoperApi files
require_once __DIR__ . '/../src/KWShoper/ShoperApi.php';

// Include the ShoperApi class definition
use KWShoper\ShoperApi;

header('Content-type: text/plain; charset=utf-8');

// Replace these values with your Shoper credentials
$shopUrl = 'https://sklep.shoparena.pl/';
$clientId = '';
$clientSecret = '';

// Create an instance of the ShoperApi class
$shoperApi = new ShoperApi($shopUrl, $clientId, $clientSecret);

// ----------------
// Example requests
// ----------------

// POST - Create new product
$data = [
    'category_id' => 5,
    'code' => 'Code',
    'pkwiu' => '',
    'stock' => [
        'price' => 9.99,
    ],
    'translations' => [
        'pl_PL' => [
            'active' => 1,
            'name' => 'Product Name',
            'short_description' => 'Short Description',
        ],
    ],
];
$postResponse = $shoperApi->call('products', 'POST', $data);
$productId = $postResponse;
print_r($postResponse);

// PUT - Update product details
$updateData = [
    'stock' => [
        'price' => 19.99,
    ]
];
$putResponse = $shoperApi->call('products/' . $productId, 'PUT', $updateData);
print_r($putResponse);

// GET - Product details
$getResponse = $shoperApi->call('products/' . $productId);
print_r($getResponse);

// DELETE - Remove product
$deleteResponse = $shoperApi->call('products/' . $productId, 'DELETE');
print_r($deleteResponse);
