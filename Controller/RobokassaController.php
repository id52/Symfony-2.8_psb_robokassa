<?php

namespace KreaLab\PaymentBundle\Controller;

use KreaLab\PaymentBundle\Entity\Log;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RobokassaController extends Controller
{
    protected $params    = [];
    protected $handlers  = [];
    protected $payments  = [];
    protected $routes    = [];
    protected $templates = [];

    public function init()
    {
        $paramService        = $this->get('krealab_payment.service.parameters');
        $newParamServiceName = $paramService->getNewHandlerParseParametersName('robokassa');

        if ($newParamServiceName && $newParamServiceName !== 'krealab_payment.service.parameters') {
            $paramService = $this->get($newParamServiceName);
        }

        $rbParameters    = $paramService->get('robokassa');
        $this->handlers  = $rbParameters['handlers'];
        $this->payments  = $rbParameters['payments'];
        $this->routes    = $rbParameters['routes'];
        $this->templates = $rbParameters['templates'];
    }

    public function queryAction($id)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $log = $em->getRepository('KrealabPaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.id = :id')->setParameter('id', $id)
            ->andWhere('l.s_type = :s_type')->setParameter('s_type', 'robokassa')
            ->andWhere('l.paid = :paid')->setParameter('paid', false)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if (!$log) {
            throw $this->createNotFoundException();
        }

        $params = [
            'login' => $this->payments['login'],
            'pass1' => $this->payments['pass1'],
            'pass2' => $this->payments['pass2'],
            'url'   => $this->payments['url'],
        ];

        $sum  = $log->getSum();
        $info = $log->getInfo();
        $sign = md5(implode(':', [$params['login'], $sum, '', $params['pass1'], 'shp_id='.$id]));
        $url  = $params['url'].'?'.http_build_query([
            'MrchLogin'      => $params['login'],
            'OutSum'         => $sum,
            'Desc'           => isset($info['desc']) ? $info['desc'] : '',
            'SignatureValue' => $sign,
            'shp_id'         => $id,
        ]);

        return $this->redirect($url);
    }

    public function resultAction(Request $request)
    {
        $response = '';

        $params = [
            'login' => $this->payments['login'],
            'pass1' => $this->payments['pass1'],
            'pass2' => $this->payments['pass2'],
            'url'   => $this->payments['url'],
        ];

        $sid  = $request->get('InvId');
        $id   = $request->get('shp_id');
        $sum  = $request->get('OutSum');
        $sign = md5(implode(':', [$sum, $sid, $params['pass2'], 'shp_id='.$id]));

        if ($sign == strtolower($request->get('SignatureValue'))) {
            $em  = $this->get('doctrine.orm.entity_manager');
            $log = $em->getRepository('KrealabPaymentBundle:Log')->createQueryBuilder('l')
                ->andWhere('l.id = :id')->setParameter('id', $id)
                ->andWhere('l.s_type = :s_type')->setParameter('s_type', 'robokassa')
                ->andWhere('l.paid = :paid')->setParameter('paid', false)
                ->setMaxResults(1)->getQuery()->getOneOrNullResult();
            if ($log && $sum == $log->getSum()) {
                $log->setPaid(true);
                $log->setSId($sid);
                $log->setUpdatedAt(new \DateTime());
                $em->persist($log);
                $em->flush();

                $handler = $this->get($this->handlers['success']['service']);
                $method  = $this->handlers['success']['action'];
                call_user_func([$handler, $method], $log);

                $response = 'OK'.$sid;
            } else {
                $handler = $this->get($this->handlers['fail']['service']);
                $method  = $this->handlers['fail']['action'];
                call_user_func([$handler, $method], $log);
            }
        }

        return new Response($response);
    }

    public function renderSuccessAction()
    {
        $handler = $this->get($this->handlers['render_success']['service']);
        $method  = $this->handlers['render_success']['action'];

        $options = [
            'layout'     => $this->templates['layout'],
            'back_route' => $this->routes['callback'],
        ];

        return call_user_func([$handler, $method], $this->templates['view_success'], $options, $this->getUser());
    }

    public function renderFailAction()
    {
        $handler = $this->get($this->handlers['render_fail']['service']);
        $method  = $this->handlers['render_fail']['action'];

        $options = [
            'layout'     => $this->templates['layout'],
            'back_route' => $this->routes['callback'],
        ];

        return call_user_func([$handler, $method], $this->templates['view_fail'], $options, $this->getUser());
    }
}
