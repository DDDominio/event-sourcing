<?php

namespace EventSourcing\Versioning\JsonTransformer;

class TokenExtractor
{
    const TOKEN_DELIMITER_REGEX = '/[\.\[]/';

    public function extract($pathExpression)
    {
        $tokensRepresentation = preg_split(self::TOKEN_DELIMITER_REGEX, $pathExpression, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = [];
        foreach ($tokensRepresentation as $tokenRepresentation) {
            if (preg_match('/(\d+)\]/', $tokenRepresentation, $matches)) {
                $arrayKey = intval($matches[1]);
                $tokens[] = new ArrayAccessToken($arrayKey);
            } else {
                $tokens[] = new ObjectAccessToken($tokenRepresentation);
            }
        }
        return $tokens;
    }
}
