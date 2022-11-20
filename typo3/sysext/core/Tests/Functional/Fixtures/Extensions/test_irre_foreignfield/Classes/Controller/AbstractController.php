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

namespace TYPO3\TestIrreForeignfield\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\TestIrreForeignfield\Service\QueueService;

/**
 * ContentController
 */
abstract class AbstractController extends ActionController
{
    protected DataMapFactory $dataMapFactory;
    protected QueueService $queueService;

    public function __construct(
        DataMapFactory $dataMapFactory,
        QueueService $queueService
    ) {
        $this->dataMapFactory = $dataMapFactory;
        $this->queueService = $queueService;
    }

    /**
     * @throws \RuntimeException
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return parent::processRequest($request);
        } catch (Exception $exception) {
            throw new \RuntimeException(
                $this->getRuntimeIdentifier() . ': ' . $exception->getMessage() . ' (' . $exception->getCode() . ')',
                1476049553
            );
        }
    }

    /**
     * @param \Iterator|AbstractEntity[] $iterator
     */
    protected function getStructure($iterator): array
    {
        $structure = [];

        if (!$iterator instanceof \Iterator) {
            $iterator = [$iterator];
        }

        foreach ($iterator as $entity) {
            $dataMap = $this->dataMapFactory->buildDataMap(get_class($entity));
            $tableName = $dataMap->getTableName();
            $identifier = $tableName . ':' . $entity->getUid();
            $properties = ObjectAccess::getGettableProperties($entity);

            $structureItem = [];
            foreach ($properties as $propertyName => $propertyValue) {
                $columnMap = $dataMap->getColumnMap($propertyName);
                if ($columnMap !== null) {
                    $propertyName = $columnMap->getColumnName();
                }
                if ($propertyValue instanceof \Iterator) {
                    $structureItem[$propertyName] = $this->getStructure($propertyValue);
                } else {
                    $structureItem[$propertyName] = $propertyValue;
                }
            }
            $structure[$identifier] = $structureItem;
        }

        return $structure;
    }

    /**
     * @param mixed $value
     */
    protected function process($value)
    {
        if ($this->queueService->isActive()) {
            $this->queueService->addValue($this->getRuntimeIdentifier(), $value);
            return (new ForwardResponse('process'))->withControllerName('Queue');
        }
        $this->view->assign('value', $value);
        return $this->responseFactory->createResponse()
            ->withBody($this->streamFactory->createStream($this->view->render()));
    }

    protected function getRuntimeIdentifier(): string
    {
        $arguments = [];
        foreach ($this->request->getArguments() as $argumentName => $argumentValue) {
            $arguments[] = $argumentName . '=' . $argumentValue;
        }
        return $this->request->getControllerActionName() . '(' . implode(', ', $arguments) . ')';
    }
}
