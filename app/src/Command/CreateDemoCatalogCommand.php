<?php

namespace App\Command;

use App\Entity\Composite;
use App\Entity\CompositeComponent;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:catalog:create-demo',
    description: 'Crea un producto compuesto de prueba para el menú de venta.',
)]
final class CreateDemoCatalogCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = [
            ['code' => 'demo-arroz-300g', 'name' => 'Arroz', 'measure' => 300, 'costPrice' => '1800.00', 'salePrice' => '3200.00', 'unit' => 'gramo'],
            ['code' => 'demo-papa-100g', 'name' => 'Papa', 'measure' => 100, 'costPrice' => '1000.00', 'salePrice' => '1800.00', 'unit' => 'gramo'],
            ['code' => 'demo-costilla-1u', 'name' => 'Costilla', 'measure' => 1, 'costPrice' => '7000.00', 'salePrice' => '10500.00', 'unit' => 'unidad'],
            ['code' => 'demo-gaseosa-250ml', 'name' => 'Gaseosa', 'measure' => 250, 'costPrice' => '1500.00', 'salePrice' => '2500.00', 'unit' => 'ml'],
        ];

        $productEntities = [];

        foreach ($products as $data) {
            $product = $this->entityManager->getRepository(Product::class)->findOneBy(['code' => $data['code']]);

            if (!$product instanceof Product) {
                $product = (new Product())
                    ->setCode($data['code'])
                    ->setName($data['name'])
                    ->setCostPrice($data['costPrice'])
                    ->setSalePrice($data['salePrice'])
                    ->setMeasure($data['measure'])
                    ->setUnit($data['unit'])
                    ->setActive(true);
                $this->entityManager->persist($product);
                $output->writeln(sprintf('<info>Producto creado:</info> %s', $data['name']));
            } else {
                $output->writeln(sprintf('<comment>Producto existente:</comment> %s', $data['name']));
            }

            $product
                ->setName($data['name'])
                ->setCostPrice($data['costPrice'])
                ->setSalePrice($data['salePrice'])
                ->setMeasure($data['measure'])
                ->setUnit($data['unit'])
                ->setActive(true);

            $productEntities[$data['code']] = $product;
        }

        $composite = $this->entityManager->getRepository(Composite::class)->findOneBy(['code' => 'demo-almuerzo-18000']);

        if (!$composite instanceof Composite) {
            $composite = (new Composite())
                ->setCode('demo-almuerzo-18000')
                ->setName('Arroz, papa, costilla y gaseosa')
                ->setDescription('Compuesto de prueba para validar el menú de venta.')
                ->setCostPrice('11300.00')
                ->setSalePrice('18000.00')
                ->setActive(true);

            $components = [
                    ['code' => 'demo-arroz-300g'],
                    ['code' => 'demo-papa-100g'],
                    ['code' => 'demo-costilla-1u'],
                    ['code' => 'demo-gaseosa-250ml'],
            ];

            foreach ($components as $position => $data) {
                $composite->addComponent(
                    (new CompositeComponent())
                        ->setProduct($productEntities[$data['code']])
                        ->setQuantity($productEntities[$data['code']]->getMeasure())
                        ->setUnit($productEntities[$data['code']]->getUnit())
                        ->setCostPrice($productEntities[$data['code']]->getCostPrice())
                        ->setSalePrice($productEntities[$data['code']]->getSalePrice())
                        ->setPosition($position),
                );
            }

            $this->entityManager->persist($composite);
            $output->writeln('<info>Compuesto creado:</info> Arroz, papa, costilla y gaseosa - $18.000');
        } else {
            foreach ($composite->getComponents() as $position => $component) {
                $product = $component->getProduct();
                if ($product instanceof Product) {
                    $component
                        ->setCostPrice($product->getCostPrice())
                        ->setSalePrice($product->getSalePrice());
                    $component->setQuantity($product->getMeasure())->setUnit($product->getUnit());
                }
            }
            $composite->refreshPricesFromComponents();
            $output->writeln('<comment>Compuesto existente:</comment> Arroz, papa, costilla y gaseosa');
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
