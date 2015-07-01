<picture>
    <% loop $Sizes %>
        <source media="$Query" srcset="$Image.URL">
    <% end_loop %>
    <img src="$DefaultImage.URL" alt="$Title">
</picture>
