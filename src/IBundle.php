<?php

declare(strict_types=1);

namespace Thunbolt\Bundles;

interface IBundle {

	/**
	 * Initialization function
	 */
	public function startup(): void;

	/**
	 * Returns base folder of bundle
	 */
	public function getBaseFolder(): string;

}
