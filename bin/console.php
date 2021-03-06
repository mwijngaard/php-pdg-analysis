<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use PhpPdgAnalysis\Analysis\LibraryInfo;
use PhpPdgAnalysis\Analysis\Visitor\FuncCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FuncEvalCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FuncIncludeCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FuncExceptionCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FuncVarFeatureCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\DuplicateNameCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\MagicMethodCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\ClassCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\ClosureCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FilesWithTopLevelLogicCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FilesWithTopLevelVariablesCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FileCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\CreateFunctionCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\CallUserFuncCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\CallCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FuncYieldGotoCountingVisitor;
use PhpPdgAnalysis\Analysis\Visitor\FuncPossibleAliasCountingVisitor;
use PhpPdgAnalysis\Analysis\ProgramDependence\DataDependenceCountsAnalysis;
use PhpPdgAnalysis\Analysis\ProgramDependence\MaybeDataDependenceAnalysis;
use PhpPdgAnalysis\Analysis\SystemDependence\ResolvedCallCountsAnalysis;
use PhpPdgAnalysis\Analysis\SystemDependence\OverloadingCountsAnalysis;
use PhpPdgAnalysis\Table\Overview;
use PhpPdgAnalysis\Table\FuncIncludes;
use PhpPdgAnalysis\Table\FuncEvalInclude;
use PhpPdgAnalysis\Table\FuncVarVar;
use PhpPdgAnalysis\Table\MethodOverloading;
use PhpPdgAnalysis\Table\DuplicateNames;
use PhpPdgAnalysis\Table\DynamicCalls;
use PhpPdgAnalysis\Table\ResolvedFunctionCalls;
use PhpPdgAnalysis\Table\ResolvedMethodCalls;
use PhpPdgAnalysis\Table\PropertyOverloading;
use PhpPdgAnalysis\Table\DataDependences;
use PhpPdgAnalysis\Table\DataDependences2;
use PhpPdgAnalysis\Table\FuncException;
use PhpPdgAnalysis\Table\FuncYield;
use PhpPdgAnalysis\Table\AnalysisResult;
use PhpPdgAnalysis\Table\CallFeatures;
use PhpPdgAnalysis\Table\FuncPossibleAlias;
use PhpPdgAnalysis\Plot\EvalMaybeDependences;
use PhpPdgAnalysis\Command\AnalysisClearCommand;
use PhpPdgAnalysis\Command\AnalysisRunCommand;
use PhpPdgAnalysis\Command\AnalysisListCommand;
use PhpPdgAnalysis\Command\TablePrintCommand;
use PhpPdgAnalysis\Command\TableListCommand;
use PhpPdgAnalysis\Command\SliceCommand;
use PhpPdgAnalysis\Command\PlotListCommand;
use PhpPdgAnalysis\Command\PlotPrintCommand;
use PhpPdgAnalysis\Command\CallPairsFromSdgCommand;
use PhpPdgAnalysis\Command\CallPairsFromTraceCommand;
use PhpPdgAnalysis\Command\CallPairsCompareCommand;

assert_options(ASSERT_BAIL, 1);
gc_disable();

$libraryRoot = 'C:\Users\mwijngaard\Documents\Projects\_verification';
$cacheFile = __DIR__ . '/cache.json';
$cacheDir = __DIR__ . '/cache';
$directoryAnalyses = [
	"library-info" => new LibraryInfo(),
];
ksort($directoryAnalyses);
$analysingVisitors = [
	"func-count" => new FuncCountingVisitor(),
	"func-eval-count" => new FuncEvalCountingVisitor(),
	"func-include-count" => new FuncIncludeCountingVisitor(),
	"func-var-feature-count" => new FuncVarFeatureCountingVisitor(),
	'duplicate-name-count' => new DuplicateNameCountingVisitor(),
	'magic-method-count' => new MagicMethodCountingVisitor(),
	'class-count' => new ClassCountingVisitor(),
	'closure-count' => new ClosureCountingVisitor(),
	'files-with-top-level-logic-count' => new FilesWithTopLevelLogicCountingVisitor(),
	'files-with-top-level-variables-count' => new FilesWithTopLevelVariablesCountingVisitor(),
	'file-count' => new FileCountingVisitor(),
	'create-function-count' => new CreateFunctionCountingVisitor(),
	'call-user-func-count' => new CallUserFuncCountingVisitor(),
	'call-count' => new CallCountingVisitor(),
	'func-exception-count' => new FuncExceptionCountingVisitor(),
	'func-yield-goto-count' => new FuncYieldGotoCountingVisitor(),
	'func-possible-alias-count' => new FuncPossibleAliasCountingVisitor(),
];
ksort($analysingVisitors);
$funcAnalyses = [
	'data-dependence-counts' => new DataDependenceCountsAnalysis(),
	'maybe-data-dependence-counts' => new MaybeDataDependenceAnalysis(),
];
ksort($funcAnalyses);
$systemAnalyses = [
	'resolved-call-counts' => new ResolvedCallCountsAnalysis(),
	'overloading-counts' => new OverloadingCountsAnalysis(),
];
ksort($systemAnalyses);
$tables = [
	"overview" => new Overview(),
	"func-eval" => new FuncEvalInclude(),
	"func-includes" => new FuncIncludes(),
	"func-var-var" => new FuncVarVar(),
	'duplicate-names' => new DuplicateNames(),
	'dynamic-calls' => new DynamicCalls(),
	'resolved-function-calls' => new ResolvedFunctionCalls(),
	'resolved-method-calls' => new ResolvedMethodCalls(),
	'property-overloading' => new PropertyOverloading(),
	"method-overloading" => new MethodOverloading(),
	'data-dependences' => new DataDependences(),
	'data-dependences2' => new DataDependences2(),
	'func-exception' => new FuncException(),
	'func-yield' => new FuncYield(),
	'analysis-result' => new AnalysisResult(),
	'call-features' => new CallFeatures(),
	'func-possible-alias' => new FuncPossibleAlias(),
];
ksort($tables);
$plots = [
	'eval-maybe' => new EvalMaybeDependences(),
];
ksort($plots);

$application = new Application();
$application->add(new AnalysisClearCommand($cacheFile));
$application->add(new AnalysisRunCommand($libraryRoot, $cacheFile, $cacheDir, $directoryAnalyses, $analysingVisitors, $funcAnalyses, $systemAnalyses));
$application->add(new AnalysisListCommand($directoryAnalyses, $analysingVisitors));
$application->add(new TablePrintCommand($cacheFile, $tables));
$application->add(new TableListCommand($tables));
$application->add(new SliceCommand());
$application->add(new PlotListCommand($plots));
$application->add(new PlotPrintCommand($cacheFile, $plots));
$application->add(new CallPairsFromSdgCommand($cacheDir));
$application->add(new CallPairsFromTraceCommand());
$application->add(new CallPairsCompareCommand());
$application->run();