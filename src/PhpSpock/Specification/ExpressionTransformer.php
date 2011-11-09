<?php
/**
 * Date: 11/9/11
 * Time: 12:34 PM
 * @author Alex Rudakov <alexandr.rudakov@modera.net>
 */

namespace PhpSpock\Specification;
 
class ExpressionTransformer {


    public function transform($expression)
    {
        $expressionLeft = $expression;
        $expressionRight = '';
        if (strpos($expression, ' >> ') !== false) {
            list($expressionLeft,$expressionRight) = explode(' >> ', $expression);
        }

        if (preg_match('/^
                \s*

                (?P<cardinality>
                    \d+ | \(\d*_?\s*\.\.\s*\d*_?\)
                )

                \s+\*\s+

                \$(?P<var>
                    [a-zA-Z0-9_]+
                )
                ->(?P<method>
                    [a-zA-Z0-9_]+
                )

                \(\s*(?P<arguments>.*)\s*\)
                $/x', $expressionLeft, $mts)) {


                $mockExpr = '';
                $mockExpr .= '$'.$mts['var'].'->shouldReceive("'.$mts['method'].'")';

                $mockExpr .= $this->transformMockArguments($mts['arguments']);
                $mockExpr .= $this->transformMockCardinality($mts['cardinality']);

                if (trim($expressionRight) != '') {
                    $mockExpr .= $this->transformMockReturn($expressionRight);
                }

            return $mockExpr;
        } else {
            return $expression;
        }
    }

    private function transformMockCardinality($expr)
    {
        if (is_numeric($expr)) {
            if ($expr == '0') {
                return '->never()';
            }
            if ($expr == '1') {
                return '->once()';
            }
            if ($expr == '2') {
                return '->twice()';
            }

            return '->times('.$expr.')';
        } else {
            if (preg_match('/^\(0\s*\.\.\s*_\)$/', $expr, $mts)) {
                return '->zeroOrMoreTimes()';
            }
            if (preg_match('/^\((?P<min>\d+)\s*\.\.\s*_\)$/', $expr, $mts)) {
                return '->atLeast()->times('.$mts['min'].')';
            }
            if (preg_match('/^\(_\s*\.\.\s*(?P<max>\d+)\)$/', $expr, $mts)) {
                return '->atMost()->times('.$mts['max'].')';
            }
            if (preg_match('/^\((?P<min>\d+)\s*\.\.\s*(?P<max>\d+)\)$/', $expr, $mts)) {
                return '->between('.$mts['min'].', '.$mts['max'].')';
            }

            throw new ParseException("Can not parse cardinality for mock object: " . $expr);
        }

    }

    private function transformMockReturn($expr)
    {
        $retList = array();

        $isClosure = false;
        foreach($this->splitArgs($expr) as $argExpr) {
            $argExpr = trim($argExpr);


            if (preg_match('/^usingClosure\((?P<args>.*)\)$/', $argExpr, $mts)) {
                $retList[] = $mts['args'];
                $isClosure = true;
            } else {
                if ($isClosure) {
                    throw new \PhpSpock\ParseException("You can not mix closures and values in one mock return statement.");
                }
                $retList[] = $argExpr;
            }
        }
        if (!count($retList)) {
            return '';
        } else {
            return '->andReturn'.($isClosure? 'Using':'').'('.implode(', ', $retList).')';
        }
    }

    private function transformMockArguments($expr)
    {
        if (trim($expr) == '') {
            return '->withNoArgs()';
        }

        if (trim($expr) == '_*_') {
            return '->withAnyArgs()';
        }

        $args = array();

        foreach($this->splitArgs($expr) as $argExpr) {

            $argExpr = trim($argExpr);

            if ($argExpr == '_') {
                $args[] = '\Mockery::any()';
            } elseif ($argExpr == '!null') {
                $args[] = '\Mockery::not(null)';
            } elseif ($argExpr == 'null') {
                $args[] = '\Mockery::mustBe(null)';
            } elseif (preg_match('/^(?P<method>[a-zA-Z0-9_]+)\((?P<args>.*)\)$/', $argExpr, $mts)) {
                $args[] = '\Mockery::' . $mts['method'] . '(' . $mts['args'] . ')';
            } else {
                $args[] = $argExpr;
            }
        }

        return '->with(' . implode(', ', $args) . ')';
    }

    private function splitArgs($expr)
    {
        $tokens = token_get_all('<?php ' . $expr);

        $args = array();
        $current = '';
        $bracesOpen = 0;
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $token = array($token, $token);
            }

            switch ($token[0]) {
                case T_OPEN_TAG:
                    continue(2);

                case '(':
                case '{':
                    $bracesOpen++;
                    $current .= $token[1];
                    break;
                case ')':
                case '}':
                    $bracesOpen--;
                    $current .= $token[1];
                    break;
                case ',':
                    if ($bracesOpen == 0) {
                        $args[] = trim($current);
                        $current = '';
                        break;
                    }
                default:
                    $current .= $token[1];
            }
        }
        if (trim($current) != '') {
            $args[] = trim($current);
            return $args;
        }
        return $args;
    }
}