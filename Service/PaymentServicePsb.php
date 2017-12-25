<?php

namespace KreaLab\PaymentBundle\Service;

use Doctrine\ORM\EntityManager;
use KreaLab\PaymentBundle\Entity\Log;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class PaymentServicePsb
{
    protected $em;
    protected $templating;
    protected $request;
    protected $token;
    protected $user;

    public function __construct(
        EntityManager $em,
        TwigEngine $templating,
        RequestStack $requestStack,
        TokenStorage $tokenStorage
    ) {
        $this->em         = $em;
        $this->templating = $templating;
        $this->request    = $requestStack->getMasterRequest();
        $this->token      = $tokenStorage->getToken();
        $this->user       = $this->token->getUser();
    }

    /** @inheritdoc */
    public function success(Log $log)
    {
    }

    /**
     * @inheritdoc
     */
    public function fail(Log $log = null)
    {
    }

    /** @inheritdoc */
    public function successRevert(Log $log)
    {
    }

    /** @inheritdoc */
    public function failRevert(Log $log = null)
    {
    }

    /** @inheritdoc */
    public function renderInfoPayment($tmpl, $options)
    {
        return $this->templating->renderResponse($tmpl, $options);
    }

    /** @inheritdoc */
    public function renderInfoRevert($tmpl, $options)
    {
        return $this->templating->renderResponse($tmpl, $options);
    }

    public function getStatusAjax()
    {
        if (!$this->request->isXmlHttpRequest()) {
            throw new NotFoundHttpException;
        }

        $logId         = $this->request->get('log_id');
        $operationType = $this->request->get('type');
        $status        = '';
        $log           = $this->em->getRepository('KrealabPaymentBundle:Log')->findOneBy([
            'id'     => $logId,
            's_type' => 'psb',
        ], ['updated_at' => 'DESC']);

        /** @var  $log Log */
        if ($log) {
            if ($operationType != 'revert') {
                if ($log->isPaid()) {
                    $status = 'paid';
                } else {
                    $status = 'not_paid';
                    $info   = $log->getInfo();
                    if (isset($info['fail'])) {
                        $status = 'fail';
                    }
                }
            } else {
                /** @var  $revert Log */
                $revertId = $log->getRevertLogId();
                $revert   = $this->em->getRepository('KrealabPaymentBundle:Log')->createQueryBuilder('l')
                    ->andWhere('l.id = :id')->setParameter('id', $revertId)
                    ->andWhere('l.paid_log = :log')->setParameter('log', $log->getId())
                    ->andWhere('l.paid = 1')
                    ->addOrderBy('l.created_at', 'DESC')
                    ->setMaxResults(1)->getQuery()->getOneOrNullResult();
                if ($revert) {
                    $status = 'paid';
                } else {
                    $revert = $this->em->getRepository('KrealabPaymentBundle:Log')->createQueryBuilder('l')
                        ->andWhere('l.paid_log = :log')->setParameter('log', $log->getId())
                        ->andWhere('l.paid = 0 AND l.info != :info')->setParameter('info', 'a:0:{}')
                        ->addOrderBy('l.created_at', 'DESC')
                        ->setMaxResults(1)->getQuery()->getOneOrNullResult();

                    $status = 'not_paid';
                    $info   = $revert->getInfo();
                    if (isset($info['fail'])) {
                        $status = 'fail';
                    }
                }
            }
        }

        return new JsonResponse($status);
    }
}
