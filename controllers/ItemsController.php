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
	 * Destination is controlled by the 'item_duplicator_redirect_after' option:
	 *   'browse'   → items list (original behaviour)
	 *   'show_new' → Show page of the new duplicate
	 *   'edit_new' → Edit page of the new duplicate (useful to fill empty fields immediately)
	 *
	 * [CHANGED] Was always redirecting to browse; now respects the admin setting.
	 *
	 * @param Omeka_Record_AbstractRecord $record The newly created duplicate item.
	 */
	protected function _redirectAfterDuplicate($record)
	{
		$redirectTo = get_option('item_duplicator_redirect_after') ?: 'browse';
		switch ($redirectTo) {
			case 'show_new':
				$this->_helper->redirector('show', 'items', 'default', array('id' => $record->id));
				break;
			case 'edit_new':
				$this->_helper->redirector('edit', 'items', 'default', array('id' => $record->id));
				break;
			case 'browse':
			default:
				$this->_helper->redirector('browse', 'items', 'default');
				break;
		}
	}

	/**
	 * Similar to 'edit' action, except this saves record as new.
	 *
	 * Every request to this action must pass a record ID in the 'id' parameter.
	 *
	 * Added explicit permission check via is_allowed() so that ACL restrictions
	 * set by third-party plugins are respected at the controller level too.
	 *
	 * The item ID from the URL is now cast to int and validated before use,
	 * preventing type-juggling issues and ensuring a clean 404 for invalid IDs.
	 *
	 * Wrap the save operation in a DB transaction so that a partial failure
	 * (e.g. element texts saved but tags not) does not leave a corrupt record.
	 *
	 * Flash message explicitly notes that files were NOT duplicated.
	 *
	 * All significant operations are written to Omeka's application log.
	 *
	 * @uses Omeka_Controller_Action_Helper_Db::getDefaultModelName()
	 * @uses Omeka_Controller_Action_Helper_Db::findById()
	 * @uses self::_getDuplicateSuccessMessage()
	 * @uses self::_redirectAfterDuplicate()
	 */
	public function duplicateAction()
	{
		// Cast ID to int and validate early; return a clean 404 for bad input.
		$id = (int) $this->getParam('id');
		if ($id <= 0) {
			throw new Omeka_Controller_Exception_404;
		}

		// Explicit permission check at the controller level.
		// hookDefineAcl sets up the rules, but a belt-and-suspenders check here ensures
		// that even if ACL rules are altered at runtime the action cannot be invoked.
		if (!is_allowed('Items', 'duplicate')) {
			$this->_helper->flashMessenger(__('You do not have permission to duplicate items.'), 'error');
			_log('[ItemDuplicator] Unauthorised duplicate attempt for item #' . $id
				. ' by user #' . (current_user() ? current_user()->id : 'guest'), Zend_Log::WARN);
			$this->_helper->redirector('show', 'items', 'default', array('id' => $id));
			return;
		}

		$this->view->elementSets = $this->_getItemElementSets();
		if (!Zend_Registry::isRegistered('file_derivative_creator') && is_allowed('Settings', 'duplicate')) {
			$this->_helper->flashMessenger(__('The ImageMagick directory path has not been set. No derivative images will be created. If you would like Omeka to create derivative images, please set the path in Settings.'));
		}

		$class   = $this->_helper->db->getDefaultModelName();
		$varName = $this->view->singularize($class);

		// Retrieve original item using the validated integer ID rather than
		// relying on findById() which reads the raw (uncast) URL parameter.
		$record = get_record_by_id('Item', $id);
		if (!$record) {
			throw new Omeka_Controller_Exception_404;
		}

		if ($this->_autoCsrfProtection) {
			$csrf = new Omeka_Form_SessionCsrf;
			$this->view->csrf = $csrf;
		}
	  
		if ($this->getRequest()->isPost()) {
			// CSRF validation: reject the request if the token is missing or stale.
			// The original code already had this guard but a typo (_flashMessenger vs flashMessenger)
			// prevented the error message from appearing; corrected below.
			if ($this->_autoCsrfProtection && !$csrf->isValid($_POST)) {
				$this->_helper->flashMessenger(__('There was an error on the form. Please try again.'), 'error');
				$this->view->$varName = $record;
				return;
			}

			$newRecord = new $class();
			$newRecord->setPostData($_POST);

			// Wrap save inside a DB transaction so the operation is atomic.
			// If element texts are written but an exception is raised before tags
			// are committed, the whole record is rolled back and no corrupt item survives.
			$db = get_db();
			$db->beginTransaction();
			try {
				$saved = $newRecord->save(false);
				if ($saved) {
					$db->commit();

					// Log successful duplication with originating and new item IDs.
					_log('[ItemDuplicator] Item #' . $id . ' duplicated as item #' . $newRecord->id
						. ' by user #' . (current_user() ? current_user()->id : 'guest'), Zend_Log::INFO);

					$successMessage = $this->_getDuplicateSuccessMessage($newRecord);
					if ($successMessage != '') {
						$this->_helper->flashMessenger($successMessage, 'success');
					}

					// Warn the user that attached files were not copied.
					// Type 'alert' maps to Omeka's orange warning banner (same colour used
					// for the ImageMagick notice above), making the message visually distinct
					// from the green success banner and the red error banner.
					if ($record->fileCount() > 0) {
						$this->_helper->flashMessenger(
							__('Note: the %d file(s) attached to the original item were NOT copied to the duplicate.',
								$record->fileCount()),
							'alert'
						);
					}

					$this->_redirectAfterDuplicate($newRecord);
				} else {
					$db->rollBack();
					$errors = $newRecord->getErrors();
					_log('[ItemDuplicator] Failed to duplicate item #' . $id . ': '
						. (string) $errors, Zend_Log::WARN);
					$this->_helper->flashMessenger($newRecord->getErrors());
				}
			} catch (\Throwable $e) {
				$db->rollBack();
				_log('[ItemDuplicator] Exception while duplicating item #' . $id . ': '
					. $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine(), Zend_Log::ERR);
			}
		}

		$this->view->$varName = $record;
	}
}
