<?php

namespace Trollfjord\Bundle\PublicUserBundle\Security;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $url_params = "";

    /**
     * @var string
     */
    private string $loginRoute;

    /**
     * @var string
     */
    private string $loginSuccessRoute;

    public function __construct(
        EntityManagerInterface       $entityManager,
        RouterInterface              $router, CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        Session                      $session,
        string                       $loginRoute,
        string                       $loginSuccessRoute
    ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->session = $session;

        $this->loginRoute = $loginRoute;
        $this->loginSuccessRoute = $loginSuccessRoute;
    }

    public function supports(Request $request)
    {
        return $this->loginRoute === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $this->url_params = $request->request->get('public_user_url_params');

        if ($request->request->get('code')) {
            $credentials = [
                'code' => strtoupper($request->request->get('code')),
                'csrf_token' => $request->request->get('_csrf_token'),
            ];

            $request->getSession()->set(
                Security::LAST_USERNAME,
                $credentials['code']
            );
        } else {
            $credentials = [
                'username' => $request->request->get('username'),
                'password' => $request->request->get('password'),
                'csrf_token' => $request->request->get('_csrf_token'),
            ];

            $request->getSession()->set(
                Security::LAST_USERNAME,
                $credentials['username']
            );
        }

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): User
    {

        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (! $this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = null;
        if (isset($credentials['code'])) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['code' => $credentials['code']]);
        }
        if (isset($credentials['username'])) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);
            if ($user && ! $this->passwordEncoder->isPasswordValid($user, $credentials["password"])) {
                throw new CustomUserMessageAuthenticationException('User or password are wrong');
            }
        }

        if (! $user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Unbekannter Zugangscode');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        if (isset($credentials['code'])) {
            return ($user->getPassword() == $credentials['code']);
        } elseif (isset($credentials["password"])) {
            return $this->passwordEncoder->isPasswordValid($user, $credentials["password"]);
        }
        return false;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        $route = $this->session->get('route_after_login');
        $this->session->remove('route_after_login');

        if ($route && $route != "") {
            $response = new RedirectResponse($this->router->generate($route));
        } else {
            $response = new RedirectResponse($this->router->generate($this->loginSuccessRoute));
        }

        // Wenn Lehrer: Code im Cookie speichern
        if ($request->request->getBoolean('save_code')) {
            if ($token->getUser()->hasRole("ROLE_TEACHER")) {
                $date = new DateTime();
                $date->modify("+6 months");
                $response->headers->setCookie(new Cookie('ST_CODE', $token->getUser()->getCode(), $date));
            }
        } else {
            $response->headers->clearCookie('ST_CODE');
            $response->send();
        }

        return $response;
    }

    protected function getLoginUrl()
    {
        $arr = (array)json_decode($this->url_params);
        if (count($arr) > 0) {
            return $this->router->generate($this->loginRoute, $arr);
        }
        return $this->router->generate($this->loginRoute);
    }
}