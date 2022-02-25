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

namespace TYPO3\CMS\Form\Tests\Functional\RequestHandling;

use Symfony\Component\Mailer\SentMessage;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractRequestHandlingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const ROOT_PAGE_BASE_URI = 'http://localhost';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_GB.UTF8', 'iso' => 'en', 'hrefLang' => 'en-GB', 'direction' => ''],
    ];

    protected const MAIL_SPOOL_FOLDER = 'typo3temp/var/transient/spool/';

    protected $coreExtensionsToLoad = ['form', 'fluid_styled_content'];

    protected $testExtensionsToLoad = [
        'typo3/sysext/form/Tests/Functional/RequestHandling/Fixtures/Extensions/form_caching_tests',
    ];

    protected $configurationToUseInTestInstance = [
        'MAIL' => [
            'defaultMailFromAddress' => 'hello@typo3.org',
            'defaultMailFromName' => 'TYPO3',
            'transport' => 'mbox',
            'transport_spool_type' => 'file',
            'transport_spool_filepath' => self::MAIL_SPOOL_FOLDER,
        ],
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'hash' => [
                        'backend' => Typo3DatabaseBackend::class,
                        'frontend' => VariableFrontend::class,
                    ],
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                        'frontend' => VariableFrontend::class,
                    ],
                    'pagesection' => [
                        'backend' => Typo3DatabaseBackend::class,
                        'frontend' => VariableFrontend::class,
                    ],
                    'rootline' => [
                        'backend' => Typo3DatabaseBackend::class,
                        'frontend' => VariableFrontend::class,
                    ],
                ],
            ],
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
        ],

    ];

    protected string $databaseScenarioFile = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'site1',
            $this->buildSiteConfiguration(1000, static::ROOT_PAGE_BASE_URI . '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function tearDown(): void
    {
        $this->purgeMailSpool();
        parent::tearDown();
    }

    private function setUpDatabase(): void
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $factory = DataHandlerFactory::fromYamlFile($this->databaseScenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );
    }

    protected function getMailSpoolMessages(): array
    {
        $messages = [];
        foreach (array_filter(glob($this->instancePath . '/' . self::MAIL_SPOOL_FOLDER . '*'), 'is_file') as $path) {
            $serializedMessage = file_get_contents($path);
            $sentMessage = unserialize($serializedMessage);
            if (!$sentMessage instanceof SentMessage) {
                continue;
            }
            $fluidEmail = $sentMessage->getOriginalMessage();
            if (!$fluidEmail instanceof FluidEmail) {
                continue;
            }

            $parsedHeaders = $this->parseRawHeaders($sentMessage->toString());
            $item = [
                'plaintext' => $fluidEmail->getTextBody(),
                'html' => $fluidEmail->getHtmlBody(),
                'subject' => $fluidEmail->getSubject(),
                'date' => $fluidEmail->getDate() ?? $parsedHeaders['Date'] ?? null,
                'to' => $fluidEmail->getTo(),
            ];
            if (is_string($item['date'])) {
                // @todo `@timezone` is not handled here - probably tests don't need date at all
                $item['date'] = new \DateTimeImmutable($item['date']);
            }
            $messages[] = $item;
        }

        return $messages;
    }

    /**
     * @param string $rawMessage
     * @return array<string, string>
     */
    protected function parseRawHeaders(string $rawMessage): array
    {
        $rawParts = explode("\r\n\r\n", $rawMessage, 2);
        $rawLines = explode("\r\n", $rawParts[0]);
        $rawHeaders = array_map(
            fn (string $rawLine) => array_map(
                'trim',
                explode(':', $rawLine, 2)
            ),
            $rawLines
        );
        return array_combine(
            array_column($rawHeaders, 0),
            array_column($rawHeaders, 1)
        );
    }

    protected function purgeMailSpool(): void
    {
        foreach (glob($this->instancePath . '/' . self::MAIL_SPOOL_FOLDER . '*') as $path) {
            unlink($path);
        }
    }
}
