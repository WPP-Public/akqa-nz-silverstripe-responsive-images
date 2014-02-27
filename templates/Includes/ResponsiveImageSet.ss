<span data-picture data-alt="$Title">
    <% loop $Sizes %>
    <span data-src="$Image.URL" data-media="$Query"></span>
    <% end_loop %>
    <!--[if (lt IE 9) & (!IEMobile)]>
      <span data-src="$DefaultImage.URL"></span>
    <![endif]-->
    <noscript>
        <img src="$DefaultImage.URL" alt="$Title">
    </noscript>
</span>
