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
            ['code' => 'demo-arroz-300g', 'name' => 'Arroz', 'price' => '3200.00', 'unit' => 'gramo'],
            ['code' => 'demo-papa-100g', 'name' => 'Papa', 'price' => '1800.00', 'unit' => 'gramo'],
            ['code' => 'demo-costilla-1u', 'name' => 'Costilla', 'price' => '10500.00', 'unit' => 'unidad'],
            ['code' => 'demo-gaseosa-250ml', 'name' => 'Gaseosa', 'price' => '2500.00', 'unit' => 'ml'],
        ];

        $productEntities = [];

        foreach ($products as $data) {
            $product = $this->entityManager->getRepository(Product::class)->findOneBy(['code' => $data['code']]);

            if (!$product instanceof Product) {
                $product = (new Product())
                    ->setCode($data['code'])
                    ->setName($data['name'])
                    ->setPrice($data['price'])
                    ->setUnit($data['unit'])
                    ->setActive(true);
                $this->entityManager->persist($product);
                $output->writeln(sprintf('<info>Producto creado:</info> %s', $data['name']));
            } else {
                $output->writeln(sprintf('<comment>Producto existente:</comment> %s', $data['name']));
            }

            $productEntities[$data['code']] = $product;
        }

        $composite = $this->entityManager->getRepository(Composite::class)->findOneBy(['code' => 'demo-almuerzo-18000']);

        if (!$composite instanceof Composite) {
            $composite = (new Composite())
                ->setCode('demo-almuerzo-18000')
                ->setName('Arroz, papa, costilla y gaseosa')
                ->setDescription('Compuesto de prueba para validar el menú de venta.')
                ->setPrice('18000.00')
                ->setActive(true);

            $components = [
                ['code' => 'demo-arroz-300g', 'quantity' => '300.000', 'unit' => 'gramo'],
                ['code' => 'demo-papa-100g', 'quantity' => '100.000', 'unit' => 'gramo'],
                ['code' => 'demo-costilla-1u', 'quantity' => '1.000', 'unit' => 'unidad'],
                ['code' => 'demo-gaseosa-250ml', 'quantity' => '250.000', 'unit' => 'ml'],
            ];

            foreach ($components as $position => $data) {
                $composite->addComponent(
                    (new CompositeComponent())
                        ->setProduct($productEntities[$data['code']])
                        ->setQuantity($data['quantity'])
                        ->setUnit($data['unit'])
                        ->setPosition($position),
                );
            }

            $this->entityManager->persist($composite);
            $output->writeln('<info>Compuesto creado:</info> Arroz, papa, costilla y gaseosa - $18.000');
        } else {
            $output->writeln('<comment>Compuesto existente:</comment> Arroz, papa, costilla y gaseosa');
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
