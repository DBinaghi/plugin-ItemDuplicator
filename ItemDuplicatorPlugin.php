<?php

/**
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright Daniele Binaghi, 2018
 * @package ItemDuplicator
 */

 
// Define Constants
define('ITEM_DUPLICATOR_DUPLICATE', __('Duplicate'));

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
		'define_routes'
    );

    public function hookInstall()
    {
        set_option('item_duplicator_restricted', '0');    
        set_option('item_duplicator_empty_title', '1');    
        set_option('item_duplicator_empty_tags', '0');    
 	}

    public function hookUninstall()
    {
        delete_option('item_duplicator_restricted');
        delete_option('item_duplicator_empty_title');
        delete_option('item_duplicator_empty_tags');
    }

    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }
	
	public function hookConfig($args)
    {
        $post = $args['post'];
        set_option('item_duplicator_restricted',  $post['item_duplicator_restricted']);
        set_option('item_duplicator_empty_title', $post['item_duplicator_empty_title']);
        set_option('item_duplicator_empty_tags',  $post['item_duplicator_empty_tags']);
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
			// contributors area able to duplicate
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
							if (buttons[i].href.indexOf('/edit/') > 0) {
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
		} else {
			queue_js_string("
				document.addEventListener('DOMContentLoaded', function() {
					alert('Controller: " . $controller . "');
					alert('Action: " . $action . "');
				}, false);
			");
		}
    }
	
	function hookDefineRoutes($args)
    {
        // Don't add these routes on the admin side to avoid conflicts.
        if (is_admin_theme()) {
            return;
        }

        $router = $args['router'];

        // Add custom routes based on the page slug.
		$router->addRoute(
			'item_duplicator_duplicate_item_' . $page->id,
			new Zend_Controller_Router_Route(
				$page->slug,
				array(
					'module'       => 'item-duplicator',
					'controller'   => 'items',
					'action'       => 'duplicate',
					'id'           => $page->id
				)
			)
		);
    }

}