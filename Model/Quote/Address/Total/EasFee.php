<?php

declare(strict_types=1);

namespace Easproject\Eucompliance\Model\Quote\Address\Total;

use Easproject\Eucompliance\Model\Config\Configuration;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use \Magento\Quote\Model\Quote\Item\Repository;

/**
 * Copyright © EAS Project Oy. All rights reserved.
 */
class EasFee extends AbstractTotal
{
    /**
     * @var Repository
     */
    private Repository $repository;

    /**
     * EasFee constructor.
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->setCode(Configuration::EAS_FEE);
    }

    /**
     * @param  Quote                       $quote
     * @param  ShippingAssignmentInterface $shippingAssignment
     * @param  Total                       $total
     * @return EasFee
     */
    public function collect(
        Quote                       $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total                       $total
    ): EasFee {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }
        $easTaxAmount = $quote->getData(Configuration::EAS_TOTAL_TAX);
        $easTotalAmount = $quote->getData(Configuration::EAS_TOTAL_AMOUNT);

        foreach ($quote->getAllItems() as $item) {
            if ($item->getExtensionAttributes()) {
                $extAttributes = $item->getExtensionAttributes();
                if ($extAttributes->getEasTaxPercent()) {
                    $item->setTaxPercent($extAttributes->getEasTaxPercent());
                }
                if ($extAttributes->getEasTaxAmount()) {
                    $item->setTaxAmount($extAttributes->getEasTaxAmount());
                }
                if ($extAttributes->getEasRowTotal()) {
                    $item->setRowTotal($extAttributes->getEasRowTotal());
                }

                if ($extAttributes->getEasRowTotalInclTax()) {
                    $item->setRowTotalInclTax($extAttributes->getEasRowTotalInclTax());
                }
            }
        }

        if ($easTaxAmount) {
            $total->setData('tax_amount', $easTaxAmount);
            $total->setData('base_tax_amount', $easTaxAmount);
        }

        if ($easTotalAmount) {
            $total->setGrandTotal($easTotalAmount);
            $total->setBaseGrandTotal($easTotalAmount);
        }

        return $this;
    }

    /**
     * @param  Quote $quote
     * @param  Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total): array
    {
        return $this->getEasFeeTotal((float)$quote->getEas());
    }

    /**
     * @param  float $easFee
     * @return array
     */
    private function getEasFeeTotal(float $easFee): array
    {
        return [
            'code' => $this->getCode(),
            'title' => Configuration::EAS_FEE,
            'value' => $easFee
        ];
    }
}
