## Container

View Document: [Document](https://cloudtay.github.io/p-ripple-document/base/2024-01-01-0x02-container.html)

## Container

Class `Core\Container\Container`

> Built-in containers for managing the lifecycle of objects, dependency injection, service positioning, and support for content isolation in multi-stack context spaces.

## Create Container

```php
<?php use Core\Container\Container; $container = new Container(); ''' ## Bind Instance '''php $container->inject(Request::class,$request);
```

## Auto Constructor

```php
The precondition for this method is that Request::class has been injected
$container->make(Session::class); 

class Session{
    public function __construct(Request $request){
    }
}
```

> The container will automatically build the dependencies of the objects based on all the objects that have been bound in the container, and automatically inject them, such as the Session object.
> A 'Core\Container\Container\Excpetion' exception is thrown when the build fails

## Call function

```php
$container->callUserFunction();
$container->callUserFunctionArray();
```

> PRipple's containers take advantage of PHP's reflection mechanism, allowing developers to take the initiative from the container's perspective
> Like the native 'call_user_function' and 'call_user_function_array'
> to call methods, support objects/static methods/anonymous functions, and the container will automatically build the method's dependencies and inject them

## Postscript

> When the target method has a basic built-in type parameter such as 'string', the container will automatically use the default value.
> If the target method does not provide a default value, an exception will be thrown
