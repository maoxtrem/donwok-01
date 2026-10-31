<?php

namespace App\Service;

use App\Entity\PrintTemplate;
use Doctrine\ORM\EntityManagerInterface;

final class PrintTemplateProvider
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /** @return list<PrintTemplate> */
    public function all(): array
    {
        return $this->entityManager->getRepository(PrintTemplate::class)->findBy([], ['id' => 'ASC']);
    }

    public function get(string $code): PrintTemplate
    {
        $template = $this->entityManager->getRepository(PrintTemplate::class)->findOneBy(['code' => $code]);
        if ($template instanceof PrintTemplate) {
            return $template;
        }

        $template = new PrintTemplate($code, $code === 'kitchen' ? 'Comanda de cocina' : 'Ticket de cliente');
        $template
            ->setBrand('DONWOK')
            ->setTitle($code === 'kitchen' ? 'COMANDA COCINA' : 'DESPRENDIBLE CLIENTE')
            ->setFooter($code === 'kitchen' ? 'Estado: en preparación' : 'Gracias por tu compra.')
            ->setShowDate(true)
            ->setShowOrderNumber(true)
            ->setShowOrderType(true)
            ->setShowTableNumber(true)
            ->setShowCustomer(true)
            ->setShowDeliveryDetails($code !== 'kitchen')
            ->setShowComponents(true)
            ->setShowPrices($code !== 'kitchen')
            ->setShowTotal($code !== 'kitchen')
            ->setShowPaymentDetails($code !== 'kitchen');

        return $template;
    }
}
