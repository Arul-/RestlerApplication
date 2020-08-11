<?php namespace Bootstrap\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Schema;
use DB;

class ModelMakeCommand extends GeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Execute the console command.
     *
     * @return void|false
     * @throws FileNotFoundException
     */
    public function handle()
    {
        if (parent::handle() === false && !$this->option('force')) {
            return false;
        }

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        }
    }

    protected function buildClass($name)
    {
        $replace = [];
        $replace = $this->buildReplacements($replace);
        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Build the model replacement values.
     *
     * @param array $replace
     * @return array
     */
    protected function buildReplacements(array $replace)
    {
        $table = $this->option('table') ?? str_plural(strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $this->argument('name'))));
        $fields = Schema::getColumnListing($table);

        $hide = ['password', 'secret'];
        $avoid = ['id', 'ID', 'verified', 'active'];

        $timestamps = 'true';
        $fillable = '';
        $hidden = '';
        $import = '';
        $use = '';

        $properties = '';
        if (!empty($fields)) {
            foreach ($fields as $property) {
                if ($isDate = ends_with($property, '_at')) {
                    $avoid[] = $property;
                }
                if ($is_id = ends_with($property, '_id')) {
                    $avoid[] = $property;
                }
                list($type, $subType) = $this->guessType($property);
                $pt = in_array($property, $hide)
                    ? '@property-write'
                    : (in_array($property, $avoid) || $isDate ? '@property-read ' : '@property      ');
                $properties .= "$pt $type \${$property}$subType\n * ";
            }

            $timestamps = in_array('created_at', $fields) ? 'true' : 'false';
            $fields = array_diff($fields, $avoid);
            $hide = array_intersect($fields, $hide);

            $fillable = "'" . implode("',\n        '", $fields) . "'";

            if (in_array('deleted_at', $fields)) {
                $import = "use Illuminate\\Database\\Eloquent\\SoftDeletingTrait;\n\n";
                $use = "use SoftDeletingTrait;\n\n    protected \$dates = ['deleted_at'];\n        ";
            }
        }

        if (!empty($hide)) {
            $hidden = "'" . implode("',\n        '", $hide) . "'";
        }
        return array_merge($replace, [
            '{{ import }}' => $import,
            '{{ properties }}' => $properties,
            '{{ use }}' => $use,
            '{{ table }}' => $table,
            '{{ timestamps }}' => $timestamps,
            '{{ fillable }}' => $fillable,
            '{{ hidden }}' => $hidden,
        ]);
    }

    /**
     * Format the command class stub.
     *
     * @param string $stub
     *
     * @return string
     */
    protected function formatStub($stub)
    {
        $className = $this->input->getArgument('name');
        $tableName = is_null($this->option('table'))
            ? str_plural(strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className)))
            : $this->option('table');
        $fields = Schema::getColumnListing($tableName);

        $hide = ['password', 'secret'];
        $avoid = ['id', 'ID', 'verified', 'active'];

        $timestamps = 'true';
        $fillable = '';
        $hidden = '';
        $import = '';
        $use = '';
        $dates = '';

        $properties = '';
        if (!empty($fields)) {
            foreach ($fields as $property) {
                if ($isDate = ends_with($property, '_at')) {
                    $avoid[] = $property;
                }
                if ($is_id = ends_with($property, '_id')) {
                    $avoid[] = $property;
                }
                //$type = DB::connection()->getDoctrineColumn($tableName, $property)->getType()->getName();
                list($type, $subType) = $this->guessType($property);
                $pt = in_array($property, $hide)
                    ? '@property-write' : (
                    in_array($property, $avoid) || $isDate ? '@property-read ' : '@property      '
                    );
                $properties .= "$pt $type \${$property}$subType\n * ";
            }

            $timestamps = in_array('created_at', $fields) ? 'true' : 'false';
            $fields = array_diff($fields, $avoid);
            $hide = array_intersect($fields, $hide);

            $fillable = "'" . implode("',\n        '", $fields) . "'";

            if (in_array('deleted_at', $fields)) {
                $import = "use Illuminate\\Database\\Eloquent\\SoftDeletingTrait;\n\n";
                $use = "use SoftDeletingTrait;\n\n    protected \$dates = ['deleted_at'];\n        ";
            }
        }

        if (!empty($hide)) {
            $hidden = "'" . implode("',\n        '", $hide) . "'";
        }

        $stub = str_replace(
            [
                'DummyNamespace',
                'class:name',
                'table:name',
                'table:timestamps',
                'table:fillable',
                'table:hidden',
                'softdelete:import',
                'softdelete:use',
                'comment:properties'
            ],
            [
                rtrim(getAppNamespace(), '\\'),
                $className,
                $tableName,
                $timestamps,
                $fillable,
                $hidden,
                $import,
                $use,
                $properties
            ],
            $stub
        );

        return $stub;
    }

    protected function guessType($name)
    {
        $subtype = '';
        $type = 'string';
        if (ends_with($name, '_at')) {
            //date field
            $subtype = ' {@type date}';
        } elseif (ends_with($name, ['id', 'ID'])) {
            //int
            $type = 'int   ';
        } else {
            //assume string
        }

        return [$type, $subtype];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . ($this->option('pivot')
                ? '/stubs/model.pivot.stub'
                : '/stubs/model.stub');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['table', 't', InputOption::VALUE_OPTIONAL, 'The table name to be associated with the model.'],
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, and resource controller for the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder file for the model'],
            ['pivot', 'p', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller'],
            ['api', null, InputOption::VALUE_NONE, 'Indicates if the generated controller should be an API controller'],
        ];
    }
}
