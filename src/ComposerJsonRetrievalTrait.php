<?php

/**
 * @see       https://github.com/laminas/laminas-skeleton-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-skeleton-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\SkeletonInstaller;

use Composer\Factory;
use Composer\Json\JsonFile;

trait ComposerJsonRetrievalTrait
{
    /**
     * @var callable Factory to use for returning the composer.json path
     */
    private $composerFileFactory = [Factory::class, 'getComposerFile'];

    /**
     * Retrieve the project composer.json as a JsonFile.
     *
     * @return JsonFile
     */
    private function getComposerJson()
    {
        $composerFile = call_user_func($this->composerFileFactory);
        return new JsonFile($composerFile);
    }
}
