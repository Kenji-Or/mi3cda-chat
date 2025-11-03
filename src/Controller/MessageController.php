<?php

namespace App\Controller;

use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MessageController extends AbstractController
{
    #[Route('/', name: 'app_messages')]
    public function index(UserRepository $userRepository): Response
    {
        if(!$this->getUser())
        {
           return $this->redirectToRoute('app_login');
        }

        $conversationsA = $this->getUser()->getConversationsAsUserA();
        $conversationsB = $this->getUser()->getConversationsAsUserB();

        $otherUsers = $userRepository->findAll();

        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
            'convsA'=>$conversationsA,
            'convsB'=>$conversationsB,
            'otherUsers'=> $otherUsers
        ]);
    }

    #[Route('/conversation/{id}')]
    public function conversation(Conversation $conversation):Response
    {


        return $this->render("message/conversation.html.twig", [
            "conversation"=>$conversation
        ]);
    }
}
