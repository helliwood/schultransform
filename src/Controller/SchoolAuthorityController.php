<?php

namespace Trollfjord\Controller;

use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\PublicUserBundle\Service\GenerateUserService;
use Trollfjord\Core\Controller\AbstractController;
use Trollfjord\Entity\SchoolAuthority;
use Trollfjord\Entity\SchoolType;
use Trollfjord\Form\AccountType;
use Trollfjord\Form\DsgvoType;
use Trollfjord\Form\SchoolAuthorityType;
use Trollfjord\Service\UserService;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function array_merge;
use function is_array;
use function is_null;
use function is_numeric;
use function md5;
use function method_exists;
use function time;
use function ucfirst;

/**
 * Class QuestionnaireController
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @method User getUser
 * @Route("/school-authority", name="school_authority_")
 */
class SchoolAuthorityController extends AbstractController
{
    /**
     * @Route("/register/{step}", defaults={"step":1}, name="register")
     *
     * @throws Exception|TransportExceptionInterface
     */
    public function register(
        Request                      $request,
        SessionInterface             $session,
        UserPasswordEncoderInterface $encoder,
        GenerateUserService          $generateUserService,
        MailerInterface              $mailer,
        UserService                  $userService,
        int                          $step = 1
    ): Response {
        $form = null;
        $schoolAuthorityId = $session->get(self::class . '_school-authority');
        if (is_numeric($schoolAuthorityId)) {
            $usedSchoolAuthority = $this->getDoctrine()->getRepository(SchoolAuthority::class)->find($schoolAuthorityId);
        } else {
            $usedSchoolAuthority = null;
        }
        $viewData = [];

        if ($step == 2) {
            $schoolAuthority = null;
            if ($session->has(self::class . 'step_2')) {
                $schoolAuthority = $session->get(self::class . 'step_2', null);
            }

            if ($request->request->has('back')) {
                return $this->redirectToRoute("school_authority_register", ["step" => 1]);
            }

            if (is_null($schoolAuthority) && is_numeric($schoolAuthorityId)) {
                $usedSchoolAuthority = $this->getDoctrine()->getRepository(SchoolAuthority::class)->find($schoolAuthorityId);
                $form = $this->createForm(SchoolAuthorityType::class, $usedSchoolAuthority);
            } else {
                $form = $this->createForm(SchoolAuthorityType::class, $schoolAuthority);
            }
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $session->set(self::class . 'step_2', $form->getData());
                return $this->redirectToRoute("school_authority_register", ["step" => 3]);
            }
        } elseif ($step == 3) {
            /** @var User $user */
            $user = clone $session->get(self::class . 'step_3', new User());
            $form = $this->createForm(AccountType::class, $user, ['user' => $user]);
            $form->handleRequest($request);

            if ($request->request->has('back')) {
                return $this->redirectToRoute("school_authority_register", ["step" => 2]);
            }
            if ($form->isSubmitted() && $form->isValid()) {
                if ($user->getNewPassword()) {
                    $user->setPassword($encoder->encodePassword($user, $user->getNewPassword()));
                    $user->setNewPassword(null);
                }
                $session->set(self::class . 'step_3', $user);
                return $this->redirectToRoute("school_authority_register", ["step" => 4]);
            }
        } elseif ($step == 4) {
            $form = $this->createForm(DsgvoType::class);
            $form->handleRequest($request);
            if ($request->request->has('back')) {
                return $this->redirectToRoute("school_authority_register", ["step" => 3]);
            }
            /** @var User $user */
            $user = $session->get(self::class . 'step_3', null);
            /** @var SchoolAuthority $schoolAuthority */
            $schoolAuthority = $session->get(self::class . 'step_2', null);
            $viewData['user'] = $user;
            $viewData['schoolAuthority'] = $schoolAuthority;

            if ($form->isSubmitted() && $form->isValid()) {
                if (! is_null($usedSchoolAuthority)) {
                    // Da in Doctrine "merge" deprecated ist, übertrage ich die Daten händisch
                    $data = $schoolAuthority->toArray(2);
                    $setData = function (&$entity, $data) use (&$setData) {
                        foreach ($data as $field => $value) {
                            if (is_array($value) && method_exists($entity, 'get' . ucfirst($field))) {
                                $subEntity = $entity->{'get' . ucfirst($field)}();
                                $setData($subEntity, $value);
                            } elseif (method_exists($entity, 'set' . ucfirst($field))) {
                                $entity->{'set' . ucfirst($field)}($value);
                            }
                        }
                    };
                    $setData($usedSchoolAuthority, $data);
                    $schoolAuthority = $usedSchoolAuthority;
                } else {
                    $this->getDoctrine()->getManager()->persist($schoolAuthority);
                }
                $schoolAuthority->setCode($generateUserService->getNewSchoolAuthorityCode());
                $schoolType = $this->getDoctrine()->getManager()->find(SchoolType::class, "weiterführende Schule");
                $user->setRoles(["ROLE_SCHOOL_AUTHORITY_LITE"]);
                $user->setHash(md5(time()));
                $user->setSchoolAuthority($schoolAuthority);
                $user->setSchoolType($schoolType);
                //save the registration date
                $registrationDate = new DateTime;
                $user->setRegistrationDate($registrationDate);
                $this->getDoctrine()->getManager()->persist($user);
                $this->getDoctrine()->getManager()->flush();
                $session->clear();

                $viewData['link'] = $this->generateUrl('school_authority_confirmation', ['hash' => $user->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);
                $email = (new TemplatedEmail())
                    ->subject('Registrierung auf schultransform.org')
                    ->from(new Address('support@schultransform.org', 'Schultransform'))
                    ->to($user->getEmail())
                    ->htmlTemplate('mail/register_school_authority.html.twig')
                    ->context(
                        ['data' => $viewData]
                    )->attach($userService->getSchoolAuthorityFaxDompdf($user)->output(), "Schultraegerfax.pdf");

                $mailer->send($email);

                return $this->redirectToRoute("school_authority_finish", ['code' => $schoolAuthority->getCode()]);
            }
        }
        if (! is_null($form)) {
            $viewData['form'] = $form->createView();
        }
        return $this->render('frontend/school-authority/register.html.twig', array_merge($viewData, [
            'usedSchoolAuthority' => $usedSchoolAuthority,
            'step' => $step
        ]));
    }

