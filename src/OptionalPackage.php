<?php
/**
 * @see       https://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\SkeletonInstaller;

class OptionalPackage
{
    private $constraint;

    private $dev = false;

    private $module = false;

    private $name;

    private $prompt;

    public function __construct(array $spec)
    {
        $this->constraint = $spec['constraint'];
        $this->name = $spec['name'];
        $this->prompt = $spec['prompt'];

        if (array_key_exists('dev', $spec)) {
            $this->dev = (bool) $spec['dev'];
        }

        if (array_key_exists('module', $spec)) {
            $this->module = (bool) $spec['module'];
        }
    }

    public static function isValidSpec(array $spec)
    {
        return (
            array_key_exists('name', $spec)
            && array_key_exists('constraint', $spec)
            && array_key_exists('prompt', $spec)
        );
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPrompt()
    {
        return $this->prompt;
    }

    public function getConstraint()
    {
        return $this->constraint;
    }

    public function isDev()
    {
        return $this->dev;
    }

    public function isModule()
    {
        return $this->module;
    }
}
