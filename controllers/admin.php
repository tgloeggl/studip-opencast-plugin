<?php
/*
 * admin.php - admin plugin controller
 * Copyright (c) 2010  Andr� Kla�en
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';
require_once $this->trails_root.'/models/OCModel.php';
require_once $this->trails_root.'/models/OCEndpointModel.php';
require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/CaptureAgentAdminClient.php';
require_once $this->trails_root.'/classes/OCRestClient/ServicesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/WorkflowClient.php';


class AdminController extends AuthenticatedController
{
    /**
     * Common code for all actions: set default layout and page title.
     */
    function before_filter(&$action, &$args)
    {

        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_admin.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);

    }

    /**
     * This is the default action of this controller.
     */
    function index_action()
    {
        $this->redirect(PluginEngine::getLink('opencast/admin/config'));
    }

    function config_action()
    {
        PageLayout::setTitle(_("Opencast Administration"));
        Navigation::activateItem('/admin/config/oc-config');



        if(($this->info_conf = OCEndpointModel::getBaseServerConf(1))) {
            $this->info_url = $this->info_conf['service_url'];
            $this->info_user = $this->info_conf['service_user'];
            $this->info_password = $this->info_conf['service_password'];
        }
        if(($this->slave_conf = OCEndpointModel::getBaseServerConf(2))) {
            $this->slave_url = $this->slave_conf['service_url'];
            $this->slave_user = $this->slave_conf['service_user'];
            $this->slave_password = $this->slave_conf['service_password'];


        }

    }


    function update_action()
    {

        $service_url =  parse_url(Request::get('info_url'));
        $config_id = 1; // we assume that we want to configure the new opencast

        if (!array_key_exists('scheme', $service_url)) {
            $this->flash['messages'] = array('error' => _('Es wurde kein g�ltiges URL-Schema angegeben.'));
            OCRestClient::clearConfig($config_id);
            $this->redirect(PluginEngine::getLink('opencast/admin/config'));
        } else {
            $service_host        = $service_url['scheme'] .'://' . $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') ;
            $this->info_url      = $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') .  $service_url['path'];
            $this->info_user     = Request::get('info_user');
            $this->info_password = Request::get('info_password');

            OCRestClient::clearConfig($config_id);
            OCRestClient::setConfig($config_id, $service_host, $this->info_user, $this->info_password);
            OCEndpointModel::setEndpoint($config_id, $this->info_url, 'services');

            $services_client = new ServicesClient($config_id);
            $comp = $services_client->getRESTComponents();

            if ($comp) {
                $services = OCModel::retrieveRESTservices($comp);

                foreach($services as $service_url => $service_type) {

                    $service_comp = explode("/", $service_url);

                    if(sizeof($service_comp) == 2) {
                        if($service_comp)
                        OCEndpointModel::setEndpoint($config_id, $service_comp[0], $service_type);
                    }
                }

                $this->flash['messages'] = array('success' => sprintf(_("�nderungen wurden erfolgreich �bernommen. Es wurden %s Endpoints f�r die angegeben Opencast Matterhorn Installation gefunden und in der Stud.IP Konfiguration eingetragen"), count($comp)));
            } else {
                $this->flash['messages'] = array('error' => _('Es wurden keine Endpoints f�r die angegeben Opencast Matterhorn Installation gefunden. �berpr�fen Sie bitte die eingebenen Daten.'));
            }
        }

        $redirect = true;

        // stupid duplication for slave-config
        $slave_url =  parse_url(Request::get('slave_url'));
        $config_id = 2; // we assume that we want to configure slave opencast server

        if (!array_key_exists('scheme', $slave_url)) {
            $this->flash['messages'] = array('error' => _('Es wurde kein g�ltiges URL-Schema angegeben.'));
            OCRestClient::clearConfig($config_id);
            //$this->redirect(PluginEngine::getLink('opencast/admin/config'));
        } else {
            $slave_host           = $slave_url['scheme'] .'://' . $slave_url['host'] . (isset($slave_url['port']) ? ':' . $slave_url['port'] : '') ;
            $this->slave_url      = $slave_url['host'] . (isset($slave_url['port']) ? ':' . $slave_url['port'] : '') .  $slave_url['path'];
            $this->slave_user     = Request::get('slave_user');
            $this->slave_password = Request::get('slave_password');

            OCRestClient::clearConfig($config_id);
            OCRestClient::setConfig($config_id, $slave_host, $this->slave_user, $this->slave_password);
            OCEndpointModel::setEndpoint($config_id, $this->slave_url, 'services');

            //fix client call here for new config
            $services_client2 = new ServicesClient($config_id);
            $comp = $services_client2->getRESTComponents();

            if ($comp) {
                $services = OCModel::retrieveRESTservices($comp);

                foreach($services as $slave_url => $slave_type) {

                    $slave_comp = explode("/", $slave_url);

                    if(sizeof($slave_comp) == 2) {
                        if($slave_comp)
                        OCEndpointModel::setEndpoint($config_id, $slave_comp[0], $slave_type);
                    }
                }

                $this->flash['messages'] = array('success' => sprintf(_("�nderungen wurden erfolgreich �bernommen. Es wurden %s Endpoints f�r die angegeben Opencast Slave Installation gefunden und in der Stud.IP Konfiguration eingetragen"), count($comp)));
            } else {
                $this->flash['messages'] = array('error' => _('Es wurden keine Endpoints f�r die angegeben Opencast Slave Installation gefunden. �berpr�fen Sie bitte die eingebenen Daten.'));
            }
        }

        if($redirect) {
            $this->redirect(PluginEngine::getLink('opencast/admin/config'));
        }
    }


    function endpoints_action()
    {
        PageLayout::setTitle(_("Opencast Endpoint Verwaltung"));
        Navigation::activateItem('/admin/config/oc-endpoints');
        // hier kann eine Endpoint�berischt angezeigt werden.
        //$services_client = ServicesClient::getInstance();
        $this->endpoints = OCEndpointModel::getEndpoints();
    }

    function update_endpoints_action()
    {
        $this->redirect(PluginEngine::getLink('opencast/admin/endpoints'));
    }



    /**
     * brings REST URL in one format before writing in db
     */
    function cleanClientURLs()
    {
        $urls = array('series', 'search', 'scheduling', 'ingest', 'captureadmin'
            , 'upload', 'mediapackage');

        foreach($urls as $pre) {
            $var = $pre.'_url';
            $this->$var = rtrim($this->$var,"/");
        }

    }

    function resources_action()
    {
        PageLayout::setTitle(_("Opencast Capture Agent Verwaltung"));
        Navigation::activateItem('/admin/config/oc-resources');

        $this->resources = OCModel::getOCRessources();
        if(empty($this->resources)) {
            $this->flash['messages'] = array('info' => _('Es wurden keine passenden Ressourcen gefunden.'));

        }

        $caa_client = CaptureAgentAdminClient::getInstance();
        $workflow_client = WorkflowClient::getInstance();

        $agents = $caa_client->getCaptureAgents();
        $this->agents = $caa_client->getCaptureAgents();


        foreach ($this->resources as $resource) {
            $assigned_agents = OCModel::getCAforResource($resource['resource_id']);
            if($assigned_agents){
                $existing_agent = false;
                foreach($agents as $key => $agent) {
                    if($agent->name ==  $assigned_agents['capture_agent']) {
                        unset($agents->$key);
                        $existing_agent = true;
                    }
                 }
                if(!$existing_agent){
                    OCModel::removeCAforResource($resource['resource_id'], $assigned_agents['capture_agent']);
                    $this->flash['messages'] = array('info' => sprintf(_("Der Capture Agent %s existiert nicht mehr und wurde entfernt."),$assigned_agents['capture_agent'] ));
                }
            }
        }

        $this->available_agents = $agents;
        $this->definitions = $workflow_client->getDefinitions();

        $this->assigned_cas = OCModel::getAssignedCAS();

    }


    function update_resource_action()
    {

        $this->resources = OCModel::getOCRessources();

        foreach($this->resources as $resource) {
            if(Request::get('action') == 'add'){
                if(($candidate_ca = Request::get($resource['resource_id'])) && $candidate_wf = Request::get('workflow')){
                    $success = OCModel::setCAforResource($resource['resource_id'], $candidate_ca, $candidate_wf);
                }
            }
        }

        if($success) $this->flash['messages'] = array('success' => _("Capture Agents wurden zugewiesen."));

        $this->redirect(PluginEngine::getLink('opencast/admin/resources'));
    }

    function remove_ca_action($resource_id, $capture_agent)
    {
        OCModel::removeCAforResource($resource_id, $capture_agent);
        $this->redirect(PluginEngine::getLink('opencast/admin/resources'));
    }

    // client status
    function client_action()
    {
        $caa_client    = CaptureAgentAdminClient::getInstance();
        $this->agents  = $caa_client->getCaptureAgents();
    }

    function refresh_episodes_action($ticket){
        if(check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('admin',$this->course_id)) {
            $stmt = DBManager::get()->prepare("SELECT DISTINCT ocs.seminar_id, ocs.series_id FROM oc_seminar_series AS ocs WHERE 1");
            $stmt->execute(array());
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (is_array($courses)) {
                foreach ($courses as $course) {

                    $ocmodel = new OCCourseModel($course['seminar_id']);
                    $ocmodel->getEpisodes(true);
                    unset($ocmodel);
                }
                $this->flash['messages'] = array('success' => _("Die Episodenliste aller Series  wurde aktualisiert."));
            }
        }
        $this->redirect(PluginEngine::getLink('opencast/admin/config/'));
    }
}
?>
