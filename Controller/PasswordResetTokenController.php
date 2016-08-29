<?php

namespace UserBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Email;
use UserBundle\Entity\PasswordResetToken;
use UserBundle\Form\EmailResetPasswordType;
use UserBundle\Service\MessageCreator;
use UserBundle\Service\TokenGenerator;

/**
 * PasswordResetToken controller.
 *
 * @Route("/passwordreset")
 */
class PasswordResetTokenController extends Controller
{

/**
 * Creates a new PasswordResetToken entity.
 *
 * @Route("/", name="forgotpassword")
 * @Method({"GET", "POST"})
 */
    public function forgotPasswordAction(Request $request)
    {
        $form = $this->createForm(EmailResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // check the if the user exists in the User database
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('UserBundle:User')
                ->findOneBy(array('email' => $form->getData()));

            // if user is not registered redirect back to input email form
            if (!$user){
                $this->addFlash('error', 'Provided email not registered');
                return $this->redirectToRoute('forgotpassword');
            }

            // when User existance is verified then generate new token
            $token = new TokenGenerator();
            $passwordResetToken = new PasswordResetToken();
            $passwordResetToken->setToken($token->generateToken());

            // link user id with token
            $passwordResetToken->setUserId($user->getId());

            // save token object to database
            $em->persist($passwordResetToken);
            $em->flush();

            // generate reset link with token
            $resetUrl = $this->get('router')->generate('user_reset_password',  array('token'=>$passwordResetToken->getToken()), 0);

            //generate email message and send it
            $message = \Swift_Message::newInstance(null)
                ->setSubject('Password reset request from URLShortener.loc')
                ->setFrom('test.testiranje5@gmail.com')
                ->setTo('test.testiranje5@gmail.com')
                ->setBody(
                    $this->renderView('@User/Default/email_password_reset.html.twig',
                        array(
                            'url' => $resetUrl
                        )
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            // notify user to check email with flash message and redirect to homepage
            $this->addFlash('success', 'Please check your email for reset password link.');
            return $this->redirectToRoute('homepage');
        }

        // display form to input email for password reset
        return $this->render('UserBundle:Default:forgot_password.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
