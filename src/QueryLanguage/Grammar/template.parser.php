<?php declare(strict_types=1);
$meta @
@semval($) $yyval
@semval($,%t) $yyval
@semval(%n) $yyastk[$yysp-(%l-%n)]
@semval(%n,%t) $yyastk[$yysp-(%l-%n)]
@include;

// $namespace

use Fazland\ApiPlatformBundle\QueryLanguage\Exception\InvalidArgumentException;
use Fazland\ApiPlatformBundle\QueryLanguage\Exception\SyntaxError;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression;

abstract class @(CLASSNAME)
{
@tokenval
    private const %s = %n;
@endtokenval

    private const YYBADCH = @(YYBADCH);
    private const YYMAXLEX = @(YYMAXLEX);
    private const YYTERMS = @(YYTERMS);
    private const YYNONTERMS = @(YYNONTERMS);

    private const YYLAST = @(YYLAST);
    private const YY2TBLSTATE = @(YY2TBLSTATE);
    private const YYGLAST = @(YYGLAST);


    private const YYSTATES = @(YYSTATES);
    private const YYNLSTATES = @(YYNLSTATES);
    private const YYINTERRTOK = @(YYINTERRTOK);
    private const YYUNEXPECTED = @(YYUNEXPECTED);
    private const YYDEFAULT = @(YYDEFAULT);

    private $buffer;
    private $token;
    private $toktype;

    private $yyastk;

/*
  #define yyclearin (yychar = -1)
  #define yyerrok (yyerrflag = 0)
  #define YYRECOVERING (yyerrflag != 0)
  #define YYERROR  goto yyerrlab
*/

    /** Debug mode flag **/
    private $yydebug = false;

    /** lexical element object **/
    private $yylval;

@if -t
    private $yyterminals = [
        @listvar terminals
        , "???"
    ];

    private $yyproduction = [
        @production-strings;
    ];
@endif

    private $yytranslate = [
        @listvar yytranslate
    ];

    private $yyaction = [
        @listvar yyaction
    ];

    private $yycheck = [
        @listvar yycheck
    ];

    private $yybase = [
        @listvar yybase
    ];

    private $yydefault = [
        @listvar yydefault
    ];

    private $yygoto = [
        @listvar yygoto
    ];

    private $yygcheck = [
        @listvar yygcheck
    ];

    private $yygbase = [
        @listvar yygbase
    ];

    private $yygdefault = [
        @listvar yygdefault
    ];

    private $yylhs = [
        @listvar yylhs
    ];

    private $yylen = [
        @listvar yylen
    ];

    public function __construct()
    {
@if -t
        $this->yydebug = true;
@endif
    }

    public function parse(string $input)
    {
        $this->__lex_buffer = $this->buffer = $input;

        try {
            $this->yyparse();
        } catch (InvalidArgumentException $e) {
            $e->setMessage(sprintf("Expression \"%s\" is invalid.\n".$e->getMessage(), $this->buffer));
            throw $e;
        }

        return reset($this->yyastk);
    }

    private function yyflush()
    {
        return;
    }

    private function yytokname(int $n): string
    {
        switch ($n) {
            @switch-for-token-name;
            default:
                return "???";
        }
    }

@if -t
    private function yyprintln(string $msg): void
    {
        echo "$msg\n";
    }

    /* Traditional Debug Mode */
    private function YYTRACE_NEWSTATE($state, $sym): void
    {
        $this->yyprintln("% State " . $state . ", Lookahead "
            . ($sym < 0 ? "--none--" : $this->yyterminals[$sym]));
    }

    private function YYTRACE_READ($sym): void
    {
        $this->yyprintln("% Reading " . $this->yyterminals[$sym]);
    }

    private function YYTRACE_SHIFT($sym): void
    {
        $this->yyprintln("% Shift " . $this->yyterminals[$sym]);
    }

    private function YYTRACE_ACCEPT(): void
    {
        $this->yyprintln("% Accepted.");
    }

    private function YYTRACE_REDUCE($n): void
    {
        $this->yyprintln("% Reduce by (" . $n . ") " . $this->yyproduction[$n]);
    }

    private function YYTRACE_POP($state): void
    {
        $this->yyprintln("% Recovering, uncovers state " . $state);
    }

    private function YYTRACE_DISCARD($sym): void
    {
        $this->yyprintln("% Discard " . $this->yyterminals[$sym]);
    }
@endif

    private function yyerror(): void
    {
        $position = $this->__lex_buffer ? strpos($this->buffer, $this->__lex_buffer) : strlen($this->buffer);

        throw new SyntaxError($this->buffer, $position);
    }

