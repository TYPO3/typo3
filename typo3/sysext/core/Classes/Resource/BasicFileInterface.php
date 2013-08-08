<?php
namespace TYPO3\CMS\Core\Resource;

/**
 * A basic interface for files. It exposes all intrinsic properties of the file,
 * like its size, creation/modification date and contents.
 *
 * Implement this to get a basically usable file that can be used almost
 * everywhere a file is required.
 */
interface BasicFileInterface extends ResourceInterface {

	public function setIdentifier();

	public function getExtension();

	public function setName();

	public function getCreationDate();

	public function getModificationDate();

	public function getContentHash($algorithm = NULL);

	public function getAvailableContentHashAlgorithms();

	public function getContent();

	public function getSize();

	public function getMimeType();
}
