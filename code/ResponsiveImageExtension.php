<?php

namespace Heyday\SilverStripeResponsiveImages;

use ArrayData;
use ArrayList;
use Config;
use Exception;
use Requirements;

/**
 * An extension to the Image class to inject methods for responsive image sets.
 * Image sets are defined in the config layer, e.g:
 *
 * Heyday\SilverStripeResponsiveImages\ResponsiveImageExtension:
 *   sets:
 *     MyResponsiveImageSet:
 *       method: CroppedImage
 *       sizes:
 *         "(min-width: 200px)": [200, 100]
 *         "(min-width: 800px)": [200, 400]
 *         "(min-width: 1200px) and (min-device-pixel-ratio: 2.0)": [800, 400]
 *       default_args: [200, 400]
 *
 * This provides $MyImage.MyResponsiveImageSet to the template. For more
 * documentation on implementation, see the README file.
 */
class ResponsiveImageExtension extends \Extension
{
    /**
     * @var array
     * @config
     */
    private static $default_args = array(800, 600);

    /**
     * @var string
     * @config
     */
    private static $default_method = 'SetWidth';

    /**
     * @var boolean
     * @config
     */
    private static $htmleditorfield_srcset = true;

    /**
     * @var array
     * @config
     */
    private static $htmleditorfield_srcset_densities = array(1, 2);

    /**
     * @var array A cached copy of the image sets
     */
    protected $configSets;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->configSets = Config::inst()->get(__CLASS__, 'sets');
    }

    /**
     * A wildcard method for handling responsive sets as template functions,
     * e.g. $MyImage.ResponsiveSet1
     *
     * @param string $method The method called
     * @param array $args The arguments passed to the method
     * @return HTMLText
     */
    public function __call($method, $args)
    {
        if ($config = $this->getConfigForSet($method)) {
            return $this->createResponsiveSet($config, $args, $method);
        }
    }

    /**
     * Requires the necessary JS and sends the required HTML structure to the
     * template for a responsive image set.
     *
     * @param array $config The configuration of the responsive image set
     * @param array $defaultArgs The arguments passed to the responsive image
     *                           method call, e.g. $MyImage.ResponsiveSet(800x600)
     * @param string $set The method, or responsive image set, to generate
     * @return SSViewer
     */
    protected function createResponsiveSet($config, $defaultArgs, $set)
    {
        Requirements::javascript(RESPONSIVE_IMAGES_DIR . '/javascript/picturefill/picturefill.min.js');

        if (!isset($config['sizes']) || !is_array($config['sizes'])) {
            throw new Exception("Responsive set $set does not have sizes defined in its config.");
        }

        if (empty($defaultArgs)) {
            if (isset($config['default_args'])) {
                $defaultArgs = $config['default_args'];
            } else {
                $defaultArgs = Config::inst()->get(__CLASS__, 'default_args');
            }
        }

        if (isset($config['method'])) {
            $methodName = $config['method'];
        } else {
            $methodName = Config::inst()->get(__CLASS__, 'default_method');
        }

        $sizes = ArrayList::create();
        foreach ($config['sizes'] as $query => $args) {
            if (is_numeric($query) || !$query) {
                throw new Exception("Responsive set $set has an empty media query defined.
					Please check your config format");
            }

            if (!is_array($args) || empty($args)) {
                throw new Exception("Responsive set $set doesn't have any arguments provided for the query: $query");
            }

            array_unshift($args, $methodName);
            $image = call_user_func_array(array($this->owner, 'getFormattedImage'), $args);
            $sizes->push(ArrayData::create(array(
                'Image' => $image,
                'Query' => $query
            )));
        }

        // The first argument may be an image method such as 'CroppedImage'
        if (!isset($defaultArgs[0]) || !$this->owner->hasMethod($defaultArgs[0])) {
            array_unshift($defaultArgs, $methodName);
        }

        $image = call_user_func_array(array($this->owner, 'getFormattedImage'), $defaultArgs);
        return $this->owner->customise(array(
            'Sizes' => $sizes,
            'DefaultImage' => $image
        ))->renderWith('ResponsiveImageSet');
    }

    /**
     * Due to {@link Object::allMethodNames()} requiring methods to be expressed
     * in all lowercase, getting the config for a given method requires iterating
     * through all the defined sets and making a case-insensitive comparison.
     *
     * @param string $setName The name of the responsive image set to get
     * @return array|false
     */
    protected function getConfigForSet($setName)
    {
        if (!$this->configSets) {
            return false;
        }

        foreach ($this->configSets as $k => $v) {
            if (strtolower($k) === strtolower($setName)) {
                return $v;
            }
        }

        return false;
    }

    /**
     * Returns a list of available image sets.
     *
     * @return array
     */
    protected function getResponsiveSets()
    {
        $list = array();
        foreach ($this->configSets as $setName => $config) {
            $list[] = strtolower($setName);
        }

        return $list;
    }

    /**
     * @param File $imageObject
     * @param DOMElement $imageElement
     */
    public function processImage($imageObject, $imageElement)
    {
        if (!$imageObject || !Config::inst()->get(__CLASS__, 'htmleditorfield_srcset')) {
            return;
        }

        $width = (int)$imageElement->getAttribute('width');
        $height = (int)$imageElement->getAttribute('height');

        $densities = (array)Config::inst()->get(__CLASS__, 'htmleditorfield_srcset_densities');
        $sources = array();
        foreach ($densities as $density) {
            $density = (int)$density;
            $resized = $imageObject->ResizedImage($width * $density, $height * $density);
            // Output in the format "assets/foo.jpg 1x"
            $sources[] = $resized->getRelativePath() . " {$density}x";
        }

        $srcset = implode(', ', $sources);
        $imageElement->setAttribute('srcset', $srcset);
    }

    /**
     * Defines all the methods that can be called in this class.
     *
     * @return array
     */
    public function allMethodNames()
    {
        return $this->getResponsiveSets();
    }
}
