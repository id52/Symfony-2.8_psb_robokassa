<?php

namespace KreaLab\PaymentBundle\Controller;

use KreaLab\CommonBundle\Entity\PaymentLog;
use KreaLab\PaymentBundle\Entity\Log;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

class PsbController extends Controller
{
    protected $isAjaxCheckPaid;
    protected $handlers  = [];
    protected $payments  = [];
    protected $routes    = [];
    protected $templates = [];

    public function init()
    {
        $paramService        = $this->get('krealab_payment.service.parameters');
        $newParamServiceName = $paramService->getNewHandlerParseParametersName('psb');

        if ($newParamServiceName && $newParamServiceName !== 'krealab_payment.service.parameters') {
            $paramService = $this->get($newParamServiceName);
        }

        $psbParameters         = $paramService->get('psb');
        $this->handlers        = $psbParameters['handlers'];
        $this->payments        = $psbParameters['payments'];
        $this->routes          = $psbParameters['routes'];
        $this->templates       = $psbParameters['templates'];
        $this->isAjaxCheckPaid = $psbParameters['is_ajax_check_paid'];
    }

    public function queryAction($id)
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $trtype  = ($request->get('trtype') == 'revert') ? 14 : 1;

        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /** @var  $log Log */
        $qb  = $em->getRepository('KrealabPaymentBundle:Log')->createQueryBuilder('l')
            ->andWhere('l.id = :id')->setParameter('id', $id)
            ->andWhere('l.s_type = :s_type')->setParameter('s_type', 'psb')
            ->andWhere('l.paid = :paid')->setParameter('paid', $trtype == 14);
        $log = $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();

        if (!$log) {
            throw $this->createNotFoundException();
        }

        if ($trtype == 14) {
            if (false === $this->isGranted('ROLE_PSB_REVERT')) {
                throw new AccessDeniedHttpException();
            }

            if ($log->getRevertLogId()) {
                throw new \LogicException('This payment already revert.');
            }

            $revert = new Log();
            $revert->setSum(-1 * $log->getSum());
            $revert->setPaidLog($log);
            $revert->setSType('psb');
            $revert->setCreatedAt(new \DateTime());
            $revert->setUpdatedAt(new \DateTime());

            $em->persist($revert);
            $em->flush();
        }

        $hmacParams = $this->generateHmacParameters($log, $trtype);

        $psign = $this->generateHmac($hmacParams['parameters'], $hmacParams['key']);

        if (!array_key_exists('MERCHANT', $hmacParams['parameters'])) {
            $hmacParams['parameters']['MERCHANT'] = $hmacParams['merchant'];
        }

