# Responsive Images for Silverstripe

## Introduction

This highly configurable module wraps an image in a picture element with a set of sources for different media queries. This enables the browser to load the matching image size for the current viewport.

This is particularly useful in responsive design and page load optimisation. Different viewports can receive an image optimised for their size rather than one size fits all.

## Requirements
Silverstripe CMS 6.0 or higher

For a CMS 5.x compatible-version, please see branch 3.0
For a CMS 4.x compatible-version, please see branch 2.0
For a CMS 3.x compatible-version, please see branch 1.0

## Installation

    composer require heyday/silverstripe-responsive-images

## How to use

Once you have this module installed, you’ll need to configure named sets of image sizes in your site’s yaml config (eg. `mysite/_config/config.yml`).
Note that there are no default image sets, but you can copy the config below to get started:

```yml
---
After: 'silverstripe-responsive-images/*'
---
Heyday\ResponsiveImages\ResponsiveImageExtension:
  sets:
    ResponsiveSet1:
      css_classes: classname
      arguments:
        '(min-width: 1200px)': [800]
        '(min-width: 800px)': [400]
        '(min-width: 200px)': [100]

    ResponsiveSet2:
      template: Includes/MyCustomImageTemplate
      method: Fill
      arguments:
        '(min-width: 1000px) and (min-device-pixel-ratio: 2.0)': [1800, 1800]
        '(min-width: 1000px)': [900, 900]
        '(min-width: 800px) and (min-device-pixel-ratio: 2.0)': [1400, 1400]
        '(min-width: 800px)': [700, 700]
        '(min-width: 400px) and (min-device-pixel-ratio: 2.0)': [600, 600]
        '(min-width: 400px)': [300, 300]
      default_arguments: [1200, 1200]

    ResponsiveSet3:
      method: Pad
      arguments:
        '(min-width: 800px)': [700, 700, '666666']
        '(min-width: 400px)': [300, 300, '666666']
      default_arguments: [1200, 1200, '666666']
```

Now, add `?flush=1` to the URL to refresh the config manifest. You can then use the new Image class methods in your template like so:

```
$MyImage.ResponsiveSet1
$MyImage.ResponsiveSet2
$MyImage.ResponsiveSet3
```

The output of the first method (`ResponsiveSet1`) will look something like this. Remember that the browser will render the first matching source.
```html
<picture>
  <source media="(min-width: 1200px)" srcset="/assets/Uploads/_resampled/SetWidth800-my-image.jpeg" width="800" height="800">
  <source media="(min-width: 800px)" srcset="/assets/Uploads/_resampled/SetWidth400-my-image.jpeg" width="400" height="400">
  <source media="(min-width: 200px)" srcset="/assets/Uploads/_resampled/SetWidth100-my-image.jpeg" width="100" height="100">
  <img src="/assets/Uploads/_resampled/SetWidth640480-my-image.jpeg" alt="My Image Title" loading="auto" width="800" height="800">
</picture>
```

As the window is resized, new images are loaded into the DOM.


### Other options

Each set should have a "default_arguments" property set in case the browser does not support media queries. By default, the "default_arguments" property results in an 800x600 image, but this can be overridden in the config.
```yml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  default_arguments: [1200, 768]
```

You can also pass arguments for the default image at the template level.
```
$MyImage.ResponsiveSet1(900, 600)
```

The default resampling method is SetWidth, but this can be overridden in your config.
```yml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  default_method: Fill
```

It can also be passed into your template function.
```
$MyImage.ResponsiveSet1('Fill', 800, 600)
```
