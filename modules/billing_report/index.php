<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.0-12                                               |
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
  $Id: index.php,v 1.1 2010-01-15 01:01:20 Eduardo Cueva ecueva@palosanto.com Exp $ */
//include issabel framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoCDR.class.php";
require_once "libs/misc.lib.php";
include_once "libs/paloSantoRate.class.php";
include_once "libs/paloSantoTrunk.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantobilling_report.class.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDBSet = new paloDB($arrConf['issabel_dsn']['settings']);

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);
    $dsn  = $arrConfig['AMPDBENGINE']['valor'] . "://" . $arrConfig['AMPDBUSER']['valor'] . ":" .
            $arrConfig['AMPDBPASS']['valor'] . "@" . $arrConfig['AMPDBHOST']['valor'] . "/asteriskcdrdb";
    $pDB  = new paloDB($dsn);

    $pDBTrunk = new paloDB($arrConf['dsn_conn_database_1']);
    $total = 0;
    $oCDR    = new paloSantoCDR($pDB);
    $smarty->assign("menu","billing_report");

    $pDBSQLite = new paloDB($arrConf['dsn_conn_database_2']); //rate

	 if(!empty($pDBSQLite->errMsg)) {
        echo "ERROR DE DB: $pDB->errMsg <br>";
    }

    $pRate = new paloRate($pDBSQLite);
    if(!empty($pRate->errMsg)) {
        echo "ERROR DE RATE: $pRate->errMsg <br>";
    }

	 $smarty->assign("module",$module_name);
	 $smarty->assign("horas",_tr('horas'));
	 $smarty->assign("minutos",_tr('minutos'));
	 $smarty->assign("segundos",_tr('segundos'));

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        default:
            $content = reportbilling_report($smarty, $module_name, $local_templates_dir, $pDBSet, $pDB,$pRate,$pDBTrunk,$pDBSQLite,$oCDR, $arrConf, $arrConfig);
            break;
    }
    return $content;
}

