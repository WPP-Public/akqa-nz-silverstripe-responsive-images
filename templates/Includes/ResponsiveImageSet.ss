<picture>
	<% loop $Sizes %>
		<source media="$Query" srcset="$Image.URL" width="$Image.Width" height="$Image.Height">
	<% end_loop %>
	<img src="$DefaultImage.URL"<% if $ExtraClasses %> class="$ExtraClasses"<% end_if %> alt="$Title" loading="auto" width="$Image.Width" height="$Image.Height">
</picture>
