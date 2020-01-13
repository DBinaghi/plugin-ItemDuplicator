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
		parent::addAction();

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
				$successMessage = $this->_getAddSuccessMessage($record);
				if ($successMessage != '') {
					$this->_helper->flashMessenger($successMessage, 'success');
				}
				$this->_redirectAfterAdd($record);
			} else {
				$this->_helper->flashMessenger($record->getErrors());
			}
		}

		$this->view->$varName = $record;
	}
}
