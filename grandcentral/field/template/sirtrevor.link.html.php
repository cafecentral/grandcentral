<script type="text/javascript" charset="utf-8">
//	External link
	$(document).on('click', '#adminContext [data-template="sirtrevor.link"] .external button', function()
	{
	//	Get the value from the iframe
		link = $('#externalLink').contents().find('input').val();
		
	//	Good link
		if(link && link.length > 0)
		{
			link_regex = /(ftp|http|https):\/\/./;
			if (!link_regex.test(link)) link = "http://" + link;
			document.execCommand('CreateLink', false, link);
			closeContext();
		}
	//	Bad link
		else console.log('That is not a valid URL, buddy');
	});
	
//	Internal link
	$(document).on('click', '#adminContext #sirtrevorlink .internal [data-item] button', function()
	{
		link = $(this).parent().data('item');
		document.execCommand('CreateLink', false, link);
		closeContext();
	});
</script>

<h1>External Link</h1>
<div class="external">
	<!-- Stored in an iframe to keep the focus on the highlighted link...-->
	<iframe id="externalLink" src="<?=$iframeLink?>"></iframe>
	<button>⇠ Add link</button>
</div>
<h1>Internal Link</h1>
<div class="internal">
	<?php foreach ($items as $structure): ?>
		<h2><span class="centered"><?=$structure['title']?></span></h2>
		<?
			$items = i($structure['key']->get(), array('order()' => 'updated'), 'site');
		?>
		<ul>
		<?php foreach ($items as $item): ?>
			<li data-item="<?=$item['url']->abbr()?>"><button>⇠ <?=$item['title']?></button></li>
		<?php endforeach ?>
		</ul>
	<?php endforeach ?>
</div>