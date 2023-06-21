<?php

namespace Trollfjord\Bundle\PublicUserBundle\ControllerPublic;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\PublicUserBundle\Form\ChangePassword;
use Trollfjord\Bundle\PublicUserBundle\Form\NewPassword;
use Trollfjord\Bundle\PublicUserBundle\Form\ResetPassword;
use Trollfjord\Core\Controller\AbstractPublicController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormError;

/**
 * Class ShowController
 * @package Trollfjord\Bundle\PublicUserBundle\Controller
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @Route("/PublicUser", name="public_user_public_")
 */
class IndexController extends AbstractPublicController
{

    /**
     * @Route("/",
     *     name="home"
     * )
     *
     * @return Response
     */
    public function indexAction(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Retrive the last email entered by the user
        //$lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@PublicUser/public/login.html.twig', [
            //'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/Success",
     *     name="success"
     * )
     *
     * @return Response
     */
    public function successAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PUBLIC');
        $user = $this->getUser();
        return $this->render('@PublicUser/public/success.html.twig', [
            "user" => $user
        ]);
    }

    /**
     * @Route("/logout",
     *     name="logout"
     * )
     *
     * @return Response
     */
    public function logoutAction(): Response
    {
        // controller can be blank: it will never be executed!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    /**
     * @Route("/reset", name="reset")
     * @param Request $request
     * @return Response
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function reset(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPassword::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /** @var User $user */
            $user = $em->getRepository(User::class)->loadUserByEmail($form->getData()['email']);
            if (\is_object($user)) {
                // create token expiration date
                $hashCreatedAt = \date_create(\date('Y-m-d H:i:s'));
                $hashExpDate = $hashCreatedAt->modify('+ 1 day');
                $user->setHashExpirationDate($hashExpDate);

                // create pw reset token
                $hash = \md5($user->getEmail() . time(). $user->getId() . time() );
                $user->setResetPasswordHash($hash);

                $em->persist($user);
                $em->flush();

                $email = (new TemplatedEmail())
                    ->subject('Schultransform.de - Setzen Sie Ihr Passwort zurück')
                    ->from(new Address('support@schultransform', 'Schultransform'))
                    ->to($form->getData()['email'])
                    ->htmlTemplate('@PublicUser/emails/reset_password.html.twig')
                    ->context(
                        [
                            'name' => $user->getDisplayName(),
                            'user' => $user,
                            'link' => $this->generateUrl('public_user_public_login_token', [
                                'token' => $hash,
                            ], UrlGeneratorInterface::ABSOLUTE_URL)
                        ]
                    );

                $mailer->send($email);
            }

            $this->addFlash('success','Eine Mail mit dem Aktivierungslink für ein neues Passwort wurde an Sie verschickt.');


            return $this->redirectToRoute('user_login');
        }
        return $this->render('@PublicUser/public/reset.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/login/{token}", name="login_token")
     * @param string                       $token
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     * @throws NonUniqueResultException
     */
    public function createNewPassword(string $token, Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findUserByToken($token);

        $form = $this->createForm(NewPassword::class, $user);
        $form->handleRequest($request);

        $today = \date_create(\date('Y-m-d'));

        // Check if hash is expired & if it belongs to an user
        if (\is_object($user) && ! \is_null($user->getHashExpirationDate()) && $today <= $user->getHashExpirationDate()) {
            if ($form->isSubmitted() && $form->isValid()) {
                //save new password
                $encoded = $encoder->encodePassword($user, $user->getPassword());
                $user->setPassword($encoded);
                $user->setResetPasswordHash(null);
                $user->setHashExpirationDate(null);

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Ihr neues Passwort wurde gespeichert. Sie können sich jetzt damit einloggen.');

                return $this->redirectToRoute('user_login');
            }

            return $this->render('@PublicUser/public/create_password.html.twig', [
                'form' => $form->createView()
            ]);
        }

        $this->addFlash('error','Zeit zum Passwort zurücksetzen überschritten oder falschen Link in Adressleiste eingegeben. Überprüfen sie den Link oder lassen Sie einen neuen generieren.');

        return $this->redirectToRoute('user_login');
    }

    /**
     * @Route("/change/", name="pwchange")
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     * @throws NonUniqueResultException
     */
    public function ChangePassword(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();


        $form = $this->createForm(ChangePassword::class, $user);
        $form->handleRequest($request);

        $today = \date_create(\date('Y-m-d'));
        // Check if user is logged in
        if ($user) {
            if ($form->isSubmitted() && $form->isValid()) {
                $oldpassword = $form->get('old')->getData();
                $new = $form->get('password')->getData();

                if($encoder->isPasswordValid($user, $oldpassword)){
                    $encoded = $encoder->encodePassword($user, $new);
                    $user->setPassword($encoded);
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('success', 'Ihr neues Passwort wurde erfolgreich gespeichert.');
                    return $this->redirectToRoute('user_login');
                }else{
                    $form->get('old')->addError(new FormError('Das altes Passwort ist nicht korrekt'));
                }
            }


            return $this->render('@PublicUser/public/change_password.html.twig', [
                'form' => $form->createView()
            ]);
        }

        return $this->redirectToRoute('user_login');
    }

}
