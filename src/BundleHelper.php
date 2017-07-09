<?php

declare(strict_types=1);

namespace Thunbolt\Bundles;

use Nette\DI\Compiler;
use Nette\DI\Statement;

class BundleHelper {

	/** @var array */
	private $bundles;

	/** @var Compiler */
	private $compiler;

	public function __construct(Compiler $compiler, array $bundles) {
		$this->compiler = $compiler;
		foreach ($bundles as $name => $bundle) {
			$this->bundles[$bundle instanceof Statement ? $bundle->getEntity() : $bundle] = $name;
		}
	}

	/**
	 * @return Compiler
	 */
	public function getCompiler(): Compiler {
		return $this->compiler;
	}

	/**
	 * @param string $class
	 * @return bool
	 */
	public function hasBundle(string $class): bool {
		return array_key_exists($class, $this->bundles);
	}

	/**
	 * @param string $class
	 * @throws BundleException
	 */
	public function needBundle(string $class): void {
		if (!$this->hasBundle($class)) {
			throw new BundleException("Bundle needs other bundle '$class'.");
		}
	}

}
