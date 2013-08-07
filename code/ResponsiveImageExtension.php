<?php




class ResponsiveImageExtension extends DataExtension 
{



	protected $_responsiveSetCache = null;


	protected $_configCache = null;



	public function __call($method, $args) 
	{
		if($config = $this->getConfigForSet($method)) {
			return $this->createResponsiveSet($config, $args, $method);
		}
	}




	protected function createResponsiveSet($config, $args, $method) 
	{
		Requirements::javascript(RESPONSIVE_IMAGES_DIR.'/javascript/picturefill/external/matchmedia.js');
		Requirements::javascript(RESPONSIVE_IMAGES_DIR.'/javascript/picturefill/picturefill.js');

		if(!isset($config['sizes']) || !is_array($config['sizes'])) {
			throw new Exception("Responsive set $method does not have sizes defined in its config.");
		}

		if(isset($args[0])) $defaultDimensions = $args[0];
		elseif(isset($config['full_size'])) $defaultDimensions = $config['full_size'];
		else $defaultDimensions = Config::inst()->forClass("ResponsiveImageExtension")->default_size;

		if(isset($args[1])) $methodName = $args[1];
		elseif(isset($config['method'])) $methodName = $config['method'];
		else $methodName = Config::inst()->forClass("ResponsiveImageExtension")->default_method;

		$sizes = ArrayList::create();
		foreach($config['sizes'] as $i => $arr) {
			if(!isset($arr['query'])) {
				throw new Exeption("Responsive set $method does not have a 'query' element defined for size index $i");
			}
			if(!isset($arr['size'])) {
				throw new Exeption("Responsive set $method does not have a 'size' element defined for size index $i");
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
			'DefaultURL' => $this->owner->getFormattedImage($methodName, $default_width, $default_height)
		))->renderWith('ResponsiveImageSet');
	}



	protected function getConfigForSet($setName) {
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



	protected function getResponsiveSets() {
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



	protected function parseDimensions($size) 
	{
			$width = $size;
			$height = null;
			if(strpos($size, 'x') !== false) {
				return explode("x", $size);
			}

			return array($width, $height);
	}




	public function allMethodNames() 
	{
		$methods = array ('createresponsiveset');
		return array_merge($methods, $this->getResponsiveSets());
	}

}