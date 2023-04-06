<?php

namespace App\Console\Commands\Server;

use Illuminate\Console\Command;
use App\Classes\Postmark;
use App\Models\{
    Server,
    ServerUsageLog,
    User
};

class Check extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:check {id?} {--all : Check available storage on all servers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of a database clusters (servers)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');
        $all = $this->option('all');
        
        if (empty($id) && empty($all)) {
            $this->error('You must specify a server id or use the --all option');

            return 1;
        }

        if ($all) {
            return $this->checkServers();
        }

        return 0;
    }

    public function checkServer($id)
    {
        // TODO: Implement someday
        $this->error('Not yet implemented');

        return 1;
    }

    public function checkServers()
    {
        $servers = Server::whereNotNull('server_provider_configuration_id')->get();
        foreach ($servers as $server) {
            $server_id = $server->id;
            $server_name = $server->name;
            $database_formatted_date = now()->toAtomString();
            $current_date = date("l, F d, Y g:i A");
    
            $usage_log = $server->usage_log;
            $last_ran_usage = isset($usage_log->usage) ? $usage_log->usage : NULL;
            $last_ran_date = isset($usage_log->last_sent) ? $usage_log->last_sent : "";
            $configuration = $server->configuration;
            $total_available_storage = $configuration->storage * 1073741824;
            $current_usage = $server->getDiskUsage();

            if ($total_available_storage > 0) {
                $usage_percent = ($current_usage / $total_available_storage) * 100;
                $usage_percent = 100 - $usage_percent;
                $server->free_space_percent = round($usage_percent);
                $server->save();

                if ($usage_percent >= $server->alert_threshold) {
                    if (empty($last_ran_date) || empty($last_ran_usage)) {
                        ServerUsageLog::create([
                            "server_id" => $server_id,
                            "last_sent" => $database_formatted_date,
                            "usage"     => $usage_percent
                        ]);
                        $this->sendFinishedEmailForDiskUsage($server_name, $usage_percent, $current_date);
                    } elseif (time() - strtotime($last_ran_date) > 86400 || $usage_percent >= $last_ran_usage + 5) {
                        $usage_log->last_sent = $database_formatted_date;
                        $usage_log->usage = $usage_percent;
                        $usage_log->save();
                        $this->sendFinishedEmailForDiskUsage($server_name, $usage_percent, $current_date, $last_ran_usage, $last_ran_date);
                    }
                }
            }
        }
    }

    /**
     * Sends an email to team admins informing them of server overuse
     *
     * @param  string $server_name    The name of the server
     * @param  int    $usage_percent  The amount of disk usage for the server
     * @param  string $current_date   The date to send to the email
     * @param  int    $last_ran_usage The last ran amount of disk usage (Optional)
     * @param  string $last_ran_date  The date of the last run (Optional)
     * @return void
     */
    public function sendFinishedEmailForDiskUsage($server_name, $usage_percent, $current_date, $last_ran_usage = NULL, $last_ran_date = "")
    {
        $admins = User::where('is_admin', TRUE)->get();

        if ((! empty($last_ran_usage) && ! empty($last_ran_date)) && (time() - strtotime($last_ran_date) < 86400 && $usage_percent >= $last_ran_usage + 5)) {
            $message = "The disk usage has increased by 5% or more today. We strongly advise that you reduce your disk usage or upgrade the size of your cluster in the Admin console under \"Servers\". Failure to address this issue in a timely manner could result in performance issues or outages for your team.";
        } else {
            $message = "You should consider upgrading the size of your cluster in the Admin Console under \"Servers\".";
        }

        foreach ($admins as $admin) {
            $admin_email = $admin->email;
            if (! empty($admin_email)) {
                $data = [
                    "usage_percent"      => $usage_percent,
                    "server_name"        => $server_name,
                    "current_date"       => $current_date,
                    "additional_message" => $message
                ];
                $this->line("Sending email for server $server_name to $admin_email");
                Postmark::send($admin_email, "server-usage-check", $data);
            }
        }
    }
}
