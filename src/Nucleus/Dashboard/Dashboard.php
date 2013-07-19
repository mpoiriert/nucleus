<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Dashboard;

use Nucleus\IService\Invoker\IInvokerService;
use Nucleus\Routing\Router;
use Nucleus\IService\DependencyInjection\IServiceContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of Dashboard
 *
 * @author Martin
 */
class Dashboard
{
    /**
     * @var \Nucleus\Routing\Router 
     */
    private $routing;

    private $serviceContainer;

    private $invoker;

    private $services = array();

    /**
     * @param \Nucleus\Routing\Router $routing
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(IServiceContainer $serviceContainer, IInvokerService $invoker, Router $routing)
    {
        $this->serviceContainer = $serviceContainer;
        $this->invoker = $invoker;
        $this->routing = $routing;
    }

    public function addService($serviceName, $title)
    {
        $this->services[] = array('name' => $serviceName, 'title' => $title);
    }

    /**
     * @Route(name="dashboard", path="/nucleus/dashboard/_services")
     */
    public function services()
    {
        $services = array();
        foreach ($this->services as $service) {
            $services[] = array(
                'name' => $service['title'],
                'url' => $this->routing->generate('serviceActions', array('serviceName' => $service['name']))
            );
        }
        return $services;
    }

    /**
     * @Route(name="serviceActions", path="/nucleus/dashboard/{serviceName}/_actions")
     */
    public function serviceActions($serviceName)
    {
        $annotations =  $this->getAnnotations($serviceName)->getAllMethodAnnotations(array(function($a) {
            return $a instanceof \Nucleus\IService\Dashboard\Action;
        }));

        $actions = array();
        foreach ($annotations as $method => $annos) {
            $anno = $annos[0];
            if (!$anno->global) {
                continue;
            }
            $actions[] = array(
                'name' => $method,
                'title' => $anno->title,
                'icon' => $anno->icon,
                'type' => $anno->type,
                'default' => $anno->default,
                'url' => $this->routing->generate('serviceAction', array(
                    'serviceName' => $serviceName, 'actionName' => $method))
            );
        }
        return $actions;
    }

    /**
     * @Route(name="serviceAction", path="/nucleus/dashboard/{serviceName}/_actions/{actionName}")
     */
    public function serviceAction($serviceName, $actionName)
    {
        $annotations =  $this->getAnnotations($serviceName)->getMethodAnnotations($actionName);
        $action = array();

        foreach ($annotations as $a) {
            $action = array_merge_recursive($action, $a->asArray());
        }

        if (isset($action['actions'])) {
            foreach ($action['actions'] as &$a) {
                $a['url'] = $this->routing->generate('serviceAction', array(
                    'serviceName' => $serviceName, 'actionName' => $a['name']));
            }
        }

        $action['url'] = $this->routing->generate('executeServiceAction', array(
            'serviceName' => $serviceName, 'actionName' => $actionName));

        return $action;
    }

    protected function getAnnotations($serviceName)
    {
        $service = $this->serviceContainer->get($serviceName);
        $annotationParser = new \Nucleus\Annotation\AnnotationParser();
        return $annotationParser->parse(get_class($service));
    }

    /**
     * @Route(name="executeServiceAction", path="/nucleus/dashboard/{serviceName}/{actionName}")
     */
    public function executeServiceAction($serviceName, $actionName=null, Request $request, Response $response)
    {
        $service = $this->serviceContainer->get($serviceName);
        $parameters = array_merge($request->query->all(), $request->request->all());
        return $this->invoker->invoke(
            array($service, $actionName), $parameters, array($request, $response)
        );
    }
}
