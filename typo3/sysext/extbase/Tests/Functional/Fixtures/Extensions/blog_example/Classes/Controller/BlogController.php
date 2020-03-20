<?php

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

namespace ExtbaseTeam\BlogExample\Controller;

use ExtbaseTeam\BlogExample\Domain\Model\Blog;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * BlogController
 */
class BlogController extends ActionController
{
    /**
     * @Extbase\Inject
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository
     */
    public $blogRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    /**
     * @Extbase\Inject
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     */
    public $dataMapFactory;

    public function listAction()
    {
        $blogs = $this->blogRepository->findAll();
        $value = [];
        $value[$this->getRuntimeIdentifier()] = $this->getStructure($blogs);

        $this->view->assign('value', $value);
    }

    public function detailsAction(Blog $blog=null)
    {
        return $blog ? $blog->getTitle() : '';
    }

    /**
     * @return string
     */
    public function testFormAction()
    {
        return 'testFormAction';
    }

    /**
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post $blogPost
     * // needs to be imported entirely, else the annotationChecker script of bamboo will complain
     * @IgnoreValidation("blogPost")
     */
    public function testForwardAction($blogPost)
    {
        $this->forward('testForwardTarget', null, null, ['blogPost' => $blogPost]);
    }

    /**
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post $blogPost
     * @return string
     */
    public function testForwardTargetAction($blogPost)
    {
        return 'testForwardTargetAction';
    }

    /**
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Blog $blog
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post $blogPost
     * @return string
     */
    public function testRelatedObjectAction($blog, $blogPost = null)
    {
        return 'testRelatedObject';
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
     * @throws \RuntimeException
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        try {
            parent::processRequest($request, $response);
        } catch (Exception $exception) {
            throw new \RuntimeException(
                $this->getRuntimeIdentifier() . ': ' . $exception->getMessage() . ' (' . $exception->getCode() . ')',
                1476122222
            );
        }
    }

    /**
     * Disable the default error flash message, otherwise we get an error because the flash message
     * session handling is not available during functional tests.
     *
     * @return bool
     */
    protected function getErrorFlashMessage()
    {
        return false;
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
