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

namespace TYPO3\CMS\Reactions\Tests\Functional\Reaction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Reaction\CreateRecordReaction;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CreateRecordReactionTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reactions'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/reactions/Tests/Functional/Fixtures/Extensions/test_throwing_items',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CreateRecordReactionTest_reactions.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/CreateRecordReactionTest_impersonation.csv');
    }

    #[Test]
    public function reactWorksForAValidRequest(): void
    {
        $reactionRecord = $this->get(ReactionRepository::class)->getReactionRecordByIdentifier('c6e9c385-d15b-4ae1-9302-931c78f1a458');
        $reaction = $this->get(CreateRecordReaction::class);
        $request = new ServerRequest('http://localhost/', 'POST');
        $payload = [
            'foo' => 'bar',
            'bar' => [
                'string' => 'bar.foo',
                'int' => 42,
                'bool' => true,
            ],
        ];
        $user = $this->setUpReactionBackendUser($request, $reactionRecord);
        $GLOBALS['BE_USER'] = $user;
        $request = $request->withHeader('x-api-key', $reactionRecord->toArray()['secret']);

        self::assertCount(0, $this->getTestPages());

        $response = $reaction->react($request, $payload, $reactionRecord);

        self::assertEquals(201, $response->getStatusCode());
        self::assertCount(1, $this->getTestPages());
    }

    #[Test]
    public function reactFailsOnInvalidTable(): void
    {
        $reactionRecord = $this->get(ReactionRepository::class)->getReactionRecordByIdentifier('5d37024e-2af4-4323-abcd-494a9ec37927');
        $reaction = $this->get(CreateRecordReaction::class);
        $request = new ServerRequest('http://localhost/', 'POST');
        $request = $request->withHeader('x-api-key', $reactionRecord->toArray()['secret']);

        $response = $reaction->react($request, [], $reactionRecord);
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('Invalid argument "table_name"', json_decode((string)$response->getBody(), true)['error']);
    }

    #[Test]
    public function reactFailsOnInvalidFields(): void
    {
        $reactionRecord = $this->get(ReactionRepository::class)->getReactionRecordByIdentifier('9d5167ef-d9f6-4d02-9dd3-b132e50492f1');
        $reaction = $this->get(CreateRecordReaction::class);
        $request = new ServerRequest('http://localhost/', 'POST');
        $request = $request->withHeader('x-api-key', $reactionRecord->toArray()['secret']);

        $response = $reaction->react($request, [], $reactionRecord);
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('No fields given.', json_decode((string)$response->getBody(), true)['error']);
    }

    #[Test]
    public function reactReportsThatTheTargetTableRejectedAValue(): void
    {
        // An invalid email address is logged and dropped by DataHandler while the record is
        // still written, so the caller gets a created record with a field it sent missing
        // from it. Without anything in the response, nothing says so.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f50', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertTrue($body['success']);
        self::assertSame(
            ['A value of the record was rejected by the target table and not written'],
            $body['warnings']
        );

        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame('', $pages[0]['author_email']);
    }

    #[Test]
    public function reactKeepsWhatDataHandlerLoggedOutOfTheResponse(): void
    {
        // Whoever holds the secret of a webhook is told that a value was rejected, and no
        // more: the messages DataHandler logs name the page the record went to, the value
        // another record already holds, the authMode a value failed, and the message of
        // the database driver. The detail stays in the log it was written to.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f50', ['foo' => 'bar']);
        $body = (string)$response->getBody();

        self::assertStringNotContainsString('e-mail address', $body);
        self::assertStringNotContainsString('author_email', $body);
        self::assertStringNotContainsString('pages', $body);
        // DataHandler prefixes its log with the type and action it filed the entry under.
        self::assertStringNotContainsString('[1.', $body);
    }

    #[Test]
    public function reactLeavesAFieldTheMapDoesNotSetToTheTargetTable(): void
    {
        // An empty value is how the field map expresses a field the reaction does not write.
        // Passing it on would overrule the default the target table declares, and a select
        // would be answered with 400 because an empty string is not one of its items.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f57', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertArrayNotHasKey('skippedFields', $body);
        self::assertArrayNotHasKey('warnings', $body);

        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(1, (int)$pages[0]['doktype']);
    }

    #[Test]
    public function reactOmitsErrorsWhenDataHandlerRejectedNothing(): void
    {
        $response = $this->dispatchReaction('c6e9c385-d15b-4ae1-9302-931c78f1a458', ['foo' => 'bar']);

        self::assertEquals(201, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        self::assertArrayNotHasKey('skippedFields', $body);
        self::assertArrayNotHasKey('warnings', $body);
    }

    #[Test]
    public function reactResolvesBooleanWordingForCheckboxFields(): void
    {
        // A checkbox column stores a bitmask, and "true" compares greater than that mask as
        // a string, so DataHandler masks it to 0. The box would end up unchecked with no
        // error anywhere, which is indistinguishable from the caller asking for that.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f51', ['foo' => 'bar']);

        self::assertEquals(201, $response->getStatusCode());
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(1, (int)$pages[0]['hidden']);
    }

    #[Test]
    public function reactWritesANumericCheckboxValueTheBitmaskHolds(): void
    {
        // l18n_cfg carries two items, so 3 is a mask the caller may set deliberately. It has
        // to survive the boolean resolution above rather than being read as a wording.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f52', ['foo' => 'bar', 'flag' => '3']);

        self::assertEquals(201, $response->getStatusCode());
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(3, (int)$pages[0]['l18n_cfg']);
    }

    #[DataProvider('reactDoesNotWriteANumericCheckboxValueOutsideTheBitmaskDataProvider')]
    #[Test]
    public function reactDoesNotWriteANumericCheckboxValueOutsideTheBitmask(string $flag): void
    {
        // DataHandler masks a value the bitmask cannot hold away without reporting it, so
        // 7 on the two items of l18n_cfg is stored as 3 and -1 as 0. Either reads as a
        // state the caller asked for, so the field is left to its default instead.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f52', ['foo' => 'bar', 'flag' => $flag]);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['l18n_cfg' => 'valueNotHoldable'], $body['skippedFields']);

        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(0, (int)$pages[0]['l18n_cfg']);
    }

    public static function reactDoesNotWriteANumericCheckboxValueOutsideTheBitmaskDataProvider(): array
    {
        return [
            'above the mask, which DataHandler would store as 3' => ['flag' => '7'],
            'below the mask, which DataHandler would store as 0' => ['flag' => '-1'],
        ];
    }

    #[Test]
    public function reactRefusesABooleanWordingOnAMultiItemCheckbox(): void
    {
        // A wording addresses one checkbox and says nothing about which bit of a mask of
        // several to set. DataHandler masks it away to 0, which reads as an unchecked box
        // rather than as a value it could not use, so the field is left to its default and
        // the caller is told instead.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f53', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['l18n_cfg' => 'valueNotHoldable'], $body['skippedFields']);

        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(0, (int)$pages[0]['l18n_cfg']);
    }

    #[Test]
    public function reactCreatesNoRecordWhenNoFieldOfItCouldBeWritten(): void
    {
        // A field map nobody has filled in yet carries a row per offered field and a value
        // for none of them. Creating the record out of the defaults of the table alone
        // would answer every call of such a reaction with an untitled record.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f58', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertFalse($body['success']);
        self::assertSame('No field of the record could be written', $body['error']);
        self::assertArrayNotHasKey('skippedFields', $body);
        self::assertArrayNotHasKey('warnings', $body);
        self::assertCount(0, $this->getTestPages());
    }

    #[Test]
    public function reactSaysWhyNoFieldOfTheRecordCouldBeWritten(): void
    {
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f59', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertSame(['title' => 'placeholderUnresolved'], $body['skippedFields']);
        self::assertCount(0, $this->getTestPages());
    }

    #[Test]
    public function reactNamesEveryFieldItSkippedWhateverTheReasonWas(): void
    {
        // Every field the reaction cannot write is reported the same way, so the answer does
        // not depend on which of them the map happens to list first.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5b', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        // The reported order follows the stored map, which a JSON column normalises on some
        // database platforms, so only the entries are compared.
        self::assertArrayHasKey('skippedFields', $body);
        $skippedFields = $body['skippedFields'];
        ksort($skippedFields);
        self::assertSame(
            [
                'doktype' => 'valueNotOffered',
                'nav_title' => 'placeholderUnresolved',
            ],
            $skippedFields
        );
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(1, (int)$pages[0]['doktype']);
    }

    #[Test]
    public function reactDoesNotResolveAPlaceholderThePayloadItselfCarries(): void
    {
        // Resolving what a payload value contains would let the caller reach a path the
        // field map addresses elsewhere, by sending a placeholder as the value of another.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5a', [
            'pub' => '${sec}',
            'sec' => 'not-for-the-caller',
        ]);

        self::assertEquals(201, $response->getStatusCode());
        $pages = $this->getTestPages('Test ${sec} not-for-the-caller');
        self::assertCount(1, $pages);
    }

    #[Test]
    public function reactWritesASelectValueTheFieldDeclares(): void
    {
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f54', ['foo' => 'bar', 'type' => '254']);

        self::assertEquals(201, $response->getStatusCode());
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(254, (int)$pages[0]['doktype']);
    }

    #[Test]
    public function reactDoesNotWriteASelectValueTheFieldDoesNotOffer(): void
    {
        // DataHandler compares a select value against nothing, so an unknown one is stored
        // as it stands and the page ends up with a doktype that does not exist. Refusing the
        // whole call instead would make the reaction depend on a list the form composes with
        // more than this can see, so the field is left to its default and reported.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f54', ['foo' => 'bar', 'type' => '9999']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['doktype' => 'valueNotOffered'], $body['skippedFields']);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(1, (int)$pages[0]['doktype']);
    }

    #[Test]
    public function reactWritesASelectValueTheStoragePageAddsToTheField(): void
    {
        // TCEFORM.pages.doktype.addItems on the storage page is part of what the field map
        // offered, so a value only that page declares is one the reaction writes.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f54', ['foo' => 'bar', 'type' => '123']);

        self::assertEquals(201, $response->getStatusCode());
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(123, (int)$pages[0]['doktype']);
    }

    #[Test]
    public function reactDoesNotWriteARadioValueTheFieldDoesNotOffer(): void
    {
        // DataHandler does compare a radio against its items, but drops a value it cannot
        // place without logging anything, so the caller would read the 201 as "all of it
        // landed" while the field stayed at its default.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5d', ['foo' => 'bar', 'flag' => '7']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['mount_pid_ol' => 'valueNotOffered'], $body['skippedFields']);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(0, (int)$pages[0]['mount_pid_ol']);
    }

    #[Test]
    public function reactWritesARadioValueTheFieldOffers(): void
    {
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5d', ['foo' => 'bar', 'flag' => '1']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertArrayNotHasKey('skippedFields', $body);
        self::assertArrayNotHasKey('warnings', $body);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(1, (int)$pages[0]['mount_pid_ol']);
    }

    #[Test]
    public function reactLeavesAFieldUnsetWhenItsPlaceholderResolvesToNothing(): void
    {
        // The payload carries the path but nothing under it. Writing the empty value would
        // overrule the default of the target table, which is what a field the reaction does
        // not write falls back to.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f54', ['foo' => 'bar', 'type' => '']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['doktype' => 'resolvedToNothing'], $body['skippedFields']);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(1, (int)$pages[0]['doktype']);
    }

    #[Test]
    public function reactDoesNotWriteAValueAProcessorRefusesToComposeItemsFor(): void
    {
        // The processor composed the list, so nothing else can say what belongs in it.
        // Letting the value through for want of a list would turn the check off for every
        // field whose processor declines to run outside the form it was written for, which
        // is the case the check exists for.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5e', ['foo' => 'bar', 'mode' => '99']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['layout' => 'valueNotOffered'], $body['skippedFields']);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(0, (int)$pages[0]['layout']);
    }

    #[Test]
    public function reactAnswersAProcessorFailingWithAnErrorRatherThanBreaking(): void
    {
        // ItemProcessingService catches an exception of its own, so only an error reaches
        // the reaction. Catching just \Exception would let it escape and answer the call
        // with a 500 instead of with the field it could not write.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5f', ['foo' => 'bar', 'mode' => '99']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['shortcut_mode' => 'valueNotOffered'], $body['skippedFields']);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(0, (int)$pages[0]['shortcut_mode']);
    }

    #[Test]
    public function reactWritesAValueAFieldDeclaresEvenWhereItsProcessorRefusesToRun(): void
    {
        // The declared items are checked before the processor is run, so a value the field
        // itself names is written without the processor being needed at all.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5e', ['foo' => 'bar', 'mode' => '1']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertArrayNotHasKey('skippedFields', $body);
        self::assertArrayNotHasKey('warnings', $body);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(1, (int)$pages[0]['layout']);
    }

    #[Test]
    public function reactDoesNotWriteACheckboxValueWhereItsProcessorRefusesToRun(): void
    {
        // A checkbox is the one type whose items are not a list of accepted values but a
        // count of boxes, and DataHandler recomposes that count itself rather than taking
        // the value as it is. It runs the same processor to do so and catches nothing, so
        // a value let through here would be answered with a 500 from inside DataHandler.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f62', ['foo' => 'bar', 'flag' => '1']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['php_tree_stop' => 'valueNotHoldable'], $body['skippedFields']);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(0, (int)$pages[0]['php_tree_stop']);
    }

    #[Test]
    public function reactDoesNotWriteAValueAFieldComposingItsItemsDoesNotOffer(): void
    {
        // pages.backend_layout builds its items with an itemsProcFunc, so what the field
        // declares is not the whole list. DataHandler composes them for a radio and a
        // checkbox before comparing, and the same is done here rather than letting anything
        // through for want of a list.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5c', ['foo' => 'bar', 'layout' => 'no-such-layout']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertSame(['backend_layout' => 'valueNotOffered'], $body['skippedFields']);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame('', (string)$pages[0]['backend_layout']);
    }

    #[Test]
    public function reactWritesAValueAFieldComposingItsItemsOffers(): void
    {
        // -1 is contributed by BackendLayoutView rather than declared in the TCA, so a check
        // against the declared items alone would refuse it.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f5c', ['foo' => 'bar', 'layout' => '-1']);

        self::assertEquals(201, $response->getStatusCode());
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame('-1', (string)$pages[0]['backend_layout']);
    }

    #[Test]
    public function reactSkipsKeysTheFieldMapDoesNotOffer(): void
    {
        // perms_everybody reaches DataHandler whatever the TCA says, because its guard
        // admits every new record. fe_group is a relation, and nonsense is no field at all.
        // None of the three can be configured through the form, so a record that carries
        // them was pointed at another table or edited by hand, and none may be written.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f55', ['foo' => 'bar']);

        self::assertEquals(201, $response->getStatusCode());
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertNotSame(31, (int)$pages[0]['perms_everybody']);
        self::assertNotSame('1', (string)$pages[0]['fe_group']);

        // Skipping them silently would leave the caller reading a 201 as "all of it landed".
        // The reported order follows the stored map, which a JSON column normalises on some
        // database platforms, so only the entries are compared.
        $body = json_decode((string)$response->getBody(), true);
        self::assertArrayHasKey('skippedFields', $body);
        $skippedFields = $body['skippedFields'];
        ksort($skippedFields);
        self::assertSame(
            [
                'fe_group' => 'notMappable',
                'nonsense' => 'notMappable',
                'perms_everybody' => 'notMappable',
            ],
            $skippedFields
        );
    }

    #[Test]
    public function reactWritesALinkTakenFromThePayload(): void
    {
        // A link is a single string the map can carry, and the one field type a caller is
        // as likely to send as a title.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f61', [
            'foo' => 'bar',
            'target' => 'https://example.org/',
        ]);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertArrayNotHasKey('skippedFields', $body);
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame('https://example.org/', (string)$pages[0]['link']);
    }

    #[Test]
    public function reactSaysNothingAboutStaleKeysThatCarryNothing(): void
    {
        // The form submits a row per offered text field, so a map keeps an empty key for
        // every field of the table it was configured for. Pointing the reaction at another
        // table would otherwise report each of them on every single call, while none of
        // them was ever going to be written.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f60', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertArrayNotHasKey('skippedFields', $body);
        self::assertCount(1, $this->getTestPages());
    }

    #[Test]
    public function reactLeavesAFieldUnsetWhenItsPlaceholderDoesNotResolve(): void
    {
        // Writing the placeholder as it stands would store the literal "${nothing.here}"
        // in doktype. The field is left out of the datamap instead, so the table's own
        // default applies.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f56', ['foo' => 'bar']);

        self::assertEquals(201, $response->getStatusCode());
        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame(1, (int)$pages[0]['doktype']);

        self::assertSame(
            ['doktype' => 'placeholderUnresolved'],
            json_decode((string)$response->getBody(), true)['skippedFields']
        );
    }

    #[Test]
    public function reactWritesOnlyWhatTheImpersonatedUserIsAllowedTo(): void
    {
        // The reaction runs as the user it impersonates, and that user is not the one who
        // configured the map: reaction 29 is written by an editor granted pages:nav_title
        // and nothing else. DataHandler drops the two fields the editor may not write
        // without logging anything, so the caller would read the 201 as "all of it landed"
        // while subtitle and TSconfig kept their defaults.
        $this->writeSiteConfigurationForTheStoragePage();

        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f63', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        // The reported order follows the stored map, which a JSON column normalises on some
        // database platforms, so only the entries are compared.
        self::assertArrayHasKey('skippedFields', $body);
        $skippedFields = $body['skippedFields'];
        ksort($skippedFields);
        self::assertSame(
            [
                // Shown to admins only, and the editor is none.
                'TSconfig' => 'notPermitted',
                // Declares "exclude" and is not in the non_exclude_fields of the editor.
                'subtitle' => 'notPermitted',
            ],
            $skippedFields
        );

        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame('Nav bar', (string)$pages[0]['nav_title']);
        self::assertSame('', (string)$pages[0]['subtitle']);
        self::assertSame('', (string)$pages[0]['TSconfig']);
    }

    #[Test]
    public function reactWritesTheSameMapInFullForAnImpersonatedAdmin(): void
    {
        // Reaction 30 carries the map of the one above and impersonates an admin instead.
        // Nothing about the map decides what is written, so the same three fields land.
        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f64', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(201, $response->getStatusCode());
        self::assertArrayNotHasKey('skippedFields', $body);
        self::assertArrayNotHasKey('warnings', $body);

        $pages = $this->getTestPages();
        self::assertCount(1, $pages);
        self::assertSame('Nav bar', (string)$pages[0]['nav_title']);
        self::assertSame('Sub bar', (string)$pages[0]['subtitle']);
        self::assertSame('foo = bar', (string)$pages[0]['TSconfig']);
    }

    #[Test]
    public function reactCreatesNoRecordWhereTheImpersonatedUserMayWriteNoFieldOfIt(): void
    {
        // Every field of the map is refused, so what is left is the defaults of the table
        // alone. Writing the record out of those would answer the call with a page the
        // caller asked nothing about.
        $this->writeSiteConfigurationForTheStoragePage();

        $response = $this->dispatchReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f65', ['foo' => 'bar']);
        $body = json_decode((string)$response->getBody(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertFalse($body['success']);
        self::assertSame('No field of the record could be written', $body['error']);
        self::assertSame(['subtitle' => 'notPermitted'], $body['skippedFields']);
        self::assertCount(0, $this->getTestPages('Test bar'));
        self::assertCount(0, $this->getTestPages('Sub bar'));
    }

    #[Test]
    public function theFieldMapIsShownInAPaletteOfItsOwn(): void
    {
        // A column in a palette no showitem names is a field nobody can reach, so the two
        // halves are asserted together.
        $tca = $GLOBALS['TCA']['sys_reaction'];

        self::assertSame('fields', $tca['palettes']['createRecordFieldMap']['showitem']);
        self::assertStringContainsString(
            '--palette--;;createRecordFieldMap',
            $tca['types'][CreateRecordReaction::getType()]['showitem']
        );
        self::assertStringNotContainsString('fields', $tca['palettes']['createRecord']['showitem']);
    }

    /**
     * A record written by a user who is no admin needs a language it may use, and
     * DataHandler takes that from the site of the page the record lands on. Without one it
     * refuses the record before any field of it is looked at, which is not what the tests
     * impersonating an editor are about.
     */
    private function writeSiteConfigurationForTheStoragePage(): void
    {
        $this->get(SiteWriter::class)->write('reactions', [
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'locale' => 'en_US.UTF-8',
                    'flag' => 'us',
                ],
            ],
        ]);
    }

    private function dispatchReaction(string $identifier, array $payload): ResponseInterface
    {
        $reactionRecord = $this->get(ReactionRepository::class)->getReactionRecordByIdentifier($identifier);
        $request = new ServerRequest('http://localhost/', 'POST');
        $GLOBALS['BE_USER'] = $this->setUpReactionBackendUser($request, $reactionRecord);
        $request = $request->withHeader('x-api-key', $reactionRecord->toArray()['secret']);

        return $this->get(CreateRecordReaction::class)->react($request, $payload, $reactionRecord);
    }

    private function getTestPages(string $title = 'Test bar'): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(new DeletedRestriction());
        return $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($title)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(1))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function setUpReactionBackendUser(ServerRequestInterface $request, ReactionInstruction $reactionInstruction): BackendUserAuthentication
    {
        $backendUser = GeneralUtility::makeInstance(ReactionUserAuthentication::class);
        /** @var ReactionUserAuthentication $backendUser */
        $backendUser->setReactionInstruction($reactionInstruction);
        return $this->authenticateBackendUser($backendUser, $request);
    }

    public static function replacePlaceHolderDataProvider(): array
    {
        return [
            'no placeholders' => [
                'value' => 'foo',
                'payload' => [],
                'expected' => 'foo',
            ],
            'placeholder in value' => [
                'value' => '${foo}',
                'payload' => [
                    'foo' => 'bar',
                ],
                'expected' => 'bar',
            ],
            'placeholder in value is integer' => [
                'value' => '${foo}',
                'payload' => [
                    'foo' => 42,
                ],
                'expected' => '42',
            ],
            'placeholder in value is float' => [
                'value' => '${foo}',
                'payload' => [
                    'foo' => 42.5,
                ],
                'expected' => '42.5',
            ],
            'placeholder in value is boolean true' => [
                'value' => '${foo}',
                'payload' => [
                    'foo' => true,
                ],
                'expected' => '1',
            ],
            'placeholder in value is boolean false' => [
                'value' => '${foo}',
                'payload' => [
                    'foo' => false,
                ],
                'expected' => '',
            ],
            'two placeholder in value' => [
                'value' => '${foo} ${bar}',
                'payload' => [
                    'foo' => 'bar',
                    'bar' => 'foo',
                ],
                'expected' => 'bar foo',
            ],
            'placeholder in value with dot' => [
                'value' => '${foo.bar}',
                'payload' => [
                    'foo' => [
                        'bar' => 'baz',
                    ],
                ],
                'expected' => 'baz',
            ],
            'placeholder in value with dot and array access' => [
                'value' => '${foo.bar.0}',
                'payload' => [
                    'foo' => [
                        'bar' => [
                            '0' => 'baz',
                        ],
                    ],
                ],
                'expected' => 'baz',
            ],
            'placeholder in value with dot and numeric array access' => [
                'value' => '${foo.bar.0}',
                'payload' => [
                    'foo' => [
                        'bar' => [
                            'baz',
                        ],
                    ],
                ],
                'expected' => 'baz',
            ],
            'placeholder in value with dot and array access and array access in value' => [
                'value' => '${foo.bar.0.baz}',
                'payload' => [
                    'foo' => [
                        'bar' => [
                            '0' => [
                                'baz' => 'qux',
                            ],
                        ],
                    ],
                ],
                'expected' => 'qux',
            ],
            'placeholder in value with dot and array access and numeric array access in value' => [
                'value' => '${foo.bar.0.baz}',
                'payload' => [
                    'foo' => [
                        'bar' => [
                            [
                                'baz' => 'qux',
                            ],
                        ],
                    ],
                ],
                'expected' => 'qux',
            ],
            'placeholder in value with dot and array access and array access in value and array access in value' => [
                'value' => '${foo.bar.0.baz.0}',
                'payload' => [
                    'foo' => [
                        'bar' => [
                            '0' => [
                                'baz' => [
                                    '0' => 'qux',
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => 'qux',
            ],
            'placeholder in value with dot and array numeric access and numeric array access in value and numeric array access in value' => [
                'value' => '${foo.bar.0.baz.0}',
                'payload' => [
                    'foo' => [
                        'bar' => [
                            [
                                'baz' => [
                                    'qux',
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => 'qux',
            ],
            'placeholder in value with dot and array access and array access in value and array access in value and array access in value' => [
                'value' => '${foo.bar.0.baz.0.qux}',
                'payload' => [
                    'foo' => [
                        'bar' => [
                            '0' => [
                                'baz' => [
                                    '0' => [
                                        'qux' => 'quux',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => 'quux',
            ],
            'placeholder in value with dot and array access and numeric array access in value and numeric array access in value and numeric array access in value' => [
                'value' => '${foo.bar.0.baz.0.qux}',
                'payload' => [
                    'foo' => [
                        'bar' => [
                            [
                                'baz' => [
                                    [
                                        'qux' => 'quux',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => 'quux',
            ],
            'placeholder resolving to an array is unresolvable' => [
                'value' => '${foo}',
                'payload' => [
                    'foo' => ['bar', 'baz'],
                ],
                'expected' => null,
            ],
            'placeholder resolving to null is unresolvable' => [
                'value' => '${foo}',
                'payload' => [
                    'foo' => null,
                ],
                'expected' => null,
            ],
            'placeholder the payload does not carry is unresolvable' => [
                'value' => '${foo}',
                'payload' => [],
                'expected' => null,
            ],
            'one unresolvable placeholder makes the whole value unresolvable' => [
                'value' => 'Prefix ${foo} and ${bar}',
                'payload' => [
                    'foo' => 'resolved',
                ],
                'expected' => null,
            ],
            'configured value that is an integer is cast' => [
                'value' => 42,
                'payload' => [],
                'expected' => '42',
            ],
            'configured value that has no string form is unresolvable' => [
                'value' => ['foo' => 'bar'],
                'payload' => [],
                'expected' => null,
            ],
            'placeholder naming no path at all is unresolvable' => [
                'value' => '${}',
                'payload' => ['foo' => 'bar'],
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('replacePlaceHolderDataProvider')]
    #[Test]
    public function replacePlaceHolders(mixed $value, array $payload, ?string $expected): void
    {
        $subject = $this->get(CreateRecordReaction::class);
        self::assertSame($expected, $subject->replacePlaceHolders($value, $payload));
    }
}
