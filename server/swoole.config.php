<?php
/**
 * Created by Yuankui
 * Date: 2018/12/26
 * Time: 14:16
 */

return [
    'host' => '0.0.0.0',
    'port' => 9501,
    'setting' => [
        'enable_static_handler' => true,
        'document_root' => "/usr/share/nginx/html/Biny/web/static",
        'worker_num' => 5,
    ]
];