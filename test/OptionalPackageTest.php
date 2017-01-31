<?php
/**
 * @see       https://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\SkeletonInstaller;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\SkeletonInstaller\OptionalPackage;

class OptionalPackageTest extends TestCase
{
    public function testMarksAsProductionPackageByDefault()
    {
        $package = new OptionalPackage([
            'constraint' => '^1.0',
            'name'       => 'foo/bar',
            'prompt'     => '',
        ]);

        $this->assertFalse($package->isDev());
    }

    public function booleanOptions()
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
     */
    public function testCastsDevOptionsToBooleans($option, $expected)
    {
        $package = new OptionalPackage([
            'constraint' => '^1.0',
            'name'       => 'foo/bar',
            'prompt'     => '',
            'dev'        => $option,
        ]);

        $this->assertSame($expected, $package->isDev());
    }

    public function testPackagesAreNotModulesByDefault()
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
     */
    public function testCastsModuleOptionsToBooleans($option, $expected)
    {
        $package = new OptionalPackage([
            'constraint' => '^1.0',
            'name'       => 'foo/bar',
            'prompt'     => '',
            'module'     => $option,
        ]);

        $this->assertSame($expected, $package->isModule());
    }

    public function specifications()
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
     */
    public function testCanValidateSpecifications($specification, $expected)
    {
        $this->assertSame($expected, OptionalPackage::isValidSpec($specification));
    }
}
