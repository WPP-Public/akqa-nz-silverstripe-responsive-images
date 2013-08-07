<span data-picture data-alt="$Title">
	<% loop $Sizes %>
    <span data-src="$Image.URL" data-media="$Query"></span>
    <% end_loop %>
    <noscript>
        <img src="$DefaultURL" alt="$Title">
    </noscript>
</span>