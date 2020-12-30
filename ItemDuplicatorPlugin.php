<?php

/**
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright Daniele Binaghi, 2018
 * @package ItemDuplicator
 */

 
// Define Constants
define('ITEM_DUPLICATOR_DUPLICATE', __('Duplicate'));
define('ITEM_DUPLICATOR_NEWPATH', 'items/duplicate');
$hcolor = get_option('item_duplicator_empty_fields_highlight');
define('ITEM_DUPLICATOR_HIGHLIGHT_COLOR', (preg_match('/#([a-f0-9]{3}){1,2}\b/i', $hcolor) ? $hcolor : ''));

class ItemDuplicatorPlugin extends Omeka_Plugin_AbstractPlugin
{
	protected $_hooks = array(
		'install',
		'uninstall',
		'initialize',
		'config',
		'config_form',
		'define_acl',
		'define_routes',
		'admin_head',
		'before_save_item',
		'admin_items_panel_buttons'
	);
	
	// Define Filters
	protected $_filters = array(
		'emptyTitleField' => array('ElementInput', 'Item', 'Dublin Core', 'Title'),
		'emptySubjectField' => array('ElementInput', 'Item', 'Dublin Core', 'Subject'),
		'emptyDateField' => array('ElementInput', 'Item', 'Dublin Core', 'Date'),
		'svp_suggest_routes'
	);

	public function hookInstall()
	{
		set_option('item_duplicator_restricted', '0');
		set_option('item_duplicator_empty_title', '1');
		set_option('item_duplicator_empty_subject', '1');
		set_option('item_duplicator_empty_date', '1');
		set_option('item_duplicator_empty_fields_check', '1');
		set_option('item_duplicator_empty_fields_highlight', '#FFFF66');
		set_option('item_duplicator_empty_tags', '0');
		set_option('item_duplicator_private', '1');
	}

	public function hookUninstall()
	{
		delete_option('item_duplicator_restricted');
		delete_option('item_duplicator_empty_title');
		delete_option('item_duplicator_empty_subject');
		delete_option('item_duplicator_empty_date');
		delete_option('item_duplicator_empty_fields_check');
		delete_option('item_duplicator_empty_fields_highlight');
		delete_option('item_duplicator_empty_tags');
		delete_option('item_duplicator_private');
	}

	public function hookInitialize()
	{
		add_translation_source(dirname(__FILE__) . '/languages');
	}
	
	public function hookConfig($args)
	{
		$post = $args['post'];
		set_option('item_duplicator_restricted',			$post['item_duplicator_restricted']);
		set_option('item_duplicator_empty_title',			$post['item_duplicator_empty_title']);
		set_option('item_duplicator_empty_subject',			$post['item_duplicator_empty_subject']);
		set_option('item_duplicator_empty_date',			$post['item_duplicator_empty_date']);
		set_option('item_duplicator_empty_fields_check',	$post['item_duplicator_empty_fields_check']);
		set_option('item_duplicator_empty_fields_highlight',$post['item_duplicator_empty_fields_highlight']);
		set_option('item_duplicator_empty_tags',			$post['item_duplicator_empty_tags']);
		set_option('item_duplicator_private',				$post['item_duplicator_private']);
	}
	
	public function hookConfigForm()
	{
		include 'config_form.php';
	}
	
	public function hookDefineAcl($args)
	{
		$acl = $args['acl'];
		
		// admins are always capable of duplicating
		$acl->allow('admin', 'Items', 'duplicate');

		if (get_option('item_duplicator_restricted')) {
			// duplication is restricted to Super User and Admin roles
			$acl->deny('contributor', 'Items', 'duplicate');
					
			// if author role exists, also authors are not allowed to duplicate
			if ($acl->hasRole('author')) {
				$acl->deny('author', 'Items', 'duplicate');
			}   
		
			// if editor role exists, also editors are not allowed to duplicate
			if ($acl->hasRole('editor')) {
				$acl->deny('editor', 'Items', 'duplicate');
			}		
		} else {
			// contributors are able to duplicate
			$acl->allow('contributor', 'Items', 'duplicate');

			// if author role exists, also authors are capable of duplicating
			if ($acl->hasRole('author')) {
				$acl->allow('author', 'Items', 'duplicate');
			}   
			
			// if editor role exists, also editors are capable of duplicating
			if ($acl->hasRole('editor')) {
				$acl->allow('editor', 'Items', 'duplicate');
			}   
		}
	}
	
