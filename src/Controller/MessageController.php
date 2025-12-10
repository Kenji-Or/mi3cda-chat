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

        /** @var User $user */
        $user = $this->getUser();

        // Récupérer toutes les conversations triées par dernier message
        $allConversations = $conversationRepository->findAllUserConversationsOrderedByLastMessage($user);

        // Séparer les conversations pour l'affichage (optionnel, pour garder la structure existante)
        $conversationsA = [];
        $conversationsB = [];

        foreach ($allConversations as $conversation) {
            if ($conversation->getUserA() === $user) {
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
        $otherUsers = array_filter($allUsers, function($otherUser) use ($existingConversationUsers, $user) {
            return $otherUser !== $user && !in_array($otherUser->getId(), $existingConversationUsers);
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

        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$conversation->hasParticipant($user)) {
            return $this->redirectToRoute('app_messages');
        }

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $emptyForm = clone $form;

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){

            $message->setAuthor($user);
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

        $jwt = $jwtGenerator->generate($user);
        $hubUrl = $hub->getPublicUrl();
        $response->headers->set(key: 'set-cookie', values: "mercureAuthorization=$jwt; Path=$hubUrl; HttpOnly");

        return $response;
    }

    #[Route('/conversation-check/{id}', name: 'app_conversation_check')]
    public function check(User $withWhom, ConversationRepository $conversationRepository, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser()){return $this->redirectToRoute('app_login');}

        /** @var User $user */
        $user = $this->getUser();

        if(!$withWhom){
            return $this->redirectToRoute('app_messages');
        }

        $conversationAsA = $conversationRepository->findOneBy([
                "userA"=>$user,
                "userB"=>$withWhom
            ]
        );

        $conversationAsB = $conversationRepository->findOneBy([
                "userA"=>$withWhom,
                "userB"=>$user
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
            $conversation->setUserA($user);
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

    /**
     * Modification d'un message (édition inline)
     */
    #[Route('/message/edit/{id}', name: 'app_message_edit', methods: ['POST'])]
    public function editMessage(Message $message, Request $request, EntityManagerInterface $manager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur est l'auteur du message
        if ($message->getAuthor() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres messages.');
            return $this->redirectToRoute('app_conversation', ['id' => $message->getConversation()->getId()]);
        }

        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$message->getConversation()->hasParticipant($user)) {
            return $this->redirectToRoute('app_messages');
        }

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('edit_message', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_conversation', ['id' => $message->getConversation()->getId()]);
        }

        // Récupérer le nouveau contenu depuis le formulaire
        $newContent = $request->request->get('content');

        if (!empty(trim($newContent))) {
            $message->setContent($newContent);
            $message->setUpdatedAt(new \DateTime());
            $manager->flush();
        }

        return $this->redirectToRoute('app_conversation', ['id' => $message->getConversation()->getId()]);
    }


    /**
     * Suppression d'un message avec mise à jour temps réel
     */
    #[Route('/message/delete/{id}', name: 'app_message_delete', methods: ['POST', 'DELETE'])]
    public function deleteMessage(Message $message, Request $request, EntityManagerInterface $manager, HubInterface $hub): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur est l'auteur du message
        if ($message->getAuthor() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres messages.');
            return $this->redirectToRoute('app_conversation', ['id' => $message->getConversation()->getId()]);
        }

        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$message->getConversation()->hasParticipant($user)) {
            return $this->redirectToRoute('app_messages');
        }

        // Vérification CSRF simple
        if (!$this->isCsrfTokenValid('delete_message_'.$message->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_conversation', ['id' => $message->getConversation()->getId()]);
        }

        $conversationId = $message->getConversation()->getId();
        $messageId = $message->getId();

        $manager->remove($message);
        $manager->flush();

        // Publier la suppression via Mercure
        $update = new Update(
            topics: "conversations/".$conversationId,
            data: $this->renderView('message/delete_stream.html.twig', [
                'messageId' => $messageId
            ]),
            private: true
        );

        $hub->publish($update);

        return $this->redirectToRoute('app_conversation', ['id' => $conversationId]);
    }


}
