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

namespace TYPO3Tests\BlogExample\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3Tests\BlogExample\Domain\Repository\TtContentRepository;

class ContentController extends ActionController
{
    public function __construct(
        private readonly TtContentRepository $contentRepository,
        private readonly DataMapFactory $dataMapFactory
    ) {
        $this->defaultViewObjectName = JsonView::class;
    }

    public function listAction(): ResponseInterface
    {
        $content = $this->contentRepository->findAll();
        $value = [];
        $value[$this->getRuntimeIdentifier()] = $this->getStructure($content);
        // this is required, so we don't try to json_encode content of the image
        /** @var JsonView $view */
        $view = $this->view;
        $view->setConfiguration([
            'value' => [
                '_descendAll' => [
                    '_descendAll' => [
                        '_descendAll' => [
                            '_descendAll' => [
                                '_descendAll' => [
                                    '_exclude' => ['contents'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $view->assign('value', $value);

        return $this->jsonResponse();
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
                1476122223
            );
        }
    }

    /**
     * @param \Iterator|\TYPO3\CMS\Extbase\DomainObject\AbstractEntity[] $iterator
     */
    protected function getStructure(\Iterator|array $iterator): array
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
            //let's flatten the structure and put file reference properties level up, so we can use StructureHasRecordConstraint
            if ($entity instanceof FileReference
                && isset($structureItem['originalResource'])
                && $structureItem['originalResource'] instanceof \TYPO3\CMS\Core\Resource\FileReference
            ) {
                $structureItem = $structureItem['originalResource']->getProperties();
            }
            $structure[$identifier] = $structureItem;
        }

        return $structure;
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
