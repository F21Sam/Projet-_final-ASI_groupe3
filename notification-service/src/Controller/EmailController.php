<?php

namespace App\Controller;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController extends AbstractController
{

    // API Post permettant l'envoi d'un mail, un sujet, un message et un destinataire doivent être fournie pour que l'API s'éxecute.
    #[Route('/send-email', name: 'app_email', methods: ['POST'])]
    public function sendEmail(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {

        //Récupération des données en JSON du corps de la requête HTTP
        $data = json_decode($request->getContent(), true);

        if (!isset($data['sujet'], $data['recipient'], $data['message'])) {
            return new JsonResponse(['error' => 'Toutes les données attendues n\'ont pas été fournies.'], Response::HTTP_BAD_REQUEST);
        }


        //Insertion en bdd des données
        $notification = new Notification();
        $notification->setSujet($data['sujet']);
        $notification->setEmailRecipient($data['recipient']);
        $notification->setMessage($data['message']);

        $entityManager->persist($notification);
        $entityManager->flush();


        //Préparation de l'envoi d'un email.
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'landtales.website@gmail.com'; 
            $mail->Password = 'ohif qsqv ccbb usdd'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('landtales.website@gmail.com', 'La Super Marque');
            $mail->addAddress($data['recipient']); 

            $mail->isHTML(true);
            $mail->Subject = $data['sujet'];
            $mail->Body    = $data['message'];
            $mail->AltBody = strip_tags($data['message']);

            $mail->send();
        } catch (Exception $e) {
            return new JsonResponse(['error' => utf8_encode('Le message n\'a pas pu être envoyé. L\'erreur de PHP mailer : ' . $mail->ErrorInfo)], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        //Retour des erreurs ou du message de succès.
        return new JsonResponse(['success' => 'L\'email a bien été envoyé à l\'utilisateur '], Response::HTTP_OK);
    }


    // API Get permettant la récupération des informations du mail envoyé. 
    #[Route('/notification/{id?}', name: 'get_notification', methods: ['GET'])]
    public function getNotificationById(EntityManagerInterface $entityManager, $id = null): JsonResponse
    {
        $repository = $entityManager->getRepository(Notification::class);


        //Récupération de l'id, si l'id n'est pas fourni, alors nous retournons l'ensemble des lignes de la bdd.
        if ($id !== null) {
            $notification = $repository->find($id);

            if (!$notification) {
                return new JsonResponse(['error' => 'Notification not found'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'id' => $notification->getId(),
                'sujet' => $notification->getSujet(),
                'recipient' => $notification->getEmailRecipient(),
                'message' => $notification->getMessage(),
            ]);
        } else {

            $notifications = $repository->findAll();

            $formattedNotifications = [];
            foreach ($notifications as $notification) {
                $formattedNotifications[] = [
                    'id' => $notification->getId(),
                    'sujet' => $notification->getSujet(),
                    'recipient' => $notification->getEmailRecipient(),
                    'message' => $notification->getMessage(),
                ];
            }


            //Retour des informations en dans une réponse JSON
            return new JsonResponse($formattedNotifications);
        }
    }
}
