<?php

namespace InfyOm\Generator\Commands\Common;

use InfyOm\Generator\Commands\BaseCommand;
use InfyOm\Generator\Generators\ServiceGenerator;

class ServiceGeneratorCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'infyom:service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create service command';

    public function handle()
    {
        parent::handle();

        /** @var ServiceGenerator $serviceGenerator */
        $serviceGenerator = app(ServiceGenerator::class);
        $serviceGenerator->generate();

        $this->performPostActions();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array_merge(parent::getOptions(), []);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array_merge(parent::getArguments(), []);
    }
}