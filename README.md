data-access-bundle
==================

This bundle integrates data-access to Symfony.
Please read the [data-access documentation reference](https://github.com/kassko/data-access/blob/master/README.md).
It provides a service named "data_access.result_builder_factory". Use it to create a "ResultBuilderFactory" instance instead of "DataAccessProvider".

Installation on Symfony 2
----------------

Add to your composer.json:
```json
"require": {
    "kassko/data-access-bundle": "dev-master"
}
```

Register the bundle to the kernel:
```php
public function registerBundles()
{
    $bundles = array(
    //...
    new Kassko\Bundle\DataAccessBundle\KasskoDataAccessBundle(),
    new Kassko\Bundle\ClassResolverBundle\KasskoClassResolverBundle(),
    //...
    );

    //...
}
```

