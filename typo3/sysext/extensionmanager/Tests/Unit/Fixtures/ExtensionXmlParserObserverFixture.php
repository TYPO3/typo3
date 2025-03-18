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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Fixtures;

use TYPO3\CMS\Extensionmanager\Parser\ExtensionXmlParser;

/**
 * Latest compatible extension object storage fixture
 */
class ExtensionXmlParserObserverFixture implements \SplObserver
{
    public array $rows = [];

    /**
     * Method receives an update from a subject.
     *
     * @param \SplSubject $subject a subject notifying this observer
     */
    public function update(\SplSubject $subject): void
    {
        if ($subject instanceof ExtensionXmlParser) {
            // @see TYPO3\CMS\Extensionmanager\Domain\Repository\BulkExtensionRepositoryWriter->loadIntoDatabase()
            $this->rows[] = [
                'extkey' => $subject->getExtkey(),
                'version' => $subject->getVersion(),
                'alldownloadcounter' => $subject->getAlldownloadcounter(),
                'downloadcounteR' => $subject->getDownloadcounter(),
                'title' => $subject->getTitle(),
                'ownerusername' => $subject->getOwnerusername(),
                'authorname' => $subject->getAuthorname(),
                'authoremail' => $subject->getAuthoremail(),
                'authorcompany' => $subject->getAuthorcompany(),
                'lastuploaddate' => $subject->getLastuploaddate(),
                't3xfilemd5' => $subject->getT3xfilemd5(),
                'state' => $subject->getState(),
                'reviewstate' => $subject->getReviewstate(),
                'category' => $subject->getCategory(),
                'description' => $subject->getDescription(),
                'dependencies' => $subject->getDependencies(),
                'uploadcomment' => $subject->getUploadcomment(),
                'documentationlink' => $subject->getDocumentationLink(),
                'distributionimage' => $subject->getDistributionImage(),
                'distributionwelcomeimage' => $subject->getDistributionWelcomeImage(),
            ];
        }
    }

}
