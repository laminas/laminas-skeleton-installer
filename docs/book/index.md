# Installation

```bash
$ composer require laminas/laminas-skeleton-installer
```

## Optional Packages

To define an optional package, you will add an object under a
`extra.laminas-skeleton-installer` array in your `composer.json`. The
object **must** contain the following properties:

* **name**: the name of the package to install
* **constraint**: the package constraint to use
* **prompt**: a textual prompt (e.g., "Do you want to install console tooling?"

You may also define two boolean flags:

* **module**: when true, indicates that the component represents a Laminas
  module. This flag will not dictate whether or not the component installer
  prompts to install the package as a module, but will affect hints provided by
  Composer during installation.
* **dev**: when true, indicates the package should be installed as a development
  requirement (`require-dev`). Additionally, the installer will prompt you to
  choose a development configuration file in which to install the component if
  it is a module.

## Example

```json
{
  "name": "laminas/laminas-skeleton-application",
  "type": "project",
  "require": {
    "php": "^5.6 || ^7.0",
    "laminas/laminas-component-installer": "^1.0 || ^0.7 || ^1.0.0-dev@dev",
    "laminas/laminas-skeleton-installer": "^1.0 || ^0.1.3 || ^1.0.0-dev@dev",
    "laminas/laminas-mvc": "^3.0.4"
  },
  "extra": {
    "laminas-skeleton-installer": [
      {
        "name": "laminas/laminas-mvc-console",
        "constraint": "^1.1.11",
        "prompt": "Would you like to install MVC console tooling?",
        "module": true
      },
      {
        "name": "laminas/laminas-mvc-i18n",
        "constraint": "^1.0",
        "prompt": "Would you like to install i18n support?",
        "module": true
      },
      {
        "name": "laminas/laminas-test",
        "constraint": "^3.0.2",
        "prompt": "Would you like to install MVC testing support?",
        "dev": true
      }
    ]
  }
}
```

The above will:

* Prompt if you want to do a minimal install; if so, the plugin will remove
  the `extra.laminas-skeleton-installer` section on completion, and then delete
  itself from the installation.
* Prompt for each of the listed components, as noted, awaiting a y/n
  answer (with "n" as the default).
* Install the selections, updating the `composer.json`.
* Remove itself from the installation on completion.
