<?php

namespace Unikov\BuyOneClick\Model;

use Magento\Framework\Mail\Template\TransportBuilder;

class ReportSend
{

    public function __construct(
        TransportBuilder $transportBuilder
    ) {
        $this->transportBuilder = $transportBuilder;
    }

    public function execute()
    {
        $report = [
            'report_date' => date("j F Y", strtotime('-1 day')),
            'orders_count' => rand(1, 10),
            'order_items_count' => rand(1, 10),
            'avg_items' => rand(1, 10)
        ];

        $postObject = new \Magento\Framework\DataObject();
        $postObject->setData($report);

        $transport = $this->transportBuilder
            ->setTemplateIdentifier('daily_status_template')
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
            ->setTemplateVars(['data' => $postObject])
            ->setFrom(['name' => 'Robot', 'email' => 'robot@server.com'])
            ->addTo(['fred@server.com', 'otherguy@server.com'])
            ->getTransport();
        $transport->sendMessage();
    }
}
