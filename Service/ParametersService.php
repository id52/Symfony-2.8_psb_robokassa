<?php

namespace KreaLab\PaymentBundle\Service;

use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Router;

class ParametersService
{
    protected $bundleName       = null;
    protected $checkedParametrs = [];
    protected $converter        = null;
    protected $parameters       = [];
    protected $request          = null;
    protected $router           = null;
    protected $twig             = null;

    public function __construct(
        $parameters,
        \Twig_Environment $twig,
        RequestStack $requestStack,
        ControllerNameParser $converter,
        Router $router
    ) {
        $this->parameters = array_change_key_case($parameters, CASE_LOWER);
        $this->twig       = $twig;
        $this->request    = $requestStack->getMasterRequest();
        $this->converter  = $converter;
        $this->router     = $router;

        $bundleName = $this->request->attributes->get('_bundle');
        if (!$bundleName) {
            throw new \LogicException('Not found bundle name in route config.');
        }

        $this->bundleName = $bundleName;
    }

    public function getAll()
    {
        $params = $this->getParametersBlock();

        $banks = array_keys($params);
        foreach ($banks as $typeBank) {
            $this->checkedParametrs[$typeBank] = $this->get($typeBank);
        }

        return $this->checkedParametrs;
    }

    public function getNewHandlerParseParametersName($type)
    {
        $params      = $this->getParametersBlock();
        $paramsBlock = $params[$type];

        return $paramsBlock['handler_parse_parameters'];
    }

    public function get($type)
    {
        $paramsForBundle = $this->getParametersBlock();
        $paramsBlock     = $paramsForBundle[$type];
        $snakeBundleName = $this->convertCamelCaseToSnakeCase($this->bundleName);

        if (!isset($this->checkedParametrs[$snakeBundleName][$type])) {
            $this->checkedParametrs[$snakeBundleName][$type] = [
                'handlers'  => $this->checkHandlers($paramsBlock['handlers']),
                'payments'  => $paramsBlock['payments'],
                'routes'    => $this->checkRoutes($paramsBlock['routes']),
                'templates' => $this->checkTemplates($paramsBlock['templates'], ucwords($type)),
            ];

            if ($type == 'psb') {
                $this->checkedParametrs[$snakeBundleName][$type]['is_ajax_check_paid']
                    = $paramsBlock['is_ajax_check_paid'];
            }

            return $this->checkedParametrs[$snakeBundleName][$type];
        }


        if (!isset($this->checkedParametrs[$snakeBundleName][$type]['handlers'])) {
            $this->checkedParametrs[$snakeBundleName][$type]['handlers']
                = $this->checkHandlers($paramsBlock['handlers']);
        }

        if (!isset($this->checkedParametrs[$snakeBundleName][$type]['payments'])) {
            $this->checkedParametrs[$snakeBundleName][$type]['payments'] = $paramsBlock['payments'];
        }

        if (!isset($this->checkedParametrs[$snakeBundleName][$type]['routes'])) {
            $this->checkedParametrs[$snakeBundleName][$type]['routes'] = $this->checkRoutes($paramsBlock['routes']);
        }

        if (!isset($this->checkedParametrs[$snakeBundleName][$type]['templates'])) {
            $this->checkedParametrs[$snakeBundleName][$type]['templates']
                = $this->checkTemplates($paramsBlock['templates'], ucwords($type));
        }

        return $this->checkedParametrs[$snakeBundleName][$type];
    }

    protected function getParametersBlock()
    {
        $bundleName = $this->convertCamelCaseToSnakeCase($this->bundleName);

        if ($bundleName && isset($this->parameters[$bundleName])) {
            $params = $this->parameters[$bundleName];

            return $params;
        }

        throw new \LogicException('Not found this bundle configuration');
    }

    protected function checkHandlers($handlersParams)
    {
        $handlers = [];

        foreach ($handlersParams as $handlerName => $value) {
            $partsValue = explode(':', $value);

            if (count($partsValue) == 2) {
                $handlers[$handlerName] = [
                    'service' => $partsValue[0],
                    'action'  => $partsValue[1],
                ];
            } else {
                throw new \LogicException('Not found method or service name for '.$this->bundleName
                                          .'Bundle, parameter "'.$handlerName.'"');
            }
        }

        return $handlers;
    }

    protected function checkRoutes($routeParams)
    {
        $routesExisting = $this->router->getRouteCollection()->all();
        $routes         = [];

        foreach ($routeParams as $routeName => $value) {
            if (!array_key_exists($value, $routesExisting)) {
                throw new RouteNotFoundException('Not found route for name "'.$value.'"');
            }

            $routes[$routeName] = $value;
        }

        return $routes;
    }

    protected function checkTemplates($templates, $type)
    {
        $result = [];

        foreach ($templates as $templateName => $value) {
            try {
                $this->twig->resolveTemplate($value);
                $result[$templateName] = $value;
                continue;
            } catch (\Twig_Error_Loader $e) {
                $result[$templateName] = null;
            }

            try {
                $this->twig->resolveTemplate($this->bundleName.'Bundle::'.$value);
                $result[$templateName] = $this->bundleName.'Bundle::'.$value;
                continue;
            } catch (\Twig_Error_Loader $e) {
                $result[$templateName] = null;
            }

            try {
                $folderName = $type;
                $this->twig->resolveTemplate(
                    'KrealabAdminSkeletonBundle:'.$folderName.':'.$value
                );
                $result[$templateName] = 'KrealabAdminSkeletonBundle:'.$folderName.':'.$value;
                continue;
            } catch (\Twig_Error_Loader $e) {
                throw new FileNotFoundException('Not found template '.$value);
            }
        }

        return $result;
    }

    protected function convertCamelCaseToSnakeCase($str)
    {
        $str = strtolower(preg_replace('#(?<!\A)([A-Z]+|[0-9]+)#', '_$1', $str));

        return $str;
    }

    protected function convertSnakeCaseToCamelCase($str)
    {
        $str = str_replace('_', ' ', $str);
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);

        return $str;
    }
}
