<?php

namespace InfyOm\Generator\Commands;

class AllGeneratorCommand extends BaseCommand
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
    protected $description = 'Create a full CRUD for given model';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();
        $this->fireFileCreatingEvent('all');

        $this->generateCommonItems();

        // api，固定模式
        $this->generateAPIItems();

        // web 自定义模式
        $this->generateScaffoldItems('web');
        // admin 自定义模式
        $this->generateScaffoldItems('admin');

        $this->performPostActionsWithMigration();
        $this->fireFileCreatedEvent('all');
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