    private function yyparse()
    {
        $this->yyastk = [];
        $yyastk = &$this->yyastk;
        $yysstk = [];

        $yyn = $yyl = 0;
        $yystate = 0;
        $yychar = -1;

        $yysp = 0;
        $yysstk[$yysp] = 0;
        $yyerrflag = 0;
        while (true) {
@if -t
            $this->YYTRACE_NEWSTATE($yystate, $yychar);
@endif
            if ($this->yybase[$yystate] == 0) {
                $yyn = $this->yydefault[$yystate];
            } elseif ($yychar < 0) {
                if (($yychar = $this->yylex()) <= 0) {
                    $yychar = 0;
                }
                $yychar = $yychar < self::YYMAXLEX ? $this->yytranslate[$yychar] : self::YYBADCH;
@if -t
                $this->YYTRACE_READ($yychar);
@endif
            }

            if ((($yyn = $this->yybase[$yystate] + $yychar) >= 0
                    && $yyn < self::YYLAST && $this->yycheck[$yyn] == $yychar
                    || ($yystate < self::YY2TBLSTATE
                        && ($yyn = $this->yybase[$yystate + self::YYNLSTATES] + $yychar) >= 0
                        && $yyn < self::YYLAST && $this->yycheck[$yyn] == $yychar))
                && ($yyn = $this->yyaction[$yyn]) != self::YYDEFAULT) {
                /*
                 * >= YYNLSTATE: shift and reduce
                 * > 0: shift
                 * = 0: accept
                 * < 0: reduce
                 * = -YYUNEXPECTED: error
                 */
                if ($yyn > 0) {
                    /* shift */
@if -t
                    $this->YYTRACE_SHIFT($yychar);
@endif
                    $yysp++;

                    $yysstk[$yysp] = $yystate = $yyn;
                    $yyastk[$yysp] = $this->yylval;
                    $yychar = -1;

                    if ($yyerrflag > 0) {
                        $yyerrflag--;
                    }

                    if ($yyn < self::YYNLSTATES) {
                        continue;
                    }

                    /* $yyn >= YYNLSTATES means shift-and-reduce */
                    $yyn -= self::YYNLSTATES;
                } else {
                    $yyn = -$yyn;
                }
            } else {
                $yyn = $this->yydefault[$yystate];
            }

            while (true) {
                /* reduce/error */
                if ($yyn == 0) {
                    /* accept */
@if -t
                    $this->YYTRACE_ACCEPT();
@endif
                    $this->yyflush();
                    return 0;
                } elseif ($yyn != self::YYUNEXPECTED) {
                    /* reduce */
                    $yyl = $this->yylen[$yyn];
                    $n = $yysp-$yyl+1;
                    $yyval = isset($yyastk[$n]) ? $yyastk[$n] : null;
@if -t
                    $this->YYTRACE_REDUCE($yyn);
@endif
                    /* Following line will be replaced by reduce actions */
                    switch($yyn) {
@reduce
                        case %n:
                            {%b} break;
@endreduce
                    }

                    /* Goto - shift nonterminal */
                    $yysp -= $yyl;
                    $yyn = $this->yylhs[$yyn];
                    if (($yyp = $this->yygbase[$yyn] + $yysstk[$yysp]) >= 0 && $yyp < self::YYGLAST
                        && $this->yygcheck[$yyp] == $yyn) {
                        $yystate = $this->yygoto[$yyp];
                    } else {
                        $yystate = $this->yygdefault[$yyn];
                    }

                    $yysp++;

                    $yysstk[$yysp] = $yystate;
                    $yyastk[$yysp] = $yyval;
                } else {
                    /* error */
                    switch ($yyerrflag) {
                        case 0:
                            $this->yyerror();
                        case 1:
                        case 2:
                            $yyerrflag = 3;
                            /* Pop until error-expecting state uncovered */

                            while (!(($yyn = $this->yybase[$yystate] + self::YYINTERRTOK) >= 0
                                && $yyn < self::YYLAST && $this->yycheck[$yyn] == self::YYINTERRTOK
                                || ($yystate < self::YY2TBLSTATE
                                    && ($yyn = $this->yybase[$yystate + self::YYNLSTATES] + self::YYINTERRTOK) >= 0
                                    && $yyn < self::YYLAST && $this->yycheck[$yyn] == self::YYINTERRTOK))) {
                                if ($yysp <= 0) {
                                    $this->yyflush();

                                    return 1;
                                }

                                $yystate = $yysstk[--$yysp];
@if -t
                                $this->YYTRACE_POP($yystate);
@endif
                            }

                            $yyn = $this->yyaction[$yyn];
@if -t
                            $this->YYTRACE_SHIFT(YYINTERRTOK);
@endif
                            $yysstk[++$yysp] = $yystate = $yyn;
                            break;

                        case 3:
@if -t
                            $this->YYTRACE_DISCARD($yychar);
@endif
                            if ($yychar == 0) {
                                $this->yyflush();

                                return 1;
                            }

                            $yychar = -1;
                            break;
                    }
                }

                if ($yystate < self::YYNLSTATES) {
                    break;
                }

                /* >= YYNLSTATES means shift-and-reduce */
                $yyn = $yystate - self::YYNLSTATES;
            }
        }
    }

@tailcode;
}
