# mezzio-valinor

Web applications almost always require reading HTTP request data, and converting
them to usable and type-safe in-memory data structures.

[Valinor](https://github.com/CuyZ/Valinor/) is a powerful type-safe serializer,
which simplifies parsing input data, by preventing invalid data from being
submitted.

The goal of mezzio-valinor is to provide an out-of-the-box Valinor installation
with fairly usable defaults, which can be applied inside any Mezzio application
layer that needs to extract typed information from a [PSR-7](http://www.php-fig.org/psr/psr-7/)
`ServerRequestInterface`.

## Installation

Use [Composer](https://getcomposer.org) to install this package:

```bash
$ composer require mezzio/mezzio-valinor
```

## Usage

This package provides a factory for `CuyZ\Valinor\Mapper\TreeMapper`;
this factory is auto-wired if you are using Mezzio and the `laminas-component-installer`
Composer plugin.

You can depend on the `TreeMapper` in your own services:

```php
namespace My\Namespace;

use CuyZ\Valinor\Mapper\TreeMapper;
use Psr\Container\ContainerInterface;

final class AddProductToShoppingCartHandlerFactory
{
    public function __invoke(ContainerInterface $container): MyShoppingCartHandler
    {
        return new AddProductToShoppingCartHandler(
            $container->get(TreeMapper::class),
        );
    }
}
```

In your service, you can then use the `TreeMapper` to convert request parameters to well-typed
payloads:

```php
namespace My\Namespace;

use CuyZ\Valinor\Mapper\Http\FromBody;
use CuyZ\Valinor\Mapper\Http\FromQuery;
use CuyZ\Valinor\Mapper\Http\FromRoute;
use My\ProductCustomization; // this is in your own domain

final class AddProductToShoppingCart
{
    /**
     * @param non-empty-string           $sku
     * @param int<1, max>                $quantity
     * @param list<ProductCustomization> $customization
     */
    public function __construct(
        #[FromRoute]
        public readonly string $sku,
        #[FromQuery]
        public readonly int $quantity,
        public readonly array $customization,
        #[FromBody]
        public readonly ?DeliveryInstructions $deliveryInstructions,
    ) {
    }
}
```

```php
namespace My\NameSpace;

use CuyZ\Valinor\Mapper\MappingError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AddProductToShoppingCartHandler implements RequestHandlerInterface {
    public function __construct(private readonly TreeMapper $mapper)
    {
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $action = $this->mapper->map(AddProductToShoppingCart::class, $request);
        } catch (MappingError $e) {
            // remember to handle mapping errors here: what should the client see, on an invalid request?
            throw $e;
        }

        $this->addProductToCart($action->sku, $action->quantity, $action->customization);

        // ...
    }

    // ...
}
```

## Mapping

For the exact details on what Valinor support, consult
[its official documentation](https://valinor-php.dev/2.4/).
