<?php

namespace InfyOm\Generator\Commands;

class APIScaffoldGeneratorCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'infyom:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full CRUD API and Scaffold for given model';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();
        $this->fireFileCreatingEvent('api_scaffold');

        $this->generateCommonItems();
        // api，固定模式
        $this->generateAPIItems();

        dump('开始');
        // web 自定义模式
        $this->generateScaffoldItems('web');
        // admin 自定义模式
        $this->generateScaffoldItems('admin');

        $this->performPostActionsWithMigration();
        $this->fireFileCreatedEvent('api_scaffold');
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
