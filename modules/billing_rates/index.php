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
  $Id: index.php,v 1.1 2010-01-27 02:01:42 Eduardo Cueva ecueva@palosanto.com Exp $ */
//include issabel framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoTrunk.class.php";
include_once "libs/paloSantoRate.class.php";
include_once "libs/misc.lib.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoBillingRates.class.php";

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
    $pDB  = new paloDB($arrConf['dsn_conn_database']);   // connection with rate.db
    $pDB2 = new paloDB($arrConf['dsn_conn_database2']);  // connection with trunk.db
	$pDB3 = new paloDB($arrConf['dsn_conn_database3']);  // connection with settings.db


    //actions
    $action = getAction();
    $content = "";
    switch($action){
		case "new_rate":
			    $content = reportBillingNewRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                break;
		case "import_rate":
				$content = reportBillingImportRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                break;
        case "view_form":
			    $content = reportBillingViewRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
				break;
        case "edit_form":
			    $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
				break;
        case "save_new":
                $content = obtainResultOperationRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                break;
        case "delete":
                $content = obtainResultOperationRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                break;
        case "save_import":
                $content = obtainResultOperationRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                break;
        case "save_edit":
                $content = obtainResultOperationRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                break;
        default:
            $content = reportBillingRates($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
            break;
    }
    return $content;
}

