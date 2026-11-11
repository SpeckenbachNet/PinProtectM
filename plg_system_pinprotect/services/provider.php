<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use SpeckenbachNet\Plugin\System\PinProtect\Extension\PinProtect;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        // Register the namespace explicitly; the dynamic namespace map is not reliable on every host
        $composerLoader = require JPATH_LIBRARIES . '/vendor/autoload.php';
        $composerLoader->addPsr4('SpeckenbachNet\\Plugin\\System\\PinProtect\\', __DIR__ . '/../src/', true);

        $container->set(
            PluginInterface::class,
            $container->lazy(PinProtect::class, function (Container $container) {
                $plugin = new PinProtect((array) PluginHelper::getPlugin('system', 'pinprotect'));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
