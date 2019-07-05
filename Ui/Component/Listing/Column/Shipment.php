<?php

namespace Xigen\AutoShipment\Ui\Component\Listing\Column;

/**
 * Shipment class
 */
class Shipment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Xigen\AutoShipment\Helper\Shipment::PREVENT_SHIPMENT_YES,
                'label' => __('Yes')
            ],
            [
                'value' => \Xigen\AutoShipment\Helper\Shipment::PREVENT_SHIPMENT_NO,
                'label' => __('No')
            ]
        ];
    }
}
