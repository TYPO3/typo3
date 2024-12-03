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

namespace TYPO3Tests\TestIrreForeignfield\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3Tests\TestIrreForeignfield\Domain\Model\Content;
use TYPO3Tests\TestIrreForeignfield\Domain\Repository\ContentRepository;
use TYPO3Tests\TestIrreForeignfield\Service\QueueService;

class ContentController extends ActionController
{
    public function __construct(
        private readonly DataMapFactory $dataMapFactory,
        private readonly QueueService $queueService,
        private readonly ContentRepository $contentRepository,
    ) {
        $this->defaultViewObjectName = JsonView::class;
    }

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

    public function listAction(): ResponseInterface
    {
        $contents = $this->contentRepository->findAll();
        $value = $this->getStructure($contents);
        return $this->process($value);
    }

    public function showAction(Content $content): ResponseInterface
    {
        $value = $this->getStructure($content);
        return $this->process($value);
    }

    /**
     * @param \Iterator|DomainObjectInterface[]|DomainObjectInterface $iterator
     */
    private function getStructure($iterator): array
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

    private function process(array $value): ResponseInterface
    {
        if ($this->queueService->isActive()) {
            $this->queueService->addValue($this->getRuntimeIdentifier(), $value);
            return (new ForwardResponse('process'))->withControllerName('Queue');
        }
        $this->view->assign('value', $value);
        return $this->responseFactory->createResponse()
            ->withBody($this->streamFactory->createStream($this->view->render()));
    }

    private function getRuntimeIdentifier(): string
    {
        $arguments = [];
        foreach ($this->request->getArguments() as $argumentName => $argumentValue) {
            $arguments[] = $argumentName . '=' . $argumentValue;
        }
        return $this->request->getControllerActionName() . '(' . implode(', ', $arguments) . ')';
    }

}
