<div class="add-new"><?php echo __('Add New Files'); ?></div>
<div class="drawer-contents opened">
    <p><?php echo __('The maximum file size is %s.', max_file_size()); ?></p>

    <div class="field two columns alpha" id="file-inputs">
        <label><?php echo __('Find a File'); ?></label>
        <button type="button" id="add-file" class="add-file button"><?php echo __('Add Another File'); ?></button>
    </div>

    <?php
    $fileTemplate = <<<FILE_TEMPLATE
    <div class="file-container">
        <input name="file[__INDEX__]" type="file" class="file-input" multiple>
        <div class="file-info">
            <div class="file-thumbnail"></div>
            <div class="file-size"></div>
        </div>
    </div>
FILE_TEMPLATE;
    ?>
    <div class="files four columns omega" data-file-container-template="<?php echo utf8_htmlspecialchars($fileTemplate); ?>"></div>
</div>

<?php fire_plugin_hook('admin_items_form_files', ['item' => $item, 'view' => $this]); ?>
