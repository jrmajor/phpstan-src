<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Elements\AnyOf;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Elements\Type;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function count;
use function is_array;

class ParametersSchemaExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::arrayOf(Expect::type(Statement::class))->min(1);
	}

	public function loadConfiguration(): void
	{
		/** @var mixed[] $config */
		$config = $this->config;
		$config['__parametersSchema'] = new Statement(Schema::class);
		$builder = $this->getContainerBuilder();
		$builder->parameters['__parametersSchema'] = $this->processArgument(
			new Statement('schema', [
				new Statement('structure', [$config]),
			]),
		);
	}

	/**
	 * @param Statement[] $statements
	 */
	private function processSchema(array $statements): Schema
	{
		if (count($statements) === 0) {
			throw new ShouldNotHappenException();
		}

		$parameterSchema = null;
		foreach ($statements as $statement) {
			$processedArguments = array_map(fn ($argument) => $this->processArgument($argument), $statement->arguments);
			if ($parameterSchema === null) {
				/** @var Type|AnyOf|Structure $parameterSchema */
				$parameterSchema = Expect::{$statement->getEntity()}(...$processedArguments);
			} else {
				$parameterSchema->{$statement->getEntity()}(...$processedArguments);
			}
		}

		$parameterSchema->required();

		return $parameterSchema;
	}

	/**
	 * @param mixed $argument
	 * @return mixed
	 */
	private function processArgument($argument)
	{
		if ($argument instanceof Statement) {
			if ($argument->entity === 'schema') {
				$arguments = [];
				foreach ($argument->arguments as $schemaArgument) {
					if (!$schemaArgument instanceof Statement) {
						throw new ShouldNotHappenException('schema() should contain another statement().');
					}

					$arguments[] = $schemaArgument;
				}

				if (count($arguments) === 0) {
					throw new ShouldNotHappenException('schema() should have at least one argument.');
				}

				return $this->processSchema($arguments);
			}

			return $this->processSchema([$argument]);
		} elseif (is_array($argument)) {
			$processedArray = [];
			foreach ($argument as $key => $val) {
				$processedArray[$key] = $this->processArgument($val);
			}

			return $processedArray;
		}

		return $argument;
	}

}
