<?php

class AddLogActions extends Migration {

    function up()
    {





        DBManager::get()->query("ALTER TABLE `oc_config` DROP PRIMARY KEY, ADD UNIQUE KEY(service_url,service_user,service_password");
        DBManager::get()->query("ALTER TABLE `oc_config` ADD `config_id` INT UNIQUE KEY NOT NULL AUTO_INCREMENT FIRST;");
        DBManager::get()->query("ALTER TABLE `oc_endpoints` ADD `config_id` INT NOT NULL DEFAULT 1 FIRST;");
        DBManager::get()->query("ALTER TABLE `oc_resources` ADD `config_id` INT NOT NULL DEFAULT 1 FIRST;");
        DBManager::get()->query("ALTER TABLE `oc_seminar_series` ADD `config_id` INT NOT NULL DEFAULT 1 FIRST;");
        DBManager::get()->query("ALTER TABLE `oc_seminar_workflows` ADD `config_id` INT NOT NULL DEFAULT 1 FIRST;");



    }

    function down()
    {
        DBManager::get()->query("ALTER TABLE `oc_config` DROP COLUMN `config_id`;")
        DBManager::get()->query("ALTER TABLE `oc_config` DROP INDEX `service_url`,  ADD PRIMARY KEY (`service_url`);")
        DBManager::get()->query("ALTER TABLE `oc_endpoints` DROP COLUMN `config_id`;")
        DBManager::get()->query("ALTER TABLE `oc_resources` DROP COLUMN `config_id`;")
        DBManager::get()->query("ALTER TABLE `oc_seminar_series` DROP COLUMN `config_id`;")
        DBManager::get()->query("ALTER TABLE `oc_seminar_workflows` DROP COLUMN `config_id`;")

    }
}

?>
