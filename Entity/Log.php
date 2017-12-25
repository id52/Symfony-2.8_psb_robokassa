<?php

namespace KreaLab\PaymentBundle\Entity;

use KreaLab\PaymentBundle\Model\Log as LogModel;

class Log extends LogModel
{
    protected $paid   = false;
    protected $info   = [];
}
