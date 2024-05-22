<?php

declare(strict_types=1);

namespace Codefog\HasteBundle;

use Contao\ArrayUtil;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\StringUtil;

class StringParser
{
    /**
     * Text filter options.
     */
    public const NO_TAGS = 1;

    public const NO_BREAKS = 2;

    public const NO_EMAILS = 4;

    public const NO_INSERTTAGS = 8;

    public const NO_ENTITIES = 16;

    public function __construct(
        private SimpleTokenParser $simpleTokenParser,
        private InsertTagParser $insertTagParser,
    ) {
    }

    /**
     * Recursively replace simple tokens and insert tags.
     */
    public function recursiveReplaceTokensAndTags(string $text, array $tokens, int $textFlags = 0): string
    {
        if ($textFlags > 0) {
            $tokens = $this->convertToText($tokens, $textFlags);
        }

        // Must decode, tokens could be encoded
        $text = StringUtil::decodeEntities($text);

        // first parse the tokens as they might have if-else clauses
        $buffer = $this->simpleTokenParser->parse($text, $tokens);

        // then replace the insert tags
        $buffer = $this->insertTagParser->replaceInline($buffer);

        // check if the insert tags have returned a simple token
        if (str_contains($buffer, '##') && $buffer !== $text) {
            $buffer = $this->recursiveReplaceTokensAndTags($buffer, $tokens, $textFlags);
        }

        $buffer = StringUtil::restoreBasicEntities($buffer);

        if ($textFlags > 0) {
            $buffer = $this->convertToText($buffer, $textFlags);
        }

        return $buffer;
    }

    /**
     * Convert the given array or string to plain text using given options.
     */
    public function convertToText(mixed $value, int $options): mixed
    {
        if (\is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->convertToText($v, $options);
            }

            return $value;
        }

        if (!\is_string($value)) {
            return $value;
        }

        if ($options & static::NO_ENTITIES) {
            $value = StringUtil::restoreBasicEntities($value);
            $value = html_entity_decode($value);

            // Convert non-breaking to regular white space
            $value = str_replace("\xC2\xA0", ' ', $value);

            // Remove invisible control characters and unused code points
            $value = (string) preg_replace('/[\pC]/u', '', $value);
        }

        // Replace friendly email before stripping tags
        if (!($options & static::NO_EMAILS)) {
            $emails = [];
            preg_match_all('{<.+@.+\.[A-Za-z]+>}', $value, $emails);

            if (!empty($emails[0])) {
                foreach ($emails[0] as $k => $v) {
                    $value = str_replace($v, '%email'.$k.'%', $value);
                }
            }
        }

        // Remove HTML tags but keep line breaks for <br> and <p>
        if ($options & static::NO_TAGS) {
            $value = strip_tags((string) preg_replace('{(?!^)<(br|p|/p).*?/?>\n?(?!$)}is', "\n", $value));
        }

        if ($options & static::NO_INSERTTAGS) {
            $value = StringUtil::stripInsertTags($value);
        }

        // Remove line breaks (e.g. for subject)
        if ($options & static::NO_BREAKS) {
            $value = str_replace(["\r", "\n"], '', $value);
        }

        // Restore friendly email after stripping tags
        if (!($options & static::NO_EMAILS) && !empty($emails[0])) {
            foreach ($emails[0] as $k => $v) {
                $value = str_replace('%email'.$k.'%', $v, $value);
            }
        }

        return $value;
    }

    /**
     * Flatten input data, Simple Tokens can't handle arrays.
     */
    public function flatten(mixed $value, string $key, array &$data, string $pattern = ', '): void
    {
        if (\is_object($value)) {
            return;
        }

        if (!\is_array($value)) {
            $data[$key] = $value;

            return;
        }

        $isAssoc = ArrayUtil::isAssoc($value);
        $values = [];

        foreach ($value as $k => $v) {
            if ($isAssoc || \is_array($v)) {
                $this->flatten($v, $key.'_'.$k, $data);
            } else {
                $data[$key.'_'.$v] = '1';
                $values[] = $v;
            }
        }

        $data[$key] = implode($pattern, $values);
    }
}
