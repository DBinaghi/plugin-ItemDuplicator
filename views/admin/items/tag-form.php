<div id="tag-form" class="field">
    <?php
        $tags = $item->getTags();

// - aggiunto da Daniele
		if (get_option('item_duplicator_empty_tags')) $tags = array();

		$request = Zend_Controller_Front::getInstance()->getRequest();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        if ($controller == 'items' && $action == 'duplicate') {            
			$tags_text = implode(option('tag_delimiter'), $tags);
        } else {
			$tags_text = '';
		}
// - fine aggiunta da Daniele
	?>

<!-- modificato da Daniele
    <input type="hidden" name="tags-to-add" id="tags-to-add" value="" />
-->
    <input type="hidden" name="tags-to-add" id="tags-to-add" value="<?php echo $tags_text?>" />
    <input type="hidden" name="tags-to-delete" id="tags-to-delete" value="" />
    <div id="add-tags">
        <label><?php echo __('Add Tags'); ?></label>           
        <input type="text" name="tags" size="20" id="tags" class="textinput" value="" />
        <p id="add-tags-explanation" class="explanation"><?php echo __('Separate tags with %s', option('tag_delimiter')); ?></p>
        <input type="submit" name="add-tags-button" id="add-tags-button" class="green button" value="<?php echo __('Add Tags'); ?>" />
    </div>
    <div id="all-tags">
    <?php if ($tags): ?>
        <h3><?php echo __('All Tags'); ?></h3>
        
        <div class="tag-list">
        <ul id="all-tags-list">
            <?php foreach( $tags as $tag ): ?>
                <li>
                    <?php echo '<span class="tag">' . html_escape($tag->name) . '</span>';
                          echo '<span class="undo-remove-tag"><a href="#">' . __('Undo') . '</a></span>';
                          echo '<span class="remove-tag"><a href="#">' . __('Remove') . '</a></span>'; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        </div>
    <?php endif; ?>
    </div>
</div>
<?php fire_plugin_hook('admin_items_form_tags', array('item' => $item, 'view' => $this)); ?>
