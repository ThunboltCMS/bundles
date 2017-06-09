<?php

declare(strict_types=1);

namespace Thunbolt\Bundles;

class BundlesInfo {

	/** @var array */
	private $namespaces;

	public function __construct(array $namespaces) {
		$this->namespaces = $namespaces;
	}

	/**
	 * @return array
	 */
	public function getNamespaces(): array {
		return $this->namespaces;
	}

}
