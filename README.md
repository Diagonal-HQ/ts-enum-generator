# Generate TypeScript definitions from PHP enums

[![Latest Version on Packagist](https://img.shields.io/packagist/v/diagonal/ts-enum-generator.svg?style=flat-square)](https://packagist.org/packages/diagonal/ts-enum-generator)
[![Tests](https://img.shields.io/github/actions/workflow/status/diagonal/ts-enum-generator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/diagonal/ts-enum-generator/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/diagonal/ts-enum-generator.svg?style=flat-square)](https://packagist.org/packages/diagonal/ts-enum-generator)

This package can generate TypeScript definitions from your PHP enums. The generated TypeScript includes type definitions, runtime objects, and utility functions to make working with enums in your frontend applications a breeze.

## Installation

You can install the package via composer:

```bash
composer require diagonal/ts-enum-generator
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="ts-enum-generator-config"
```

This is the contents of the published config file:

```php
return [
    'default_source_dir' => 'app/Enums',
    'default_destination_dir' => 'resources/ts/enums',
    
    'output' => [
        'use_namespaces' => true,
        'single_file' => true,
        'output_filename' => 'enums.ts',
        'namespace_separator' => '.',
        'include_comments' => true,
        'generate_runtime_objects' => true,
        'generate_per_type_utils' => true,
        'generate_generic_utils' => true,
        'types_only' => false,
    ],
    
    'namespace' => [
        'root_namespace' => null,
        'strip_namespace_prefix' => null,
        'namespace_suffix' => 'Enums',
    ],
];
```

## Usage

Create PHP enums in your Laravel application:

```php
<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
}
```

```php
<?php

namespace App\Enums;

enum Status
{
    case PENDING;
    case APPROVED;
    case REJECTED;
}
```

Generate TypeScript definitions:

```bash
php artisan ts-enums:generate
```

This will create a TypeScript file with the following content:

```typescript
declare namespace App.Enums {
  type UserRole = 'admin' | 'user' | 'moderator';

  const UserRole: {
    readonly ADMIN: 'admin';
    readonly USER: 'user';
    readonly MODERATOR: 'moderator';
  };

  const UserRoleUtils: {
    values: UserRole[];
    keys: (keyof typeof UserRole)[];
    entries: [keyof typeof UserRole, UserRole][];
    isValid: (value: any) => value is UserRole;
    fromKey: (key: keyof typeof UserRole) => UserRole;
  };

  type Status = 'PENDING' | 'APPROVED' | 'REJECTED';

  const Status: {
    readonly PENDING: 'PENDING';
    readonly APPROVED: 'APPROVED';
    readonly REJECTED: 'REJECTED';
  };

  const StatusUtils: {
    values: Status[];
    keys: (keyof typeof Status)[];
    entries: [keyof typeof Status, Status][];
    isValid: (value: any) => value is Status;
    fromKey: (key: keyof typeof Status) => Status;
  };
}
```

Use the generated TypeScript in your frontend:

```typescript
// Type checking
function setUserRole(role: App.Enums.UserRole) {
    // TypeScript knows role can only be 'admin' | 'user' | 'moderator'
}

// Runtime usage
const adminRole = App.Enums.UserRole.ADMIN; // 'admin'

// Validation
if (App.Enums.UserRoleUtils.isValid(someValue)) {
    // someValue is now typed as UserRole
}

// Get all values
const allRoles = App.Enums.UserRoleUtils.values; // ['admin', 'user', 'moderator']
```

### Command options

The `ts-enums:generate` command accepts the following options:

```bash
php artisan ts-enums:generate --source=app/Models/Enums --destination=resources/js/types
```

- `--source`: Override the source directory (default: configured in config file)
- `--destination`: Override the destination directory (default: configured in config file)

### Configuration options

**Output format**

Control how the TypeScript is generated:

```php
'output' => [
    'use_namespaces' => false, // Use export syntax instead of declare namespace
    'single_file' => false,    // Generate separate files for each enum
    'types_only' => true,      // Only generate type definitions (no runtime objects)
],
```

**Namespace handling**

Customize how PHP namespaces are converted to TypeScript:

```php
'namespace' => [
    'strip_namespace_prefix' => 'App\\',   // Remove App\ from namespaces
    'namespace_suffix' => 'Types',         // Add Types suffix
],
```

With these settings, `App\Enums\UserRole` becomes `Enums.Types.UserRole`.

**Runtime utilities**

Choose what utility functions to generate:

```php
'output' => [
    'generate_runtime_objects' => true,   // Generate const objects (UserRole.ADMIN)
    'generate_per_type_utils' => true,    // Generate UserRoleUtils.isValid()
    'generate_generic_utils' => true,     // Generate EnumUtils.isValid()
],
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Branick Weix](https://github.com/bdweix)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
