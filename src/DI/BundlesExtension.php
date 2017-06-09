<?php

declare(strict_types=1);

namespace Thunbolt\Bundles\DI;

use Kdyby\Doctrine\DI\IEntityProvider;
use Kdyby\Doctrine\DI\OrmExtension;
use Kdyby\Translation\DI\ITranslationProvider;
use Kdyby\Translation\DI\TranslationExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Thunbolt\Bundles\BundleException;
use Thunbolt\Bundles\BundleHelper;
use Thunbolt\Bundles\BundlesInfo;
use Thunbolt\Bundles\Conflicts\IEntityProviderReplacement;
use Thunbolt\Bundles\Conflicts\ITranslationReplacement;
use Thunbolt\Bundles\IBundleExtension;
use Thunbolt\Bundles\IBundlesInfoProvider;

if (!interface_exists(ITranslationProvider::class)) {
	class_alias(ITranslationReplacement::class, ITranslationProvider::class);
}
if (!interface_exists(IEntityProvider::class)) {
	class_alias(IEntityProviderReplacement::class, IEntityProvider::class);
}

/**
 * @internal
 */
final class BundlesExtension extends CompilerExtension implements ITranslationProvider, IEntityProvider, IBundlesInfoProvider {

	const EXTENSION_NAME = 'bundles';

	const BUNDLE_PREFIX = 'bundle.';

	/** @var array */
	private $transPaths = [];

	/** @var array */
	private $entityPaths = [];

	/** @var BundleHelper */
	private $helper;

	/** @var BundlesInfo */
	private $infoObject;

	public function load(): void {
		$config = $this->getConfig();
		$hasTranslator = class_exists(TranslationExtension::class);
		$hasEntityProvider = class_exists(OrmExtension::class);

		$this->helper = new BundleHelper($this->compiler->getExtensions(), $config);
		$namespaces = [];
		foreach ($config as $name => $class) {
			$object = $this->createExtension((string) $name, $class);
			$this->compiler->addExtension(self::BUNDLE_PREFIX . $name, $object);
			$object->startup();

			$path = realpath($object->getBaseFolder());
			$namespaces[$name] = $namespace = $this->extractNamespace($object, $class);
			if ($hasTranslator && is_dir($path . '/translations')) {
				$this->transPaths[] = $path . '/translations';
			}
			if ($hasEntityProvider && is_dir($path . '/Model')) {
				$this->entityPaths[$namespace . '\\Model'] = $path . '/Model';
			}
		}

		$this->infoObject = new BundlesInfo($namespaces);
	}

	private function extractNamespace(IBundleExtension $object, string $class): string {
		if (method_exists($object, 'getNamespace')) {
			$namespace = $object->getNamespace();
		} else {
			$namespace = substr($class, 0, strrpos($class, '\\'));
			$namespace = substr($namespace, 0, strrpos($namespace, '\\'));
		}

		return $namespace;
	}

	private function createExtension(string $name, &$class): IBundleExtension {
		if ($class instanceof Statement) {
			$reflection = new \ReflectionClass($class->getEntity());
			array_unshift($class->arguments, $this->helper);
			$object = $reflection->newInstanceArgs($class->arguments);
			$class = $class->getEntity();
		} else if (!class_exists($class)) {
			throw new BundleException("Bundle '$class' not exists in bundle '$name'");
		} else {
			$object= new $class($this->helper);
		}
		if (!$object instanceof IBundleExtension) {
			throw new BundleException("Bundle '$class' must implements " . IBundleExtension::class);
		}
		if (!$object instanceof CompilerExtension) {
			throw new BundleException("Bundle '$class' must be instance of " . CompilerExtension::class);
		}

		return $object;
	}

	public function getBundlesInfo(): BundlesInfo {
		return $this->infoObject;
	}

	public function getEntityMappings(): array {
		return $this->entityPaths;
	}

	public function getTranslationResources(): array {
		return $this->transPaths;
	}

	public static function register(Configurator $configurator, ?string $extensionName = NULL): void {
		$extensionName = $extensionName ?: self::EXTENSION_NAME;
		$configurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) use ($extensionName) {
			$self = new self();
			$self->setCompiler($compiler, $extensionName);
			$self->setConfig($compiler->getConfig()[$extensionName] ?? []);
			$self->load();

			$compiler->addExtension($extensionName, $self);
		};
	}

}
