%token ALL STRING NOT EQ NEQ LIKE LT LTE GT GTE ORDER RANGE AND_OP OR_OP IN_OP ORDER_DIRECTION ENTRY EXISTS
%start expression

%%

simple     : ALL | EXISTS
unary      : EQ | NEQ | LIKE | LT | LTE | GT | GTE
variadic   : AND_OP | OR_OP | IN_OP

token      : STRING { $$ = $1; }
           | ORDER_DIRECTION { $$ = $1; }
           ;

simple_expression : simple              { $$ = $this->unaryExpression($1, null); }
                  | simple '(' ')'      { $$ = $this->unaryExpression($1, null); }
                  ;

not_expression   : NOT '(' expression ')'     { $$ = $this->unaryExpression('not', $3); }
                 ;

equality_expression : token            { $$ = $this->unaryExpression('eq', Expression\Literal\LiteralExpression::create($1)); }
                    | '(' token ')'    { $$ = $this->unaryExpression('eq', Expression\Literal\LiteralExpression::create($2)); }
                    ;

unary_expression : unary '(' token ')' { $$ = $this->unaryExpression($1, Expression\Literal\LiteralExpression::create($3)); }
                 | unary '(' complex ')' { $$ = $this->unaryExpression($1, $3); }
                 ;

binary_expression : ENTRY '(' token ',' expression ')' { $$ = $this->binaryExpression($1, Expression\Literal\LiteralExpression::create($3), $5); }
                  | RANGE '(' token ',' token ')' { $$ = $this->binaryExpression($1, Expression\Literal\LiteralExpression::create($3), Expression\Literal\LiteralExpression::create($5)); }
                  ;

order_expression : ORDER '(' token ')' { $$ = $this->orderExpression($3, 'asc'); }
                 | ORDER '(' token ',' ORDER_DIRECTION ')' { $$ = $this->orderExpression($3, $5); }
                 ;

argument_list    : /* empty */                              { $$ = []; }
                 | equality_expression                      { $$ = [ $1 ]; }
                 | unary_expression                         { $$ = [ $1 ]; }
                 | binary_expression                        { $$ = [ $1 ]; }
                 | variadic_expression                      { $$ = [ $1 ]; }
                 | argument_list ',' equality_expression    { $$[] = $3; }
                 | argument_list ',' unary_expression       { $$[] = $3; }
                 | argument_list ',' binary_expression      { $$[] = $3; }
                 | argument_list ',' variadic_expression    { $$[] = $3; }
                 ;

variadic_expression : variadic '(' argument_list ')' { $$ = $this->variadicExpression($1, $3); }

complex    : simple_expression
           | not_expression
           | unary_expression
           | binary_expression
           | order_expression
           | variadic_expression
           ;

expression : /* empty */
           | equality_expression
           | complex
           ;

%%

private $__lex_buffer = '';
private const __QL_OPERATORS = [
    self::ALL => 'all',
    self::NOT => 'not',
    self::EQ => 'eq',
    self::NEQ => 'neq',
    self::LIKE => 'like',
    self::LTE => 'lte',
    self::LT => 'lt',
    self::GTE => 'gte',
    self::GT => 'gt',
    self::ORDER => 'order',
    self::AND_OP => 'and',
    self::OR_OP => 'or',
    self::IN_OP => 'in',
    self::RANGE => 'range',
    self::ENTRY => 'entry',
    self::EXISTS => 'exists',
];

private function yylex(): int
{
    $this->__lex_buffer = preg_replace('/^\s+/', '', $this->__lex_buffer);
    if (preg_match('/^\s*(asc|desc)\s*/i', $this->__lex_buffer, $matches)) {
        $this->yylval = strtolower($matches[1]);
        $this->__lex_buffer = substr($this->__lex_buffer, strlen($matches[0]));

        return self::ORDER_DIRECTION;
    }

    if (preg_match('/^([^\(\)\$\n\r\0\t,]|(?<=\\\\)[\)\(\$])+/', $this->__lex_buffer, $matches)) {
        $this->yylval = trim($matches[0]);
        $this->__lex_buffer = substr($this->__lex_buffer, strlen($matches[0]));

        return self::STRING;
    }

    foreach (self::__QL_OPERATORS as $t => $val) {
        if (preg_match('/^\$'.$val.'/i', $this->__lex_buffer, $matches)) {
            $this->yylval = strtolower($val);
            $this->__lex_buffer = substr($this->__lex_buffer, strlen($matches[0]));

            return $t;
        }
    }

    if (! strlen($this->__lex_buffer)) {
        return 0;
    }

    $val = $this->__lex_buffer[0];
    $this->__lex_buffer = substr($this->__lex_buffer, 1);

    return ord($val);
}

/**
 * Evaluates an unary expression.
 *
 * @param string $type
 * @param mixed $value
 *
 * @return mixed
 */
abstract protected function unaryExpression(string $type, $value);

/**
 * Evaluates a binary expression.
 *
 * @param string $type
 * @param mixed $left
 * @param mixed $right
 *
 * @return mixed
 */
abstract protected function binaryExpression(string $type, $left, $right);

/**
 * Evaluates an order expression.
 *
 * @param string $field
 * @param string $direction
 *
 * @return mixed
 */
abstract protected function orderExpression(string $field, string $direction);

/**
 * Evaluates an expression with variadic arguments.
 *
 * @param string $type
 * @param mixed[] $arguments
 *
 * @return mixed
 */
abstract protected function variadicExpression(string $type, array $arguments);
