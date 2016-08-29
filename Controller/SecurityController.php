<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login_form")
     */
    public function loginAction()
    {
        $helper = $this->get('security.authentication_utils');

        // display the login error if there is one
        if($helper->getLastAuthenticationError()){
            $this->addFlash('error', 'Invalid user credentials or user is not activated');
        }

        return $this->render('UserBundle:Default:login.html.twig', array(
            // last username entered by the user
            'last_username' => $helper->getLastUsername()
        ));
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
    }
}