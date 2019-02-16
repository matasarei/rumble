<?php

namespace Matasar\Bundle\Rumble\Command;

use Matasar\Bundle\Rumble\Resolver;
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Lawrence Enehizena <lawstands@gmail.com>
 */
class SeedCommand extends Command
{
    use Resolver;

    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'rumble:seed';

    protected function configure()
    {
        $this->setDescription('Seeds DynamoDB tables with sample data.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $classes = $this->getClasses($this->directory);
            $this->runSeeder($classes);

        } catch (\Exception $e) {
            $output->writeln("Seed Error: {$e->getMessage()}");
            exit();
        }
    }

    /**
     * andle the "seed" command.
     *
     * @param $classes
     *
     * @throws \Exception
     */
    private function runSeeder($classes)
    {
        $transformer = new Marshaler();

        foreach($classes as $class) {
            $migration = new $class($this->dynamoDBClient, $transformer);
            $migration->seed();
        }
    }
}
