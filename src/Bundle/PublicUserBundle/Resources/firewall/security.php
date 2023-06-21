<?php

use Symfony\Component\Yaml\Yaml;

if (isset($_security)) {
    /** @var Symfony\Component\DependencyInjection\Loader\PhpFileLoader $this */
    /** @var Symfony\Component\DependencyInjection\ContainerBuilder $container */

    $_config = [];
    $fileForOverload = $container->getParameter('kernel.project_dir') . '/config/packages/public_user.yaml';
    if (file_exists($fileForOverload)) {
        $_config = Yaml::parse(file_get_contents($fileForOverload));
    }
    $container->getExtension("public_user")->load($_config, $container);

    $_security['providers']['public_users_provider'] = [
        "entity" => [
            "class"    => Trollfjord\Bundle\PublicUserBundle\Entity\User::class,
            "property" => 'code'
        ]
    ];
    $_security['encoders'][Trollfjord\Bundle\PublicUserBundle\Entity\User::class] = 'auto';
    $_security["firewalls"]["public_user"] = [
        "provider"    => "public_users_provider",
        "anonymous"   => true,
        "form_login"  => $container->getParameter("public_user.security_form_login"),
        "logout"      => $container->getParameter("public_user.security_logout"),
        "remember_me" => [
            "secret"   => '%kernel.secret%',
            "lifetime" => 604800, // 1 week in seconds
            "path"     => "~"
        ],
        "guard"       => [
            'entry_point'    => Trollfjord\Bundle\PublicUserBundle\Security\LoginFormAuthenticator::class,
            "authenticators" => [
                Trollfjord\Bundle\PublicUserBundle\Security\LoginFormAuthenticator::class
            ]
        ],
    ];
    $_security["access_control"][] = [
        'path'  => $container->getParameter("public_user.login_route"),
        'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'
    ];

    $_security["role_hierarchy"] = [
        "ROLE_SCHOOL_LITE"           => ["ROLE_USER"],
        "ROLE_SCHOOL_AUTHORITY_LITE" => ["ROLE_USER"],
        "ROLE_SCHOOL"                => ["ROLE_SCHOOL_LITE"],
        "ROLE_SCHOOL_AUTHORITY"      => ["ROLE_SCHOOL_AUTHORITY_LITE"],
    ];

    $_security["access_control"][] = [
        'path'  => '^/PublicUser/Success',
        'roles' => 'ROLE_PUBLIC'
    ];
}