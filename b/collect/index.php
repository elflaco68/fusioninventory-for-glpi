<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2016 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    David Durieux
   @co-author
   @copyright Copyright (c) 2010-2016 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2013

   ------------------------------------------------------------------------
 */

ob_start();
include ("../../../../inc/includes.php");
ob_end_clean();

$response = new Stdclass();
//Agent communication using REST protocol
if (isset($_GET['action'])) {
   switch ($_GET['action']) {

      case 'getJobs':
         if(isset($_GET['machineid'])) {
            $pfAgent        = new PluginFusioninventoryAgent();
            $pfTaskjobstate = new PluginFusioninventoryTaskjobstate();
            $pfTaskjoblog   = new PluginFusioninventoryTaskjoblog();
            $a_agent = $pfAgent->InfosByKey(Toolbox::addslashes_deep($_GET['machineid']));
            if (isset($a_agent['id'])) {
               $moduleRun = $pfTaskjobstate->getTaskjobsAgent($a_agent['id']);

               foreach ($moduleRun as $className => $array) {
                  if (class_exists($className)) {
                     if ($className == "PluginFusioninventoryCollect") {

                        $class = new PluginFusioninventoryCollect();
                        foreach ($array as $data) {
                           $out = $class->run($data, $a_agent);
                           if (count($out) > 0) {
                              $response->jobs = $out;
                           }
                           $pfTaskjobstate->changeStatus(
                                   $data['id'],
                                   PluginFusioninventoryTaskjobstate::SERVER_HAS_SENT_DATA);

                           $a_input = array();
                           $a_input['plugin_fusioninventory_taskjobstates_id'] = $data['id'];
                           $a_input['items_id'] = $a_agent['id'];
                           $a_input['itemtype'] = 'PluginFusioninventoryAgent';
                           $a_input['date'] = date("Y-m-d H:i:s");
                           $a_input['comment'] = '';
                           $a_input['state'] = PluginFusioninventoryTaskjoblog::TASK_STARTED;
                           $pfTaskjoblog->add($a_input);


                        }
                     }
                  }
               }
            }
         }
         break;

      case 'setAnswer':
         // example
         // ?action=setAnswer&InformationSource=0x00000000&BIOSVersion=VirtualBox&SystemManufacturer=innotek%20GmbH&uuid=fepjhoug56743h&SystemProductName=VirtualBox&BIOSReleaseDate=12%2F01%2F2006
         $pfTaskjobstate = new PluginFusioninventoryTaskjobstate();
         $pfAgent = new PluginFusioninventoryAgent();

         //Collect UUID format : UUID_COLLECTTASKID
         //Exemple : 5730f0b5aea3f_5

         $_GET['uuid'] = explode("_", $_GET['uuid']);

         $jobstate = current($pfTaskjobstate->find("`uniqid`='".$_GET['uuid'][0]."'
            AND `state`!='".PluginFusioninventoryTaskjobstate::FINISHED."'", '', 1));


         
         



         
            


         if (isset($jobstate['plugin_fusioninventory_agents_id'])) {

            //At this point, itemtype and items_id relates to the parent Collect, they should be translated to the actual collect task inside.

           $parent['itemtype'] = $jobstate['itemtype'];
           $parent['items_id'] = $jobstate['items_id'];

           $jobstate['itemtype'] = PluginFusioninventoryCollect::getRealItemtypeById($jobstate['items_id']);
           $jobstate['items_id'] = $_GET['uuid'][1];

           
            //Is this the final task of that collect?
            $status_finish = false;
            $CollectObj = new $jobstate['itemtype'];
            $CollectObjSearch = $CollectObj->find("plugin_fusioninventory_collects_id = {$parent['items_id']}");
            end($CollectObjSearch);

            if($jobstate['items_id'] == key($CollectObjSearch)){
              $status_finish = true;
            }

            $pfAgent->getFromDB($jobstate['plugin_fusioninventory_agents_id']);
            $computers_id = $pfAgent->fields['computers_id'];

            $a_values = $_GET;
            unset($a_values['action']);
            //we might need the uuid after all
            //unset($a_values['uuid']);

            

            switch ($jobstate['itemtype']) {

               case 'PluginFusioninventoryCollect_Registry':
                  // update registry content
                  $pfCRC = new PluginFusioninventoryCollect_Registry_Content();
                  $pfCRC->updateComputer($computers_id,
                                         $a_values,
                                         $jobstate['items_id']);
                  $pfTaskjobstate->changeStatus(
                          $jobstate['id'],
                          PluginFusioninventoryTaskjobstate::AGENT_HAS_SENT_DATA);
                  if (isset($a_values['_cpt'])
                          && $a_values['_cpt'] == 0) { // it not find the path

                    if($status_finish){
                      $pfTaskjobstate->changeStatusFinish(
                          $jobstate['id'],
                          $jobstate['items_id'],
                          $jobstate['itemtype'],
                          1,
                          'Path not found');  
                    }
                    
                  }
                  if (isset($a_values['_cpt'])
                          && $a_values['_cpt'] == 1) { // it last value
                    if($status_finish){
                      $pfTaskjobstate->changeStatusFinish(
                          $jobstate['id'],
                          $jobstate['items_id'],
                          $jobstate['itemtype']);
                    }
                  }
                  break;

               case 'PluginFusioninventoryCollect_Wmi':
                  // update registry content
                  $pfCWC = new PluginFusioninventoryCollect_Wmi_Content();
                  $pfCWC->updateComputer($computers_id,
                                         $a_values,
                                         $jobstate['items_id'],$a_values['_cpt']);
                  $pfTaskjobstate->changeStatus(
                          $jobstate['id'],
                          PluginFusioninventoryTaskjobstate::AGENT_HAS_SENT_DATA);
                  if (isset($a_values['_cpt'])) {
                    if($status_finish){
                      $pfTaskjobstate->changeStatusFinish(
                          $jobstate['id'],
                          $jobstate['items_id'],
                          $jobstate['itemtype']);
                    } 
                     
                  }
                  break;

               case 'PluginFusioninventoryCollect_File':
                  // update file content
                  $pfCFC = new PluginFusioninventoryCollect_File_Content();
                  $pfCFC->storeTempFilesFound($jobstate['id'], $a_values);
                  $pfTaskjobstate->changeStatus(
                          $jobstate['id'],
                          PluginFusioninventoryTaskjobstate::AGENT_HAS_SENT_DATA);
                  if (isset($a_values['_cpt'])) { // it last value
                     $pfCFC->updateComputer($computers_id,
                                            $jobstate['items_id'],
                                            $jobstate['id'],$a_values['_cpt']);
                     if($status_finish){
                      $pfTaskjobstate->changeStatusFinish(
                          $jobstate['id'],
                          $jobstate['items_id'],
                          $jobstate['itemtype']); 
                     }
                     
                  }
                  break;

            }
         }
         break;

   }

   if (count($response) > 0) {
      echo json_encode($response);
   } else {
      echo json_encode((object)array());
   }

}

?>