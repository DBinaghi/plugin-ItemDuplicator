<div class="add-new"><?php echo __('Add New Files'); ?></div>
<div class="drawer-contents">
    <p><?php echo __('The maximum file size is %s.', max_file_size()); ?></p>
    
    <div class="field two columns alpha" id="file-inputs">
        <label><?php echo __('Find a File'); ?></label>
    </div>

    <div class="files four columns omega">
        <input name="file[0]" type="file">
    </div>
</div>

<?php fire_plugin_hook('admin_items_form_files', array('item' => $item, 'view' => $this)); ?>
