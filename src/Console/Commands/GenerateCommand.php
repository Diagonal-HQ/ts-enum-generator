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
    protected array $collectedEnums = [];

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
        $this->collectedEnums = [];
        
        foreach ($this->sourceDirectories as $sourceDir) {
            $phpEnumFiles = File::allFiles($sourceDir);
            
            foreach ($phpEnumFiles as $phpEnumFile) {
                try {
                    if (!$this->isEnumFile($phpEnumFile)) {
                        continue;
                    }
                    
                    $enumData = $this->extractEnumData($phpEnumFile);
                    
                    if ($enumData) {
                        $this->collectedEnums[] = $enumData;
                        $this->line("Processed: {$enumData['name']}");
                    }
                } catch (Exception $exception) {
                    $this->warn("Failed to process {$phpEnumFile->getFilename()}: {$exception->getMessage()}");
                }
            }
        }
        
        // Generate TypeScript files based on configuration
        if (config('ts-enum-generator.output.single_file', true)) {
            $this->generateSingleTypeScriptFile();
        } else {
            $this->generateMultipleTypeScriptFiles();
        }
    }

    private function isEnumFile(SplFileInfo $phpEnumFile): bool
    {
        $contents = File::get($phpEnumFile);
        return preg_match('/enum\s+\w+/', $contents);
    }

    private function extractEnumData(SplFileInfo $phpEnumFile): ?array
    {
        $phpEnumClassName = $this->getClassNameFrom($phpEnumFile);
        
        if (!$phpEnumClassName) {
            return null;
        }
        
        if (!enum_exists($phpEnumClassName)) {
            return null;
        }
        
        $reflection = new ReflectionClass($phpEnumClassName);
        $enumName = $reflection->getShortName();
        $enumCases = $phpEnumClassName::cases();
        $namespace = $this->extractNamespace($reflection->getNamespaceName());
        
        return [
            'name' => $enumName,
            'namespace' => $namespace,
            'cases' => $enumCases,
            'full_class_name' => $phpEnumClassName,
        ];
    }

    private function extractNamespace(string $phpNamespace): string
    {
        $config = config('ts-enum-generator.namespace');
        
        // Strip prefix if configured
        if ($config['strip_namespace_prefix']) {
            $phpNamespace = Str::after($phpNamespace, $config['strip_namespace_prefix']);
        }
        
        // Convert namespace separators
        $tsNamespace = str_replace('\\', config('ts-enum-generator.output.namespace_separator'), $phpNamespace);
        
        // Add suffix if configured and not already present
        if ($config['namespace_suffix'] && !Str::endsWith($tsNamespace, $config['namespace_suffix'])) {
            $tsNamespace = $tsNamespace . config('ts-enum-generator.output.namespace_separator') . $config['namespace_suffix'];
        }
        
        return $tsNamespace;
    }

    private function generateSingleTypeScriptFile(): void
    {
        if (empty($this->collectedEnums)) {
            return;
        }
        
        $filename = config('ts-enum-generator.output.output_filename', 'enums.ts');
        $outputPath = $this->destinationDir . '/' . $filename;
        
        // Convert relative destination path to absolute if needed
        if (!str_starts_with($outputPath, '/')) {
            $outputPath = getcwd() . '/' . $outputPath;
        }
        
        // Check if we should use namespaces or ES modules
        if (config('ts-enum-generator.output.use_namespaces', true)) {
            $content = $this->generateNamespacedContent();
        } else {
            $content = $this->generateESModuleContent();
        }
        
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $content);
        
        $this->line("Generated: {$outputPath}");
    }

    private function generateMultipleTypeScriptFiles(): void
    {
        if (empty($this->collectedEnums)) {
            return;
        }
        
        File::ensureDirectoryExists($this->destinationDir);
        
        foreach ($this->collectedEnums as $enumData) {
            $filename = $enumData['name'] . '.ts';
            $outputPath = $this->destinationDir . '/' . $filename;
            
            // Convert relative destination path to absolute if needed
            if (!str_starts_with($outputPath, '/')) {
                $outputPath = getcwd() . '/' . $outputPath;
            }
            
            $content = $this->generateIndividualFileContent($enumData);
            
            File::put($outputPath, $content);
            $this->line("Generated: {$outputPath}");
        }
    }

    private function generateIndividualFileContent(array $enumData): string
    {
        $output = '';
        
        if (config('ts-enum-generator.output.include_comments')) {
            $output .= "// Auto-generated TypeScript enum from PHP enum\n\n";
        }
        
        // Individual files should use unprefixed names since each enum is in its own file
        $output .= $this->generateIndividualEnumDefinition($enumData);
        
        return $output;
    }

    private function generateIndividualEnumDefinition(array $enumData): string
    {
        $enumName = $enumData['name'];
        $cases = $enumData['cases'];
        $output = '';
        
        // Generate the type definition
        $typeValues = [];
        foreach ($cases as $case) {
            if ($this->isBackedEnum($case)) {
                $value = $case->value;
                $typeValues[] = "'" . addslashes($value) . "'";
            } else {
                $typeValues[] = "'" . $case->name . "'";
            }
        }
        
        $output .= "export type {$enumName} = " . implode(' | ', $typeValues) . ";\n";
        
        // Generate runtime objects if enabled
        if (config('ts-enum-generator.output.generate_runtime_objects') && 
            !config('ts-enum-generator.output.types_only')) {
            $output .= $this->generateIndividualRuntimeObject($enumData);
        }
        
        // Generate per-type utilities if enabled
        if (config('ts-enum-generator.output.generate_per_type_utils') && 
            config('ts-enum-generator.output.generate_runtime_objects') &&
            !config('ts-enum-generator.output.types_only')) {
            $output .= $this->generateIndividualPerTypeUtilities($enumData);
        }
        
        $output .= "\n";
        
        return $output;
    }

    private function generateIndividualRuntimeObject(array $enumData): string
    {
        $enumName = $enumData['name'];
        $cases = $enumData['cases'];
        
        $output = "\nexport const {$enumName} = {\n";
        
        foreach ($cases as $case) {
            if ($this->isBackedEnum($case)) {
                $value = $case->value;
                $output .= "  " . $case->name . ": '" . addslashes($value) . "' as const,\n";
            } else {
                $name = $case->name;
                $output .= "  " . $name . ": '" . $name . "' as const,\n";
            }
        }
        
        $output .= "} as const;\n";
        
        return $output;
    }

    private function generateIndividualPerTypeUtilities(array $enumData): string
    {
        $enumName = $enumData['name'];
        
        $output = "\nexport const {$enumName}Utils = {\n";
        $output .= "  values: Object.values({$enumName}),\n";
        $output .= "  keys: Object.keys({$enumName}) as Array<keyof typeof {$enumName}>,\n";
        $output .= "  entries: Object.entries({$enumName}) as Array<[keyof typeof {$enumName}, {$enumName}]>,\n";
        $output .= "  isValid: (value: any): value is {$enumName} => Object.values({$enumName}).includes(value),\n";
        $output .= "  fromKey: (key: keyof typeof {$enumName}): {$enumName} => {$enumName}[key],\n";
        $output .= "};\n";
        
        return $output;
    }

    private function generateNamespacedContent(): string
    {
        $groupedEnums = $this->groupEnumsByNamespace();
        $output = '';
        
        if (config('ts-enum-generator.output.include_comments')) {
            $output .= "// Auto-generated TypeScript enums from PHP enums\n\n";
        }
        
        foreach ($groupedEnums as $namespace => $enums) {
            $output .= "declare namespace {$namespace} {\n";
            
            foreach ($enums as $enumData) {
                $output .= $this->generateNamespaceEnumDefinition($enumData);
            }
            
            // Generate generic utilities if enabled
            if (config('ts-enum-generator.output.generate_generic_utils') && 
                config('ts-enum-generator.output.generate_runtime_objects') &&
                !config('ts-enum-generator.output.types_only')) {
                $output .= $this->generateNamespaceGenericUtilities($enums);
            }
            
            $output .= "}\n";
        }
        
        return $output;
    }

    private function generateESModuleContent(): string
    {
        $output = '';
        
        if (config('ts-enum-generator.output.include_comments')) {
            $output .= "// Auto-generated TypeScript enums from PHP enums\n\n";
        }
        
        foreach ($this->collectedEnums as $enumData) {
            $output .= $this->generateESModuleEnumDefinition($enumData);
        }
        
        // Generate generic utilities if enabled
        if (config('ts-enum-generator.output.generate_generic_utils') && 
            config('ts-enum-generator.output.generate_runtime_objects') &&
            !config('ts-enum-generator.output.types_only')) {
            $output .= $this->generateESModuleGenericUtilities();
        }
        
        return $output;
    }

    private function generateNamespaceEnumDefinition(array $enumData): string
    {
        $enumName = $enumData['name'];
        $cases = $enumData['cases'];
        $output = '';
        
        // Generate the type definition
        $typeValues = [];
        foreach ($cases as $case) {
            if ($this->isBackedEnum($case)) {
                $value = $case->value;
                $typeValues[] = "'" . addslashes($value) . "'";
            } else {
                $typeValues[] = "'" . $case->name . "'";
            }
        }
        
        $output .= "  type {$enumName} = " . implode(' | ', $typeValues) . ";\n";
        
        // Generate runtime objects if enabled - as ambient declarations
        if (config('ts-enum-generator.output.generate_runtime_objects') && 
            !config('ts-enum-generator.output.types_only')) {
            $output .= $this->generateNamespaceRuntimeObject($enumData);
        }
        
        // Generate per-type utilities if enabled - as ambient declarations
        if (config('ts-enum-generator.output.generate_per_type_utils') && 
            config('ts-enum-generator.output.generate_runtime_objects') &&
            !config('ts-enum-generator.output.types_only')) {
            $output .= $this->generateNamespacePerTypeUtilities($enumData);
        }
        
        $output .= "\n";
        
        return $output;
    }

    private function generateESModuleEnumDefinition(array $enumData): string
    {
        $enumName = $this->getPrefixedEnumName($enumData);
        $cases = $enumData['cases'];
        $output = '';
        
        // Generate the type definition
        $typeValues = [];
        foreach ($cases as $case) {
            if ($this->isBackedEnum($case)) {
                $value = $case->value;
                $typeValues[] = "'" . addslashes($value) . "'";
            } else {
                $typeValues[] = "'" . $case->name . "'";
            }
        }
        
        $output .= "export type {$enumName} = " . implode(' | ', $typeValues) . ";\n";
        
        // Generate runtime objects if enabled
        if (config('ts-enum-generator.output.generate_runtime_objects') && 
            !config('ts-enum-generator.output.types_only')) {
            $output .= $this->generateESModuleRuntimeObject($enumData);
        }
        
        // Generate per-type utilities if enabled
        if (config('ts-enum-generator.output.generate_per_type_utils') && 
            config('ts-enum-generator.output.generate_runtime_objects') &&
            !config('ts-enum-generator.output.types_only')) {
            $output .= $this->generateESModulePerTypeUtilities($enumData);
        }
        
        $output .= "\n";
        
        return $output;
    }

    private function generateNamespaceRuntimeObject(array $enumData): string
    {
        $enumName = $enumData['name'];
        $cases = $enumData['cases'];
        
        $output = "\n  const {$enumName}: {\n";
        
        foreach ($cases as $case) {
            if ($this->isBackedEnum($case)) {
                $value = $case->value;
                $output .= "    readonly " . $case->name . ": '" . addslashes($value) . "';\n";
            } else {
                $name = $case->name;
                $output .= "    readonly " . $name . ": '" . $name . "';\n";
            }
        }
        
        $output .= "  };\n";
        
        return $output;
    }

    private function generateESModuleRuntimeObject(array $enumData): string
    {
        $enumName = $this->getPrefixedEnumName($enumData);
        $cases = $enumData['cases'];
        
        $output = "\nexport const {$enumName} = {\n";
        
        foreach ($cases as $case) {
            if ($this->isBackedEnum($case)) {
                $value = $case->value;
                $output .= "  " . $case->name . ": '" . addslashes($value) . "' as const,\n";
            } else {
                $name = $case->name;
                $output .= "  " . $name . ": '" . $name . "' as const,\n";
            }
        }
        
        $output .= "} as const;\n";
        
        return $output;
    }

    private function generateNamespacePerTypeUtilities(array $enumData): string
    {
        $enumName = $enumData['name'];
        
        $output = "\n  const {$enumName}Utils: {\n";
        $output .= "    values: {$enumName}[];\n";
        $output .= "    keys: (keyof typeof {$enumName})[];\n";
        $output .= "    entries: [keyof typeof {$enumName}, {$enumName}][];\n";
        $output .= "    isValid: (value: any) => value is {$enumName};\n";
        $output .= "    fromKey: (key: keyof typeof {$enumName}) => {$enumName};\n";
        $output .= "  };\n";
        
        return $output;
    }

    private function generateESModulePerTypeUtilities(array $enumData): string
    {
        $enumName = $this->getPrefixedEnumName($enumData);
        
        $output = "\nexport const {$enumName}Utils = {\n";
        $output .= "  values: Object.values({$enumName}),\n";
        $output .= "  keys: Object.keys({$enumName}) as Array<keyof typeof {$enumName}>,\n";
        $output .= "  entries: Object.entries({$enumName}) as Array<[keyof typeof {$enumName}, {$enumName}]>,\n";
        $output .= "  isValid: (value: any): value is {$enumName} => Object.values({$enumName}).includes(value),\n";
        $output .= "  fromKey: (key: keyof typeof {$enumName}): {$enumName} => {$enumName}[key],\n";
        $output .= "};\n";
        
        return $output;
    }

    private function generateNamespaceGenericUtilities(array $enums): string
    {
        $output = "\n  const EnumUtils: {\n";
        $output .= "    isValid: <T extends Record<string, string>>(enumObject: T, value: any) => value is T[keyof T];\n";
        $output .= "    values: <T extends Record<string, string>>(enumObject: T) => T[keyof T][];\n";
        $output .= "    keys: <T extends Record<string, string>>(enumObject: T) => (keyof T)[];\n";
        $output .= "    entries: <T extends Record<string, string>>(enumObject: T) => [keyof T, T[keyof T]][];\n";
        $output .= "    fromKey: <T extends Record<string, string>>(enumObject: T, key: keyof T) => T[keyof T];\n";
        $output .= "  };\n";
        
        return $output;
    }

    private function generateESModuleGenericUtilities(): string
    {
        $output = "\nexport const EnumUtils = {\n";
        $output .= "  isValid: <T extends Record<string, string>>(enumObject: T, value: any): value is T[keyof T] => {\n";
        $output .= "    return Object.values(enumObject).includes(value);\n";
        $output .= "  },\n";
        $output .= "  values: <T extends Record<string, string>>(enumObject: T): T[keyof T][] => {\n";
        $output .= "    return Object.values(enumObject);\n";
        $output .= "  },\n";
        $output .= "  keys: <T extends Record<string, string>>(enumObject: T): (keyof T)[] => {\n";
        $output .= "    return Object.keys(enumObject) as (keyof T)[];\n";
        $output .= "  },\n";
        $output .= "  entries: <T extends Record<string, string>>(enumObject: T): [keyof T, T[keyof T]][] => {\n";
        $output .= "    return Object.entries(enumObject) as [keyof T, T[keyof T]][];\n";
        $output .= "  },\n";
        $output .= "  fromKey: <T extends Record<string, string>>(enumObject: T, key: keyof T): T[keyof T] => {\n";
        $output .= "    return enumObject[key];\n";
        $output .= "  },\n";
        $output .= "};\n";
        
        return $output;
    }

    private function groupEnumsByNamespace(): array
    {
        $grouped = [];
        
        foreach ($this->collectedEnums as $enumData) {
            $namespace = $enumData['namespace'];
            if (!isset($grouped[$namespace])) {
                $grouped[$namespace] = [];
            }
            $grouped[$namespace][] = $enumData;
        }
        
        return $grouped;
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

    private function getPrefixedEnumName(array $enumData): string
    {
        return $enumData['name'];
    }
} 