<?php
return array(
    'locale' => array(
        'lang' => 'english',
        'locale' => 'en_US',
    ),
	'info_collect' => true,
    'time_zone' => 'Asia/Tokyo',
    'encode' => array(
        'defalut' => 'UTF-8',
        'input' => 'UTF-8',
        'output' => 'UTF-8'
    ),
    'session' => array(
        'name' => 'session_name_you_want',
        'session_prefix' => 'session_prefix_you_want',
        'cookie_domain' => 'domain_of_your_website',
        'save_path' => 'save_path',
        'cookie_lifetime' => 3600,
        'gc_maxlifetime' => 3600,
        'gc_probability' => 1,
        'gc_divisor' => 100
    ),
    'crypt_key' => 'crypt_encode_key_you_want'
);
