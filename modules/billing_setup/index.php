<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoDB.class.php";
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/paloSantoTrunk.class.php";
    require_once "libs/misc.lib.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];


    $contenido='';
    $msgError='';
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsn     = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" . $arrConfig['AMPDBPASS']['valor'] . "@" .
               $arrConfig['AMPDBHOST']['valor'] . "/asterisk";
    $pDB     = new paloDB($dsn);
    $pDBSetting = new paloDB($arrConf['issabel_dsn']['settings']);
    $pDBTrunk = new paloDB($arrConfModule['dsn_conn_database_1']);
    $arrForm  = array("default_rate"       => array("LABEL"                   => _tr("Default Rate"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "float",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                      "default_rate_offset"       => array("LABEL"                   => _tr("Default Rate Offset"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "float",
                                                    "VALIDATION_EXTRA_PARAM" => ""));


    $oForm = new paloForm($smarty, $arrForm);
    $oForm->setViewMode();
    //obtener el valor de la tarifa por defecto
    $arrDefaultRate['default_rate']=get_key_settings($pDBSetting,"default_rate");
    $arrDefaultRate['default_rate_offset']=get_key_settings($pDBSetting,"default_rate_offset");
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $strReturn = $oForm->fetchForm("$local_templates_dir/default_rate.tpl", _tr("Default Rate Configuration"), $arrDefaultRate);

    if(isset($_POST['edit_default'])) {
        $arrDefaultRate['default_rate']=get_key_settings($pDBSetting,"default_rate");
        $arrDefaultRate['default_rate_offset']=get_key_settings($pDBSetting,"default_rate_offset");
        $oForm = new paloForm($smarty, $arrForm);

        $smarty->assign("CANCEL", _tr("Cancel"));
        $smarty->assign("SAVE", _tr("Save"));
        $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
        $strReturn = $oForm->fetchForm("$local_templates_dir/default_rate.tpl", _tr("Default Rate Configuration"), $arrDefaultRate);

    }
    else if(isset($_POST['save_default'])) {
        $oForm = new paloForm($smarty, $arrForm);
        $arrDefaultRate['default_rate'] = $_POST['default_rate'];
        $arrDefaultRate['default_rate_offset'] = $_POST['default_rate_offset'];
        if($oForm->validateForm($_POST)) {
            $bValido=set_key_settings($pDBSetting,'default_rate',$arrDefaultRate['default_rate']);
            $bValido=set_key_settings($pDBSetting,'default_rate_offset',$arrDefaultRate['default_rate_offset']);
            if(!$bValido) {
                echo _tr("Error when saving default rate");
            } else {
                header("Location: index.php?menu=billing_setup");
            }
        } else {
            // Error
            $smarty->assign("mb_title", _tr("Validation Error"));
            $smarty->assign("mb_message", _tr("Value for rate is not valid"));
            $smarty->assign("CANCEL", _tr("Cancel"));
            $smarty->assign("SAVE", _tr("Save"));
            $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
            $strReturn = $oForm->fetchForm("$local_templates_dir/default_rate.tpl", _tr("Default Rate Configuration"), $arrDefaultRate);
        }

    }
    $arrTrunks=array();
    $arrData=array();
    $arrTrunksBill=array();
    //obtener todos los trunks
    $oTrunk     = new paloTrunk($pDBTrunk);
    //obtener todos los trunks que son para billing
    //$arrTrunksBill=array("DAHDI/g0","DAHDI/g1");
    getTrunksBillFiltrado($pDB, $oTrunk, $arrConfig, $arrTrunks, $arrTrunksBill);
    if(isset($_POST['submit_bill_trunks'])) {
        //obtengo las que estan guardadas y las que ahora no estan

        $selectedTrunks= isset($_POST['trunksBills'])?array_keys($_POST['trunksBills']):array();
        if (count($selectedTrunks)>0)
        {
            foreach ($selectedTrunks as $selectedTrunk)
                 $nuevaListaTrunks[]=base64_decode($selectedTrunk);
        }else $nuevaListaTrunks=array();

        $listaTrunksNuevos = array_diff($nuevaListaTrunks, $arrTrunksBill);
        $listaTrunksAusentes = array_diff($arrTrunksBill, $nuevaListaTrunks);
        //tengo que borrar los trunks ausentes
        //tengo que agregar los trunks nuevos
       // print_r($listaTrunksNuevos);
        //print_r($listaTrunksAusentes);
        if (count($listaTrunksAusentes)>0){
            $bExito=$oTrunk->deleteTrunksBill($listaTrunksAusentes);
            if (!$bExito)
               $msgError=$oTrunk->errMsg;
        }
        if (count($listaTrunksNuevos)>0){
            $bExito=$oTrunk->saveTrunksBill($listaTrunksNuevos);
            if (!$bExito)
               $msgError.=$oTrunk->errMsg;
        }
        if (!empty($msgError))
                $smarty->assign("mb_message", $msgError);
    }


    getTrunksBillFiltrado($pDB, $oTrunk, $arrConfig, $arrTrunks, $arrTrunksBill);

    $end = count($arrTrunks);
    if (is_array($arrTrunks)){
    	foreach($arrTrunks as $trunk) {
        	$arrTmp    = array();

        	$checked=(in_array($trunk[1],$arrTrunksBill))?"checked":"";
        	$arrTmp[0] = "<input type='checkbox' name='trunksBills[".base64_encode($trunk[1])."]' $checked>";
        	$arrTmp[1] = $trunk[1];
        	$arrData[] = $arrTmp;
    	}
    }


    $arrGrid = array("title"    => _tr("Trunk Bill Configuration"),
                     "icon"     => "/modules/$module_name/images/reports_billing_setup.png",
		     "width"    => "99%",
                     "start"    => ($end==0) ? 0 : 1,
                     "end"      => $end,
                     "total"    => $end,
                     "columns"  => array(0 => array("name"      => "",
                                                    "property1" => ""),
                                         1 => array("name"      => _tr("Trunk"),
                                                    "property1" => ""),
                                        )
                    );

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->pagingShow(false);
    $oGrid->customAction('submit_bill_trunks',_tr('Billing Capable'));
    $trunk_config = $oGrid->fetchGrid($arrGrid, $arrData);
    if (strpos($trunk_config, '<form') === FALSE)
        $trunk_config =
            "<form style='margin-bottom:0;' method='POST' action='?menu=billing_setup'>$trunk_config</form>";
   //mostrar los dos formularios
    $contenido.=$strReturn.$trunk_config;
    return $contenido;
}

function getTrunksBillFiltrado(&$oDB, &$oTrunk, $arrConfig, &$arrTrunks, &$arrTrunksBill)
{
    $arrTrunks = getTrunks($oDB);
    $arrTrunksBill = $oTrunk->getTrunksBill();
    $oTrunk->getExtendedTrunksBill($grupos, $arrConfig['ASTETCDIR']['valor'].'/chan_dahdi.conf');

    // Sólo los puertos no-DAHDI y los grupos de puertos DAHDI son elegibles para tener un precio
    $t = array();
    if (is_array($arrTrunks)) {
        foreach ($arrTrunks as $tupla) {
            if (substr($tupla[1], 0, 3) != 'DAHDI' || $tupla[1]{4} == 'g')
                $t[] = $tupla;
        }
        $arrTrunks = $t;
    }
}
?>
