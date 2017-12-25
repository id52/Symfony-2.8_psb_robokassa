<?php

namespace KreaLab\PaymentBundle\Model;

/**
 * Log
 */
abstract class Log
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $s_type;

    /**
     * @var string
     */
    protected $s_id;

    /**
     * @var integer
     */
    protected $sum;

    /**
     * @var boolean
     */
    protected $paid;

    /**
     * @var array
     */
    protected $info;

    /**
     * @var integer
     */
    protected $revert_log_id;

    /**
     * @var \DateTime
     */
    protected $created_at;

    /**
     * @var \DateTime
     */
    protected $updated_at;

    /**
     * @var \KreaLab\PaymentBundle\Model\Log
     */
    protected $paid_log;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sType
     *
     * @param string $sType
     *
     * @return Log
     */
    public function setSType($sType)
    {
        $this->s_type = $sType;

        return $this;
    }

    /**
     * Get sType
     *
     * @return string
     */
    public function getSType()
    {
        return $this->s_type;
    }

    /**
     * Set sId
     *
     * @param string $sId
     *
     * @return Log
     */
    public function setSId($sId)
    {
        $this->s_id = $sId;

        return $this;
    }

    /**
     * Get sId
     *
     * @return string
     */
    public function getSId()
    {
        return $this->s_id;
    }

    /**
     * Set sum
     *
     * @param integer $sum
     *
     * @return Log
     */
    public function setSum($sum)
    {
        $this->sum = $sum;

        return $this;
    }

    /**
     * Get sum
     *
     * @return integer
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * Set paid
     *
     * @param boolean $paid
     *
     * @return Log
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;

        return $this;
    }

    /**
     * Is paid
     *
     * @return boolean
     */
    public function isPaid()
    {
        return $this->paid;
    }

    /**
     * Set info
     *
     * @param array $info
     *
     * @return Log
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set revertLogId
     *
     * @param integer $revertLogId
     *
     * @return Log
     */
    public function setRevertLogId($revertLogId)
    {
        $this->revert_log_id = $revertLogId;

        return $this;
    }

    /**
     * Get revertLogId
     *
     * @return integer
     */
    public function getRevertLogId()
    {
        return $this->revert_log_id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Log
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Log
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set paidLog
     *
     * @param \KreaLab\PaymentBundle\Model\Log $paidLog
     *
     * @return Log
     */
    public function setPaidLog(\KreaLab\PaymentBundle\Model\Log $paidLog = null)
    {
        $this->paid_log = $paidLog;

        return $this;
    }

    /**
     * Get paidLog
     *
     * @return \KreaLab\PaymentBundle\Model\Log
     */
    public function getPaidLog()
    {
        return $this->paid_log;
    }
}

