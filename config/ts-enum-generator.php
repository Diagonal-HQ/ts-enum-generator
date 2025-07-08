<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Source Directory
    |--------------------------------------------------------------------------
    |
    | The default directory where PHP enums are located.
    | This can be overridden using the --source option.
    |
    */
    'default_source_dir' => 'app/Enums',

    /*
    |--------------------------------------------------------------------------
    | Default Destination Directory  
    |--------------------------------------------------------------------------
    |
    | The default directory where TypeScript enum files will be generated.
    | This can be overridden using the --destination option.
    |
    */
    'default_destination_dir' => 'resources/ts/enums',

    /*
    |--------------------------------------------------------------------------
    | Output Options
    |--------------------------------------------------------------------------
    |
    | Configure the TypeScript output format and features.
    |
    */
    'output' => [
        'use_namespaces' => true,
        'single_file' => true,
        'output_filename' => 'enums.ts',
        'namespace_separator' => '.',
        'include_comments' => true,
        'generate_runtime_objects' => true,
        'generate_per_type_utils' => true,
        'generate_generic_utils' => true,
        'types_only' => false, // If true, only generates type definitions
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how PHP namespaces are mapped to TypeScript namespaces.
    |
    */
    'namespace' => [
        'root_namespace' => null, // If null, uses the first namespace part
        'strip_namespace_prefix' => null, // Strip this prefix from PHP namespaces
        'namespace_suffix' => 'Enums', // Add this suffix to namespace
    ],

    /*
    |--------------------------------------------------------------------------
    | File Processing
    |--------------------------------------------------------------------------
    |
    | Configure how files are processed and filtered.
    |
    */
    'processing' => [
        'file_extensions' => ['php'],
        'exclude_patterns' => [
            '*/Tests/*',
            '*/tests/*',
            '*/vendor/*',
        ],
        'include_only_enums' => true,
    ],
]; 