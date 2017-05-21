<?php

declare(strict_types=1);

namespace Thunbolt\Bundles;

use Nette\DI\Statement;

class BundleHelper {

	/** @var array */
	private $extensions;

	/** @var array */
	private $bundles;

	public function __construct(array $extensions, array $bundles) {
		foreach ($extensions as $name => $extension) {
			$this->extensions[get_class($extension)] = $name;
		}
		foreach ($bundles as $name => $bundle) {
			$this->bundles[$bundle instanceof Statement ? $bundle->getEntity() : $bundle] = $name;
		}
	}

	public function hasBundle(string $class): bool {
		return array_key_exists($class, $this->bundles);
	}

	public function hasExtension(string $class): bool {
		return array_key_exists($class, $this->extensions);
	}

	public function getExtensionName(string $class): ?string {
		if ($this->hasExtension($class)) {
			return $this->extensions[$class];
		}

		return NULL;
	}

	/**
	 * @throws BundleException
	 */
	public function needExtension(string $class): void {
		if (!$this->hasExtension($class)) {
			throw new BundleException("Bundle needs extension '$class'.");
		}
	}

	/**
	 * @throws BundleException
	 */
	public function needBundle(string $class): void {
		if (!$this->hasBundle($class)) {
			throw new BundleException("Bundle needs other bundle '$class'.");
		}
	}

}
