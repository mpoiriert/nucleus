<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Invoker;

use Nucleus\IService\Invoker\IInvokerService;

/**
 * Description of Invoker
 *
 * @author Martin
 */
class Invoker implements IInvokerService
{

    public function invoke($callable, array $namedParameters = array(), array $typedParameters = array())
    {
        $parameters = $this->getReflectionParameters($callable);

        $arguments = array();
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $namedParameters)) {
                $parameterValue = $namedParameters[$param->name];
                $arguments[] = $parameterValue;
                continue;
            }

            if ($param->getClass()) {
                $found = false;
                foreach ($typedParameters as $typedParameter) {
                    if ($param->getClass()->isInstance($typedParameter)) {
                        $arguments[] = $typedParameter;
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    continue;
                }
            }

            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
                continue;
            }

            $repr = $this->getErrorMessage($callable);
            throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
        }

        return call_user_func_array($callable, $arguments);
    }

    private function getErrorMessage($callable)
    {
        switch (true) {
            case is_array($callable):
                return sprintf('%s::%s()', get_class($callable[0]), $callable[1]);
            case is_object($callable):
                return get_class($callable);
            default:
                return $callable;
        }
    }

    /**
     * @param mixed $callable
     * return \ReflectionParameter[]
     */
    private function getReflectionParameters($callable)
    {
        if (is_array($callable)) {
            $reflectionCallable = new \ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_object($callable) && !$callable instanceof \Closure) {
            $reflectionObject = new \ReflectionObject($callable);
            $reflectionCallable = $reflectionObject->getMethod('__invoke');
        } else {
            $reflectionCallable = new \ReflectionFunction($callable);
        }

        return $reflectionCallable->getParameters();
    }
}
