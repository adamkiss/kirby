<?php

namespace Kirby\Content;

use Kirby\Data\Data;
use Kirby\Exception\NotFoundException;

/**
 * @coversDefaultClass Kirby\Content\Version
 * @covers ::__construct
 */
class VersionTest extends TestCase
{
	public const TMP = KIRBY_TMP_DIR . '/Content.Version';

	public function assertContentFileExists(string|null $language = null, VersionId|null $versionId = null)
	{
		$this->assertFileExists($this->contentFile($language, $versionId));
	}

	public function assertContentFileDoesNotExist(string|null $language = null, VersionId|null $versionId = null)
	{
		$this->assertFileDoesNotExist($this->contentFile($language, $versionId));
	}

	public function contentFile(string|null $language = null, VersionId|null $versionId = null): string
	{
		return
			$this->model->root() .
			// add the changes folder
			($versionId?->value() === 'changes' ? '/_changes/' : '/') .
			// template
			'article' .
			// language code
			($language === null ? '' : '.' . $language) .
			'.txt';
	}

	public function createContentMultiLanguage(): array
	{
		Data::write($fileEN = $this->contentFile('en'), $contentEN = [
			'title'    => 'Title English',
			'subtitle' => 'Subtitle English'
		]);

		Data::write($fileDE = $this->contentFile('de'), $contentDE = [
			'title'    => 'Title Deutsch',
			'subtitle' => 'Subtitle Deutsch'
		]);

		return [
			'en' => [
				'content' => $contentEN,
				'file'    => $fileEN,
			],
			'de' => [
				'content' => $contentDE,
				'file'    => $fileDE,
			]
		];
	}

	public function createContentSingleLanguage(): array
	{
		Data::write($file = $this->contentFile(), $content = [
			'title'    => 'Title',
			'subtitle' => 'Subtitle'
		]);

		return [
			'content' => $content,
			'file'    => $file
		];
	}

	/**
	 * @covers ::content
	 */
	public function testContentMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentMultiLanguage();

