<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Core;

use Composer\ClassMapGenerator\ClassMapGenerator;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generates class loading information (class maps, class aliases etc.) and writes it to files
 * for further inclusion in the bootstrap
 * @internal
 */
readonly class ClassLoadingInformationGenerator
{
    /**
     * Returns class loading information for a single package
     *
     * @param bool $useRelativePaths If set to TRUE, make the path relative to the current TYPO3 public web path
     */
    public function buildClassLoadingInformationForPackage(
        PackageInterface $package,
        bool $useRelativePaths,
        bool $isDevMode,
        string $installationRoot,
    ): array {
        $classMap = [];
        $psr4 = [];
        $packagePath = $package->getPackagePath();
        $manifest = $package->getValueFromComposerManifest();
        if (empty($manifest->autoload)) {
            // Legacy mode: Scan the complete extension directory for class files
            // @todo: Drop this as breaking change in v14?! Extensions must deliver a proper
            //        composer.json nowadays, and PSR-4 can and should be strong requirement as well?!
            $classMap = $this->createClassMap($packagePath, $useRelativePaths, $installationRoot, !$isDevMode);
        } else {
            $autoloadPsr4 = $this->getAutoloadSectionFromManifest($manifest, 'psr-4', $isDevMode);
            if (!empty($autoloadPsr4)) {
                foreach ($autoloadPsr4 as $namespacePrefix => $paths) {
                    foreach ((array)$paths as $path) {
                        $namespacePath = $packagePath . $path;
                        $namespaceRealPath = (string)realpath($namespacePath);
                        if ($useRelativePaths) {
                            $psr4[$namespacePrefix][] = $this->makePathRelative($namespacePath, $namespaceRealPath, $installationRoot);
                        } else {
                            $psr4[$namespacePrefix][] = $namespacePath;
                        }
                        if (!empty($namespaceRealPath) && is_dir($namespaceRealPath)) {
                            // Add all prs-4 classes to the class map for improved class loading performance
                            $classMap = array_merge($classMap, $this->createClassMap($namespacePath, $useRelativePaths, $installationRoot, false, $namespacePrefix));
                        }
                    }
                }
            }
            $autoloadClassmap = $this->getAutoloadSectionFromManifest($manifest, 'classmap', $isDevMode);
            if (!empty($autoloadClassmap)) {
                foreach ($autoloadClassmap as $path) {
                    $classMap = array_merge($classMap, $this->createClassMap($packagePath . $path, $useRelativePaths, $installationRoot));
                }
            }
        }
        return [
            'classMap' => $classMap,
            'psr-4' => $psr4,
        ];
    }

    /**
     * Returns class alias map for given package
     *
     * @throws Exception
     */
    public function buildClassAliasMapForPackage(PackageInterface $package): array
    {
        $aliasToClassNameMapping = [];
        $classNameToAliasMapping = [];
        $possibleClassAliasFiles = [];
        $manifest = $package->getValueFromComposerManifest();
        if (!empty($manifest->extra->{'typo3/class-alias-loader'}->{'class-alias-maps'})) {
            $possibleClassAliasFiles = $manifest->extra->{'typo3/class-alias-loader'}->{'class-alias-maps'};
            if (!is_array($possibleClassAliasFiles)) {
                throw new Exception('"typo3/class-alias-loader"/"class-alias-maps" must return an array!', 1444142481);
            }
        } else {
            $possibleClassAliasFiles[] = 'Migrations/Code/ClassAliasMap.php';
        }
        $packagePath = $package->getPackagePath();
        foreach ($possibleClassAliasFiles as $possibleClassAliasFile) {
            $possiblePathToClassAliasFile = $packagePath . $possibleClassAliasFile;
            if (file_exists($possiblePathToClassAliasFile)) {
                $packageAliasMap = require $possiblePathToClassAliasFile;
                if (!is_array($packageAliasMap)) {
                    throw new Exception('"class alias maps" must return an array', 1422625075);
                }
                foreach ($packageAliasMap as $aliasClassName => $className) {
                    $lowerCasedAliasClassName = strtolower($aliasClassName);
                    $aliasToClassNameMapping[$lowerCasedAliasClassName] = $className;
                    $classNameToAliasMapping[$className][$lowerCasedAliasClassName] = $lowerCasedAliasClassName;
                }
            }
        }
        return [
            'aliasToClassNameMapping' => $aliasToClassNameMapping,
            'classNameToAliasMapping' => $classNameToAliasMapping,
        ];
    }

    /**
     * Generate the class map file
     *
     * @return string[]
     */
    public function buildAutoloadInformationFiles(
        bool $isDevMode,
        string $installationRoot,
        array $activeExtensionPackages,
    ): array {
        $psr4File = $classMapFile = <<<EOF
<?php

// autoload_classmap.php @generated by TYPO3

\$typo3InstallDir = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';

return array(

EOF;
        $classMap = [];
        $psr4 = [];
        foreach ($activeExtensionPackages as $package) {
            $classLoadingInformation = $this->buildClassLoadingInformationForPackage($package, true, $isDevMode, $installationRoot);
            $classMap = array_merge($classMap, $classLoadingInformation['classMap']);
            $psr4 = array_merge($psr4, $classLoadingInformation['psr-4']);
        }
        ksort($classMap);
        ksort($psr4);
        foreach ($classMap as $class => $relativePath) {
            $classMapFile .= sprintf('    %s => %s,', var_export($class, true), $this->getPathCode($relativePath)) . "\n";
        }
        $classMapFile .= ");\n";
        foreach ($psr4 as $prefix => $relativePaths) {
            $psr4File .= sprintf('    %s => array(%s),', var_export($prefix, true), implode(',', array_map($this->getPathCode(...), $relativePaths))) . "\n";
        }
        $psr4File .= ");\n";
        return ['classMapFile' => $classMapFile, 'psr-4File' => $psr4File];
    }

    /**
     * Build class alias mapping file
     */
    public function buildClassAliasMapFile(array $activeExtensionPackages): string
    {
        $aliasToClassNameMapping = [];
        $classNameToAliasMapping = [];
        foreach ($activeExtensionPackages as $package) {
            $aliasMappingForPackage = $this->buildClassAliasMapForPackage($package);
            $aliasToClassNameMapping = array_merge($aliasToClassNameMapping, $aliasMappingForPackage['aliasToClassNameMapping']);
            $classNameToAliasMapping = array_merge($classNameToAliasMapping, $aliasMappingForPackage['classNameToAliasMapping']);
        }
        $exportArray = [
            'aliasToClassNameMapping' => $aliasToClassNameMapping,
            'classNameToAliasMapping' => $classNameToAliasMapping,
        ];
        $fileContent = "<?php\nreturn ";
        $fileContent .= var_export($exportArray, true);
        $fileContent .= ";\n";
        return $fileContent;
    }

    /**
     * Fetches class loading info from the according section from the manifest file.
     * Development information will be extracted and merged as well.
     */
    protected function getAutoloadSectionFromManifest(\stdClass $manifest, string $section, bool $isDevMode): array
    {
        $finalAutoloadSection = [];
        $autoloadDefinition = json_decode((string)json_encode($manifest->autoload), true);
        if (!empty($autoloadDefinition[$section]) && is_array($autoloadDefinition[$section])) {
            $finalAutoloadSection = $autoloadDefinition[$section];
        }
        if ($isDevMode) {
            if (isset($manifest->{'autoload-dev'})) {
                $autoloadDefinitionDev = json_decode((string)json_encode($manifest->{'autoload-dev'}), true);
                if (!empty($autoloadDefinitionDev[$section]) && is_array($autoloadDefinitionDev[$section])) {
                    $finalAutoloadSection = array_merge($finalAutoloadSection, $autoloadDefinitionDev[$section]);
                }
            }
        }
        return $finalAutoloadSection;
    }

    /**
     * Creates a class map for a given absolute path
     */
    protected function createClassMap(
        string $classesPath,
        bool $useRelativePaths,
        string $installationRoot,
        bool $ignorePotentialTestClasses = false,
        ?string $namespace = null
    ): array {
        $classMap = [];
        $blacklistExpression = null;
        if ($ignorePotentialTestClasses) {
            $blacklistPathPrefix = (string)realpath($classesPath);
            $blacklistPathPrefix = str_replace('\\', '/', $blacklistPathPrefix);
            $blacklistExpression = "{($blacklistPathPrefix/tests/|$blacklistPathPrefix/Tests/|$blacklistPathPrefix/Resources/|$blacklistPathPrefix/res/)}";
        }
        $generator = new ClassMapGenerator();
        $generator->scanPaths($classesPath, $blacklistExpression, 'classmap', $namespace);
        $map = $generator->getClassMap()->getMap();
        foreach ($map as $class => $path) {
            if ($useRelativePaths) {
                $classMap[$class] = $this->makePathRelative($classesPath, realpath($path), $installationRoot);
            } else {
                $classMap[$class] = $path;
            }
        }
        return $classMap;
    }

    /**
     * Generate a relative path string from an absolute path within a give package path
     */
    protected function makePathRelative(string $packagePath, string $realPathOfClassFile, string $installationRoot): string
    {
        $realPathOfClassFile = GeneralUtility::fixWindowsFilePath($realPathOfClassFile);
        $packageRealPath = GeneralUtility::fixWindowsFilePath((string)realpath($packagePath));
        $relativePackagePath = rtrim(substr($packagePath, strlen($installationRoot)), '/');
        if ($realPathOfClassFile === $packageRealPath) {
            return $relativePackagePath;
        }
        return $relativePackagePath . '/' . ltrim(substr($realPathOfClassFile, strlen($packageRealPath)), '/');
    }

    /**
     * Generate a relative path string from a relative path
     */
    protected function getPathCode(string $relativePathToClassFile): string
    {
        return '$typo3InstallDir . ' . var_export($relativePathToClassFile, true);
    }
}