function reportBillingRates($smarty, $module_name, $local_templates_dir, &$pDB, &$pDB2, &$pDB3, $arrConf)
{
    $pBillingRates = new paloSantoBillingRates($pDB);
    $action = getParameter("nav");
    $start  = getParameter("start");
    $as_csv = getParameter("exportcsv");
    $arrResult  = "";
    $arrColumns = "";
	 //obtain parameters from new rates
	$prefix_new      = getParameter("Prefix");
	$name_new        = getParameter("Name");
	$rate_new        = getParameter("Rate");
	$rate_offset_new = getParameter("Rate_offset");
	$trunk_new       = getParameter("Trunk");
	$hidden_digits   = getParameter("Hidden_Digits");
	$id              = getParameter("id");


	 	 //exists Default rate in rate.db // actualizar los rates por defecto en settings
    $cant = $pBillingRates->contRates();
    if(isset($cant['cant']) & $cant['cant'] < 1)
	    $pBillingRates->existsDefaultRate($pDB3);

	$action = getAction();

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $totalBillingRates = $pBillingRates->getNumBillingRates();

    $url = array(
        'menu' =>  $module_name,
    );
    //$oGrid->enableExport();   // enable csv export.
    $oGrid->pagingShow(true); // show paging section.
    $oGrid->setTitle(_tr("Billing Rates"));
    $oGrid->setIcon("modules/$module_name/images/reports_billing_rates.png");
    $oGrid->setNameFile_Export("Billing_Rates");
    $oGrid->setURL($url);
    $oGrid->addNew("new_rate",_tr("create_rate"));

    $smarty->assign("module_name", $module_name);

    $arrData = null;

    if($oGrid->isExportAction()) {
        $limit  = $totalBillingRates;
        $offset = 0;
        $arrResult = $pBillingRates->getBillingRates($limit, $offset);
        if(is_array($arrResult) && $totalBillingRates>0){
            foreach($arrResult as $key => $value){
                $arrTmp[0] = (isset($value['prefix'])&$value['prefix']!="")?$value['prefix']:"*";
                $arrTmp[1] = (isset($value['name'])&$value['name']!="")?$value['name']:"-";
                $arrTmp[2] = (isset($value['rate'])&$value['rate']!="")?$value['rate']:"-";
                $arrTmp[3] = (isset($value['rate_offset'])&$value['rate_offset']!="")?$value['rate_offset']:"-";
                $arrTmp[4] = (isset($value['hided_digits'])&$value['hided_digits']!="")?$value['hided_digits']:"-";
                $arrTmp[5] = (isset($value['trunk'])&$value['trunk']!="")?$value['trunk']:"*";
                $arrTmp[6] = (isset($value['fecha_creacion'])&$value['fecha_creacion']!="")?$value['fecha_creacion']:"-";
                $arrData[] = $arrTmp;
            }
        }
        $arrColumns  = array(_tr("Prefix"), _tr("Name"), _tr("Rate"), _tr("Rate Offset"), _tr("Hidden_Digits"), _tr("Trunk"),_tr("Creation Date"));
    }else{
        $limit  = 20;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($totalBillingRates);
        $offset = $oGrid->calculateOffset();
        $arrResult = $pBillingRates->getBillingRates($limit, $offset);
        if(is_array($arrResult) && $totalBillingRates>0){
            foreach($arrResult as $key => $value){
			    if($value['name']=="Default"){
				    $default = _tr('Default');
				    $arrTmp[0] = "<font color='green'>*</font>";
				    $arrTmp[1] = "<font color='green'>".$default."</font>";
				    $arrTmp[2] = "<font color='green'>".$value['rate']."</font>";
				    $arrTmp[3] = "<font color='green'>".$value['rate_offset']."</font>";
				    $arrTmp[4] = "<font color='green'>".$value['hided_digits']."</font>";
				    $arrTmp[5] = "<font color='green'>*</font>";
				    $arrTmp[6] = "<font color='green'>".$value['fecha_creacion']."</font>";
				    $arrTmp[7] = "<a href='?menu=$module_name&action=view&id=".$value['id']."'>"._tr("View")."</a>";
			    }else{
				    $arrTmp[0] = $value['prefix'];
				    $arrTmp[1] = $value['name'];
				    $arrTmp[2] = $value['rate'];
				    $arrTmp[3] = $value['rate_offset'];
				    $arrTmp[4] = $value['hided_digits'];
				    $arrTmp[5] = $value['trunk'];
				    $arrTmp[6] = $value['fecha_creacion'];
				    $arrTmp[7] = "<a href='?menu=$module_name&action=view&id=".$value['id']."'>"._tr("View")."</a>";
			    }
                $arrData[] = $arrTmp;
            }
        }

        // arreglo de columnas
        $arrColumns  = array(_tr("Prefix"), _tr("Name"), _tr("Rate"), _tr("Rate Offset"), _tr("Hidden_Digits"), _tr("Trunk"),_tr("Creation Date"),_tr("View"));
    }

    $oGrid->setColumns($arrColumns);
    $oGrid->setData($arrData);

    //begin section filter
    $arrFormFilterBillingRates = createFieldFilter();
    $oFilterForm = new paloForm($smarty, $arrFormFilterBillingRates);
    $smarty->assign("import_rate", _tr("import_rate"));
    $smarty->assign("by_min",_tr("by_min"));
    $smarty->assign("Date_close",_tr("Date close"));
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter
    $oGrid->customAction("import_rate",_tr("import_rate"));

    $content = $oGrid->fetchGrid();

    return $content;
}

function reportBillingNewRate($smarty, $module_name, $local_templates_dir, &$pDB, &$pDB2, &$pDB3, $arrConf){
    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
	 $pBillingRates = new paloSantoBillingRates($pDB);
	 $currency = $pBillingRates->getCurrency($pDB3);
	 if($currency == null || $currency == ""){
      $currency = "$";
	 }
	 $arrTrunks = $pBillingRates->getTrunks($pDB2); //obtain the trunks for billing
	 $arrForm = createFormNew($arrTrunks);
    $oForm = new paloForm($smarty,$arrForm);

	 $smarty->assign("CANCEL", _tr("CANCEL"));
	 $smarty->assign("SAVE", _tr("SAVE"));
	 $smarty->assign("REQUIRED_FIELD", _tr("REQUIRED_FIELD"));
	 $smarty->assign("EDIT", _tr("EDIT"));
    $smarty->assign("DELETE", _tr("DELETE"));
	 $smarty->assign("module_name", $module_name);
    $smarty->assign("currency", $currency);
    $smarty->assign("by_min",_tr("by_min"));
    $smarty->assign("Date_close",_tr("Date close"));

    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_rate.tpl",_tr("New_rate"), $_DATA);
    $content  = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $content;

}

