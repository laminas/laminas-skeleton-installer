# zend-skeleton-installer

[![Build Status](https://secure.travis-ci.org/zendframework/zend-skeleton-installer.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-skeleton-installer)
[![Coverage Status](https://coveralls.io/repos/zendframework/zend-skeleton-installer/badge.svg?branch=master)](https://coveralls.io/r/zendframework/zend-skeleton-installer?branch=master)

zend-skeleton-installer is a composer plugin for use in the initial install of
the ZendSkeletonApplication. It prompts for common requirements, adding packages
to the composer requirements for each selection, and then uninstalls itself on
completion.

The installer requires [zend-component-installer](https://zendframework.github.io/zend-component-installer/),
and we recommend requiring that component in your project skeleton as well.

- File issues at https://github.com/zendframework/zend-skeleton-installer/issues
- Documentation is at https://docs.zendframework.com/zend-skeleton-installer/
