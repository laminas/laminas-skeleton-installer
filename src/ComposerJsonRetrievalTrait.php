<?php
/**
 * @link      http://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\SkeletonInstaller;

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
