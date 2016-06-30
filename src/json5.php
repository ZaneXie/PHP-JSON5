<?php
// JSON5 for PHP
// [URL] https://github.com/zanexie/PHP-JSON5

// This file is based directly off of json5's json5.js:
// https://github.com/json5/json5/blob/v0.5.0/lib/json5.js

function json5_encode($obj)
{
    // TBA
    return json_encode($obj);
}

class SyntaxError extends ErrorException
{
    public $message;
    public $at;
    public $lineNumber;
    public $columnNumber;
}

function json5_decode($json5)
{
    $escapee = array(
        "'" => "'",
        '"' => '"',
        '\\' => '\\',
        '/' => '/',
        "\n" => '',       // Replace escaped newlines in strings w/ empty string
        'b' => chr(8),
        'f' => "\f",
        'n' => "\n",
        'r' => "\r",
        't' => "\t"
    );
    $ws = array(
        ' ',
        "\t",
        "\r",
        "\n",
        "\v",
        "\f",
        '\xA0',
        '\uFEFF'
    );
    $text = $json5;
    $textLength = strlen($json5);
    $at = 0;
    $lineNumber = 1;
    $columnNumber = 1;
    $ch = ' ';
    $renderChar = function ($chr) {
        return $chr === '' ? 'EOF' : "'" . $chr . "'";
    };
    $error = function ($m) use (&$lineNumber, &$columnNumber, &$at, $text) {

// Call error when something is wrong.
        $error = new SyntaxError();
        // beginning of message suffix to agree with that provided by Gecko - see https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/JSON/parse
        $error->message = $m . " at line " . $lineNumber . " column " . $columnNumber . " of the JSON5 data. Still to read: " . json_encode(substr($text, $at - 1, $at + 19));
        $error->at = $at;
        // These two property names have been chosen to agree with the ones in Gecko, the only popular
        // environment which seems to supply this info on JSON.parse
        $error->lineNumber = $lineNumber;
        $error->columnNumber = $columnNumber;
        throw $error;
    };

    $peek = function () use ($text, &$at) {

// Get the next character without consuming it or
// assigning it to the ch varaible.

        return $text[$at];
    };

    $next = function ($c = null) use ($error, &$ch, &$text, &$at, &$columnNumber, &$lineNumber, $peek, $renderChar, $textLength) {

// If a c parameter is provided, verify that it matches the current character.

        if ($c && $c !== $ch) {
            $error("Expected " . $renderChar($c) . " instead of " . $renderChar($ch));
        }

// Get the next character. When there are no more characters,
// return the empty string.

        if ($at >= $textLength) {
            $ch = null;
            return $ch;
        }
        $ch = $text[$at];
        $at++;
        $columnNumber++;
        if ($ch === "\n" || $ch === "\r" && $peek() !== "\n") {
            $lineNumber++;
            $columnNumber = 0;
        }
        return $ch;
    };

    $identifier = function () use (&$ch, $error, $next) {

// Parse an identifier. Normally, reserved words are disallowed here, but we
// only use this for unquoted object keys, where reserved words are allowed,
// so we don't check for those here. References:
// - http://es5.github.com/#x7.6
// - https://developer.mozilla.org/en/Core_JavaScript_1.5_Guide/Core_Language_Features#Variables
// - http://docstore.mik.ua/orelly/webprog/jscript/ch02_07.htm
// TODO Identifiers can have Unicode "letters" in them; add support for those.
        $key = $ch;

        // Identifiers must start with a letter, _ or $.
        if (($ch !== '_' && $ch !== '$') &&
            ($ch < 'a' || $ch > 'z') &&
            ($ch < 'A' || $ch > 'Z')
        ) {
            $error("Bad identifier as unquoted key");
        }

        // Subsequent characters can contain digits.
        while ($next() && (
                $ch === '_' || $ch === '$' ||
                ($ch >= 'a' && $ch <= 'z') ||
                ($ch >= 'A' && $ch <= 'Z') ||
                ($ch >= '0' && $ch <= '9'))) {
            $key .= $ch;
        }

        return $key;
    };

    // todo: what is NaN equivalent in php?
    $NaN = new stdClass();

    $isNaN = function ($obj) use ($NaN) {
        return $obj === $NaN;
    };

    $word = function () use (&$ch, $next, $error, $renderChar, &$NaN) {

// true, false, or null.

        switch ($ch) {
            case 't':
                $next('t');
                $next('r');
                $next('u');
                $next('e');
                return true;
            case 'f':
                $next('f');
                $next('a');
                $next('l');
                $next('s');
                $next('e');
                return false;
            case 'n':
                $next('n');
                $next('u');
                $next('l');
                $next('l');
                return null;
            case 'I':
                $next('I');
                $next('n');
                $next('f');
                $next('i');
                $next('n');
                $next('i');
                $next('t');
                $next('y');
                return INF;
            case 'N':
                $next('N');
                $next('a');
                $next('N');
                return $NaN;
        }
        $error("Unexpected " . $renderChar($ch));
    };
    $number = function () use (&$ch, $word, $error, $next, $isNaN, $NaN) {

// Parse a number value.

        $number = null;
        $sign = '';
        $string = '';
        $base = 10;

        if ($ch === '-' || $ch === '+') {
            $sign = $ch;
            $next($ch);
        }

        // support for Infinity (could tweak to allow other words):
        if ($ch === 'I') {
            $number = $word();
            // if (typeof number !== 'number' || isNaN(number)) {
            if (!($number === $NaN || $number === INF)) {
                $error('Unexpected word for number');
            }
            return ($sign === '-') ? -$number : $number;
        }

        // support for NaN
        if ($ch === 'N') {
            $number = $word();
            if (!$isNaN($number)) {
                $error('expected word to be NaN');
            }
            // ignore sign as -NaN also is NaN
            return $number;
        }

        if ($ch === '0') {
            $string .= $ch;
            $next();
            if ($ch === 'x' || $ch === 'X') {
                $string .= $ch;
                $next();
                $base = 16;
            } else if ($ch >= '0' && $ch <= '9') {
                $error('Octal literal');
            }
        }

        switch ($base) {
            case 10:
                while ($ch >= '0' && $ch <= '9') {
                    $string .= $ch;
                    $next();
                }
                if ($ch === '.') {
                    $string .= '.';
                    while ($next() && $ch >= '0' && $ch <= '9') {
                        $string .= $ch;
                    }
                }
                if ($ch === 'e' || $ch === 'E') {
                    $string .= $ch;
                    $next();
                    if ($ch === '-' || $ch === '+') {
                        $string .= $ch;
                        $next();
                    }
                    while ($ch >= '0' && $ch <= '9') {
                        $string .= $ch;
                        $next();
                    }
                }
                break;
            case 16:
                while ($ch >= '0' && $ch <= '9' || $ch >= 'A' && $ch <= 'F' || $ch >= 'a' && $ch <= 'f') {
                    $string .= $ch;
                    $next();
                }
                break;
        }

        if ($sign === '-') {
            $number = -$string;
        } else {
            $number = +$string;
        }

        if (is_infinite($number)) {
            $error("Bad number");
        } else {
            return $number;
        }
    };

    $string = function () use (&$ch, $next, $peek, $escapee, $error) {

// Parse a string value.

        $hex = null;
        $i = null;
        $string = '';
        $delim = null;      // double quote or single quote
        $uffff = null;

// When parsing for string values, we must look for ' or " and \ characters.

        if ($ch === '"' || $ch === "'") {
            $delim = $ch;
            while ($next() != null) {
                if ($ch === $delim) {
                    $next();
                    return $string;
                } else if ($ch === '\\') {
                    $next();
                    if ($ch === 'u') {
                        $ts = '\\u';
                        $uffff = 0;
                        for ($i = 0; $i < 4; $i += 1) {
                            $hex = intval($next(), 16);
                            $ts .= $ch;
                            if (is_infinite($hex)) {
                                break;
                            }
                            $uffff = $uffff * 16 + $hex;
                        }
                        // string += String.fromCharCode(uffff);
                        if (extension_loaded('mbstring')) {
                            $string .= mb_convert_encoding('&#' . intval($uffff) . ';', 'UTF-8', 'HTML-ENTITIES');
                        } else {
                            $string .= $ts;
                            error_log('mbstring extension not found, decode string failed;');
                        }
                    } else if ($ch === "\r") {
                        if ($peek() === "\n") {
                            $next();
                        }
                    } else if (gettype($escapee[$ch]) === 'string') {
                        $string .= $escapee[$ch];
                    } else {
                        break;
                    }
                } else if ($ch === "\n") {
                    // unescaped newlines are invalid; see:
                    // https://github.com/aseemk/json5/issues/24
                    // TODO this feels special-cased; are there other
                    // invalid unescaped chars?
                    break;
                } else {
                    $string .= $ch;
                }
            }
        }
        $error("Bad string");
    };
    $inlineComment = function () use (&$ch, $error, $next) {

// Skip an inline comment, assuming this is one. The current character should
// be the second / character in the // pair that begins this inline comment.
// To finish the inline comment, we look for a newline or the end of the text.

        if ($ch !== '/') {
            $error("Not an inline comment");
        }

        do {
            $next();
            if ($ch === "\n" || $ch === "\r") {
                $next();
                return;
            }
        } while ($ch);
    };

    $blockComment = function () use (&$ch, $error, $next) {

// Skip a block comment, assuming this is one. The current character should be
// the * character in the /* pair that begins this block comment.
// To finish the block comment, we look for an ending */ pair of characters,
// but we also watch for the end of text before the comment is terminated.

        if ($ch !== '*') {
            $error("Not a block comment");
        }

        do {
            $next();
            while ($ch === '*') {
                $next('*');
                if ($ch === '/') {
                    $next('/');
                    return;
                }
            }
        } while ($ch);

        $error("Unterminated block comment");
    };
    $comment = function () use (&$ch, $error, $next, $inlineComment, $blockComment, $error) {

// Skip a comment, whether inline or block-level, assuming this is one.
// Comments always begin with a / character.

        if ($ch !== '/') {
            $error("Not a comment");
        }

        $next('/');

        if ($ch === '/') {
            $inlineComment();
        } else if ($ch === '*') {
            $blockComment();
        } else {
            $error("Unrecognized comment");
        }
    };

    $white = function () use (&$ch, &$ws, $comment, $next) {

// Skip whitespace and comments.
// Note that we're detecting comments by only a single / character.
// This works since regular expressions are not valid JSON(5), but this will
// break if there are other valid values that begin with a / character!

        while ($ch) {
            if ($ch === '/') {
                $comment();
            } else if (array_search($ch, $ws) !== false) {
                $next();
            } else {
                return;
            }
        }
    };

    $value = function () {
    };         // Place holder for the value function.
    $array = function () use (&$ch, $next, $white, $error, &$value) {

// Parse an array value.

        $array = array();

        if ($ch === '[') {
            $next('[');
            $white();
            while ($ch) {
                if ($ch === ']') {
                    $next(']');
                    return $array;   // Potentially empty array
                }
                // ES5 allows omitting elements in arrays, e.g. [,] and
                // [,null]. We don't allow this in JSON5.
                if ($ch === ',') {
                    $error("Missing array element");
                } else {
                    $array[] = $value();
                }
                $white();
                // If there's no comma after this value, this needs to
                // be the end of the array.
                if ($ch !== ',') {
                    $next(']');
                    return $array;
                }
                $next(',');
                $white();
            }
        }
        $error("Bad array");
    };

    $object = function () use (&$ch, $white, $next, $identifier, &$value, $string, $error) {

// Parse an object value.

        $key = null;
        $object = array();

        if ($ch === '{') {
            $next('{');
            $white();
            while ($ch) {
                if ($ch === '}') {
                    $next('}');
                    return $object;   // Potentially empty object
                }

                // Keys can be unquoted. If they are, they need to be
                // valid JS identifiers.
                if ($ch === '"' || $ch === "'") {
                    $key = $string();
                } else {
                    $key = $identifier();
                }

                $white();
                $next(':');
                $object[$key] = $value();
                $white();
                // If there's no comma after this pair, this needs to be
                // the end of the object.
                if ($ch !== ',') {
                    $next('}');
                    return $object;
                }
                $next(',');
                $white();
            }
        }
        $error("Bad object");
    };

    $value = function () use ($white, &$ch, $object, $array, $string, $number, $word) {

// Parse a JSON value. It could be an object, an array, a string, a number,
// or a word.

        $white();
        switch ($ch) {
            case '{':
                return $object();
            case '[':
                return $array();
            case '"':
            case "'":
                return $string();
            case '-':
            case '+':
            case '.':
                return $number();
            default:
                return $ch >= '0' && $ch <= '9' ? $number() : $word();
        }
    };

    $result = $value();
    $white();
    if ($ch) {
        $error("Syntax error");
    }

    return $result;

}

