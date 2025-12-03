<?php

namespace Heyday\ResponsiveImages;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;
use Exception;
use RuntimeException;

/**
 * An extension to the Image class to inject methods for responsive image sets.
 * Image sets are defined in the config layer, e.g:
 *
 * Heyday\ResponsiveImages\ResponsiveImageExtension:
 *   sets:
 *     MyResponsiveImageSet:
 *       method: Fill
 *       arguments:
 *         "(min-width: 200px)": [200, 100]
 *         "(min-width: 800px)": [200, 400]
 *         "(min-width: 1200px) and (min-device-pixel-ratio: 2.0)": [800, 400]
 *       default_arguments: [200, 400]
 *
 * This provides $MyImage.MyResponsiveImageSet to the template. For more
 * documentation on implementation, see the README file.
 */
class ResponsiveImageExtension extends Extension
{
    /**
     * @config
     */
    private static array $default_arguments = [800, 600];

    /**
     * @config
     */
    private static string $default_method = 'ScaleWidth';

    /**
     * @config
     */
    private static string $default_css_classes = '';

    /**
     * @var array A cached copy of the image sets
     */
    protected array $configSets = [];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->configSets = Config::inst()->get(__CLASS__, 'sets') ?: [];
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
        if (!isset($config['arguments']) || !is_array($config['arguments'])) {
            throw new Exception("Responsive set $set does not have any arguments defined in its config.");
        }

        if (empty($defaultArgs)) {
            if (isset($config['default_arguments'])) {
                $defaultArgs = $config['default_arguments'];
            } else {
                $defaultArgs = Config::inst()->get(__CLASS__, 'default_arguments');
            }
        }

        if (isset($config['method'])) {
            $methodName = $config['method'];
        } else {
            $methodName = Config::inst()->get(__CLASS__, 'default_method');
        }


        if (!$this->owner->hasMethod($methodName)) {
            throw new RuntimeException(get_class($this->owner) . ' has no method ' . $methodName);
        }

        // Create the resampled images for each query in the set
        $sizes = ArrayList::create();
        foreach ($config['arguments'] as $query => $args) {
            if (is_numeric($query) || !$query) {
                throw new Exception("Responsive set $set has an empty media query. Please check your config format");
            }

            if (!is_array($args) || empty($args)) {
                throw new Exception("Responsive set $set doesn't have any arguments provided for the query: $query");
            }

            $sizes->push(ArrayData::create([
                'Image' => $this->getResampledImage($methodName, $args),
                'Query' => $query
            ]));
        }

        if (isset($config['css_classes'])) {
            $extraClasses = $config['css_classes'];
        } else {
            $extraClasses = Config::inst()->get(__CLASS__, 'default_css_classes');
        }

        $templatePath = isset($config['template']) ? $config['template'] : 'Includes/ResponsiveImageSet';

        return $this->owner->customise([
            'Sizes' => $sizes,
            'ExtraClasses' => $extraClasses,
            'DefaultImage' => $this->getResampledImage($methodName, $defaultArgs)
        ])->renderWith($templatePath);
    }

    /**
     * Return a resampled image equivalent to $Image.MethodName(...$args) in a template
     *
     * @param string $methodName
     * @param array $args
     * @return Image
     */
    protected function getResampledImage($methodName, $args)
    {
        return call_user_func_array([$this->owner, $methodName], $args);
    }

    /**
     * Due to {@link Object::allMethodNames()} requiring methods to be expressed
     * in all lowercase, getting the config for a given method requires a
     * case-insensitive comparison.
     *
     * @param string $setName The name of the responsive image set to get
     */
    protected function getConfigForSet(string $setName): null|bool|array
    {
        $name = strtolower($setName);
        $sets = array_change_key_case($this->configSets, CASE_LOWER);

        return (isset($sets[$name])) ? $sets[$name] : false;
    }

    /**
     * Returns a list of available image sets.
     */
    protected function getResponsiveSets(): array
    {
        return array_map('strtolower', array_keys($this->configSets));
    }

    /**
     * Defines all the methods that can be called in this class.
     */
    public function allMethodNames(): array
    {
        return $this->getResponsiveSets();
    }
}
