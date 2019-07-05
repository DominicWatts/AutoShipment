# Auto Shipment # 

Automatically ship orders on cron or via console script

Mass action option on admin order grid to prevent auto shipment

# Install instructions #

`composer require dominicwatts/autoshipment`

`php bin/magento setup:upgrade`

`php bin/magento setup:di:compile`

# Usage instructions #

Console script to generate shipments either by batch or by increment ID.  Batch process is same process as triggered on cron.

`xigen:autoshipment:autoship [-o|--orderid [ORDERID]] [-e|--email [EMAIL]] [--] [<all>]`

Run Auto Shipment process

`php bin/magento xigen:autoshipment:autoship all`

Ship but don't notify

`php bin/magento xigen:autoshipment:autoship -o 000000030 -e 0`

Ship and notify

`php bin/magento xigen:autoshipment:autoship -o 000000030 -e 1`