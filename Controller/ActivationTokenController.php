<?php

namespace UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use UserBundle\Entity\ActivationToken;
use UserBundle\Form\ActivationTokenType;

/**
 * ActivationToken controller.
 *
 * @Route("/activation")
 */
class ActivationTokenController extends Controller
{
    /**
     * Lists all ActivationToken entities.
     *
     * @Route("/{token}", name="user_activate")
     * @Method("GET")
     */
    public function indexAction($token)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenEntry = $em->getRepository('UserBundle:ActivationToken')
            ->findOneBy(array('token' => $token));

        // find user based on email from token
        $userObject = $em->getRepository('UserBundle:User')
            ->findOneBy(array('email' => $tokenEntry->getEmail()));

        // activate user
        $userObject->setEnabled(true);
        $em->persist($userObject);

        // remove token from db
        $em->remove($tokenEntry);
        $em->flush();

        $this->addFlash('success', 'User enabled, proceed to login');
        return $this->redirectToRoute('homepage');
    }

    /**
     * Creates a new ActivationToken entity.
     *
     * @Route("/new", name="activation_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $activationToken = new ActivationToken();
        $form = $this->createForm('UserBundle\Form\ActivationTokenType', $activationToken);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($activationToken);
            $em->flush();

            return $this->redirectToRoute('activation_show', array('id' => $activationToken->getId()));
        }

        return $this->render('activationtoken/new.html.twig', array(
            'activationToken' => $activationToken,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a ActivationToken entity.
     *
     * @Route("/{id}", name="activation_show")
     * @Method("GET")
     */
    public function showAction(ActivationToken $activationToken)
    {
        $deleteForm = $this->createDeleteForm($activationToken);

        return $this->render('activationtoken/show.html.twig', array(
            'activationToken' => $activationToken,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing ActivationToken entity.
     *
     * @Route("/{id}/edit", name="activation_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, ActivationToken $activationToken)
    {
        $deleteForm = $this->createDeleteForm($activationToken);
        $editForm = $this->createForm('UserBundle\Form\ActivationTokenType', $activationToken);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($activationToken);
            $em->flush();

            return $this->redirectToRoute('activation_edit', array('id' => $activationToken->getId()));
        }

        return $this->render('activationtoken/edit.html.twig', array(
            'activationToken' => $activationToken,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a ActivationToken entity.
     *
     * @Route("/{id}", name="activation_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, ActivationToken $activationToken)
    {
        $form = $this->createDeleteForm($activationToken);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($activationToken);
            $em->flush();
        }

        return $this->redirectToRoute('activation_index');
    }

    /**
     * Creates a form to delete a ActivationToken entity.
     *
     * @param ActivationToken $activationToken The ActivationToken entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(ActivationToken $activationToken)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('activation_delete', array('id' => $activationToken->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
