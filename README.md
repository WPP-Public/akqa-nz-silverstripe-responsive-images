# Responsive Images for SilverStripe

## Introduction
This module provides the ability to send a series of image options to the client without actually loading any resources until a media query can be executed. This is particularly useful for sites that use responsive design, because it means that smaller viewports can receive images optimised for their size rather than pulling down a single image optimised for desktop. This module is highly configurable and relies on [picturefill.js](https://github.com/scottjehl/picturefill) for the client-side magic.

## Requirements
SilverStripe 3.0 or higher

## Installation
Place this repository in the root of your SilverStripe project, and run ?flush=1.

## Usage

### Basic implementation
First, define one or many sets of responsive images in your project config file.
```
ResponsiveImageExtension:
  sets:
    ResponsiveSet1:
      sizes:
        - {query: "(min-width: 200px)", size: 100}
        - {query: "(min-width: 800px)", size: 400}
        - {query: "(min-width: 1200px)", size: 800}
    ResponsiveSet2:
      method: CroppedImage
      sizes:
        - {query: "(min-width: 400px)", size: 300x300}
        - {query: "(min-width: 400px) and (min-device-pixel-ratio: 2.0)", size: 600x600}
        - {query: "(min-width: 800px)", size: 700x700}
        - {query: "(min-width: 800px) and and (min-device-pixel-ratio: 2.0), size: 1400x1400}
        - {query: "(min-width: 1000px)", size: 900x900}
        - {query: "(min-width: 1000px) and and (min-device-pixel-ratio: 2.0), size: 1800x1800}
      default_size: 1200x1200
```

Now, run ?flush=1 to refresh the config manifest, and you will have two new methods injected into your Image class.

```
$MyImage.ResponsiveSet1
$MyImage.ResponsiveSet2
```

The output of these methods in the source code will look something like this:
```html
<span data-picture="" data-alt="my-image.jpeg">
    <span data-src="/assets/Uploads/_resampled/SetWidth100-my-image.jpeg" data-media="(min-width: 200px)"></span>    
    <span data-src="/assets/Uploads/_resampled/SetWidth400-my-image.jpeg" data-media="(min-width: 800px)"></span>    
    <span data-src="/assets/Uploads/_resampled/SetWidth800-my-image.jpeg" data-media="(min-width: 1200px)"><img alt="my-image.jpeg" src="/assets/Uploads/_resampled/SetWidth800-my-image.jpeg"></span>
    <noscript>
        &lt;img src="&lt;img src="/assets/mock-files/_resampled/SetWidth640480-my-image.jpeg" alt="my-image.jpeg" /&gt;" alt="my-image.jpeg"&gt;
    </noscript>
</span>
```

The final output to your browser will place the correct image URL into one of the span tags and only one image will render. As the window is resized, new images are loaded into the DOM.


### Setting defaults

Each set should have a "default_size" property set in case the browser does not support media queries. By default, the "default_size" property is 800x600, but this can be overridden in the config.
```
ResponsiveImageExtension:
  default_size: 1200x768
```

You can also pass a default size at the template level.
```
$MyImage.MyResponsiveSet(900x600)
```

The default resampling method is SetWidth, but this can be overridden in your config.
```
ResponsiveImageExtension:
  default_method: CroppedImage
```

It can also be passed into your template function.
```
$MyImage.MyResponsiveSet(800x600, CroppedImage)
```



