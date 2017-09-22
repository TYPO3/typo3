<?php
namespace TYPO3\CMS\Core\Core;

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

use Composer\Autoload\ClassLoader;
use Composer\Autoload\ClassMapGenerator;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generates class loading information (class maps, class aliases etc.) and writes it to files
 * for further inclusion in the bootstrap
 * @internal
 */
class ClassLoadingInformationGenerator
{
    /**
     * @var PackageInterface[]
     */
    protected $activeExtensionPackages;

    /**
     * @var ClassLoader
     */
    protected $classLoader;

    /**
     * @var string
     */
    protected $installationRoot;

    /**
     * @var bool
     */
    protected $isDevMode;

    /**
     * @param ClassLoader $classLoader
     * @param array $activeExtensionPackages
     * @param string $installationRoot
     * @param bool $isDevMode
     */
    public function __construct(ClassLoader $classLoader, array $activeExtensionPackages, $installationRoot, $isDevMode = false)
    {
        $this->classLoader = $classLoader;
        $this->activeExtensionPackages = $activeExtensionPackages;
        $this->installationRoot = $installationRoot;
        $this->isDevMode = $isDevMode;
    }

    /**
     * Returns class loading information for a single package
     *
     * @param PackageInterface $package The package to generate the class loading info for
     * @param bool $useRelativePaths If set to TRUE, make the path relative to the current TYPO3 instance (PATH_site)
     * @return array
     */
    public function buildClassLoadingInformationForPackage(PackageInterface $package, $useRelativePaths = false)
    {
        $classMap = [];
        $psr4 = [];
        $packagePath = $package->getPackagePath();
        $manifest = $package->getValueFromComposerManifest();

        if (empty($manifest->autoload)) {
            // Legacy mode: Scan the complete extension directory for class files
            $classMap = $this->createClassMap($packagePath, $useRelativePaths, !$this->isDevMode);
        } else {
            $autoloadPsr4 = $this->getAutoloadSectionFromManifest($manifest, 'psr-4');
            if (!empty($autoloadPsr4)) {
                foreach ($autoloadPsr4 as $namespacePrefix => $paths) {
                    foreach ((array)$paths as $path) {
                        $namespacePath = $packagePath . $path;
                        $namespaceRealPath = realpath($namespacePath);
                        if ($useRelativePaths) {
                            $psr4[$namespacePrefix][] = $this->makePathRelative($namespacePath, $namespaceRealPath);
                        } else {
                            $psr4[$namespacePrefix][] = $namespacePath;
                        }
                        if (!empty($namespaceRealPath) && is_dir($namespaceRealPath)) {
                            // Add all prs-4 classes to the class map for improved class loading performance
                            $classMap = array_merge($classMap, $this->createClassMap($namespacePath, $useRelativePaths, false, $namespacePrefix));
                        }
                    }
                }
            }
            $autoloadClassmap = $this->getAutoloadSectionFromManifest($manifest, 'classmap');
            if (!empty($autoloadClassmap)) {
                foreach ($autoloadClassmap as $path) {
                    $classMap = array_merge($classMap, $this->createClassMap($packagePath . $path, $useRelativePaths));
                }
            }
        }

        return ['classMap' => $classMap, 'psr-4' => $psr4];
    }

    /**
     * Fetches class loading info from the according section from the manifest file.
     * Development information will be extracted and merged as well.
     *
     * @param \stdClass $manifest
     * @param string $section
     * @return array
     */
    protected function getAutoloadSectionFromManifest($manifest, $section)
    {
        $finalAutoloadSection = [];
        $autoloadDefinition = json_decode(json_encode($manifest->autoload), true);
        if (!empty($autoloadDefinition[$section]) && is_array($autoloadDefinition[$section])) {
            $finalAutoloadSection = $autoloadDefinition[$section];
        }
        if ($this->isDevMode) {
            if (isset($manifest->{'autoload-dev'})) {
                $autoloadDefinitionDev = json_decode(json_encode($manifest->{'autoload-dev'}), true);
                if (!empty($autoloadDefinitionDev[$section]) && is_array($autoloadDefinitionDev[$section])) {
                    $finalAutoloadSection = array_merge($finalAutoloadSection, $autoloadDefinitionDev[$section]);
                }
            }
        }

        return $finalAutoloadSection;
    }

    /**
     * Creates a class map for a given (absolute) path
     *
     * @param string $classesPath
     * @param bool $useRelativePaths
     * @param bool $ignorePotentialTestClasses
     * @param string $namespace
     * @return array
     */
    protected function createClassMap($classesPath, $useRelativePaths = false, $ignorePotentialTestClasses = false, $namespace = null)
    {
        $classMap = [];
        $blacklistExpression = null;
        if ($ignorePotentialTestClasses) {
            $blacklistPathPrefix = realpath($classesPath);
            $blacklistPathPrefix = strtr($blacklistPathPrefix, '\\', '/');
            $blacklistExpression = "{($blacklistPathPrefix/tests/|$blacklistPathPrefix/Tests/|$blacklistPathPrefix/Resources/|$blacklistPathPrefix/res/|$blacklistPathPrefix/class.ext_update.php)}";
        }
        foreach (ClassMapGenerator::createMap($classesPath, $blacklistExpression, null, $namespace) as $class => $path) {
            if ($useRelativePaths) {
                $classMap[$class] = $this->makePathRelative($classesPath, $path);
            } else {
                $classMap[$class] = $path;
            }
        }
        return $classMap;
    }

