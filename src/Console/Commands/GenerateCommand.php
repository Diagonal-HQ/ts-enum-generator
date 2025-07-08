<?php

namespace Diagonal\TsEnumGenerator\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionEnum;
use Symfony\Component\Finder\SplFileInfo;

class GenerateCommand extends Command
{
    protected $signature = 'ts-enums:generate 
                           {--source= : Source directory containing PHP enums (supports glob patterns)}
                           {--destination= : Destination directory for TypeScript files}
                           {--watch : Watch for changes and regenerate automatically}';
    
    protected $description = 'Generate runtime-usable TypeScript enums from PHP enums';
    
    protected array $sourceDirectories;
    protected string $destinationDir;

    public function handle(): void
    {
        $this->setOptions();
        
        try {
            $this->info('Generating runtime-usable TypeScript enums...');
            $this->generate();
            $this->info('TypeScript enums generated successfully.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    private function setOptions(): void
    {
        $sourceDir = $this->option('source') ?? config('ts-enum-generator.default_source_dir');
        $this->destinationDir = $this->option('destination') ?? config('ts-enum-generator.default_destination_dir');
        
        // Handle glob patterns
        if (str_contains($sourceDir, '*')) {
            $this->sourceDirectories = glob($sourceDir, GLOB_ONLYDIR);
            if (empty($this->sourceDirectories)) {
                throw new Exception("No directories found matching pattern '{$sourceDir}'");
            }
        } else {
            $this->sourceDirectories = [$sourceDir];
        }
        
        // Convert relative destination path to absolute for proper resolution
        if (!str_starts_with($this->destinationDir, '/')) {
            $this->destinationDir = getcwd() . '/' . $this->destinationDir;
        }
        
        // Validate directories exist and convert to absolute paths
        foreach ($this->sourceDirectories as $index => $dir) {
            if (!str_starts_with($dir, '/')) {
                $dir = getcwd() . '/' . $dir;
                $this->sourceDirectories[$index] = $dir;
            }
            
            if (!is_dir($dir)) {
                throw new Exception("Source directory '{$dir}' does not exist.");
            }
        }
    }

    private function generate(): void
    {
        $generatedFiles = [];
        
        foreach ($this->sourceDirectories as $sourceDir) {
            $phpEnumFiles = File::allFiles($sourceDir);
            
            foreach ($phpEnumFiles as $phpEnumFile) {
                try {
                    if (!$this->isEnumFile($phpEnumFile)) {
                        continue;
                    }
                    
                    $tsEnumFilePath = $this->getTsEnumFilePathFrom($phpEnumFile, $sourceDir);
                    $tsEnumFileContent = $this->generateTsEnumContentFrom($phpEnumFile);
                    
                    if ($tsEnumFileContent) {
                        $this->createTsEnumFile($tsEnumFilePath, $tsEnumFileContent);
                        $generatedFiles[] = $tsEnumFilePath;
                        $this->line("Generated: {$tsEnumFilePath}");
                    }
                } catch (Exception $exception) {
                    $this->warn("Failed to process {$phpEnumFile->getFilename()}: {$exception->getMessage()}");
                }
            }
        }
        
        // Generate index file with all exports
        $this->generateIndexFile($generatedFiles);
    }

    private function isEnumFile(SplFileInfo $phpEnumFile): bool
    {
        $contents = File::get($phpEnumFile);
        return preg_match('/enum\s+\w+/', $contents);
    }

    private function getTsEnumFilePathFrom(SplFileInfo $phpEnumFile, string $sourceDir): string
    {
        $directoriesConvention = config('ts-enum-generator.convention.directories', 'kebab');
        $filesConvention = config('ts-enum-generator.convention.files', 'kebab');
        
        $dirPath = Str::of($phpEnumFile->getPath())
            ->replace('\\', '/')
            ->after($sourceDir)
            ->trim('/')
            ->explode('/')
            ->filter()
            ->map(fn ($directory) => Str::$directoriesConvention($directory))
            ->join('/');
            
        $fileName = Str::$filesConvention($phpEnumFile->getFilenameWithoutExtension());
        
        $fullPath = $dirPath ? "{$this->destinationDir}/{$dirPath}/{$fileName}.ts" : "{$this->destinationDir}/{$fileName}.ts";
        
        // Convert relative destination path to absolute if needed
        if (!str_starts_with($fullPath, '/')) {
            $fullPath = getcwd() . '/' . $fullPath;
        }
        
        return $fullPath;
    }

    private function generateTsEnumContentFrom(SplFileInfo $phpEnumFile): ?string
    {
        $phpEnumClassName = $this->getClassNameFrom($phpEnumFile);
        
        if (!$phpEnumClassName) {
            return null;
        }
        
        if (!enum_exists($phpEnumClassName)) {
            return null;
        }
        
        $enumName = (new ReflectionClass($phpEnumClassName))->getShortName();
        $enumCases = $phpEnumClassName::cases();
        
        return $this->generateRuntimeUsableEnum($enumName, $enumCases);
    }

    private function generateRuntimeUsableEnum(string $enumName, array $enumCases): string
    {
        $output = "// Auto-generated from PHP enum\n\n";
        
        // Generate the type definition
        $output .= "export type {$enumName}Type = ";
        $typeValues = [];
        
        foreach ($enumCases as $case) {
            if ($this->isBackedEnum($case)) {
                $value = $case->value;
                $typeValues[] = "'" . $value . "'";
            } else {
                $typeValues[] = "'" . $case->name . "'";
            }
        }
        
        $output .= implode(' | ', $typeValues) . ";\n\n";
        
        // Generate the runtime object
        $output .= "export const {$enumName} = {\n";
        
        foreach ($enumCases as $case) {
            if ($this->isBackedEnum($case)) {
                $value = $case->value;
                $output .= "  " . $case->name . ": '" . $value . "' as const,\n";
            } else {
                $name = $case->name;
                $output .= "  " . $name . ": '" . $name . "' as const,\n";
            }
        }
        
        $output .= "} as const;\n\n";
        
        // Generate utility functions
        $output .= "export const {$enumName}Utils = {\n";
        $output .= "  values: Object.values({$enumName}),\n";
        $output .= "  keys: Object.keys({$enumName}) as Array<keyof typeof {$enumName}>,\n";
        $output .= "  entries: Object.entries({$enumName}) as Array<[keyof typeof {$enumName}, {$enumName}Type]>,\n";
        $output .= "  isValid: (value: any): value is {$enumName}Type => Object.values({$enumName}).includes(value),\n";
        $output .= "  fromKey: (key: keyof typeof {$enumName}): {$enumName}Type => {$enumName}[key],\n";
        $output .= "};\n\n";
        
        // Generate default export
        $output .= "export default {$enumName};\n";
        
        return $output;
    }

    protected function getClassNameFrom(SplFileInfo $phpEnumFile): ?string
    {
        $contents = File::get($phpEnumFile);
        
        if (preg_match('/namespace\s+(.+?);/', $contents, $namespaceMatch) &&
            preg_match('/enum\s+(\w+)/', $contents, $enumMatch)) {
            return $namespaceMatch[1] . '\\' . $enumMatch[1];
        }
        
        return null;
    }

    private function isBackedEnum(object $caseObject): bool
    {
        return $caseObject instanceof \BackedEnum;
    }

    protected function createTsEnumFile(string $path, string $tsEnumContent): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $tsEnumContent);
    }
    
    private function generateIndexFile(array $generatedFiles): void
    {
        if (empty($generatedFiles)) {
            return;
        }
        
        $indexPath = $this->destinationDir . '/index.ts';
        
        // Convert relative destination path to absolute if needed
        if (!str_starts_with($indexPath, '/')) {
            $indexPath = getcwd() . '/' . $indexPath;
        }
        
        $indexContent = "// Auto-generated index file\n\n";
        
        foreach ($generatedFiles as $filePath) {
            $relativePath = Str::of($filePath)
                ->after(dirname($indexPath) . '/')
                ->beforeLast('.ts')
                ->replace('\\', '/');
                
            $enumName = Str::of($relativePath)->afterLast('/')->studly();

            $indexContent .= "export { default as {$enumName}, type {$enumName}Type, {$enumName}Utils } from './{$relativePath}';\n";
        }
        
        File::ensureDirectoryExists(dirname($indexPath));
        File::put($indexPath, $indexContent);
        
        $this->line("Generated index file: {$indexPath}");
    }
} 