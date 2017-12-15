<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Script Class for colorpicker wizard
 *
 * Unused with new renderType "inputColorPicker" since v8.
 *
 * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
 */
class ColorpickerController extends AbstractWizardController
{
    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * @var array
     */
    public $wizardParameters;

    /**
     * Value of the current color picked.
     *
     * @var string
     */
    public $colorValue;

    /**
     * Serialized functions for changing the field...
     * Necessary to call when the value is transferred to the FormEngine since the form might
     * need to do internal processing. Otherwise the value is simply not be saved.
     *
     * @var string
     */
    public $fieldChangeFunc;

    /**
     * @var string
     */
    protected $fieldChangeFuncHash;

    /**
     * Form name (from opener script)
     *
     * @var string
     */
    public $fieldName;

    /**
     * Field name (from opener script)
     *
     * @var string
     */
    public $formName;

    /**
     * ID of element in opener script for which to set color.
     *
     * @var string
     */
    public $md5ID;

    /**
     * Internal: If FALSE, a frameset is rendered, if TRUE the content of the picker script.
     *
     * @var int
     */
    public $showPicker;

    /**
     * @var string
     */
    public $HTMLcolorList = 'aqua,black,blue,fuchsia,gray,green,lime,maroon,navy,olive,purple,red,silver,teal,yellow,white';

    /**
     * @var string
     */
    public $pickerImage = '';

    /**
     * Error message if image not found.
     *
     * @var string
     */
    public $imageError = '';

    /**
     * Document template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    protected $exampleImg;

    /**
     * Constructor
     *
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function __construct()
    {
        GeneralUtility::logDeprecatedFunction();
        parent::__construct();
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_wizards.xlf');
        $GLOBALS['SOBE'] = $this;

        $this->init();
    }

    /**
     * Initialises the Class
     */
    protected function init()
    {
        // Setting GET vars (used in frameset script):
        $this->wizardParameters = GeneralUtility::_GP('P');
        // Setting GET vars (used in colorpicker script):
        $this->colorValue = GeneralUtility::_GP('colorValue');
        $this->fieldChangeFunc = GeneralUtility::_GP('fieldChangeFunc');
        $this->fieldChangeFuncHash = GeneralUtility::_GP('fieldChangeFuncHash');
        $this->fieldName = GeneralUtility::_GP('fieldName');
        $this->formName = GeneralUtility::_GP('formName');
        $this->md5ID = GeneralUtility::_GP('md5ID');
        $this->exampleImg = GeneralUtility::_GP('exampleImg');
        // Resolving image (checking existence etc.)
        $this->imageError = '';
        if ($this->exampleImg) {
            $this->pickerImage = GeneralUtility::getFileAbsFileName($this->exampleImg);
            if (!$this->pickerImage || !@is_file($this->pickerImage)) {
                $this->imageError = 'ERROR: The image "' . $this->exampleImg . '" could not be found!';
            }
        }
        $update = [];
        if ($this->areFieldChangeFunctionsValid()) {
            // Setting field-change functions:
            $fieldChangeFuncArr = unserialize($this->fieldChangeFunc);
            unset($fieldChangeFuncArr['alert']);
            foreach ($fieldChangeFuncArr as $v) {
                $update[] = 'parent.opener.' . $v;
            }
        }
        // Initialize document object:
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->getPageRenderer()->loadRequireJsModule(
            'TYPO3/CMS/Backend/Wizard/Colorpicker',
            'function(Colorpicker) {
				Colorpicker.setFieldChangeFunctions({
					fieldChangeFunctions: function() {'
                        . implode('', $update) .
                    '}
				});
			}'
        );
        // Start page:
        $this->content .= $this->doc->startPage($this->getLanguageService()->getLL('colorpicker_title'));
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();

        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);

        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * Main Method, rendering either colorpicker or frameset depending on ->showPicker
     */
    public function main()
    {
        // Show frameset by default:
        if (!GeneralUtility::_GP('showPicker')) {
            $this->frameSet();
        } else {
            // Putting together the items into a form:
            $content = '
				<form name="colorform" method="post" action="' . htmlspecialchars(BackendUtility::getModuleUrl('wizard_colorpicker')) . '">
					' . $this->colorMatrix() . '
					' . $this->colorList() . '
					' . $this->colorImage() . '

					<!-- Value box: -->
					<p class="c-head">' . htmlspecialchars($this->getLanguageService()->getLL('colorpicker_colorValue')) . '</p>
					<table border="0" cellpadding="0" cellspacing="3">
						<tr>
							<td>
								<input id="colorValue" type="text" ' . $this->doc->formWidth(7) . ' maxlength="10" name="colorValue" value="' . htmlspecialchars($this->colorValue) . '" />
							</td>
							<td style="background-color:' . htmlspecialchars($this->colorValue) . '; border: 1px solid black;">
								<span style="color: black;">' . htmlspecialchars($this->getLanguageService()->getLL('colorpicker_black')) . '</span>&nbsp;<span style="color: white;">' . htmlspecialchars($this->getLanguageService()->getLL('colorpicker_white')) . '</span>
							</td>
							<td>
								<input class="btn btn-default" type="submit" id="colorpicker-saveclose" value="' . htmlspecialchars($this->getLanguageService()->getLL('colorpicker_setClose')) . '" />
							</td>
						</tr>
					</table>

					<!-- Hidden fields with values that has to be kept constant -->
					<input type="hidden" name="showPicker" value="1" />
					<input type="hidden" name="fieldChangeFunc" value="' . htmlspecialchars($this->fieldChangeFunc) . '" />
					<input type="hidden" name="fieldChangeFuncHash" value="' . htmlspecialchars($this->fieldChangeFuncHash) . '" />
					<input type="hidden" name="fieldName" value="' . htmlspecialchars($this->fieldName) . '" />
					<input type="hidden" name="formName" value="' . htmlspecialchars($this->formName) . '" />
					<input type="hidden" name="md5ID" value="' . htmlspecialchars($this->md5ID) . '" />
					<input type="hidden" name="exampleImg" value="' . htmlspecialchars($this->exampleImg) . '" />
				</form>';

            $this->content .= '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('colorpicker_title')) . '</h2>';
            $this->content .= $content;
        }
    }

