<?php


namespace Xigen\AutoShipment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * AutoShip helper class
 */
class Shipment extends AbstractHelper
{
    const PREVENT_SHIPMENT_NO = 2;
    const PREVENT_SHIPMENT_YES = 1;

    const SEND_EMAIL_NO = 0;
    const SEND_EMAIL_YES = 1;

    const ORDERS_BACKLOG = 900;
    const ORDERS_RECENT = 14;
    const ORDERS_NOW = 1;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterfaces
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * Shipment constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
        $this->convertOrder = $convertOrder;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        parent::__construct($context);
    }

    /**
     * Query to fetch orders to flag as shipped and email customer
     * @param int $from
     * @param int $to
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function orderCollection($from = self::ORDERS_RECENT, $to = self::ORDERS_NOW)
    {
        $collection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('prevent_automatic_shipment', ['eq' => self::PREVENT_SHIPMENT_NO])
            ->addAttributeToFilter('state', ['eq' => \Magento\Sales\Model\Order::STATE_PROCESSING])
            ->addAttributeToFilter(
                'created_at',
                [
                    'from' => [
                        date("Y-m-d h:i:s", strtotime("-$from days"))
                    ],
                    'to' => [
                        date("Y-m-d h:i:s", strtotime("-$to day"))
                    ],
                ]
            );

        return $collection;
    }

    public function shipOrders()
    {
        // ship and notify recent orders
        $recentOrders = $this->shipmentHelper
            ->orderCollection(self::ORDERS_RECENT, self::ORDERS_NOW);
        foreach ($recentOrders as $order) {
            $this->markAsShipped($order, self::SEND_EMAIL_YES);
        }

        // ship old orders
        $oldOrders = $this->shipmentHelper
            ->orderCollection(self::ORDERS_BACKLOG, self::ORDERS_RECENT);
        foreach ($oldOrders as $order) {
            $this->markAsShipped($order, self::SEND_EMAIL_NO);
        }
        return true;
    }

    /**
     * Load order by increment Id
     * @param string $incrementId
     * @return \Magento\Sales\Model\Data\Order
     */
    public function getOrderByIncrementId($incrementId = null)
    {
        if (!$incrementId) {
            return false;
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId, 'eq')
            ->create();
        $order = $this->orderRepository
            ->getList($searchCriteria)
            ->getFirstItem();
        if ($order && $order->getId()) {
            return $order;
        }
        return false;
    }

    /**
     * Mark order as shipped
     * @param \Magento\Sales\Model\Order $order
     * @param boolean $doNotify
     * @return bool
     */
    public function markAsShipped($order, $doNotify = true)
    {
        if (!$order || !$order->canShip()) {
            return;
        }
        try {
            $orderShipment = $this->convertOrder->toShipment($order);
            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }
                $shipmentItem = $this->convertOrder
                    ->itemToShipmentItem($orderItem)
                    ->setQty($orderItem->getQtyToShip());
                $orderShipment->addItem($shipmentItem);
            }
            $orderShipment->register();
            $orderShipment->getOrder()->setIsInProcess(true);

            $orderShipment->save();
            $orderShipment->getOrder()->save();

            if ($doNotify) {
                $this->objectManager->create(\Magento\Shipping\Model\ShipmentNotifier::class)
                    ->notify($orderShipment);
                $orderShipment->save();
            }

            $order->addStatusToHistory($order->getStatus(), 'Order has been marked as complete');
            $order->save();

            return true;
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return false;
    }
}
