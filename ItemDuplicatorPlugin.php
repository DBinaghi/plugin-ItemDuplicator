<?php

/**
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright Daniele Binaghi, 2018
 * @package ItemDuplicator
 */

 
// Define Constants
define('ITEM_DUPLICATOR_DUPLICATE', __('Duplicate'));
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
		'admin_head',
		'before_save_item',
		'define_routes'
	);
	
	// Define Filters
	protected $_filters = array(
		'emptyTitleField' => array('ElementInput', 'Item', 'Dublin Core', 'Title'),
		'emptySubjectField' => array('ElementInput', 'Item', 'Dublin Core', 'Subject'),
		'emptyDateField' => array('ElementInput', 'Item', 'Dublin Core', 'Date'),
	);

	public function hookInstall()
	{
		set_option('item_duplicator_restricted', '0');
		set_option('item_duplicator_empty_title', '1');
		set_option('item_duplicator_empty_subject', '1');
		set_option('item_duplicator_empty_date', '1');
		set_option('item_duplicator_empty_fields_check', '1');
		set_option('item_duplicator_empty_fields_highlight', '#ffcc00');
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
			// contributors are aallowed to duplicate
			$acl->allow('contributor', 'Items', 'duplicate');

			// if author role exists, also authors are allowed to duplicate
			if ($acl->hasRole('author')) {
				$acl->allow('author', 'Items', 'duplicate');
			}   
			
			// if editor role exists, also editors are allowed to duplicate
			if ($acl->hasRole('editor')) {
				$acl->allow('editor', 'Items', 'duplicate');
			}   
		}
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
									newLI = newLI.replace('items/edit', 'items/duplicate');
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
						var regex = />([^<][a-zA-Z]*)</gi;
						var buttons = panel.children;
						for (i=0; i < buttons.length; i++) {
							if (buttons[i].href.indexOf('/items/edit/') > 0) {
								var cln = buttons[i].cloneNode(true);
								cln.innerHTML = '" . __(ITEM_DUPLICATOR_DUPLICATE) . "';
								cln.href = cln.href.replace('items/edit', 'items/duplicate');
								buttons[i].parentNode.insertBefore(cln, buttons[i].nextSibling);
								break;
							}
						}
					}, false);
				");
			}
		} elseif ($controller == 'index' && $action == 'index') {
			queue_js_string("
				document.addEventListener('DOMContentLoaded', function() {
					var paragraphs = document.getElementsByClassName('dash-edit');
					for (i=0; i < paragraphs.length; i++) {
						var links = paragraphs[i].children;
						for (ii=0; ii < links.length; ii++) {
							if (links[ii].href.indexOf('/items/edit/') > 0) {
								var cln = links[ii].cloneNode(true);
								cln.innerHTML = '" . __(ITEM_DUPLICATOR_DUPLICATE) . "';
								cln.href = cln.href.replace('items/edit', 'items/duplicate');
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
		$item = $args['record'];
		$post = $args['post'];
		// if POST is empty, skip the validation, so it doesn't break when saving item in another way
		if (!empty($post)) {
			if (get_option('item_duplicator_empty_title')) {
				// you may simply hardcode DC:Title element id, but it's safer for Item Type Metadata elements
				$titleElement = $item->getElement('Dublin Core', 'Title');
				$title = '';
				if (!empty($post['Elements'][$titleElement->id])) {
					foreach ($post['Elements'][$titleElement->id] as $text) {
						$title .= trim($text);
					}
				}
				if (empty($title)) {
					$item->addError("DC Title", __('DC Title cannot be empty!'));
				}
			}
			if (get_option('item_duplicator_empty_subject')) {
				// you may simply hardcode DC:Subject element id, but it's safer for Item Type Metadata elements
				$subjectElement = $item->getElement('Dublin Core', 'Subject');
				$subject = '';
				if (!empty($post['Elements'][$subjectElement->id])) {
					foreach ($post['Elements'][$subjectElement->id] as $text) {
						$subject .= trim($text);
					}
				}
				if (empty($subject)) {
					$item->addError("DC Subject", __('DC Subject cannot be empty!'));
				}
			}
			if (get_option('item_duplicator_empty_date')) {
				// you may simply hardcode DC:Date element id, but it's safer for Item Type Metadata elements
				$dateElement = $item->getElement('Dublin Core', 'Date');
				$date = '';
				if (!empty($post['Elements'][$dateElement->id])) {
					foreach ($post['Elements'][$dateElement->id] as $text) {
						$date .= trim($text);
					}
				}
				if (empty($date)) {
					$item->addError("DC Date", __('DC Date cannot be empty!'));
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

	public function hookDefineRoutes($args)
	{
//		$args['router']->addConfig(new Zend_Config_Ini(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'routes.ini', 'routes'));
	}
}
