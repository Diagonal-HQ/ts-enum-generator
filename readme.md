# TypeScript Enum Generator for Laravel

Generate **runtime-usable** TypeScript enums from PHP Laravel enums with **namespace organization**. Unlike traditional TypeScript enums which are only available at compile-time, this package generates TypeScript code that provides both type safety and runtime functionality in organized namespaces.

## Features

- 🚀 **Runtime-usable**: Generated TypeScript provides both types and runtime objects
- 🔄 **Automatic Generation**: Scan your PHP enums and generate TypeScript equivalents
- 🛡️ **Type Safety**: Full TypeScript type support with union types
- 🧰 **Utility Functions**: Built-in validation, conversion, and enumeration utilities
- 📁 **Namespace Organization**: Groups enums by PHP namespace into TypeScript namespaces
- 🎯 **Single File Output**: All enums in one organized TypeScript file
- 🔧 **Configurable**: Choose between type-only, runtime objects, per-type utils, or generic utils
- 🔍 **Smart Detection**: Automatically detects backed vs pure enums
- 📦 **Laravel Integration**: Seamless Laravel artisan command integration

## Installation

Install the package via Composer:

```bash
composer require diagonal/laravel-ts-enum-generator
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ts-enum-generator-config
```

## Quick Start

1. Create PHP enums in your Laravel project:

```php
<?php
// app/Enums/UserRole.php
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
// app/Enums/Status.php
namespace App\Enums;

enum Status
{
    case PENDING;
    case APPROVED;
    case REJECTED;
}
```

2. Generate TypeScript enums:

```bash
php artisan ts-enums:generate
```

3. Use in your TypeScript/JavaScript code:

```typescript
// Types for compile-time checking
function setUserRole(role: App.Enums.UserRole) {
    // role is typed as 'admin' | 'user' | 'moderator'
}

// Runtime object access
const adminRole = App.Enums.UserRole.ADMIN; // 'admin'

// Per-type utilities
const isValid = App.Enums.UserRoleUtils.isValid('admin'); // true
const allValues = App.Enums.UserRoleUtils.values; // ['admin', 'user', 'moderator']

// Generic utilities (work with any enum)
const isValidGeneric = App.Enums.EnumUtils.isValid(App.Enums.UserRole, 'admin'); // true
```

## Generated Output

For PHP enums like this:

```php
namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
}

enum Status
{
    case PENDING;
    case APPROVED;
    case REJECTED;
}
```

The package generates a single `enums.ts` file:

```typescript
// Auto-generated TypeScript enums from PHP enums

declare namespace App.Enums {
export type UserRole = 'admin' | 'user' | 'moderator';

export const UserRole = {
  ADMIN: 'admin' as const,
  USER: 'user' as const,
  MODERATOR: 'moderator' as const,
} as const;

export const UserRoleUtils = {
  values: Object.values(UserRole),
  keys: Object.keys(UserRole) as Array<keyof typeof UserRole>,
  entries: Object.entries(UserRole) as Array<[keyof typeof UserRole, UserRole]>,
  isValid: (value: any): value is UserRole => Object.values(UserRole).includes(value),
  fromKey: (key: keyof typeof UserRole): UserRole => UserRole[key],
};

export type Status = 'PENDING' | 'APPROVED' | 'REJECTED';

export const Status = {
  PENDING: 'PENDING' as const,
  APPROVED: 'APPROVED' as const,
  REJECTED: 'REJECTED' as const,
} as const;

export const StatusUtils = {
  values: Object.values(Status),
  keys: Object.keys(Status) as Array<keyof typeof Status>,
  entries: Object.entries(Status) as Array<[keyof typeof Status, Status]>,
  isValid: (value: any): value is Status => Object.values(Status).includes(value),
  fromKey: (key: keyof typeof Status): Status => Status[key],
};

export const EnumUtils = {
  isValid: <T extends Record<string, string>>(enumObject: T, value: any): value is T[keyof T] => {
    return Object.values(enumObject).includes(value);
  },
  values: <T extends Record<string, string>>(enumObject: T): T[keyof T][] => {
    return Object.values(enumObject);
  },
  keys: <T extends Record<string, string>>(enumObject: T): (keyof T)[] => {
    return Object.keys(enumObject) as (keyof T)[];
  },
  entries: <T extends Record<string, string>>(enumObject: T): [keyof T, T[keyof T]][] => {
    return Object.entries(enumObject) as [keyof T, T[keyof T]][];
  },
  fromKey: <T extends Record<string, string>>(enumObject: T, key: keyof T): T[keyof T] => {
    return enumObject[key];
  },
};
}
```

## Runtime Usage Examples

### Basic Usage

```typescript
// Type-safe function parameters
function checkPermission(role: App.Enums.UserRole) {
    if (role === App.Enums.UserRole.ADMIN) {
        return 'full access';
    }
    return 'limited access';
}

// Runtime object usage
const currentRole = App.Enums.UserRole.USER; // 'user'
```

