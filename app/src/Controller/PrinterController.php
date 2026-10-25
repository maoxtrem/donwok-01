<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

final class PrinterController extends AbstractController
{
    public function __construct(private readonly string $printerName)
    {
    }

    #[Route('/api/impresora/estado', name: 'api_printer_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $process = new Process(['lpstat', '-p', $this->printerName]);
        $process->setTimeout(5);
        $process->run();
        $available = $process->isSuccessful();

        return $this->json([
            'available' => $available,
            'printer' => $this->printerName,
            'message' => $available ? 'Impresora disponible.' : 'La impresora no está disponible.',
        ], $available ? 200 : 503);
    }
}
