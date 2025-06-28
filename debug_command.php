<?php

require_once 'vendor/autoload.php';

use Diagonal\TsEnumGenerator\Tests\Fixtures\Enums\UserRole;
use Diagonal\TsEnumGenerator\Tests\Fixtures\Enums\Status;
use ReflectionClass;
use ReflectionEnum;

// Test if we can load the enum classes
echo "Testing enum loading...\n";

try {
    $userRoleReflection = new ReflectionClass(UserRole::class);
    echo "✅ UserRole class loaded successfully\n";
    echo "Is enum: " . ($userRoleReflection->isEnum() ? 'Yes' : 'No') . "\n";
    
    if ($userRoleReflection->isEnum()) {
        $enumReflection = new ReflectionEnum(UserRole::class);
        $cases = $enumReflection->getCases();
        echo "Cases found: " . count($cases) . "\n";
        foreach ($cases as $case) {
            echo "- {$case->name}" . (property_exists($case, 'value') ? " = {$case->value}" : "") . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error loading UserRole: " . $e->getMessage() . "\n";
}

try {
    $statusReflection = new ReflectionClass(Status::class);
    echo "✅ Status class loaded successfully\n";
    echo "Is enum: " . ($statusReflection->isEnum() ? 'Yes' : 'No') . "\n";
    
    if ($statusReflection->isEnum()) {
        $enumReflection = new ReflectionEnum(Status::class);
        $cases = $enumReflection->getCases();
        echo "Cases found: " . count($cases) . "\n";
        foreach ($cases as $case) {
            echo "- {$case->name}" . (property_exists($case, 'value') ? " = {$case->value}" : "") . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error loading Status: " . $e->getMessage() . "\n";
}

// Test file discovery
echo "\nTesting file discovery...\n";
use Symfony\Component\Finder\Finder;

$finder = new Finder();
$files = $finder->files()->in('tests/fixtures/enums')->name('*.php');

foreach ($files as $file) {
    echo "Found file: " . $file->getPathname() . "\n";
    $contents = file_get_contents($file->getPathname());
    
    // Test the regex from the command
    if (preg_match('/enum\s+\w+/', $contents)) {
        echo "✅ File contains enum definition\n";
    } else {
        echo "❌ File does not match enum regex\n";
    }
    
    // Test class name extraction
    if (preg_match('/namespace\s+(.+?);/', $contents, $namespaceMatch) &&
        preg_match('/enum\s+(\w+)/', $contents, $enumMatch)) {
        $className = $namespaceMatch[1] . '\\' . $enumMatch[1];
        echo "Extracted class name: {$className}\n";
    } else {
        echo "❌ Could not extract class name\n";
    }
} 