    /**
     * Returns a frameset so our JavaScript Reference isn't lost
     * Took some brains to figure this one out ;-)
     * If Peter wouldn't have been I would've gone insane...
     */
    public function frameSet()
    {
        $this->getDocumentTemplate()->JScode = GeneralUtility::wrapJS('
				if (!window.opener) {
					alert("ERROR: Sorry, no link to main window... Closing");
					close();
				}
		');
        $this->getDocumentTemplate()->startPage($this->getLanguageService()->getLL('colorpicker_title'));

        // URL for the inner main frame:
        $url = BackendUtility::getModuleUrl(
            'wizard_colorpicker',
            [
                'showPicker' => 1,
                'colorValue' => $this->wizardParameters['currentValue'],
                'fieldName' => $this->wizardParameters['itemName'],
                'formName' => $this->wizardParameters['formName'],
                'exampleImg' => $this->wizardParameters['exampleImg'],
                'md5ID' => $this->wizardParameters['md5ID'],
                'fieldChangeFunc' => serialize($this->wizardParameters['fieldChangeFunc']),
                'fieldChangeFuncHash' => $this->wizardParameters['fieldChangeFuncHash'],
            ]
        );
        $this->content = $this->getPageRenderer()->render(PageRenderer::PART_HEADER) . '
			<frameset rows="*,1" framespacing="0" frameborder="0" border="0">
				<frame name="content" src="' . htmlspecialchars($url) . '" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />
				<frame name="menu" src="' . htmlspecialchars(BackendUtility::getModuleUrl('dummy')) . '" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
			</frameset>
		</html>';
    }

    /************************************
     *
     * Rendering of various color selectors
     *
     ************************************/
    /**
     * Creates a color matrix table
     *
     * @return string
     */
    public function colorMatrix()
    {
        $steps = 51;
        // Get colors:
        $color = [];
        for ($rr = 0; $rr < 256; $rr += $steps) {
            for ($gg = 0; $gg < 256; $gg += $steps) {
                for ($bb = 0; $bb < 256; $bb += $steps) {
                    $color[] = '#' . substr(('0' . dechex($rr)), -2) . substr(('0' . dechex($gg)), -2) . substr(('0' . dechex($bb)), -2);
                }
            }
        }
        // Traverse colors:
        $columns = 24;
        $rows = 0;
        $tRows = [];
        while (isset($color[$columns * $rows])) {
            $tCells = [];
            for ($i = 0; $i < $columns; $i++) {
                $tCells[] = '<td bgcolor="' . $color[$columns * $rows + $i] . '" class="t3js-colorpicker-value" data-color-value="' . htmlspecialchars($color[($columns * $rows + $i)]) . '" title="' . htmlspecialchars($color[($columns * $rows + $i)]) . '">&nbsp;&nbsp;</td>';
            }
            $tRows[] = '<tr>' . implode('', $tCells) . '</tr>';
            $rows++;
        }
        return '<p class="c-head">' . htmlspecialchars($this->getLanguageService()->getLL('colorpicker_fromMatrix')) . '</p>
			<table style="width:100%; border: 1px solid black; cursor:crosshair;">' . implode('', $tRows) . '</table>';
    }

    /**
     * Creates a selector box with all HTML color names.
     *
     * @return string
     */
    public function colorList()
    {
        // Initialize variables:
        $colors = explode(',', $this->HTMLcolorList);
        $currentValue = strtolower($this->colorValue);
        $opt = [];
        $opt[] = '<option value=""></option>';
        // Traverse colors, making option tags for selector box.
        foreach ($colors as $colorName) {
            $opt[] = '<option style="background-color: ' . $colorName . ';" value="' . htmlspecialchars($colorName) . '"' . ($currentValue === $colorName ? ' selected="selected"' : '') . '>' . htmlspecialchars($colorName) . '</option>';
        }
        // Compile selector box and return result:
        return '<p class="c-head">' . htmlspecialchars($this->getLanguageService()->getLL('colorpicker_fromList')) . '</p>
			<select class="t3js-colorpicker-selector">' . implode(LF, $opt) . '</select><br />';
    }

    /**
     * Creates a color image selector
     *
     * @return string
     */
    public function colorImage()
    {
        // Handling color-picker image if any:
        if (!$this->imageError) {
            if ($this->pickerImage) {
                if (GeneralUtility::_POST('coords_x')) {
                    /** @var $image \TYPO3\CMS\Core\Imaging\GraphicalFunctions */
                    $image = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\GraphicalFunctions::class);
                    $this->colorValue = '#' . $this->getIndex($image->imageCreateFromFile($this->pickerImage), GeneralUtility::_POST('coords_x'), GeneralUtility::_POST('coords_y'));
                }
                $pickerFormImage = '
				<p class="c-head">' . htmlspecialchars($this->getLanguageService()->getLL('colorpicker_fromImage')) . '</p>
				<input type="image" src="' . PathUtility::getAbsoluteWebPath($this->pickerImage) . '" name="coords" style="cursor:crosshair;" /><br />';
            } else {
                $pickerFormImage = '';
            }
        } else {
            $pickerFormImage = '
			<p class="c-head">' . htmlspecialchars($this->imageError) . '</p>';
        }
        return $pickerFormImage;
    }