		$this->assertSame($expected['en']['content']['title'], $version->content('en')->get('title')->value());
		$this->assertSame($expected['en']['content']['title'], $version->content($this->app->language('en'))->get('title')->value());
		$this->assertSame($expected['en']['content']['title'], $version->content()->get('title')->value());
		$this->assertSame($expected['de']['content']['title'], $version->content('de')->get('title')->value());
		$this->assertSame($expected['de']['content']['title'], $version->content($this->app->language('de'))->get('title')->value());
	}

	/**
	 * @covers ::content
	 */
	public function testContentSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentSingleLanguage();

		$this->assertSame($expected['content']['title'], $version->content()->get('title')->value());
	}

	/**
	 * @covers ::contentFile
	 */
	public function testContentFileMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertSame($this->contentFile('en'), $version->contentFile());
		$this->assertSame($this->contentFile('en'), $version->contentFile('en'));
		$this->assertSame($this->contentFile('en'), $version->contentFile($this->app->language('en')));
		$this->assertSame($this->contentFile('de'), $version->contentFile('de'));
		$this->assertSame($this->contentFile('de'), $version->contentFile($this->app->language('de')));
	}

	/**
	 * @covers ::contentFile
	 */
	public function testContentFileSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertSame($this->contentFile(), $version->contentFile());
	}

	/**
	 * @covers ::create
	 */
	public function testCreateMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertContentFileDoesNotExist('en');
		$this->assertContentFileDoesNotExist('de');

		// with Language argument
		$version->create([
			'title' => 'Test'
		], $this->app->language('en'));

		// with string argument
		$version->create([
			'title' => 'Test'
		], 'de');

		$this->assertContentFileExists('en');
		$this->assertContentFileExists('de');
	}

	/**
	 * @covers ::create
	 */
	public function testCreateSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertContentFileDoesNotExist();

		$version->create([
			'title' => 'Test'
		]);

		$this->assertContentFileExists();
	}

	/**
	 * @covers ::delete
	 */
	public function testDeleteMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertContentFileDoesNotExist('de');
		$this->assertContentFileDoesNotExist('en');

		$this->createContentMultiLanguage();

		$this->assertContentFileExists('en');
		$this->assertContentFileExists('de');

		$version->delete();

		$this->assertContentFileDoesNotExist('en');
		$this->assertContentFileDoesNotExist('de');
	}

	/**
	 * @covers ::delete
	 */
	public function testDeleteSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertContentFileDoesNotExist();

		$this->createContentSingleLanguage();

		$this->assertContentFileExists();

		$version->delete();

		$this->assertContentFileDoesNotExist();
	}

	/**
	 * @covers ::ensure
	 */
	public function testEnsureMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->createContentMultiLanguage();

		$this->assertNull($version->ensure('en'));
		$this->assertNull($version->ensure($this->app->language('en')));

		$this->assertNull($version->ensure('de'));
		$this->assertNull($version->ensure($this->app->language('de')));
	}

	/**
	 * @covers ::ensure
	 */
	public function testEnsureSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->createContentSingleLanguage();

		$this->assertNull($version->ensure());
	}

	/**
	 * @covers ::ensure
	 */
	public function testEnsureWhenMissingMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('Version "published (de)" does not already exist');

		$version->ensure('de');
	}

	/**
	 * @covers ::ensure
	 */
	public function testEnsureWhenMissingSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('Version "published" does not already exist');

		$version->ensure();
	}

	/**
	 * @covers ::ensure
	 */
	public function testEnsureWithInvalidLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('Invalid language: fr');

		$version->ensure('fr');
	}

	/**
	 * @covers ::exists
	 */
	public function testExistsMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertFalse($version->exists('en'));
		$this->assertFalse($version->exists($this->app->language('en')));

		$this->assertFalse($version->exists('de'));
		$this->assertFalse($version->exists($this->app->language('de')));

		$this->createContentMultiLanguage();

		$this->assertTrue($version->exists('en'));
		$this->assertTrue($version->exists($this->app->language('en')));

		$this->assertTrue($version->exists('de'));
		$this->assertTrue($version->exists($this->app->language('de')));
	}

	/**
	 * @covers ::exists
	 */
	public function testExistsSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertFalse($version->exists());

		$this->createContentSingleLanguage();

		$this->assertTrue($version->exists());
	}

	/**
	 * @covers ::id
	 */
	public function testId(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: $id = VersionId::published()
		);

		$this->assertSame($id, $version->id());
	}

	/**
	 * @covers ::model
	 */
	public function testModel(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertSame($this->model, $version->model());
	}

	/**
	 * @covers ::modified
	 */
	public function testModifiedMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		touch($this->contentFile('de'), $modified = 123456);

		$this->assertSame($modified, $version->modified('de'));
		$this->assertSame($modified, $version->modified($this->app->language('de')));
	}

	/**
	 * @covers ::modified
	 */
	public function testModifiedMultiLanguageIfNotExists(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertNull($version->modified('en'));
		$this->assertNull($version->modified($this->app->language('en')));
		$this->assertNull($version->modified('de'));
		$this->assertNull($version->modified($this->app->language('de')));
	}

	/**
	 * @covers ::modified
	 */
	public function testModifiedSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		touch($this->contentFile(), $modified = 123456);

		$this->assertSame($modified, $version->modified());
	}

	/**
	 * @covers ::modified
	 */
	public function testModifiedSingleLanguageIfNotExists(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertNull($version->modified());
	}

	/**
	 * @covers ::move
	 */
	public function testMoveToLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: $versionId = VersionId::published()
		);

		$this->assertContentFileDoesNotExist('en');
		$this->assertContentFileDoesNotExist('de');

		$fileEN = $this->contentFile('en');
		$fileDE = $this->contentFile('de');

		Data::write($fileEN, $content = [
			'title' => 'Test'
		]);

		$this->assertContentFileExists('en');
		$this->assertContentFileDoesNotExist('de');

		// move with string arguments
		$version->move('en', $versionId, 'de');

		$this->assertContentFileDoesNotExist('en');
		$this->assertContentFileExists('de');

		$this->assertSame($content, Data::read($fileDE));

		// move with Language arguments
		$version->move($this->app->language('de'), $versionId, $this->app->language('en'));

		$this->assertContentFileExists('en');
		$this->assertContentFileDoesNotExist('de');

		$this->assertSame($content, Data::read($fileEN));
	}

	/**
	 * @covers ::move
	 */
	public function testMoveToVersion(): void
	{
		$this->setUpMultiLanguage();

		$versionPublished = new Version(
			model: $this->model,
			id: $versionIdPublished = VersionId::published()
		);

		$versionChanges = new Version(
			model: $this->model,
			id: $versionIdChanges = VersionId::changes()
		);

		$this->assertContentFileDoesNotExist('en', $versionIdPublished);
		$this->assertContentFileDoesNotExist('en', $versionIdChanges);

		$fileENPublished = $this->contentFile('en', $versionIdPublished);
		$fileENChanges   = $this->contentFile('en', $versionIdChanges);

		Data::write($fileENPublished, $content = [
			'title' => 'Test'
		]);

		$this->assertContentFileExists('en', $versionIdPublished);
		$this->assertContentFileDoesNotExist('en', $versionIdChanges);

		// move with string arguments
		$versionPublished->move('en', $versionIdChanges, 'en');

		$this->assertContentFileDoesNotExist('en', $versionIdPublished);
		$this->assertContentFileExists('en', $versionIdChanges);

		$this->assertSame($content, Data::read($fileENChanges));

		// move the version back
		$versionChanges->move('en', $versionIdPublished, 'en');

		$this->assertContentFileDoesNotExist('en', $versionIdChanges);
		$this->assertContentFileExists('en', $versionIdPublished);

		$this->assertSame($content, Data::read($fileENPublished));
	}

	/**
	 * @covers ::read
	 */
	public function testReadMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentMultiLanguage();

		$this->assertSame($expected['en']['content'], $version->read('en'));
		$this->assertSame($expected['en']['content'], $version->read($this->app->language('en')));
		$this->assertSame($expected['de']['content'], $version->read('de'));
		$this->assertSame($expected['de']['content'], $version->read($this->app->language('de')));
	}

	/**
	 * @covers ::read
	 */
	public function testReadSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentSingleLanguage();

		$this->assertSame($expected['content'], $version->read());
	}

	/**
	 * @covers ::replace
	 */
	public function testReplaceMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentMultiLanguage();

		// with Language argument
		$version->replace([
			'title' => 'Updated Title English'
		], $this->app->language('en'));

		// with string argument
		$version->replace([
			'title' => 'Updated Title Deutsch',
		], 'de');

		$this->assertSame(['title' => 'Updated Title English'], Data::read($expected['en']['file']));
		$this->assertSame(['title' => 'Updated Title Deutsch'], Data::read($expected['de']['file']));
	}

	/**
	 * @covers ::replace
	 */
	public function testReplaceSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentSingleLanguage();

		$version->replace([
			'title' => 'Updated Title'
		]);

		$this->assertSame(['title' => 'Updated Title'], Data::read($expected['file']));
	}

	/**
	 * @covers ::save
	 */
	public function testSaveExistingMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentMultiLanguage();

		// with Language argument
		$version->save([
			'title' => 'Updated Title English'
		], $this->app->language('en'));

		// with string argument
		$version->save([
			'title' => 'Updated Title Deutsch',
		], 'de');

		$this->assertSame('Updated Title English', Data::read($expected['en']['file'])['title']);
		$this->assertSame('Subtitle English', Data::read($expected['en']['file'])['subtitle']);
		$this->assertSame('Updated Title Deutsch', Data::read($expected['de']['file'])['title']);
		$this->assertSame('Subtitle Deutsch', Data::read($expected['de']['file'])['subtitle']);
	}

	/**
	 * @covers ::save
	 */
	public function testSaveExistingSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentSingleLanguage();

		$version->save([
			'title' => 'Updated Title'
		]);

		$this->assertSame('Updated Title', Data::read($expected['file'])['title']);
		$this->assertSame('Subtitle', Data::read($expected['file'])['subtitle']);
	}

	/**
	 * @covers ::save
	 */
	public function testSaveNonExistingMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertContentFileDoesNotExist('en');
		$this->assertContentFileDoesNotExist('de');

		// with Language argument
		$version->save([
			'title' => 'Test'
		], $this->app->language('en'));

		// with string argument
		$version->save([
			'title' => 'Test'
		], 'de');

		$this->assertContentFileExists('en');
		$this->assertContentFileExists('de');
	}

	/**
	 * @covers ::save
	 */
	public function testSaveNonExistingSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$this->assertContentFileDoesNotExist();

		$version->save([
			'title' => 'Test'
		]);

		$this->assertContentFileExists();
	}

	/**
	 * @covers ::save
	 */
	public function testSaveOverwriteMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentMultiLanguage();

		// with Language argument
		$version->save([
			'title' => 'Updated Title English'
		], $this->app->language('en'), true);

		// with string argument
		$version->save([
			'title' => 'Updated Title Deutsch',
		], 'de', true);

		$this->assertSame(['title' => 'Updated Title English'], Data::read($expected['en']['file']));
		$this->assertSame(['title' => 'Updated Title Deutsch'], Data::read($expected['de']['file']));
	}

	/**
	 * @covers ::save
	 */
	public function testSaveOverwriteSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentSingleLanguage();

		$version->save([
			'title' => 'Updated Title'
		], 'default', true);

		$this->assertSame(['title' => 'Updated Title'], Data::read($expected['file']));
	}

	/**
	 * @covers ::touch
	 */
	public function testTouchMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		touch($rootEN = $this->contentFile('en'), 123456);
		touch($rootDE = $this->contentFile('de'), 123456);

		$this->assertSame(123456, filemtime($rootEN));
		$this->assertSame(123456, filemtime($rootDE));

		$minTime = time();

		// with Language argument
		$version->touch($this->app->language('en'));

		// with string argument
		$version->touch('de');

		clearstatcache();

		$this->assertGreaterThanOrEqual($minTime, filemtime($rootEN));
		$this->assertGreaterThanOrEqual($minTime, filemtime($rootDE));
	}

	/**
	 * @covers ::touch
	 */
	public function testTouchSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		touch($root = $this->contentFile(), 123456);
		$this->assertSame(123456, filemtime($root));

		$minTime = time();

		$version->touch();

		clearstatcache();

		$this->assertGreaterThanOrEqual($minTime, filemtime($root));
	}

	/**
	 * @covers ::update
	 */
	public function testUpdateMultiLanguage(): void
	{
		$this->setUpMultiLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentMultiLanguage();

		// with Language argument
		$version->update([
			'title' => 'Updated Title English'
		], $this->app->language('en'));

		// with string argument
		$version->update([
			'title' => 'Updated Title Deutsch',
		], 'de');

		$this->assertSame('Updated Title English', Data::read($expected['en']['file'])['title']);
		$this->assertSame('Subtitle English', Data::read($expected['en']['file'])['subtitle']);
		$this->assertSame('Updated Title Deutsch', Data::read($expected['de']['file'])['title']);
		$this->assertSame('Subtitle Deutsch', Data::read($expected['de']['file'])['subtitle']);
	}

	/**
	 * @covers ::update
	 */
	public function testUpdateSingleLanguage(): void
	{
		$this->setUpSingleLanguage();

		$version = new Version(
			model: $this->model,
			id: VersionId::published()
		);

		$expected = $this->createContentSingleLanguage();

		$version->update([
			'title' => 'Updated Title'
		]);

		$this->assertSame('Updated Title', Data::read($expected['file'])['title']);
		$this->assertSame('Subtitle', Data::read($expected['file'])['subtitle']);
	}
}
