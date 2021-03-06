<?php

namespace PhpPdgAnalysis\Analysis\SystemDependence;

use PHPCfg\Op\Expr\Assign;
use PHPCfg\Op\Expr\AssignRef;
use PHPCfg\Op\Expr\Isset_;
use PHPCfg\Op\Expr\MethodCall;
use PHPCfg\Op\Expr\PropertyFetch;
use PHPCfg\Op\Expr\StaticCall;
use PHPCfg\Op\Terminal\Unset_;
use PHPCfg\Operand\Literal;
use PhpParser\Node\Expr\AssignOp;
use PhpPdg\ProgramDependence\Node\OpNode;
use PhpPdg\SystemDependence\Node\FuncNode;
use PhpPdg\SystemDependence\System;

class OverloadingCountsAnalysis implements SystemAnalysisInterface {
	public function analyse(System $system) {
		$__issetNodeCount = 0;
		$__issetNodesLinkedCount = 0;
		$__issetOverloadingCallEdgeCount = 0;
		$__issetExplicitCallEdgeCount = 0;

		$__unsetNodeCount = 0;
		$__unsetNodesLinkedCount = 0;
		$__unsetOverloadingCallEdgeCount = 0;
		$__unsetExplicitCallEdgeCount = 0;

		$__setNodeCount = 0;
		$__getNodesLinkedCount = 0;
		$__getOverloadingCallEdgeCount = 0;
		$__getExplicitCallEdgeCount = 0;

		$__getNodeCount = 0;
		$__setNodesLinkedCount = 0;
		$__setOverloadingCallEdgeCount = 0;
		$__setExplicitCallEdgeCount = 0;

		$__callNodeCount = 0;
		$__callNodesLinkedCount = 0;
		$__callOverloadingCallEdgeCount = 0;
		$__callExplicitCallEdgeCount = 0;

		$__callStaticNodeCount = 0;
		$__callStaticNodesLinkedCount = 0;
		$__callStaticOverloadingCallEdgeCount = 0;
		$__callStaticExplicitCallEdgeCount = 0;

		foreach ($system->sdg->getNodes() as $node) {
			if ($node instanceof FuncNode) {
				$func = $node->getFunc();
				if($func->class_name !== null) {
					$name = strtolower($func->name);
					if (in_array($name, ['__isset', '__unset', '__set', '__get', '__call', '__callstatic'], true)) {
						$edges = $system->sdg->getEdges(null, $node, ['type' => 'call']);
						switch ($name) {
							case '__isset':
								$__issetNodeCount++;
								if (count($edges) > 0) {
									$__issetNodesLinkedCount++;
									foreach ($edges as $edge) {
										$from_node = $edge->getFromNode();
										if ($from_node instanceof OpNode) {
											$from_op = $from_node->op;
											if ($from_op instanceof Isset_) {
												$__issetOverloadingCallEdgeCount++;
											} else if ($from_op instanceof MethodCall || $from_op instanceof StaticCall) {
												$__issetExplicitCallEdgeCount++;
											} else {
												throw new \LogicException(sprintf("Found call to __isset from op %s@%s:%d", $from_op->getType(), $from_op->getFile(), $from_op->getLine()));
											}
										}
									}
								}
								break;
							case '__unset':
								$__unsetNodeCount++;
								if (count($edges) > 0) {
									$__unsetNodesLinkedCount++;
									foreach ($edges as $edge) {
										$from_node = $edge->getFromNode();
										if ($from_node instanceof OpNode) {
											$from_op = $from_node->op;
											if ($from_op instanceof Unset_) {
												$__unsetOverloadingCallEdgeCount++;
											} else if ($from_op instanceof MethodCall || $from_op instanceof StaticCall) {
												$__unsetExplicitCallEdgeCount++;
											} else {
												throw new \LogicException(sprintf("Found call to __unset from op %s@%s:%d", $from_op->getType(), $from_op->getFile(), $from_op->getLine()));
											}
										}
									}
								}
								break;
							case '__set':
								$__setNodeCount++;
								if (count($edges) > 0) {
									$__setNodesLinkedCount++;
									foreach ($edges as $edge) {
										$from_node = $edge->getFromNode();
										if ($from_node instanceof OpNode) {
											$from_op = $from_node->op;
											if ($from_op instanceof Assign || $from_op instanceof AssignRef || $from_op instanceof AssignOp) {
												$__setOverloadingCallEdgeCount++;
											} else if ($from_op instanceof MethodCall || $from_op instanceof StaticCall) {
												$__setExplicitCallEdgeCount++;
											} else {
												throw new \LogicException(sprintf("Found call to __set from op %s@%s:%d", $from_op->getType(), $from_op->getFile(), $from_op->getLine()));
											}
										}
									}
								}
								break;
							case '__get':
								$__getNodeCount++;
								if (count($edges) > 0) {
									$__getNodesLinkedCount++;
									foreach ($edges as $edge) {
										$from_node = $edge->getFromNode();
										if ($from_node instanceof OpNode) {
											$from_op = $from_node->op;
											if ($from_op instanceof PropertyFetch) {
												$__getOverloadingCallEdgeCount++;
											} else if ($from_op instanceof MethodCall || $from_op instanceof StaticCall) {
												$__getExplicitCallEdgeCount++;
											} else {
												throw new \LogicException(sprintf("Found call to __get from op %s@%s:%d", $from_op->getType(), $from_op->getFile(), $from_op->getLine()));
											}
										}
									}
								}
								break;
							case '__call':
								$__callNodeCount++;
								if (count($edges) > 0) {
									$__callNodesLinkedCount++;
									foreach ($edges as $edge) {
										$from_node = $edge->getFromNode();
										if ($from_node instanceof OpNode) {
											$from_op = $from_node->op;
											assert($from_op instanceof MethodCall || $from_op instanceof StaticCall);
											assert($from_op->name instanceof Literal);
											if (strtolower($from_op->name->value) === '__call') {
												$__callExplicitCallEdgeCount++;
											} else {
												assert($from_op instanceof MethodCall);
												$__callOverloadingCallEdgeCount++;
											}
										}
									}
								}
								break;
							case '__callstatic':
								$__callStaticNodeCount++;
								if (count($edges) > 0) {
									$__callStaticNodesLinkedCount++;
									foreach ($edges as $edge) {
										$from_node = $edge->getFromNode();
										if ($from_node instanceof OpNode) {
											$from_op = $from_node->op;
											assert($from_op instanceof MethodCall || $from_op instanceof StaticCall);
											assert($from_op->name instanceof Literal);
											if (strtolower($from_op->name->value) === '__callstatic') {
												$__callStaticExplicitCallEdgeCount++;
											} else {
												assert($from_op instanceof StaticCall);
												$__callStaticOverloadingCallEdgeCount++;
											}
										}
									}
								}
								break;
							default:
								throw new \LogicException("Not possible");
						}
					}
				}
			}
		}

		return array_combine($this->getSuppliedAnalysisKeys(), [
			$__issetNodeCount,
			$__issetNodesLinkedCount,
			$__issetOverloadingCallEdgeCount,
			$__issetExplicitCallEdgeCount,

			$__unsetNodeCount,
			$__unsetNodesLinkedCount,
			$__unsetOverloadingCallEdgeCount,
			$__unsetExplicitCallEdgeCount,

			$__getNodeCount,
			$__getNodesLinkedCount,
			$__getOverloadingCallEdgeCount,
			$__getExplicitCallEdgeCount,

			$__setNodeCount,
			$__setNodesLinkedCount,
			$__setOverloadingCallEdgeCount,
			$__setExplicitCallEdgeCount,

			$__callNodeCount,
			$__callNodesLinkedCount,
			$__callOverloadingCallEdgeCount,
			$__callExplicitCallEdgeCount,

			$__callStaticNodeCount,
			$__callStaticNodesLinkedCount,
			$__callStaticOverloadingCallEdgeCount,
			$__callStaticExplicitCallEdgeCount,
		]);
	}

	public function getSuppliedAnalysisKeys() {
		return [
			'__issetNodeCount',
			'__issetNodesLinkedCount',
			'__issetOverloadingCallEdgeCount',
			'__issetExplicitCallEdgeCount',

			'__unsetNodeCount',
			'__unsetNodesLinkedCount',
			'__unsetOverloadingCallEdgeCount',
			'__unsetExplicitCallEdgeCount',

			'__getNodeCount',
			'__getNodesLinkedCount',
			'__getOverloadingCallEdgeCount',
			'__getExplicitCallEdgeCount',

			'__setNodeCount',
			'__setNodesLinkedCount',
			'__setOverloadingCallEdgeCount',
			'__setExplicitCallEdgeCount',

			'__callNodeCount',
			'__callNodesLinkedCount',
			'__callOverloadingCallEdgeCount',
			'__callExplicitCallEdgeCount',

			'__callStaticNodeCount',
			'__callStaticNodesLinkedCount',
			'__callStaticOverloadingCallEdgeCount',
			'__callStaticExplicitCallEdgeCount',
		];
	}
}