    /**
     * Returns class alias map for given package
     *
     * @param PackageInterface $package The package to generate the class alias info for
     * @throws \TYPO3\CMS\Core\Error\Exception
     * @return array
     */
    public function buildClassAliasMapForPackage(PackageInterface $package)
    {
        $aliasToClassNameMapping = [];
        $classNameToAliasMapping = [];
        $possibleClassAliasFiles = [];
        $manifest = $package->getValueFromComposerManifest();
        if (!empty($manifest->extra->{'typo3/class-alias-loader'}->{'class-alias-maps'})) {
            $possibleClassAliasFiles = $manifest->extra->{'typo3/class-alias-loader'}->{'class-alias-maps'};
            if (!is_array($possibleClassAliasFiles)) {
                throw new \TYPO3\CMS\Core\Error\Exception('"typo3/class-alias-loader"/"class-alias-maps" must return an array!', 1444142481);
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
                    throw new \TYPO3\CMS\Core\Error\Exception('"class alias maps" must return an array', 1422625075);
                }
                foreach ($packageAliasMap as $aliasClassName => $className) {
                    $lowerCasedAliasClassName = strtolower($aliasClassName);
                    $aliasToClassNameMapping[$lowerCasedAliasClassName] = $className;
                    $classNameToAliasMapping[$className][$lowerCasedAliasClassName] = $lowerCasedAliasClassName;
                }
            }
        }

        return ['aliasToClassNameMapping' => $aliasToClassNameMapping, 'classNameToAliasMapping' => $classNameToAliasMapping];
    }

    /**
     * Generate the class map file
     * @return string[]
     * @internal
     */
    public function buildAutoloadInformationFiles()
    {
        $psr4File = $classMapFile = <<<EOF
<?php

// autoload_classmap.php @generated by TYPO3

\$typo3InstallDir = PATH_site;

return array(

EOF;
        $classMap = [];
        $psr4 = [];
        foreach ($this->activeExtensionPackages as $package) {
            $classLoadingInformation = $this->buildClassLoadingInformationForPackage($package, true);
            $classMap = array_merge($classMap, $classLoadingInformation['classMap']);
            $psr4 = array_merge($psr4, $classLoadingInformation['psr-4']);
        }

        ksort($classMap);
        ksort($psr4);
        foreach ($classMap as $class => $relativePath) {
            $classMapFile .= sprintf('    %s => %s,', var_export($class, true), $this->getPathCode($relativePath)) . LF;
        }
        $classMapFile .= ");\n";

        foreach ($psr4 as $prefix => $relativePaths) {
            $psr4File .= sprintf('    %s => array(%s),', var_export($prefix, true), implode(',', array_map([$this, 'getPathCode'], $relativePaths))) . LF;
        }
        $psr4File .= ");\n";

        return ['classMapFile' => $classMapFile, 'psr-4File' => $psr4File];
    }

    /**
     * Generate a relative path string from an absolute path within a give package path
     *
     * @param string $packagePath
     * @param string $realPathOfClassFile
     * @param bool $relativeToRoot
     * @return string
     */
    protected function makePathRelative($packagePath, $realPathOfClassFile, $relativeToRoot = true)
    {
        $realPathOfClassFile = GeneralUtility::fixWindowsFilePath($realPathOfClassFile);
        $packageRealPath = GeneralUtility::fixWindowsFilePath(realpath($packagePath));
        $relativePackagePath = rtrim(substr($packagePath, strlen($this->installationRoot)), '/');
        if ($relativeToRoot) {
            if ($realPathOfClassFile === $packageRealPath) {
                $relativePathToClassFile = $relativePackagePath;
            } else {
                $relativePathToClassFile = $relativePackagePath . '/' . ltrim(substr($realPathOfClassFile, strlen($packageRealPath)), '/');
            }
        } else {
            $relativePathToClassFile = ltrim(substr($realPathOfClassFile, strlen($packageRealPath)), '/');
        }

        return $relativePathToClassFile;
    }

    /**
     * Generate a relative path string from a relative path
     *
     * @param string $relativePathToClassFile
     * @return string
     */
    protected function getPathCode($relativePathToClassFile)
    {
        return '$typo3InstallDir . ' . var_export($relativePathToClassFile, true);
    }

    /**
     * Build class alias mapping file
     *
     * @return string
     * @throws \Exception
     * @internal
     */
    public function buildClassAliasMapFile()
    {
        $aliasToClassNameMapping = [];
        $classNameToAliasMapping = [];
        foreach ($this->activeExtensionPackages as $package) {
            $aliasMappingForPackage = $this->buildClassAliasMapForPackage($package);
            $aliasToClassNameMapping = array_merge($aliasToClassNameMapping, $aliasMappingForPackage['aliasToClassNameMapping']);
            $classNameToAliasMapping = array_merge($classNameToAliasMapping, $aliasMappingForPackage['classNameToAliasMapping']);
        }
        $exportArray = [
            'aliasToClassNameMapping' => $aliasToClassNameMapping,
            'classNameToAliasMapping' => $classNameToAliasMapping
        ];
        $fileContent = '<?php' . chr(10) . 'return ';
        $fileContent .= var_export($exportArray, true);
        $fileContent .= ";\n";
        return $fileContent;
    }
}
