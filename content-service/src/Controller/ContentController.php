<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContentController extends AbstractController
{
    private $entityManager;
    private $httpClient;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient)
    {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
    }

    #[Route('/content', name: 'create_order', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['product_id'], $data['customer_email'], $data['quantity'], $data['total_price'])) {
            return $this->json(['message' => 'Invalid data'], 400);
        }

        $order = new Commande();
        $order->setProductId($data['product_id']);
        $order->setCustomerEmail($data['customer_email']);
        $order->setQuantity($data['quantity']);
        $order->setTotalPrice($data['total_price']);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $secondApiData = [
            'amount' => $data['total_price'],
            'due_date' => '2024-08-01', 
            'customer_email' => $data['customer_email'],
            'orderId' => $order->getId(),
        ];

        try {
            $response = $this->httpClient->request('POST', 'http://127.0.0.1:8001/billing', [
                'json' => $secondApiData
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Une erreur est survenue dans l\'appel de l\'API de facture.');
            }

            $secondApiResponse = $response->toArray();

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'La commande a bien été créée mais il n\'a pas été possible de contacter la seconde api.',
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ], 500);
        }

        return $this->json([
            'message' => 'La commande ' . $order->getId() . ' a bien été ajoutée.',
            'order_id' => $order->getId(),
            'second_api_response' => $secondApiResponse,
        ]);
    }

    #[Route('/content/{id?}', name: 'get_order', methods: ['GET'])]
    public function getOrder(?int $id): JsonResponse
    {
        if ($id) {
            $order = $this->entityManager->getRepository(Commande::class)->find($id);

            if (!$order) {
                return $this->json(['message' => 'La commande numéro ' . $id . ' n\'a pas été trouvée. Merci de bien vouloir réessayer.'], 404);
            }

            $orderData = [
                'id' => $order->getId(),
                'product_id' => $order->getProductId(),
                'customer_email' => $order->getCustomerEmail(),
                'quantity' => $order->getQuantity(),
                'total_price' => $order->getTotalPrice(),
            ];

            return $this->json($orderData);
        } else {
            $orders = $this->entityManager->getRepository(Commande::class)->findAll();

            $ordersData = [];
            foreach ($orders as $order) {
                $ordersData[] = [
                    'id' => $order->getId(),
                    'product_id' => $order->getProductId(),
                    'customer_email' => $order->getCustomerEmail(),
                    'quantity' => $order->getQuantity(),
                    'total_price' => $order->getTotalPrice(),
                ];
            }

            return $this->json($ordersData);
        }
    }

    #[Route('/content/{id}', name: 'update_order', methods: ['PUT'])]
    public function updateOrder(int $id, Request $request): JsonResponse
    {
        $order = $this->entityManager->getRepository(Commande::class)->find($id);
    
        if (!$order) {
            return $this->json(['message' => 'La commande numéro ' . $id . ' n\'a pas été trouvée. Merci de bien vouloir réessayer.'], 404);
        }
    
        $data = json_decode($request->getContent(), true);
    
        if (!isset($data['product_id'], $data['customer_email'], $data['quantity'], $data['total_price'])) {
            return $this->json(['message' => 'Invalid data'], 400);
        }
    
        $order->setProductId($data['product_id']);
        $order->setCustomerEmail($data['customer_email']);
        $order->setQuantity($data['quantity']);
        $order->setTotalPrice($data['total_price']);
        $this->entityManager->flush();
    
        return $this->json(['message' => 'La commande ' .$id . ' a bien été mise à jour.']);
    }
    
    #[Route('/content/{id}', name: 'delete_order', methods: ['DELETE'])]
    public function deleteOrder(int $id): JsonResponse
    {
        $order = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$order) {
            return $this->json(['message' => 'La commande numéro ' . $id . ' n\'a pas été trouvée. Merci de bien vouloir réessayer.'], 404);
        }

        $this->entityManager->remove($order);
        $this->entityManager->flush();

        try {
            $response = $this->httpClient->request('DELETE', 'http://127.0.0.1:8001/billing/' . $id);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Une erreur est survenue dans l\'appel de l\'API de facture.');
            }

            $secondApiResponse = $response->toArray();

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'La commande a bien été supprimée mais il n\'a pas été possible de contacter la seconde API.',
                'order_id' => $id,
                'error' => $e->getMessage()
            ], 500);
        }

        return $this->json(['message' => 'La commande ' . $id . ' a bien été supprimée.', 'second_api_response' => $secondApiResponse]);
    }
}
