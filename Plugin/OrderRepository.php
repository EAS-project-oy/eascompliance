<?php

namespace Easproject\Eucompliance\Plugin;

use Easproject\Eucompliance\Service\Calculate;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Copyright © EAS Project Oy. All rights reserved.
 */
class OrderRepository
{
    const PENDING = 'pending';
    /**
     * @var Calculate
     */
    private Calculate $calculate;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $cartRepository;

    /**
     * @param Calculate               $calculate
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Calculate               $calculate,
        CartRepositoryInterface $cartRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->calculate = $calculate;
    }

    /**
     * @param \Magento\Sales\Model\OrderRepository $subject
     * @param OrderInterface                       $result
     *
     * @return OrderInterface
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws \Zend_Http_Client_Exception
     */
    public function afterGet(
        \Magento\Sales\Model\OrderRepository $subject,
        OrderInterface                       $result
    ): OrderInterface {
        if ($result->getStatus() == 'processing' || $result->getStatus() == 'complete') {
            $this->calculate->confirmOrder($result);
        }

        return $result;
    }

    /**
     * @param  \Magento\Sales\Model\OrderRepository $subject
     * @param  OrderInterface                       $entity
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws \Zend_Http_Client_Exception
     */
    public function beforeSave(
        \Magento\Sales\Model\OrderRepository $subject,
        OrderInterface                       $entity
    ): array {
        if ($entity->getQuoteId()) {
            $quote = $this->cartRepository->get((int)$entity->getQuoteId());
            if ($quote->getEasTotalVat()) {
                $entity->setEasTotalVat($quote->getEasTotalVat());
            }
        }

        //Confirm order
        if (!$entity->getEntityId() && $entity->getPayment()->getMethod() !== Checkmo::PAYMENT_METHOD_CHECKMO_CODE) {
            if ($entity->getStatus() == self::PENDING) {
                $this->calculate->confirmOrder($entity);
            }
        }

        return [$entity];
    }
}
