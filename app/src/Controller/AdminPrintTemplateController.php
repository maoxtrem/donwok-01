<?php

namespace App\Controller;

use App\Entity\PrintTemplate;
use App\Service\PrintTemplateProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminPrintTemplateController extends AbstractController
{
    #[Route('/admin/plantillas-impresion', name: 'app_admin_print_templates', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PrintTemplateProvider $provider,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        $templates = [];
        foreach (['kitchen', 'customer'] as $code) {
            $template = $provider->get($code);
            if ($template->getId() === null) {
                $entityManager->persist($template);
            }
            $templates[$code] = $template;
        }

        $error = null;
        if ($request->isMethod('POST')) {
            if (!$csrf->isTokenValid(new CsrfToken('admin_print_templates', (string) $request->request->get('_token')))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            try {
                $data = $request->request->all('templates');
                foreach ($templates as $code => $template) {
                    $values = $data[$code] ?? [];
                    $brand = trim((string) ($values['brand'] ?? ''));
                    $title = trim((string) ($values['title'] ?? ''));
                    $label = trim((string) ($values['label'] ?? ''));
                    if ($brand === '' || $title === '' || $label === '') {
                        throw new \InvalidArgumentException('El nombre, encabezado y título son obligatorios para cada plantilla.');
                    }

                    $template
                        ->setLabel($label)
                        ->setBrand($brand)
                        ->setTitle($title)
                        ->setFooter((string) ($values['footer'] ?? ''))
                        ->setShowDate(isset($values['showDate']))
                        ->setShowOrderNumber(isset($values['showOrderNumber']))
                        ->setShowOrderType(isset($values['showOrderType']))
                        ->setShowTableNumber(isset($values['showTableNumber']))
                        ->setShowCustomer(isset($values['showCustomer']))
                        ->setShowDeliveryDetails(isset($values['showDeliveryDetails']))
                        ->setShowComponents(isset($values['showComponents']))
                        ->setShowPrices(isset($values['showPrices']))
                        ->setShowTotal(isset($values['showTotal']))
                        ->setShowPaymentDetails(isset($values['showPaymentDetails']));
                }

                $entityManager->flush();

                return $this->redirectToRoute('app_admin_print_templates');
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        $entityManager->flush();

        return $this->render('admin/print_templates.html.twig', [
            'user' => $this->getUser(),
            'templates' => $templates,
            'error' => $error,
        ]);
    }
}
