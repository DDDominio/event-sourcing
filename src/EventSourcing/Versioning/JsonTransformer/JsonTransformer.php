<?php

namespace DDDominio\EventSourcing\Versioning\JsonTransformer;

class JsonTransformer
{
    /**
     * @var TokenExtractor
     */
    private $tokenExtractor;

    /**
     * @param TokenExtractor $tokenExtractor
     */
    public function __construct(TokenExtractor $tokenExtractor)
    {
        $this->tokenExtractor = $tokenExtractor;
    }

    /**
     * @param string $jsonString
     * @param $pathExpression
     * @return string
     */
    public function removeKey($jsonString, $pathExpression)
    {
        $decodedJson = json_decode($jsonString);
        $tokens = $this->tokenExtractor->extract($pathExpression);
        $currentNode = &$decodedJson;
        while (count($tokens) > 0) {
            $currentToken = array_shift($tokens);
            if (count($tokens) === 0) {
                if ($currentToken instanceof ArrayAccessToken) {
                    unset($currentNode[$currentToken->index()]);
                    $currentNode = array_values($currentNode);
                } else if ($currentToken instanceof ObjectAccessToken) {
                    unset($currentNode->{$currentToken->fieldName()});
                }
            } else {
                if ($currentToken instanceof ArrayAccessToken) {
                    $currentNode = &$currentNode[$currentToken->index()];
                }  else if ($currentToken instanceof ObjectAccessToken) {
                    $currentNode = &$currentNode->{$currentToken->fieldName()};
                }
            }
        }
        return json_encode($decodedJson);
    }

    public function addKey($jsonString, $pathExpression, $value)
    {
        $decodedJson = json_decode($jsonString);
        $tokens = $this->tokenExtractor->extract($pathExpression);
        $currentNode = &$decodedJson;
        while (count($tokens) > 0) {
            $currentToken = array_shift($tokens);
            if (count($tokens) === 0) {
                if ($currentToken instanceof ArrayAccessToken) {
                    $currentNode[$currentToken->index()] = $value;
                } else if ($currentToken instanceof ObjectAccessToken) {
                    $currentNode->{$currentToken->fieldName()} = $value;
                }
            } else {
                if ($currentToken instanceof ArrayAccessToken) {
                    $currentNode = &$currentNode[$currentToken->index()];
                }  else if ($currentToken instanceof ObjectAccessToken) {
                    $currentNode = &$currentNode->{$currentToken->fieldName()};
                }
            }
        }
        return json_encode($decodedJson);
    }

    public function renameKey($jsonString, $pathExpression, $newName)
    {
        $decodedJson = json_decode($jsonString);
        $tokens = $this->tokenExtractor->extract($pathExpression);
        $currentNode = &$decodedJson;
        while (count($tokens) > 0) {
            $currentToken = array_shift($tokens);
            if (count($tokens) === 0) {
                if ($currentToken instanceof ObjectAccessToken) {
                    $currentNode->{$newName} = $currentNode->{$currentToken->fieldName()};
                    unset($currentNode->{$currentToken->fieldName()});
                }
            } else {
                if ($currentToken instanceof ArrayAccessToken) {
                    $currentNode = &$currentNode[$currentToken->index()];
                }  else if ($currentToken instanceof ObjectAccessToken) {
                    $currentNode = &$currentNode->{$currentToken->fieldName()};
                }
            }
        }
        return json_encode($decodedJson);
    }
}