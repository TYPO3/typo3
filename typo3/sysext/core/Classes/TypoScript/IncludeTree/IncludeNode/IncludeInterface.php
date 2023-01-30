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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;

/**
 * General interface of IncludeTree tree nodes.
 *
 * The TreeBuilder classes return a tree of these nodes, with the root node being
 * a RootInclude. Each "include type" is represented by an own class: There
 * is for instance "SysTemplateInclude" for a node that represents a sys_template
 * row, and DefaultTypoScriptInclude for the default TypoScript string included from
 * TYPO3_CONF_VARS.
 *
 * Nodes may have children, and a single stream of lines from the tokenizer
 * may be split into multiple children: Each @import creates an own child node,
 * and condition trigger splitting as well.
 *
 * @internal: Internal tree structure.
 */
interface IncludeInterface
{
    /**
     * A human-readable string derived from class name - Used in BE template analyzer
     */
    public function getType(): string;

    /**
     * An identifier for this include. Typically, a hash of some kind. This identifier
     * is unique within the tree, by being created from the parent identifier plus
     * something unique for this level like a counter. This identifier is used in the backend,
     * when referencing single includes to be rendered.
     * Calculating identifiers is initiated by calling setIdentifier() on RootNode, which
     * will recurse the tree. Call this on the final tree, after include calculation finished,
     * so include building itself does not need to fiddle with identifier updates.
     * Note this value is skipped when persisting to caches since it's a Backend related
     * thing that does not use cached context: When retrieving includes from cache
     * (e.g. in Frontend), the identifier is null and calling the getter will throw an exception.
     */
    public function setIdentifier(string $identifier): void;
    public function getIdentifier(): string;

    /**
     * A human-readable version of the identifier: Used in backend tree rendering.
     */
    public function setName(string $name): void;
    public function getName(): string;

    /**
     * This is set to non-empty string for includes that represent files. The file location
     * is stored here, typically something like "EXT:my_extension/path/to/foo.typoscript".
     * This is used when resolving file includes relative to a parent include, so a
     * potential child node knows where to look relative to its parent path.
     * Note this value is skipped when persisting to caches: The parent path
     * information is no longer needed when a tree is fetched from cache since
     * all children were attached already and don't need to be recalculated
     * depending on their parent path value.
     */
    public function setPath(string $path): void;
    public function getPath(): string;

    /**
     * Child maintenance methods.
     */
    public function addChild(IncludeInterface $node): void;
    public function hasChildren(): bool;

    /**
     * @return iterable<IncludeInterface>
     */
    public function getNextChild(): iterable;

    /**
     * True for IncludeTypoScriptInclude - this node represents a sys_template record.
     * When true, methods like isRoot() and isClear() are relevant.
     */
    public function isSysTemplateRecord(): bool;

    /**
     * The source split into single lines by a tokenizer.
     */
    public function setLineStream(?LineStream $lineStream): void;
    public function getLineStream(): ?LineStream;

    /**
     * When an imports are handled, such a line is substituted by the included
     * content. To be able to still output the original line, it is parked here.
     * Relevant in backend tree and source display only.
     */
    public function setOriginalLine(LineInterface $line): void;
    public function getOriginalLine(): ?LineInterface;

    /**
     * When included line streams contain conditions or imports, the node is split into
     * children that contain single segments of the source. The node itself is then just
     * a container and the LineStream attached is irrelevant for further processing.
     * This flag is set when a line stream is split and the children fully represent the source.
     */
    public function setSplit(): void;
    public function isSplit(): bool;

    /**
     * Set to true for IncludeTypoScriptInclude's (sys_template records) when "root" flag is set.
     */
    public function setRoot(bool $root): void;
    public function isRoot(): bool;

    /**
     * Set to true for IncludeTypoScriptInclude's (sys_template records) when "clear constants"
     * or "clear setup" is set. Depends on context if currently constants or setup are parsed.
     */
    public function setClear(bool $clear): void;
    public function isClear(): bool;

    /**
     * Set to the pid of IncludeTypoScriptInclude's (sys_template records). Relevant in backend
     * tree rendering only.
     */
    public function setPid(int $pid): void;
    public function getPid(): ?int;
}
