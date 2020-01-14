<?php
$itemTitle = metadata('item', 'display_title');
if ($itemTitle != '' && $itemTitle != __('[Untitled]')) {
    $itemTitle = ': &quot;' . $itemTitle . '&quot; ';
	$itemId = '#' . metadata('item', 'id');
} else {
    $itemTitle = '';
	$itemId = '';
}
$itemTitle = __('Duplicate Item %s', $itemId) . $itemTitle;

echo head(array('title'=> $itemTitle, 'bodyclass'=>'items edit'));
include 'form-tabs.php';
echo flash();
?>

<form method="post" enctype="multipart/form-data" id="item-form" action="">
	<?php include 'form.php'; ?>
    <section class="three columns omega">
        <div id="save" class="panel">
            <input type="submit" name="submit" class="submit big green button" id="add_item" value="<?php echo __('Add duplicate'); ?>" />        
            
			<?php fire_plugin_hook("admin_items_panel_buttons", array('view'=>$this, 'record'=>$item)); ?>
            
            <div id="public-featured">
                <?php if ( is_allowed('Items', 'makePublic') ): ?>
                    <div class="public">
                        <label for="public"><?php echo __('Public'); ?>:</label> 
                        <?php 
							if (get_option(item_duplicator_private)) {
								echo $this->formCheckbox('public', '0', array('disabled' => '1'), array('1', '0'));
							} else {
								echo $this->formCheckbox('public', $item->public, array(), array('1', '0')); 
							}
						?>
                    </div>
                <?php endif; ?>
                <?php if ( is_allowed('Items', 'makeFeatured') ): ?>
                    <div class="featured">
                        <label for="featured"><?php echo __('Featured'); ?>:</label> 
                        <?php echo $this->formCheckbox('featured', $item->featured, array(), array('1', '0')); ?>
                    </div>
                <?php endif; ?>
            </div> <!-- end public-featured  div -->
            
            <div id="collection-form" class="field">
                <?php echo $this->formLabel('collection-id', __('Collection'));?>
                <div class="inputs">
                    <?php
                        echo $this->formSelect(
                            'collection_id',
                            $item->collection_id,
                            array('id' => 'collection-id'),
                            get_table_options('Collection')
                        );
                    ?>
                </div>
                <?php fire_plugin_hook('admin_items_form_collection', array('item' => $item, 'view' => $this)); ?>
            </div> <!-- end collection-form div -->
            <?php fire_plugin_hook("admin_items_panel_fields", array('view'=>$this, 'record'=>$item)); ?>
        </div>
    </section>
</form>
<?php echo foot();?>