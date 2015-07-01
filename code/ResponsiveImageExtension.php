<?php



/**
 * Defines the extension to the Image class that injects methods for
 * for responsive image sets. Image sets are defined in the config layer, e.g:
 * 
 * ResponsiveImageExtension:
 *   sets:
 *     MyResponsiveImageSet:
 *       sizes:
 *         - {query: "(min-width: 200px)", size: 100}
 *         - {query: "(min-width: 800px)", size: 400}
 *         - {query: "(min-width: 1200px)  and (min-device-pixel-ratio: 2.0)", size: 800}
 *
 * This provides $MyImage.MyResponsiveImageSet to the template.
 *
 * For more documentation on implementation, see the README file.
 *
 * @package heyday/silverstripe-responsive-images
 * @author Aaron Carlino <aaron.carlino@heyday.co.nz>
 *
 */
class ResponsiveImageExtension extends DataExtension 
{


	/**
	 * @var array The list of responsive set methods, in lowercase, to be injected into the Image class
	 */
	protected $_responsiveSetCache = null;



	/**
	 * @var Config_ForClass A cached copy of the config object for ResponsiveImageExtension
	 */
	protected $_configCache = null;




	/**
	 * A wildcard method for handling responsive sets as template functions,
	 * e.g. $MyImage.ResponsiveSet1
	 *
	 * @param string $method The method called
	 * @param array $args The arguments passed to the method
	 * @return SSViewer
	 */
	public function __call($method, $args) 
	{

		if($config = $this->getConfigForSet($method)) {
			return $this->createResponsiveSet($config, $args, $method);
		}
	}



	/**
	 * Requires the necessary JS and sends the required HTML structure to the template
	 * for a responsive image set
	 *
	 * @param array $config The configuration of the responsive image set from the config
	 * @param array $args The arguments passed to the responsive image method, e.g. $MyImage.ResponsiveSet1(800x600)
	 * @param string $method The method, or responsive image set, to generate
	 * @return SSViewer
	 */
	protected function createResponsiveSet($config, $args, $method) 
	{
//		Requirements::javascript(RESPONSIVE_IMAGES_DIR.'/javascript/picturefill/external/matchmedia.js');
		Requirements::javascript(RESPONSIVE_IMAGES_DIR.'/javascript/picturefill/picturefill.min.js');

		if(!isset($config['sizes']) || !is_array($config['sizes'])) {
			throw new Exception("Responsive set $method does not have sizes defined in its config.");
		}

		if(isset($args[0])) $defaultDimensions = $args[0];
		elseif(isset($config['default_size'])) $defaultDimensions = $config['default_size'];
		else $defaultDimensions = Config::inst()->forClass("ResponsiveImageExtension")->default_size;

		if(isset($args[1])) $methodName = $args[1];
		elseif(isset($config['method'])) $methodName = $config['method'];
		else $methodName = Config::inst()->forClass("ResponsiveImageExtension")->default_method;

		$sizes = ArrayList::create();
		foreach($config['sizes'] as $i => $arr) {
			if(!isset($arr['query'])) {
				throw new Exception("Responsive set $method does not have a 'query' element defined for size index $i");
			}
			if(!isset($arr['size'])) {
				throw new Exception("Responsive set $method does not have a 'size' element defined for size index $i");
			}			

			list($width, $height) = $this->parseDimensions($arr['size']);
			$sizes->push(ArrayData::create(array(
				'Image' => $this->owner->getFormattedImage($methodName, $width, $height),
				'Query' => $arr['query']
			)));

		}

		list($default_width, $default_height) = $this->parseDimensions($defaultDimensions);
		return $this->owner->customise(array(
			'Sizes' => $sizes,
			'DefaultImage' => $this->owner->getFormattedImage($methodName, $default_width, $default_height)
		))->renderWith('ResponsiveImageSet');
	}




	/**
	 * Due to {@link Object::allMethodNames()} requiring methods to be expressed
	 * in all lowercase, getting the config for a given method requires iterating
	 * through all the defined sets and making a case-insensitive comparison.
	 *
	 * @param string $setName The name of the responsive image set to get
	 * @return array	 
	 */
	protected function getConfigForSet($setName) 
	{
		if(!$this->_configCache) {
			$this->_configCache = Config::inst()->forClass("ResponsiveImageExtension")->sets;
		}

		if($this->_configCache) {
			foreach($this->_configCache as $k => $v) {
				if(strtolower($k) == strtolower($setName)) {
					return $v;
				}
			}
		}

		return false;
	}




	/**
	 * An accessor for $_responsiveSetCache. Stores cache if not set
	 *
	 * @return array
	 */
	protected function getResponsiveSets() 
	{
		if(!$this->_responsiveSetCache) {
			$list = array ();
			if($sets = Config::inst()->forClass("ResponsiveImageExtension")->sets) {			
				foreach($sets as $setName => $config) {
					$list[] = strtolower($setName);
				}				
			}
			$this->_responsiveSetCache = $list;
		}

		return $this->_responsiveSetCache;
	}



	/**
	 * Parses a string such as "400" or "400x600" and returns width and height values
	 *
	 * @param string $size The string to parse
	 * @return array
	 * @todo Should this be a static method?
	 */
	protected function parseDimensions($size) 
	{
			$width = $size;
			$height = null;
			if(strpos($size, 'x') !== false) {
				return explode("x", $size);
			}

			return array($width, $height);
	}




	/**
	 * Defines all the methods that can be called in this class
	 *
	 * @return array
	 */
	public function allMethodNames() 
	{
		$methods = array ('createresponsiveset');
		return array_merge($methods, $this->getResponsiveSets());
	}


}
