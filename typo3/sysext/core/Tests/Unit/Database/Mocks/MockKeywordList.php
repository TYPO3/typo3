<?php
namespace TYPO3\CMS\Core\Tests\Unit\Database\Mocks;

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

use Doctrine\DBAL\Platforms\Keywords\KeywordList;

class MockKeywordList extends KeywordList
{
    /**
     * Returns the name of this keyword list.
     *
     * @return string
     */
    public function getName()
    {
        return 'mock';
    }

    /**
     * Returns the list of keywords.
     *
     * @return array
     */
    protected function getKeywords()
    {
        return [
            'RESERVED',
        ];
    }
}
