<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


class CommandeController extends AbstractController
{
    #[Route('/commande', name: 'app_commande')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CommandeController.php',
        ]);
    }
}
// src/Controller/CommandeController.php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CommandeController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande", name="create_commande", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $commande = new Commande();
        $commande->setProductId($data['product_id']);
        $commande->setCustomerEmail($data['customer_email']);
        $commande->setQuantity($data['quantity']);
        $commande->setTotalPrice($data['total_price']);
        $commande->setOrderStatus($data['order_status']);

        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Commande created!'], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/commande/{id}", name="get_commande", methods={"GET"})
     */
    public function read($id): JsonResponse
    {
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            return new JsonResponse(['status' => 'Commande not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $commande->getId(),
            'product_id' => $commande->getProductId(),
            'customer_email' => $commande->getCustomerEmail(),
            'quantity' => $commande->getQuantity(),
            'total_price' => $commande->getTotalPrice(),
            'order_status' => $commande->getOrderStatus(),
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/commande/{id}", name="update_commande", methods={"PUT"})
     */
    public function update($id, Request $request): JsonResponse
    {
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            return new JsonResponse(['status' => 'Commande not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['product_id'])) {
            $commande->setProductId($data['product_id']);
        }
        if (isset($data['customer_email'])) {
            $commande->setCustomerEmail($data['customer_email']);
        }
        if (isset($data['quantity'])) {
            $commande->setQuantity($data['quantity']);
        }
        if (isset($data['total_price'])) {
            $commande->setTotalPrice($data['total_price']);
        }
        if (isset($data['order_status'])) {
            $commande->setOrderStatus($data['order_status']);
        }

        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Commande updated!'], JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/commande/{id}", name="delete_commande", methods={"DELETE"})
     */
    public function delete($id): JsonResponse
    {
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            return new JsonResponse(['status' => 'Commande not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($commande);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'Commande deleted!'], JsonResponse::HTTP_OK);
    }
}

