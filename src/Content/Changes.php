<?php

namespace Kirby\Content;

use Kirby\Cache\Cache;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Site;
use Kirby\Cms\User;
use Kirby\Cms\Users;
use Kirby\Toolkit\A;

/**
 * The Changes class tracks changed models
 * in the Site's changes field.
 *
 * @package   Kirby Content
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 */
class Changes
{
	/**
	 * Access helper for the cache, in which changes are stored
	 */
	public function cache(): Cache
	{
		return App::instance()->cache('changes');
	}

	/**
	 * Returns the cache key for a given model
	 */
	public function cacheKey(ModelWithContent $model): string
	{
		return match(true) {
			$model instanceof File => 'files',
			$model instanceof Page => 'pages',
			$model instanceof User => 'users'
		};
	}

	/**
	 * Return all files with unsaved changes
	 */
	public function files(): Files
	{
		$files = new Files([]);
		$kirby = App::instance();

		foreach ($this->read('files') as $id) {
			if ($file = $kirby->file($id)) {
				$files->add($file);
			}
		}

		return $files;
	}

	/**
	 * Return all pages with unsaved changes
	 */
	public function pages(): Pages
	{
		return App::instance()->site()->find(
			false,
			false,
			...$this->read('pages')
		);
	}

	/**
	 * Read the changes for a given model type
	 */
	public function read(string $key): array
	{
		return $this->cache()->get($key) ?? [];
	}

	/**
	 * Add a new model to the list of unsaved changes
	 */
	public function track(ModelWithContent $model): void
	{
		$key = $this->cacheKey($model);

		$changes = $this->read($key);
		$changes[] = (string)$model->uuid();

		$this->update($key, $changes);
	}

	/**
	 * Remove a model from the list of unsaved changes
	 */
	public function untrack(ModelWithContent $model): void
	{
		// get the cache key for the model type
		$key = $this->cacheKey($model);

		// remove the model from the list of changes
		$changes = A::filter(
			$this->read($key),
			fn ($uuid) => $uuid !== (string)$model->uuid()
		);

		$this->update($key, $changes);
	}

	/**
	 * Update the changes field
	 */
	public function update(string $key, array $changes): void
	{
		$changes = array_unique($changes);
		$changes = array_values($changes);

		$this->cache()->set($key, $changes);
	}

	/**
	 * Return all users with unsaved changes
	 */
	public function users(): Users
	{
		return App::instance()->users()->find(
			false,
			false,
			...$this->read('users')
		);
	}
}
