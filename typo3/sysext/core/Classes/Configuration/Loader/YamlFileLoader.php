<?php
namespace TYPO3\CMS\Core\Configuration\Loader;

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

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A YAML file loader that allows to load YAML files, based on the Symfony/Yaml component
 *
 * In addition to just load a YAML file, it adds some special functionality.
 *
 * - A special "imports" key in the YAML file allows to include other YAML files recursively.
 *   The actual YAML file gets loaded after the import statements, which are interpreted first,
 *   at the very beginning. Imports can be referenced with a relative path.
 *
 * - Merging configuration options of import files when having simple "lists" will add items to the list instead
 *   of overwriting them.
 *
 * - Special placeholder values set via %optionA.suboptionB% replace the value with the named path of the configuration
 *   The placeholders will act as a full replacement of this value.
 *
 * - Environment placeholder values set via %env(option)% will be replaced by env variables of the same name
 */
class YamlFileLoader
{
    public const PROCESS_PLACEHOLDERS = 1;
    public const PROCESS_IMPORTS = 2;

    /**
     * @var int
     */
    private $flags;

    /**
     * Loads and parses a YAML file, and returns an array with the found data
     *
     * @param string $fileName either relative to TYPO3's base project folder or prefixed with EXT:...
     * @param int $flags Flags to configure behaviour of the loader: see public PROCESS_ constants above
     * @return array the configuration as array
     */
    public function load(string $fileName, int $flags = self::PROCESS_PLACEHOLDERS | self::PROCESS_IMPORTS): array
    {
        $this->flags = $flags;
        return $this->loadAndParse($fileName, null);
    }

    /**
     * Internal method which does all the logic. Built so it can be re-used recursively.
     *
     * @param string $fileName either relative to TYPO3's base project folder or prefixed with EXT:...
     * @param string|null $currentFileName when called recursively
     * @return array the configuration as array
     */
    protected function loadAndParse(string $fileName, ?string $currentFileName): array
    {
        $sanitizedFileName = $this->getStreamlinedFileName($fileName, $currentFileName);
        $content = $this->getFileContents($sanitizedFileName);
        $content = Yaml::parse($content);

        if (!is_array($content)) {
            throw new \RuntimeException(
                'YAML file "' . $fileName . '" could not be parsed into valid syntax, probably empty?',
                1497332874
            );
        }

        if ($this->hasFlag(self::PROCESS_IMPORTS)) {
            $content = $this->processImports($content, $sanitizedFileName);
        }
        if ($this->hasFlag(self::PROCESS_PLACEHOLDERS)) {
            // Check for "%" placeholders
            $content = $this->processPlaceholders($content, $content);
        }
        return $content;
    }

    /**
     * Put into a separate method to ease the pains with unit tests
     *
     * @param string $fileName
     * @return string the contents
     */
    protected function getFileContents(string $fileName): string
    {
        return file_get_contents($fileName);
    }

    /**
     * Fetches the absolute file name, but if a different file name is given, it is built relative to that.
     *
     * @param string $fileName either relative to TYPO3's base project folder or prefixed with EXT:...
     * @param string|null $currentFileName when called recursively this contains the absolute file name of the file that included this file
     * @return string the contents of the file
     * @throws \RuntimeException when the file was not accessible
     */
    protected function getStreamlinedFileName(string $fileName, ?string $currentFileName): string
    {
        if (!empty($currentFileName)) {
            if (strpos($fileName, 'EXT:') === 0 || GeneralUtility::isAbsPath($fileName)) {
                $streamlinedFileName = GeneralUtility::getFileAbsFileName($fileName);
            } else {
                // Now this path is considered to be relative the current file name
                $streamlinedFileName = PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath(
                    $currentFileName,
                    $fileName
                );
                if (!GeneralUtility::isAllowedAbsPath($streamlinedFileName)) {
                    throw new \RuntimeException(
                        'Referencing a file which is outside of TYPO3s main folder',
                        1560319866
                    );
                }
            }
        } else {
            $streamlinedFileName = GeneralUtility::getFileAbsFileName($fileName);
        }
        if (!$streamlinedFileName) {
            throw new \RuntimeException('YAML File "' . $fileName . '" could not be loaded', 1485784246);
        }
        return $streamlinedFileName;
    }

    /**
     * Return value from environment variable
     *
     * Environment variables may only contain word characters and underscores (a-zA-Z0-9_)
     * to be compatible to shell environments.
     *
     * @param string $value
     * @return string
     */
    protected function getValueFromEnv(string $value): string
    {
        $matches = [];
        preg_match_all('/%env\([\'"]?(\w+)[\'"]?\)%/', $value, $matches);
        $envVars = array_combine($matches[0], $matches[1]);
        foreach ($envVars as $substring => $envVarName) {
            $envVar = getenv($envVarName);
            $value = $envVar ? str_replace($substring, $envVar, $value) : $value;
        }
        return $value;
    }

    /**
     * Checks for the special "imports" key on the main level of a file,
     * which calls "load" recursively.
     * @param array $content
     * @param string|null $fileName
     * @return array
     */
    protected function processImports(array $content, ?string $fileName): array
    {
        if (isset($content['imports']) && is_array($content['imports'])) {
            foreach ($content['imports'] as $import) {
                $importedContent = $this->loadAndParse($import['resource'], $fileName);
                // override the imported content with the one from the current file
                $content = ArrayUtility::replaceAndAppendScalarValuesRecursive($importedContent, $content);
            }
            unset($content['imports']);
        }
        return $content;
    }

    /**
     * Main function that gets called recursively to check for %...% placeholders
     * inside the array
     *
     * @param array $content the current sub-level content array
     * @param array $referenceArray the global configuration array
     *
     * @return array the modified sub-level content array
     */
    protected function processPlaceholders(array $content, array $referenceArray): array
    {
        foreach ($content as $k => $v) {
            if ($this->isEnvPlaceholder($v)) {
                $content[$k] = $this->getValueFromEnv($v);
            } elseif ($this->isPlaceholder($v)) {
                $content[$k] = $this->getValueFromReferenceArray($v, $referenceArray);
            } elseif (is_array($v)) {
                $content[$k] = $this->processPlaceholders($v, $referenceArray);
            }
        }
        return $content;
    }

    /**
     * Returns the value for a placeholder as fetched from the referenceArray
     *
     * @param string $placeholder the string to search for
     * @param array $referenceArray the main configuration array where to look up the data
     *
     * @return array|mixed|string
     */
    protected function getValueFromReferenceArray(string $placeholder, array $referenceArray)
    {
        $pointer = trim($placeholder, '%');
        $parts = explode('.', $pointer);
        $referenceData = $referenceArray;
        foreach ($parts as $part) {
            if (isset($referenceData[$part])) {
                $referenceData = $referenceData[$part];
            } else {
                // return unsubstituted placeholder
                return $placeholder;
            }
        }
        if ($this->isPlaceholder($referenceData)) {
            $referenceData = $this->getValueFromReferenceArray($referenceData, $referenceArray);
        }
        return $referenceData;
    }

    /**
     * Checks if a value is a string and begins and ends with %...%
     *
     * @param mixed $value the probe to check for
     * @return bool
     */
    protected function isPlaceholder($value): bool
    {
        return is_string($value) && strpos($value, '%') === 0 && substr($value, -1) === '%';
    }

    /**
     * Checks if a value is a string and contains an env placeholder
     *
     * @param mixed $value the probe to check for
     * @return bool
     */
    protected function isEnvPlaceholder($value): bool
    {
        return is_string($value) && (strpos($value, '%env(') !== false);
    }

    protected function hasFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }
}