function reportbilling_report($smarty, $module_name, $local_templates_dir, &$pDBSet, &$pDB,&$pRate,&$pDBTrunk,&$pDBSQLite,&$oCDR, $arrConf, $arrConfig)
{
    $pbilling_report = new paloSantobilling_report($pDB);
    $filter_field     = getParameter("filter_field"); //combo
    $filter_value     = getParameter("filter_value"); //textfield
    $start_date_tmp   = getParameter("date_start");
    $end_date_tmp     = getParameter("date_end");
    $horas            = getParameter("horas");
    $minutos          = getParameter("minutos");
    $segundos         = getParameter("segundos");
    $action           = getParameter("nav");
    $start            = getParameter("start");

    $arrColumns = "";
    $hourToSec = "";
    $minToSec  = "";
    $style_time = "";
    $style_text = "";
    $filter_value_tmp = "";
    $time = 0;

    if($filter_field == 'duration'){
            $filter_value = "";
            if(isset($horas)){
                $hourToSec = $horas * 3600;
                $_POST['horas']  = $horas;
            }else{
                $hourToSec = 0;
                $_POST['horas']  = $hourToSec;
            }

            if(isset($minutos)){
                $minToSec = $minutos * 60;
                $_POST['minutos']  = $minutos;
            }else{
                $minToSec = 0;
                $_POST['minutos']  = $minToSec;
            }

            if(isset($segundos)){
                $_POST['segundos'] = $segundos;
            }else{
                $segundos = 0;
                $_POST['segundos'] = $segundos;
            }

        $time = $hourToSec + $minToSec + $segundos;
        $_POST['filter_value'] = $filter_value;
        $style_time = "style='display: block;'";
        $style_text = "style='display: none;'";
    }else{
        $horas = "";
        $minutos = "";
        $segundos = "";
        $_POST['horas']    = $horas;
        $_POST['minutos']  = $minutos;
        $_POST['segundos'] = $segundos;
        $_POST['filter_value'] = $filter_value;
        if($filter_field == "dst" || $filter_field == "rate_applied" || $filter_field == "dstchannel"){
            if($filter_value != "")
                $filter_value_tmp = $pDB->DBCAMPO('%'.$filter_value.'%');
            else
                $filter_value_tmp = "";
        }else{
            if($filter_value != "")
                $filter_value_tmp = $pDB->DBCAMPO($filter_value);
            else
                $filter_value_tmp = "";
        }
        $style_time = "style='display: none;'";
        $style_text = "style='display: block;'";
    }

    if(isset($start_date_tmp)){
        $start_date = translateDate($start_date_tmp)." 00:00:00";
        $_POST['date_start']  = $start_date_tmp;
    }else{
        $start_date = date("Y-m-d")." 00:00:00";
        $_POST['date_start']  = date("d M Y");
    }

    if(isset($end_date_tmp)){
        $end_date = translateDate($end_date_tmp)." 23:59:59";
        $_POST['date_end']  = $end_date_tmp;
    }else{
        $end_date = date("Y-m-d")." 23:59:59";
        $_POST['date_end']  = date("d M Y");
    }

    $smarty->assign("style_time",$style_time);
    $smarty->assign("style_text",$style_text);

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);

    $arrData = array();
    $arrData = null;
    $extension = "";
    $totalbilling_report = $pbilling_report->obtainNumReport($filter_field, $filter_value_tmp, $start_date, $end_date, $pDBSQLite, $time, "ANSWERED", "outgoing", $arrConfig);

    $url = array(
        'menu'          =>  $module_name,
        'filter_field'  =>  $filter_field,
        'filter_value'  =>  $filter_value,
        'date_start'    =>  $_POST['date_start'],
        'date_end'      =>  $_POST['date_end'],
        'horas'         =>  $horas,
        'minutos'       =>  $minutos,
        'segundos'      =>  $segundos,
    );
    $oGrid->enableExport();   // enable csv export.
    $oGrid->pagingShow(true); // show paging section.
    $oGrid->setTitle(_tr("Billing Report"));
    $oGrid->setNameFile_Export("Billing_Report");
    $oGrid->setURL($url);

    $arr_rates = $pbilling_report->getRates($pDBSQLite);

    if($oGrid->isExportAction()) {
        $limit  = $totalbilling_report;
        $offset = 0;
        $arrResult = $pbilling_report->obtainReport($limit, $offset, $filter_field, $filter_value_tmp, $start_date, $end_date, $pDBSQLite, $time, "ANSWERED", "outgoing", $arrConfig);

        $arrData = array();
	    // obteniendo tarifa default
	    $rates_default = $pbilling_report->getDefaultRate($pDBSQLite);
	    $rate = $rates_default['rate'];
	    $sum_cost = 0;
	    $rate_offset_default = $rates_default['rate_offset'];
	    if(is_array($arrResult) && $totalbilling_report>0){
		    foreach($arrResult as $key => $value){
			    $arrTmp[0] = $value['Date'];
                $hidden_digits = $value['digits'];

				if($value['Rate_applied'] == null){
                    $arrRateTmp = getRate($arr_rates, $value);
                    $value['Rate_applied'] = $arrRateTmp['Rate_applied'];
                    $value['Rate_value'] = $arrRateTmp['Rate_value'];
                    $value['Offset'] = $arrRateTmp['Offset'];
                    $hidden_digits = $arrRateTmp['digits'];
                }

                if($value['Rate_applied'] == null)
                    $rate_applied = _tr('default');
                else
                    $rate_applied = $value['Rate_applied'];
                $arrTmp[1] = $rate_applied;
                if($value['Rate_value'] == null)
                    $rate_value = $rate;
                else
                    $rate_value = $value['Rate_value'];
                if($value['Offset'] == null)
                    $rate_offset = $rate_offset_default;
                else
                    $rate_offset = $value['Offset'];

                if($hidden_digits == 0)
                    $destination = $value['Destination'];
                else{
                    $size_destination = strlen($value['Destination']);
                    if($hidden_digits < $size_destination){
                        $hide = getCharsAsterisk($hidden_digits);
                        $destination = substr($value['Destination'],0,-$hidden_digits).$hide;
                    }
                    else{
                        $size_destination = strlen($value['Destination']);
                        $destination = getCharsAsterisk($size_destination);
                    }
                }

			    $arrTmp[2] = $rate_value;
			    $arrTmp[3] = $value['Src'];
			    $arrTmp[4] = $destination;
			    $arrTmp[5] = $value['Dst_channel'];
                $arrTmp[6] = $value['accountcode'];

			    $iDuracion = $value['duration'];
                $iSec = $iDuracion % 60; $iDuracion = (int)(($iDuracion - $iSec) / 60);
                $iMin = $iDuracion % 60; $iDuracion = (int)(($iDuracion - $iMin) / 60);
                $sTiempo = $value['duration']."s";
                if ($value['duration'] >= 60) {
                      if ($iDuracion > 0) $sTiempo .= " ({$iDuracion}h {$iMin}m {$iSec}s)";
                      elseif ($iMin > 0)  $sTiempo .= " ({$iMin}m {$iSec}s)";
                }
                $arrTmp[7] = $sTiempo;

			    $charge=(($value['duration']/60)*$rate_value)+$rate_offset;
			    $arrTmp[8] = number_format($charge,3);
			    $sum_cost  = $sum_cost + $arrTmp[8];
			    $arrTmp[9] = $sum_cost;
			    $arrData[] = $arrTmp;
		    }
	    }
        $arrColumns  = array(_tr("Date"), _tr("Rate Applied"), _tr("Rate Value"), _tr("Source"), _tr("Destination"), _tr("Dst. Channel"),_tr("Account Code"),_tr("Duration"),_tr("Cost"),_tr("Summary Cost"));
    }else{
        $limit  = 20;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($totalbilling_report);
        $offset = $oGrid->calculateOffset();
        $arrResult = $pbilling_report->obtainReport($limit, $offset, $filter_field, $filter_value_tmp, $start_date, $end_date, $pDBSQLite, $time, "ANSWERED", "outgoing", $arrConfig);
        $arrData = array();
        // obteniendo tarifa default
        $rates_default = $pbilling_report->getDefaultRate($pDBSQLite);
        $rate = $rates_default['rate'];
        $sum_cost = 0;
        $rate_offset_default = $rates_default['rate_offset'];
        if(is_array($arrResult) && $totalbilling_report>0){
            foreach($arrResult as $key => $value){
                $arrTmp[0] = $value['Date'];
                $hidden_digits = $value['digits'];

                if($value['Rate_applied'] == null){
                    $arrRateTmp = getRate($arr_rates, $value);
                    $value['Rate_applied'] = $arrRateTmp['Rate_applied'];
                    $value['Rate_value'] = $arrRateTmp['Rate_value'];
                    $value['Offset'] = $arrRateTmp['Offset'];
                    $hidden_digits = $arrRateTmp['digits'];
                }

                if($value['Rate_applied'] == null)
                    $rate_applied = _tr('default');
                else
                    $rate_applied = $value['Rate_applied'];
                $arrTmp[1] = $rate_applied;
                if($value['Rate_value'] == null)
                    $rate_value = $rate;
                else
                    $rate_value = $value['Rate_value'];
                if($value['Offset'] == null)
                    $rate_offset = $rate_offset_default;
                else
                    $rate_offset = $value['Offset'];

                if($hidden_digits == 0)
                    $destination = $value['Destination'];
                else{
                    $size_destination = strlen($value['Destination']);
                    if($hidden_digits < $size_destination){
                        $hide = getCharsAsterisk($hidden_digits);
                        $destination = substr($value['Destination'],0,-$hidden_digits).$hide;
                    }
                    else{
                        $size_destination = strlen($value['Destination']);
                        $destination = getCharsAsterisk($size_destination);
                    }
                }

                $arrTmp[2] = $rate_value;
                $arrTmp[3] = $value['Src'];
                $arrTmp[4] = $destination;
                $arrTmp[5] = $value['Dst_channel'];
                $arrTmp[6] = $value['accountcode'];

                $iDuracion = $value['duration'];
                $iSec = $iDuracion % 60; $iDuracion = (int)(($iDuracion - $iSec) / 60);
                $iMin = $iDuracion % 60; $iDuracion = (int)(($iDuracion - $iMin) / 60);
                $sTiempo = $value['duration']."s";
                if ($value['duration'] >= 60) {
                      if ($iDuracion > 0) $sTiempo .= " ({$iDuracion}h {$iMin}m {$iSec}s)";
                      elseif ($iMin > 0)  $sTiempo .= " ({$iMin}m {$iSec}s)";
                }
                $arrTmp[7] = $sTiempo;

                $charge=(($value['duration']/60)*$rate_value)+$rate_offset;
                $arrTmp[8] = number_format($charge,3);
                $sum_cost  = $sum_cost + $arrTmp[8];
                $arrTmp[9] = $sum_cost;
                $arrData[] = $arrTmp;
            }
        }
        $arrColumns  = array(_tr("Date"), _tr("Rate Applied"), _tr("Rate Value"), _tr("Source"), _tr("Destination"), _tr("Dst. Channel"),_tr("Account Code"),_tr("Duration"),_tr("Cost"),_tr("Summary Cost"));
    }

    $oGrid->setColumns($arrColumns);
    $oGrid->setData($arrData);
    //begin section filter
    $arrFormFilterbilling_report = createFieldFilter();

    if($_POST['date_start']==="")
        $_POST['date_start']  = " ";

    if($_POST['date_end']==="")
        $_POST['date_end']  = " ";

    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Start Date")." = ".$_POST['date_start'].", "._tr("End Date")." = ".
    $_POST['date_end'], $_POST, array('date_start' => date("d M Y"),'date_end' => date("d M Y")),true);

    if(!is_null($filter_field)){
         $valueFilterField = $arrFormFilterbilling_report['filter_field']["INPUT_EXTRA_PARAM"][$filter_field];
    }else{
         $valueFilterField = "";
    }

    if(!is_null($filter_field)){
        if($filter_field=="duration"){
            $oGrid->addFilterControl(_tr("Filter applied: ").$valueFilterField." = ".$_POST['horas']."H:".$_POST['minutos']."M:".$_POST['segundos']."S",$_POST, array('filter_field' => "dst",'horas' => "","minutos" => "","segundos" => ""));
        }else{
            $oGrid->addFilterControl(_tr("Filter applied: ").$valueFilterField." = ".$filter_value,$_POST, array('filter_field' => "src",'filter_value' => ""));
        }
    }

    $oFilterForm = new paloForm($smarty, $arrFormFilterbilling_report);
    $smarty->assign("SHOW", _tr("Show"));

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    return $content;
}

