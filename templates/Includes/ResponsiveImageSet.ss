<picture>
	<!--[if IE 9]><video style="display: none;"><![endif]-->
	<% loop $Sizes %>
	<source media="$Query" srcset="$Image.URL">
	<% end_loop %>
	<!--[if IE 9]></video><![endif]-->
	<img src="$DefaultImage.URL" alt="$Title">
</picture>
