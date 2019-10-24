<?php


namespace Xigen\AutoShipment\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * AutoShip command class
 */
class AutoShip extends Command
{
    const ALL_ARGUMENT = 'all';
    const ORDERID_OPTION = 'orderid';
    const EMAIL_OPTION = 'email';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

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
     * @param \Magento\Framework\App\State $state
     * @param \Xigen\AutoShipment\Helper\Shipment $shipmentHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        \Xigen\AutoShipment\Helper\Shipment $shipmentHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->logger = $logger;
        $this->state = $state;
        $this->shipmentHelper = $shipmentHelper;
        $this->dateTime = $dateTime;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $orderId = $this->input->getOption(self::ORDERID_OPTION);
        $doNotify = (int) $this->input->getOption(self::EMAIL_OPTION);
        $all = $input->getArgument(self::ALL_ARGUMENT) ?: false;

        if ($orderId) {
            $this->output->writeln((string) __(
                '%1 Processing order <info>%2</info>',
                $this->dateTime->gmtDate(),
                $orderId
            ));
            $order = $this->shipmentHelper
                ->getOrderByIncrementId($orderId);
            if ($order) {
                $shipped = $this->shipmentHelper->markAsShipped($order, (bool) $doNotify);
                $message = $shipped ? '[success]' : '[failure]';
                $this->output->writeln((string) __(
                    '%1 <info>%2</info> shipping order %3',
                    $this->dateTime->gmtDate(),
                    $message,
                    $orderId
                ));
            }
        } elseif ($all) {
            $this->output->writeln((string) __('%1 Start Processing orders', $this->dateTime->gmtDate()));
            $this->shipmentHelper->shipOrders();
            $this->output->writeln((string) __('%1 Finish Processing orders', $this->dateTime->gmtDate()));
        }
    }

    /**
     * {@inheritdoc}
     * xigen:autoshipment:autoship [-o|--orderid ORDERID] [-e|--email [EMAIL]] [--] <all>
     */
    protected function configure()
    {
        $this->setName('xigen:autoshipment:autoship');
        $this->setDescription('Automatically ship orders');
        $this->setDefinition([
            new InputArgument(self::ALL_ARGUMENT, InputArgument::OPTIONAL, 'All'),
            new InputOption(self::ORDERID_OPTION, '-o', InputOption::VALUE_OPTIONAL, 'Order Increment ID'),
            new InputOption(self::EMAIL_OPTION, '-e', InputOption::VALUE_OPTIONAL, 'Email'),
        ]);
        parent::configure();
    }
}
