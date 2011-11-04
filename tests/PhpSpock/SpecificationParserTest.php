<?php
/**
 * This file is part of PhpSpock.
 *
 * PhpSpock is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PhpSpock is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PhpSpock.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright 2011 Aleksandr Rudakov <ribozz@gmail.com>
 *
 **/
/**
 * Date: 11/3/11
 * Time: 10:38 AM
 * @author Alex Rudakov <alexandr.rudakov@modera.net>
 */

namespace PhpSpock;
 
class SpecificationParserTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \PhpSpock\SpecificationParser
     */
    protected $parser;

    protected function setUp()
    {
        parent::setUp();

        $this->parser = new SpecificationParser();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function parseFullSpecification()
    {
        // add some noise here to add complexity to parsing
        function(){}; $spec = function() { /** this line should not be saved! */
            /**
             * @var $sym
             * @var $result
             */

            setup:
            $foo = '123';

            when:
            $foo .= $sym;

            then:
            $foo == $result;

            where:
            $sym | $result;
            '!' | '123!';
            '4' | '1234';
         /** this line also will be ignored */ }; $a = function(){}; // and here some noise

        $result = $this->parser->parse($spec);

        $this->assertType('PhpSpock\Specification', $result);

        $this->assertEquals('/**
             * @var $sym
             * @var $result
             */

            setup:
            $foo = \'123\';

            when:
            $foo .= $sym;

            then:
            $foo == $result;

            where:
            $sym | $result;
            \'!\' | \'123!\';
            \'4\' | \'1234\';', $result->getRawBody());

        $blocks = $result->getRawBlocks();

        $this->assertEquals(4, count($blocks));
        $this->assertEquals('$foo = \'123\';', $blocks['setup']);
        $this->assertEquals('$foo .= $sym;', $blocks['when']);
        $this->assertEquals('$foo == $result;', $blocks['then']);
        $this->assertEquals('$sym | $result;
            \'!\' | \'123!\';
            \'4\' | \'1234\';', $blocks['where']);

        $this->assertType('PhpSpock\Specification\SimpleBlock', $result->getSetupBlock());
        $this->assertType('PhpSpock\Specification\SimpleBlock', $result->getWhenBlock());
        $this->assertType('PhpSpock\Specification\ThenBlock', $result->getThenBlock());
        $this->assertType('PhpSpock\Specification\WhereBlock', $result->getWhereBlock());
    }

    /**
     * @test
     */
    public function parseSpecWithoutSetup()
    {
        $spec = function() {
            /**
             * @var $sym
             * @var $result
             */

            $foo = '123';

            when:
            $foo .= $sym;

            then:
            true;
         };

        $result = $this->parser->parse($spec);

        $this->assertType('PhpSpock\Specification', $result);

        $this->assertEquals('/**
             * @var $sym
             * @var $result
             */

            $foo = \'123\';

            when:
            $foo .= $sym;

            then:
            true;', $result->getRawBody());

        $blocks = $result->getRawBlocks();
        $this->assertEquals(3, count($blocks));

        $this->assertEquals('$foo = \'123\';', $blocks['setup']);
    }

    /**
     * @test
     * @expectedException PhpSpock\ParseException
     */
    public function parseSpecWithCodeOutsideOfSetup()
    {
        $spec = function() {
            /**
             * @var $sym
             * @var $result
             */

            $foo = '124';

            setup:
            $foo = '123';

            when:
            $foo .= $sym;
         };

        $result = $this->parser->parse($spec);

        $this->assertType('PhpSpock\Specification', $result);

        $this->assertEquals('/**
             * @var $sym
             * @var $result
             */

            $foo = \'123\';

            when:
            $foo .= $sym;', $result->getRawBody());

        $blocks = $result->getRawBlocks();
        $this->assertEquals(2, count($blocks));

        $this->assertEquals('$foo = \'123\';', $blocks['setup']);
    }

    /**
     * @test
     * @expectedException PhpSpock\ParseException
     */
    public function badBlockOrder1()
    {
        $spec = function() {
            /**
             * @var $sym
             * @var $result
             * @var $foo
             */

            then:
            $foo == $sym;

            when:
            $foo .= $sym;
         };

        $result = $this->parser->parse($spec);
    }

    /**
     * @test
     */
    public function parseSpeckWitOneLineComments()
    {
        $spec = function() {
            /**
             * @var $sym
             * @var $result
             * @var $foo
             */

            // hofdasoihofsa
            // fasdas

            setup:
            $a = 1;

            when:
            $foo .= $sym;

            then:
            $foo == $sym;

         };

        $result = $this->parser->parse($spec);
    }

    /**
     * @test
     * @expectedException PhpSpock\ParseException
     */
    public function parseEmptyTest()
    {
        $spec = function() {
            
         };

        $result = $this->parser->parse($spec);
    }



    /**
     * @test
     * @expectedException PhpSpock\ParseException
     */
    public function parseSpecWithUnknownBlock()
    {
        $spec = function() {
            /**
             * @var $sym
             * @var $result
             */

            setup:
            $foo = '123';

            trololo:
            $foo .= $sym;

            when:
            $foo .= $sym;
         };

        $result = $this->parser->parse($spec);
    }
}
