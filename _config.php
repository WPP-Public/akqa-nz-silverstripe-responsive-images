<?php

define('RESPONSIVE_IMAGES_DIR', basename(dirname(__FILE__)));

// Enable srcset attributes on image tags (the rest of the attributes are copied
// from framework/admin/_config.php)
$config = HtmlEditorConfig::get('cms');
$config->setOption(
	'extended_valid_elements',
	$config->getOption('extended_valid_elements') . ',img[class|src|srcset|alt|'
		. 'title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|'
		. 'usemap|data*]'
);
