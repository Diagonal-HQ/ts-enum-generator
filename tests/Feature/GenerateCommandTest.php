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
            '--destination' => 'tests/output'
        ])
        ->expectsOutput('Generating runtime-usable TypeScript enums...')
        ->expectsOutput('TypeScript enums generated successfully.')
        ->assertExitCode(0);

        // Check that files were generated
        $this->assertTrue(File::exists('tests/output/user-role.ts'));
        $this->assertTrue(File::exists('tests/output/status.ts'));
        $this->assertTrue(File::exists('tests/output/index.ts'));
    }

    #[Test]
    public function it_generates_correct_typescript_for_backed_enum()
    {
        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output'
        ]);

        $userRoleContent = File::get('tests/output/user-role.ts');
        
        // Check type definition
        $this->assertStringContainsString('export type UserRoleType = \'admin\' | \'user\' | \'moderator\' | \'guest\';', $userRoleContent);
        
        // Check runtime object
        $this->assertStringContainsString('export const UserRole = {', $userRoleContent);
        $this->assertStringContainsString('ADMIN: \'admin\' as const,', $userRoleContent);
        $this->assertStringContainsString('USER: \'user\' as const,', $userRoleContent);
        
        // Check utilities
        $this->assertStringContainsString('export const UserRoleUtils = {', $userRoleContent);
        $this->assertStringContainsString('isValid: (value: any): value is UserRoleType', $userRoleContent);
    }

    #[Test]
    public function it_generates_correct_typescript_for_pure_enum()
    {
        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output'
        ]);

        $statusContent = File::get('tests/output/status.ts');
        
        // Check type definition
        $this->assertStringContainsString('export type StatusType = \'PENDING\' | \'APPROVED\' | \'REJECTED\' | \'CANCELLED\';', $statusContent);
        
        // Check runtime object  
        $this->assertStringContainsString('PENDING: \'PENDING\' as const,', $statusContent);
        $this->assertStringContainsString('APPROVED: \'APPROVED\' as const,', $statusContent);
    }

    #[Test]
    public function it_generates_index_file_with_all_exports()
    {
        $this->artisan('ts-enums:generate', [
            '--source' => 'tests/fixtures/enums',
            '--destination' => 'tests/output'
        ]);

        $indexContent = File::get('tests/output/index.ts');

        $this->assertStringContainsString('export { default as UserRole, type UserRoleType, UserRoleUtils }', $indexContent);
        $this->assertStringContainsString('export { default as Status, type StatusType, StatusUtils }', $indexContent);
    }

    #[Test]
    public function itUsesTheConfiguredSourceAndDestinationIfNoneAreProvided()
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
        $this->assertTrue(File::exists('tests/output/somewhere/user-role.ts'));
        $this->assertTrue(File::exists('tests/output/somewhere/status.ts'));
        $this->assertTrue(File::exists('tests/output/somewhere/index.ts'));
    }
}
