<?php

namespace Diagonal\TsEnumGenerator\Tests\Feature;

use Diagonal\TsEnumGenerator\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class GenerateCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing output
        if (File::exists('tests/output')) {
            File::deleteDirectory('tests/output');
        }
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        if (File::exists('tests/output')) {
            File::deleteDirectory('tests/output');
        }

        parent::tearDown();
    }

    #[Test]
    public function it_can_generate_typescript_enums_from_php_enums()
    {
        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ])
            ->expectsOutput('Generating runtime-usable TypeScript enums...')
            ->expectsOutput('TypeScript enums generated successfully.')
            ->assertExitCode(0);

        // Check that the single enum file was generated
        $this->assertTrue(File::exists('tests/output/enums.ts'));
    }

    #[Test]
    public function it_generates_correct_typescript_with_namespaces()
    {
        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ]);

        $enumsContent = File::get('tests/output/enums.ts');

        // Check namespace declaration
        $this->assertStringContainsString('declare namespace Diagonal.TsEnumGenerator.Tests.Fixtures.Enums', $enumsContent);

        // Check UserRole type definition (no export in namespace)
        $this->assertStringContainsString('type UserRole = \'admin\' | \'user\' | \'moderator\' | \'guest\';', $enumsContent);

        // Check UserRole runtime object declaration
        $this->assertStringContainsString('const UserRole: {', $enumsContent);
        $this->assertStringContainsString('readonly ADMIN: \'admin\';', $enumsContent);

        // Check UserRole utilities declaration
        $this->assertStringContainsString('const UserRoleUtils: {', $enumsContent);
        $this->assertStringContainsString('isValid: (value: any) => value is UserRole;', $enumsContent);

        // Check Status type definition
        $this->assertStringContainsString('type Status = \'PENDING\' | \'APPROVED\' | \'REJECTED\' | \'CANCELLED\';', $enumsContent);

        // Check generic utilities declaration
        $this->assertStringContainsString('const EnumUtils: {', $enumsContent);
        $this->assertStringContainsString('isValid: <T extends Record<string, string>>', $enumsContent);
    }

    #[Test]
    public function it_generates_correct_typescript_with_es_modules()
    {
        config(['ts-enum-generator.output.use_namespaces' => false]);

        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ]);

        $enumsContent = File::get('tests/output/enums.ts');

        // Check that no namespace declaration is present
        $this->assertStringNotContainsString('declare namespace', $enumsContent);

        // Check UserRole type definition with export
        $this->assertStringContainsString('export type UserRole = \'admin\' | \'user\' | \'moderator\' | \'guest\';', $enumsContent);

        // Check UserRole runtime object with export
        $this->assertStringContainsString('export const UserRole = {', $enumsContent);
        $this->assertStringContainsString('ADMIN: \'admin\' as const,', $enumsContent);

        // Check UserRole utilities with export
        $this->assertStringContainsString('export const UserRoleUtils = {', $enumsContent);
        $this->assertStringContainsString('isValid: (value: any): value is UserRole', $enumsContent);

        // Check Status type definition with export
        $this->assertStringContainsString('export type Status = \'PENDING\' | \'APPROVED\' | \'REJECTED\' | \'CANCELLED\';', $enumsContent);

        // Check generic utilities with export
        $this->assertStringContainsString('export const EnumUtils = {', $enumsContent);
        $this->assertStringContainsString('isValid: <T extends Record<string, string>>(enumObject: T, value: any): value is T[keyof T]', $enumsContent);
    }

    #[Test]
    public function it_generates_only_types_when_types_only_is_enabled()
    {
        config(['ts-enum-generator.output.types_only' => true]);

        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ]);

        $enumsContent = File::get('tests/output/enums.ts');

        // Check type definitions are present (no export in namespace)
        $this->assertStringContainsString('type UserRole = \'admin\' | \'user\' | \'moderator\' | \'guest\';', $enumsContent);
        $this->assertStringContainsString('type Status = \'PENDING\' | \'APPROVED\' | \'REJECTED\' | \'CANCELLED\';', $enumsContent);

        // Check runtime objects are NOT present
        $this->assertStringNotContainsString('const UserRole: {', $enumsContent);
        $this->assertStringNotContainsString('const UserRoleUtils: {', $enumsContent);
        $this->assertStringNotContainsString('const EnumUtils: {', $enumsContent);
    }

    #[Test]
    public function it_can_disable_per_type_utilities()
    {
        config(['ts-enum-generator.output.generate_per_type_utils' => false]);

        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ]);

        $enumsContent = File::get('tests/output/enums.ts');

        // Check type definitions and runtime objects are present (no export in namespace)
        $this->assertStringContainsString('type UserRole = \'admin\' | \'user\' | \'moderator\' | \'guest\';', $enumsContent);
        $this->assertStringContainsString('const UserRole: {', $enumsContent);

        // Check per-type utilities are NOT present
        $this->assertStringNotContainsString('const UserRoleUtils: {', $enumsContent);

        // Check generic utilities are still present
        $this->assertStringContainsString('const EnumUtils: {', $enumsContent);
    }

    #[Test]
    public function it_can_disable_generic_utilities()
    {
        config(['ts-enum-generator.output.generate_generic_utils' => false]);

        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ]);

        $enumsContent = File::get('tests/output/enums.ts');

        // Check type definitions and runtime objects are present (no export in namespace)
        $this->assertStringContainsString('type UserRole = \'admin\' | \'user\' | \'moderator\' | \'guest\';', $enumsContent);
        $this->assertStringContainsString('const UserRole: {', $enumsContent);

        // Check per-type utilities are present
        $this->assertStringContainsString('const UserRoleUtils: {', $enumsContent);

        // Check generic utilities are NOT present
        $this->assertStringNotContainsString('const EnumUtils: {', $enumsContent);
    }

    #[Test]
    public function it_generates_correct_typescript_for_backed_enum()
    {
        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ]);

        $enumsContent = File::get('tests/output/enums.ts');

        // Check that the namespace contains the backed enum with all components (no export in namespace)
        $this->assertStringContainsString('type UserRole = \'admin\' | \'user\' | \'moderator\' | \'guest\';', $enumsContent);
        $this->assertStringContainsString('const UserRole: {', $enumsContent);
        $this->assertStringContainsString('readonly ADMIN: \'admin\';', $enumsContent);
        $this->assertStringContainsString('const UserRoleUtils: {', $enumsContent);
    }

    #[Test]
    public function it_generates_correct_typescript_for_pure_enum()
    {
        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ]);

        $enumsContent = File::get('tests/output/enums.ts');

        // Check that the namespace contains the pure enum with all components (no export in namespace)
        $this->assertStringContainsString('type Status = \'PENDING\' | \'APPROVED\' | \'REJECTED\' | \'CANCELLED\';', $enumsContent);
        $this->assertStringContainsString('const Status: {', $enumsContent);
        $this->assertStringContainsString('readonly PENDING: \'PENDING\';', $enumsContent);
        $this->assertStringContainsString('const StatusUtils: {', $enumsContent);
    }

    #[Test]
    public function it_generates_single_file_with_all_enums()
    {
        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ]);

        $enumsContent = File::get('tests/output/enums.ts');

        // Check that both enums are in the same namespace with all components (no export in namespace)
        $this->assertStringContainsString('declare namespace Diagonal.TsEnumGenerator.Tests.Fixtures.Enums', $enumsContent);
        $this->assertStringContainsString('type UserRole', $enumsContent);
        $this->assertStringContainsString('const UserRole: {', $enumsContent);
        $this->assertStringContainsString('const UserRoleUtils: {', $enumsContent);
        $this->assertStringContainsString('type Status', $enumsContent);
        $this->assertStringContainsString('const Status: {', $enumsContent);
        $this->assertStringContainsString('const StatusUtils: {', $enumsContent);
        $this->assertStringContainsString('const EnumUtils: {', $enumsContent);
    }

    #[Test]
    public function it_uses_config_defaults_when_no_options_provided()
    {
        // Set up config defaults
        config(['ts-enum-generator.default_source_dir' => 'tests/fixtures/enums']);
        config(['ts-enum-generator.default_destination_dir' => 'tests/output']);

        // Run command without options
        $this->artisan('ts-enums:generate')
            ->expectsOutput('Generating runtime-usable TypeScript enums...')
            ->expectsOutput('TypeScript enums generated successfully.')
            ->assertExitCode(0);

        // Check that the single enum file was generated using config defaults
        $this->assertTrue(File::exists('tests/output/enums.ts'));
    }

    #[Test]
    public function it_supports_glob_patterns_for_multiple_directories()
    {
        // Create multiple test directories
        File::makeDirectory('tests/fixtures/module1/enums', 0755, true);
        File::makeDirectory('tests/fixtures/module2/enums', 0755, true);

        // Copy existing enum files to multiple directories
        File::copy('tests/fixtures/enums/UserRole.php', 'tests/fixtures/module1/enums/UserRole.php');
        File::copy('tests/fixtures/enums/Status.php', 'tests/fixtures/module2/enums/Status.php');

        try {
            // Test glob pattern
            $this->artisan('ts-enums:generate', [
                '--source' => 'tests/fixtures/*/enums',
                '--destination' => 'tests/output',
            ])
                ->expectsOutput('Generating runtime-usable TypeScript enums...')
                ->expectsOutput('TypeScript enums generated successfully.')
                ->assertExitCode(0);

            // Check that the single enum file was generated from both directories
            $this->assertTrue(File::exists('tests/output/enums.ts'));
            $enumsContent = File::get('tests/output/enums.ts');

            // Check that both enums are present (no export in namespace)
            $this->assertStringContainsString('type UserRole', $enumsContent);
            $this->assertStringContainsString('type Status', $enumsContent);
        } finally {
            // Clean up test directories
            File::deleteDirectory('tests/fixtures/module1');
            File::deleteDirectory('tests/fixtures/module2');
        }
    }

    #[Test]
    public function it_uses_the_configured_source_and_destination_if_none_are_provided()
    {
        // Given
        Config::set('ts-enum-generator.default_source_dir', 'tests/fixtures/enums');
        Config::set('ts-enum-generator.default_destination_dir', 'tests/output/somewhere');

        // When
        $this->artisan('ts-enums:generate')
            ->expectsOutput('Generating runtime-usable TypeScript enums...')
            ->expectsOutput('TypeScript enums generated successfully.')
            ->assertExitCode(0);

        // Then
        $this->assertTrue(File::exists('tests/output/somewhere/enums.ts'));
    }

    #[Test]
    public function it_generates_multiple_files_when_single_file_is_disabled()
    {
        config(['ts-enum-generator.output.single_file' => false]);

        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output',
        ])
            ->expectsOutput('Generating runtime-usable TypeScript enums...')
            ->expectsOutput('TypeScript enums generated successfully.')
            ->assertExitCode(0);

        // Check that individual files were generated instead of a single file
        $this->assertFalse(File::exists('tests/output/enums.ts'));
        $this->assertTrue(File::exists('tests/output/UserRole.ts'));
        $this->assertTrue(File::exists('tests/output/Status.ts'));

        // Verify content of UserRole.ts
        $userRoleContent = File::get('tests/output/UserRole.ts');
        $this->assertStringContainsString('export type UserRole = \'admin\' | \'user\' | \'moderator\' | \'guest\';', $userRoleContent);
        $this->assertStringContainsString('export const UserRole = {', $userRoleContent);
        $this->assertStringContainsString('ADMIN: \'admin\' as const,', $userRoleContent);
        $this->assertStringContainsString('export const UserRoleUtils = {', $userRoleContent);

        // Verify content of Status.ts
        $statusContent = File::get('tests/output/Status.ts');
        $this->assertStringContainsString('export type Status = \'PENDING\' | \'APPROVED\' | \'REJECTED\' | \'CANCELLED\';', $statusContent);
        $this->assertStringContainsString('export const Status = {', $statusContent);
        $this->assertStringContainsString('PENDING: \'PENDING\' as const,', $statusContent);
        $this->assertStringContainsString('export const StatusUtils = {', $statusContent);
    }
}
