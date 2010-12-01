<?php

include_once 'vfsStream/vfsStreamWrapper.php';
include_once 'vfsStream/vfsStream.php';

class t3lib_file_storage_FileSystemStorageTest extends tx_phpunit_testcase {
	/**
	 * @test
	 */
	public function basePathAlwaysEndsWithSlash() {
		$storageConfiguration = array('relative' => TRUE, 'path' => 'relative/path');

		$storageBackend = new tx_fal_storage_FileSystemStorage($storageConfiguration);
		$this->assertEquals(PATH_site . $storageConfiguration['path'] . '/', $storageBackend->getBasePath());

		$storageConfiguration = array('relative' => FALSE, 'path' => '/absolute/path');

		$storageBackend = new tx_fal_storage_FileSystemStorage($storageConfiguration);
		$this->assertEquals($storageConfiguration['path'] . '/', $storageBackend->getBasePath());
	}

	/**
	 * @test
	 */
	public function relativePathIsResolvedCorrectly() {
		$storageConfiguration = array('relative' => 1, 'path' => 'relative/path/');

		$storageBackend = new tx_fal_storage_FileSystemStorage($storageConfiguration);
		$this->assertEquals(PATH_site . $storageConfiguration['path'], $storageBackend->getBasePath());
	}

	/**
	 * @test
	 */
	public function relativePathWithDotsIsResolvedCorrectly() {
		$storageConfiguration = array('relative' => 1, 'path' => '../a/relative/path/outside/the/typo3/site/directory/');

		$storageBackend = new tx_fal_storage_FileSystemStorage($storageConfiguration);
		$expectedPath = substr(PATH_site, 0, strrpos(PATH_site, '/', -2)) . substr($storageConfiguration['path'], strpos($storageConfiguration['path'], '/'));
		$this->assertEquals($expectedPath, $storageBackend->getBasePath());
	}

	/**
	 * @test
	 */
	public function copyCopiesFileToCorrectLocation() {
		$root = vfsStream::setup('root');
		$subdir = vfsStream::newDirectory('somedir')->at($root);
		$file = vfsStream::newFile('source.txt')->at($subdir);
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));
		$storageBackend->copyFile('somedir/source.txt', 'somedir/target.txt');

		$this->assertTrue($subdir->hasChild('target.txt'));
	}

	/**
	 * @test
	 */
	public function copyThrowsExceptionIfSourceFileIsNotFound() {
		$this->setExpectedException('InvalidArgumentException');

		$root = vfsStream::setup('root');
		$subdir = vfsStream::newDirectory('somedir')->at($root);
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));
		$storageBackend->copyFile('somedir/source.txt', 'somedir/target.txt');
	}

	/**
	 * @test
	 */
	public function moveFileMovesToCorrectLocation() {
		$this->markTestSkipped('This test does not work because t3lib_extFileFunc is incompatible with vfsStream.');
		$root = vfsStream::setup('root');
		$subdir = vfsStream::newDirectory('somedir')->at($root);
		$file = vfsStream::newFile('source.txt')->at($subdir);
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));
		$storageBackend->moveFile('somedir/source.txt', 'somedir/target.txt');

		$this->assertFalse($subdir->hasChild('source.txt'));
		$this->assertTrue($subdir->hasChild('target.txt'));
	}

	/**
	 * @test
	 */
	public function moveFileThrowsExceptionIfSourceFileDoesNotExist() {
		$this->setExpectedException('InvalidArgumentException');

		$root = vfsStream::setup('root');
		$subdir = vfsStream::newDirectory('somedir')->at($root);
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));
		$storageBackend->moveFile('somedir/source.txt', 'somedir/target.txt');
	}

	/**
	 * @test
	 */
	public function moveFileThrowsExceptionIfDestinationFileExists() {
		$this->setExpectedException('InvalidArgumentException');

		$root = vfsStream::setup('root');
		$subdir = vfsStream::newDirectory('somedir')->at($root);
		vfsStream::newFile('source.txt')->at($subdir);
		vfsStream::newFile('target.txt')->at($subdir);
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));
		$storageBackend->moveFile('somedir/source.txt', 'somedir/target.txt');
	}

	/**
	 * @test
	 */
	public function createDirectoryCreatesDirectory() {
		$root = vfsStream::setup('root');
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));

		$storageBackend->createDirectory('', 'aDirOnTheFirstLevel');
		$this->assertTrue($root->hasChild('aDirOnTheFirstLevel'));

		$subdir = vfsStream::newDirectory('someDir')->at($root);
		$storageBackend->createDirectory('someDir', 'anotherDir');

		$this->assertTrue($subdir->hasChild('anotherDir'));
	}

	/**
	 * @test
	 */
	public function getListingReturnsCorrectTypesOfElements() {
		$root = vfsStream::setup('root');
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));

		$root->addChild(vfsStream::newFile('aFile'));
		$root->addChild(vfsStream::newDirectory('aFolder'));

		$fileListing = $storageBackend->getListing('');

		$this->assertEquals('file', $fileListing['aFile']['type']);
		$this->assertEquals('dir', $fileListing['aFolder']['type']);
	}

	/**
	 * @test
	 */
	public function getListingCanReturnItemsInSubpath() {
		$root = vfsStream::setup('root');
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));

		$subDir = vfsStream::newDirectory('somedir')->at($root);
		vfsStream::newFile('aFile')->at($subDir);

		$dirListing = $storageBackend->getListing('somedir');

		$this->assertArrayHasKey('aFile', $dirListing);

		//$this->markTestIncomplete();
	}

	/**
	 * @test
	 */
	public function getListingReturnsAllFilesAndFoldersInPath() {
		$fileList = array(
			'file_' . uniqid(),
			'file_' . uniqid(),
			'file_' . uniqid(),
			'file_' . uniqid()
		);
		$folderList = array(
			'folder_' . uniqid(),
			'folder_' . uniqid(),
			'folder_' . uniqid(),
			'folder_' . uniqid(),
		);
		$root = vfsStream::setup('root');
		$storageBackend = new tx_fal_storage_FileSystemStorage(array('path' => vfsStream::url('root'), 'relative' => FALSE));

		foreach ($fileList as $fileName) {
			$root->addChild(vfsStream::newFile($fileName));
		}
		foreach ($folderList as $folderName) {
			$root->addChild(vfsStream::newDirectory($folderName));
		}

		$dirListing = $storageBackend->getListing('');

		foreach ($fileList as $expectedFile) {
			$this->assertArrayHasKey($expectedFile, $dirListing);
			$this->assertEquals($expectedFile, $dirListing[$expectedFile]['name']);
		}
		foreach ($folderList as $expectedFolder) {
			$this->assertArrayHasKey($expectedFile, $dirListing);
			$this->assertEquals($expectedFile, $dirListing[$expectedFile]['name']);
		}
	}
}