    /**
     * @Route("/finish/{code}", name="finish")
     * @param string $code
     * @return Response
     */
    public function finish(string $code): Response
    {
        return $this->render('frontend/school-authority/finish.html.twig', ['code' => $code]);
    }

    /**
     * @Route("/confirmation/{hash}", name="confirmation")
     * @param string $hash
     * @return Response
     */
    public function confirmation(string $hash): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(["hash" => $hash]);
        if ($user instanceof User) {
            $user->setHash(null);
            $user->setUsername($user->getEmail());
            $user->setRoles(["ROLE_SCHOOL_AUTHORITY_LITE"]);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush($user);

            return $this->render('frontend/school-authority/confirmation.html.twig', [
                'user' => $user
            ]);
        } else {
            return $this->render('frontend/school-authority/confirmation.html.twig', [
                'user' => false
            ]);
        }

    }

    /**
     * @Route("/fax", name="fax")
     * @IsGranted("ROLE_SCHOOL_AUTHORITY_LITE")
     * @param UserService $userService
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function fax(UserService $userService): void
    {
        $dompdf = $userService->getSchoolAuthorityFaxDompdf();
        $dompdf->stream("Bestätigung des Schultraegers.pdf");
    }

    /**
     * @Route("/search", name="search")
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $data = $this->getDoctrine()
            ->getRepository(SchoolAuthority::class)
            ->findByPostalcode($request->query->get('search'));
        return new JsonResponse($data);
    }

    /**
     * @Route("/set-session", name="set_session")
     * @param SessionInterface $session
     * @param Request          $request
     * @return JsonResponse
     */
    public function setSession(SessionInterface $session, Request $request): JsonResponse
    {
        if (is_numeric($request->query->get('school-authority-id'))) {
            $session->set(self::class . '_school-authority', $request->query->get('school-authority-id'));
            $prevData = $session->get(self::class . 'step_2', null);
            if (! is_null($prevData) && $prevData->getId() != $request->query->get('school-authority-id')) {
                $session->remove(self::class . 'step_2');
            }
        } else {
            $session->remove(self::class . '_school-authority');
        }
        return new JsonResponse($request->query->get('school-authority-id'));
    }

}
