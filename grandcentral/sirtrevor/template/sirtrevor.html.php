<label for="<?= $_FIELD->get_name(); ?>">
	<?= $_FIELD->get_label(); ?>
	<span class="help"></span>
</label>
<div class="wrapper">
	<?php if ($_FIELD->get_descr() != null) : ?><div class="help"><?= $_FIELD->get_descr(); ?></div><?php endif ?>
	<span class="field">
		<textarea <?=$attrs?> data-cache="<?= app('cache')->get_templateroot('site'); ?>"><?=$value?></textarea>
	</span>
</div>