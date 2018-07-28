<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/7/28
 * Time: 10:16
 */

namespace Ming\Component\BuildEnv;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Build implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    public function buildNginx()
    {
        $this->io->write(__METHOD__);
    }

    public function buildShellEnv()
    {
        $this->io->write(__METHOD__);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'build-nginx-env' => 'buildNginx',
            'build-shell-env' => 'buildShellEnv',
        );
    }

}