function reportBillingEditRate($smarty, $module_name, $local_templates_dir, &$pDB, &$pDB2, &$pDB3, $arrConf){
	 //begin, Form data persistence to errors and other events.
     $_DATA  = $_POST;
     $id = getParameter("id");
	 $pBillingRates = new paloSantoBillingRates($pDB);
	 $arrData = $pBillingRates->getBillingRatesById($id); //obtain the rate
	 $arrTrunks = $pBillingRates->getTrunks($pDB2); //obtain the trunks for billing
	 $arrForm = createEditForm($arrTrunks);
     $oForm = new paloForm($smarty,$arrForm);
	 $currency = $pBillingRates->getCurrency($pDB3);
	 if($currency == null || $currency == ""){
        $currency = "$";
	 }
	 $_DATA['Name'] = $arrData['name'];
	 $_DATA['Rate'] = $arrData['rate'];
	 $_DATA['Rate_offset'] = $arrData['rate_offset'];
	 $_DATA['Hidden_Digits'] = $arrData['hided_digits'];
	 $_DATA['Trunk'] = $arrData['trunk'];

	 $smarty->assign("CANCEL", _tr("CANCEL"));
	 $smarty->assign("APPLY_CHANGES", _tr("APPLY_CHANGES"));
	 $smarty->assign("REQUIRED_FIELD", _tr("REQUIRED_FIELD"));
	 $smarty->assign("EDIT", _tr("EDIT"));
	 $smarty->assign("module_name", $module_name);
	 $smarty->assign("prefix", $arrData['prefix']);
	 $smarty->assign("rate", $arrData['rate']);
	 $smarty->assign("creation_date", $arrData['fecha_creacion']);
	 $smarty->assign("name", $arrData['name']);
	 $smarty->assign("rate_offset", $arrData['rate_offset']);
     $smarty->assign("trunk", $arrData['trunk']);
	 $smarty->assign("currency", $currency);
     $smarty->assign("by_min",_tr("by_min"));
     $smarty->assign("Date_close",_tr("Date close"));
     $smarty->assign("Creation_Date",_tr("Creation Date"));
     $smarty->assign("History",_tr("Rate History"));
     $smarty->assign("Status",_tr("Status"));
     $smarty->assign("Obsolete",_tr("Obsolete"));
     $smarty->assign("Current",_tr("Current"));
     $smarty->assign("text_info",_tr("This option allow to create a new rate and keep the history of their rates, or if not only edit the current rate values."));
     $arrRates = $pBillingRates->getRatesPast($id);
     $arrRates = setEmptySpaces($arrRates);
     $smarty->assign("arrRates",$arrRates);

     $htmlForm = $oForm->fetchForm("$local_templates_dir/edit_rate.tpl",_tr("Edit Rate"), $_DATA);
     $content  = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name&id=$id&namerate=".$arrData['name']."'>".$htmlForm."</form>";
     return $content;
//
}

function reportBillingImportRate($smarty, $module_name, $local_templates_dir, &$pDB, &$pDB2, &$pDB3, $arrConf){
	$_DATA  = $_POST;
	$arrForm = createImportForm();
	$oForm = new paloForm($smarty,$arrForm);

	$smarty->assign("CANCEL", _tr("CANCEL"));
	$smarty->assign("SAVE", _tr("SAVE"));
	$smarty->assign("REQUIRED_FIELD", _tr("REQUIRED_FIELD"));
	$smarty->assign("module_name", $module_name);
    $smarty->assign("by_min",_tr("by_min"));
    $smarty->assign("alert_import",_tr("alert_import"));
	$htmlForm = $oForm->fetchForm("$local_templates_dir/import_rate.tpl",_tr("import_rate"), $_DATA);
    $content  = "<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    return $content;

}

