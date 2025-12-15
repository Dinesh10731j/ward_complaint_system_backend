<?php
require __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;

// Configure Cloudinary globally
Configuration::instance([
    'cloud' => [
        'cloud_name' => 'dztcsje3w',
        'api_key'    => '125537293924567',
        'api_secret' => 'Xg3S3mKspqSZSmYHBOkvF40rVcA',
    ],
    'url' => [
        'secure' => true
    ]
]);
