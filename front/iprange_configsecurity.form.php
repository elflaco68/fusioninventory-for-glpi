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
   @since     2014

   ------------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

$pfIPRange_ConfigSecurity = new PluginFusioninventoryIPRange_ConfigSecurity();

if (isset ($_POST["add"])) {

   $a_data = current(getAllDatasFromTable('glpi_plugin_fusioninventory_ipranges_configsecurities',
                                 "`plugin_fusioninventory_ipranges_id`='".$_POST['plugin_fusioninventory_ipranges_id']."'",
                                 false,
                                 '`rank` DESC'));
   $_POST['rank'] = 1;
   if (isset($a_data['rank'])) {
      $_POST['rank'] = $a_data['rank'] + 1;
   }
   $pfIPRange_ConfigSecurity->add($_POST);
   Html::back();
}

?>
