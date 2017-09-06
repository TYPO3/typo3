<?php
namespace ExtbaseTeam\BlogExample\Controller;

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

/**
 * ContentController
 */
class ContentController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @inject
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\TtContentRepository
     */
    protected $contentRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Extbase\Mvc\View\JsonView::class;

    /**
     * @inject
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     */
    protected $dataMapFactory;

    /**
     * @return array
     */
    public function listAction()
    {
        $content = $this->contentRepository->findAll();
        $value[$this->getRuntimeIdentifier()] = $this->getStructure($content);
        // this is required so we don't try to json_encode content of the image
        $this->view->setConfiguration(['value' => [
            '_descendAll' => [
                '_descendAll' => [
                    '_descendAll' => [
                        '_descendAll' => [
                            '_descendAll' => [
                                '_exclude' => ['contents']
                            ]
                        ]
                    ]
                ]
            ]
        ]]);
        $this->view->assign('value', $value);
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
     * @throws \RuntimeException
     */
    public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        try {
            parent::processRequest($request, $response);
        } catch (\TYPO3\CMS\Extbase\Property\Exception $exception) {
            throw new \RuntimeException(
                $this->getRuntimeIdentifier() . ': ' . $exception->getMessage() . ' (' . $exception->getCode() . ')',
                1476122223
            );
        }
    }

    /**
     * @param \Iterator|\TYPO3\CMS\Extbase\DomainObject\AbstractEntity[] $iterator
     * @return array
     */
    protected function getStructure($iterator)
    {
        $structure = [];

        if (!$iterator instanceof \Iterator) {
            $iterator = [$iterator];
        }

        foreach ($iterator as $entity) {
            $dataMap = $this->dataMapFactory->buildDataMap(get_class($entity));
            $tableName = $dataMap->getTableName();
            $identifier = $tableName . ':' . $entity->getUid();
            $properties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettableProperties($entity);

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
            if ($entity instanceof \TYPO3\CMS\Extbase\Domain\Model\FileReference
                && isset($structureItem['originalResource'])
                && $structureItem['originalResource'] instanceof \TYPO3\CMS\Core\Resource\FileReference
            ) {
                $structureItem = $structureItem['originalResource']->getProperties();
            }
            $structure[$identifier] = $structureItem;
        }

        return $structure;
    }

    /**
     * @return string
     */
    protected function getRuntimeIdentifier()
    {
        $arguments = [];
        foreach ($this->request->getArguments() as $argumentName => $argumentValue) {
            $arguments[] = $argumentName . '=' . $argumentValue;
        }
        return $this->request->getControllerActionName() . '(' . implode(', ', $arguments) . ')';
    }
}
