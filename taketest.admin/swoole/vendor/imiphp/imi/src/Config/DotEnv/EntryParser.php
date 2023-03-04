<?php

declare(strict_types=1);

namespace Imi\Config\DotEnv;

use Dotenv\Parser\Entry;
use Dotenv\Parser\Lexer;
use Dotenv\Parser\Value;
use Dotenv\Util\Regex;
use Dotenv\Util\Str;
use GrahamCampbell\ResultType\Error;
use GrahamCampbell\ResultType\Result;
use GrahamCampbell\ResultType\Success;

final class EntryParser
{
    private const INITIAL_STATE = 0;
    private const UNQUOTED_STATE = 1;
    private const SINGLE_QUOTED_STATE = 2;
    private const DOUBLE_QUOTED_STATE = 3;
    private const ESCAPE_SEQUENCE_STATE = 4;
    private const WHITESPACE_STATE = 5;
    private const COMMENT_STATE = 6;
    private const REJECT_STATES = [self::SINGLE_QUOTED_STATE, self::DOUBLE_QUOTED_STATE, self::ESCAPE_SEQUENCE_STATE];

    /**
     * This class is a singleton.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Parse a raw entry into a proper entry.
     *
     * That is, turn a raw environment variable entry into a name and possibly
     * a value. We wrap the answer in a result type.
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry,string>
     */
    public static function parse(string $entry)
    {
        return self::splitStringIntoParts($entry)->flatMap(static function (array $parts) {
            [$name, $value] = $parts;

            return self::parseName($name)->flatMap(static function (string $name) use ($value) {
                /** @var Result<Value|null,string> */
                $parsedValue = null === $value ? Success::create(null) : self::parseValue($value);

                return $parsedValue->map(static fn (?Value $value) => new Entry($name, $value));
            });
        });
    }

    /**
     * Split the compound string into parts.
     *
     * @return \GrahamCampbell\ResultType\Result<array{string,string|null},string>
     */
    private static function splitStringIntoParts(string $line)
    {
        /** @var array{string,string|null} */
        $result = Str::pos($line, '=')->map(static fn () => array_map('trim', explode('=', $line, 2)))->getOrElse([$line, null]);

        if ('' === $result[0])
        {
            return Error::create(self::getErrorMessage('an unexpected equals', $line));
        }

        /** @var \GrahamCampbell\ResultType\Result<array{string,string|null},string> */
        return Success::create($result);
    }

    /**
     * Parse the given variable name.
     *
     * That is, strip the optional quotes and leading "export" from the
     * variable name. We wrap the answer in a result type.
     *
     * @return \GrahamCampbell\ResultType\Result<string,string>
     */
    private static function parseName(string $name)
    {
        if (Str::len($name) > 8 && 'export' === Str::substr($name, 0, 6) && ctype_space(Str::substr($name, 6, 1)))
        {
            $name = ltrim(Str::substr($name, 6));
        }

        if (self::isQuotedName($name))
        {
            $name = Str::substr($name, 1, -1);
        }

        if (!self::isValidName($name))
        {
            return Error::create(self::getErrorMessage('an invalid name', $name));
        }

        return Success::create($name);
    }

    /**
     * Is the given variable name quoted?
     *
     * @return bool
     */
    private static function isQuotedName(string $name)
    {
        if (Str::len($name) < 3)
        {
            return false;
        }

        $first = Str::substr($name, 0, 1);
        $last = Str::substr($name, -1, 1);

        return ('"' === $first && '"' === $last) || ('\'' === $first && '\'' === $last);
    }

    /**
     * Is the given variable name valid?
     *
     * @return bool
     */
    private static function isValidName(string $name)
    {
        return Regex::matches('~\A[a-zA-Z0-9_.@\-]+\z~', $name)->success()->getOrElse(false);
    }

