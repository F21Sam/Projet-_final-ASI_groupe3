<?php

//Controller de la commande, permet de créer, modifier, afficher ou supprimer une commande.


namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        return $this->json([
            'message' => 'La commande ' . $order->getId() . ' a bien été supprimée.',
            'order_id' => $order->getId(),
        ]);
    }

    #[Route('/content/{id}', name: 'get_order', methods: ['GET'])]
    public function getOrder(int $id): JsonResponse
    {
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

        return $this->json(['message' => 'La commande ' .$id . ' a bien été supprimée.']);
    }


}
