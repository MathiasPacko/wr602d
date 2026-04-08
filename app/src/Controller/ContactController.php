<?php

namespace App\Controller;

use App\Entity\UserContact;
use App\Form\UserContactType;
use App\Repository\UserContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contacts')]
class ContactController extends AbstractController
{
    #[Route('/', name: 'app_contacts')]
    public function index(UserContactRepository $contactRepository): Response
    {
        $user = $this->getUser();
        $contacts = $contactRepository->findBy(
            ['user' => $user],
            ['lastname' => 'ASC', 'firstname' => 'ASC']
        );

        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,
        ]);
    }

    #[Route('/add', name: 'app_contact_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new UserContact();
        $form = $this->createForm(UserContactType::class, $contact, [
            'submit_label' => 'Ajouter le contact',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contact->setUser($this->getUser());
            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Contact ajouté avec succès.');
            return $this->redirectToRoute('app_contacts');
        }

        return $this->render('contact/add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_contact_edit')]
    public function edit(
        UserContact $contact,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que le contact appartient à l'utilisateur
        if ($contact->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(UserContactType::class, $contact, [
            'submit_label' => 'Modifier',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Contact modifié avec succès.');
            return $this->redirectToRoute('app_contacts');
        }

        return $this->render('contact/edit.html.twig', [
            'form' => $form,
            'contact' => $contact,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(
        UserContact $contact,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que le contact appartient à l'utilisateur
        if ($contact->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete_contact_' . $contact->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contact);
            $entityManager->flush();
            $this->addFlash('success', 'Contact supprimé avec succès.');
        }

        return $this->redirectToRoute('app_contacts');
    }
}
