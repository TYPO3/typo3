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

namespace TYPO3\CMS\Filelist\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFileTypeMatcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Type\SortDirection;
use TYPO3\CMS\Filelist\Type\ViewMode;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileListTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['filelist'];

    /**
     * @var array<string, non-empty-string>
     */
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/filelist/Tests/Functional/Fixtures/textfile.txt' => 'fileadmin/textfile.txt',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function selectionCheckboxIsLabelledWithResourceName(): void
    {
        $selectableMatcher = new Matcher();
        $selectableMatcher->addMatcher(new ResourceFileTypeMatcher());
        $selectableMatcher->addMatcher(new ResourceFolderTypeMatcher());

        $listHtml = $this->renderListView($this->createFileList(), $selectableMatcher);

        self::assertMatchesRegularExpression(
            '/<input [^>]*type="checkbox"[^>]*value="1:\/textfile\.txt"[^>]*aria-label="textfile\.txt"/',
            $listHtml
        );
    }

    private function createFileList(): FileList
    {
        $request = new ServerRequest('http://localhost/');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $request = $request->withAttribute('route', new Route('/module/file/list', ['_identifier' => 'media_management']));
        $fileList = GeneralUtility::makeInstance(FileList::class, $request);
        $fileList->viewMode = ViewMode::LIST;
        return $fileList;
    }

    private function renderListView(FileList $fileList, ?Matcher $selectableMatcher = null): string
    {
        $folder = $this->get(StorageRepository::class)->findByUid(1)->getFolder('/');
        $fileList->start($folder, 1, 'name', SortDirection::ASCENDING);
        if ($selectableMatcher !== null) {
            $fileList->setResourceSelectableMatcher($selectableMatcher);
        }

        $view = new class implements ViewInterface {
            private array $values = [];

            public function assign(string $key, mixed $value): self
            {
                $this->values[$key] = $value;
                return $this;
            }

            public function assignMultiple(array $values): self
            {
                $this->values = array_merge($this->values, $values);
                return $this;
            }

            public function render(string $templateFileName = ''): string
            {
                return (string)($this->values['tableBody'] ?? '');
            }
        };

        return $fileList->render(null, $view);
    }
}
