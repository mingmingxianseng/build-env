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
use Composer\Json\JsonFile;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

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

    /**
     * getComposerJson
     *
     * @author chenmingming
     *
     * @param null $key
     *
     * @return mixed|null
     */
    private function getComposerJson($key = null)
    {
        static $json;

        if (null === $json) {
            // force reloading scripts as we might have added and removed during this run
            $jsonFile = new JsonFile(trim(getenv('COMPOSER')) ?: './composer.json');
            $json     = $jsonFile->read();
        }
        if (null === $key) {
            return $json;
        }

        return $json[$key] ?? null;
    }

    /**
     * buildNginx
     *
     * @author chenmingming
     *
     * @param Event $event
     */
    public function buildNginx(Event $event)
    {
        $event->stopPropagation();

        $env = trim(getenv('CI_APP_ENV'));
        if (empty($env)) {
            $this->io->write("no app_env jumped build nginx env");

            return;
        }

        $config = $this->getComposerJson()['scripts']['build-nginx-env'];

        $file = $config['file'] ?: trim(getenv('CI_NGINX_ENV_FILE'));
        if (empty($file)) {
            throw new \InvalidArgumentException("file must be input");
        }
        $prefix  = 'CI_' . strtoupper($env) . '_';
        $content = "fastcgi_param APP_ENV {$env}" . PHP_EOL;
        foreach ($this->getEnvByPrefix($prefix) as $k => $v) {
            $content .= "fastcgi_param {$k} {$v};" . PHP_EOL;
        }
        $rs = file_put_contents($file, $content);

        if ($rs === false) {
            throw new \RuntimeException(sprintf("%s write failed", $file));
        }
        $this->io->write('[ ok ] build nginx env');
    }

    /**
     * getEnvByPrefix
     *
     * @author chenmingming
     *
     * @param $prefix
     *
     * @return \Generator
     */
    private function getEnvByPrefix($prefix)
    {
        $prefixLen = strlen($prefix);
        foreach (getenv() as $k => $v) {
            if (substr($k, 0, $prefixLen) === $prefix) {
                $key = substr($k, $prefixLen);
                yield $key => $v;
            }
        }
    }

    public function buildShellEnv(Event $event)
    {
        $event->stopPropagation();

        $env = trim(getenv('CI_APP_ENV'));
        if (empty($env)) {
            $this->io->write("no app_env jumped build nginx env");

            return;
        }

        $config = $this->getComposerJson()['scripts']['build-shell-env'];

        $file = $config['file'] ?: trim(getenv('CI_SHELL_ENV_FILE'));
        if (empty($file)) {
            throw new \InvalidArgumentException("file must be input");
        }
        $prefix  = 'CI_' . strtoupper($env) . '_';
        $content = "export APP_ENV \"{$env}\"" . PHP_EOL;
        foreach ($this->getEnvByPrefix($prefix) as $k => $v) {
            $content .= "export {$k}=\"{$v}\";" . PHP_EOL;
        }
        $rs = file_put_contents($file, $content);

        if ($rs === false) {
            throw new \RuntimeException(sprintf("%s write failed", $file));
        }
        $this->io->write('[ ok ] build shell env');
    }

    public static function getSubscribedEvents()
    {
        return array(
            'build-nginx-env' => 'buildNginx',
            'build-shell-env' => 'buildShellEnv',
        );
    }

}