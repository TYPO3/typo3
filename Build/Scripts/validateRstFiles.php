#!/usr/bin/env php
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

require __DIR__ . '/../../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

use Symfony\Component\Finder\Finder;

/**
 * Check ReST files for integrity. If errors are found, they will be
 * output on stdout and the program will exit with exit code 1.
 *
 * Optional arguments: -d <directory>
 *
 * By default, the standard path is used. You can override this for
 * testing purposes.
 */
class validateRstFiles
{
    /**
     * @var array
     */
    protected $messages;

    /**
     * @var bool
     */
    protected $isError;

    /**
     * @var string
     */
    protected $baseDir = 'typo3/sysext/core/Documentation/Changelog';

    public function __construct(string $dir = '')
    {
        if ($dir) {
            $this->baseDir = $dir;
        }
    }

    public function validate()
    {
        printf('Searching for rst snippets in ' . $this->baseDir . chr(10));

        $count = 0;
        $finder = $this->findFiles();
        foreach ($finder as $file) {
            $filename = (string)$file;
            $this->clearMessages();
            $fileContent = $file->getContents();
            $this->validateContent($fileContent);
            $a = explode(chr(10), trim($fileContent));
            $lastLine = array_pop($a);
            $this->validateLastLine($lastLine);
            $this->validateLastLineByFilename($filename, $lastLine);

            if ($this->isError) {
                $shortPath = substr($filename, strlen($this->baseDir));
                $shortPath = ltrim($shortPath, '/\\');
                $count++;
                printf(
                    '%-10s | %-12s | %-17s | %s ' . chr(10),
                    $this->messages['include']['title'],
                    $this->messages['reference']['title'],
                    $this->messages['index']['title'],
                    $shortPath
                );
                if ($this->messages['include']['message']) {
                    printf($this->messages['include']['message'] . chr(10));
                }
                if ($this->messages['reference']['message']) {
                    printf($this->messages['reference']['message'] . chr(10));
                }
                if ($this->messages['index']['message']) {
                    printf($this->messages['index']['message'] . chr(10));
                }
            }
        }

        if ($count > 0) {
            fwrite(STDERR, 'Found ' . $count . ' rst files with errors, check full log for details.' . chr(10));
            exit(1);
        }
        exit(0);
    }

    public function findFiles(): Finder
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->baseDir)
            ->name('/\.rst$/')
            ->notName('Index.rst')
            ->notName('Howto.rst');

        return $finder;
    }

    protected function clearMessages()
    {
        $this->messages = [
            'include' => [
                'title' => '',
                'message' => '',
            ],
            'reference' => [
                'title' => '',
                'message' => '',
            ],
            'index' => [
                'title' => '',
                'message' => '',
            ],
        ];

        $this->isError = false;
    }

    protected function validateContent(string $fileContent)
    {
        $checkFor = [
            [
                'type' => 'include',
                'regex' => '#^\\.\\. include:: \\.\\./\\.\\./Includes.txt#m',
                'title' => 'no include',
                'message' => 'insert \'.. include:: ../../Includes.txt\' in first line of the file',
            ],
            [
                'type' => 'reference',
                'regex' => '#^See :issue:`[0-9]{4,6}`#m',
                'title' => 'no reference',
                'message' => 'insert \'See :issue:`<issuenumber>`\' after headline',
            ],
        ];

        foreach ($checkFor as $values) {
            if (preg_match($values['regex'], $fileContent) !== 1) {
                $this->messages[$values['type']]['title'] = $values['title'];
                $this->messages[$values['type']]['message'] = $values['message'];
                $this->isError = true;
            }
        }
    }

    protected function validateLastLine(string $line)
    {
        $checkFor = [
            [
                'type' => 'index',
                'regex' => '#^\.\. index:: (?:(?:TypoScript|TSConfig|TCA|FlexForm|LocalConfiguration|Fluid|FAL|Database|JavaScript|PHP-API|Frontend|Backend|CLI|RTE|YAML|ext:[a-zA-Z_0-9]+)(?:,\\s|$))+#',
                'title' => 'no or wrong index',
                'message' => 'insert \'.. index:: <at least one valid keyword>\' at the last line of the file. See Build/Scripts/validateRstFiles.php for allowed keywords',
            ],
        ];

        foreach ($checkFor as $values) {
            if (preg_match($values['regex'], $line) !== 1) {
                $this->messages[$values['type']]['title'] = $values['title'];
                $this->messages[$values['type']]['message'] = $values['message'];
                $this->isError = true;
            }
        }
    }

    protected function validateLastLineByFilename(string $path, string $lastLine)
    {
        $checkFor = [
            [
                'type' => 'index',
                'regexIgnoreFilename' => '#'
                    . 'Changelog[\\\\/]'         // Ignore all Changelog files
                    . '(?:'                      // which are either
                    . '.+[\\\\/](?:Feature|Important)' // from any version but of type "Feature" or "Important"
                    . '|'                        // or
                    . '[78]'                     // from 7.x and 8.x (as there was no extension scanner back then)
                    . ')'
                    . '#',
                'regex' => '#^\.\. index:: .*(?:FullyScanned|PartiallyScanned|NotScanned).*#',
                'title' => 'missing FullyScanned / PartiallyScanned / NotScanned tag',
                'message' => 'insert \'.. index:: <at least one valid keyword and either FullyScanned, PartiallyScanned or NotScanned>\' at the last line of the file. See Build/Scripts/validateRstFiles.php for allowed keywords',
            ],
        ];

        foreach ($checkFor as $values) {
            if (preg_match($values['regexIgnoreFilename'], $path) === 1) {
                continue;
            }
            if (preg_match($values['regex'], $lastLine) !== 1) {
                $this->messages[$values['type']]['title'] = $values['title'];
                $this->messages[$values['type']]['message'] = $values['message'];
                $this->isError = true;
            }
        }
    }
}

$dir = '';
$args = getopt('d:');
if (isset($args['d'])) {
    $dir = $args['d'];
}
$validator = new validateRstFiles($dir);
$validator->validate();
