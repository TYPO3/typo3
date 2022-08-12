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

namespace ExtbaseTeam\BlogExample\Controller;

use ExtbaseTeam\BlogExample\Domain\Model\Blog;
use ExtbaseTeam\BlogExample\Domain\Model\Post;
use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class BlogController extends ActionController
{
    private BlogRepository $blogRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    private DataMapFactory $dataMapFactory;

    public function __construct(BlogRepository $blogRepository, DataMapFactory $dataMapFactory)
    {
        $this->blogRepository = $blogRepository;
        $this->dataMapFactory = $dataMapFactory;
    }

    public function listAction(): ResponseInterface
    {
        $blogs = $this->blogRepository->findAll();
        $value = [];
        $value[$this->getRuntimeIdentifier()] = $this->getStructure($blogs);

        $this->view->assign('value', $value);

        return $this->htmlResponse();
    }

    public function detailsAction(Blog $blog = null): ResponseInterface
    {
        return $this->htmlResponse($blog ? $blog->getTitle() : '');
    }

    public function testFormAction(): ResponseInterface
    {
        return $this->htmlResponse('testFormAction');
    }

    /**
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post $blogPost
     * // needs to be imported entirely, else the annotationChecker test script complains
     * @IgnoreValidation("blogPost")
     */
    public function testForwardAction(Post $blogPost): ForwardResponse
    {
        return (new ForwardResponse('testForwardTarget'))->withArguments(['blogPost' => $blogPost]);
    }

    /**
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post $blogPost
     */
    public function testForwardTargetAction(Post $blogPost): ResponseInterface
    {
        return $this->htmlResponse('testForwardTargetAction');
    }

    /**
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Blog $blog
     * @param \ExtbaseTeam\BlogExample\Domain\Model\Post|null $blogPost
     */
    public function testRelatedObjectAction(Blog $blog, ?Post $blogPost = null): ResponseInterface
    {
        return $this->htmlResponse('testRelatedObject');
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
                1476122222
            );
        }
    }

    /**
     * Disable the default error flash message, otherwise we get an error because the flash message
     * session handling is not available during functional tests.
     */
    protected function getErrorFlashMessage(): bool
    {
        return false;
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