function reportBillingViewRate($smarty, $module_name, $local_templates_dir, &$pDB, &$pDB2, &$pDB3, $arrConf){
    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $id = getParameter("id");
	$pBillingRates = new paloSantoBillingRates($pDB);
	$arrData = $pBillingRates->getBillingRatesById($id); //obtain the rate
	$arrTrunks = $pBillingRates->getTrunks($pDB2);
	$arrForm = createViewForm($arrTrunks);
    $oForm = new paloForm($smarty,$arrForm);
	 $currency = $pBillingRates->getCurrency($pDB3);
	 if($currency == null || $currency == ""){
      $currency = "$";
	 }
	 if($arrData['trunk'] == null || $arrData['trunk'] == "")
		$trunk = "*";
	 else		$trunk = $arrData['trunk'];

	 if($arrData['prefix'] == null || $arrData['prefix'] == "")
		$prefix = "*";
	 else		$prefix = $arrData['prefix'];
	 $smarty->assign("CANCEL", _tr("CANCEL"));
	 $smarty->assign("SAVE", _tr("SAVE"));
	 $smarty->assign("REQUIRED_FIELD", _tr("REQUIRED_FIELD"));
	 $smarty->assign("EDIT", _tr("EDIT"));
     $smarty->assign("DELETE", _tr("DELETE"));
	 $smarty->assign("module_name", $module_name);
	 $smarty->assign("prefix", $prefix);
	 $smarty->assign("rate", $arrData['rate']);
	 $smarty->assign("creation_date", $arrData['fecha_creacion']);
	 $smarty->assign("name", $arrData['name']);
	 $smarty->assign("rate_offset", $arrData['rate_offset']);
     $smarty->assign("trunk", $trunk);
	 $smarty->assign("hidden_digits", $arrData['hided_digits']);
	 $smarty->assign("CONFIRM_CONTINUE", _tr('CONFIRM_CONTINUE'));
	 $smarty->assign("currency", $currency);
     $smarty->assign("by_min",_tr("by_min"));
     $smarty->assign("Date_close",_tr("Date close"));

    $htmlForm = $oForm->fetchForm("$local_templates_dir/view_rate.tpl",_tr("View Rate"), $_DATA);
    $content  = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name&id=$id'>".$htmlForm."</form>";
    return $content;

}

