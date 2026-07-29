<?php
	$item_duplicator_restricted			= get_option('item_duplicator_restricted');
	$item_duplicator_empty_title		= get_option('item_duplicator_empty_title');
	$item_duplicator_empty_subject		= get_option('item_duplicator_empty_subject');
	$item_duplicator_empty_date			= get_option('item_duplicator_empty_date');
	$item_duplicator_empty_fields_check	= get_option('item_duplicator_empty_fields_check');
	$item_duplicator_empty_fields_highlight	= get_option('item_duplicator_empty_fields_highlight');
	$item_duplicator_empty_tags			= get_option('item_duplicator_empty_tags');
	$item_duplicator_private			= get_option('item_duplicator_private');
	$item_duplicator_title_prefix		= get_option('item_duplicator_title_prefix');
	$item_duplicator_title_suffix		= get_option('item_duplicator_title_suffix');
	$item_duplicator_redirect_after		= get_option('item_duplicator_redirect_after') ?: 'browse';

	$view = get_view();
?>

<h2><?php echo __('Allowed users'); ?></h2>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_restricted', __('Restrict privilege')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('If checked, only users with Super User or Admin role will be able to duplicate Items.'); ?>
		</p>
		<?php echo $view->formCheckbox('item_duplicator_restricted', $item_duplicator_restricted, null, array('1', '0')); ?>
	</div>
</div>

<h2><?php echo __('Fields'); ?></h2>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_empty_title', __('Empty Title field')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('If checked, field DublinCore:Title will be emptied when showing duplicate Item.'); ?>
		</p>
		<?php echo $view->formCheckbox('item_duplicator_empty_title', $item_duplicator_empty_title, array('id' => 'item_duplicator_empty_title'), array('1', '0')); ?>
	</div>
</div>

<div class="field" id="item_duplicator_title_prefix_field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_title_prefix', __('Title prefix')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('Text to prepend to the original DC:Title when duplicating (e.g. "Copy of "). Leave blank for no prefix. Applies only if field Title is emptied.'); ?>
		</p>
		<?php echo $view->formText('item_duplicator_title_prefix', $item_duplicator_title_prefix, array('placeholder' => __('e.g. Copy of '))); ?>
	</div>
</div>

<div class="field" id="item_duplicator_title_suffix_field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_title_suffix', __('Title suffix')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('Text to append to the original DC:Title when duplicating (e.g. " (duplicate)"). Leave blank for no suffix. Applies only if field Title is emptied.'); ?>
		</p>
		<?php echo $view->formText('item_duplicator_title_suffix', $item_duplicator_title_suffix, array('placeholder' => __('e.g.  (duplicate)'))); ?>
	</div>
</div>

<script>
(function () {
	var checkbox = document.getElementById('item_duplicator_empty_title');
	var prefixField = document.getElementById('item_duplicator_title_prefix_field');
	var suffixField = document.getElementById('item_duplicator_title_suffix_field');

	function toggle() {
		var enabled = checkbox.checked;
		prefixField.querySelectorAll('input').forEach(function (el) {
			el.disabled = !enabled;
			el.style.opacity = enabled ? '1' : '0.4';
		});
		suffixField.querySelectorAll('input').forEach(function (el) {
			el.disabled = !enabled;
			el.style.opacity = enabled ? '1' : '0.4';
		});
	}

	checkbox.addEventListener('change', toggle);
	toggle(); // apply on page load
}());
</script>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_empty_subject', __('Empty Subject field')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('If checked, field DublinCore:Subject will be emptied when showing duplicate Item.'); ?>
		</p>
		<?php echo $view->formCheckbox('item_duplicator_empty_subject', $item_duplicator_empty_subject, null, array('1', '0')); ?>
	</div>
</div>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_empty_date', __('Empty Date field')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('If checked, field DublinCore:Date will be emptied when showing duplicate Item.'); ?>
		</p>
		<?php echo $view->formCheckbox('item_duplicator_empty_date', $item_duplicator_empty_date, null, array('1', '0')); ?>
	</div>
</div>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_empty_fields_check', __('Check for empty fields')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('If checked, fields above are checked before saving and, if any is found empty, a warning will be shown and the saving process will be interrupted.'); ?>
		</p>
		<?php echo $view->formCheckbox('item_duplicator_empty_fields_check', $item_duplicator_empty_fields_check, null, array('1', '0')); ?>
	</div>
</div>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_empty_fields_highlight', __('Highlight emptied fields')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('Color hex code (e.g.: #ff0000) to highlight the fields that have been emptied (blank means no highlight).'); ?>
		</p>
		<?php echo $view->formText('item_duplicator_empty_fields_highlight', $item_duplicator_empty_fields_highlight, array('title'=>__('pound sign (#) followed by six hex values'),'pattern'=>'^#[0-9a-f]{6}$')); ?>
	</div>
</div>

<h2><?php echo __('Tags'); ?></h2>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_empty_tags', __('Remove Tags')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('If checked, tags will be removed when showing duplicate Item.'); ?>
		</p>
		<?php echo $view->formCheckbox('item_duplicator_empty_tags', $item_duplicator_empty_tags, null, array('1', '0')); ?>
	</div>
</div>

<h2><?php echo __('Publishing Item'); ?></h2>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_private', __('Make private')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('If checked, duplicate items will not be made automatically public, even if user role allows that.'); ?>
		</p>
		<?php echo $view->formCheckbox('item_duplicator_private', $item_duplicator_private, null, array('1', '0')); ?>
	</div>
</div>

<!-- [NEW] Redirect after duplication section -->
<h2><?php echo __('After duplication'); ?></h2>

<div class="field">
	<div class="two columns alpha">
		<?php echo $view->formLabel('item_duplicator_redirect_after', __('Redirect to')); ?>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('Choose where to send the user after a duplicate item has been saved successfully.'); ?>
		</p>
		<?php echo $view->formSelect(
			'item_duplicator_redirect_after',
			$item_duplicator_redirect_after,
			array(),
			array(
				'browse'   => __('Items list (default)'),
				'show_new' => __('Show page of the new duplicate'),
				'edit_new' => __('Edit page of the new duplicate'),
			)
		); ?>
	</div>
</div>
