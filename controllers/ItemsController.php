<?php
/**
 * Omeka
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Omeka\Controller
 */
class ItemDuplicator_ItemsController extends Omeka_Controller_AbstractActionController
{
	protected $_autoCsrfProtection = true;

	protected $_browseRecordsPerPage = self::RECORDS_PER_PAGE_SETTING;

	public function init()
	{
		$this->_helper->db->setDefaultModelName('Item');
	}

	protected function _getDuplicateSuccessMessage($item)
	{
		$itemTitle = $this->_getElementMetadata($item, 'Dublin Core', 'Title');
		if ($itemTitle != '') {
			return __('The item "%s" was successfully duplicated!', $itemTitle);
		} else {
			return __('The item #%s was successfully duplicated!', strval($item->id));
		}
	}

	/**
	 * Gets the element sets for the 'Item' record type.
	 * 
	 * @return array The element sets for the 'Item' record type
	 */
	protected function _getItemElementSets()
	{
		return $this->_helper->db->getTable('ElementSet')->findByRecordType('Item');
	}

	protected function _getElementMetadata($item, $elementSetName, $elementName)
	{
		$m = new Omeka_View_Helper_Metadata;
		return strip_formatting($m->metadata($item, array($elementSetName, $elementName)));
	}

    /**
     * Redirect to another page after a record is successfully duplicated.
     *
     * The default is to redirect to this controller's browse page.
     *
     * @param Omeka_Record_AbstractRecord $record
     */
    protected function _redirectAfterDuplicate($record)
    {
        $this->_helper->redirector('browse', 'items', 'default');
    }

	/**
	 * Similar to 'edit' action, except this saves record as new.
	 *
	 * Every request to this action must pass a record ID in the 'id' parameter.
	 *
	 * @uses Omeka_Controller_Action_Helper_Db::getDefaultModelName()
	 * @uses Omeka_Controller_Action_Helper_Db::findById()
	 * @uses self::_getDuplicateSuccessMessage()
	 * @uses self::_redirectAfterDuplicate()
	 */
	public function duplicateAction()
	{
		/**
		* Adds an additional permissions check to the built-in edit action.
		*/
		// Get all the element sets that apply to the item.
		$this->view->elementSets = $this->_getItemElementSets();
		if (!Zend_Registry::isRegistered('file_derivative_creator') && is_allowed('Settings', 'duplicate')) {
			$this->_helper->flashMessenger(__('The ImageMagick directory path has not been set. No derivative images will be created. If you would like Omeka to create derivative images, please set the path in Settings.'));
		}
		//parent::addAction();

		$class = $this->_helper->db->getDefaultModelName();
		$varName = $this->view->singularize($class);

		$record = $this->_helper->db->findById();

		if ($this->_autoCsrfProtection) {
			$csrf = new Omeka_Form_SessionCsrf;
			$this->view->csrf = $csrf;
		}
	  
		if ($this->getRequest()->isPost()) {
		$record = new $class();
			if ($this->_autoCsrfProtection && !$csrf->isValid($_POST)) {
				$this->_helper->_flashMessenger(__('There was an error on the form. Please try again.'), 'error');
				$this->view->$varName = $record;
				return;
			}
			$record->setPostData($_POST);
			if ($record->save(false)) {
				$successMessage = $this->_getDuplicateSuccessMessage($record);
				if ($successMessage != '') {
					$this->_helper->flashMessenger($successMessage, 'success');
				}
				$this->_redirectAfterDuplicate($record);
			} else {
				$this->_helper->flashMessenger($record->getErrors());
			}
		}

		$this->view->$varName = $record;
	}
}