<?php



namespace Composer\Autoload;

class ComposerStaticInit701e905f5ccf3d2d7b07acac81e0c5e1
{
    public static $prefixLengthsPsr4 = array (
        'S' =>
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' =>
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit701e905f5ccf3d2d7b07acac81e0c5e1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit701e905f5ccf3d2d7b07acac81e0c5e1::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit701e905f5ccf3d2d7b07acac81e0c5e1::$classMap;

        }, null, ClassLoader::class);
    }
}
