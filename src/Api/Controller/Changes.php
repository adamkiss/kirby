<?php

namespace Kirby\Api\Controller;

use Kirby\Cms\ModelWithContent;
use Kirby\Content\Lock;
use Kirby\Content\VersionId;
use Kirby\Filesystem\F;
use Kirby\Form\Form;

/**
 * The Changes controller takes care of the request logic
 * to save, discard and publish changes.
 *
 * @package   Kirby Api
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 */
class Changes
{
	/**
	 * Cleans up legacy lock files
	 */
	protected static function cleanup(ModelWithContent $model): void
	{
		F::remove(Lock::legacyFile($model));
	}

	/**
	 * Discards unsaved changes by deleting the changes version
	 */
	public static function discard(ModelWithContent $model): array
	{
		$model->version(VersionId::changes())->delete('current');

		static::cleanup($model);

		return [
			'status' => 'ok'
		];
	}

	/**
	 * Saves the lastest state of changes first and then publishs them
	 */
	public static function publish(ModelWithContent $model, array $input): array
	{
		// save the given changes first
		static::save(
			model: $model,
			input: $input
		);

		static::cleanup($model);

		// get the changes version
		$changes = $model->version(VersionId::changes());

		// if the changes version does not exist, we need to return early
		if ($changes->exists('current') === false) {
			return [
				'status' => 'ok',
			];
		}

		// publish the changes
		$changes->publish(
			language: 'current'
		);

		return [
			'status' => 'ok'
		];
	}

	/**
	 * Saves form input in a new or existing `changes` version
	 */
	public static function save(ModelWithContent $model, array $input): array
	{
		// we need to run the input through the form
		// class to get a set of storable field values
		// that we can send to the content storage handler
		$form = Form::for($model, [
			'ignoreDisabled' => true,
			'input'          => $input,
		]);

		$changes = $model->version(VersionId::changes());
		$latest  = $model->version(VersionId::latest());

		static::cleanup($model);

		// combine the new field changes with the
		// last published state
		$changes->save(
			fields: [
				...$latest->read(),
				...$form->strings(),
			],
			language: 'current'
		);

		if ($changes->isIdentical(version: $latest, language: 'current')) {
			$changes->delete(
				language: 'current'
			);
		}

		return [
			'status' => 'ok'
		];
	}
}
