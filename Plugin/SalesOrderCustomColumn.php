<?php
namespace Xigen\AutoShipment\Plugin;

use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as SalesOrderGridCollection;

/**
 * SalesOrderCustomColumn class
 */
class SalesOrderCustomColumn
{
    private $messageManager;
    private $collection;

    public function __construct(
        MessageManager $messageManager,
        SalesOrderGridCollection $collection
    ) {
        $this->messageManager = $messageManager;
        $this->collection = $collection;
    }

    public function aroundGetReport(
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject,
        \Closure $proceed,
        $requestName
    ) {
        $result = $proceed($requestName);
        if ($requestName == 'sales_order_grid_data_source') {
            if ($result instanceof $this->collection
            ) {
                $select = $this->collection->getSelect();
                $select->joinLeft(
                    ["sales_order" => $this->collection->getTable("sales_order")],
                    'main_table.increment_id = sales_order.increment_id',
                    ['prevent_automatic_shipment']
                );
                return $this->collection;
            }
        }
        return $result;
    }
}
