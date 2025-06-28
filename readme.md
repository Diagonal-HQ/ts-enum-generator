# Laravel TypeScript Enum Generator

Generate **runtime-usable** TypeScript enums from PHP Laravel enums. Unlike traditional TypeScript enums which are only available at compile-time, this package generates TypeScript code that provides both type safety and runtime functionality.

## Features

- 🚀 **Runtime-usable**: Generated TypeScript provides both types and runtime objects
- 🔄 **Automatic Generation**: Scan your PHP enums and generate TypeScript equivalents
- 🛡️ **Type Safety**: Full TypeScript type support with union types
- 🧰 **Utility Functions**: Built-in validation, conversion, and enumeration utilities
- 📁 **Flexible Output**: Configurable naming conventions and output structure
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

2. Generate TypeScript enums:

```bash
php artisan ts-enums:generate
```

3. Use in your TypeScript/JavaScript code:

```typescript
import { UserRole, UserRoleType, UserRoleUtils } from './enums';

// Runtime object access
const adminRole = UserRole.ADMIN; // 'admin'

// Type safety
function setUserRole(role: UserRoleType) {
    // role is typed as 'admin' | 'user' | 'moderator'
}

// Runtime validation
const isValid = UserRoleUtils.isValid('admin'); // true
const allValues = UserRoleUtils.values; // ['admin', 'user', 'moderator']
```

## Generated Output

For a PHP enum like this:

```php
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
}
```

The package generates:

```typescript
// Auto-generated from PHP enum

export type UserRoleType = 'admin' | 'user' | 'moderator';

export const UserRole = {
  ADMIN: 'admin' as const,
  USER: 'user' as const,
  MODERATOR: 'moderator' as const,
} as const;

export const UserRoleUtils = {
  values: Object.values(UserRole),
  keys: Object.keys(UserRole) as Array<keyof typeof UserRole>,
  entries: Object.entries(UserRole) as Array<[keyof typeof UserRole, UserRoleType]>,
  isValid: (value: any): value is UserRoleType => Object.values(UserRole).includes(value),
  fromKey: (key: keyof typeof UserRole): UserRoleType => UserRole[key],
};

export default UserRole;
```

## Runtime Usage Examples

### Basic Usage

```typescript
import { UserRole, UserRoleType } from './enums';

// Compile-time type checking
function checkPermission(role: UserRoleType) {
    if (role === UserRole.ADMIN) {
        return 'full access';
    }
    return 'limited access';
}

// Runtime object usage
const currentRole = UserRole.USER; // 'user'
```

### Validation

```typescript
import { UserRoleUtils } from './enums';

// Validate user input
function processUserRole(input: unknown) {
    if (UserRoleUtils.isValid(input)) {
        // input is now typed as UserRoleType
        console.log(`Valid role: ${input}`);
    }
}

// API response validation
const apiResponse = await fetch('/api/user');
const userData = await apiResponse.json();

if (UserRoleUtils.isValid(userData.role)) {
    // Safe to use userData.role as UserRoleType
}
```

### Enumeration

```typescript
import { UserRoleUtils } from './enums';

// Get all possible values
const allRoles = UserRoleUtils.values; // ['admin', 'user', 'moderator']

// Get all keys
const allKeys = UserRoleUtils.keys; // ['ADMIN', 'USER', 'MODERATOR']

// Get key-value pairs
const entries = UserRoleUtils.entries; // [['ADMIN', 'admin'], ...]

// Convert from key to value
const roleValue = UserRoleUtils.fromKey('ADMIN'); // 'admin'
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

The `config/ts-enum-generator.php` file allows you to customize:

```php
return [
    'default_source_dir' => 'app/Enums',
    'default_destination_dir' => 'resources/ts/enums',
    
    'convention' => [
        'directories' => 'kebab', // kebab, snake, camel, studly, lower
        'files' => 'kebab',
    ],
    
    'output' => [
        'generate_index_file' => true,
        'generate_utils' => true,
        'use_const_assertions' => true,
        'include_comments' => true,
    ],
];
```

## Pure Enums Support

The package also supports pure (non-backed) enums:

```php
enum Status
{
    case PENDING;
    case APPROVED;
    case REJECTED;
}
```

Generates:

```typescript
export type StatusType = 'PENDING' | 'APPROVED' | 'REJECTED';

export const Status = {
  PENDING: 'PENDING' as const,
  APPROVED: 'APPROVED' as const,
  REJECTED: 'REJECTED' as const,
} as const;
```

## Differences from Traditional TypeScript Enums

Traditional TypeScript enums:
```typescript
enum UserRole {
    ADMIN = 'admin',
    USER = 'user'
}
// Limited runtime capabilities, can't validate arbitrary values
```

This package generates:
```typescript
// Full runtime object + type safety + utility functions
const UserRole = { ADMIN: 'admin', USER: 'user' } as const;
type UserRoleType = 'admin' | 'user';
// + validation, enumeration, and conversion utilities
```

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
