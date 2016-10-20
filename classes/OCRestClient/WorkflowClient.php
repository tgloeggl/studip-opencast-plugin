<?php
    require_once "OCRestClient.php";
    class WorkflowClient extends OCRestClient
    {
        static $me;
        function __construct($config_id = 1) {
            $this->serviceName = 'WorkflowClient';
            try {
                if ($config = parent::getConfig('workflow', $config_id)) {
                    parent::__construct($config['service_url'],
                                        $config['service_user'],
                                        $config['service_password']);
                } else {
                    throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
                }
            } catch(Exception $e) {

            }
        }

        /**
         * getWorkflowInstance - Get a specific workflow instance
         *
         * @param $id The workflow instance identifier
         *
         * @return $result A JSON representation of a workflow instance
         */
        function getWorkflowInstance($id) {
            $service_url = "/instance/" . $id . ".json";
            if($result = $this->getJSON($service_url)){
                return $result->workflow;
            } else return false;
        }

        /**
         * getInstances() - returns all Workflow instances for a given SeriesID
         *
         *  @return array Workflow Instances
         */
        function getInstances($seriesID) {

            $service_url = sprintf( "/instances.json?state=&q=&seriesId=%s&seriesTitle=&creator=&contributor=&fromdate=&todate=&language="
                         . "&license=&title=&subject=&workflowdefinition=&mp=&op=&sort=&startPage=0&count=1000&compact=true", $seriesID);

            if($instances = $this->getJSON($service_url)){
                return $instances;
            } else {
                return false;
            }
        }

        /**
         * getDefinitions() - returns all Workflow definitions
         *
         *  @return array Workflow Instances
         */
        function getDefinitions() {
            $service_url = sprintf( "/definitions.json");

            if($definitions = $this->getJSON($service_url)){
                return $definitions;
            } else {
                return false;
            }
        }


    }
?>
