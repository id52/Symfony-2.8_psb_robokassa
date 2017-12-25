<?php

namespace KreaLab\PaymentBundle\Service;

use Doctrine\ORM\EntityManager;
use KreaLab\PaymentBundle\Entity\Log;
use Symfony\Bundle\TwigBundle\TwigEngine;

class PaymentServiceRobokassa
{
    protected $em;
    protected $templating;

    public function __construct(EntityManager $em, TwigEngine $templating)
    {
        $this->em         = $em;
        $this->templating = $templating;
    }

    /** @inheritdoc */
    public function success(Log $log)
    {
    }

    /** @inheritdoc */
    public function renderSuccess($tmpl, $options, $user)
    {
        return $this->templating->renderResponse($tmpl, $options);
    }

    /** @inheritdoc */
    public function fail(Log $log = null)
    {
    }

    /** @inheritdoc */
    public function renderFail($tmpl, $options, $user)
    {
        return $this->templating->renderResponse($tmpl, $options);
    }
}
