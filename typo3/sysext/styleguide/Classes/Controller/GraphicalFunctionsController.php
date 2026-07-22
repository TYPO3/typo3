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

namespace TYPO3\CMS\Styleguide\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\Enum\ModuleLayout;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;

/**
 * Styleguide: GifBuilder showcase.
 *
 * Runs every GIFBUILDER object type (BOX, ELLIPSE, TEXT, OUTLINE, EMBOSS,
 * SHADOW, EFFECT, ADJUST, IMAGE, CROP, SCALE) through the real GifBuilder
 * pipeline and renders the results inline so a developer can visually
 * verify the pure-GD rendering path.
 *
 * @internal
 */
#[AsController]
final class GraphicalFunctionsController
{
    private const string FONT = 'EXT:core/Resources/Private/Font/nimbus.ttf';
    private const string TEST_IMAGE = 'EXT:styleguide/Resources/Public/Images/GraphicsTest/test.jpg';
    private const string BACKGROUND_IMAGE = 'EXT:styleguide/Resources/Public/Images/GraphicsTest/background-orange.gif';
    private const string MASK_IMAGE = 'EXT:styleguide/Resources/Public/Images/GraphicsTest/mask-black-white.gif';
    private const string COLORBARS_IMAGE = 'EXT:styleguide/Resources/Public/Images/GraphicsTest/colorbars.png';

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request);
        $view->assignMultiple([
            'demoGroups' => [
                'basic' => ['title' => 'Basic objects', 'demos' => $this->basicObjectDemos()],
                'effects' => ['title' => 'Effects', 'demos' => $this->effectDemos()],
                'adjust' => ['title' => 'Adjust (tone curves)', 'demos' => $this->adjustDemos()],
                'composites' => ['title' => 'Composites', 'demos' => $this->compositeDemos()],
            ],
        ]);
        return $view->renderResponse('Backend/GraphicalFunctions/GifBuilder');
    }

    /**
     * BOX, ELLIPSE, TEXT, OUTLINE, EMBOSS and SHADOW rendered stand-alone.
     */
    private function basicObjectDemos(): array
    {
        $demos = [];

        // BOX — two side-by-side boxes on a dark background.
        $demos['box'] = $this->runDemo(
            'BOX',
            'Two solid rectangles on a dark canvas.',
            [
                'XY' => '320,240',
                'backColor' => '#1a1a2e',
                'format' => 'png',
                '10' => 'BOX',
                '10.' => ['color' => '#e94560', 'dimensions' => '20,40,130,160'],
                '20' => 'BOX',
                '20.' => ['color' => '#0f3460', 'dimensions' => '165,40,135,160'],
            ]
        );

        // ELLIPSE — the dimensions offset is the *centre* of the ellipse (not top-left).
        // Three concentric ellipses centred at (160,120) in a 320×240 canvas.
        $demos['ellipse'] = $this->runDemo(
            'ELLIPSE',
            'Three concentric filled ellipses.',
            [
                'XY' => '320,240',
                'backColor' => '#1a1a2e',
                'format' => 'png',
                '10' => 'ELLIPSE',
                '10.' => ['color' => '#0f3460', 'dimensions' => '160,120,300,224'],
                '20' => 'ELLIPSE',
                '20.' => ['color' => '#e94560', 'dimensions' => '160,120,178,132'],
                '30' => 'ELLIPSE',
                '30.' => ['color' => '#16213e', 'dimensions' => '160,120,80,60'],
            ]
        );

        // TEXT with niceText (TTF).
        // align='center' centres horizontally. offset y is the TTF baseline position.
        // For nimbus 28px the cap-height is ~20px, so baseline at (240+20)/2 = 130 centres the text.
        $demos['textTtf'] = $this->runDemo(
            'TEXT (niceText / TTF)',
            'Antialiased TrueType text centred on a dark canvas.',
            [
                'XY' => '320,240',
                'backColor' => '#1a1a2e',
                'format' => 'png',
                '10' => 'TEXT',
                '10.' => [
                    'text' => 'Hello GIFBUILDER',
                    'fontColor' => '#e2e2e2',
                    'fontSize' => '28',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,130',
                    'niceText' => '1',
                ],
            ]
        );

        // TEXT with shadow on a light background so the dark shadow is visible.
        // Baseline at 125 leaves room for the 5px shadow offset and blur below.
        $demos['textShadow'] = $this->runDemo(
            'TEXT with shadow',
            'Text with a soft drop-shadow on a light canvas.',
            [
                'XY' => '320,240',
                'backColor' => '#e8e8f0',
                'format' => 'png',
                '10' => 'TEXT',
                '10.' => [
                    'text' => 'Hello GIFBUILDER',
                    'fontColor' => '#1a1a2e',
                    'fontSize' => '28',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,125',
                    'niceText' => '1',
                    'shadow.' => [
                        'offset' => '5,5',
                        'blur' => '30',
                        'opacity' => '80',
                        'color' => '#1a1a2e',
                    ],
                ],
            ]
        );

        // niceText quality ladder — mirrors the install tool's GD image processing test.
        // Three TEXT objects on the same canvas show the rendering quality progression:
        //   line 1 (key 10) — plain TTF via imagettftext, aliased edges
        //   line 2 (key 20) — niceText=1 supersampled, smooth antialiasing
        //   line 3 (key 30) — niceText + drop-shadow
        // Background colour RGB(128,128,150) matches the install tool reference exactly.
        // 320×240 canvas, fontSize=36: cap-height ≈ 26px, descender ≈ 8px →
        // baselines at 60, 130, 200 (70px spacing) so the text block is optically
        // centred with equal top and bottom padding (~34px each).
        $demos['niceTextLadder'] = $this->runDemo(
            'niceText quality comparison',
            'Plain TTF, niceText antialiased, and niceText with shadow stacked on one canvas.',
            [
                'XY' => '320,240',
                'backColor' => '#808096',
                'format' => 'png',
                '10' => 'TEXT',
                '10.' => [
                    'text' => 'HELLO WORLD',
                    'fontColor' => '#003366',
                    'fontSize' => '36',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,60',
                ],
                '20' => 'TEXT',
                '20.' => [
                    'text' => 'HELLO WORLD',
                    'fontColor' => '#003366',
                    'fontSize' => '36',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,130',
                    'niceText' => '1',
                ],
                '30' => 'TEXT',
                '30.' => [
                    'text' => 'HELLO WORLD',
                    'fontColor' => '#003366',
                    'fontSize' => '36',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,200',
                    'niceText' => '1',
                    'shadow.' => [
                        'offset' => '2,2',
                        'blur' => '20',
                        'opacity' => '50',
                        'color' => 'black',
                    ],
                ],
            ]
        );

        // OUTLINE — stand-alone object that strokes a border around an existing TEXT.
        // OUTLINE must render BEFORE the TEXT it references (lower numeric key) so the fill
        // paints on top. `textObjNum` is the key of the TEXT to outline; `thickness` is 1–2 px.
        $demos['outline'] = $this->runDemo(
            'OUTLINE',
            'Text with a stroked border around each glyph.',
            [
                'XY' => '320,240',
                'backColor' => '#1a1a2e',
                'format' => 'png',
                '10' => 'OUTLINE',
                '10.' => ['textObjNum' => '20', 'thickness' => '2', 'color' => '#e94560'],
                '20' => 'TEXT',
                '20.' => [
                    'text' => 'OUTLINE',
                    'fontColor' => '#fbbf24',
                    'fontSize' => '60',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,150',
                    'niceText' => '1',
                ],
            ]
        );

        // EMBOSS — stand-alone object that fakes a 3D bevel on an existing TEXT.
        // Paints the text twice behind the real TEXT: once at +offset in `highColor` and once
        // at −offset in `lowColor`. Classic light-from-top-left look with high=white, low=black.
        $demos['emboss'] = $this->runDemo(
            'EMBOSS',
            'Text with a faux-3D bevel effect.',
            [
                'XY' => '320,240',
                'backColor' => '#4b5563',
                'format' => 'png',
                '10' => 'EMBOSS',
                '10.' => [
                    'textObjNum' => '20',
                    'offset' => '2,2',
                    'blur' => '0',
                    'intensity' => '80',
                    'highColor' => '#ffffff',
                    'lowColor' => '#000000',
                ],
                '20' => 'TEXT',
                '20.' => [
                    'text' => 'EMBOSS',
                    'fontColor' => '#9ca3af',
                    'fontSize' => '60',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,150',
                    'niceText' => '1',
                ],
            ]
        );

        // SHADOW (stand-alone) — same pipeline as TEXT's `shadow.` sub-object, but as a
        // separate GIFBUILDER object that references a TEXT via `textObjNum`. Must render at a
        // lower key so the shadow paints underneath the text.
        $demos['shadowStandalone'] = $this->runDemo(
            'SHADOW (standalone)',
            'Text with a drop-shadow painted as a separate object.',
            [
                'XY' => '320,240',
                'backColor' => '#e8e8f0',
                'format' => 'png',
                '10' => 'SHADOW',
                '10.' => [
                    'textObjNum' => '20',
                    'offset' => '5,5',
                    'blur' => '40',
                    'opacity' => '70',
                    'color' => '#1a1a2e',
                    'intensity' => '80',
                ],
                '20' => 'TEXT',
                '20.' => [
                    'text' => 'SHADOW',
                    'fontColor' => '#1a1a2e',
                    'fontSize' => '60',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,150',
                    'niceText' => '1',
                ],
            ]
        );

        return $demos;
    }

    /**
     * Every EFFECT keyword applied to the same colourful base canvas.
     */
    private function effectDemos(): array
    {
        // Shared colorful shape base reused in the EFFECT demos so you can compare before/after.
        // 3 columns × 2 rows grid — distinct colours per row/column so flip (vertical mirror) and
        // flop (horizontal mirror) are both visibly verifiable.  The colorbars fixture is a
        // pre-rendered 320×240 opaque PNG so effects never operate on GifBuilder-generated
        // (potentially alpha-tainted) canvases.
        $colorBars = [
            'XY' => '320,240',
            'format' => 'png',
            '10' => 'IMAGE',
            '10.' => [
                'file' => self::COLORBARS_IMAGE,
            ],
        ];

        $demos = [];

        // EFFECT — grayscale applied to an already-rendered colourful composition.
        $demos['gray'] = $this->runDemo(
            'EFFECT: gray',
            'Coloured shapes desaturated to grayscale.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'gray']]
        );

        // EFFECT — invert flips every colour to its complement.
        $demos['invert'] = $this->runDemo(
            'EFFECT: invert',
            'Coloured shapes with every colour inverted.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'invert']]
        );

        // EFFECT — blur softens the entire canvas.
        $demos['blur'] = $this->runDemo(
            'EFFECT: blur',
            'Coloured shapes softened with a strong blur.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'blur = 80']]
        );

        // EFFECT — emboss applies a custom 3×3 directional convolution.
        // The kernel has sum 1 and is biased by +32 so flat regions get a small
        // highlight lift while edges still produce directional contrast.
        $demos['emboss'] = $this->runDemo(
            'EFFECT: emboss',
            'Coloured shapes rendered as a bas-relief.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'emboss']]
        );

        // EFFECT — edge runs a dual-direction Sobel gradient with a
        // threshold to produce sparse colour-preserving edges. Takes no parameters.
        $demos['edge'] = $this->runDemo(
            'EFFECT: edge',
            'Coloured shapes with edge detection applied.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'edge']]
        );

        // EFFECT — charcoal simulates a pencil drawing: grayscale,
        // edge-detect, invert. The value (0–100) controls the softening radius.
        $demos['charcoal'] = $this->runDemo(
            'EFFECT: charcoal',
            'Coloured shapes rendered as a charcoal drawing.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'charcoal = 1']]
        );

        // EFFECT — colors posterises by reducing the palette.
        $demos['colors'] = $this->runDemo(
            'EFFECT: colors',
            'Coloured shapes posterised to four colours.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'colors = 4']]
        );

        // EFFECT — sharpen accentuates edges. The value (0–99) controls strength.
        $demos['sharpen'] = $this->runDemo(
            'EFFECT: sharpen',
            'Coloured shapes with strong edge sharpening.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'sharpen = 80']]
        );

        // EFFECT — gamma adjusts midtone brightness. <1 darkens, >1 brightens; 1 is neutral.
        $demos['gamma'] = $this->runDemo(
            'EFFECT: gamma',
            'Coloured shapes brightened with gamma 2.0.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'gamma = 2.0']]
        );

        // EFFECT — solarize inverts pixels above the threshold, leaving darker pixels untouched.
        $demos['solarize'] = $this->runDemo(
            'EFFECT: solarize',
            'Coloured shapes with a 50% solarize threshold.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'solarize = 50']]
        );

        // EFFECT — swirl twirls pixels around the centre, more near the centre than the edge.
        $demos['swirl'] = $this->runDemo(
            'EFFECT: swirl',
            'Coloured shapes twirled 360 degrees around the centre.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'swirl = 360']]
        );

        // EFFECT — flip mirrors the canvas vertically (upside-down).
        $demos['flip'] = $this->runDemo(
            'EFFECT: flip',
            'Coloured shapes mirrored vertically.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'flip']]
        );

        // EFFECT — flop mirrors the canvas horizontally (left-to-right).
        $demos['flop'] = $this->runDemo(
            'EFFECT: flop',
            'Coloured shapes mirrored horizontally.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'flop']]
        );

        // EFFECT — rotate turns the canvas by a float number of degrees (counter-clockwise in GifBuilder).
        $demos['rotate'] = $this->runDemo(
            'EFFECT: rotate',
            'Coloured shapes rotated 15 degrees.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'rotate = 15']]
        );

        // EFFECT — shear slants the canvas horizontally by an integer number of degrees.
        $demos['shear'] = $this->runDemo(
            'EFFECT: shear',
            'Coloured shapes sheared 20 degrees horizontally.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'shear = 20']]
        );

        // EFFECT — wave distorts the canvas with a sinusoidal ripple.
        // Value is "amplitude,length" — amplitude 0–99 (pixels), length 1–99 (wavelength in pixels).
        $demos['wave'] = $this->runDemo(
            'EFFECT: wave',
            'Coloured shapes distorted with a sinusoidal wave.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'wave = 5,30']]
        );

        // EFFECT — chained pipeline. Multiple effects separated by `|` are
        // applied left-to-right in a single EFFECT object.
        $demos['chained'] = $this->runDemo(
            'EFFECT: chained pipeline',
            'Invert, gamma 1.3, wave, 360° swirl and 45° rotation applied in sequence.',
            $colorBars + ['70' => 'EFFECT', '70.' => ['value' => 'invert | gamma = 1.3 | wave = 5,30 | swirl = 360 | rotate = 45']]
        );

        return $demos;
    }

    /**
     * ADJUST is a sibling of EFFECT but dedicated to tone curves.
     */
    private function adjustDemos(): array
    {
        // Shared photographic base so the histogram actually shifts visibly. test.jpg is a
        // 400×300 JPEG which scales to 320×240 exactly — perfect fit for the 4:3 canvas.
        $photoBase = [
            'XY' => '320,240',
            'format' => 'jpg',
            '10' => 'IMAGE',
            '10.' => [
                'file' => self::TEST_IMAGE,
                'file.' => ['width' => '320'],
            ],
        ];

        $demos = [];

        // ADJUST: autoLevels — stretches the darkest pixel to pure black and the brightest
        // to pure white, maximising contrast. Visible on any photo that doesn't already use
        // the full 0–255 range.
        $demos['autoLevels'] = $this->runDemo(
            'ADJUST: autoLevels',
            'Photo with automatic contrast stretch.',
            $photoBase + ['20' => 'ADJUST', '20.' => ['value' => 'autoLevels']]
        );

        // ADJUST: inputLevels — manually remaps an input range to the full 0–255 output.
        // Values above `high` clip to white; values below `low` clip to black. Boosts contrast.
        $demos['inputLevels'] = $this->runDemo(
            'ADJUST: inputLevels',
            'Photo with input levels remapped from 60–200 to 0–255.',
            $photoBase + ['20' => 'ADJUST', '20.' => ['value' => 'inputLevels = 60,200']]
        );

        // ADJUST: outputLevels — compresses the full 0–255 input into a narrower output
        // range (here 40–200). The image loses contrast and looks washed out — the inverse
        // of inputLevels.
        $demos['outputLevels'] = $this->runDemo(
            'ADJUST: outputLevels',
            'Photo compressed to the 40–200 output range.',
            $photoBase + ['20' => 'ADJUST', '20.' => ['value' => 'outputLevels = 40,200']]
        );

        return $demos;
    }

    /**
     * Recipes that combine multiple GIFBUILDER objects on one canvas.
     */
    private function compositeDemos(): array
    {
        $demos = [];

        // TEXT over a coloured background — the most common real-world GIFBUILDER recipe.
        // align='center' centres horizontally; offset y = TTF baseline at canvas vertical centre.
        $demos['textOnBox'] = $this->runDemo(
            'Composite: TEXT on BOX',
            'A solid red background with centred white text.',
            [
                'XY' => '320,240',
                'backColor' => '#1a1a2e',
                'format' => 'png',
                '10' => 'BOX',
                '10.' => ['color' => '#e94560', 'dimensions' => '0,0,320,240'],
                '20' => 'TEXT',
                '20.' => [
                    'text' => 'Hello GIFBUILDER',
                    'fontColor' => '#ffffff',
                    'fontSize' => '28',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,130',
                    'niceText' => '1',
                ],
            ]
        );

        // IMAGE — load and scale a raster file onto the canvas.
        // file. width/height use the same processing instructions as standard TYPO3 image scaling.
        // test.jpg is 400×300; scaled to width=320 it becomes 320×240 — the canvas clips the bottom half.
        $demos['imageLoad'] = $this->runDemo(
            'IMAGE: load a file',
            'A photo loaded and scaled to fill the canvas.',
            [
                'XY' => '320,240',
                'format' => 'jpg',
                '10' => 'IMAGE',
                '10.' => [
                    'file' => self::TEST_IMAGE,
                    'file.' => ['width' => '320'],
                ],
            ]
        );

        // IMAGE as background with TEXT overlay.
        // Render a photo first (key 10), then paint niceText with a shadow on top (key 20).
        // A semi-transparent dark shadow makes the white text legible over busy image content.
        $demos['imageTextOverlay'] = $this->runDemo(
            'IMAGE + TEXT overlay',
            'A photo with text and a drop-shadow painted on top.',
            [
                'XY' => '320,240',
                'format' => 'jpg',
                '10' => 'IMAGE',
                '10.' => [
                    'file' => self::TEST_IMAGE,
                    'file.' => ['width' => '320'],
                ],
                '20' => 'TEXT',
                '20.' => [
                    'text' => 'Hello GIFBUILDER',
                    'fontColor' => '#ffffff',
                    'fontSize' => '28',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,130',
                    'niceText' => '1',
                    'shadow.' => [
                        'offset' => '2,2',
                        'blur' => '25',
                        'opacity' => '80',
                        'color' => '#000000',
                    ],
                ],
            ]
        );

        // IMAGE with mask — reveal one image through a grayscale mask.
        // The orange background (key 10) is always visible. Key 20 composites test.jpg on top,
        // but only where mask-black-white.gif is white; black mask areas are transparent,
        // letting the orange layer show through.
        $demos['imageMask'] = $this->runDemo(
            'IMAGE with mask',
            'A photo composited onto an orange background through a grayscale mask.',
            [
                'XY' => '320,240',
                'format' => 'jpg',
                '10' => 'IMAGE',
                '10.' => [
                    'file' => self::BACKGROUND_IMAGE,
                    'file.' => ['width' => '320'],
                ],
                '20' => 'IMAGE',
                '20.' => [
                    'file' => self::TEST_IMAGE,
                    'file.' => ['width' => '320'],
                    'mask' => self::MASK_IMAGE,
                    'mask.' => ['width' => '320'],
                ],
            ]
        );

        // CROP — reduces the canvas to a sub-rectangle after other objects have rendered.
        // `crop = offsetX,offsetY,width,height`. Positive offsets pick a window out of the existing
        // canvas; negative offsets pad the canvas. Here we render a colourful scene on a 320×240
        // canvas and then crop down to a 200×150 window (still 4:3).
        $demos['crop'] = $this->runDemo(
            'CROP',
            'A scene rendered at 320×240 and cropped to a 200×150 window.',
            [
                'XY' => '320,240',
                'backColor' => '#1a1a2e',
                'format' => 'png',
                '10' => 'BOX',
                '10.' => ['color' => '#e94560', 'dimensions' => '0,0,320,240'],
                '20' => 'ELLIPSE',
                '20.' => ['color' => '#60a5fa', 'dimensions' => '160,120,240,180'],
                '30' => 'TEXT',
                '30.' => [
                    'text' => 'CROP',
                    'fontColor' => '#ffffff',
                    'fontSize' => '60',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,140',
                    'niceText' => '1',
                ],
                '40' => 'CROP',
                '40.' => ['crop' => '60,45,200,150', 'backColor' => '#000000'],
            ]
        );

        // SCALE — resamples the entire canvas to a new pixel size after all prior
        // objects have painted. `width` and `height` accept the same processing instructions as
        // TYPO3's image scaling. Here we render at 320×240 and scale down to 160×120.
        $demos['scale'] = $this->runDemo(
            'SCALE',
            'A scene rendered at 320×240 and resampled down to 160×120.',
            [
                'XY' => '320,240',
                'backColor' => '#1a1a2e',
                'format' => 'png',
                '10' => 'BOX',
                '10.' => ['color' => '#0f3460', 'dimensions' => '0,0,320,240'],
                '20' => 'ELLIPSE',
                '20.' => ['color' => '#e94560', 'dimensions' => '160,120,260,200'],
                '30' => 'TEXT',
                '30.' => [
                    'text' => 'SCALE',
                    'fontColor' => '#ffffff',
                    'fontSize' => '60',
                    'fontFile' => self::FONT,
                    'align' => 'center',
                    'offset' => '0,140',
                    'niceText' => '1',
                ],
                '40' => 'SCALE',
                '40.' => ['width' => '160', 'height' => '120'],
            ]
        );

        return $demos;
    }

    /**
     * @param array $conf GIFBUILDER TypoScript configuration
     * @return array{url: string, info: string, error: string, phpSnippet: string, typoScriptSnippet: string}
     */
    private function runDemo(string $title, string $description, array $conf): array
    {
        $base = [
            'title' => $title,
            'description' => $description,
            'phpSnippet' => $this->createPhpCodeSnippet($conf),
            'typoScriptSnippet' => $this->createTypoScriptSnippet($conf),
        ];
        $gifBuilder = new GifBuilder();
        $gifBuilder->start($conf, []);
        $imageResource = $gifBuilder->gifBuild();
        if ($imageResource === null) {
            return ['url' => '', 'info' => '—', 'error' => 'gifBuild() returned null.', ...$base];
        }
        $fullPath = $imageResource->getFullPath();
        if (!is_file($fullPath)) {
            return ['url' => '', 'info' => '—', 'error' => 'Output file missing: ' . PathUtility::basename($fullPath), ...$base];
        }
        $info = sprintf(
            '%d×%d · %s',
            $imageResource->getWidth(),
            $imageResource->getHeight(),
            GeneralUtility::formatSize(new FileInfo($fullPath)->getSize(), 'iec'),
        );
        return ['url' => PathUtility::getAbsoluteWebPath($fullPath), 'info' => $info, 'error' => '', ...$base];
    }

    private function createPhpCodeSnippet(array $config): string
    {
        $arrayLiteral = ArrayUtility::arrayExport($config);
        return <<<PHP
use TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder;
use TYPO3\\CMS\\Core\\Utility\\GeneralUtility;

\$gifBuilder = GeneralUtility::makeInstance(GifBuilder::class);
\$gifBuilder->start({$arrayLiteral}, []);
\$imageResource = \$gifBuilder->gifBuild();
PHP;
    }

    private function createTypoScriptSnippet(array $config): string
    {
        $body = $this->arrayToTypoScript($config, 2);
        return <<<TS
page.10 = IMAGE
page.10 {
  file = GIFBUILDER
  file {
{$body}
  }
}
TS;
    }

    private function arrayToTypoScript(array $config, int $indent = 0): string
    {
        $pad = str_repeat('  ', $indent);
        $lines = [];
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $displayKey = rtrim((string)$key, '.');
                $lines[] = $pad . $displayKey . ' {';
                $lines[] = $this->arrayToTypoScript($value, $indent + 1);
                $lines[] = $pad . '}';
            } else {
                $lines[] = $pad . $key . ' = ' . $value;
            }
        }
        return implode("\n", $lines);
    }

    private function createModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $languageService = $this->getLanguageService();
        $moduleTitle = $languageService->sL('styleguide.messages:styleguide');
        $actionTitle = $languageService->sL('styleguide.messages:action.gifBuilder');

        $view = $this->moduleTemplateFactory->create($request);
        $view->setLayout(ModuleLayout::NORMAL);
        $view->setTitle($moduleTitle, $actionTitle);
        $view->setModuleClass('module-styleguide');
        $view->makeDocHeaderModuleMenu();
        $view->getDocHeaderComponent()->setShortcutContext(
            'styleguide_graphical_functions',
            $moduleTitle . ' - ' . $actionTitle,
            ['action' => 'gifBuilder'],
        );
        return $view;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
