<?php
include 'vendor/autoload.php';

use Dotenv\Dotenv;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CompatibilityLayer
{
    /**
     * @var Dotenv
     */
    private $dotenv;

    /**
     * @var array
     */
    private $arguments;
    
    /**
     * Laravel application
     *
     * @var Illuminate\Foundation\Application
     */
    private $app;

    /**
     * @var bool
     */
    private $useRollbar = FALSE;

    function __construct()
    {
        $this->app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $this->app->make(Kernel::class);
        $response = $kernel->handle(
            $request = Request::capture()
        );

        $this->dotenv = Dotenv::createImmutable(__DIR__);
        $this->dotenv->load();
    }

    /**
     * Process arguments and call the appropriate method
     *
     * @return void
     */
    function handle(array $arguments)
    {
        $file = __FILE__;

        $arguments_json = json_encode($arguments);
        error_log("Code Igniter compatibility layer has been used. Please convert command in. Script: $file Arguments: $arguments_json\n");

        $this->arguments = $arguments;

        if ($this->arguments[2] == 'Run' || $this->arguments[1] == 'Integrations') {
            $this->runConnectorCommand();
        } elseif ($this->arguments[2] == 'BP_Publish') {
            $this->runPublisherCommand();
        } elseif ($this->arguments[2] == 'DW_View') {
            $this->runViewCommand();
        }
    }

    /**
     * Convert CI Connector command and run equivalent Artisan command
     *
     * @return void
     */
    function runConnectorCommand()
    {
        $arguments = ['action' => $this->arguments[3], 'database_id' => $this->arguments[4]];

        if (count($this->arguments) > 5) {
            $arguments['--table'] = $this->arguments[5];
        }

        Artisan::call('connector:run', $arguments);
    }

    /**
     * Convert CI Publisher command and run equivalent Artisan command
     *
     * @return void
     */
    function runPublisherCommand()
    {
        $action = $this->arguments[3];

        if ($action == 'publishSchedule') {
            Artisan::call('publish:schedule', ['schedule_id' => $this->arguments[4]]);
        } elseif ($action == 'publishOnce') {
            Artisan::call('publish:once', ['data_id' => $this->arguments[4]]);
        }
    }

    /**
     * Convert CI View command and run equivalent Artisan command
     *
     * @return void
     */
    function runViewCommand()
    {
        echo "Refreshing view...";
        if ($this->arguments[3] == 'refreshView') {
            Artisan::call('view:refresh', [
                'database_id' => $this->arguments[4],
                'view_schema' => $this->arguments[6],
                'view_name' => $this->arguments[5],
            ]);
        }
        echo "DONE\n";
    }

    /**
     * Output a message to a line in the console
     *
     * @param  string $string Message to output
     * @return void
     */
    function line(string $string)
    {
        echo $string . PHP_EOL;
    }

    /**
     * Path of current application
     */
    function getApplicationPath() : string
    {
        if (isset($_ENV['APPLICATION_PATH'])) {
            $application_path = $_ENV['APPLICATION_PATH'];
        } else {
            $application_path = __DIR__;
        }

        return rtrim($application_path, '/');
    }
}

try {
    (new CompatibilityLayer())->handle($argv);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
