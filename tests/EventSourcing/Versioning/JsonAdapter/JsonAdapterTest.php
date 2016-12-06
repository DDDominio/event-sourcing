<?php

namespace Tests\EventSourcing\Versioning\JsonTransformer;

use EventSourcing\Versioning\JsonTransformer\JsonTransformer;
use EventSourcing\Versioning\JsonTransformer\TokenExtractor;

class JsonTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldRemoveAKey()
    {
        $json = '{"a": 10, "b": "text"}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, 'a');

        $this->assertEquals('{"b":"text"}', $modifiedJson);
    }

    /**
     * @test
     */
    public function itShouldRemoveAnotherKey()
    {
        $json = '{"a": 10, "b": "text"}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, 'b');

        $this->assertEquals('{"a":10}', $modifiedJson);
    }

    /**
     * @test
     */
    public function whenRemovingAKeyIfItDoesNotExistReturnCurrentJson()
    {
        $json = '{"a": 10, "b": "text"}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, 'c');

        $this->assertEquals('{"a":10,"b":"text"}', $modifiedJson);
    }

    /**
     * @test
     */
    public function removeAKeyNotInRoot()
    {
        $json = '{"a": 10, "b": {"c": 4, "d": "text"}}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, 'b.c');

        $this->assertEquals('{"a":10,"b":{"d":"text"}}', $modifiedJson);
    }

    /**
     * @test
     */
    public function removeAnArrayKey()
    {
        $json = '[{"a": 10}, 5, "text"]';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, '[0]');

        $this->assertEquals('[5,"text"]', $modifiedJson);
    }

    /**
     * @test
     */
    public function removeAnotherArrayKey()
    {
        $json = '[{"a": 10}, 5, "text"]';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, '[1]');

        $this->assertEquals('[{"a":10},"text"]', $modifiedJson);
    }

    /**
     * @test
     */
    public function whenRemovingAnArrayKeyThatDoesNotExistReturnCurrentJson()
    {
        $json = '[{"a": 10}, 5, "text"]';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, '[5]');

        $this->assertEquals('[{"a":10},5,"text"]', $modifiedJson);
    }

    /**
     * @test
     */
    public function removeAnArrayKeyNotInRoot()
    {
        $json = '{"a": 10, "b": {"c": [1, 2, 3], "d": "text"}}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, 'b.c[2]');

        $this->assertEquals('{"a":10,"b":{"c":[1,2],"d":"text"}}', $modifiedJson);
    }

    /**
     * @test
     */
    public function removeAnArrayKeyInAComplexJson()
    {
        $json = '{"a": 10, "b": {"c": [1, 2, {"object": [[0,{"key": "value"}], 2]}], "d": "text"}}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->removeKey($json, 'b.c[2].object[0][1].key');

        $this->assertEquals('{"a":10,"b":{"c":[1,2,{"object":[[0,{}],2]}],"d":"text"}}', $modifiedJson);
    }

    /**
     * @test
     */
    public function itShouldAddAKey()
    {
        $json = '{"a": 10, "b": "text"}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->addKey($json, 'c', 'test');

        $this->assertEquals('{"a":10,"b":"text","c":"test"}', $modifiedJson);
    }

    /**
     * @test
     */
    public function itShouldAddAnotherKey()
    {
        $json = '{"a": 10, "b": "text"}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->addKey($json, 'another', 'test');

        $this->assertEquals('{"a":10,"b":"text","another":"test"}', $modifiedJson);
    }

    /**
     * @test
     */
    public function itShouldAddAKeyNotInRoot()
    {
        $json = '{"a": 10, "b": {"c": 4, "d": "text"}}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->addKey($json, 'b.new', 'test');

        $this->assertEquals('{"a":10,"b":{"c":4,"d":"text","new":"test"}}', $modifiedJson);
    }

    /**
     * @test
     */
    public function itShouldAddAKeyInAComplexJson()
    {
        $json = '{"a": 10, "b": {"c": [1, 2, {"object": [[0,{"key": "value"}], 2]}], "d": "text"}}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->addKey($json, 'b.c[2].object[0][1].new', 'test');

        $this->assertEquals('{"a":10,"b":{"c":[1,2,{"object":[[0,{"key":"value","new":"test"}],2]}],"d":"text"}}', $modifiedJson);
    }

    /**
     * @test
     */
    public function itShouldRenameAKey()
    {
        $json = '{"a": 10, "b": "text"}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->renameKey($json, 'a', 'c');

        $this->assertEquals('{"b":"text","c":10}', $modifiedJson);
    }

    /**
     * @test
     */
    public function itShouldRenameAKeyNotInRoot()
    {
        $json = '{"a": 10, "b": {"c": 4, "d": "text"}}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->renameKey($json, 'b.c', 'new');

        $this->assertEquals('{"a":10,"b":{"d":"text","new":4}}', $modifiedJson);
    }

    /**
     * @test
     */
    public function renameAKeyInAComplexJson()
    {
        $json = '{"a": 10, "b": {"c": [1, 2, {"object": [[0,{"key": "value"}], 2]}], "d": "text"}}';
        $jsonTransformer = $this->buildJsonTransformer();

        $modifiedJson = $jsonTransformer->renameKey($json, 'b.c[2].object[0][1].key', 'new');

        $this->assertEquals('{"a":10,"b":{"c":[1,2,{"object":[[0,{"new":"value"}],2]}],"d":"text"}}', $modifiedJson);
    }

    /**
     * @return JsonTransformer
     */
    private function buildJsonTransformer()
    {
        return new JsonTransformer(new TokenExtractor());
    }
}
