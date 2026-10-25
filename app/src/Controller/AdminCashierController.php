<?php

namespace App\Controller;

use App\Entity\CashBox;
use App\Entity\Cashier;
use App\Entity\Enum\PaymentMethod;
use App\Entity\Enum\CashBoxMoneyType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/cajeros')]
final class AdminCashierController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'app_admin_cashiers', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $error = null;
        $userRepository = $this->entityManager->getRepository(User::class);

        if ($request->isMethod('POST')) {
            if ($request->request->get('unassign_user_id')) {
                $cashier = $this->entityManager->getRepository(Cashier::class)->findOneBy(['uuid' => (string) $request->request->get('unassign_user_id')]);
                if (!$cashier instanceof Cashier || $cashier->getUser() === null) {
                    $error = 'El módulo no tiene un usuario asignado.';
                } elseif ($cashier->isActive()) {
                    $error = 'Desactiva el módulo antes de desvincular al usuario.';
                } else {
                    $cashier->setUser(null);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('unassign_box_id')) {
                $box = $this->entityManager->getRepository(CashBox::class)->findOneBy(['uuid' => (string) $request->request->get('unassign_box_id')]);
                if (!$box instanceof CashBox || $box->getCashier() === null) {
                    $error = 'La caja no está asignada a un módulo.';
                } elseif ($box->getCashier()->isActive()) {
                    $error = 'Desactiva el módulo antes de desvincular una caja.';
                } elseif (!$this->isZeroBalance($box)) {
                    $error = 'Solo puedes desvincular una caja con saldo cero.';
                } else {
                    $box->setCashier(null)->setDefaultPaymentMethod(null);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('toggle_default_box_id')) {
                $box = $this->entityManager->getRepository(CashBox::class)->findOneBy(['uuid' => (string) $request->request->get('toggle_default_box_id')]);
                if (!$box instanceof CashBox || $box->getCashier() === null) {
                    $error = 'Selecciona una caja asignada a un módulo.';
                } elseif ($request->request->get('default_enabled') !== '1') {
                    $box->setDefaultPaymentMethod(null);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                } else {
                    $defaultMethod = $box->getMoneyType() === CashBoxMoneyType::CASH ? PaymentMethod::CASH : PaymentMethod::DIGITAL;
                    foreach ($box->getCashier()->getBoxes() as $moduleBox) {
                        if ($moduleBox !== $box && $moduleBox->getDefaultPaymentMethod() === $defaultMethod) {
                            $moduleBox->setDefaultPaymentMethod(null);
                        }
                    }
                    $box->setDefaultPaymentMethod($defaultMethod);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('default_box_id')) {
                $box = $this->entityManager->getRepository(CashBox::class)->findOneBy(['uuid' => (string) $request->request->get('default_box_id')]);
                if (!$box instanceof CashBox || $box->getCashier() === null) {
                    $error = 'Selecciona una caja asignada a un módulo.';
                } else {
                    $defaultMethod = $box->getMoneyType() === CashBoxMoneyType::CASH ? PaymentMethod::CASH : PaymentMethod::DIGITAL;
                    foreach ($box->getCashier()->getBoxes() as $moduleBox) {
                        if ($moduleBox !== $box && $moduleBox->getDefaultPaymentMethod() === $defaultMethod) {
                            $moduleBox->setDefaultPaymentMethod(null);
                        }
                    }
                    $box->setDefaultPaymentMethod($defaultMethod);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('delete_box_id')) {
                $box = $this->entityManager->getRepository(CashBox::class)->findOneBy(['uuid' => (string) $request->request->get('delete_box_id')]);
                if (!$box instanceof CashBox) {
                    $error = 'La caja no existe.';
                } elseif ($box->getCashier() !== null) {
                    $error = 'No puedes eliminar una caja asignada a un módulo.';
                } elseif (!$this->isZeroBalance($box)) {
                    $error = 'Solo puedes eliminar una caja con saldo cero.';
                } else {
                    $this->entityManager->remove($box);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('edit_box_id')) {
                $box = $this->entityManager->getRepository(CashBox::class)->findOneBy(['uuid' => (string) $request->request->get('edit_box_id')]);
                $boxName = trim((string) $request->request->get('edit_box_name'));
                if (!$box instanceof CashBox || $boxName === '') {
                    $error = 'La caja y su nombre son obligatorios.';
                } else {
                    $box->setName($boxName);
                    $boxType = CashBoxMoneyType::tryFrom((string) $request->request->get('edit_box_type'));
                    if ($boxType instanceof CashBoxMoneyType) {
                        $box->setMoneyType($boxType);
                        if (($boxType === CashBoxMoneyType::CASH && $box->getDefaultPaymentMethod() === PaymentMethod::DIGITAL)
                            || ($boxType === CashBoxMoneyType::DIGITAL && $box->getDefaultPaymentMethod() === PaymentMethod::CASH)) {
                            $box->setDefaultPaymentMethod(null);
                        }
                    }
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('assign_cashier_id')) {
                $cashier = $this->entityManager->getRepository(Cashier::class)->findOneBy(['uuid' => (string) $request->request->get('assign_cashier_id')]);
                $user = $userRepository->find((int) $request->request->get('assign_user_id'));
                if (!$cashier instanceof Cashier || !$user instanceof User || !$user->isActive()) {
                    $error = 'Selecciona un cajero y un usuario activo.';
                } elseif ($cashier->getUser() !== null) {
                    $error = 'Ese módulo ya está asignado a un usuario.';
                } elseif ($user->getCashier() instanceof Cashier && $user->getCashier() !== $cashier) {
                    $error = 'Ese usuario ya está asignado a otro cajero.';
                } else {
                    $cashier->setUser($user);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('free_box') !== null) {
                $boxName = trim((string) $request->request->get('free_name'));
                $box = null;
                if ($boxName === '') {
                    $error = 'El nombre de la caja libre es obligatorio.';
                } else {
                    $boxType = CashBoxMoneyType::tryFrom((string) $request->request->get('free_type'));
                    if (!$boxType instanceof CashBoxMoneyType) {
                        $error = 'Selecciona si la caja manejará efectivo o dinero digital.';
                        $box = null;
                    } else {
                        $box = (new CashBox())->setName($boxName)->setMoneyType($boxType);
                    }
                }
                if ($box instanceof CashBox) {
                    $this->entityManager->persist($box);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('assign_box') !== null) {
                $cashier = $this->entityManager->getRepository(Cashier::class)->findOneBy(['uuid' => (string) $request->request->get('cashier_id')]);
                $box = $this->entityManager->getRepository(CashBox::class)->findOneBy(['uuid' => (string) $request->request->get('box_id')]);
                if (!$cashier instanceof Cashier || !$cashier->isActive() || !$box instanceof CashBox || $box->getCashier() !== null) {
                    $error = 'Selecciona un módulo activo y una caja libre.';
                } else {
                    $cashier->addBox($box);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            } elseif ($request->request->get('cashier_name') !== null) {
                $cashierName = trim((string) $request->request->get('cashier_name'));
                if ($cashierName === '') {
                    $error = 'El nombre del módulo de pago es obligatorio.';
                } else {
                    $cashier = (new Cashier())->setName($cashierName)->setCreatedBy($this->getUser());
                        $cashier->addBox(
                            (new CashBox())
                                ->setName('Efectivo')
                                ->setMoneyType(CashBoxMoneyType::CASH)
                                ->setDefaultPaymentMethod(PaymentMethod::CASH)
                        );
                        $cashier->addBox(
                            (new CashBox())
                                ->setName('Digital')
                                ->setMoneyType(CashBoxMoneyType::DIGITAL)
                                ->setDefaultPaymentMethod(PaymentMethod::DIGITAL)
                        );
                    $this->entityManager->persist($cashier);
                    $this->entityManager->flush();
                    return $this->redirectToRoute('app_admin_cashiers');
                }
            }
        }

        return $this->render('admin/cashiers.html.twig', [
            'user' => $this->getUser(),
            'cashiers' => $this->entityManager->getRepository(Cashier::class)->findAll(),
            'free_boxes' => $this->entityManager->getRepository(CashBox::class)->createQueryBuilder('b')->where('b.cashier IS NULL')->orderBy('b.name', 'ASC')->getQuery()->getResult(),
            'users' => $userRepository->findBy(['active' => true], ['username' => 'ASC']),
            'error' => $error,
        ]);
    }

    private function isZeroBalance(CashBox $box): bool
    {
        return preg_match('/^0+(?:\.0{1,2})?$/', trim($box->getBalance())) === 1;
    }
}
