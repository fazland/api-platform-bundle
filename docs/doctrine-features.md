Fazland - ApiPlatformBundle - Doctrine Features
===============================================

This bundle introduce some [Doctrine](https://github.com/doctrine/orm) features and helpers.

`objects` is often a shortcut reference to `entities` or `documents`.

EntityIterator and DocumentIterator
---------------------------------------
Given an instance of `Doctrine\ORM\QueryBuilder` or `Doctrine\ODM\MongoDB\Query\Builder` the iterator allows you to iterate a single entity/document query confortably:

ORM
```php
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Fazland\ApiPlatformBundle\Doctrine\ORM\EntityIterator;

/** @var EntityManagerInterface $em */
$em = // The EntityManagerInterface instance.
$qb = $em->createQueryBuilder();
$qb
    ->select('u')
    ->from(User::class, 'u')
;

$users = new EntityIterator($qb);
foreach ($users as $user) {
    var_dump($user instanceof User);
}
```

MongoDB:
```php
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Fazland\ApiPlatformBundle\Doctrine\Mongo\DocumentIterator;

/** @var DocumentManager $dm */
$dm = // The EntityManagerInterface instance.
$qb = $dm->createQueryBuilder(User::class);

$users = new DocumentIterator($qb);
foreach ($users as $user) {
    var_dump($user instanceof User);
}
```

EntityRepository and DocumentRepository
---------------------------------------
These two classes are just an extension of the base Doctrine Repository class with some utility methods:
```php
public function all(): ObjectIterator
```
returns all objects matching that object.

```php
public function count(array $criteria = []): int
```
returns an integer representing the count of all objects matching the given criteria.

```php
public function findOneByCached(array $criteria, array $orderBy = null, int $ttl = 28800)
```
executes the base `findOneBy` and caches the results.

```php
public function findByCached(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, int $ttl = 28800)
```
executes the base `findBy` method and caches the results.

```php
public function get($id, $lockMode = null, $lockVersion = null)
```
executes the base `find` method and throws a `NoResultException` if no result has been found.

```php
public function getOneBy(array $criteria, ?array $orderBy = null)
```
executes the base `findOneBy` method and throws a `NoResultException` if no result has been found.

```php
public function getOneByCached(array $criteria, ?array $orderBy = null, int $ttl = 28800)
```

executes the `findOneByCached` method and throws a `NoResultException` if no result has been found.

Unfortunately, `Doctrine\ODM\MongoDB` does not support cache.

How to use
----------
Just set the `default_repository_class` in the Doctrine configuration:

ORM
```yaml
doctrine:
    orm:
        default_repository_class: Fazland\ApiPlatformBundle\Doctrine\ORM\EntityRepository
```

MongoDB:
```yaml
doctrine_mongodb:
    document_managers:
        default_repository_class: Fazland\ApiPlatformBundle\Doctrine\Mongo\DocumentRepository
```
