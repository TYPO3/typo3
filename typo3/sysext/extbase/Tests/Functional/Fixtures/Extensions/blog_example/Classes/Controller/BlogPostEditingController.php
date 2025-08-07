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
use TYPO3\CMS\Extbase\Attribute\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\CategoryRepository;

final class BlogPostEditingController extends ActionController
{
    public function __construct(
        private readonly BlogRepository $blogRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly PersistenceManager $persistenceManager,
    ) {}

    public function listAction(): ResponseInterface
    {
        $blogs = $this->blogRepository->findAll();
        $this->view->assign('blogs', $blogs);
        return $this->htmlResponse();
    }

    public function viewAction(Blog $blog): ResponseInterface
    {
        $this->view->assign('blog', $blog);
        return $this->htmlResponse();
    }

    /**
     * Note that we ignore validation intentionally here, so that
     * the action can take in a not-validated blog to be able to report
     * errors after the errorAction() redirection!
     */
    #[IgnoreValidation(['argumentName' => 'blog'])]
    public function editAction(Blog $blog): ResponseInterface
    {
        $categories = $this->categoryRepository->findAll();
        $categoriesSelect = [];
        foreach ($categories as $category) {
            $categoriesSelect[$category->getUid()] = $category->getTitle();
        }
        $this->view->assignMultiple([
            'categories' => $categories,
            'categoriesSelect' => $categoriesSelect,
            'blog' => $blog,
        ]);
        return $this->htmlResponse();
    }

    public function newAction(): ResponseInterface
    {
        $blog = new Blog();
        $this->view->assignMultiple([
            'blog' => $blog,
            'categories' => $this->categoryRepository->findAll(),
        ]);
        return $this->htmlResponse();
    }

    public function persistAction(Blog $blog): ResponseInterface
    {
        // IMPORTANT: This is just an example case for testing purposes. This is missing any
        // kind of access control or permission gating. NEVER do this in production without it.
        $this->blogRepository->update($blog);
        return $this->redirect('list');
    }

    public function createAction(Blog $blog): ResponseInterface
    {
        // IMPORTANT: This is just an example case for testing purposes. This is missing any
        // kind of access control or permission gating. NEVER do this in production without it.
        $blog->setPid((int)$this->settings['pidNewRecords']);
        $this->blogRepository->add($blog);
        $this->persistenceManager->persistAll();
        return $this->redirect('list');
    }
}
