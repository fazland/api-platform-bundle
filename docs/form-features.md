Fazland - ApiPlatformBundle - Form Features
===========================================

This bundle introduce some [Symfony Form](https://github.com/symfony/form) features:

Types
-----
#### `Fazland\ApiPlatformBundle\Form\CheckboxType`
Type that extends the Symfony base type with the `false_values` options set to `['0', 'false', 'no', 'off', 'n', 'f']`

#### `Fazland\ApiPlatformBundle\Form\CollectionType`
Type that extends the Symfony base type with the `allow_add`, `allow_delete`, `delete_empty` options set to `true` and `error_bubbling` to false.

#### `Fazland\ApiPlatformBundle\Form\IsoDateTimeType` and `Fazland\ApiPlatformBundle\Form\IsoDateTimeImmutableType`
Type with the `DateTimeToIso8601Transformer` view transformer already set.

#### `Fazland\ApiPlatformBundle\Form\PageTokenType`
This type accepts a valid `Fazland\ApiPlatformBundle\Pagination\PageToken` string representation. `Fazland\ApiPlatformBundle\Pagination\PageToken` are discussed into the [Pagination and continuation token](./pagination-continuation-token.md) section.

#### `Fazland\ApiPlatformBundle\Form\TelType`
Type with the `PhoneNumberToStringTransformer` view transformer already set.

#### `Fazland\ApiPlatformBundle\Form\UnstructuredType`
This type accepts any data. This is suitable if you don't have a structure or you don't want to specify the single fields on it.

Transformers
------------
#### `Fazland\ApiPlatformBundle\Form\DataTransformer\Money\CodeToCurrencyTransformer`
Transforms a valid currency ISO string into a valid `Money\Currency` object.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\Money\MoneyTransformer`
Transforms an array with keys `amount` and `currency` into a valid `Money\Money` object.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\Base64ToUploadedFileTransformer`
Transforms a data uri string into an instance of `UploadedFile`.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\BooleanTransformer`
Transforms `['1', 'true', 'yes', 'on', 'y', 't']` as true values and `['0', 'false', 'no', 'off', 'n', 'f']` as false values.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\BooleanTransformer`
Accepts at its construction a list of transformers and use those one by one to transform the passed values.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\DateTimeToIso8601Transformer` and `Fazland\ApiPlatformBundle\Form\DataTransformer\DateTransformer`
Accepts strings in some date formats and transform those into valid `\DateTimeInterface` instances. 

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\IntegerTransformer`
Accepts numeric strings and transform it into valid integers.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\MappingTransformer`
Accepts a transformer at its construction and applies it for each value passed.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\NullableMappingTransformer`
As above but returns null if the value is null.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\PageTokenTransformer`
Transforms a `PageToken` instance into a string and vice versa.

#### `Fazland\ApiPlatformBundle\Form\DataTransformer\PhoneNumberToString`
Transforms a `PhoneNumber` instance into a string and vice versa.
