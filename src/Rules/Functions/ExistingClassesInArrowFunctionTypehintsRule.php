<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\FunctionDefinitionCheck;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\ArrowFunction>
 */
class ExistingClassesInArrowFunctionTypehintsRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\FunctionDefinitionCheck $check;

	public function __construct(FunctionDefinitionCheck $check)
	{
		$this->check = $check;
	}

	public function getNodeType(): string
	{
		return Node\Expr\ArrowFunction::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->check->checkAnonymousFunction(
			$scope,
			$node->getParams(),
			$node->getReturnType(),
			'Parameter $%s of anonymous function has invalid typehint type %s.',
			'Return typehint of anonymous function has invalid type %s.',
			'Anonymous function uses native union types but they\'re supported only on PHP 8.0 and later.'
		);
	}

}
