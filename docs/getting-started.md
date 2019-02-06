Fazland - ApiPlatformBundle
===========================

ApiPlatformBundle is a Symfony bundle that helps you building RESTful API within your application.
Just register in the `bundles.php` the bundle and you'll have all the features available.
```php
<?php declare(strict_types=1);

return [
    // [...]
    Fazland\ApiPlatformBundle\ApiPlatformBundle::class => ['all' => true],
    // [...]
];
```

What comes with ApiPlatformBundle?
----------------------------------
- [Doctrine's ORM/ODM features](./doctrine-features.md)
- [Form features](./form-features.md)
- [Request body converters](./request-body-converters.md)
- [PatchManager](./patch-manager.md)
- [Annotations](./annotations.md)
- [Pagination and continuation token](./pagination-continuation-token.md)
- [AQL (ApiPlatformBundle Query language)](./aql.md)
