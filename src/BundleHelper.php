<?php

namespace Thunbolt\Bundles;

use Nette\DI\Statement;

class BundleHelper {

	/** @var array */
	private $extensions;

	/** @var array */
	private $bundles;

	/**
	 * @param array $extensions
	 * @param array $bundles
	 */
	public function __construct(array $extensions, array $bundles) {
		foreach ($extensions as $name => $extension) {
			$this->extensions[get_class($extension)] = $name;
		}
		foreach ($bundles as $name => $bundle) {
			$this->bundles[$bundle instanceof Statement ? $bundle->getEntity() : $bundle] = $name;
		}
	}

	/**
	 * @param string $class
	 * @return bool
	 */
	public function hasBundle($class) {
		return array_key_exists($class, $this->bundles);
	}

	/**
	 * @param string $class
	 * @return bool
	 */
	public function hasExtension($class) {
		return array_key_exists($class, $this->extensions);
	}

	/**
	 * @param string $class
	 * @return string|null
	 */
	public function getExtensionName($class) {
		if ($this->hasExtension($class)) {
			return $this->extensions[$class];
		}

		return NULL;
	}

	/**
	 * @param string $class
	 * @throws BundleException
	 */
	public function needExtension($class) {
		if (!$this->hasExtension($class)) {
			throw new BundleException("Bundle needs extension '$class'.");
		}
	}

	/**
	 * @param string $class
	 * @throws BundleException
	 */
	public function needBundle($class) {
		if (!$this->hasBundle($class)) {
			throw new BundleException("Bundle needs other bundle '$class'.");
		}
	}

}
