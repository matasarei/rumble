<?php

namespace Matasar\Bundle\Rumble\Command;

use Aws\DynamoDb\Marshaler;
use Matasar\Bundle\Rumble\Resolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Lawrence Enehizena <lawstands@gmail.com>
 */
class MigrateCommand extends Command
{
    use Resolver;

    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'rumble:migrate';

    protected function configure()
    {
        $this->setDescription('Creates and updates DynamoDB tables.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $classes = $this->getClasses($this->directory);
            $this->runMigration($classes, $output);

        } catch(\Exception $e) {
            $output->writeln("Migration Error: {$e->getMessage()}");

            exit();
        }
    }

    /**
     * Handle the "migrate" command.
     *
     * @param array $classes
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    private function runMigration(array $classes, OutputInterface $output)
    {
        if (!$this->isMigrationsTableExist()) {
            $this->createMigrationTable();
        }

        $ranMigrations = $this->getRanMigrations();
        $pendingMigrations = $this->getPendingMigrations($classes, $ranMigrations);

        if (count($pendingMigrations) == 0) {
            $output->writeln("Nothing new to migrate");

            return;
        }

        foreach ($pendingMigrations as $pendingMigration) {
            $migration = new $pendingMigration($this->dynamoDBClient);
            $migration->up();
            $this->addToRanMigrations($pendingMigration);
        }
    }

    /**
     * @param array $classes
     * @param array $ranMigrations
     *
     * @return mixed
     */
    private function getPendingMigrations(array $classes, array $ranMigrations)
    {
        foreach ($ranMigrations as $ranMigration) {
            $key = array_search($ranMigration, $classes);

            if ($key !== FALSE) {
                unset($classes[$key]);
            }
        }

        return $classes;
    }

    /**
     * @return array
     */
    private function getRanMigrations()
    {
        $result =  $this->dynamoDBClient->scan([
            'TableName' => 'migrations'
        ]);

        $marsh = new Marshaler();
        $ranMigrations = [];

        foreach ($result['Items'] as $item) {
            $ranMigrations[] = $marsh->unmarshalItem($item)['migration'];
        }

        return $ranMigrations;
    }

    /**
     * @return bool
     */
    private function isMigrationsTableExist()
    {
        $tables = $this->dynamoDBClient->listTables();

        return in_array('migrations', $tables['TableNames']);
    }

    private function createMigrationTable()
    {
        $this->dynamoDBClient->createTable([
            'TableName' => 'migrations',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'migration',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'migration',
                    'KeyType'       => 'HASH'
                ]
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits'  => 5,
                'WriteCapacityUnits' => 5
            ]
        ]);
    }

    /**
     * @param string $migration
     */
    private function addToRanMigrations($migration)
    {
        $this->dynamoDBClient->putItem([
            'TableName' => 'migrations',
            'Item' => [
                'migration' => ['S' => $migration]
            ]
        ]);
    }
}
