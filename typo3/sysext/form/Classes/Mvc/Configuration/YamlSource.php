<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Mvc\Configuration;

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

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;

/**
 * Configuration source based on YAML files
 *
 * Scope: frontend / backend
 * @internal
 */
class YamlSource
{
    /**
     * Will be set if the PHP YAML Extension is installed.
     * Having this installed massively improves YAML parsing performance.
     *
     * @var bool
     * @see http://pecl.php.net/package/yaml
     */
    protected $usePhpYamlExtension = false;

    /**
     * Use PHP YAML Extension if installed.
     * @internal
     */
    public function __construct()
    {
        if (extension_loaded('yaml')) {
            $this->usePhpYamlExtension = true;
        }
    }

    /**
     * Loads the specified configuration files and returns its merged content
     * as an array.
     *
     * @param array $filesToLoad
     * @return array
     * @throws ParseErrorException
     * @throws NoSuchFileException
     * @internal
     */
    public function load(array $filesToLoad): array
    {
        $configuration = [];
        foreach ($filesToLoad as $fileToLoad) {
            if ($fileToLoad instanceof File) {
                $fileIdentifier = $fileToLoad->getIdentifier();
                $rawYamlContent = $fileToLoad->getContents();
            } else {
                $fileIdentifier = $fileToLoad;
                $fileToLoad = GeneralUtility::getFileAbsFileName($fileToLoad);
                if (is_file($fileToLoad)) {
                    $rawYamlContent = file_get_contents($fileToLoad);
                } else {
                    throw new NoSuchFileException(
                        'The file "' . $fileToLoad . '" does not exist.',
                        1471473378
                    );
                }
            }

            try {
                if ($this->usePhpYamlExtension) {
                    $loadedConfiguration = @yaml_parse($rawYamlContent);
                    if ($loadedConfiguration === false) {
                        throw new ParseErrorException(
                            'A parse error occurred while parsing file "' . $fileIdentifier . '".',
                            1391894094
                        );
                    }
                } else {
                    $loadedConfiguration = Yaml::parse($rawYamlContent);
                }

                if (is_array($loadedConfiguration)) {
                    ArrayUtility::mergeRecursiveWithOverrule($configuration, $loadedConfiguration);
                }
            } catch (ParseException $exception) {
                throw new ParseErrorException(
                    'A parse error occurred while parsing file "' . $fileIdentifier . '". Error message: ' . $exception->getMessage(),
                    1480195405
                );
            }
        }

        $configuration = FormArrayUtility::convertBooleanStringsToBooleanRecursive($configuration);
        return $configuration;
    }

    /**
     * Save the specified configuration array to the given file in YAML format.
     *
     * @param File|string $fileToSave The file to write to.
     * @param array $configuration The configuration to save
     * @return void
     * @internal
     */
    public function save($fileToSave, array $configuration)
    {
        $header = $this->getHeaderFromFile($fileToSave);
        $yaml = Yaml::dump($configuration, 99, 2);
        if ($fileToSave instanceof File) {
            $fileToSave->setContents($header . LF . $yaml);
        } else {
            @file_put_contents($fileToSave, $header . LF . $yaml);
        }
    }

    /**
     * Read the header part from the given file. That means, every line
     * until the first non comment line is found.
     *
     * @param File|string $file
     * @return string The header of the given YAML file
     */
    protected function getHeaderFromFile($file): string
    {
        $header = '';
        if ($file instanceof File) {
            $fileLines = explode(LF, $file->getContents());
        } else {
            $fileLines = file($file);
        }
        foreach ($fileLines as $line) {
            if (preg_match('/^#/', $line)) {
                $header .= $line;
            } else {
                break;
            }
        }
        return $header;
    }
}
