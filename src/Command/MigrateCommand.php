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
     * @var string
     */
    const TABLE_DEFAULT = 'migrations';

    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'rumble:migrate';

    /**
     * Migrations table.
     *
     * @var string
     */
    private $tableName;

    /**
     * Use one migrations table for sevaral apps.
     *
     * @var bool
     */
    private $multiAppMode;

    /**
     * Current App uniq name.
     *
     * @var string
     */
    private $appName;

    /**
     * MigrateCommand constructor.
     *
     * @param $directory
     * @param $endpoint
     * @param $region
     * @param $version
     * @param null $key
     * @param null $secret
     * @param string $tableName
     * @param bool $multiAppMode
     * @param string $appName
     */
    public function __construct(
        $directory,
        $endpoint,
        $region,
        $version,
        $key = null,
        $secret = null,
        $tableName = self::TABLE_DEFAULT,
        $multiAppMode = false,
        $appName = ''
    )
    {
        parent::__construct($directory, $endpoint, $region, $version, $key, $secret);

        $this->tableName = $tableName;
        $this->multiAppMode = $multiAppMode;
    }

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
        $params = ['TableName' => 'migrations'];

        if ($this->multiAppMode) {
            $params['FilterExpression'] = '#app_name = :app_name';
            $params['ExpressionAttributeNames'] = ['#app_name' => 'app_name'];
            $params['ExpressionAttributeValues'] = (new Marshaler())->marshalItem([':app_name' => $this->appName]);
        }

        $result =  $this->dynamoDBClient->scan($params);

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
        $attributes = [
            [
                'AttributeName' => 'migration',
                'AttributeType' => 'S'
            ]
        ];
        $keys = [
            [
                'AttributeName' => 'migration',
                'KeyType'       => 'HASH'
            ]
        ];

        if ($this->multiAppMode) {
            $attributes[] = [
                'AttributeName' => 'app_id',
                'AttributeType' => 'S'
            ];
            $keys[] = [
                'AttributeName' => 'app_id',
                'KeyType' => 'RANGE'
            ];
        }

        $this->dynamoDBClient->createTable([
            'TableName' => $this->tableName,
            'AttributeDefinitions' => $attributes,
            'KeySchema' => $keys,
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
        $item = [
            'migration' => ['S' => $migration]
        ];

        if ($this->multiAppMode) {
            $item['app_name'] = $this->appName;
        }

        $this->dynamoDBClient->putItem([
            'TableName' => 'migrations',
            'Item' => $item
        ]);
    }
}
