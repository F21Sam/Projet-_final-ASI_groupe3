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
    #[Route('/send-email', name: 'app_email', methods: ['POST'])]
    public function sendEmail(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['sujet'], $data['recipient'], $data['message'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $notification = new Notification();
        $notification->setSujet($data['sujet']);
        $notification->setEmailRecipient($data['recipient']);
        $notification->setMessage($data['message']);

        $entityManager->persist($notification);
        $entityManager->flush();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'landtales.website@gmail.com'; 
            $mail->Password = 'secret'; 
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

        return new JsonResponse(['success' => 'L\'email a bien été envoyé à l\'utilisateur '], Response::HTTP_OK);
    }
}