function getCharsAsterisk($size)
{
    $result = "";
    for($i=0; $i<$size; $i++)
        $result .= "*";
    return $result;
}

function getRate($arr_ratesTmp, $arrRateValue)
{
    $arrResult = "";
    foreach($arr_ratesTmp as $key => $value){
        // primero comparamos si el registro tiene un rate activo
        $estado  = $value['estado'];
        $destino = $arrRateValue['Destination'];
        $prefix  = $value['prefix'];
        $arrResult['Rate_applied'] = null;
        $arrResult['Rate_value'] = null;
        $arrResult['Offset'] = null;
        $arrResult['digits'] = 0;
        if($estado == "activo"){ // es un rate activo
            //filtrando si se cumple el prefijo
            $cant = strlen($prefix);
            if($cant > 0){
                $val = substr($destino,0,$cant);
                if($val == $prefix){
                    $arrResult['Rate_applied'] = $value['name'];
                    $arrResult['Rate_value'] = $value['rate'];
                    $arrResult['Offset'] = $value['rate_offset'];
                    $arrResult['digits'] = $value['hided_digits'];
                    return $arrResult;
                }
            }
        }

    }
    return $arrResult;
}

function createFieldFilter(){
    $arrFilter = array(
        "rate_applied"  => _tr("Rate Applied"),
        "duration"      => _tr("Duration"),
	    "rate_value"    => _tr("Rate Value"),
	    "src"           => _tr("Source"),
	    "dst"           => _tr("Destination"),
	    "dstchannel"    => _tr("Dst. Channel"),
	    //"cost"          => _tr("Cost"),
        "accountcode"   => _tr("Account Code"),
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => _tr("Search"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "date_start"  => array("LABEL"                  => _tr("Start Date"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "DATE",
                                                    "INPUT_EXTRA_PARAM"      => "",
                                                    "VALIDATION_TYPE"        => "ereg",
                                                    "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
            "date_end"    => array("LABEL"                  => _tr("End Date"),
                                   "REQUIRED"               => "yes",
                                   "INPUT_TYPE"             => "DATE",
                                   "INPUT_EXTRA_PARAM"      => "",
                                   "VALIDATION_TYPE"        => "ereg",
                                   "VALIDATION_EXTRA_PARAM" => "^[[:digit:]]{1,2}[[:space:]]+[[:alnum:]]{3}[[:space:]]+[[:digit:]]{4}$"),
                "horas"   => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("size" => "2", "maxlength" => "2", "onkeypress" => "return onlyNumbers(event)"),
                                    "VALIDATION_TYPE"        => "text",),
                "minutos" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("size" => "2", "maxlength" => "2", "onkeypress" => "return onlyNumbers(event)"),
                                    "VALIDATION_TYPE"        => "text",),
                "segundos" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("size" => "2", "maxlength" => "2", "onkeypress" => "return onlyNumbers(event)"),
                                    "VALIDATION_TYPE"        => "text",),
                    );
    return $arrFormElements;
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("new_open"))
        return "view_form";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_form";
    else
        return "report"; //cancel
}
?>