        return $this->render('KrealabPaymentBundle:Psb:query.html.twig', [
            'url'         => $hmacParams['url'],
            'hmac_params' => $hmacParams['parameters'],
            'psign'       => $psign,
        ]);
    }

    public function resultAction(Request $request)
    {
        $response = '';

        $params = [
            'key'            => $this->payments['key'],
            'terminal_id'    => $this->payments['terminal_id'],
            'merchant_id'    => $this->payments['merchant_id'],
            'merchant_name'  => $this->payments['merchant_name'],
            'merchant_email' => $this->payments['merchant_email'],
            'url'            => $this->payments['url'],
        ];

        $trtype = intval($request->get('TRTYPE'));
        switch ($trtype) {
            case 1: // Оплата
                $hmacParams = [
                    $request->get('AMOUNT'),
                    $request->get('CURRENCY'),
                    $request->get('ORDER'),
                    $request->get('MERCH_NAME'),
                    $request->get('MERCHANT'),
                    $request->get('TERMINAL'),
                    $request->get('EMAIL'),
                    $request->get('TRTYPE'),
                    $request->get('TIMESTAMP'),
                    $request->get('NONCE'),
                    $request->get('BACKREF'),
                    $request->get('RESULT'),
                    $request->get('RC'),
                    $request->get('RCTEXT'),
                    $request->get('AUTHCODE'),
                    $request->get('RRN'),
                    $request->get('INT_REF'),
                ];
                break;
            case 14: // Возврат
                $hmacParams = [
                    $request->get('ORDER'),
                    $request->get('AMOUNT'),
                    $request->get('CURRENCY'),
                    $request->get('ORG_AMOUNT'),
                    $request->get('RRN'),
                    $request->get('INT_REF'),
                    $request->get('TRTYPE'),
                    $request->get('TERMINAL'),
                    $request->get('BACKREF'),
                    $request->get('EMAIL'),
                    $request->get('TIMESTAMP'),
                    $request->get('NONCE'),
                    $request->get('RESULT'),
                    $request->get('RC'),
                    $request->get('RCTEXT'),
                ];
                break;
            default:
                $hmacParams = [];
        }

        $psign = $this->generateHmac($hmacParams, $params['key']);

        if ($psign == strtolower($request->get('P_SIGN'))
            && $request->get('CURRENCY') == 'RUB'
        ) {
            $em = $this->get('doctrine.orm.entity_manager');

            $paidStatus = ($trtype == 1) ? false : true;
            /** @var  $log Log */
            $log = $em->getRepository('KrealabPaymentBundle:Log')->createQueryBuilder('l')
                ->andWhere('l.id = :id')->setParameter('id', intval($request->get('ORDER')))
                ->andWhere('l.s_type = :s_type')->setParameter('s_type', 'psb')
                ->andWhere('l.paid = :paid')->setParameter('paid', $paidStatus)
                ->setMaxResults(1)->getQuery()->getOneOrNullResult();
            if ($log && $request->get('AMOUNT') == $log->getSum()) {
                $result = intval($request->get('RESULT'));

                switch ($trtype) {
                    case 1: // Оплата
                        if ($result == 0) {
                            $log->setPaid(true);
                            $log->setSId(trim($request->get('RRN')));

                            $info             = $log->getInfo();
                            $info['authcode'] = trim($request->get('AUTHCODE'));
                            $info['name']     = trim($request->get('NAME'));
                            $info['card']     = trim($request->get('CARD'));
                            $info['int_ref']  = trim($request->get('INT_REF'));
                            $log->setInfo($info);
                            $log->setUpdatedAt(new \DateTime());

                            $em->persist($log);
                            $em->flush();

                            $handler = $this->get($this->handlers['success']['service']);
                            $method  = $this->handlers['success']['action'];
                            call_user_func([$handler, $method], $log);

                            $response = 'OK';
                        } else {
                            $log->setInfo(array_merge($log->getInfo(), ['fail' => $result]));
                            $log->setUpdatedAt(new \DateTime());

                            $em->persist($log);
                            $em->flush();

                            $handler = $this->get($this->handlers['fail']['service']);
                            $method  = $this->handlers['fail']['action'];
                            call_user_func([$handler, $method], $log);
                        }
                        break;
                    case 14: // Возврат
                        /** @var  $revert Log */
                        $revert = $em->getRepository('KrealabPaymentBundle:Log')->createQueryBuilder('l')
                            ->andWhere('l.paid_log = :log')->setParameter('log', $log)
                            ->andWhere('l.info = :info')->setParameter('info', 'a:0:{}')
                            ->addOrderBy('l.created_at', 'DESC')
                            ->setMaxResults(1)->getQuery()->getOneOrNullResult();

                        if ($result == 0) {
                            $revert->setPaid(true);
                            $revert->setSId(trim($request->get('RRN')));
                            $revert->setInfo(['success' => true]);
                            $revert->setUpdatedAt(new \DateTime());
                            $em->persist($revert);

                            $log->setRevertLogId($revert->getId());
                            $log->setUpdatedAt(new \DateTime());
                            $em->persist($log);

                            $em->flush();

                            $handler = $this->get($this->handlers['success_revert']['service']);
                            $method  = $this->handlers['success_revert']['action'];
                            call_user_func([$handler, $method], $log);

                            $response = 'OK';
                        } else {
                            $revert->setInfo(array_merge($revert->getInfo(), ['fail' => $result]));
                            $revert->setUpdatedAt(new \DateTime());

                            $em->persist($revert);
                            $em->flush();

                            $handler = $this->get($this->handlers['fail_revert']['service']);
                            $method  = $this->handlers['fail_revert']['action'];
                            call_user_func([$handler, $method], $log);
                        }
                        break;
                }
            }
        }

        return new Response($response);
    }

    protected function generateHmac(array $params, $key)
    {
        $str = '';
        foreach ($params as $k => $v) {
            if ($k !== 'DESC') {
                $str .= strlen($v).$v;
            }
        }

        return hash_hmac('sha1', $str, pack('H*', $key));
    }

    protected function generateHmacParameters($log, $trtype)
    {
        /** @var  $log Log */
        $params = [
            'key'            => $this->payments['key'],
            'terminal_id'    => $this->payments['terminal_id'],
            'merchant_id'    => $this->payments['merchant_id'],
            'merchant_name'  => $this->payments['merchant_name'],
            'merchant_email' => $this->payments['merchant_email'],
            'url'            => $this->payments['url'],
        ];

        $date      = new \DateTime('now', new \DateTimeZone('UTC'));
        $timestamp = $date->format('YmdHis');
        $info      = $log->getInfo();

        switch ($trtype) {
            case 1:
                $hmacParams = [
                    'AMOUNT'     => $log->getSum(),
                    'CURRENCY'   => 'RUB',
                    'ORDER'      => sprintf('%06s', $log->getId()),
                    'DESC'       => isset($info['desc']) ? $info['desc'] : '-',
                    'MERCH_NAME' => $params['merchant_name'],
                    'MERCHANT'   => $params['merchant_id'],
                    'TERMINAL'   => $params['terminal_id'],
                    'EMAIL'      => $params['merchant_email'],
                    'TRTYPE'     => 1,
                    'TIMESTAMP'  => $timestamp,
                    'NONCE'      => md5(rand(100000, 9000000)),
                    'BACKREF'    => $this->generateUrl($this->routes['info'], [
                        'log_id'     => $log->getId(),
                    ], true),
                ];
                break;
            case 14:
                $backurl    = $this->generateUrl($this->routes['info_revert'], [
                    'log_id'     => $log->getId(),
                ], true);
                $hmacParams = [
                    'ORDER'      => sprintf('%06s', $log->getId()),
                    'AMOUNT'     => $log->getSum(),
                    'CURRENCY'   => 'RUB',
                    'ORG_AMOUNT' => $log->getSum(),
                    'RRN'        => $log->getSId(),
                    'INT_REF'    => $info['int_ref'],
                    'TRTYPE'     => 14,
                    'TERMINAL'   => $params['terminal_id'],
                    'BACKREF'    => $backurl,
                    'EMAIL'      => $params['merchant_email'],
                    'TIMESTAMP'  => $timestamp,
                    'NONCE'      => md5(rand(100000, 9000000)),
                ];
                break;
            default:
                throw $this->createNotFoundException();
        }

        return [
            'parameters' => $hmacParams,
            'key'        => $params['key'],
            'url'        => $params['url'],
            'merchant'   => $params['merchant_id'],
        ];
    }

    public function renderInfoPaymentAction(Request $request)
    {
        $handler = $this->get($this->handlers['render_info_payment']['service']);
        $method  = $this->handlers['render_info_payment']['action'];

        $options = [
            'check_route' => $this->routes['check_status'],
            'layout'      => $this->templates['layout'],
            'log_id'      => $request->get('log_id'),
            'back_route'  => $this->routes['callback'],
            'type'        => 'paid',
            'check_paid'  => $this->isAjaxCheckPaid,
        ];

        return call_user_func([$handler, $method], $this->templates['view_info_payment'], $options);
    }

    public function renderInfoRevertAction(Request $request)
    {
        $handler = $this->get($this->handlers['render_info_revert']['service']);
        $method  = $this->handlers['render_info_revert']['action'];

        $options = [
            'check_route' => $this->routes['check_status'],
            'layout'      => $this->templates['layout'],
            'log_id'      => $request->get('log_id'),
            'back_route'  => $this->routes['callback'],
            'type'        => 'revert',
            'check_paid'  => $this->isAjaxCheckPaid,
        ];

        return call_user_func([$handler, $method], $this->templates['view_info_revert'], $options);
    }

    public function getStatusAjaxAction()
    {
        $handler = $this->get($this->handlers['check_status']['service']);
        $method  = $this->handlers['check_status']['action'];

        return call_user_func([$handler, $method]);
    }
}
