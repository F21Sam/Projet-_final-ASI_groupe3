<?php

namespace App\Controller;

use App\Entity\Billing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BillingController extends AbstractController
{
    private $entityManager;
    private $httpClient;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient)
    {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
    }

    #[Route('/billing', name: 'create_billing', methods: ['POST'])]
    public function createBilling(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['amount'], $data['due_date'], $data['customer_email'], $data['orderId'])) {
            return $this->json(['message' => 'Invalid data'], 400);
        }

        $billing = new Billing();
        $billing->setAmount($data['amount']);
        $billing->setDueDate(new \DateTime($data['due_date']));
        $billing->setCustomerEmail($data['customer_email']);
        $billing->setOrderId($data['orderId']);
        $this->entityManager->persist($billing);
        $this->entityManager->flush();

        $notificationData = [
            'sujet' => 'Nouvelle notification',
            'recipient' => $data['customer_email'],
            'message' => 'Détails de la commande'
        ];

        try {
            $response = $this->httpClient->request('POST', 'http://127.0.0.1:8001/send-email', [
                'json' => $notificationData
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to send notification');
            }

            $notificationResponse = $response->toArray();

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Billing created but failed to send notification.',
                'billing_id' => $billing->getId(),
                'error' => $e->getMessage()
            ], 500);
        }

        return $this->json([
            'message' => 'Billing created successfully',
            'billing_id' => $billing->getId(),
            'notification_response' => $notificationResponse,
        ]);
    }

    #[Route('/billing/{id}', name: 'get_billing', methods: ['GET'])]
    public function getBilling(int $id): JsonResponse
    {
        $billing = $this->entityManager->getRepository(Billing::class)->find($id);

        if (!$billing) {
            return $this->json(['message' => 'Billing not found'], 404);
        }

        $billingData = [
            'id' => $billing->getId(),
            'amount' => $billing->getAmount(),
            'due_date' => $billing->getDueDate()->format('Y-m-d'),
            'customer_email' => $billing->getCustomerEmail(),
            'orderId' => $billing->getOrderId(),
        ];

        return $this->json($billingData);
    }

    #[Route('/billing/{id}', name: 'update_billing', methods: ['PUT'])]
    public function updateBilling(int $id, Request $request): JsonResponse
    {
        $billing = $this->entityManager->getRepository(Billing::class)->find($id);

        if (!$billing) {
            return $this->json(['message' => 'Billing not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['amount'])) {
            $billing->setAmount($data['amount']);
        }
        if (isset($data['due_date'])) {
            $billing->setDueDate(new \DateTime($data['due_date']));
        }
        if (isset($data['customer_email'])) {
            $billing->setCustomerEmail($data['customer_email']);
        }
        if (isset($data['orderId'])) {
            $billing->setOrderId($data['orderId']);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Billing updated successfully']);
    }

    #[Route('/billing/{orderId}', name: 'delete_billing', methods: ['DELETE'])]
    public function deleteBillingByOrderId(int $orderId, HttpClientInterface $httpClient): JsonResponse
    {
        $billing = $this->entityManager->getRepository(Billing::class)->findOneBy(['orderId' => $orderId]);

        if (!$billing) {
            return $this->json(['message' => 'Billing not found for the given orderId'], 404);
        }

        $customerEmail = $billing->getCustomerEmail();
        $orderId = $billing->getOrderId();

        $this->entityManager->remove($billing);
        $this->entityManager->flush();

        try {
            $notificationData = [
                'sujet' => 'Notification de suppression de commande',
                'recipient' => $customerEmail,
                'message' => "Votre commande avec l'ID $orderId a été supprimée avec succès."
            ];

            $response = $httpClient->request('POST', 'http://127.0.0.1:8001/send-email', [
                'json' => $notificationData
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to send notification email');
            }

            $notificationResponse = $response->toArray();

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Billing deleted but failed to send notification email.',
                'error' => $e->getMessage()
            ], 500);
        }

        return $this->json(['message' => 'Billing deleted successfully', 'notification_response' => $notificationResponse ?? null]);
    }

}

