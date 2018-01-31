<?php

return [

    'driver'            => 'oss',

    // Aliyun OSS AccessKeyId
    'access_id'         => '',

    // Aliyun OSS AccessKeySecret
    'access_key'        => '',

    // OSS bucket name
    'bucket'            => '',

    // OSS extranet node or custom external domain name
    'endpoint'          => '',

    // OSS intranet node
    'endpoint_internal' => null,

    // path prefix
    'prefix'            => null,

    // Custom domain name binding
    // You need to bind the custom domain name CNAME to the OSS bucket
    'domain'            => null,

    // true to use 'https://' and false to use 'http://'
    'ssl'               => false,

    // Whether to open Debug mode
    'debug'             => env('APP_DEBUG', false),
];