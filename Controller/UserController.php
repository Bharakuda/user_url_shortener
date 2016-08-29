<?php

namespace UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UserBundle\Entity\ActivationToken;
use UserBundle\Entity\PasswordResetToken;
use UserBundle\Entity\User;
use UserBundle\Form\ResetPasswordType;
use UserBundle\Form\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use UserBundle\Service\TokenGenerator;


/**
 * User controller.
 *
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * Lists all User entities.
     *
     * @Route("/", name="user_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('homepage');
        }
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('UserBundle:User')->findAll();

        return $this->render('@User/Default/admin_panel.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/register", name="user_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('homepage');
        }
        // create new user instance
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // encode and set new password on that user
            $plainPassword = $form['password']->getData();
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $plainPassword);
            $user->setPassword($encoded);

            $em->persist($user);
            $em->flush();

            // create activation token - using reset token entity
            $token = new TokenGenerator();
            $activationToken = new ActivationToken();
            $activationToken->setToken($token->generateToken());

            // link email with token
            $activationToken->setEmail($user->getEmail());

            // save token object to database
            $em->persist($activationToken);
            $em->flush();

            // generate reset link with token
            $activateUrl = $this->get('router')->generate('user_activate',  array('token'=>$activationToken->getToken()), 0);

            // send welcome email and activation link
            $message = \Swift_Message::newInstance(null)
                ->setSubject('Welcome to URLShortener.loc')
                ->setFrom('test.testiranje5@gmail.com')
                ->setTo('test.testiranje5@gmail.com')
                ->setBody(
                    $this->renderView('@User/Default/email_register.html.twig',
                        array(
                            'username' => $user->getUsername(),
                            'fistName' => $user->getFirstname(),
                            'lastName' => $user->getLastname(),
                            'email' => $user->getEmail(),
                            'url' => $activateUrl
                        )
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            // show success message and redirect to homepage
            $this->addFlash('success', 'User created, please check your email for activation link.');
            return $this->redirectToRoute('homepage');
        }

        // render form for user to input his data
        return $this->render('UserBundle:default:new.html.twig', array(
            'user' => $user,
            'form' => $form->createView()
        ));
    }

//    /**
//     * Creates a new Admin entity.
//     *
//     * @Route("/new/admin", name="admin_new")
//     * @Method({"GET", "POST"})
//     */
//    public function createAdmin(){
//        $admin = new User();
//        $admin->setUsername('Admin');
//        $admin->setFirstname('Alisa');
//        $admin->setLastname('Kopric');
//        $admin->setPassword('$2y$12$WvfMcaNblN4RSUu4ok/RSOHbZuLFy.Ysw5GRgqVOvGuwlYyfhKdcm');
//        $admin->setEmail('admin@test.com');
//        $admin->setRoles(array('ROLE_ADMIN'));
//
//        $em = $this->getDoctrine()->getManager();
//        $em->persist($admin);
//        $em->flush();
//
//        return true;
//    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/account", name="user_account")
     * @Method("GET")
     */
    public function showAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('UserBundle:User')->findOneBy(array(
            'id' => $this->get('security.token_storage')->getToken()->getUser()->getId()
        ));

        return $this->render('@User/Default/account.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/edit/{id}", name="user_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $id)
    {
        // if it is logged user or ROLE_ADMIN
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $this->get('security.token_storage')->getToken()->getUser()->getId() == $id){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('UserBundle:User')->findOneBy(array(
                'id' => $id
            ));

            $editForm = $this->createForm(UserType::class, $user);
            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $em = $this->getDoctrine()->getManager();

                // encode and set new password on that user, get new password from input field
                $plainPassword = $editForm['password']->getData();
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $plainPassword);

                $user->setPassword($encoded);

                // save all changes to database
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'User data changed.');
                return $this->redirectToRoute('user_edit', array('id' => $user->getId()));
            }
            return $this->render('UserBundle:default:edit.html.twig', array(
                'user' => $user,
                'roles' =>$user->getRoles(),
                'edit_form' => $editForm->createView(),
            ));
        }
        return $this->redirectToRoute('homepage');
    }

    /**
     * Change the Enabled status ( Enable or Disable certain user)
     *
     * @Route("/switch/{id}", name="user_switch_status")
     * @Method({"GET", "POST"})
     */
    public function switchStatusAction(Request $request, $id){
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('UserBundle:User')->findOneBy(array(
                'id' => $id
            ));



        }
    }

    /**
     * Displays a form to change forgotten password.
     *
     * @Route("/resetpassword/{token}", name="user_reset_password")
     * @Method({"GET", "POST"})
     */
    public function resetPasswordAction(Request $request, $token)
    {
        $resetForm = $this->createForm(ResetPasswordType::class);
        $resetForm->handleRequest($request);

        // verify if token entry exists in database
        $em = $this->getDoctrine()->getManager();
        $tokenEntry = $em->getRepository('UserBundle:PasswordResetToken')
            ->findOneBy(array('token' => $token));

        // if token does not exist display flash message with notification and redirect to homepage
        if(!$tokenEntry){
            $this->addFlash('error', 'Provided token is not valid');
            return $this->redirectToRoute('homepage');
        }

        if ($resetForm->isSubmitted() && $resetForm->isValid()) {
            // find user object based on user id from token
            $userObject = $em->getRepository('UserBundle:User')
                ->findOneBy(array('id' => $tokenEntry->getUserId()));

            // remove token from db
            $em->remove($tokenEntry);

            // encode and set new password on that user, get new password from input field
            $plainPassword = $resetForm['password']->getData();
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($userObject, $plainPassword);

            $userObject->setPassword($encoded);
            $em->persist($userObject);

            // save all changes to database
            $em->flush();

            return $this->redirectToRoute('login_form');
        }

        return $this->render('UserBundle:Default:reset_password.html.twig', array(
            'token' => $token,
            'resetForm' => $resetForm->createView()
        ));
    }


    /**
     * Deletes a User entity.
     *
     * @Route("/delete/{id}", name="user_delete")
     * @Method({"GET"})
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('UserBundle:User')->findOneBy(array(
            'id' => $id
        ));

        // if user is administrator
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $em->remove($user);
            $em->flush();
            return $this->redirectToRoute('user_index');
        }

        // if user
        if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->get('security.token_storage')->getToken()->getUser()->getId() == $id) {
            $session = $this->get('session');
            $em->remove($user);
            $em->flush();
            $request->getSession()->invalidate();
            $session->clear();
        }
        return $this->redirectToRoute('homepage');
    }
}
    /**
     * Finds and displays a Customer entity.
     *
     * @Route("/account", name="customer_account")
     * @Method({"GET", "POST"})
     */
//    public function accountAction(Request $request){

//        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
//            throw $this->createAccessDeniedException();
//        }
//
//        if ($customer = $this->getUser()) {
//
//            $editForm = $this->createForm('\CustomerBundle\Form\CustomerType', $customer, array( 'action' => $this->generateUrl('customer_account')));
//            $editForm->handleRequest($request);
//
//            if ($editForm->isSubmitted() && $editForm->isValid()) {
//                $em = $this->getDoctrine()->getManager();
//                $em->persist($customer);
//                $em->flush();
//
//                $this->addFlash('success', 'Account updated.');
//                return $this->redirectToRoute('customer_account');
//            }
//
//            return $this->render('UserBundle:Default:account.html.twig', array(
//                'customer' => $customer,
//                'form' => $editForm->createView(),
//                'customer_orders' => $this->get('_customer.customer_orders')->getOrders()
//            ));
//        } else {
//            $this->addFlash('notice', 'Only logged in customers can access account page.');
//            return $this->redirectToRoute('_customer_login');
//        }
//    }
//}

