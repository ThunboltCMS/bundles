<?php

declare(strict_types=1);

namespace Thunbolt\Bundles;

interface IBundlesInfoProvider {

	public function getBundlesInfo(): BundlesInfo;

}
