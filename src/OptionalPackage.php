<?php

declare(strict_types=1);

namespace Laminas\SkeletonInstaller;

use function array_key_exists;

class OptionalPackage
{
    /** @var string */
    private $constraint;

    /** @var bool */
    private $dev = false;

    /** @var bool */
    private $module = false;

    /** @var string*/
    private $name;

    /** @var string */
    private $prompt;

    public function __construct(array $spec)
    {
        $this->constraint = $spec['constraint'];
        $this->name       = $spec['name'];
        $this->prompt     = $spec['prompt'];

        if (array_key_exists('dev', $spec)) {
            $this->dev = (bool) $spec['dev'];
        }

        if (array_key_exists('module', $spec)) {
            $this->module = (bool) $spec['module'];
        }
    }

    public static function isValidSpec(array $spec): bool
    {
        return array_key_exists('name', $spec)
            && array_key_exists('constraint', $spec)
            && array_key_exists('prompt', $spec);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getConstraint(): string
    {
        return $this->constraint;
    }

    public function isDev(): bool
    {
        return $this->dev;
    }

    public function isModule(): bool
    {
        return $this->module;
    }
}
