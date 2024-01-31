<?php declare(strict_types=1);
/*
 * Copyright (c) 2023 cclilshy
 * Contact Information:
 * Email: jingnigg@gmail.com
 * Website: https://cc.cloudtay.com/
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * 版权所有 (c) 2023 cclilshy
 *
 * 特此免费授予任何获得本软件及相关文档文件（“软件”）副本的人，不受限制地处理
 * 本软件，包括但不限于使用、复制、修改、合并、出版、发行、再许可和/或销售
 * 软件副本的权利，并允许向其提供本软件的人做出上述行为，但须符合以下条件：
 *
 * 上述版权声明和本许可声明应包含在本软件的所有副本或主要部分中。
 *
 * 本软件按“原样”提供，不提供任何形式的保证，无论是明示或暗示的，
 * 包括但不限于适销性、特定目的的适用性和非侵权性的保证。在任何情况下，
 * 无论是合同诉讼、侵权行为还是其他方面，作者或版权持有人均不对
 * 由于软件或软件的使用或其他交易而引起的任何索赔、损害或其他责任承担责任。
 */

namespace Cclilshy\Container;

use Cclilshy\Container\Exception\Exception;
use Closure;
use ReflectionException;
use ReflectionFunctionAbstract;

/**
 * @Class Container
 */
class Container
{
    /**
     * 内建类型
     * @var string[] BUILT_TYPE
     */
    private const array BUILT_TYPE = ['int', 'string', 'float', 'bool', 'array', 'object', 'callable', 'iterable', 'mixed'];

    /**
     * 类反射缓存
     * @var array $classCache
     */
    private static array $classCache = [];

    /**
     * 单例映射
     * @var array $dependenceMap
     */
    public array $dependenceMap = [];

    public function __construct()
    {
        $this->dependenceMap[Container::class] = $this;
        if (get_class($this) !== Container::class) {
            $this->inject(get_class($this), $this);
        }
    }

    /**
     * 主动绑定单例
     * @param string $class
     * @param object $instance
     * @return object
     */
    public function inject(string $class, object $instance): object
    {
        return $this->dependenceMap[$class] = $instance;
    }

    /**
     * 绑定单例
     * @param string $class
     * @param object $instance
     * @return object
     */
    public function bind(string $class, object $instance): object
    {
        return $this->dependenceMap[$class] = $instance;
    }

    /**
     * 卸载依赖
     * @param string $class
     * @return void
     */
    public function unload(string $class): void
    {
        unset($this->dependenceMap[$class]);
    }

    /**
     * 获取或构造类依赖
     * @param string    $class
     * @param bool|null $flush
     * @return object
     * @throws Exception|ReflectionException
     */
    public function make(string $class, bool|null $flush = false): object
    {
        if (!$flush && $object = $this->get($class)) {
            return $object;
        }
        return $this->new($class);
    }

    /**
     * 存在单例则获取,不自动构建
     * @param string $class
     * @return object|null
     */
    public function get(string $class): object|null
    {
        return $this->dependenceMap[$class] ?? null;
    }

    /**
     * 是否存在单例
     * @param string $class
     * @return bool
     */
    public function has(string $class): bool
    {
        return isset($this->dependenceMap[$class]);
    }

    /**
     * 构造单例,如容器中已存在则覆盖
     * @param string $class
     * @return object
     * @throws Exception|ReflectionException
     */
    public function new(string $class): object
    {
        if ($classReflection = ReflectionMap::resolveClass($class)) {
            if (!$constructor = $classReflection->getConstructor()) {
                return $this->inject($class, new $class());
            }
            $params = $this->resolveParams($constructor);
            return $this->inject($class, new $class(...$params));
        }

        throw new Exception("Unable to find class {$class}");
    }


    /**
     * 自动反射CallFunction
     * @param array|Closure $route
     * @param mixed         ...$arguments
     * @return mixed
     * @throws Exception|ReflectionException
     */
    public function callUserFunction(array|Closure $route, mixed ...$arguments): mixed
    {
        return $this->callUserFunctionArray($route, $arguments);
    }

    /**
     * 自动反射CallFunction
     * @param array|Closure $route
     * @param array|null    $arguments
     * @return mixed
     * @throws Exception|ReflectionException
     */
    public function callUserFunctionArray(array|Closure $route, array|null $arguments = []): mixed
    {
        if ($arguments) {
            return call_user_func_array($route, $arguments);
        }
        if (is_array($route)) {
            [$target, $method] = $route;
            if (is_string($target)) {
                $classInfo  = ReflectionMap::resolveClass($target);
                $methodInfo = ReflectionMap::resolveMethod($target, $method);

            } elseif (is_object($target)) {
                $classInfo  = ReflectionMap::resolveClass($target);
                $methodInfo = ReflectionMap::resolveMethod($target, $method);
            } else {
                throw new Exception("Unable to find class {$target}");
            }

            foreach ($classInfo->getAttributes() as $attribute) {
                $attributeObject = $attribute->newInstance();
                if ($attributeObject instanceof AttributeBase) {
                    $this->callUserFunction([$attributeObject, 'buildAttribute']);
                    $this->inject($attribute->getName(), $attributeObject);
                }
            }

            foreach ($methodInfo->getAttributes() as $attribute) {
                $attributeObject = $attribute->newInstance();
                if ($attributeObject instanceof AttributeBase) {
                    $this->callUserFunction([$attributeObject, 'buildAttribute']);
                    $this->inject($attribute->getName(), $attributeObject);
                }
            }

            $params = $this->resolveParams($methodInfo);
            if (is_string($target)) {
                return $methodInfo->invokeArgs(null, $params);
            }
            return $methodInfo->invokeArgs($target, $params);
        } elseif ($route instanceof Closure) {
            $closureReflection = ReflectionMap::resolveFunction($route);
            $params            = $this->resolveParams($closureReflection);
            return $closureReflection->invokeArgs($params);
        }
        throw new Exception('Unable to resolve route');
    }

    /**
     * 自动解析一个反射方法方法需要的参数
     * @param ReflectionFunctionAbstract $reflection
     * @return array
     * @throws Exception|ReflectionException
     */
    private function resolveParams(ReflectionFunctionAbstract $reflection): array
    {
        $params = [];
        foreach ($reflection->getParameters() as $parameter) {
            if ($paramClass = $parameter->getType()?->getName()) {
                if (in_array($paramClass, Container::BUILT_TYPE)) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $params[] = $parameter->getDefaultValue();
                        continue;
                    }
                    throw new Exception("Resolve Class: {$paramClass} param ' {$parameter->getName()} ' cannot be a basic type");
                }
                $params[] = $this->make($paramClass);
            } else {
                throw new Exception("The parameters required for the construct class {$paramClass} are not recognized");
            }
        }
        return $params;
    }
}
