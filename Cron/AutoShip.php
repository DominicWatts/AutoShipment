<?php


namespace Xigen\AutoShipment\Cron;

/**
 * AutoShip cron class
 */
class AutoShip
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Xigen\AutoShipment\Helper\Shipment
     */
    protected $shipmentHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * AutoShip constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Xigen\AutoShipment\Helper\Shipment $shipmentHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Xigen\AutoShipment\Helper\Shipment $shipmentHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->shipmentHelper = $shipmentHelper;
    }

    /**
     * Execute the cron
     * @return void
     */
    public function execute()
    {
        $this->logger->addInfo(__('Cronjob AutoShip is executed.'));
        $this->shipmentHelper->shipOrders();
        $this->logger->addInfo(__('Cronjob AutoShip is finished.'));
    }
}