### Validation with Per-Type Utilities

```typescript
// Validate user input with strong typing
function processUserRole(input: unknown) {
    if (App.Enums.UserRoleUtils.isValid(input)) {
        // input is now typed as App.Enums.UserRole
        console.log(`Valid role: ${input}`);
    }
}

// API response validation
const apiResponse = await fetch('/api/user');
const userData = await apiResponse.json();

if (App.Enums.UserRoleUtils.isValid(userData.role)) {
    // Safe to use userData.role as App.Enums.UserRole
}
```

### Generic Utilities

```typescript
// Generic utilities work with any enum
const roles = App.Enums.EnumUtils.values(App.Enums.UserRole); // ['admin', 'user', 'moderator']
const statuses = App.Enums.EnumUtils.values(App.Enums.Status); // ['PENDING', 'APPROVED', 'REJECTED']

// Generic validation
const isValidRole = App.Enums.EnumUtils.isValid(App.Enums.UserRole, 'admin'); // true
const isValidStatus = App.Enums.EnumUtils.isValid(App.Enums.Status, 'PENDING'); // true
```

### Enumeration

```typescript
// Get all possible values
const allRoles = App.Enums.UserRoleUtils.values; // ['admin', 'user', 'moderator']

// Get all keys
const allKeys = App.Enums.UserRoleUtils.keys; // ['ADMIN', 'USER', 'MODERATOR']

// Get key-value pairs
const entries = App.Enums.UserRoleUtils.entries; // [['ADMIN', 'admin'], ...]

// Convert from key to value
const roleValue = App.Enums.UserRoleUtils.fromKey('ADMIN'); // 'admin'
```

## Command Options

```bash
php artisan ts-enums:generate [options]
```

### Options

- `--source=PATH`: Source directory containing PHP enums (default: `app/Enums`)
- `--destination=PATH`: Destination directory for TypeScript files (default: `resources/ts/enums`)

### Examples

```bash
# Generate from custom source directory
php artisan ts-enums:generate --source=app/Models/Enums

# Generate to custom destination
php artisan ts-enums:generate --destination=resources/js/types/enums

# Custom source and destination
php artisan ts-enums:generate --source=app/Enums --destination=frontend/src/types
```

## Configuration

