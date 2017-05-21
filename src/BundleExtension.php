<?php

declare(strict_types=1);

namespace Thunbolt\Bundles;

use Nette\DI\CompilerExtension;

abstract class BundleExtension extends CompilerExtension implements IBundleExtension {

	/** @var BundleHelper */
	protected $helper;

	public function __construct(BundleHelper $helper) {
		$this->helper = $helper;
	}

	public function startup(): void {}

}
