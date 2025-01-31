<?php

namespace App\Modules\Core\Console\Installers\Scripts;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;
use App\Modules\Core\Console\Installers\SetupScript;
use App\Modules\Core\Console\Installers\Writers\EnvFileWriter;

class ConfigureAppUrl implements SetupScript
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EnvFileWriter
     */
    protected $env;

    /**
     * @param Config        $config
     * @param EnvFileWriter $env
     */
    public function __construct(Config $config, EnvFileWriter $env)
    {
        $this->config = $config;
        $this->env = $env;
    }

    /**
     * @var Command
     */
    protected $command;

    /**
     * Fire the install script
     * @param  Command $command
     * @return void
     */
    public function fire(Command $command)
    {
        $this->command = $command;

        $vars = [];

        $vars['app_url'] = $this->askAppUrl();

        $this->setLaravelConfiguration($vars);

        $this->env->write($vars);

        if ($command->option('verbose'))
        {
            $command->info('Application URL successfully configured');
        }
    }

    /**
     * Ensure that the APP_URL is valid
     *
     * e.g. http://localhost, http://192.168.0.10, https://www.example.com etc.
     *
     * @return string
     */
    protected function askAppUrl()
    {
        do {
            $str = $this->command->ask('Enter you application url (e.g. http://localhost, http://dev.example.com)', 'http://localhost');

            if ($str == '' || (strpos($str, 'http://') !== 0 && strpos($str, 'https://') !== 0))
            {
                $this->command->error('A valid http:// or https:// url is required');

                $str = false;
            }
        } while (!$str);

        return $str;
    }

    /**
     * @param array<string,mixed> $vars
     * @return void
     */
    protected function setLaravelConfiguration($vars)
    {
        $this->config['app.url'] = $vars['app_url'];
    }
}
