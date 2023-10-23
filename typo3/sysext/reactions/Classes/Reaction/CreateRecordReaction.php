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

namespace TYPO3\CMS\Reactions\Reaction;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Validation\CreateRecordReactionTable;

/**
 * A reaction that creates a database record based on the payload within a request.
 *
 * @internal This is a specific reaction implementation and is not considered part of the Public TYPO3 API.
 */
class CreateRecordReaction implements ReactionInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public static function getType(): string
    {
        return 'create-record';
    }

    public static function getDescription(): string
    {
        return 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:sys_reaction.reaction_type.create_record';
    }

    public static function getIconIdentifier(): string
    {
        return 'content-database';
    }

    public function react(ServerRequestInterface $request, array $payload, ReactionInstruction $reaction): ResponseInterface
    {
        // @todo: Response needs to be based on given accept headers

        $table = (string)($reaction->toArray()['table_name'] ?? '');
        $fields = (array)($reaction->toArray()['fields'] ?? []);

        if (!(new CreateRecordReactionTable($table))->isAllowedForCreation()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid argument "table_name"'], 400);
        }

        if ($fields === []) {
            return $this->jsonResponse(['success' => false, 'error' => 'No fields given.'], 400);
        }

        $dataHandlerData = [];
        foreach ($fields as $fieldName => $value) {
            $dataHandlerData[$fieldName] = $this->replacePlaceHolders($value, $payload);
        }
        $dataHandlerData['pid'] = (int)($reaction->toArray()['storage_pid'] ?? 0);

        $data[$table][StringUtility::getUniqueId('NEW')] = $dataHandlerData;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, [], $this->getBackendUser());
        $dataHandler->process_datamap();

        return $this->buildResponseFromDataHandler($dataHandler, 201);
    }

    /**
     * @internal only public due to tests
     */
    public function replacePlaceHolders(mixed $value, array $payload): string
    {
        if (is_string($value)) {
            $re = '/\$\{([^\}]*)\}/m';
            preg_match_all($re, $value, $matches, PREG_SET_ORDER, 0);
            foreach ($matches as $match) {
                try {
                    $value = str_replace($match[0], (string)ArrayUtility::getValueByPath($payload, $match[1], '.'), $value);
                } catch (MissingArrayPathException) {
                    // Ignore this exception to show the user that there was no placeholder in the payload
                }
            }
        }
        return $value;
    }

    protected function buildResponseFromDataHandler(DataHandler $dataHandler, int $successCode = 200): ResponseInterface
    {
        // Success depends on whether at least one NEW id has been substituted
        $success = $dataHandler->substNEWwithIDs !== [] && $dataHandler->substNEWwithIDs_table !== [];

        $statusCode = $successCode;
        $data = [
            'success' => $success,
        ];

        if (!$success) {
            $statusCode = 400;
            $data['error'] = 'Record could not be created';
        }

        return $this->jsonResponse($data, $statusCode);
    }

    protected function jsonResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream((string)json_encode($data)));
    }

    private function getBackendUser(): ReactionUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
