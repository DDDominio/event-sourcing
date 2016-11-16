<?php

namespace EventSourcing\Common\Model;

class JsonAdapter
{
    public function removeField($json, $string)
    {
        $decodedJson = json_decode($json);
        unset($decodedJson->$string);
        return json_encode($decodedJson);
    }
}