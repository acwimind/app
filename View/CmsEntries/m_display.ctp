<div class="static_content">
    <div class="content-header">
<h2><?php echo $entry['CmsTextLang']['name']; ?></h2>
</div>
<?php if (!empty($entry['CmsTextLang']['perex'])):?>
<p class="perex"><strong><?php echo $entry['CmsTextLang']['perex']; ?></strong></p>
<?php endif; ?>
<p class="main_text"><?php echo $entry['CmsTextLang']['text']; ?></p>
</div>