<?php return [
    // Determines if language will be autodetected on request.
    // If this option are enabled it will use 'Accept-Language' http header
    // to lookup. 
    'autodetect_language' => true,
    // An array with package drivers.
    'drivers' => [
        // Db driver: Store/Retrieve data from Database.
        'db' => [
            'class' => Sluy\LaravelTranslation\Drivers\Common\DbDriver::class,
            'location' => 'translations', // Default table to work
            'connection' => null, // null for default connection
        ],
        // Php driver: Store/Retrieve data from default Laravel php location files.
        'php' => [
            'class' => Sluy\LaravelTranslation\Drivers\Common\PhpDriver::class,
            'location' => resource_path('lang'),
        ],
        // JS driver: Store/Retrieve data in Js Modules format. Common of VanillaJs/VueJS
        // apps works with this
        'js' => [
            'class' => Sluy\LaravelTranslation\Drivers\Common\JsDriver::class,
            'location' => storage_path('laravel_translation/lang/js'),
            // determines if will generate index.js files when store/delete items
            'generate_index' => true,
             // determines if npm will reload files when store/delete items
            'npm_reload' => true,
        ],
        // Json driver: Store/Retrieve data in JSON format. You can perform, for example,
        // remote fetches of translations.
        'json' => [
            'class' => Sluy\LaravelTranslation\Drivers\Common\JsonDriver::class,
            'location' => storage_path('laravel_translation/lang/json'),
        ],
        // Xml driver: Store/Retrieve data in XML format. Yes, we know, XML doesnt seems
        // like a "popular" format, but it have too!.
        'xml' => [
            'class' => Sluy\LaravelTranslation\Drivers\Common\XmlDriver::class,
            'location' => storage_path('laravel_translation/lang/xml'),
        ],
    ],
];
