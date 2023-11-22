<?php

declare(strict_types=1);

namespace LaminasTest\SkeletonInstaller;

use Laminas\SkeletonInstaller\OptionalPackage;
use PHPUnit\Framework\TestCase;

class OptionalPackageTest extends TestCase
{
    public function testMarksAsProductionPackageByDefault(): void
    {
        $package = new OptionalPackage([
            'constraint' => '^1.0',
            'name'       => 'foo/bar',
            'prompt'     => '',
        ]);

        $this->assertFalse($package->isDev());
    }

    public static function booleanOptions(): array
    {
        return [
            'null'            => [null, false],
            'false'           => [false, false],
            'true'            => [true, true],
            'zero'            => [0, false],
            'int'             => [1, true],
            'zero-float'      => [0.0, false],
            'float'           => [1.1, true],
            'empty-string'    => ['', false],
            'string'          => ['true', true],
            'empty-array'     => [[], false],
            'non-empty-array' => [[false], true],
        ];
    }

    /**
     * @dataProvider booleanOptions
     * @param mixed $option
     * @param bool $expected
     */
    public function testCastsDevOptionsToBooleans($option, $expected): void
    {
        $package = new OptionalPackage([
            'constraint' => '^1.0',
            'name'       => 'foo/bar',
            'prompt'     => '',
            'dev'        => $option,
        ]);

        $this->assertSame($expected, $package->isDev());
    }

    public function testPackagesAreNotModulesByDefault(): void
    {
        $package = new OptionalPackage([
            'constraint' => '^1.0',
            'name'       => 'foo/bar',
            'prompt'     => '',
        ]);

        $this->assertFalse($package->isModule());
    }

    /**
     * @dataProvider booleanOptions
     * @param mixed $option
     * @param bool $expected
     */
    public function testCastsModuleOptionsToBooleans($option, $expected): void
    {
        $package = new OptionalPackage([
            'constraint' => '^1.0',
            'name'       => 'foo/bar',
            'prompt'     => '',
            'module'     => $option,
        ]);

        $this->assertSame($expected, $package->isModule());
    }

    public static function specifications(): array
    {
        // @codingStandardsIgnoreStart
        //                          specification                                                 expectation
        return [
            'empty'             => [[],                                                                 false],
            'name-only'         => [['name' => 'foo/bar'],                                              false],
            'constraint-only'   => [['constraint' => '^1.0'],                                           false],
            'prompt-only'       => [['prompt' => 'prompt'],                                             false],
            'name-constraint'   => [['name' => 'foo/bar', 'constraint' => '^1.0'],                      false],
            'name-prompt'       => [['name' => 'foo/bar', 'prompt' => 'prompt'],                        false],
            'constraint-prompt' => [['constraint' => '^1.0', 'prompt' => 'prompt'],                     false],
            'valid'             => [['name' => 'foo/bar', 'constraint' => '^1.0', 'prompt' => 'prompt'], true],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider specifications
     * @param array $specification
     * @param bool $expected
     */
    public function testCanValidateSpecifications(array $specification, $expected): void
    {
        $this->assertSame($expected, OptionalPackage::isValidSpec($specification));
    }
}
