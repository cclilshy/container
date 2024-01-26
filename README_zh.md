## Container

查看文档: [Document](https://cloudtay.github.io/p-ripple-document/base/2024-01-01-0x02-container.html)

## 容器(Container)

Class `Core\Container\Container`

> 内置容器, 用于管理对象的生命周期, 依赖注入, 服务定位, 为多栈上下文空间的内容隔离提供支持.

## 创建容器(Create Container)

```php
<?php
use Core\Container\Container;

$container = new Container();
```

## 绑定单例(Bind Instance)

```php
$container->inject(Request::class,$request);
```

## 自动构建对象(Auto Constructor)

```php
// 该方法的前置条件为已经注入了Request::class
$container->make(Session::class); 

class Session{
    public function __construct(Request $request){
    }
}
```

> 容器将会根据容器内所有已经绑定的对象, 自动构建对象的依赖关系, 并且自动注入, 如Session对象,
> 构建失败时会抛出一个 `Core\Container\Container\Excpetion` 异常

## 主动执行函数(Call function)

```php
$container->callUserFunction();
$container->callUserFunctionArray();
```

> PRipple的容器利用了PHP的反射机制,使得开发者可以主动以容器的视角
> 像原生`call_user_function`以及`call_user_function_array`
> 一样去调用方法,支持对象/静态方法/匿名函数,容器将会自动构建方法的依赖关系并注入

## 附言(Postscript)

> 容器当目标方法存在基础内建类型的参数如`string`时,将会自动使用默认值,
> 如目标方法未提供默认值,将会抛出异常
