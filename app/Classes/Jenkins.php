<?php

namespace App\Classes;

use JenkinsKhan\Jenkins as JenkinsApi;
use SebastianBergmann\Environment\Runtime;
use RuntimeException;
use Exception;
use stdClass;

class Jenkins
{
    private $base_url;
    
    protected $jenkins;

    public function __construct()
    {
        $this->base_url = '';
        $this->jenkins = NULL;
    }

    public function setJenkins($url)
    {
        $this->baseUrl = rtrim($url, '/');
        $this->jenkins = new JenkinsApi($url);
    }

    public function createFolder($name, $view_name = "All", $filterExecutors = "false", $filterQueue = "false", $nonRecursive = "false")
    {
        $folder_xml = <<<XML
            <com.cloudbees.hudson.plugins.folder.Folder plugin="cloudbees-folder@6.12">
                <actions/>
                <properties/>
                <folderViews class="com.cloudbees.hudson.plugins.folder.views.DefaultFolderViewHolder">
                    <views>
                        <hudson.model.AllView>
                            <owner class="com.cloudbees.hudson.plugins.folder.Folder" reference="../../../.."/>
                            <name>{$view_name}</name>
                            <filterExecutors>{$filterExecutors}</filterExecutors>
                            <filterQueue>{$filterQueue}</filterQueue>
                            <properties class="hudson.model.View\$PropertyList"/>
                        </hudson.model.AllView>
                    </views>
                    <tabBar class="hudson.views.DefaultViewsTabBar"/>
                </folderViews>
                <healthMetrics>
                    <com.cloudbees.hudson.plugins.folder.health.WorstChildHealthMetric>
                        <nonRecursive>{$nonRecursive}</nonRecursive>
                    </com.cloudbees.hudson.plugins.folder.health.WorstChildHealthMetric>
                </healthMetrics>
                <icon class="com.cloudbees.hudson.plugins.folder.icons.StockFolderIcon"/>
            </com.cloudbees.hudson.plugins.folder.Folder>
            XML;

        try {
            $this->jenkins->createJob($name, $folder_xml);

            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Creates command jobs based on the xml and schedule sent in
     *
     * @param  string $name     The name of the job being created
     * @param  string $command  The command for the job
     * @param  string $schedule (optional) The schedule for the job
     * @return bool
     */
    public function createCommandJob($name, $command, $schedule = "", $post_build = NULL)
    {
        $script_xml = $this->buildCommandConfig($command, $schedule, $post_build);

        try {
            $this->jenkins->createJob($name, $script_xml);

            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Updates command jobs to the xml and schedule sent in
     *
     * @param  string $name       The name of the job being created
     * @param  string $command    The command for the job
     * @param  string $schedule   (optional) The schedule for the job
     * @param  string $post_build (optional) The post build job to be ran if job succeeds
     * @return bool
     */
    public function updateCommandJob($name, $command, $schedule = "", $post_build = NULL)
    {
        $script_xml = $this->buildCommandConfig($command, $schedule, $post_build);

        try {
            $this->jenkins->setJobConfig($name, $script_xml);

            return TRUE;
        } catch (Exception $e) {
            logger()->error("Failed to update Jenkins command job. Error message: " . $e->getMessage(), compact('name', 'command', 'schedule'));

            return FALSE;
        }
    }

    /**
     * Build a command jobs to the xml
     *
     * @param  string $command  The command for the job
     * @param  string $schedule (optional) The schedule for the job
     * @return bool
     */
    protected function buildCommandConfig($command, $schedule = "", $post_build = NULL, $delete_workspace = FALSE)
    {
        if (empty($schedule)) {
            $schedule_xml = <<<XML
                <triggers/>
                XML;
        } else {
            $schedule_xml = <<<XML
                <triggers>
                    <hudson.triggers.TimerTrigger>
                        <spec>{$schedule}</spec>
                    </hudson.triggers.TimerTrigger>
                </triggers>
                XML;
        }

        $delete_xml = '';

        if ($delete_workspace) {
            $delete_xml = <<<XML
                <hudson.plugins.ws__cleanup.WsCleanup plugin="ws-cleanup@0.42">
                    <patterns class="empty-list"/>
                    <deleteDirs>false</deleteDirs>
                    <skipWhenFailed>false</skipWhenFailed>
                    <cleanWhenSuccess>true</cleanWhenSuccess>
                    <cleanWhenUnstable>true</cleanWhenUnstable>
                    <cleanWhenFailure>true</cleanWhenFailure>
                    <cleanWhenNotBuilt>true</cleanWhenNotBuilt>
                    <cleanWhenAborted>true</cleanWhenAborted>
                    <notFailBuild>false</notFailBuild>
                    <cleanupMatrixParent>false</cleanupMatrixParent>
                    <externalDelete></externalDelete>
                    <disableDeferredWipeout>false</disableDeferredWipeout>
                </hudson.plugins.ws__cleanup.WsCleanup>
                XML;
        }

        if (empty($post_build) && ! $delete_workspace) {
            $post_build_xml = <<<XML
                <publishers/>
                XML;
        } elseif ($delete_workspace && empty($post_build)) {
            $post_build_xml = <<<XML
                <publishers>
                    {$delete_xml}
                </publishers>
                XML;
        } else {
            $post_build_xml = <<<XML
                <publishers>
                    <hudson.tasks.BuildTrigger>
                    <childProjects>{$post_build}</childProjects>
                        <threshold>
                            <name>SUCCESS</name>
                            <ordinal>0</ordinal>
                            <color>BLUE</color>
                            <completeBuild>true</completeBuild>
                        </threshold>
                    </hudson.tasks.BuildTrigger>
                    {$delete_xml}
                </publishers>
                XML;
        }

        $script_xml = <<<XML
            <project>
                <description/>
                <keepDependencies>false</keepDependencies>
                <properties/>
                <scm class="hudson.scm.NullSCM"/>
                <canRoam>true</canRoam>
                <disabled>false</disabled>
                <blockBuildWhenDownstreamBuilding>false</blockBuildWhenDownstreamBuilding>
                <blockBuildWhenUpstreamBuilding>false</blockBuildWhenUpstreamBuilding>
                {$schedule_xml}
                <concurrentBuild>false</concurrentBuild>
                <builders>
                    <hudson.tasks.Shell>
                        <command>{$command}</command>
                    </hudson.tasks.Shell>
                </builders>
                {$post_build_xml}
                <buildWrappers/>
            </project>
            XML;

        return $script_xml;
    }

    /**
     * Creates command jobs based on the xml and schedule send in
     * with a post build condition built in
     *
     * @param  string $name     The name of the job being created
     * @param  string $command  The command for the job
     * @param  string $path     The path for the post build job
     * @param  string $schedule (optional) The schedule for the job
     * @return bool
     */
    public function createCommandJobWithPostBuild($name, $command, $path, $schedule = "")
    {
        $script_xml = $this->buildCommandConfig($command, $schedule, $path);

        try {
            $this->jenkins->createJob($name, $script_xml);

            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Updates a job that exists or creates the job if it does not exist.
     *
     * @param  string $name       Name of job to create
     * @param  string $definition XML definition of the job
     * @return bool   TRUE on success, otherwise FALSE
     */
    public function createOrUpdateJob($name, $definition)
    {
        try {
            $this->setJobConfig($name, $definition);
        } catch (RuntimeException|Exception $e) {
            try {
                $this->jenkins->createJob($name, $definition);
            } catch (Exception $e2) {
                $message = sprintf("Error occured while creating job %s. Message: %s", $name, $e2->getMessage());
                $this->handleJobError(
                    $message,
                    $name,
                    $definition,
                    FALSE
                );

                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Retrieve the XML configuration of an existing job
     *
     * @param  mixed  $name Jenkins job name
     * @return string XML configuration of existing job
     */
    public function getJobConfig($name)
    {
        try {
            return $this->jenkins->getJobConfig($name);
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Gets all current executers
     *
     * @return array
     */
    public function getExecuters()
    {
        try {
            $jenkins_url = config("services.jenkins.region_url");
            $this->setJenkins($jenkins_url);

            return $this->jenkins->getExecutors();
        } catch (Exception $e) {
            return [];
        }
    }

    public function reloadJob($url, $name)
    {
        try {
            $url = sprintf('%s/job/%s/reload', $url, $name);
            $curl = curl_init($url);
            curl_setopt($curl, \CURLOPT_POST, 1);
            curl_setopt($curl, \CURLOPT_POSTFIELDS, "");

            $headers = ['Content-Type: text/xml'];

            curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
            curl_exec($curl);
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Check if Jenkins jobs exists
     *
     * @param  string $name Jenkins job to check for
     * @return bool   Returns TRUE if job exists, otherwise FALSE
     */
    public function checkJobExists($name)
    {
        $check = FALSE;

        try {
            $jobs = $this->jenkins->getAllJobs();
        } catch (Exception $e) {
            $jobs = [];
        }

        foreach ($jobs as $job) {
            if ($job["name"] == $name) {
                $check = TRUE;
            }
        }

        return $check;
    }

    /**
     * Check if jobs exists and create a folder if it does not
     *
     * @param  string $name Jenkins job to check for and folder to create
     * @return bool   Returns TRUE if folder exists or is successfully created, otherwise FALSE
     */
    public function checkJobExistsAndCreateFolderIfNot($name)
    {
        $check = $this->checkJobExists($name);

        if ($check === FALSE) {
            $check = $this->createFolder($name);
        }

        return $check;
    }

    public function getQueue($url)
    {
        $url = sprintf('%s/queue/api/json', $url);
        $curl = curl_init($url);

        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $infos = json_decode($ret);
        if (! $infos instanceof stdClass) {
            throw new RuntimeException('Error during json_decode');
        }

        return $infos;
    }

    public function getConsoleTextFile($url, $jobname, $buildNumber)
    {
        $file_path = config("app.upload_directory") . "/tmp/{$jobname}_{$buildNumber}_" . date('YmdHis') . ".txt";
        $fp = fopen($file_path, "w");

        $url = sprintf('%s/job/%s/%s/consoleText', $url, $jobname, $buildNumber);
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, \CURLOPT_FILE, $fp);

        curl_exec($curl);

        $size = number_format(filesize($file_path) / 1048576, 2);

        return $file_path;
    }

    public function getConsoleText($url, $jobname, $buildNumber)
    {
        $got_all = TRUE;
        $response = "";
        $total_size = 0;

        $url = sprintf('%s/job/%s/%s/consoleText', $url, $jobname, $buildNumber);
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, \CURLOPT_WRITEFUNCTION, function ($curl, $data) use (&$response, &$total_size) {
            $response .= $data;
            $size = strlen($data);
            $total_size += $size;

            if ($total_size > 1048576) {
                return -1;
            }

            return $size;
        });

        curl_exec($curl);

        if ($total_size >= 1048576) {
            $got_all = FALSE;
        }

        $response = strstr($response, "[$jobname]");
        $response = preg_replace('/^.+\n/', '', $response);

        return ["console_text" => $response, "got_all" => $got_all];
    }

    public function launchFunction($job, $params)
    {
        $jenkins_url = config("services.jenkins.region_url");
        $this->setJenkins("{$jenkins_url}/job/Functions");

        return $this->jenkins->launchJob($job, $params);
    }

    public function checkFunctionStatus($team, $job_name)
    {
        $jenkins_url = config("services.jenkins.region_url");
        $queue_list = $this->getQueue($jenkins_url);
        if (empty($queue_list->items) == FALSE) {
            foreach ($queue_list->items as $queue_item) {
                if ($job_name == $queue_item->task->name) {
                    foreach ($queue_item->actions[0]->parameters as $param) {
                        if ($param->name == "TEAM" && $param->value == $team) {
                            return "QUEUED";
                        }
                    }
                }
            }
        }

        $this->setJenkins("{$jenkins_url}/job/Functions");

        try {
            $job = $this->jenkins->getJob($job_name);
        } catch (Exception $err) {
            $job = [];
        }

        if (empty($job) == FALSE) {
            $builds = $job->getBuilds();
        }

        foreach ($builds as $build) {
            $params = $build->getInputParameters();

            if ($params["TEAM"] == $team) {
                $status = $build->getResult();
                break;
            }
        }

        if ($status == "SUCCESS" || $status == "WAITING" || $status == "RUNNING") {
            return $status;
        }

        return "FAILURE";
    }

    /**
     * @param string $jobname
     * @param        $configuration
     *
     * @internal param string $document
     */
    public function setJobConfig($jobname, $configuration)
    {
        $url = sprintf('%s/job/%s/config.xml', $this->baseUrl, $jobname);
        $curl = curl_init($url);
        curl_setopt($curl, \CURLOPT_POST, 1);
        curl_setopt($curl, \CURLOPT_POSTFIELDS, $configuration);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, TRUE);

        $headers = ['Content-Type: text/xml'];

        curl_setopt($curl, \CURLOPT_HTTPHEADER, $headers);
        curl_exec($curl);

        if (curl_errno($curl) !== 0) {
            $message = curl_error($curl);
            $this->handleJobError(
                sprintf('Error setting configuration for job %s. Message: %s', $jobname, $message),
                $jobname,
                $configuration
            );
        }
        $info = curl_getinfo($curl);
        $statusCode = $info['http_code'];

        switch ($statusCode) {
            case 200:
                break;
            case 403:
                $this->handleJobError(
                    sprintf('Access denied to URL %s"', $info['url']),
                    $jobname,
                    $configuration
                );
                break;
            case 404:
                throw new Exception(sprintf('Job %s was not found', $jobname));
                break;
            default:
                $this->handleJobError(
                    sprintf('Unknown Error. HTTP response code %s received', $statusCode),
                    $jobname,
                    $configuration
                );
                break;
        }
    }

    /**
     * Log job change error to Rollbar and, optionally, throw an exception
     *
     * @param  string $message        A message with details of the error
     * @param  string $jobname        The name of the job being changed
     * @param  string $configuration  The XML configuration of the job
     * @param  bool   $throwException (optional) TRUE when exception should be thrown, otherwise FALSE
     * @return void
     */
    public function handleJobError($message, $jobname, $configuration, $throwException = TRUE)
    {
        logger()->error($message, compact('jobname', 'configuration'));
        if ($throwException) {
            throw new RuntimeException($message);
        }
    }

    /**
     * runJenkinsJob This function is run job if exist in jenkins
     *
     * @param  string $jenkins_url This is the url of jenkins where we run the build
     * @param  string $job_name    This is the job name
     * @return bool
     */
    public function runJenkinsJob($jenkins_url, $job_name)
    {
        try {
            $this->setJenkins($jenkins_url);
            $job = $this->jenkins->getJob($job_name);
            if ($job) {
                return $this->jenkins->launchJob($job_name);
            }

            loger()->error("Failed to run Jenkins build. Could not find Jenkins job $job_name", compact('jenkins_url', 'job_name'));

            return FALSE;
        } catch (Exception $e) {
            loger()->error("Failed to run Jenkins build. Error message: " . $e->getMessage(), compact('jenkins_url', 'job_name'));

            return FALSE;
        }
    }
}
