<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class PriceBooks_ProductPriceBookPopup_View extends Vtiger_Popup_View
{

	public function process(\App\Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('TRIGGER_EVENT_NAME', $request->getByType('triggerEventName', 2));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$viewer->view('ProductPriceBookPopup.tpl', 'PriceBooks');
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\App\Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getByType('src_module', 2);
		$jsFileNames = [
			"modules.PriceBooks.resources.PriceBooksPopup",
			'~layouts/resources/validator/BaseValidator.js',
			'~layouts/resources/validator/FieldValidator.js',
			"modules.$moduleName.resources.validator.FieldValidator"
		];
		return array_merge($headerScriptInstances, $this->checkAndConvertJsScripts($jsFileNames));
	}
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */

	public function initializeListViewContents(\App\Request $request, Vtiger_Viewer $viewer)
	{
		$moduleName = $request->getModule();
		$cvId = $request->getByType('cvid', 2);
		$pageNumber = $request->getInteger('page');
		$orderBy = $request->getForSql('orderby');
		$sortOrder = $request->getForSql('sortorder');
		$sourceModule = $request->getByType('src_module', 2);
		$sourceField = $request->getByType('src_field', 2);
		$sourceRecord = $request->getInteger('src_record');
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');

		if (empty($cvId)) {
			$cvId = 0;
		}
		if (empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$listViewModel = Vtiger_ListView_Model::getInstanceForPopup($moduleName, $sourceModule);

		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel);
		if (empty($orderBy) && empty($sortOrder)) {
			$moduleInstance = CRMEntity::getInstance($moduleName);
			$orderBy = $moduleInstance->default_order_by;
			$sortOrder = $moduleInstance->default_sort_order;
		}
		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}

		$productPriceDetails = [];
		if (!empty($sourceModule)) {
			$listViewModel->set('src_module', $sourceModule);
			$listViewModel->set('src_field', $request->get('src_field'));
			$listViewModel->set('src_record', $sourceRecord);
			$sourceRecordModel = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
			//get the unit prices for the pricebooks based on the product currency
			$productUnitPrice = $sourceRecordModel->get('unit_price');
			$productPriceDetails = $sourceRecordModel->getPriceDetailsForProduct($sourceRecord, $productUnitPrice, 'available', $sourceModule);
		}
		if ((!empty($searchKey)) && (!empty($searchValue))) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}

		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if (!$this->listViewEntries) {
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}
		$productCurrencyPrice = [];
		foreach ($productPriceDetails as $priceDetails) {
			$productCurrencyPrice[$priceDetails['curid']] = $priceDetails['curvalue'];
		}
		foreach ($this->listViewEntries as $recordModel) {
			$recordModel->set('unit_price', isset($productCurrencyPrice[$recordModel->get('currency_id')]) ? $productCurrencyPrice[$recordModel->get('currency_id')] : null);
		}

		$noOfEntries = count($this->listViewEntries);

		if (empty($sortOrder)) {
			$sortOrder = "ASC";
		}
		if ($sortOrder == "ASC") {
			$nextSortOrder = "DESC";
			$sortImage = "downArrowSmall.png";
		} else {
			$nextSortOrder = "ASC";
			$sortImage = "upArrowSmall.png";
		}

		$viewer->assign('MODULE', $moduleName);

		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('SOURCE_FIELD', $sourceField);
		$viewer->assign('SOURCE_RECORD', $sourceRecord);
		$viewer->assign('SOURCE_RECORD_MODEL', $sourceRecordModel);
		//PARENT_MODULE is used for only translations
		$viewer->assign('PARENT_MODULE', 'PriceBooks');

		$viewer->assign('SEARCH_KEY', $searchKey);
		$viewer->assign('SEARCH_VALUE', $searchValue);

		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
		$viewer->assign('SORT_IMAGE', $sortImage);

		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());

		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);

		$viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);

		$viewer->assign('VIEW', 'ProductPriceBookPopup');
	}
}
