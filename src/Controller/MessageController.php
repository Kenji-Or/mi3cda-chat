<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use App\Service\MercureJwtGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

final class MessageController extends AbstractController
{
    #[Route('/', name: 'app_messages')]
    public function index(UserRepository $userRepository, ConversationRepository $conversationRepository): Response
    {
        if(!$this->getUser())
        {
           return $this->redirectToRoute('app_login');
        }

        // Récupérer toutes les conversations triées par dernier message
        $allConversations = $conversationRepository->findAllUserConversationsOrderedByLastMessage($this->getUser());

        // Séparer les conversations pour l'affichage (optionnel, pour garder la structure existante)
        $conversationsA = [];
        $conversationsB = [];

        foreach ($allConversations as $conversation) {
            if ($conversation->getUserA() === $this->getUser()) {
                $conversationsA[] = $conversation;
            } else {
                $conversationsB[] = $conversation;
            }
        }

        // Récupérer tous les utilisateurs sauf l'utilisateur courant
        $allUsers = $userRepository->findAll();

        // Récupérer les IDs des utilisateurs avec qui on a déjà une conversation
        $existingConversationUsers = [];
        foreach ($conversationsA as $conv) {
            $existingConversationUsers[] = $conv->getUserB()->getId();
        }
        foreach ($conversationsB as $conv) {
            $existingConversationUsers[] = $conv->getUserA()->getId();
        }

        // Filtrer pour ne garder que les utilisateurs sans conversation
        $otherUsers = array_filter($allUsers, function($user) use ($existingConversationUsers) {
            return $user !== $this->getUser() && !in_array($user->getId(), $existingConversationUsers);
        });

        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
            'allConversations' => $allConversations,
            'convsA'=>$conversationsA,
            'convsB'=>$conversationsB,
            'otherUsers'=> $otherUsers
        ]);
    }

    #[Route('/conversation/{id}', name:"app_conversation")]
    public function conversation(MercureJwtGenerator $jwtGenerator ,HubInterface $hub, Conversation $conversation, EntityManagerInterface $manager, Request $request):Response
    {
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}


        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $emptyForm = clone $form;

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){

            $message->setAuthor($this->getUser());
            $message->setConversation($conversation);
            $message->setCreatedAt(new \DateTime());
            $manager->persist($message);
            $manager->flush();


            $update = new Update(
                topics: "conversations/".$conversation->getId(),
                data: $this->renderView('message/stream.html.twig', [
                    'message'=>$message
                ]),
                private: true
            );

            $hub->publish($update);
            $form = $emptyForm;

        }



        $response = $this->render("message/conversation.html.twig", [
            "conversation"=>$conversation,
            "form"=>$form
        ]);

        $jwt = $jwtGenerator->generate($this->getUser());
        $hubUrl = $hub->getPublicUrl();
        $response->headers->set(key: 'set-cookie', values: "mercureAuthorization=$jwt; Path=$hubUrl; HttpOnly");

        return $response;
    }

    #[Route('/conversation-check/{id}', name: 'app_conversation_check')]
    public function check(User $withWhom, ConversationRepository $conversationRepository, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}

        if(!$withWhom){return $this->redirectToRoute('app_messages');}

        $conversationAsA = $conversationRepository->findOneBy([
                "userA"=>$this->getUser(),
                "userB"=>$withWhom
            ]
        );

        $conversationAsB = $conversationRepository->findOneBy([
                "userA"=>$withWhom,
                "userB"=>$this->getUser()
            ]
        );

        $conversation = null;

        if(!$conversationAsA){
            $conversation = $conversationAsB;
        }

        if(!$conversationAsB){
            $conversation = $conversationAsA;
        }


        if(!$conversation){
            $conversation = new Conversation();
            $conversation->setUserA($this->getUser());
            $conversation->setUserB($withWhom);
            $conversation->setCreatedAt(new \DateTime());
            $manager->persist($conversation);
            $manager->flush();
            $idConversation = $conversation->getId();
        }else{
            $idConversation = $conversation->getId();
        }

        return $this->redirectToRoute('app_conversation', [
            "id"=>$idConversation,
        ]);
    }
}