    /**
     * Gets the HTML (Hex) Color Code for the selected pixel of an image
     * This method handles the correct imageResource no matter what format
     *
     * @param resource $im Valid ImageResource returned by \TYPO3\CMS\Core\Imaging\GraphicalFunctions::imageCreateFromFile
     * @param int $x X-Coordinate of the pixel that should be checked
     * @param int $y Y-Coordinate of the pixel that should be checked
     * @return string HEX RGB value for color
     * @see colorImage()
     */
    public function getIndex($im, $x, $y)
    {
        $rgb = imagecolorat($im, $x, $y);
        $colorRgb = imagecolorsforindex($im, $rgb);
        $index['r'] = dechex($colorRgb['red']);
        $index['g'] = dechex($colorRgb['green']);
        $index['b'] = dechex($colorRgb['blue']);
        $hexValue = [];
        foreach ($index as $value) {
            if (strlen($value) === 1) {
                $hexValue[] = strtoupper('0' . $value);
            } else {
                $hexValue[] = strtoupper($value);
            }
        }
        $hex = implode('', $hexValue);
        return $hex;
    }

    /**
     * Determines whether submitted field change functions are valid
     * and are coming from the system and not from an external abuse.
     *
     * @return bool Whether the submitted field change functions are valid
     */
    protected function areFieldChangeFunctionsValid()
    {
        return $this->fieldChangeFunc && $this->fieldChangeFuncHash && hash_equals(GeneralUtility::hmac($this->fieldChangeFunc), $this->fieldChangeFuncHash);
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
