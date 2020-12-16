# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.2.2 - 2020-12-16

### Fixed

- [#16](https://github.com/laminas/laminas-skeleton-installer/pull/16) fixes an issue with prompting present when using Composer v2 releases.

- [#15](https://github.com/laminas/laminas-skeleton-installer/pull/15) fixes an issue under Composer v2 whereby a fatal error would occur due to a previously optional method argument now being required.


-----

### Release Notes for [0.2.2](https://github.com/laminas/laminas-skeleton-installer/milestone/3)

0.2.x bugfix release (patch)

### 0.2.2

- Total issues resolved: **2**
- Total pull requests resolved: **2**
- Total contributors: **3**

#### Bug

 - [16: Fix errors prompting users when using Composer v2](https://github.com/laminas/laminas-skeleton-installer/pull/16) thanks to @weierophinney and @rbroen
 - [15: Update OptionalPackagesInstaller.php](https://github.com/laminas/laminas-skeleton-installer/pull/15) thanks to @rbroen
 - [13: Fatal error on non-minimal install (selecting optional packages)](https://github.com/laminas/laminas-skeleton-installer/issues/13) thanks to @PAStheLoD

## 0.2.1 - 2020-09-11


-----

### Release Notes for [0.2.1](https://github.com/laminas/laminas-skeleton-installer/milestone/2)



### 0.2.1

- Total issues resolved: **0**
- Total pull requests resolved: **1**
- Total contributors: **1**

#### Documentation

 - [10: Added note about how this is not to be used stand-alone](https://github.com/laminas/laminas-skeleton-installer/pull/10) thanks to @TomHAnderson

## 0.2.0 - 2020-07-01

### Added

- [#9](https://github.com/laminas/laminas-skeleton-installer/pull/9) Added support for composer 2.0

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.7 - 2019-11-15

### Added

- [zendframework/zend-skeleton-installer#17](https://github.com/zendframework/zend-skeleton-installer/pull/17) adds support for laminas-component-installer v2 releases.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-skeleton-installer#15](https://github.com/zendframework/zend-skeleton-installer/pull/15) fixes an issue whereby nested dependencies of optional packages selected during installation were not having their modules or components injected in the application configuration.

## 0.1.6 - 2019-06-18

### Added

- [zendframework/zend-skeleton-installer#13](https://github.com/zendframework/zend-skeleton-installer/pull/13) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.5 - 2018-04-30

### Added

- [zendframework/zend-skeleton-installer#12](https://github.com/zendframework/zend-skeleton-installer/pull/12) adds support for PHP 7.2.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-skeleton-installer#12](https://github.com/zendframework/zend-skeleton-installer/pull/12) removes support for HHVM.

### Fixed

- Nothing.

## 0.1.4 - 2017-03-10

### Added

- Nothing.

### Changed

- [zendframework/zend-skeleton-installer#7](https://github.com/zendframework/zend-skeleton-installer/pull/7) updates
  the minimum accepted laminas-component-installer version to 0.7.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.3 - 2016-06-27

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-skeleton-installer#4](https://github.com/zendframework/zend-skeleton-installer/pull/4) updates
  the minimum accepted laminas-component-installer version to 0.3.

## 0.1.2 - 2016-06-02

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Allows using laminas-component-installer `^0.2` stable versions.

## 0.1.1 - 2016-06-02

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-skeleton-installer#2](https://github.com/zendframework/zend-skeleton-installer/pull/2) updates
  the `Uninstaller` to ensure it also updates the `composer.lock` when complete.

## 0.1.0 - 2016-05-23

First tagged release.

### Added

- Everything.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
