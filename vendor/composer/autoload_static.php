<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite41308d82de4b5f9f60bd3beafdfc369
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'M' => 
        array (
            'Monolog\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Monolog\\' => 
        array (
            0 => __DIR__ . '/..' . '/monolog/monolog/src/Monolog',
        ),
    );

    public static $prefixesPsr0 = array (
        'H' => 
        array (
            'Httpful' => 
            array (
                0 => __DIR__ . '/..' . '/nategood/httpful/src',
            ),
        ),
    );

    public static $classMap = array (
        'RocketChat\\Channel' => __DIR__ . '/..' . '/esup-portail/rocket-chat-rest-client/src/RocketChatChannel.php',
        'RocketChat\\Client' => __DIR__ . '/..' . '/esup-portail/rocket-chat-rest-client/src/RocketChatClient.php',
        'RocketChat\\Group' => __DIR__ . '/..' . '/esup-portail/rocket-chat-rest-client/src/RocketChatGroup.php',
        'RocketChat\\Settings' => __DIR__ . '/..' . '/esup-portail/rocket-chat-rest-client/src/RocketChatSettings.php',
        'RocketChat\\User' => __DIR__ . '/..' . '/esup-portail/rocket-chat-rest-client/src/RocketChatUser.php',
        'RocketChat\\UserManager' => __DIR__ . '/..' . '/esup-portail/rocket-chat-rest-client/src/RocketChatUserManager.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite41308d82de4b5f9f60bd3beafdfc369::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite41308d82de4b5f9f60bd3beafdfc369::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInite41308d82de4b5f9f60bd3beafdfc369::$prefixesPsr0;
            $loader->classMap = ComposerStaticInite41308d82de4b5f9f60bd3beafdfc369::$classMap;

        }, null, ClassLoader::class);
    }
}