function obtainResultOperationRate($smarty, $module_name, $local_templates_dir, &$pDB, &$pDB2, &$pDB3, $arrConf){
    $_DATA  = $_POST;
    $pBillingRates = new paloSantoBillingRates($pDB);

     //obtain parameters from new rates
     $prefix_new      = getParameter("Prefix");
     $name_new        = getParameter("Name");
     $rate_new        = getParameter("Rate");
     $rate_offset_new = getParameter("Rate_offset");
     $trunk_new       = getParameter("Trunk");
     $hidden_digits   = getParameter("Hidden_Digits");
     $id              = getParameter("id");
     $edit            = getParameter("namerate");
     $varUpdate       = getParameter("checkUpdate");
         //exists Default rate in rate.db
     $pBillingRates->existsDefaultRate($pDB3);

     $action = getAction();
     //into to create new rate
     if($action=="save_new"){
		$arrTrunks = $pBillingRates->getTrunks($pDB2);
		$arrFormNew = createFormNew($arrTrunks);
		$oForm = new paloForm($smarty,$arrFormNew);

		if(!$oForm->validateForm($_POST)) {
			$strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
			$arrErrores = $oForm->arrErroresValidacion;
			if(is_array($arrErrores) && count($arrErrores) > 0){
				foreach($arrErrores as $k=>$v) {
					$strErrorMsg .= "$k: [$v[mensaje]] <br /> ";
				}
			}
			$smarty->assign("mb_message", $strErrorMsg);
			$content=reportBillingNewRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
			return $content;
		}else{
			$result = create_rate($pBillingRates,$prefix_new,$name_new,$rate_new,$rate_offset_new,$trunk_new,$hidden_digits);
			if($result == "name"){
				$smarty->assign("mb_message", _tr("error_name"));
				$content=reportBillingNewRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
				return $content;
			}
			if($result == "successful"){
				$smarty->assign("mb_message", _tr("create_new_rate"));
				$content = reportBillingRates($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
				return $content;
			}
			if($result == "prefix"){
				$smarty->assign("mb_message", _tr("error_prefix"));
				$content=reportBillingNewRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
				return $content;
			}
			if($result == "error"){
				$smarty->assign("mb_message", _tr("error"));
				$content=reportBillingNewRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
				return $content;
			}
		}
    }
    //into to delete rate
    if($action=="delete"){
         $result = $pBillingRates->deleteRate($id);
         if($result == true){
             $smarty->assign("mb_message", _tr("deleted"));
             $content = reportBillingRates($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
             return $content;
         }
         else{
             $smarty->assign("mb_message", _tr("deleted_error"));
             $content = reportBillingViewRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
             return $content;
         }
     }

     if($action=="save_edit"){
		  $arrTrunks = $pBillingRates->getTrunks($pDB2);
		  $arrFormEdit = createEditForm($arrTrunks);
		  $oForm = new paloForm($smarty,$arrFormEdit);
		  if($edit == 'Default'){
			  $trunk_new = isset($trunk_new)?$trunk_new:"";
			  $prefix_new = isset($prefix_new)?$prefix_new:"";
			  $name_new = isset($name_new)?$name_new:"Default";
		  }
		  if(!$oForm->validateForm($_POST)) {
			  $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
			  $arrErrores = $oForm->arrErroresValidacion;
			  if(is_array($arrErrores) && count($arrErrores) > 0){
				  foreach($arrErrores as $k=>$v) {
					  $strErrorMsg .= "$k: [$v[mensaje]] <br /> ";
				  }
			  }
			  //into to edit rate but the field are empty
			  $smarty->assign("mb_message", $strErrorMsg);
			  $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
			  return $content;
		  }else{
			  if($varUpdate == "on"){
				  $rateOLD = $pBillingRates->getBillingRatesById($id);
				  $prefix_new = $rateOLD['prefix'];
				  $pDB->beginTransaction();
				  $result = update_Last_rates($pBillingRates,$prefix_new,$name_new,$rate_new,$rate_offset_new,$trunk_new,$hidden_digits);// crea el rate nuevo
				  if($result == "successful"){
					  $id_new = $pDB->getLastInsertId();
					  $result = $pBillingRates->updateIdParent($id, $id_new);// actualizamos los hijos anteriores al rate
					  if($result){
						  $result = $pBillingRates->deleteRate($id); // cerrando el anterior rate valido
						  if($name_new == "Default"){
							  $result = $pBillingRates->updateSettingRate($rate_new,$rate_offset_new, $pDB3);
							  if($result){
								  $pDB->commit();
								  $smarty->assign("mb_message", _tr("edit_rate"));
								  $content = reportBillingRates($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
								  return $content;
							  }else{
								  $pDB->rollBack();
								  $smarty->assign("mb_message", _tr("errorUpdateParent"));
								  $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
								  return $content;
							  }
						  }else{
							  $pDB->commit();
							  $smarty->assign("mb_message", _tr("edit_rate"));
							  $content = reportBillingRates($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
							  return $content;
						  }
					  }
					  $pDB->rollBack();
					  $smarty->assign("mb_message", _tr("errorUpdateParent"));
					  $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
					  return $content;

				  }
				  if($result == "error"){
					  $smarty->assign("mb_message", _tr("error"));
					  $pDB->rollBack();
					  $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
					  return $content;
				  }
			  }else{
				  $pDB->beginTransaction();
				  $result = edit_rate($pBillingRates,$id,$name_new,$rate_new,$rate_offset_new,$trunk_new,$hidden_digits);
				  if($result == "prefix"){
					  $smarty->assign("mb_message", _tr("error_prefix"));
					  $pDB->rollBack();
					  $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
					  return $content;
				  }
				  if($result == "name"){
					  $smarty->assign("mb_message", _tr("error_name"));
					  $pDB->rollBack();
					  $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
					  return $content;
				  }
				  if($result == "successful"){
					  if($name_new == "Default"){
						  $result = $pBillingRates->updateSettingRate($rate_new,$rate_offset_new, $pDB3);
						  if($result){
							  $pDB->commit();
							  $smarty->assign("mb_message", _tr("edit_rate"));
							  $content = reportBillingRates($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
							  return $content;
						  }else{
							  $pDB->rollBack();
							  $smarty->assign("mb_message", _tr("errorUpdateParent"));
							  $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
							  return $content;
						  }
					  }else{
						  $pDB->commit();
						  $smarty->assign("mb_message", _tr("edit_rate"));
						  $content = reportBillingRates($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
						  return $content;
					  }
				  }
				  if($result == "error"){
					  $smarty->assign("mb_message", _tr("error"));
					  $pDB->rollBack();
					  $content = reportBillingEditRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
					  return $content;
				  }
			  }
		  }
     }
     if($action=="save_import"){
        $arrErrorMsg = "";
        if(!preg_match('/.*\.csv$/', $_FILES['importcsv']['name'])){
            $smarty->assign("mb_message", _tr("Invalid_file_extension"));
            $content = reportBillingImportRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
            return $content;
        }else{
            if(is_uploaded_file($_FILES['importcsv']['tmp_name']))
            {
                $arrTrunks = $pBillingRates->getTrunks($pDB2); //obtain the trunks for billing
                $arrForm = createFormNew($arrTrunks);
                $oForm = new paloForm($smarty,$arrForm);

                //$count=0;
                $row = 1;
                if ($handle = fopen($_FILES['importcsv']['tmp_name'], "r")) {
                    $rate_val = fgetcsv($handle, 4096);
                    //Linea 1 header ignorada
                    //Desde linea 2 son datos
					$pDB->beginTransaction();
                    while (($rate_val = fgetcsv($handle, 4096)) !== FALSE) {
                        $record=array('Prefix'        => trim($rate_val[0]),
                                      'Name'          => trim($rate_val[1]),
                                      'Rate'          => trim($rate_val[2]),
                                      'Rate_offset'   => trim(($rate_val[3]==0?'0.0':$rate_val[3])),
                                      'Hidden_Digits' => trim($rate_val[4]),
                                      'Trunk'         => trim($rate_val[5]));
                        if($oForm->validateForm($record))
                        {
                            $dig = $rate_val[4];
                            if($rate_val[4]=="")
                                $dig=0;
                            $result = create_rate($pBillingRates,trim($rate_val[0]),trim($rate_val[1]),trim($rate_val[2]),trim($rate_val[3]),trim($rate_val[5]),$dig);
                            if($result == "name"){
								$pDB->rollBack();
                                $smarty->assign("mb_message", _tr("Error rate name already exists in database or is duplicated in csv file"));
                                $content = reportBillingImportRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                                return $content;
                            }
                            if($result == "prefix"){
								$pDB->rollBack();
                                $smarty->assign("mb_message", _tr("The prefix already exists with the same Trunk in database or is duplicated in csv file"));
                                $content = reportBillingImportRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                                return $content;
                            }
                            if($result == "error"){
								$pDB->rollBack();
                                $smarty->assign("mb_message", _tr("error_CVS"));
                                $content = reportBillingImportRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                                return $content;
                            }
                        }else
                            $arrErrorMsg[_tr("Validation Error")][$count] = $oForm->arrErroresValidacion;
					}
                    fclose($handle);
                }
                if($arrErrorMsg!=""){
					$pDB->rollBack();
                    foreach ($arrErrorMsg as $Error_type => $on_line)
                    {
                        $strErrorMsg.= "<B><font color=\"red\">".$Error_type.":</font></B><BR>";
                        foreach ($on_line as $line=>$error_msg)
                        {
                                if (is_array($error_msg)) foreach ($error_msg as $k=>$msg)
                                {
                                    if (!is_array($msg)) $error_msg=$msg;
                                    else foreach ($msg as $v) $error_msg= $k." has ".$v;
                                }
                                    $strErrorMsg.= _tr("Error on line").": ". $line."  ".$error_msg."<br>";
                        }
                        $strErrorMsg.='<BR>';
                    }
                    if($strErrorMsg!=""){
                        $smarty->assign("mb_message", $strErrorMsg);
                        $content = reportBillingImportRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                        return $content;
                    }
                }else{
					$pDB->commit();
					$smarty->assign("mb_message", _tr("File was imported successful"));
					$content = reportBillingRates($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
					return $content;
				}
            }else{
                $smarty->assign("mb_message", _tr("File_error"));
                $content = reportBillingImportRate($smarty, $module_name, $local_templates_dir, $pDB, $pDB2, $pDB3, $arrConf);
                return $content;
            }
        }
     }
}

function createFieldFilter(){
    $arrFormElements = array();
    return $arrFormElements;
}

function create_rate($pBillingRates,$prefix_new,$name_new,$rate_new,$rate_offset_new,$trunk_new,$hidden_digits){
	 //validar que el prefix_new sea un numero y no exista en base
	 $val = existPrefix($pBillingRates,$prefix_new,$trunk_new);
     $val2= existName($pBillingRates,$name_new);
     $date_ini = date("Y-m-d H:i:s");
     if($val2==true)
        return "name";
	 if($val==true)
	 	return "prefix"; //prefix exist
	 else{
		$val = $pBillingRates->createRate($prefix_new,$name_new,$rate_new,$rate_offset_new,$trunk_new,$date_ini,$hidden_digits);
		if($val == true)    return "successful";
		else    return "error";
	 }
}

function update_Last_rates($pBillingRates,$prefix_new,$name_new,$rate_new,$rate_offset_new,$trunk_new,$hidden_digits){
     $date_ini = date("Y-m-d H:i:s");
     $val = $pBillingRates->createRate($prefix_new,$name_new,$rate_new,$rate_offset_new,$trunk_new,$date_ini,$hidden_digits);
     if($val == true)    return "successful";
     else    return "error";
}

function edit_rate($pBillingRates,$id,$name_new,$rate_new,$rate_offset_new,$trunk_new,$hidden_digits){

    $rate_register = $pBillingRates->getBillingRatesById($id);
    if($trunk_new != $rate_register['trunk']){
        $val3 = existPrefix($pBillingRates,$rate_register['prefix'],$trunk_new);
        if($val3 == true)   return "prefix";
    }
    if($name_new != $rate_register['name']){
        $val2 = existName($pBillingRates, $name_new);
        if($val2 == true)   return "name";
    }
    $val = $pBillingRates->editRate($id,$name_new,$rate_new,$rate_offset_new,$trunk_new,$hidden_digits);

	if($val == true)	return "successful";
	else    return "error";
}

// (possible) if exist only one combination of prefix with trunk
// (possible) if exist one or more prefix but trunks differents
// (not possible) if exist two or more combinations of prefix, trunk
function existPrefix($pBillingRates, $prefix_new, $trunk_new){
	 $arrRates = $pBillingRates->getBillingALLRates();
     $prefix_new .=" ";
	 for($i=0; $i<count($arrRates); $i++){
		 $prefix = $arrRates[$i]['prefix'];
         $trunk = $arrRates[$i]['trunk'];
		 $prefix .= " ";
		 if($prefix_new == $prefix && $trunk_new == $trunk)
			return true;
	 }
	 return false;
}

function existName($pBillingRates, $name_new){
    $arrRates = $pBillingRates->getBillingALLRates();
    $name = "";
     for($i=0; $i<count($arrRates); $i++){
         $name = ($arrRates[$i]['name']);
         if($name_new == $name)
            return true;
     }
     return false;
}

function getHiddenDigits(){
	$arrDigits = "";
	for($i=0; $i<11; $i++){
		$arrDigits[$i] = $i;
	}
	return $arrDigits;
}

function createFormNew($arrTrunks){

	$arrDigits = getHiddenDigits();

   $arrFields = array(
            "Prefix"   => array(      "LABEL"                  => _tr("Prefix"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "Rate"   => array(      "LABEL"                  => _tr("Rate"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
				"Name"   => array(      "LABEL"                  => _tr("Name"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "Rate_offset"   => array(      "LABEL"                  => _tr("Rate_offset"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
				"Trunk" => array("LABEL"                  => _tr("Trunk"),
                                    "REQUIRED"               => "yes",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrTrunks,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""
														  ),
				"Hidden_Digits"   => array(      "LABEL"                  => _tr("Hidden_Digits"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrDigits,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            );
    return $arrFields;
}

function createImportForm(){
	$arrFields = array(
					"importcsv"    => array("LABEL"                  =>	_tr("import_rate"),
													"REQUIRED"               => "yes",
													"INPUT_TYPE"             => "FILE",
													"INPUT_EXTRA_PARAM"      => "",
													"VALIDATION_TYPE"        => "filename",
													"VALIDATION_EXTRA_PARAM" => ""),
	);
   return $arrFields;
}

function setEmptySpaces($arrRate){
    for($i=0; $i<count($arrRate); $i++){
        if($arrRate[$i]['name']=='Default'){
            $arrRate[$i]['trunk'] = "*";
            $arrRate[$i]['fecha_cierre'] = isset($arrRate[$i]['fecha_cierre'])?$arrRate[$i]['fecha_cierre']:"-";
        }else{
            $arrRate[$i]['fecha_cierre'] = isset($arrRate[$i]['fecha_cierre'])?$arrRate[$i]['fecha_cierre']:"-";
        }
    }
    return $arrRate;
}

function createEditForm($arrTrunks){

	$arrDigits = getHiddenDigits();

	$arrFields = array(
				  "Prefix"   => array(      "LABEL"                  => _tr("Prefix"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
                    "Rate"   => array(      "LABEL"                  => _tr("Rate"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
				    "Name"   => array(      "LABEL"                  => _tr("Name"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
             "Rate_offset"   => array(      "LABEL"                  => _tr("Rate_offset"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
		   "Hidden_Digits"   => array(      "LABEL"                  => _tr("Hidden_Digits"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrDigits,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
				"Trunk"     => array(       "LABEL"                  => _tr("Trunk"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrTrunks,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
											),
            "checkUpdate"   => array(       "LABEL"                  => _tr("Keep history of the current rate"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "",
                                            "EDITABLE"               => "yes",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                                                        ),
	);
   return $arrFields;
}

function createViewForm($arrTrunks){
	$arrFields = array(
					"Prefix"   => array(      "LABEL"                  => _tr("Prefix"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "Rate"   => array(      "LABEL"                  => _tr("Rate"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
				"Name"   => array(      "LABEL"                  => _tr("Name"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "Rate_offset"   => array(      "LABEL"                  => _tr("Rate_offset"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
				"Hidden_Digits"   => array(      "LABEL"                  => _tr("Hidden_Digits"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
				"Trunk" => array("LABEL"                  => _tr("Trunk"),
                                    "REQUIRED"               => "yes",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrTrunks,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""
														  ),
				"Creation_Date" => array("LABEL"                  => _tr("Creation Date"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""
														  ),
	);
   return $arrFields;
}

function getAction()
{
    if(getParameter("submit_apply_changes"))
        return "save_edit";
    else if(getParameter("edit"))
        return "edit_form";
    else if(getParameter("delete"))
        return "delete";
    else if(getParameter("submit_save_rate"))
        return "save_new";
    else if(getParameter("cancel"))
        return "cancel";
    else if(getParameter("action")=="view")
        return "view_form";
    else if(getParameter("submit_import_changes"))
        return "save_import";
	 else if(getParameter("new_rate"))
        return "new_rate";
	 else if(getParameter("import_rate"))
        return "import_rate";
    else
        return "report"; //cancel
}
?>