	public function hookDefineRoutes($args)
	{
		// Don't add these routes on the public side to avoid conflicts.
        	if (!is_admin_theme()) {
            		return;
		}
		$router = $args['router'];
		$router->addRoute(
			'duplicate',
			new Zend_Controller_Router_Route(
				'items/duplicate/:id',
				array(
					'module'       => 'item-duplicator',
					'controller'   => 'items',
					'action'       => 'duplicate'
				)
			)
		);
	}
	
	public function hookAdminHead()
	{
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$controller = $request->getControllerName();
		$action = $request->getActionName();
		if ($controller == 'items') {
			if ($action == 'browse') {			
				queue_js_string("
					document.addEventListener('DOMContentLoaded', function() {
						var items = document.getElementsByClassName('action-links group');
						var regex = />([^<][a-zA-Z]*)</gi;
						for (i=0; i < items.length; i++) {
							var listItems = items[i].children;
							for (ii=0; ii < listItems.length; ii++) {
								var newLI = listItems[ii].innerHTML;
								if (newLI.indexOf('items/edit') > 0) {
									newLI = newLI.replace('items/edit', '" . ITEM_DUPLICATOR_NEWPATH . "');
									newLI = newLI.replace(regex, '>" . __(ITEM_DUPLICATOR_DUPLICATE) . "<');
									var entry = document.createElement('li');
									entry.innerHTML = newLI;
									items[i].appendChild(entry);
									break;
								}
							}
						}
					}, false);
				");
			} elseif ($action == 'show') {
				queue_js_string("
					document.addEventListener('DOMContentLoaded', function() {
						var panel = document.getElementById('edit');
						var buttons = panel.children;
						for (i=0; i < buttons.length; i++) {
							if (buttons[i].href.indexOf('/items/edit/') > 0) {
								var cln = buttons[i].cloneNode(true);
								cln.innerHTML = '" . __(ITEM_DUPLICATOR_DUPLICATE) . "';
								cln.href = cln.href.replace('items/edit', '" . ITEM_DUPLICATOR_NEWPATH . "');
								buttons[i].parentNode.insertBefore(cln, buttons[i].nextSibling);
								break;
							}
						}
					}, false);
				");
			}
		} elseif (($controller == 'index' && $action == 'index') || ($controller == 'error' && $action == 'not-found')) {
			queue_js_string("
				document.addEventListener('DOMContentLoaded', function() {
					var paragraphs = document.getElementsByClassName('dash-edit');
					for (i=0; i < paragraphs.length; i++) {
						var links = paragraphs[i].children;
						for (ii=0; ii < links.length; ii++) {
							if (links[ii].href.indexOf('/items/edit/') > 0) {
								var cln = links[ii].cloneNode(true);
								cln.innerHTML = '" . __(ITEM_DUPLICATOR_DUPLICATE) . "';
								cln.href = cln.href.replace('items/edit', '" . ITEM_DUPLICATOR_NEWPATH . "');
								links[ii].parentNode.insertBefore(cln, links[ii].nextSibling);
								var textNode = document.createTextNode(' · ');
								links[ii].parentNode.insertBefore(textNode, links[ii].nextSibling);
								break;
							}
						}	
					}
				}, false);
			");
		}
	}
	
	public function hookBeforeSaveItem($args)
	{
		if (get_option(item_duplicator_empty_fields_check)) {
			$request = Zend_Controller_Front::getInstance()->getRequest();
			if (is_null($request)) return; // added to avoid conflict with other plugins
			$controller = $request->getControllerName();
			$action = $request->getActionName();
			//runs checks only when duplicating item
			if ($controller == 'items' && $action == 'duplicate') {
				$item = $args['record'];
				$post = $args['post'];
				// if POST is empty, skip the validation, so it doesn't break when saving item in another way
				if (!empty($post)) {
					// one may simply hardcode DC:Title element id, but it's safer for Item Type Metadata elements
					$titleElement = $item->getElement('Dublin Core', 'Title');
					$title = '';
					if (!empty($post['Elements'][$titleElement->id])) {
						foreach ($post['Elements'][$titleElement->id] as $textbox) {
							$title .= trim($textbox['text']);
						}
					}
					if (empty($title)) {
						$item->addError("DC Title", __('DC Title field cannot be empty!'));
					}
					// one may simply hardcode DC:Subject element id, but it's safer for Item Type Metadata elements
					$subjectElement = $item->getElement('Dublin Core', 'Subject');
					$subject = '';
					if (!empty($post['Elements'][$subjectElement->id])) {
						foreach ($post['Elements'][$subjectElement->id] as $textbox) {
							$subject .= trim($textbox['text']);
						}
					}
					if (empty($subject)) {
						$item->addError("DC Subject", __('DC Subject field cannot be empty!'));
					}
					// one may simply hardcode DC:Date element id, but it's safer for Item Type Metadata elements
					$dateElement = $item->getElement('Dublin Core', 'Date');
					$date = '';
					if (!empty($post['Elements'][$dateElement->id])) {
						foreach ($post['Elements'][$dateElement->id] as $textbox) {
							$date .= trim($textbox['text']);
						}
					}
					if (empty($date)) {
						$item->addError("DC Date", __('DC Date field cannot be empty!'));
					}
					//checks whether item MUST be private
					if (get_option(item_duplicator_private)) {
						$item->setPublic(false);
					}
				}
			}
		}
	}
	
	public function emptyTitleField($components, $args)
	{
		if (get_option('item_duplicator_empty_title')) {
			$request = Zend_Controller_Front::getInstance()->getRequest();
			$controller = $request->getControllerName();
			$action = $request->getActionName();
			if ($controller == 'items' && $action == 'duplicate') {
				$components['input'] = get_view()->formTextarea($args['input_name_stem'] . '[text]', '', array(
					'cols' => 50,
					'rows' => 3,
					'autofocus' => 1,
					'style' => 'background-color: ' . ITEM_DUPLICATOR_HIGHLIGHT_COLOR
				));
			}
		}
		return $components;
	}

	public function emptySubjectField($components, $args)
	{
		if (get_option('item_duplicator_empty_subject')) {
			$request = Zend_Controller_Front::getInstance()->getRequest();
			$controller = $request->getControllerName();
			$action = $request->getActionName();
			if ($controller == 'items' && $action == 'duplicate') {
				$components['input'] = get_view()->formTextarea($args['input_name_stem'] . '[text]', '', array(
					'cols' => 50,
					'rows' => 3,
					'autofocus' => 1,
					'style' => 'background-color: ' . ITEM_DUPLICATOR_HIGHLIGHT_COLOR
				));
			}
		}
		return $components;
	}

	public function emptyDateField($components, $args)
	{
		if (get_option('item_duplicator_empty_date')) {
			$request = Zend_Controller_Front::getInstance()->getRequest();
			$controller = $request->getControllerName();
			$action = $request->getActionName();
			if ($controller == 'items' && $action == 'duplicate') {
				$components['input'] = get_view()->formTextarea($args['input_name_stem'] . '[text]', '', array(
					'cols' => 50,
					'rows' => 3,
					'autofocus' => 1,
					'style' => 'background-color: ' . ITEM_DUPLICATOR_HIGHLIGHT_COLOR
				));
			}
		}
		return $components;
	}

	public function hookAdminItemsPanelButtons($args)
	{
		// Add a 'Cancel' button on the admin right button panel. It appears when editing or duplicating an existing
		// item or adding a new item. When editing or duplicating, pressing the Cancel button takes the user back to
		// the Show page for the item. When adding a new item, it takes them to the Dashboard.
		$itemId = $args['record']->id;
		$url = $itemId ? 'items/show/' . $itemId : '.';
		echo '<a href=' . html_escape(admin_url($url)) . ' class="big blue button">' . __('Cancel') . '</a>';
	}
	
	/**
	 * Add a route to a plugin.
     	 *
     	 * @param array $routePlugins Route plugins array.
     	 * @return array Filtered route plugins array.
    	*/
    	public function filterSvpSuggestRoutes($routePlugins)
    	{
        	$routePlugins['itemduplicator'] = array(
            		'module' => 'item-duplicator',
            		'controller' => 'items',
            		'actions' => array('duplicate')
        	);

        	return $routePlugins;
    	}
}
