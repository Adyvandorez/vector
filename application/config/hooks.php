<?php
defined('BASEPATH') or exit('No direct script access allowed');

$hook['post_controller_constructor'][] = [
    'class'    => 'AuthHook',
    'function' => 'check',
    'filename' => 'AuthHook.php',
    'filepath' => 'hooks'
];
