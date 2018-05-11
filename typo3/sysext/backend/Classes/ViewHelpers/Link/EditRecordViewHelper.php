<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\ViewHelpers\Link;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Use this ViewHelper to provide edit links to records. The ViewHelper will
 * pass the uid and table to FormEngine.
 *
 * The uid must be given as a positive integer.
 * For new records, use the newRecordViewHelper
 *
 * = Examples =
 *
 * <code title="Link to the record-edit action passed to FormEngine">
 * <be:link.editRecord uid="42" table="a_table" returnUrl="foo/bar" />
 * </code>
 * <output>
 * <a href="/typo3/index.php?route=/record/edit&edit[a_table][42]=edit&returnUrl=foo/bar">
 *   Edit record
 * </a>
 * </output>
 */
class EditRecordViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('uid', 'int', 'uid of record to be edited', true);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('returnUrl', 'string', '', false, '');
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function render(): string
    {
        if ($this->arguments['uid'] < 1) {
            throw new \InvalidArgumentException('Uid must be a positive integer, ' . $this->arguments['uid'] . ' given.', 1526127158);
        }
        if (empty($this->arguments['returnUrl'])) {
            $this->arguments['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        $params = [
            'edit' => [$this->arguments['table'] => [$this->arguments['uid'] => 'edit']],
            'returnUrl' => $this->arguments['returnUrl']
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
