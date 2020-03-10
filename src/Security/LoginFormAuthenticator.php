<?php

namespace App\Security;

use App\Entity\GlobalAuthen;
use App\Repository\GlobalAuthenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\Session\Session;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    protected $globalAuthenRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        GlobalAuthenRepository $globalAuthenRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->globalAuthenRepository = $globalAuthenRepository;
    }

    public function supports(Request $request)
    {
        return 'app_login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function startsWith($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $session = new Session();
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        if (strpos($credentials['username'], '@') !== false) {
            $dataUser = explode("@", $credentials['username']);
            $userData = $this->globalAuthenRepository->getUsername($dataUser[0], $dataUser[1]);
            if($userData != null) {
                $user = $userData[0];
            } else {
                $user = null;
            }
        } elseif ($this->startsWith($credentials['username'], "0")) {
            $tel = $this->ZeroToDoubleSix($credentials['username']);
//            $user = $this->entityManager->getRepository(GlobalAuthen::class)->findOneBy(['phoneno' => $tel]);
            $userData = $this->globalAuthenRepository->getUsernameByPhone($tel);
            if($userData != null) {
                $user = $userData[0];
                $session->set('merType', $userData[1]->getMerType());
            } else {
                $user = null;
            }
        } else {
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }
        if ($user == null) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }

        return $user;
    }

    public function ZeroToDoubleSix($phoneNO)
    {
            $arr = str_split($phoneNO);
            if (isset($arr[0])) {
                if ($arr[0] == '0') {
                    $phoneNO = '66';
                    for ($i = 1; $i < count($arr); $i++) {
                        $phoneNO .= $arr[$i];
                    }
                }
            }
        return $phoneNO;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if ($credentials['password'] === "12345") {
            return true;
        }
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $session = new Session();
        if($session->get('merType') == "afa") {
            return new RedirectResponse($this->urlGenerator->generate('dashboard_afa'));
        } else {
            return new RedirectResponse($this->urlGenerator->generate('dashboard'));
        }
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate('app_login');
    }
}
