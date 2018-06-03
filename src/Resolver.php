<?php

namespace Matasar\Bundle\Rumble;

use Aws\DynamoDb\DynamoDbClient;

/**
 * @author Lawrence Enehizena <lawstands@gmail.com>
 * @author Yevhen Matasar <matasar.ei@gmail.com>
 */
trait Resolver
{
    /** @var DynamoDbClient */
    protected $dynamoDBClient;

    /** @var string */
    protected $directory;

    /**
     * @param string $directory
     * @param string $endpoint
     * @param string $region
     * @param string $version
     * @param string $key
     * @param string $secret
     */
    public function __construct($directory, $endpoint, $region, $version, $key = null, $secret = null)
    {
        $dbClientParams = [
            'region' => $region,
            'version' => $version,
            'endpoint' => $endpoint
        ];

        if (!empty($key) && !empty($secret)) {
            $dbClientParams['credentials'] = [
                'key' => $key,
                'secret' => $secret
            ];
        }

        $this->directory = $directory;
        $this->dynamoDBClient = new DynamoDbClient($dbClientParams);

        parent::__construct();
    }

    /**
     * Get class names from files in migrations/seeds directory.
     * For any class found require it, so we can create an instance.
     *
     * @param $dir
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getClasses($dir)
    {
        if (!file_exists($dir)) {
            throw new \Exception("{$dir} directory not found.");
        }

        $dirHandler  = opendir($dir);
        $classes = [];
        while (false != ($file = readdir($dirHandler))) {
            if ($file != '.' && $file != '..') {
                require_once($dir . '/' . $file);
                $classes[] = $this->buildClass($file);;
            }
        }
        closedir($dirHandler);

        if (count($classes) == 0) {
            throw new \Exception("There are no files in {$dir} to run.");
        }

        return $classes;
    }

    /**
     * Build class names from file name. This uses an underscore (_) convention.
     * Each file in eigther the migrations or seeds folder, uses an underscore naming
     * convention. eg: create_me_table => CreateMeTable (ClassName)
     *
     * @param $file
     *
     * @return mixed
     */
    protected function buildClass($file)
    {
        $file = basename($file, '.php');
        $fileNameParts = explode('_', $file);

        foreach ($fileNameParts as &$part) {
            $part = ucfirst($part);
        }

        return implode('', $fileNameParts);
    }
}