    /**
     * Parse the given variable value.
     *
     * This has the effect of stripping quotes and comments, dealing with
     * special characters, and locating nested variables, but not resolving
     * them. Formally, we run a finite state automaton with an output tape: a
     * transducer. We wrap the answer in a result type.
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Value,string>
     */
    private static function parseValue(string $value)
    {
        if ('' === trim($value))
        {
            return Success::create(Value::blank());
        }

        return array_reduce(iterator_to_array(Lexer::lex($value)), static fn (Result $data, string $token) => $data->flatMap(static fn (array $data) => self::processToken($data[1], $token)->map(static fn (array $val) => [$data[0]->append($val[0], $val[1]), $val[2]])), Success::create([Value::blank(), self::INITIAL_STATE]))->flatMap(static function (array $result) {
            if (\in_array($result[1], self::REJECT_STATES, true))
            {
                return Error::create('a missing closing quote');
            }

            return Success::create($result[0]);
        })->mapError(static fn (string $err) => self::getErrorMessage($err, $value));
    }

    /**
     * Process the given token.
     *
     * @return \GrahamCampbell\ResultType\Result<array{string,bool,int},string>
     */
    private static function processToken(int $state, string $token)
    {
        switch ($state) {
            case self::INITIAL_STATE:
                if ('\'' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::SINGLE_QUOTED_STATE]);
                }
                elseif ('"' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::DOUBLE_QUOTED_STATE]);
                }
                elseif ('#' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::COMMENT_STATE]);
                }
                elseif ('$' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, true, self::UNQUOTED_STATE]);
                }
                else
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, false, self::UNQUOTED_STATE]);
                }
                // no break
            case self::UNQUOTED_STATE:
                if ('#' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::COMMENT_STATE]);
                }
                elseif (ctype_space($token))
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                }
                elseif ('$' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, true, self::UNQUOTED_STATE]);
                }
                else
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, false, self::UNQUOTED_STATE]);
                }
                // no break
            case self::SINGLE_QUOTED_STATE:
                if ('\'' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                }
                else
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, false, self::SINGLE_QUOTED_STATE]);
                }
                // no break
            case self::DOUBLE_QUOTED_STATE:
                if ('"' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                }
                elseif ('\\' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::ESCAPE_SEQUENCE_STATE]);
                }
                elseif ('$' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, true, self::DOUBLE_QUOTED_STATE]);
                }
                else
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, false, self::DOUBLE_QUOTED_STATE]);
                }
                // no break
            case self::ESCAPE_SEQUENCE_STATE:
                if ('"' === $token || '\\' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, false, self::DOUBLE_QUOTED_STATE]);
                }
                elseif ('$' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create([$token, false, self::DOUBLE_QUOTED_STATE]);
                }
                else
                {
                    $first = Str::substr($token, 0, 1);
                    if (\in_array($first, ['f', 'n', 'r', 't', 'v'], true))
                    {
                        /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                        return Success::create([stripcslashes('\\' . $first) . Str::substr($token, 1), false, self::DOUBLE_QUOTED_STATE]);
                    }
                    else
                    {
                        /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                        return Error::create('an unexpected escape sequence');
                    }
                }
                // no break
            case self::WHITESPACE_STATE:
                if ('#' === $token)
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::COMMENT_STATE]);
                }
                elseif (!ctype_space($token))
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Error::create('unexpected whitespace');
                }
                else
                {
                    /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                }
                // no break
            case self::COMMENT_STATE:
                /* @var \GrahamCampbell\ResultType\Result<array{string,bool,int},string> */
                return Success::create(['', false, self::COMMENT_STATE]);
            default:
                throw new \Error('Parser entered invalid state.');
        }
    }

    /**
     * Generate a friendly error message.
     *
     * @return string
     */
    private static function getErrorMessage(string $cause, string $subject)
    {
        return sprintf(
            'Encountered %s at [%s].',
            $cause,
            strtok($subject, "\n")
        );
    }
}
