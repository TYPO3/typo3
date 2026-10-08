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

namespace TYPO3\CMS\Reactions\Tests\Functional\Http\Middleware;

use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\ConnectionLost;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteResult;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Reactions\Http\Middleware\ReactionResolver;
use TYPO3\CMS\Reactions\Http\ReactionHandler;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;
use TYPO3\CMS\Reactions\ReactionRegistry;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ReactionResolverTest extends FunctionalTestCase
{
    private const ACTIVE = '8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f01';
    private const DISABLED = '8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f02';
    private const ORPHANED = '8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f03';
    private const NO_FIELDS = '8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f04';
    private const NOW = 1699963200;

    protected array $coreExtensionsToLoad = ['reactions'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ReactionsRepositoryTest_pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ReactionResolverTest_reactions.csv');
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_reaction')->update(
            'sys_reaction',
            ['secret' => $this->get(PasswordHashFactory::class)->getDefaultHashInstance('BE')->getHashedPassword('secret')],
            ['deleted' => 0]
        );
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . self::NOW)));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    #[Test]
    public function successfulCallIsRecorded(): void
    {
        $response = $this->callReaction(self::ACTIVE);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['last_called' => self::NOW, 'last_status' => 201, 'last_failure' => ''], $this->getLastCall(1));
    }

    #[Test]
    public function callWithAWrongSecretIsNotRecorded(): void
    {
        $response = $this->callReaction(self::ACTIVE, 'wrong');

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(0, $this->countRecordedCalls());
    }

    #[Test]
    public function successfulCallClearsTheReasonOfAPreviousFailure(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_reaction')
            ->update('sys_reaction', ['last_called' => 1, 'last_status' => 400, 'last_failure' => 'No fields given.'], ['uid' => 1]);

        $this->callReaction(self::ACTIVE);

        self::assertSame(['last_called' => self::NOW, 'last_status' => 201, 'last_failure' => ''], $this->getLastCall(1));
    }

    #[Test]
    public function callOfAnUnknownReactionIsNotRecorded(): void
    {
        $response = $this->callReaction('8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4fff');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(0, $this->countRecordedCalls());
    }

    #[Test]
    public function callOfADisabledReactionIsNotRecorded(): void
    {
        $response = $this->callReaction(self::DISABLED);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(0, $this->countRecordedCalls());
    }

    #[Test]
    public function callWithoutASecretIsNotRecorded(): void
    {
        $response = $this->callReaction(self::ACTIVE, '');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(0, $this->countRecordedCalls());
    }

    #[Test]
    public function failedCallIsRecordedWithTheErrorTheCallerGot(): void
    {
        $response = $this->callReaction(self::NO_FIELDS);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['last_called' => self::NOW, 'last_status' => 400, 'last_failure' => 'No fields given.'], $this->getLastCall(4));
    }

    #[Test]
    public function callOfAReactionWithAnUnregisteredTypeIsRecorded(): void
    {
        $response = $this->callReaction(self::ORPHANED);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(['last_called' => self::NOW, 'last_status' => 404, 'last_failure' => 'No reaction found for given reaction type'], $this->getLastCall(3));
    }

    #[Test]
    public function recordingTheCallLeavesTheModificationDateAndHistoryAlone(): void
    {
        $this->callReaction(self::NO_FIELDS);

        $row = $this->get(ConnectionPool::class)->getConnectionForTable('sys_reaction')
            ->select(['updatedon'], 'sys_reaction', ['uid' => 4])->fetchAssociative();
        self::assertSame(1, (int)$row['updatedon']);
        self::assertSame(0, $this->get(ConnectionPool::class)->getConnectionForTable('sys_history')->count('*', 'sys_history', ['tablename' => 'sys_reaction']));
    }

    #[Test]
    public function callerStillReceivesTheWholeBodyTheReasonWasReadFrom(): void
    {
        $body = (string)json_encode(['success' => false, 'error' => 'Something specific']);

        $response = $this->callReactionReturning($this->createJsonResponse(422, $body));

        self::assertSame($body, $response->getBody()->getContents());
        self::assertSame(['last_called' => self::NOW, 'last_status' => 422, 'last_failure' => 'Something specific'], $this->getLastCall(1));
    }

    #[Test]
    public function reasonIsShortenedTo255Characters(): void
    {
        $this->callReactionReturning($this->createJsonResponse(400, (string)json_encode(['error' => str_repeat('ä', 300)])));

        self::assertSame(str_repeat('ä', 254) . '…', $this->getLastCall(1)['last_failure']);
    }

    #[Test]
    public function reasonOf255CharactersIsKeptAsItIs(): void
    {
        $this->callReactionReturning($this->createJsonResponse(400, (string)json_encode(['error' => str_repeat('ä', 255)])));

        self::assertSame(str_repeat('ä', 255), $this->getLastCall(1)['last_failure']);
    }

    #[Test]
    public function reasonIsOnlyTakenFromAJsonBody(): void
    {
        $response = $this->get(ResponseFactoryInterface::class)->createResponse(400)
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($this->get(StreamFactoryInterface::class)->createStream('{"error":"Not JSON"}'));

        $this->callReactionReturning($response);

        self::assertSame(['last_called' => self::NOW, 'last_status' => 400, 'last_failure' => ''], $this->getLastCall(1));
    }

    #[Test]
    public function reactionThatThrowsIsRecordedAsFailedAndTheExceptionPassedOn(): void
    {
        $exception = new \RuntimeException('Broken', 1791465817);
        $reaction = $this->createReaction(static fn(): never => throw $exception);
        $logger = $this->createRecordingLogger();

        try {
            $this->createResolver($reaction, $logger)->process($this->createRequest(self::ACTIVE), $this->createFailingHandler());
            self::fail('The exception of the reaction was swallowed');
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }

        self::assertSame(['last_called' => self::NOW, 'last_status' => 500, 'last_failure' => 'The reaction failed unexpectedly'], $this->getLastCall(1));
        $warnings = array_values(array_filter($logger->records, static fn(array $record): bool => $record['level'] === 'warning'));
        self::assertCount(1, $warnings);
        self::assertSame('Active Reaction', $warnings[0]['context']['name']);
        self::assertSame(self::ACTIVE, $warnings[0]['context']['identifier']);
        self::assertSame('create-record', $warnings[0]['context']['type']);
        self::assertSame($exception, $warnings[0]['context']['exception']);
    }

    #[Test]
    public function responseThatIsPropagatedIsRecordedAndPassedOn(): void
    {
        $exception = new PropagateResponseException($this->createJsonResponse(403, (string)json_encode(['error' => 'Propagated'])), 1791467000);
        $reaction = $this->createReaction(static fn(): never => throw $exception);

        try {
            $this->createResolver($reaction)->process($this->createRequest(self::ACTIVE), $this->createFailingHandler());
            self::fail('The propagated response was swallowed');
        } catch (PropagateResponseException $e) {
            self::assertSame($exception, $e);
            self::assertSame('{"error":"Propagated"}', $e->getResponse()->getBody()->getContents());
        }

        self::assertSame(['last_called' => self::NOW, 'last_status' => 403, 'last_failure' => 'Propagated'], $this->getLastCall(1));
    }

    #[Test]
    public function failureToRecordTheCallIsLoggedAndLeavesTheResponseAlone(): void
    {
        $repository = new class ($this->get(ConnectionPool::class), $this->get(Context::class)) extends ReactionRepository {
            public function updateLastCall(int $uid, int $statusCode, string $failure): void
            {
                $exception = new ConnectionLost(PdoDriverException::new(new \PDOException('gone')), null);
                throw $exception;
            }
        };
        $logger = $this->createRecordingLogger();
        $body = (string)json_encode(['success' => true]);

        $response = $this->createResolver($this->createReaction(fn(): ResponseInterface => $this->createJsonResponse(201, $body)), $logger, $repository)
            ->process($this->createRequest(self::ACTIVE), $this->createFailingHandler());

        self::assertSame(201, $response->getStatusCode());
        self::assertSame($body, (string)$response->getBody());
        $warnings = array_values(array_filter($logger->records, static fn(array $record): bool => $record['level'] === 'warning'));
        self::assertCount(1, $warnings);
        self::assertSame('Active Reaction', $warnings[0]['context']['name']);
        self::assertSame(self::ACTIVE, $warnings[0]['context']['identifier']);
    }

    /**
     * @return AbstractLogger&object{records: list<array{level: mixed, message: string, context: array<string, mixed>}>}
     */
    private function createRecordingLogger(): AbstractLogger
    {
        return new class extends AbstractLogger {
            public array $records = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string)$message, 'context' => $context];
            }
        };
    }

    private function callReaction(string $identifier, string $secret = 'secret'): ResponseInterface
    {
        return $this->get(ReactionResolver::class)->process($this->createRequest($identifier, $secret), $this->createFailingHandler());
    }

    private function callReactionReturning(ResponseInterface $response): ResponseInterface
    {
        return $this->createResolver($this->createReaction(static fn(): ResponseInterface => $response))
            ->process($this->createRequest(self::ACTIVE), $this->createFailingHandler());
    }

    private function createResolver(ReactionInterface $reaction, ?LoggerInterface $logger = null, ?ReactionRepository $repository = null): ReactionResolver
    {
        $reactionHandler = new ReactionHandler(
            new ReactionRegistry(new \ArrayObject(['create-record' => $reaction])),
            new NullLogger(),
            $this->get(LanguageServiceFactory::class)
        );
        return new ReactionResolver(
            $logger ?? new NullLogger(),
            $reactionHandler,
            $repository ?? $this->get(ReactionRepository::class),
            $this->get(ResponseFactoryInterface::class),
            $this->get(StreamFactoryInterface::class)
        );
    }

    private function createReaction(\Closure $react): ReactionInterface
    {
        return new readonly class ($react) implements ReactionInterface {
            public function __construct(private \Closure $react) {}

            public static function getType(): string
            {
                return 'create-record';
            }

            public static function getDescription(): string
            {
                return '';
            }

            public static function getIconIdentifier(): string
            {
                return '';
            }

            public function react(ServerRequestInterface $request, array $payload, ReactionInstruction $reaction): ResponseInterface
            {
                return ($this->react)();
            }
        };
    }

    private function createJsonResponse(int $statusCode, string $body): ResponseInterface
    {
        $stream = $this->get(StreamFactoryInterface::class)->createStream($body);
        $stream->rewind();
        return $this->get(ResponseFactoryInterface::class)->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    private function createRequest(string $identifier, string $secret = 'secret'): ServerRequest
    {
        $request = new ServerRequest('https://example.com/typo3/reaction/' . $identifier, 'POST')
            ->withAttribute('routing', new RouteResult(
                new Route('/reaction/{reactionIdentifier?}', ['_identifier' => 'reaction']),
                ['reactionIdentifier' => $identifier]
            ))
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->get(StreamFactoryInterface::class)->createStream('{"foo":"bar"}'));
        return $secret !== '' ? $request->withHeader('x-api-key', $secret) : $request;
    }

    private function createFailingHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('A reaction request must not be passed on', 1791465818);
            }
        };
    }

    /**
     * @return array{last_called: int, last_status: int, last_failure: string}
     */
    private function getLastCall(int $uid): array
    {
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('sys_reaction')
            ->select(['last_called', 'last_status', 'last_failure'], 'sys_reaction', ['uid' => $uid])
            ->fetchAssociative();
        return [
            'last_called' => (int)$row['last_called'],
            'last_status' => (int)$row['last_status'],
            'last_failure' => (string)$row['last_failure'],
        ];
    }

    private function countRecordedCalls(): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_reaction');
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder->count('*')
            ->from('sys_reaction')
            ->where($queryBuilder->expr()->gt('last_called', 0))
            ->executeQuery()
            ->fetchOne();
    }
}
