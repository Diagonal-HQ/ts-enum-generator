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
    | Naming Conventions
    |--------------------------------------------------------------------------
    |
    | Configure how directory and file names should be converted.
    | Available options: 'kebab', 'snake', 'camel', 'studly', 'lower'
    |
    */
    'convention' => [
        'directories' => 'kebab',
        'files' => 'kebab',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Options
    |--------------------------------------------------------------------------
    |
    | Configure the TypeScript output format and features.
    |
    */
    'output' => [
        'generate_index_file' => true,
        'generate_utils' => true,
        'use_const_assertions' => true,
        'include_comments' => true,
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