The `config/ts-enum-generator.php` file provides extensive customization options:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Source Directory
    |--------------------------------------------------------------------------
    */
    'default_source_dir' => 'app/Enums',

    /*
    |--------------------------------------------------------------------------
    | Default Destination Directory  
    |--------------------------------------------------------------------------
    */
    'default_destination_dir' => 'resources/ts/enums',

    /*
    |--------------------------------------------------------------------------
    | Output Options
    |--------------------------------------------------------------------------
    */
    'output' => [
        'use_namespaces' => true,           // Use declare namespace syntax
        'single_file' => true,              // Generate single file vs multiple files
        'output_filename' => 'enums.ts',    // Name of the generated file
        'namespace_separator' => '.',       // Separator for namespace parts
        'include_comments' => true,         // Include auto-generated comments
        'generate_runtime_objects' => true, // Generate const objects
        'generate_per_type_utils' => true,  // Generate XxxUtils for each enum
        'generate_generic_utils' => true,   // Generate generic EnumUtils
        'types_only' => false,              // Only generate type definitions
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace Configuration
    |--------------------------------------------------------------------------
    */
    'namespace' => [
        'root_namespace' => null,           // If null, uses the first namespace part
        'strip_namespace_prefix' => null,   // Strip this prefix from PHP namespaces
        'namespace_suffix' => 'Enums',      // Add this suffix to namespace
    ],

    /*
    |--------------------------------------------------------------------------
    | File Processing
    |--------------------------------------------------------------------------
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
```

### Configuration Options Explained

#### Output Options

- **`use_namespaces`**: When `true`, generates `declare namespace` syntax. When `false`, generates traditional export syntax.
- **`single_file`**: When `true`, all enums go into one file. When `false`, each enum gets its own file.
- **`output_filename`**: Name of the generated file when `single_file` is `true`.
- **`namespace_separator`**: Character used to separate namespace parts (e.g., `App.Enums` vs `App_Enums`).
- **`include_comments`**: Whether to include auto-generated comments in the output.
- **`generate_runtime_objects`**: Whether to generate `const` objects for runtime usage.
- **`generate_per_type_utils`**: Whether to generate `XxxUtils` objects for each enum type.
- **`generate_generic_utils`**: Whether to generate generic `EnumUtils` that work with any enum.
- **`types_only`**: When `true`, only generates type definitions (like `.d.ts` files).

#### Namespace Configuration

- **`root_namespace`**: Override the root namespace. If `null`, uses the first part of the PHP namespace.
- **`strip_namespace_prefix`**: Remove this prefix from PHP namespaces (e.g., `App\\` becomes empty).
- **`namespace_suffix`**: Add this suffix to the end of namespaces (e.g., `App` becomes `App.Enums`).

#### Common Configuration Examples

**Types Only (like .d.ts files):**
```php
'output' => [
    'types_only' => true,
    'generate_runtime_objects' => false,
    'generate_per_type_utils' => false,
    'generate_generic_utils' => false,
],
```

**Runtime Objects Only (no utilities):**
```php
'output' => [
    'generate_runtime_objects' => true,
    'generate_per_type_utils' => false,
    'generate_generic_utils' => false,
],
```

**Generic Utils Only:**
```php
'output' => [
    'generate_per_type_utils' => false,
    'generate_generic_utils' => true,
],
```

**Custom Namespace Structure:**
```php
'namespace' => [
    'strip_namespace_prefix' => 'App\\',
    'namespace_suffix' => 'Types',
],
// App\Enums\UserRole becomes Enums.Types.UserRole
```

## Advanced Features

### Multiple Source Directories

Use glob patterns to scan multiple directories:

```bash
php artisan ts-enums:generate --source="app/*/Enums"
```

This will scan `app/Models/Enums`, `app/Services/Enums`, etc.

### Namespace Mapping

Control how PHP namespaces map to TypeScript namespaces:

```php
// PHP: App\Admin\Enums\UserRole
// Default TS: App.Admin.Enums.UserRole

// With strip_namespace_prefix = 'App\\'
// Result TS: Admin.Enums.UserRole

// With namespace_suffix = 'Types'
// Result TS: Admin.Enums.Types.UserRole
```

### Enum Type Usage

The generated types work seamlessly with TypeScript:

```typescript
// Function parameters
function updateStatus(status: App.Enums.Status) { }

// Object properties
interface User {
    role: App.Enums.UserRole;
    status: App.Enums.Status;
}

// Union with other types
type UserAction = App.Enums.UserRole | 'guest';

// Generic constraints
function processEnum<T extends App.Enums.UserRole>(value: T): T {
    return value;
}
```

## Gotchas and Troubleshooting

### 1. Namespace Conflicts

**Problem:** TypeScript namespace conflicts when using multiple enum sources.

**Solution:** Use `strip_namespace_prefix` to avoid deeply nested namespaces:

```php
'namespace' => [
    'strip_namespace_prefix' => 'App\\',
],
```

### 2. Generic Utils vs Per-Type Utils

**Problem:** Choosing between generic utilities and per-type utilities.

**Per-Type Utils (Better IntelliSense):**
```typescript
App.Enums.UserRoleUtils.isValid('admin'); // Strong typing, better autocomplete
```

**Generic Utils (DRY, Less Code):**
```typescript
App.Enums.EnumUtils.isValid(App.Enums.UserRole, 'admin'); // More flexible, less generated code
```

**Recommendation:** Use both for maximum flexibility, or disable one if you prefer a specific approach.

### 3. Large Enum Files

**Problem:** Generated file becomes very large with many enums.

**Solutions:**
- Set `generate_per_type_utils` to `false` and use only generic utils
- Set `types_only` to `true` if you don't need runtime functionality
- Use multiple source directories with different configurations

### 4. Import Statements

**Problem:** How to import the generated types and objects.

**Solution:** Import from the generated file:

```typescript
// For types
import type { UserRole, Status } from './path/to/enums';

// For runtime objects and utilities
import { UserRole, UserRoleUtils, EnumUtils } from './path/to/enums';
```

### 5. Enum Value Escaping

**Problem:** Enum values with special characters.

**Solution:** The generator automatically escapes special characters:

```php
enum SpecialChars: string
{
    case QUOTE = "It's working";
    case BACKSLASH = "path\\to\\file";
}
```

Becomes:
```typescript
export const SpecialChars = {
  QUOTE: 'It\'s working' as const,
  BACKSLASH: 'path\\to\\file' as const,
} as const;
```

### 6. Build Integration

**Problem:** Integrating with build tools.

**Solution:** Add the generation command to your build process:

```json
{
  "scripts": {
    "build": "php artisan ts-enums:generate && npm run build"
  }
}
```

## Pure Enums Support

The package supports both backed and pure enums:

```php
// Backed enum
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
}

// Pure enum
enum Status
{
    case PENDING;
    case APPROVED;
}
```

Both generate identical TypeScript structures, with pure enums using the case name as the value.

## Migration from Separate Files

If you're upgrading from a version that generated separate files, the new namespace approach offers several advantages:

1. **Single Import:** Import everything from one file
2. **Namespace Organization:** Clear separation by PHP namespace
3. **Reduced File Count:** Easier to manage and deploy
4. **Better IntelliSense:** Organized autocomplete experience

To migrate, simply run the generator again. The old files can be safely deleted.

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for information.
