# Rumble

A DynamoDB migration bundle for Symfony 4 based on original Rumble lib.

### Class definition
- **Migration:** Every migration file (class) you create must extend the rumble ```Migration``` class and must define an ```up``` method.
- **Seed:** Every seed file (class) you create must extend the rumble ```Seeder``` class and must define a ```seed``` method.

### Using Rumble
- **Migration:** to apply migrations, run ```./bin/console rumble:migrate```
- **Seed:** to execute seed files, run ```./bin/console rumble:seed```

### Supported DynamoDB features
Currently, ```rumble``` supports only the below dynamodb features:
- Create table
- Update table
- Delete table
- Add Item
- Batch Write Item

### Installation

1. Add bundle to your project:
    ```composer require matasarei/rumble```
2. Add config file: 
    ```config/packages/rumble.yaml```
    
    With default content:
    ```yaml
     rumble:
         migrations_dir: 'migrations' # <project_root>/migrations/... (optional)
         seeds_dir: 'seeds' # <project_root>/seeds/... (optional)
         version: '2012-08-10' # (default, optional)
         region: 'dev'
         key: 'dev'
         secret: 'dev'
         endpoint: 'http://dynamodb:8000'
    ```
    You can also override values by adding additional configurations to:
    * `config/packages/dev/rumble.yaml` for `dev` environment;
    * `config/packages/test/rumble.yaml` for `test` \ `qa` environment;
    * `config/packages/prod/rumble.yaml` for `prod` environment.

### Create a new table
```php
<?php
// migrations/CreateAppRecordsTable.php

use Matasar\Bundle\Rumble\Migration;

class CreateAppRecordsTable extends Migration
{
    public function up()
    {
        $table = $this->table('test_table'); // table name.
        $table->addAttribue('test_field', 'S'); // primary key data type - String (S)
        $table->addHash('test_field');
        $table->setWCU(1); // Write Capacity Unit (Provisioned write throughput)
        $table->setRCU(1); // Read Capacity Unit (Provisioned read throughput)
        $table->create();
    }
}
```
You change write \ read capacity later in table settings \ DynamoDB console or setup auto scaling.

### Seed table
```php
<?php
// seeds/CreateAppRecordsTable.php

use Matasar\Bundle\Rumble\Seeder;

class AppRecordsTableSeeder extends Seeder 
{
    public function seed()
    {
        $table = $this->table('test_table');
        $table->addItem(['test_field' => 'First record']);
        $table->addItem(['test_field' => 'Second record']); 
        $table->save();
    }
}
