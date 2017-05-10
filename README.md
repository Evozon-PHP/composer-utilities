# Composer Utilities

Different Composer utilities for various use cases.

### Installation

```
composer require evozon-php/composer-utilities --dev
```

### Synchronize `composer.json`

When working on a monolith repository, you might have different `.json` files.

In this case, there might one `.json` file for development (i.e. `dev.json`) which installs from local repositories and maybe also different releases. Another `.json` file (i.e. `composer.json`) would be used for installing packages from remote repositories and probably stable versions.

Add the following to your source `.json` file:

```
{
  "config": {
      "composer-utilities": {
          "sync": {
              "ignore": {
                  "nodes": [
                      "[require][vendorAbc/packageAbc]",
                      "[require][vendorXyz/bundleXyz]",
                      "[repositories]"
                  ]
              }
          }
      }
  }
}
```

All you need to do is to define the nodes you want to ignore during synchronization. Make sure you define them in [PropertyAccess](http://symfony.com/doc/current/components/property_access.html) format.

The plugin will hook automatically to the `post-install-cmd` and `post-update-cmd` events and ask if you want to synchronize.

This will work only if you specifically use a custom `.json` file:

```
COMPOSER=dev.json composer install
```

There is also a custom command added when you want to manually trigger the synchronization process:

```
composer sync:json -source dev.json -target composer.json
```
